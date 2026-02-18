<?php

namespace MediaWiki\Extension\NotifyMe;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Title\TitleFactory;
use MWStake\MediaWiki\Component\Events\Notification;

class ForeignNotificationFactory {

	/**
	 * @param HookContainer $hookContainer
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		private readonly HookContainer $hookContainer,
		private readonly TitleFactory $titleFactory
	) {
	}

	/**
	 * @param Notification $notification
	 * @param string $sourceWikiId
	 * @return ForeignNotification
	 */
	public function createFromNative( Notification $notification, string $sourceWikiId ) {
		return new ForeignNotification(
			$notification->getEvent(),
			$notification->getTargetUser(),
			$notification->getChannel(),
			$notification->getStatus(),
			$notification->getSourceProviders(),
			$sourceWikiId,
			$this->hookContainer,
			$this->titleFactory
		);
	}
}
