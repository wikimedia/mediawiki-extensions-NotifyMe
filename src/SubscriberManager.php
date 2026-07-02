<?php

namespace MediaWiki\Extension\NotifyMe;

use InvalidArgumentException;
use MediaWiki\Extension\NotifyMe\Channel\WebChannel;
use MediaWiki\Extension\NotifyMe\SubscriberProvider\PersonalProvider;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\ForcedEvent;
use MWStake\MediaWiki\Component\Events\INotificationEvent;
use MWStake\MediaWiki\Component\Events\NotifyAgentEvent;

final class SubscriberManager {
	/** @var ISubscriberProvider[] */
	private $providers;
	/** @var BucketProvider */
	private BucketProvider $bucketProvider;

	/**
	 * @param array $providers
	 * @param BucketProvider $bucketProvider
	 */
	public function __construct( array $providers, BucketProvider $bucketProvider ) {
		$this->providers = $providers;
		$this->bucketProvider = $bucketProvider;
	}

	/**
	 * @param INotificationEvent $event
	 * @param IChannel $channel
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getSubscribers( INotificationEvent $event, IChannel $channel ): array {
		$logger = LoggerFactory::getInstance( 'notifications' );
		$logger->info( "Getting subscribers for event {$event->getKey()} and channel {$channel->getKey()}" );
		$subscribers = [];
		if ( $event instanceof ForcedEvent && $channel instanceof WebChannel ) {
			// Only send mandatory notifications to "primary" channel, which is "web"
			// Not generic though
			return $this->getMandatorySubscribers( $event );
		}
		$buckets = $this->bucketProvider->getEventBuckets( $event );
		if ( in_array( 'personal', $buckets ) ) {
			// Personal events must contain target users and they are unsubscribable, so
			// we don't want to run whole expensive subscriber provider logic, because we already know the target users
			$subscribers = $this->merge( [], $event->getPresetSubscribers(), 'personal' );
		} else {
			$logger->info( "Getting subscribers for event {$event->getKey()} and channel {$channel->getKey()}" );
			foreach ( $this->providers as $key => $provider ) {
				$logger->info( "Getting subscribers from provider {$key}" );
				$subscribers = $this->merge( $subscribers, $provider->getSubscribers( $event, $channel ), $key );
			}
			if ( !empty( $subscribers ) ) {
				if ( $event->getPresetSubscribers() !== null ) {
					// On self-subscribing events, group of people to notify is already set,
					// but we still need to filter out any user that is not subscribed
					$subscribers = $this->fromSubset( $subscribers, $event->getPresetSubscribers() );
				}
			}
		}

		if ( !( $event instanceof NotifyAgentEvent ) ) {
			$subscribers = $this->removeAgent( $subscribers, $event );
		}
		return array_values( $subscribers );
	}

	/**
	 * For mandatory events, we notify all users that event has pre-set,
	 * even if user opted-out of receiving notifications
	 * @param ForcedEvent $event
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function getMandatorySubscribers( ForcedEvent $event ): array {
		$subscribers = $event->getPresetSubscribers();
		if ( empty( $subscribers ) ) {
			throw new \Exception( 'Mandatory events must provide preset subscribers' );
		}
		$mandatoryUsers = [];
		foreach ( $subscribers as $user ) {
			$mandatoryUsers[$user->getId()] = [
				'user' => $user,
				'providers' => []
			];
		}
		return $this->removeAgent( $mandatoryUsers, $event );
	}

	/**
	 * @param string $providerName
	 *
	 * @return ISubscriberProvider
	 */
	public function getProvider( string $providerName ): ISubscriberProvider {
		if ( $providerName === 'personal' ) {
			// Special case for personal notifications
			return new PersonalProvider();
		}
		if ( isset( $this->providers[$providerName] ) ) {
			return $this->providers[$providerName];
		}
		throw new InvalidArgumentException( "Provider $providerName not found" );
	}

	/**
	 * Add new users to the list of subscribers
	 *
	 * @param array $a
	 * @param array $b
	 * @param string $providerName
	 *
	 * @return array
	 */
	private function merge( array $a, array $b, string $providerName ): array {
		foreach ( $b as $user ) {
			if ( !$user instanceof UserIdentity || !$user->isRegistered() ) {
				continue;
			}
			foreach ( $a as $userId => &$userData ) {
				if ( $user->getId() === $userId ) {
					$userData['providers'][] = $providerName;
					continue 2;
				}
			}
			$a[$user->getId()] = [
				'user' => $user,
				'providers' => [ $providerName ],
			];
		}

		return $a;
	}

	/**
	 * @param array $users
	 * @param INotificationEvent $event
	 *
	 * @return array
	 */
	private function removeAgent( array $users, INotificationEvent $event ): array {
		$agent = $event->getAgent();
		return array_filter( $users, static function ( $userId ) use ( $agent ) {
			return $userId !== $agent->getId();
		}, ARRAY_FILTER_USE_KEY );
	}

	/**
	 * @param array $users
	 * @param array $subset
	 *
	 * @return array
	 */
	private function fromSubset( array $users, array $subset ): array {
		$subset = array_map( static function ( User $user ) {
			return $user->getId();
		}, $subset );
		$subset = array_flip( $subset );
		return array_filter( $users, static function ( $userId ) use ( $subset ) {
			return isset( $subset[$userId] );
		}, ARRAY_FILTER_USE_KEY );
	}
}
