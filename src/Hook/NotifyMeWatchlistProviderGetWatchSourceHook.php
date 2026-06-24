<?php

namespace MediaWiki\Extension\NotifyMe\Hook;

use MediaWiki\Message\Message;
use MWStake\MediaWiki\Component\Events\Notification;

interface NotifyMeWatchlistProviderGetWatchSourceHook {

	/**
	 * In case hook handler is the one who provided the "watcher",
	 * explain why the user is considered watching the page or event in question.
	 * This is shown to the user in the text of notification
	 *
	 * @param Notification $notification
	 * @param Message &$description
	 * @return mixed
	 */
	public function onNotifyMeWatchlistProviderGetWatchSource( Notification $notification, Message &$description );
}
