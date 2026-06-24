<?php

namespace MediaWiki\Extension\NotifyMe\SubscriberProvider\DerivedWatches;

use MediaWiki\Extension\NotifyMe\Hook\NotifyMeWatchlistProviderGetWatchersHook;
use MediaWiki\Extension\NotifyMe\Hook\NotifyMeWatchlistProviderGetWatchSourceHook;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use MediaWiki\User\UserFactory;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\INotificationEvent;
use MWStake\MediaWiki\Component\Events\ITitleEvent;
use MWStake\MediaWiki\Component\Events\Notification;
use Wikimedia\Rdbms\ILoadBalancer;

class NamespaceWatch implements NotifyMeWatchlistProviderGetWatchersHook, NotifyMeWatchlistProviderGetWatchSourceHook {

	/** @var array */
	private array $namespaceWatchers = [];

	/**
	 * @param ILoadBalancer $lb
	 * @param UserFactory $userFactory
	 */
	public function __construct(
		private readonly ILoadBalancer $lb,
		private readonly UserFactory $userFactory
	) {
	}

	public function onNotifyMeWatchlistProviderGetWatchSource( Notification $notification, Message &$description ) {
		if ( !( $notification->getEvent() instanceof ITitleEvent ) ) {
			return;
		}
		$title = $notification->getEvent()->getTitle();
		if ( !$title->exists() ) {
			return;
		}
		if ( !isset( $this->categoryWatchers[$title->getNamespace()] ) ) {
			$this->namespaceWatchers[$title->getNamespace()] = $this->getNamespaceWatchers( $title );
		}

		foreach ( $this->namespaceWatchers[$title->getNamespace()] as $watcher ) {
			if ( $watcher->getId() === $notification->getTargetUser()->getId() ) {
				$description = Message::newFromKey( 'notifyme-namespace-subscription-description' );
				return;
			}
		}
	}

	/**
	 * @param INotificationEvent $event
	 * @param IChannel $channel
	 * @param array &$watchers
	 * @return void
	 */
	public function onNotifyMeWatchlistProviderGetWatchers(
		INotificationEvent $event, IChannel $channel, array &$watchers
	): void {
		if ( !( $event instanceof ITitleEvent ) ) {
			return;
		}
		$title = $event->getTitle();

		$this->namespaceWatchers[$title->getNamespace()] = $this->getNamespaceWatchers( $title );
		$watchers = array_merge( $watchers, $this->namespaceWatchers[$title->getNamespace()] );
	}

	/**
	 * @param Title $title
	 * @return array
	 */
	private function getNamespaceWatchers( Title $title ): array {
		$db = $this->lb->getConnection( DB_REPLICA );

		$res = $db->newSelectQueryBuilder()
			->from( 'watchlist', 'wl' )
			->select( 'wl_user' )
			->where( [
				'wl_namespace' => $title->getNamespace(),
				'wl_title' => '--*--'
			] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$users = [];
		foreach ( $res as $row ) {
			$users[] = $this->userFactory->newFromId( $row->wl_user );
		}
		return $users;
	}
}
