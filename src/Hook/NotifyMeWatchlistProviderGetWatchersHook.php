<?php

namespace MediaWiki\Extension\NotifyMe\Hook;

use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\INotificationEvent;

interface NotifyMeWatchlistProviderGetWatchersHook {

	/**
	 * Get all users that are considered watching the page or event in question.
	 * Do not resolve to normal page watchers, this is only for special cases!
	 *
	 * @param INotificationEvent $event
	 * @param IChannel $channel
	 * @param array &$watchers
	 * @return void
	 */
	public function onNotifyMeWatchlistProviderGetWatchers(
		INotificationEvent $event, IChannel $channel, array &$watchers
	): void;
}
