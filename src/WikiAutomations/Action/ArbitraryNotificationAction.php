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
				'name' => 'subject',
				'label' => Message::newFromKey( 'notifyme-arbitrary-event-action-subject-label' )->text(),
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

		$event = new ArbitraryEvent(
			$this->getAgent( $data ), $data['message'] ?? '', $data['subject'] ?? '', $users
		);
		$this->notifier->emit( $event );

		return Status::newGood( [
			'users' => array_map( static function ( $user ) {
				return $user->getName();
			}, $users ),
			'subject' => $data['subject'] ?? '',
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
	 * @return array
	 */
	public function getDisplayData(): array {
		$data = $this->getData();

		$displayData = [];
		if ( $data['subject'] ?? '' ) {
			$displayData[] = [
				'value' => $data['subject']
			];
		}
		$message = $data['message'] ?? '';
		if ( strlen( $message ) > 50 ) {
			$message = substr( $message, 0, 47 ) . '...';
		}
		if ( $message ) {
			$displayData[] = [
				'value' => $message
			];
		}
		$targetUsers = array_map( static function ( $user ) {
			return $user->getName();
		}, $this->getTargetUsers( $data ) );
		if ( !empty( $targetUsers ) ) {
			$displayData[] = [
				'value' => implode( ', ', $targetUsers )
			];
		}

		return $displayData;
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
