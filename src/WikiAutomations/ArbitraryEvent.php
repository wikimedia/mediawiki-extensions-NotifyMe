<?php

namespace MediaWiki\Extension\NotifyMe\WikiAutomations;

use MediaWiki\Language\RawMessage;
use MediaWiki\Message\Message;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\NotificationEvent;
use MWStake\MediaWiki\Component\Events\PriorityEvent;

class ArbitraryEvent extends NotificationEvent implements PriorityEvent {

	public function __construct( UserIdentity $agent, private string $message, private readonly array $targetUsers ) {
		parent::__construct( $agent );
	}

	/**
	 * @return string
	 */
	public function getKey(): string {
		return 'arbitrary-message-event';
	}

	public function getMessage( IChannel $forChannel ): Message {
		return new RawMessage( $this->message );
	}

	public function getLinks( IChannel $forChannel ): array {
		return [];
	}

	public function getPresetSubscribers(): ?array {
		return $this->targetUsers;
	}
}
