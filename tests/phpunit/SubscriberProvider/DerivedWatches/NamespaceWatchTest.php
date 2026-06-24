<?php

namespace MediaWiki\Extension\NotifyMe\Tests\SubscriberProvider\DerivedWatches;

use MediaWiki\Extension\NotifyMe\SubscriberProvider\DerivedWatches\NamespaceWatch;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\INotificationEvent;
use MWStake\MediaWiki\Component\Events\ITitleEvent;
use MWStake\MediaWiki\Component\Events\Notification;
use PHPUnit\Framework\TestCase;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @covers \MediaWiki\Extension\NotifyMe\SubscriberProvider\DerivedWatches\NamespaceWatch
 */
class NamespaceWatchTest extends TestCase {
	public function testOnNotifyMeWatchlistProviderGetWatchersAddsNamespaceWatchers(): void {
		$watch = new NamespaceWatch(
			$this->getLoadBalancerMock( [ 10, 11 ] ),
			$this->getUserFactoryMock()
		);

		$event = $this->getTitleEventMock( $this->getTitleMock( NS_HELP, true ) );
		$channel = $this->createMock( IChannel::class );
		$watchers = [ $this->getUserMock( 5 ) ];

		$watch->onNotifyMeWatchlistProviderGetWatchers( $event, $channel, $watchers );

		$ids = array_map( static fn ( User $user ) => $user->getId(), $watchers );
		$this->assertSame( [ 5, 10, 11 ], $ids );
	}

	public function testOnNotifyMeWatchlistProviderGetWatchSourceSetsNamespaceDescription(): void {
		$watch = new NamespaceWatch(
			$this->getLoadBalancerMock( [ 11 ] ),
			$this->getUserFactoryMock()
		);

		$title = $this->getTitleMock( NS_HELP, true );
		$event = $this->getTitleEventMock( $title );
		$notification = $this->createMock( Notification::class );
		$notification->method( 'getEvent' )->willReturn( $event );
		$notification->method( 'getTargetUser' )->willReturn( $this->getUserMock( 11 ) );

		$description = Message::newFromKey( 'initial' );
		$watch->onNotifyMeWatchlistProviderGetWatchSource( $notification, $description );

		$this->assertSame(
			'You are watching the namespace this page belongs to',
			$description->text()
		);
	}

	public function testOnNotifyMeWatchlistProviderGetWatchersSkipsNonTitleEvents(): void {
		$watch = new NamespaceWatch(
			$this->getLoadBalancerMock( [ 10 ] ),
			$this->getUserFactoryMock()
		);

		$event = $this->createMock( INotificationEvent::class );
		$channel = $this->createMock( IChannel::class );
		$watchers = [ $this->getUserMock( 5 ) ];

		$watch->onNotifyMeWatchlistProviderGetWatchers( $event, $channel, $watchers );
		$ids = array_map( static fn ( User $user ) => $user->getId(), $watchers );

		$this->assertSame( [ 5 ], $ids );
	}

	private function getTitleEventMock( Title $title ): ITitleEvent {
		$event = $this->createMock( ITitleEvent::class );
		$event->method( 'getTitle' )->willReturn( $title );
		return $event;
	}

	private function getTitleMock( int $namespace, bool $exists ): Title {
		$title = $this->createMock( Title::class );
		$title->method( 'getNamespace' )->willReturn( $namespace );
		$title->method( 'exists' )->willReturn( $exists );
		return $title;
	}

	private function getUserFactoryMock(): UserFactory {
		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->method( 'newFromId' )->willReturnCallback(
			fn ( int $id ) => $this->getUserMock( $id )
		);
		return $userFactory;
	}

	private function getUserMock( int $id ): User {
		$user = $this->createMock( User::class );
		$user->method( 'getId' )->willReturn( $id );
		return $user;
	}

	private function getLoadBalancerMock( array $userIds ): ILoadBalancer {
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

		$lb = $this->createMock( ILoadBalancer::class );
		$lb->method( 'getConnection' )->willReturn( $db );
		return $lb;
	}
}
