<?php

namespace MediaWiki\Extension\NotifyMe\WikiAutomations\Action;

use MediaWiki\Extension\NotifyMe\WikiAutomations\ArbitraryEvent;
use MediaWiki\Extension\WikiAutomations\Action\GenericAutomationAction;
use MediaWiki\Extension\WikiAutomations\Util\WikitextExpressionParser;
use MediaWiki\Message\Message;
use MediaWiki\Status\Status;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Events\BotAgent;
use MWStake\MediaWiki\Component\Events\Notifier;
use MWStake\MediaWiki\Component\FormEngine\IFormSpecification;
use MWStake\MediaWiki\Component\FormEngine\StandaloneFormSpecification;

class ArbitraryNotificationAction extends GenericAutomationAction {

	/**
	 * @param UserFactory $userFactory
	 * @param Notifier $notifier
	 * @param WikitextExpressionParser $expressionParser
	 */
	public function __construct(
		protected readonly UserFactory $userFactory,
		protected readonly Notifier $notifier,
		protected readonly WikitextExpressionParser $expressionParser
	) {
	}

	public function getLayout(): IFormSpecification {
		$spec = new StandaloneFormSpecification();
		$spec->setItems( [
			[
				'type' => 'text',
				'name' => 'subject',
				'label' => Message::newFromKey( 'notifyme-arbitrary-event-action-subject-label' )->text(),
				'labelAlign' => 'top',
				'help' => Message::newFromKey( 'notifyme-arbitrary-event-action-subject-help' )->text(),
				'helpInline' => true,
			],
			[
				'type' => 'textarea',
				'required' => true,
				'name' => 'message',
				'label' => Message::newFromKey( 'notifyme-arbitrary-event-action-message-label' )->text(),
				'labelAlign' => 'top',
				'help' => $this->getMessageHelpText(),
				'helpInline' => true,
			],
			[
				'type' => 'textarea',
				'name' => 'target_users',
				'label' => Message::newFromKey( 'notifyme-arbitrary-event-action-event-target-user-label' )->text(),
				'labelAlign' => 'top',
				'help' => Message::newFromKey( 'notifyme-arbitrary-event-action-event-target-user-help' )->text(),
				'helpInline' => true,
				'widget_rows' => 2
			],
			[
				'type' => 'checkbox',
				'name' => 'sendAsBot',
				'label' => Message::newFromKey( 'notifyme-arbitrary-event-action-send-as-bot-label' )->text(),
				'labelAlign' => 'inline'
			],
		] );
		return $spec;
	}

	public function execute(): Status {
		$data = $this->getData();
		$agent = $this->getAgent( $data );
		$users = $this->expressionParser->processUsers( $data['target_users'] ?? '', $agent );

		$event = new ArbitraryEvent(
			$agent, $data['message'] ?? '', $data['subject'] ?? '', $users
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
		if ( !empty( $data['target_users'] ) ) {
			$displayData[] = [
				'value' => $data['target_users']
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

	/**
	 * @return string
	 */
	protected function getMessageHelpText(): string {
		return Message::newFromKey( 'notifyme-arbitrary-event-action-message-help' )->text();
	}

}
