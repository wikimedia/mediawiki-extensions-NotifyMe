<?php

namespace MediaWiki\Extension\NotifyMe\Tests;

use DateTime;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\Delivery\NotificationStatus;
use MWStake\MediaWiki\Component\Events\INotificationEvent;
use MWStake\MediaWiki\Component\Events\Notification;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Title;

class DummyNotificationStore extends TestCase {
	/** @var int */
	private $maxEventId = 0;
	/** @var int */
	private $maxNotifId = 0;
	/** @var array */
	private $tempSubset = [];
	/** @var array */
	private $events = [];
	/** @var array */
	protected $notifications = [];

	/**
	 * @param INotificationEvent $event
	 *
	 * @return int
	 */
	public function persistEvent( INotificationEvent $event ) {
		$this->events[$event->getId()] = $event;
		return $event->getId();
	}

	/**
	 * @param int $id
	 *
	 * @return array|null
	 */
	public function getEvent( int $id ) {
		return [ $this->events[$id] ] ?? null;
	}

	/**
	 * @param Notification $notification
	 *
	 * @return void
	 */
	public function persist( Notification $notification ) {
		if ( !$notification->getId() ) {
			$this->maxNotifId++;
			$notification->setId( $this->maxNotifId );
		}
		$this->notifications[$notification->getId()] = $notification;
	}

	/**
	 * @param IChannel|string $channel
	 *
	 * @return $this
	 */
	public function forChannel( $channel ) {
		$key = $channel instanceof IChannel ? $channel->getKey() : $channel;
		$mainSet = !empty( $this->tempSubset ) ? $this->tempSubset : $this->notifications;
		foreach ( $mainSet as $id => $notification ) {
			if ( $notification->getChannel()->getKey() !== $key ) {
				unset( $mainSet[$id] );
			}
		}
		$this->tempSubset = $mainSet;

		return $this;
	}

	/**
	 * @param \User $user
	 *
	 * @return $this
	 */
	public function forUser( \User $user ) {
		$mainSet = !empty( $this->tempSubset ) ? $this->tempSubset : $this->notifications;
		foreach ( $mainSet as $id => $notification ) {
			if ( $notification->getTargetuser()->getId() === $user->getId() ) {
				unset( $mainSet[$id] );
			}
		}
		$this->tempSubset = $mainSet;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function pending() {
		$mainSet = !empty( $this->tempSubset ) ? $this->tempSubset : $this->notifications;
		foreach ( $mainSet as $id => $notification ) {
			if ( $notification->getStatus()->isCompleted() ) {
				unset( $mainSet[$id] );
			}
		}
		$this->tempSubset = $mainSet;

		return $this;
	}

	/**
	 * @return $this
	 */
	public function delivered() {
		$mainSet = !empty( $this->tempSubset ) ? $this->tempSubset : $this->notifications;
		foreach ( $mainSet as $id => $notification ) {
			if ( !$notification->getStatus()->isCompleted() ) {
				unset( $mainSet[$id] );
			}
		}
		$this->tempSubset = $mainSet;

		return $this;
	}

	/**
	 * @return array
	 */
	public function query() {
		$data = $this->tempSubset;
		$this->tempSubset = [];

		return array_values( $data );
	}

	/**
	 * @return array
	 */
	public function getAll() {
		return array_values( $this->notifications );
	}

	/**
	 * @return void
	 */
	public function generateDummyData() {
		$events = [ 'edit', 'delete', 'move', 'group-assign' ];
		$channels = [ 'web', 'email', 'external' ];
		$titles = [ null, Title::newMainPage(), Title::newFromText( 'Dummy' ) ];
		for ( $i = 0; $i < 100; $i++ ) {
			$notif = $this->generateNotification(
				$this->generateEvent(
					$events[rand( 0, 3 )], rand( 1, 5 ),
					$this->getRandomTime()->format( 'YmdHis' ), $titles[rand( 0, 2 )]
				),
				$channels[rand( 0, 2 )],
				rand( 5, 10 ),
				$this->randomStatus()
			);
			$this->persistEvent( $notif->getEvent() );
			$this->persist( $notif );
		}
	}

	/**
	 * @param array $data
	 *
	 * @return void
	 */
	public function insertNotifications( $data ) {
		foreach ( $data as $notif ) {
			$event = $this->generateEvent( ...$notif['event'] );
			$notif = $this->generateNotification(
				$event,
				$notif['channel'] ?? 'web',
				$notif['targetUser'] ?? 1,
				$notif['status'] ?? $this->randomStatus()
			);
			$this->persistEvent( $notif->getEvent() );
			$this->persist( $notif );
		}
	}

	/**
	 * @param INotificationEvent $event
	 * @param string $channel
	 * @param int $targetUser
	 * @param array $status
	 *
	 * @return Notification
	 */
	protected function generateNotification(
		INotificationEvent $event, $channel, $targetUser, $status
	): Notification {
		$targetUserMock = $this->getUserMock( $targetUser );
		$channelMock = $this->createMock( IChannel::class );
		$channelMock->method( 'getKey' )->willReturn( $channel );

		return new Notification(
			$event,
			$targetUserMock,
			$channelMock,
			new NotificationStatus(
				$status['status'], $status['error'] ?? '', $this->getStatusTime( $status['time'] )
			),
			[]
		);
	}

	/**
	 * @param string $key
	 * @param int $agentId
	 * @param string $time YmdHis
	 * @param string|null $title
	 *
	 * @return INotificationEvent
	 */
	protected function generateEvent( $key, $agentId, $time, $title = null ) {
		$this->maxEventId++;
		$class = $title ? DummyTitleEvent::class : DummyEvent::class;

		$instance = new $class(
			$this->maxEventId,
			$this->getUserMock( $agentId ),
			$key,
			$time instanceof DateTime ? $time : DateTime::createFromFormat( 'YmdHis', $time )
		);

		if ( $title ) {
			$instance->setTitle( Title::newFromText( $title ) );
		}

		return $instance;
	}

	/**
	 * @param string $time
	 *
	 * @return DateTime|null
	 */
	protected function getStatusTime( $time ) {
		if ( $time ) {
			return DateTime::createFromFormat( 'YmdHis', $time );
		}
		return null;
	}

	/**
	 * @param string|int $userId
	 *
	 * @return MockObject|\User
	 */
	private function getUserMock( $userId ) {
		$mock = $this->createMock( \User::class );
		$mock->method( 'getId' )->willReturn( $userId );
		$mock->mId = $userId;
		$mock->method( 'getName' )->willReturn( 'DemoUser' );
		$mock->method( 'getUserPage' )->willReturn( $this->createMock( Title::class ) );

		return $mock;
	}

	/**
	 * @return DateTime
	 */
	private function getRandomTime() {
		// Generate random time/date in past 2 months
		$now = new DateTime();
		$now->modify( '-2 month' );
		$now->modify( '+' . rand( 0, 60 ) . ' day' );
		$now->modify( '+' . rand( 0, 24 ) . ' hour' );
		$now->modify( '+' . rand( 0, 60 ) . ' minute' );
		$now->modify( '+' . rand( 0, 60 ) . ' second' );

		return $now;
	}

	/**
	 * @return array
	 */
	private function randomStatus() {
		// Make NotificationStatus with random pending/completed
		$status = rand( 0, 1 ) ? 'pending' : 'completed';
		$error = rand( 0, 1 ) ? 'error' : '';
		$time = rand( 0, 1 ) ? $this->getRandomTime()->format( 'YmdHis' ) : null;

		return [ 'status' => $status, 'error' => $error, 'time' => $time ];
	}

	/**
	 * @param string $title
	 *
	 * @return MockObject|Title
	 */
	private function getTitleMock( string $title ) {
		$mock = $this->createMock( Title::class );
		$mock->method( 'getPrefixedText' )->willReturn( $title );
		$mock->method( 'getDBkey' )->willReturn( $title );
		$mock->method( 'getArticleID' )->willReturn( 1 );
		$mock->method( 'getNamespace' )->willReturn( NS_MAIN );

		return $mock;
	}
}
