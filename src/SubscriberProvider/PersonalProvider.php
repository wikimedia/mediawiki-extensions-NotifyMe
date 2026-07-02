<?php

namespace MediaWiki\Extension\NotifyMe\SubscriberProvider;

use Exception;
use MediaWiki\Extension\NotifyMe\ISubscriberProvider;
use MediaWiki\Message\Message;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\INotificationEvent;
use MWStake\MediaWiki\Component\Events\Notification;

/**
 * This is a non-standard provider. It's not registered into the registry, but used
 * exclusively for bucket "personal" notifications. It's a "special case", because in case
 * of personal notifications, we don't want to run whole
 * expensive subscriber provider logic, because we already know the target users
 */
class PersonalProvider implements ISubscriberProvider {

	/**
	 * @return string
	 */
	public function getKey(): string {
		return 'personal';
	}

	/**
	 * @param INotificationEvent $event
	 * @param IChannel $channel
	 *
	 * @return array|UserIdentity[]
	 * @throws Exception
	 */
	public function getSubscribers( INotificationEvent $event, IChannel $channel ): array {
		return $event->getPresetSubscribers() ?? [];
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription( Notification $notification ): Message {
		return Message::newFromKey( 'notifyme-subscriber-provider-personal-desc' );
	}

	/**
	 * @inheritDoc
	 */
	public function getConfigurationLink(): ?string {
		return null;
	}
}
