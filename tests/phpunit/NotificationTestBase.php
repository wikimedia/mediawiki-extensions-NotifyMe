<?php

namespace MediaWiki\Extension\NotifyMe\Tests;

use MediaWiki\Extension\NotifyMe\Storage\NotificationStore;
use MWStake\MediaWiki\Component\Events\INotificationEvent;
use MWStake\MediaWiki\Component\Events\Notification;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use User;

class NotificationTestBase extends TestCase {
	/**
	 * @return NotificationStore
	 */
	protected function getNewStoreMock() {
		$store = new DummyNotificationStore();
		return $this->getMockForStore( $store );
	}

	/**
	 * @param DummyNotificationStore $store
	 *
	 * @return NotificationStore
	 */
	protected function getMockForStore( DummyNotificationStore $store ) {
		return $this->getStoreMock( $store );
	}

	/**
	 * @param DummyNotificationStore $testStore
	 *
	 * @return NotificationStore
	 */
	private function getStoreMock( DummyNotificationStore $testStore ) {
		$mock = $this->createMock( NotificationStore::class );
		$mock->method( 'getEvent' )->willReturnCallback( static function ( int $id ) use ( $testStore ) {
			return $testStore->getEvent( $id );
		} );
		$mock->method( 'persist' )->willReturnCallback(
			static function ( Notification $notification ) use ( $testStore ) {
				$testStore->persist( $notification );
			} );
		$mock->method( 'forChannel' )->willReturnCallback( static function ( $channel ) use ( $testStore ) {
			$testStore->forChannel( $channel );
		} );
		$mock->method( 'forUser' )->willReturnCallback( static function ( User $user ) use ( $testStore ) {
			$testStore->forUser( $user );
		} );
		$mock->method( 'pending' )->willReturnCallback( static function () use ( $testStore ) {
			$testStore->pending();
		} );
		$mock->method( 'delivered' )->willReturnCallback( static function () use ( $testStore ) {
			$testStore->delivered();
		} );
		$mock->method( 'query' )->willReturnCallback( static function ( $conds = [] ) use ( $testStore ) {
			return $testStore->query();
		} );
		$mock->method( 'persistEvent' )->willReturnCallback(
			static function ( INotificationEvent $event ) use ( $testStore ) {
				return $testStore->persistEvent( $event );
			} );

		return $mock;
	}

	/**
	 * @param string|int $userId
	 *
	 * @return MockObject|User
	 */
	protected function getUserMock( $userId ) {
		$mock = $this->createMock( User::class );
		$mock->method( 'getId' )->willReturn( $userId );
		$mock->method( 'getName' )->willReturn( 'DemoUser' );

		return $mock;
	}
}
