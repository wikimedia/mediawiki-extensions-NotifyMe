<?php

namespace MediaWiki\Extension\NotifyMe\WikiAutomations\Action;

use MediaWiki\Extension\NotifyMe\WikiAutomations\ArbitraryEvent;
use MediaWiki\Extension\WikiAutomations\Action\GenericAutomationAction;
use MediaWiki\Message\Message;
use MediaWiki\Status\Status;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Events\BotAgent;
use MWStake\MediaWiki\Component\Events\Notifier;
use MWStake\MediaWiki\Component\FormEngine\IFormSpecification;
use MWStake\MediaWiki\Component\FormEngine\StandaloneFormSpecification;

class ArbitraryNotificationAction extends GenericAutomationAction {

	public function __construct(
		protected readonly UserFactory $userFactory,
		protected readonly Notifier $notifier
	) {
	}

	public function getLayout(): IFormSpecification {
		$spec = new StandaloneFormSpecification();
		$spec->setItems( [
			[
				'type' => 'checkbox',
				'name' => 'sendAsBot',
				'label' => Message::newFromKey( 'notifyme-arbitrary-event-action-send-as-bot-label' )->text(),
			],
			[
				'type' => 'text',
				'name' => 'message',
				'label' => Message::newFromKey( 'notifyme-arbitrary-event-action-message-label' )->text(),
			],
			[
				'type' => 'user_multiselect',
				'name' => 'target_users',
				'label' => Message::newFromKey( 'notifyme-arbitrary-event-action-event-target-user-label' )->text(),
			],
		] );
		return $spec;
	}

	public function execute(): Status {
		$data = $this->getData();
		$users = $this->getTargetUsers( $data );

		$event = new ArbitraryEvent( $this->getAgent( $data ), $data['message'] ?? '', $users );
		$this->notifier->emit( $event );

		return Status::newGood( [
			'users' => array_map( static function ( $user ) {
				return $user->getName();
			}, $users ),
			'message' => $data['message'] ?? ''
		] );
	}

	/**
	 * @param array $data
	 * @return array
	 */
	protected function getTargetUsers( array $data ): array {
		$users = array_map( function ( $userName ) {
			return $this->userFactory->newFromName( $userName );
		}, $data['target_users'] ?? [] );
		return array_filter( $users, static function ( $user ) {
			return $user && $user->isRegistered();
		} );
	}

	/**
	 * @param array $data
	 * @return UserIdentity
	 */
	protected function getAgent( array $data = [] ): UserIdentity {
		if ( ( isset( $data['sendAsBot'] ) && !$data['sendAsBot'] ) && $this->triggeredBy ) {
			return $this->triggeredBy->getUser();
		}
		return new BotAgent();
	}
}
