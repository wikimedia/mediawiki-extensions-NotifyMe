<?php

namespace MediaWiki\Extension\NotifyMe\Tests\SubscriberProvider;

use MediaWiki\Extension\NotifyMe\BucketProvider;
use MediaWiki\Extension\NotifyMe\SubscriberProvider\WatchlistSubscriberProvider;
use MediaWiki\Extension\NotifyMe\SubscriptionConfigurator;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\INotificationEvent;
use MWStake\MediaWiki\Component\Events\ITitleEvent;
use PHPUnit\Framework\TestCase;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\LoadBalancer;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @covers \MediaWiki\Extension\NotifyMe\SubscriberProvider\WatchlistSubscriberProvider
 */
class WatchlistSubscriberProviderTest extends TestCase {
	public function testGetSubscribersForTitleEventFiltersAndDeduplicates(): void {
		$event = $this->createMock( ITitleEvent::class );
		$event->method( 'getKey' )->willReturn( 'edit' );
		$event->method( 'getTitle' )->willReturn( $this->getPageMock( NS_MAIN, 'Main_Page' ) );

		$channel = $this->createMock( IChannel::class );
		$channel->method( 'getKey' )->willReturn( 'web' );

		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->method( 'newFromId' )->willReturnCallback( function ( int $id ) {
			return $this->getUserMock( $id, $id !== 4, $id === 3 );
		} );

		$lb = $this->createMock( LoadBalancer::class );
		$lb->method( 'getConnection' )->willReturn( $this->getDbMockForWatcherRows( [ 1, 2, 2, 3, 4 ] ) );

		$configurator = $this->createMock( SubscriptionConfigurator::class );
		$configurator->method( 'getConfiguration' )->willReturnCallback(
			static function ( User $user ) {
				if ( $user->getId() === 2 ) {
					return [ 'subscriptions' => [ 'edit' => false ] ];
				}
				return [ 'subscriptions' => [] ];
			}
		);

		$bucketProvider = $this->createMock( BucketProvider::class );
		$bucketProvider->method( 'hasMandatoryBuckets' )->willReturn( false );

		$hookContainer = $this->createMock( HookContainer::class );
		$hookContainer->method( 'run' )->willReturn( true );

		$provider = new WatchlistSubscriberProvider(
			$hookContainer,
			$userFactory,
			$lb,
			$configurator,
			$bucketProvider
		);

		$subscribers = $provider->getSubscribers( $event, $channel );
		$ids = array_map( static fn ( User $user ) => $user->getId(), $subscribers );

		$this->assertSame( [ 1 ], $ids );
	}

	public function testGetSubscribersForNonTitleEventUsesHookWatchers(): void {
		$event = $this->createMock( INotificationEvent::class );
		$event->method( 'getKey' )->willReturn( 'edit' );

		$channel = $this->createMock( IChannel::class );
		$channel->method( 'getKey' )->willReturn( 'email' );

		$hookWatcher = $this->getUserMock( 15, true, false );
		$hookContainer = $this->createMock( HookContainer::class );
		$hookContainer->method( 'run' )->willReturnCallback(
			static function ( string $hookName, array $args ) use ( $hookWatcher ) {
				$args[2][] = $hookWatcher;
				return true;
			}
		);

		$configurator = $this->createMock( SubscriptionConfigurator::class );
		$configurator->expects( $this->never() )->method( 'getConfiguration' );

		$bucketProvider = $this->createMock( BucketProvider::class );
		$bucketProvider->method( 'hasMandatoryBuckets' )->willReturn( true );

		$provider = new WatchlistSubscriberProvider(
			$hookContainer,
			$this->createMock( UserFactory::class ),
			$this->createMock( LoadBalancer::class ),
			$configurator,
			$bucketProvider
		);

		$subscribers = $provider->getSubscribers( $event, $channel );
		$ids = array_map( static fn ( User $user ) => $user->getId(), $subscribers );

		$this->assertSame( [ 15 ], $ids );
	}

	public function testModifyConfigurationAddsMissingEventSubscriptions(): void {
		$bucketProvider = $this->createMock( BucketProvider::class );
		$bucketProvider->method( 'getEventTypes' )->willReturn( [ 'edit', 'delete' ] );

		$provider = new WatchlistSubscriberProvider(
			$this->createMock( HookContainer::class ),
			$this->createMock( UserFactory::class ),
			$this->createMock( LoadBalancer::class ),
			$this->createMock( SubscriptionConfigurator::class ),
			$bucketProvider
		);

		$modified = $provider->modifyConfiguration( [
			'subscriptions' => [ 'edit' => false ]
		] );

		$this->assertSame(
			[ 'edit' => false, 'delete' => true ],
			$modified['subscriptions']
		);
	}

	private function getPageMock( int $namespace, string $dbKey ): Title {
		$page = $this->createMock( Title::class );
		$page->method( 'getNamespace' )->willReturn( $namespace );
		$page->method( 'getDBkey' )->willReturn( $dbKey );
		return $page;
	}

	private function getUserMock( int $id, bool $registered, bool $blocked ): User {
		$user = $this->createMock( User::class );
		$user->method( 'getId' )->willReturn( $id );
		$user->method( 'isRegistered' )->willReturn( $registered );
		$user->method( 'getBlock' )->willReturn(
			$blocked ? $this->createMock( \MediaWiki\Block\Block::class ) : null
		);
		return $user;
	}

	private function getDbMockForWatcherRows( array $userIds ) {
		$queryBuilder = $this->createMock( SelectQueryBuilder::class );
		$queryBuilder->method( 'from' )->willReturnSelf();
		$queryBuilder->method( 'select' )->willReturnSelf();
		$queryBuilder->method( 'where' )->willReturnSelf();
		$queryBuilder->method( 'caller' )->willReturnSelf();
		$queryBuilder->method( 'fetchResultSet' )->willReturn(
			new FakeResultWrapper(
				array_map(
					static fn ( int $id ) => (object)[ 'wl_user' => $id ],
					$userIds
				)
			)
		);

		$db = $this->createMock( \Wikimedia\Rdbms\IDatabase::class );
		$db->method( 'newSelectQueryBuilder' )->willReturn( $queryBuilder );
		return $db;
	}
}
