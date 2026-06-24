<?php

namespace MediaWiki\Extension\NotifyMe\Tests\SubscriberProvider\DerivedWatches;

use MediaWiki\Extension\NotifyMe\SubscriberProvider\DerivedWatches\Category;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\ITitleEvent;
use MWStake\MediaWiki\Component\Events\Notification;
use PHPUnit\Framework\TestCase;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @covers \MediaWiki\Extension\NotifyMe\SubscriberProvider\DerivedWatches\Category
 */
class CategoryTest extends TestCase {
	public function testOnNotifyMeWatchlistProviderGetWatchersAddsCategoryWatchers(): void {
		$watch = new Category(
			$this->getLoadBalancerMock( [ 20, 21 ] ),
			$this->getUserFactoryMock(),
			$this->getTitleFactoryMock()
		);

		$title = $this->getTitleMock( true, 99, [
			'Category:Root' => [
				'Category:Child' => []
			]
		] );
		$event = $this->getTitleEventMock( $title );
		$channel = $this->createMock( IChannel::class );
		$watchers = [ $this->getUserMock( 5 ) ];

		$watch->onNotifyMeWatchlistProviderGetWatchers( $event, $channel, $watchers );
		$ids = array_map( static fn ( User $user ) => $user->getId(), $watchers );

		$this->assertSame( [ 5, 20, 21 ], $ids );
	}

	public function testOnNotifyMeWatchlistProviderGetWatchSourceSetsCategoryDescription(): void {
		$watch = new Category(
			$this->getLoadBalancerMock( [ 22 ] ),
			$this->getUserFactoryMock(),
			$this->getTitleFactoryMock()
		);

		$title = $this->getTitleMock( true, 100, [ 'Category:Root' => [] ] );
		$event = $this->getTitleEventMock( $title );
		$notification = $this->createMock( Notification::class );
		$notification->method( 'getEvent' )->willReturn( $event );
		$notification->method( 'getTargetUser' )->willReturn( $this->getUserMock( 22 ) );

		$description = Message::newFromKey( 'initial' );
		$watch->onNotifyMeWatchlistProviderGetWatchSource( $notification, $description );

		$this->assertSame(
			'You are watching a category this page belongs to',
			$description->text()
		);
	}

	private function getTitleEventMock( Title $title ): ITitleEvent {
		$event = $this->createMock( ITitleEvent::class );
		$event->method( 'getTitle' )->willReturn( $title );
		return $event;
	}

	private function getTitleMock( bool $exists, int $articleId, array $categoryTree ): Title {
		$title = $this->createMock( Title::class );
		$title->method( 'exists' )->willReturn( $exists );
		$title->method( 'getArticleID' )->willReturn( $articleId );
		$title->method( 'getParentCategoryTree' )->willReturn( $categoryTree );
		return $title;
	}

	private function getTitleFactoryMock(): TitleFactory {
		$titleFactory = $this->createMock( TitleFactory::class );
		$titleFactory->method( 'newFromText' )->willReturnCallback( function ( string $text ) {
			$title = $this->createMock( Title::class );
			$title->method( 'getDBkey' )->willReturn( str_replace( 'Category:', '', $text ) );
			return $title;
		} );
		return $titleFactory;
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
		$queryBuilder->method( 'groupBy' )->willReturnSelf();
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
		$db->method( 'makeList' )->willReturn( 'cond' );

		$lb = $this->createMock( ILoadBalancer::class );
		$lb->method( 'getConnection' )->willReturn( $db );
		return $lb;
	}
}
