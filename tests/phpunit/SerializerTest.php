<?php

namespace MediaWiki\Extension\NotifyMe\Tests;

use MediaWiki\Config\Config;
use MediaWiki\Extension\NotifyMe\BucketProvider;
use MediaWiki\Extension\NotifyMe\ChannelFactory;
use MediaWiki\Extension\NotifyMe\EventFactory;
use MediaWiki\Extension\NotifyMe\Grouping\NotificationGroup;
use MediaWiki\Extension\NotifyMe\NotificationSerializer;
use MediaWiki\Extension\NotifyMe\SubscriberManager;
use MediaWiki\Extension\NotifyMe\SubscriptionConfigurator;
use MediaWiki\Language\Language;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserOptionsLookup;
use MediaWiki\User\UserOptionsManager;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\Notification;

/**
 * @covers \MediaWiki\Extension\NotifyMe\NotificationSerializer
 */
class SerializerTest extends NotificationTestBase {
	/** @var Notification|null */
	private $notification = null;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$store = new DummyNotificationStore();
		$store->insertNotifications( [
			[
				'event' => [ 'edit', 1, '20220901000000', 'Main_Page' ],
				'channel' => 'web',
				'targetUser' => 5,
				'status' => [ 'status' => 'completed', 'time' => '20220916094701' ],
			]
		] );

		$this->notification = $store->getAll()[0];
	}

	/**
	 * @covers \MediaWiki\Extension\NotifyMe\NotificationSerializer::serialize
	 */
	public function testSerialize() {
		$serializer = $this->getSerializer();
		$serialized = $serializer->serialize( $this->notification );

		$this->assertSame( [
			'id' => 1,
			'channel' => 'web',
			'target_user' => 5,
			'status' => [
				'status' => 'completed',
				'error' => '',
				'time' => '20220916094701'
			],
			'source_providers' => []
		], $serialized );
	}

	/**
	 * @covers \MediaWiki\Extension\NotifyMe\NotificationSerializer::serializeForOutput
	 */
	public function testSerializeForOutput() {
		$serializer = $this->getSerializer();
		$serialized = $serializer->serializeForOutput(
			$this->notification, $this->getTargetUserMock()
		);

		$this->assertSame( [
			'entity_type' => 'single_notification',
			'id' => 1,
			'message' => 'dummy',
			'links_intro' => '',
			'links' => [],
			'agent' => [
				'display_name' => 'DemoUser',
				'username' => 'DemoUser',
				'user_page' => null
			],
			'agent_is_bot' => false,
			'icon' => '',
			'user_timestamp' => '20220901000000',
			'timestamp' => '2022-09-01T00:00:00+00:00',
			'status' => 'completed',
			'target_user' => [
				'display_name' => 'DemoUser',
				'username' => 'DemoUser',
				'user_page' => null
			],
			'channel' => 'web',
			'source_providers' => [],
		], $serialized );
	}

	/**
	 * @covers \MediaWiki\Extension\NotifyMe\NotificationSerializer::serializeNotificationGroupForOutput
	 */
	public function testSerializeGroupNotificationForOutput() {
		$serializer = $this->getSerializer();
		$group = new NotificationGroup( [ $this->notification ] );
		$serialized = $serializer->serializeNotificationGroupForOutput(
			$group, $this->getTargetUserMock()
		);

		$this->assertSame( [
			'entity_type' => 'group',
			'message' => 'dummy',
			'icon' => '',
			'timestamp' => '2022-09-01T00:00:00+00:00',
			'count' => 1,
			'target_user' => [
				'display_name' => 'DemoUser',
				'username' => 'DemoUser',
				'user_page' => null
			],
			'notifications' => [
				[
					'entity_type' => 'single_notification',
					'id' => 1,
					'message' => 'dummy',
					'links_intro' => '',
					'links' => [],
					'agent' => [
						'display_name' => 'DemoUser',
						'username' => 'DemoUser',
						'user_page' => null
					],
					'agent_is_bot' => false,
					'icon' => '',
					'user_timestamp' => '20220901000000',
					'timestamp' => '2022-09-01T00:00:00+00:00',
					'status' => 'completed',
					'target_user' => [
						'display_name' => 'DemoUser',
						'username' => 'DemoUser',
						'user_page' => null
					],
					'channel' => 'web',
					'source_providers' => [],
				]
			]
		], $serialized );
	}

	/**
	 * @covers \MediaWiki\Extension\NotifyMe\NotificationSerializer::serializeEvent
	 * @covers \MediaWiki\Extension\NotifyMe\NotificationSerializer::unserializeEvent
	 * @covers \MediaWiki\Extension\NotifyMe\NotificationSerializer::unserialize
	 */
	public function testUnserialize() {
		$serializer = $this->getSerializer();
		$serializedEvent = $serializer->serializeEvent( $this->notification->getEvent() );
		$unserialized = $serializer->unserialize( (object)[
			'ni_id' => 1,
			'ni_payload' => json_encode( [
				'id' => 1,
				'channel' => 'web',
				'target_user' => 5,
				'status' => [
					'status' => 'completed',
					'error' => '',
					'time' => '20220916094701'
				],
				'source_providers' => []
			] ),
			'ne_payload' => $serializedEvent
		] );

		$channel = $this->notification->getChannel();
		// Assert event
		$originalEvent = $this->notification->getEvent();
		$unserializedEvent = $unserialized->getEvent();
		$this->assertSame(
			$originalEvent->getTime()->format( 'YmdHis' ),
			$unserializedEvent->getTime()->format( 'YmdHis' )
		);
		// Disabled: Seems that in REL1_39 User::class mock is not serialized properly
		// $this->assertSame( $originalEvent->getAgent()->getId(), $unserializedEvent->getAgent()->getId() );
		$this->assertSame(
			$originalEvent->getTitle()->getPrefixedText(),
			$unserializedEvent->getTitle()->getPrefixedText()
		);
		$this->assertSame(
			$originalEvent->getMessage( $channel )->text(), $unserializedEvent->getMessage( $channel )->text()
		);
		$this->assertSame( $originalEvent->getKey(), $unserializedEvent->getKey() );

		$this->assertSame( $this->notification->getId(), $unserialized->getId() );
		$this->assertSame( $this->notification->getChannel()->getKey(), $unserialized->getChannel()->getKey() );
		$this->assertSame( $this->notification->getTargetUser()->getId(), $unserialized->getTargetUser()->getId() );
		$this->assertSame( $this->notification->getStatus()->getStatus(), $unserialized->getStatus()->getStatus() );
		$this->assertSame(
			$this->notification->getStatus()->getTime()->format( 'YmdHis' ),
			$unserialized->getStatus()->getTime()->format( 'YmdHis' )
		);
	}

	/**
	 * @return NotificationSerializer
	 */
	protected function getSerializer(): NotificationSerializer {
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->method( 'newFromRow' )->willReturnCallback( function ( $row ) {
			$title = $this->createMock( Title::class );
			$title->method( 'getPrefixedText' )->willReturn( $row->page_title );
			$title->method( 'getPrefixedDBkey' )->willReturn( $row->page_title );
			$title->method( 'getNamespace' )->willReturn( $row->page_namespace );
			$title->method( 'getId' )->willReturn( $row->page_id );
			return $title;
		} );
		$userFactoryMock = $this->createMock( UserFactory::class );
		$userFactoryMock->method( 'newFromId' )->willReturnCallback( function ( $id ) {
			$user = $this->createMock( User::class );
			$user->method( 'getId' )->willReturn( $id );
			$user->method( 'getUserPage' )->willReturn( $this->createMock( Title::class ) );
			$user->method( 'getName' )->willReturn( 'DemoUser' );
			$user->method( 'getRealName' )->willReturn( '' );
			$user->mId = $id;
			return $user;
		} );
		$channelFactoryMock = $this->createMock( ChannelFactory::class );
		$channelFactoryMock->method( 'getChannel' )->willReturnCallback( function ( $channel ) {
			$channelMock = $this->createMock( IChannel::class );
			$channelMock->method( 'getKey' )->willReturn( $channel );
			return $channelMock;
		} );

		$languageFactoryMock = $this->createMock( LanguageFactory::class );
		$languageFactoryMock->method( 'getLanguage' )->willReturnCallback( function ( $code ) {
			$languageMock = $this->createMock( Language::class );
			$languageMock->method( 'getCode' )->willReturn( $code );
			$languageMock->method( 'userTimeAndDate' )->willReturnCallback( static function ( $time, $user ) {
				return $time;
			} );
			return $languageMock;
		} );
		$userOptionLookupMock = $this->createMock( UserOptionsLookup::class );
		$userOptionLookupMock->method( 'getOption' )->willReturn( 'en' );

		$contentLanguage = $this->createMock( Language::class );
		$contentLanguage->method( 'getCode' )->willReturn( 'en' );

		$subscriberConfigurator = new SubscriptionConfigurator(
			$this->createMock( ChannelFactory::class ),
			$this->createMock( UserOptionsManager::class ),
			$this->createMock( BucketProvider::class ),
			$this->createMock( Config::class )
		);
		return new NotificationSerializer(
			$userFactoryMock,
			$channelFactoryMock,
			new SubscriberManager( [], $subscriberConfigurator ),
			$languageFactoryMock,
			$userOptionLookupMock,
			$contentLanguage,
			$this->createMock( EventFactory::class )
		);
	}

	/**
	 * @return \PHPUnit\Framework\MockObject\MockObject|User|User&\PHPUnit\Framework\MockObject\MockObject
	 */
	protected function getTargetUserMock() {
		$user = $this->createMock( User::class );
		$user->method( 'getName' )->willReturn( 'DemoUser' );
		$user->method( 'getUserPage' )->willReturn( $this->createMock( Title::class ) );
		return $user;
	}
}
