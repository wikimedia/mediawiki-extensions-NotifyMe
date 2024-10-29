<?php

namespace MediaWiki\Extension\NotifyMe\Tests;

use DateTime;
use MWStake\MediaWiki\Component\Events\Delivery\NotificationStatus;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\NotifyMe\NotificationStatus
 */
class NotificationStatusTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\NotifyMe\NotificationStatus::markAsCompleted
	 */
	public function testSuccess() {
		$status = new NotificationStatus();
		$status->markAsCompleted();

		$this->assertSame( NotificationStatus::STATUS_COMPLETED, $status->getStatus() );
		$this->assertTrue( $status->isCompleted() );
		$this->assertFalse( $status->isFailed() );
		$this->assertFalse( $status->isPending() );
		$this->assertInstanceOf( DateTime::class, $status->getTime() );
	}

	/**
	 * @covers \MediaWiki\Extension\NotifyMe\NotificationStatus::markAsFailed
	 */
	public function testError() {
		$status = new NotificationStatus();
		$status->markAsFailed( 'error' );

		$this->assertSame( NotificationStatus::STATUS_FAILED, $status->getStatus() );
		$this->assertTrue( $status->isFailed() );
		$this->assertFalse( $status->isCompleted() );
		$this->assertFalse( $status->isPending() );
		$this->assertSame( 'error', $status->getErrorMessage() );
		$this->assertInstanceOf( DateTime::class, $status->getTime() );
	}
}
