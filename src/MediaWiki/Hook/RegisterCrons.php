<?php

namespace MediaWiki\Extension\NotifyMe\MediaWiki\Hook;

use MediaWiki\Extension\NotifyMe\Process\SendDailyDigest;
use MediaWiki\Extension\NotifyMe\Process\SendWeeklyDigest;
use MediaWiki\Extension\NotifyMe\Process\UpdateEventProcessStatus;
use MediaWiki\Hook\MediaWikiServicesHook;
use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\ProcessManager\ManagedProcess;
use MWStake\MediaWiki\Component\WikiCron\WikiCronManager;

class RegisterCrons implements MediaWikiServicesHook {

	/**
	 * @param MediaWikiServices $services
	 * @return void
	 */
	public function onMediaWikiServices( $services ) {
		/** @var WikiCronManager $cronManager */
		$cronManager = $services->getService( 'MWStake.WikiCronManager' );
		$cronManager->registerCron( 'notifyme-send-daily', '0 7 * * *', new ManagedProcess( [
			'send-daily' => [
				'class' => SendDailyDigest::class,
				'services' => [ 'NotifyMe.Store', 'NotifyMe.ChannelFactory', 'NotifyMe.Logger' ],
			]
		] ) );
		$cronManager->registerCron( 'notifyme-send-weekly', '0 7 * * 1', new ManagedProcess( [
			'send-daily' => [
				'class' => SendWeeklyDigest::class,
				'services' => [ 'NotifyMe.Store', 'NotifyMe.ChannelFactory', 'NotifyMe.Logger' ],
			]
		] ) );
		$cronManager->registerCron( 'notifyme-update-event-process-status', '*/30 * * * *', new ManagedProcess( [
			'update-event-process-status' => [
				'class' => UpdateEventProcessStatus::class,
				'services' => [ 'NotifyMe.Store', 'ProcessManager', 'NotifyMe.Logger' ],
			]
		] ) );
	}
}
