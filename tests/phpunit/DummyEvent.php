<?php

namespace MediaWiki\Extension\NotifyMe\Tests;

use DateTime;
use MediaWiki\Language\RawMessage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\GroupableEvent;
use MWStake\MediaWiki\Component\Events\INotificationEvent;

class DummyEvent implements INotificationEvent, GroupableEvent {

	/** @var UserIdentity */
	protected $agent;
	/** @var DateTime|null */
	protected $time;
	/** @var int */
	protected $id;
	/** @var string */
	protected $key;

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

	/**
	 * @return array
	 */
	public function __serialize(): array {
		return [
			'id' => $this->id,
			'key' => $this->key,
			'time' => $this->time,
		];
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function __unserialize( array $data ): void {
		$this->id = $data['id'];
		$this->key = $data['key'];
		$this->time = $data['time'];
	}
}
