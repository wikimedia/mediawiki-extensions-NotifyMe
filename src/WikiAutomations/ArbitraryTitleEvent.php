<?php

namespace MediaWiki\Extension\NotifyMe\WikiAutomations;

use MediaWiki\Language\RawMessage;
use MediaWiki\Message\Message;
use MediaWiki\Page\PageIdentity;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\EventLink;
use MWStake\MediaWiki\Component\Events\PriorityEvent;
use MWStake\MediaWiki\Component\Events\TitleEvent;

class ArbitraryTitleEvent extends TitleEvent implements PriorityEvent {

	/**
	 * @param UserIdentity $agent
	 * @param PageIdentity $title
	 * @param string $message
	 * @param string $subject
	 * @param array $targetUsers
	 */
	public function __construct(
		UserIdentity $agent,
		PageIdentity $title,
		private readonly string $message,
		private string $subject,
		private readonly array $targetUsers
	) {
		parent::__construct( $agent, $title );
	}

	/**
	 * @return string
	 */
	public function getKey(): string {
		return 'arbitrary-title-message-event';
	}

	public function getKeyMessage(): Message {
		if ( !$this->subject ) {
			$this->subject = 'notifyme-arbitrary-event-default-subject';
		}
		$subject = Message::newFromKey( $this->subject );
		if ( !$subject->exists() ) {
			$subject = new RawMessage( $this->subject );
		}

		return $subject;
	}

	/**
	 * @param IChannel $forChannel
	 * @return Message
	 */
	public function getMessage( IChannel $forChannel ): Message {
		$msg = Message::newFromKey( $this->message );
		if ( $msg->exists() ) {
			return $msg->params(
				$this->getTitleAnchor( $this->getTitle(), $forChannel )
			);
		}
		return ( new RawMessage( $this->message ) )->params(
			$this->getTitleAnchor( $this->getTitle(), $forChannel )
		);
	}

	/**
	 * @param IChannel $forChannel
	 * @return array|EventLink[]
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
