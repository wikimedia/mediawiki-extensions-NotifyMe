<?php

namespace MediaWiki\Extension\NotifyMe\Tests;

use MediaWiki\Extension\NotifyMe\Grouping\Grouper;
use MediaWiki\Extension\NotifyMe\Grouping\NotificationGroup;
use MWStake\MediaWiki\Component\Events\Notification;

/**
 * @covers \MediaWiki\Extension\NotifyMe\Grouping\Grouper
 */
class GrouperTest extends NotificationTestBase {

	/**
	 * @covers \MediaWiki\Extension\NotifyMe\Grouping\Grouper::group
	 * @covers \MediaWiki\Extension\NotifyMe\Grouping\Grouper::onSubject
	 * @covers \MediaWiki\Extension\NotifyMe\Grouping\NotificationGroup::getNotifications
	 * @covers \MediaWiki\Extension\NotifyMe\Grouping\NotificationGroup::getEvent
	 * @covers \MediaWiki\Extension\NotifyMe\Grouping\NotificationGroup::getTargetUser
	 */
	public function testGrouping() {
		$notifications = $this->getNotifications();
		$grouper = new Grouper( $notifications );

		// Group on subject (in addition to standard key and title)
		$onSubject = $grouper->onSubject()->group();
		$this->assertCount( 2, $onSubject );
		$this->assertInstanceOf( NotificationGroup::class, $onSubject[0] );
		$this->assertCount( 4, $onSubject[0]->getNotifications() );
		$this->assertInstanceOf( Notification::class, $onSubject[1] );

		// Normal grouping
		$normal = $grouper->group();
		$this->assertCount( 2, $normal );
		$this->assertInstanceOf( NotificationGroup::class, $onSubject[0] );
		$this->assertInstanceOf( Notification::class, $onSubject[1] );

		$this->assertSame( 'edit', $normal[0]->getEvent()->getKey() );
		$this->assertSame( 5, $normal[0]->getTargetUser()->getId() );
	}

	/**
	 * @return array
	 */
	public function getNotifications(): array {
		$store = new DummyNotificationStore();
		$store->insertNotifications( [
			[
				'event' => [ 'edit', 1, '20220901000000' ],
				'channel' => 'web',
				'targetUser' => 5,
			],
			[
				'event' => [ 'edit', 1, '20220912000000' ],
				'channel' => 'web',
				'targetUser' => 5,
			],
			[
				'event' => [ 'edit', 2, '20220912000000' ],
				'channel' => 'web',
				'targetUser' => 5,
			],
			[
				'event' => [ 'edit', 1, '20220911010000' ],
				'channel' => 'email',
				'targetUser' => 5,
			],
			[
				'event' => [ 'edit', 1, '20220911010000' ],
				'channel' => 'web',
				'targetUser' => 6,
			],
			[
				'event' => [ 'delete', 2, '20220911000000' ],
				'channel' => 'web',
				'targetUser' => 5,
			]
		] );

		return $store->forChannel( 'web' )->query();
	}
}
