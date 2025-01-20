<?php

namespace MediaWiki\Extension\NotifyMe\Tests;

use MediaWiki\Extension\NotifyMe\ISubscriberProvider;
use MediaWiki\Extension\NotifyMe\SubscriberManager;
use MediaWiki\User\User;
use MediaWiki\User\UserOptionsManager;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\INotificationEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\NotifyMe\SubscriberManager
 */
class SubscriberManagerTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\NotifyMe\SubscriberManager::getSubscribers
	 * @dataProvider getProviders
	 */
	public function testGetSubscribers( $providers, $expected, INotificationEvent $event ) {
		$userOptionsLookupMock = $this->createMock( UserOptionsManager::class );
		$userOptionsLookupMock->method( 'getOption' )->willReturn( json_encode( [
			'type' => 'auto',
			'channels' => [ 'web', 'email' ],
			'channel-configuration' => []
		] ) );
		$manager = new SubscriberManager( $providers );
		$channelMock = $this->createMock( IChannel::class );
		$channelMock->method( 'getKey' )->willReturn( 'web' );
		$this->assertSame(
			$expected,
			$manager->getSubscribers(
				$event,
				$channelMock
			)
		);
	}

	/**
	 * @return array[]
	 */
	public function getProviders(): array {
		$agent = $this->createMock( User::class );
		$agent->method( 'isRegistered' )->willReturn( true );
		$agent->method( 'getId' )->willReturn( 10 );
		$agent->method( 'getName' )->willReturn( 'NotificationAgent' );

		$existingUser1 = $this->createMock( User::class );
		$existingUser1->method( 'isRegistered' )->willReturn( true );
		$existingUser1->method( 'getId' )->willReturn( 1 );
		$existingUser1->method( 'getName' )->willReturn( 'TestNotificationUser' );
		$existingUser2 = $this->createMock( User::class );
		$existingUser2->method( 'isRegistered' )->willReturn( true );
		$existingUser2->method( 'getId' )->willReturn( 2 );
		$existingUser2->method( 'getName' )->willReturn( 'TestNotificationUser2' );
		$nonExistingUser = $this->createMock( User::class );
		$nonExistingUser->method( 'isRegistered' )->willReturn( false );
		$nonExistingUser->method( 'getId' )->willReturn( 0 );
		$nonExistingUser->method( 'getName' )->willReturn( 'NonExistingUser' );

		return [
			[
				[
					'provider1' => $this->getProviderMock( [ $agent, $existingUser1, $existingUser2 ] ),
					'provider2' => $this->getProviderMock( [ $existingUser1, $agent, $nonExistingUser ] ),
				],
				[
					[
						'user' => $existingUser1,
						'providers' => [ 'provider1', 'provider2' ],
					],
					[
						'user' => $existingUser2,
						'providers' => [ 'provider1' ],
					],
				],
				$this->getEventMock( $agent, null ),
			],
			[
				[
					'provider1' => $this->getProviderMock( [ $existingUser1, $existingUser2 ] ),
					'provider2' => $this->getProviderMock( [ $existingUser1, $nonExistingUser, $agent ] ),
				],
				[
					[
						'user' => $existingUser1,
						'providers' => [ 'provider1', 'provider2' ],
					],
				],
				$this->getEventMock( $agent, [ $existingUser1 ] ),
			]
		];
	}

	/**
	 * @param User $agent
	 * @param array|null $presetSubscribers
	 *
	 * @return INotificationEvent
	 */
	protected function getEventMock( User $agent, ?array $presetSubscribers ): INotificationEvent {
		$eventMock = $this->createMock( INotificationEvent::class );
		$eventMock->method( 'getPresetSubscribers' )->willReturn( $presetSubscribers );
		$eventMock->method( 'getAgent' )->willReturn( $agent );

		return $eventMock;
	}

	/**
	 * @param User[] $users
	 *
	 * @return ISubscriberProvider|ISubscriberProvider
	 */
	protected function getProviderMock( $users ) {
		$provider = $this->createMock( ISubscriberProvider::class );
		$provider->method( 'getSubscribers' )->willReturn( $users );
		return $provider;
	}

	/**
	 * @return array
	 */
	protected function getChannels() {
		// Make two IChannel mocks that will return different getKeys
		$channel1 = $this->createMock( IChannel::class );
		$channel1->method( 'getKey' )->willReturn( 'channel1' );
		$channel2 = $this->createMock( IChannel::class );
		$channel2->method( 'getKey' )->willReturn( 'channel2' );
		return [ $channel1, $channel2 ];
	}
}
