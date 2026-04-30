<?php

namespace MediaWiki\Extension\NotifyMe\WikiAutomations\Action;

use Exception;
use MediaWiki\Extension\NotifyMe\WikiAutomations\ArbitraryTitleEvent;
use MediaWiki\Extension\WikiAutomations\IPageScopedAutomationAction;
use MediaWiki\Message\Message;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Status\Status;

class ArbitraryTitleNotificationAction extends ArbitraryNotificationAction implements IPageScopedAutomationAction {

	/**
	 * @param PageIdentity $page
	 * @return Status
	 * @throws Exception
	 */
	public function executeForPage( PageIdentity $page ): Status {
		$data = $this->getData();
		$users = $this->getTargetUsers( $data );
		$event = new ArbitraryTitleEvent(
			$this->getAgent( $data ), $page, $data['message'] ?? '', $data['subject'], $users
		);
		$this->notifier->emit( $event );

		return Status::newGood( [
			'users' => array_map( static function ( $user ) {
				return $user->getName();
			}, $users ),
			'subject' => $data['subject'] ?? '',
			'message' => $data['message'] ?? '',
			'title' => $page->getPrefixedText()
		] );
	}

	/**
	 * @return string
	 */
	protected function getMessageHelpText(): string {
		return Message::newFromKey( 'notifyme-arbitrary-event-action-with-title-message-help' )->text();
	}
}
