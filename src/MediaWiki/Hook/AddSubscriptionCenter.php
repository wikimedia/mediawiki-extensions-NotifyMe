<?php

namespace MediaWiki\Extension\NotifyMe\MediaWiki\Hook;

use MediaWiki\Extension\NotifyMe\MediaWiki\Html\NotificationsSubscriptionsElement;
use MediaWiki\Extension\NotifyMe\SubscriberManager;
use MediaWiki\Extension\NotifyMe\SubscriberProvider\WatchlistSubscriberProvider;
use MediaWiki\Extension\NotifyMe\SubscriptionConfigurator;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\User\Hook\UserGetDefaultOptionsHook;
use MediaWiki\User\User;

class AddSubscriptionCenter implements GetPreferencesHook, UserGetDefaultOptionsHook {
	/** @var SubscriptionConfigurator */
	private $configurator;

	/** @var SubscriberManager */
	private $manager;

	/**
	 * @param SubscriptionConfigurator $configurator
	 * @param SubscriberManager $manger
	 */
	public function __construct( SubscriptionConfigurator $configurator, SubscriberManager $manger ) {
		$this->configurator = $configurator;
		$this->manager = $manger;
	}

	/**
	 * @param User $user
	 * @param array &$preferences
	 *
	 * @return bool|void
	 * @throws \Exception
	 */
	public function onGetPreferences( $user, &$preferences ) {
		/** @var WatchlistSubscriberProvider $watchlistProvider */
		$watchlistProvider = $this->manager->getProvider( 'watchlist-subscriptions' );
		$config = $this->configurator->getConfiguration( $user );
		$config = $watchlistProvider->modifyConfiguration( $config );

		HTMLForm::$typeMappings['notifications-subscriptions'] = NotificationsSubscriptionsElement::class;
		$preferences[$this->configurator::SUBSCRIPTION_PREF_KEY] = [
			'type' => 'notifications-subscriptions',
			'section' => 'notifications/subs',
			'rl-modules' => [ 'ext.notifyme.subscription-preferences' ],
			'value' => [
				'configuration' => $config,
				'bucketData' => $this->configurator->getBucketData(),
				'eventData' => $this->configurator->getEventData(),
				'channelLabels' => $this->configurator->getChannelLabels(),
			]
		];
	}

	/**
	 * @param array &$defaultOptions
	 *
	 * @return void
	 */
	public function onUserGetDefaultOptions( &$defaultOptions ) {
		$defaultOptions[$this->configurator::SUBSCRIPTION_PREF_KEY] = $this->configurator->getDefaultValue();
	}
}
