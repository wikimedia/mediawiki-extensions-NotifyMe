<?php

namespace MediaWiki\Extension\NotifyMe\Tests\SubscriberProvider;

use MediaWiki\Block\AbstractBlock;
use MediaWiki\Extension\NotifyMe\BucketProvider;
use MediaWiki\Extension\NotifyMe\EventProvider;
use MediaWiki\Extension\NotifyMe\SubscriberProvider\ManualProvider\SubscriptionSet\CategorySet;
use MediaWiki\Extension\NotifyMe\SubscriberProvider\ManualProvider\SubscriptionSet\NamespaceSet;
use MediaWiki\Extension\NotifyMe\SubscriberProvider\ManualProvider\SubscriptionSet\WatchlistSet;
use MediaWiki\Extension\NotifyMe\SubscriberProvider\ManualSubscriberProvider;
use MediaWiki\Extension\NotifyMe\SubscriptionConfigurator;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\INotificationEvent;
use MWStake\MediaWiki\Component\Events\ITitleEvent;
use PHPUnit\Framework\TestCase;
use WatchedItemStoreInterface;
use Wikimedia\ObjectFactory\ObjectFactory;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LoadBalancer;

class ManualSubscriberProviderTest extends TestCase {

	/**
	 * @param array $data
	 * @param array $expected
	 * @covers \MediaWiki\Extension\NotifyMe\SubscriberProvider\ManualSubscriberProvider::getSubscribers
	 * @dataProvider provideData
	 *
	 * @return void
	 */
	public function testGetSubscribers( array $data, array $expected ) {
		$provider = $this->getProvider( $data['model'], $data['subscriptions'], $data['eventConfiguration'] );

		$users = $provider->getSubscribers( $data['event'], $data['channel'] );
		$ids = array_map( static function ( $user ) {
			return $user->getId();
		}, $users );

		$this->assertSame( $expected, $ids );
	}

	public function provideData() {
		$config = [
			'a' => [ 'buckets' => [ 'no-opt-out' ] ],
			'b' => [ 'buckets' => [ 'bar' ] ],
			'c' => [ 'buckets' => [ 'foo' ] ],
			'd' => [ 'buckets' => [ 'foo', 'bar' ] ],
		];
		return [
			'should-skip' => [
				[
					'event' => $this->getEvent( 'c' ),
					'model' => 'invalid',
					'subscriptions' => [],
					'eventConfiguration' => $config,
					'channel' => $this->getChannel( 'c1' ),
				],
				[
					// No ids expected
				]
			],
			'no-opt-out' => [
				[
					'event' => $this->getEvent( 'a' ),
					'model' => 'manual',
					'subscriptions' => [],
					'eventConfiguration' => $config,
					'channel' => $this->getChannel( 'c1' ),
				],
				// All but the blocked user
				[ 1, 2, 4, 5 ]
			],
			'title-event-ns' => [
				[
					'event' => $this->getEvent( 'b', NS_HELP ),
					'model' => 'manual',
					'subscriptions' => [
						// Multi user return, with different NS values
						1 => [ [
							'setType' => 'ns',
							// String of NS_HELP
							'set' => [ 'ns' => '12' ],
							'bucket' => 'bar',
							'channels' => [ 'c1' ],
						] ],
						4 => [ [
							'setType' => 'ns',
							'set' => [ 'ns' => NS_HELP ],
							'bucket' => 'bar',
							'channels' => [ 'c1' ],
						] ],
						2 => [ [
								'setType' => 'ns',
								'set' => [ 'ns' => NS_HELP ],
								'bucket' => 'foo',
								'channels' => [ 'c1' ],
							], [
								'setType' => 'ns',
								'set' => [ 'ns' => NS_MAIN ],
								'bucket' => 'bar',
								'channels' => [ 'c1' ],
						] ],
					],
					'eventConfiguration' => $config,
					'channel' => $this->getChannel( 'c1' ),
				],
				[ 1, 4 ],
			],
			'title-event-cat' => [
				[
					'event' => $this->getEvent( 'c' ),
					'model' => 'manual',
					'subscriptions' => [
						1 => [ [
							'setType' => 'category',
							'set' => [ 'category' => 'Foo' ],
							'bucket' => 'bar',
							'channels' => [ 'c1' ],
						] ],
						2 => [ [
							'setType' => 'category',
							'set' => [ 'category' => 'Foo_bar' ],
							'bucket' => 'foo',
							'channels' => [ 'c1', 'c2', 'c3' ],
						] ],
						4 => [ [
							'setType' => 'category',
							'set' => [ 'category' => 'Foo' ],
							'bucket' => 'foo',
							'channels' => [ 'c1' ],
						], [
							'setType' => 'category',
							'set' => [ 'category' => 'Foo_bar' ],
							'bucket' => 'foo',
							'channels' => [ 'c2' ],
						] ]
					],
					'eventConfiguration' => $config,
					'channel' => $this->getChannel( 'c1' ),
				],
				[ 2 ],
			],
			'title-event-watchlist' => [
				[
					'event' => $this->getEvent( 'c', NS_MAIN, 'Watched' ),
					'model' => 'manual',
					'subscriptions' => [
						1 => [ [
							'setType' => 'watchlist',
							'bucket' => 'foo',
							'channels' => [ 'c1' ],
						] ],
						2 => [ [
							'setType' => 'watchlist',
							'bucket' => 'foo',
							'channels' => [ 'c1' ],
						] ],
						4 => [ [
							'setType' => 'watchlist',
							'bucket' => 'foo',
							'channels' => [ 'c2' ],
						] ],
					],
					'eventConfiguration' => $config,
					'channel' => $this->getChannel( 'c1' ),
				],
				[ 2 ],
			],
		];
	}

	private function getProvider( $model, $subscriptions, $eventConfiguration ) {
		$eventProviderMock = $this->createMock( EventProvider::class );
		$eventProviderMock->method( 'getRegisteredEvents' )->willReturn( $eventConfiguration );
		$mockUserFactory = $this->createMock( UserFactory::class );
		$mockUserFactory->method( 'newFromId' )->willReturnCallback( function ( $id ) {
			$userMock = $this->createMock( User::class );
			$userMock->method( 'getId' )->willReturn( $id );
			$blockMock = $this->createMock( AbstractBlock::class );
			if ( $id === 3 ) {
				$userMock->method( 'getBlock' )->willReturn( $blockMock );
			} else {
				$userMock->method( 'getBlock' )->willReturn( null );
			}
			return $userMock;
		} );

		$mockDatabase = $this->createMock( IDatabase::class );
		$mockDatabase->method( 'select' )->willReturn(
			array_map( static function ( $userId ) {
				return (object)[
					'user_id' => $userId,
				];
			}, [ 1, 2, 3, 4, 5 ] )
		);
		$mockLoadBalancer = $this->createMock( LoadBalancer::class );
		$mockLoadBalancer->method( 'getConnection' )
			->willReturn( $mockDatabase );
		$mockSubscriptionConfigurator = $this->createMock( SubscriptionConfigurator::class );
		$mockSubscriptionConfigurator->method( 'getConfiguration' )->willReturnCallback(
			static function ( UserIdentity $user ) use ( $subscriptions ) {
				$data = $subscriptions[$user->getId()] ?? [];
				return [ 'subscriptions' => $data ];
			} );

		$mockTitleFactory = $this->createMock( TitleFactory::class );
		$mockTitleFactory->method( 'newFromText' )->willReturnCallback( function ( $text ) {
			$categoryTitleMock = $this->createMock( Title::class );
			$categoryTitleMock->method( 'getPrefixedDBkey' )->willReturn( "Category:$text" );
			return $categoryTitleMock;
		} );

		$mockWatchedItemStore = $this->createMock( WatchedItemStoreInterface::class );
		$mockWatchedItemStore->method( 'getWatchedItem' )->willReturnCallback( function ( $user, $title ) {
			if ( $user->getId() === 1 || $title->getText() !== 'Watched' ) {
				return null;
			}
			$watchedItem = $this->createMock( \WatchedItem::class );
			$watchedItem->method( 'isExpired' )->willReturn( false );
			return $watchedItem;
		} );

		$ofMock = $this->createMock( ObjectFactory::class );
		$setProviders = [
			'category' => new CategorySet( $mockTitleFactory ),
			'ns' => new NamespaceSet(),
			'watchlist' => new WatchlistSet( $mockWatchedItemStore ),
		];

		$bucketProvider = new BucketProvider( [ 'no-opt-out' => [], 'foo' => [], 'bar' => [] ], $eventProviderMock );
		$instance = new ManualSubscriberProvider(
			$mockUserFactory,
			$mockLoadBalancer,
			$mockSubscriptionConfigurator,
			$ofMock,
			$bucketProvider,
			'dummy'
		);
		$instance->setSetProviders( $setProviders );
		return $instance;
	}

	private function getEvent( string $key, $ns = NS_MAIN, $title = 'Foo' ) {
		$class = $key === 'b' || $key === 'c' ? ITitleEvent::class : INotificationEvent::class;
		$event = $this->createMock( $class );
		$event->method( 'getKey' )->willReturn( $key );
		if ( $class === ITitleEvent::class ) {
			$titleMock = $this->createMock( Title::class );
			$titleMock->method( 'getNamespace' )->willReturn( $ns );
			$titleMock->method( 'getText' )->willReturn( $title );
			$titleMock->method( 'getParentCategories' )->willReturn( [
				'Category:Foo_bar' => true,
				'Category:Bar' => true
			] );
			$event->method( 'getTitle' )->willReturn( $titleMock );
		}
		return $event;
	}

	private function getChannel( string $key ) {
		$channel = $this->createMock( IChannel::class );
		$channel->method( 'getKey' )->willReturn( $key );
		return $channel;
	}
}
