<?php

namespace MediaWiki\Extension\NotifyMe\WikiAutomations;

use MediaWiki\Language\RawMessage;
use MediaWiki\Message\Message;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\NotificationEvent;
use MWStake\MediaWiki\Component\Events\PriorityEvent;

class ArbitraryEvent extends NotificationEvent implements PriorityEvent {

	/**
	 * @param UserIdentity $agent
	 * @param string $message
	 * @param array $targetUsers
	 */
	public function __construct( UserIdentity $agent, private string $message, private readonly array $targetUsers ) {
		parent::__construct( $agent );
	}

	/**
	 * @return string
	 */
	public function getKey(): string {
		return 'arbitrary-message-event';
	}

	/**
	 * @param IChannel $forChannel
	 * @return Message
	 */
	public function getMessage( IChannel $forChannel ): Message {
		$msg = Message::newFromKey( $this->message );
		if ( $msg->exists() ) {
			return $msg;
		}
		return new RawMessage( $this->message );
	}

	/**
	 * @param IChannel $forChannel
	 * @return array|\MWStake\MediaWiki\Component\Events\EventLink[]
	 */
	public function getLinks( IChannel $forChannel ): array {
		return [];
	}

	/**
	 * @return array|null
	 */
	public function getPresetSubscribers(): ?array {
		return $this->targetUsers;
	}
}
