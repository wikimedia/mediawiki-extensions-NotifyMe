<?php

namespace MediaWiki\Extension\NotifyMe\SubscriberProvider;

use Exception;
use MediaWiki\Extension\NotifyMe\BucketProvider;
use MediaWiki\Extension\NotifyMe\ISubscriberProvider;
use MediaWiki\Extension\NotifyMe\SubscriptionConfigurator;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Message\Message;
use MediaWiki\Page\PageIdentity;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\INotificationEvent;
use MWStake\MediaWiki\Component\Events\ITitleEvent;
use MWStake\MediaWiki\Component\Events\Notification;
use Wikimedia\Rdbms\LoadBalancer;

class WatchlistSubscriberProvider implements ISubscriberProvider {

	private \HashBagOStuff $pageWatcherCache;

	/**
	 * @param HookContainer $hookContainer
	 * @param UserFactory $userFactory
	 * @param LoadBalancer $lb
	 * @param SubscriptionConfigurator $configurator
	 * @param BucketProvider $bucketProvider
	 */
	public function __construct(
		private readonly HookContainer $hookContainer,
		private readonly UserFactory $userFactory,
		private readonly LoadBalancer $lb,
		private readonly SubscriptionConfigurator $configurator,
		private readonly BucketProvider $bucketProvider
	) {
		$this->pageWatcherCache = new \HashBagOStuff();
	}

	/**
	 * @return string
	 */
	public function getKey(): string {
		return 'watchlist-subscriptions';
	}

	/**
	 * @param INotificationEvent $event
	 * @param IChannel $channel
	 *
	 * @return array|UserIdentity[]
	 * @throws Exception
	 */
	public function getSubscribers( INotificationEvent $event, IChannel $channel ): array {
		$watchers = [];
		if ( !( $event instanceof ITitleEvent ) ) {
			// Maybe something can resolve watchers...
			$this->hookContainer->run(
				'NotifyMeWatchlistProviderGetWatchers', [ $event, $channel, &$watchers ]
			);
			return $this->resolveToSubscribers( $watchers, $event, $channel );
		}
		$watchers = $this->getWatchers( $event->getTitle() );
		$this->hookContainer->run(
			'NotifyMeWatchlistProviderGetWatchers', [ $event, $channel, &$watchers ]
		);

		return $this->resolveToSubscribers( $watchers, $event, $channel );
	}

	/**
	 * Filter out watches that are either not subscribed to the event or the channel
	 * @param array $watchers
	 * @param INotificationEvent $event
	 * @param IChannel $channel
	 * @return array
	 */
	private function resolveToSubscribers( array $watchers, INotificationEvent $event, IChannel $channel ): array {
		$valid = $this->validateWatchers( $watchers );
		$subscribers = [];
		foreach ( $valid as $user ) {
			if ( $this->isSubscribed( $user, $event, $channel ) ) {
				$subscribers[] = $user;
			}
		}
		return $subscribers;
	}

	/**
	 * @param UserIdentity $user
	 * @param INotificationEvent $event
	 * @param IChannel $channel
	 * @return bool
	 */
	private function isSubscribed( UserIdentity $user, INotificationEvent $event, IChannel $channel ): bool {
		try {
			if ( $this->bucketProvider->hasMandatoryBuckets( $event ) ) {
				// Buckets user cannot opt out of
				return true;
			}
			$config = $this->configurator->getConfiguration( $user );
			$delivery = $config['delivery'] ?? [];
			if ( $channel->getKey() !== 'web' && !in_array( $channel->getKey(), $delivery ) ) {
				return false;
			}
			$subscriptions = $config['subscriptions'] ?? [];
			// WARNING: Opt out only, if not opted out, its considered subscribed
			if ( isset( $subscriptions[$event->getKey()] ) && !$subscriptions[$event->getKey()] ) {
				return false;
			}
			return true;
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/**
	 * We need to enable all events by default, so that only ones user actually opted-out of are disabled.
	 *
	 * @param array $configuration
	 * @return array
	 */
	public function modifyConfiguration( array $configuration ): array {
		$subscriptions = $configuration['subscriptions'] ?? [];
		foreach ( $this->bucketProvider->getEventTypes() as $event ) {
			if ( !isset( $subscriptions[$event] ) ) {
				$subscriptions[$event] = true;
			}
		}
		$configuration['subscriptions'] = $subscriptions;
		return $configuration;
	}

	/**
	 * @param PageIdentity $page
	 * @return array
	 */
	private function getWatchers( PageIdentity $page ): array {
		$cacheKey = $this->pageWatcherCache->makeKey( 'watcher', $page->getNamespace(), $page->getDBkey() );
		if ( $this->pageWatcherCache->hasKey( $cacheKey ) ) {
			return $this->pageWatcherCache->get( $cacheKey );
		}
		$res = $this->lb->getConnection( DB_REPLICA )->newSelectQueryBuilder()
			->from( 'watchlist', 'wl' )
			->select( 'wl_user' )
			->where( [
				'wl_namespace' => $page->getNamespace(),
				'wl_title' => $page->getDBkey(),
			] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$users = [];
		foreach ( $res as $row ) {
			$users[] = $this->userFactory->newFromId( $row->wl_user );
		}

		$this->pageWatcherCache->set( $cacheKey, $users );
		return $users;
	}

	/**
	 * @param array $subscribers
	 * @return array
	 */
	private function validateWatchers( array $subscribers ): array {
		$validSubscribers = [];
		foreach ( $subscribers as $subscriber ) {
			if ( !( $subscriber instanceof User ) || !$subscriber->isRegistered() ) {
				continue;
			}
			if ( $subscriber->getBlock() !== null ) {
				continue;
			}

			$validSubscribers[$subscriber->getId()] = $subscriber;
		}
		return array_values( $validSubscribers );
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription( Notification $notification ): Message {
		if ( $this->bucketProvider->hasMandatoryBuckets( $notification->getEvent() ) ) {
			return Message::newFromKey( 'notifyme-subscriber-provider-mandatory-desc' );
		}
		$desc = Message::newFromKey( 'notifyme-subscriber-provider-watched-source-desc' );

		$watchers = $this->getWatchers( $notification->getEvent()->getTitle() );
		$target = $notification->getTargetUser();
		foreach ( $watchers as $watcher ) {
			if ( $watcher->getId() === $target->getId() ) {
				return $desc;
			}
		}
		$this->hookContainer->run( 'NotifyMeWatchlistProviderGetWatchSource', [ $notification, &$desc ] );
		return $desc;
	}

	/**
	 * @inheritDoc
	 */
	public function getConfigurationLink(): ?string {
		$sp = SpecialPage::getTitleFor( 'Preferences', false, 'mw-prefsection-notifications' );
		return $sp->getFullURL();
	}
}
