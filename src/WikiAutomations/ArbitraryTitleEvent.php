<?php

namespace MediaWiki\Extension\NotifyMe\WikiAutomations;

use MediaWiki\Language\RawMessage;
use MediaWiki\Message\Message;
use MediaWiki\Page\PageIdentity;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\PriorityEvent;
use MWStake\MediaWiki\Component\Events\TitleEvent;

class ArbitraryTitleEvent extends TitleEvent implements PriorityEvent {

	public function __construct(
		UserIdentity $agent, PageIdentity $title, private string $message, private readonly array $targetUsers
	) {
		parent::__construct( $agent, $title );
	}

	/**
	 * @return string
	 */
	public function getKey(): string {
		return 'arbitrary-title-message-event';
	}

	public function getMessage( IChannel $forChannel ): Message {
		return ( new RawMessage( $this->message ) )->params(
			$this->getTitleAnchor( $this->getTitle(), $forChannel )
		);
	}

	public function getLinks( IChannel $forChannel ): array {
		return [];
	}

	public function getPresetSubscribers(): ?array {
		return $this->targetUsers;
	}
}
