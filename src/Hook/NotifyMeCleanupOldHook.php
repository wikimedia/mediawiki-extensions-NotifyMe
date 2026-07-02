<?php

namespace MediaWiki\Extension\NotifyMe\Hook;

interface NotifyMeCleanupOldHook {

	/**
	 * @param array $expiredNotificationInstances
	 * @param string $forWiki
	 * @param \DateTimeImmutable $cutoff
	 * @return void
	 */
	public function onNotifyMeCleanupOld(
		array $expiredNotificationInstances, string $forWiki, \DateTimeImmutable $cutoff
	): void;
}
