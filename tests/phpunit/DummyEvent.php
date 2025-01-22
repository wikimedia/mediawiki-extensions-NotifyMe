<?php

namespace MediaWiki\Extension\NotifyMe\Tests;

use DateTime;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\GroupableEvent;
use MWStake\MediaWiki\Component\Events\INotificationEvent;
use RawMessage;

class DummyEvent implements INotificationEvent, GroupableEvent {
	/** @var UserIdentity */
	private $agent;
	/** @var DateTime|null */
	private $time;
	/** @var int */
	private $id;
	/** @var string */
	private $key;

	/**
	 * @param int $id
	 * @param UserIdentity $agent
	 * @param string $key
	 * @param DateTime|null $time
	 */
	public function __construct( $id, UserIdentity $agent, $key, ?DateTime $time = null ) {
		$this->id = $id;
		$this->agent = $agent;
		$this->key = $key;
		$this->time = $time;
	}

	/**
	 * @return string
	 */
	public function getKey(): string {
		return $this->key;
	}

	/**
	 * @return UserIdentity
	 */
	public function getAgent(): UserIdentity {
		return $this->agent;
	}

	/**
	 * @return DateTime
	 */
	public function getTime(): DateTime {
		return $this->time ?? new DateTime();
	}

	/**
	 * @return array
	 */
	public function getPresetSubscribers(): array {
		return [];
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage( IChannel $forChannel ): Message {
		return new RawMessage( 'dummy' );
	}

	/**
	 * @return string
	 */
	public function getIcon(): string {
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public function getLinks( IChannel $forChannel ): array {
		return [];
	}

	/**
	 * @return Message
	 */
	public function getKeyMessage(): Message {
		return new RawMessage( 'Dummy event' );
	}

	/**
	 * @inheritDoc
	 */
	public function getGroupMessage( int $count, IChannel $forChannel ): Message {
		return $this->getMessage( $forChannel );
	}

	/**
	 * @param DateTime $time
	 * @return void
	 */
	public function setTime( DateTime $time ): void {
		// NOOP
	}

	/**
	 * @return array
	 */
	public function hasPriorityOver(): array {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getLinksIntroMessage( IChannel $forChannel ): ?Message {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public static function getArgsForTesting(
		UserIdentity $agent, MediaWikiServices $services, array $extra = []
	): array {
		return [];
	}
}
