<?php

namespace MediaWiki\Extension\NotifyMe\Hook;

use MediaWiki\Extension\NotifyMe\Storage\WebNotificationQueryStore;

class CleanUpExpiredWebNotificationStoreEntries implements NotifyMeCleanupOldHook {

	/**
	 * @param WebNotificationQueryStore $notificationQueryStore
	 */
	public function __construct(
		private readonly WebNotificationQueryStore $notificationQueryStore
	) {
	}

	/**
	 * @param array $expiredNotificationInstances
	 * @param string $forWiki
	 * @param \DateTimeImmutable $cutoff
	 * @return void
	 */
	public function onNotifyMeCleanupOld(
		array $expiredNotificationInstances, string $forWiki, \DateTimeImmutable $cutoff
	): void {
		$this->notificationQueryStore->cleanUpNonExisting();
	}
}
