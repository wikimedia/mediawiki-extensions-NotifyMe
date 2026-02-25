<?php

namespace MediaWiki\Extension\NotifyMe\WikiAutomations\Action;

use MediaWiki\Extension\NotifyMe\WikiAutomations\ArbitraryTitleEvent;
use MediaWiki\Extension\WikiAutomations\IPageScopedAutomationAction;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Status\Status;

class ArbitraryTitleNotificationAction extends ArbitraryNotificationAction implements IPageScopedAutomationAction {

	public function executeForPage( PageIdentity $page ): Status {
		$data = $this->getData();
		$users = $this->getTargetUsers( $data );
		$event = new ArbitraryTitleEvent( $this->getAgent( $data ), $page, $data['message'] ?? '', $users );
		$this->notifier->emit( $event );

		return Status::newGood( [
			'users' => array_map( static function ( $user ) {
				return $user->getName();
			}, $users ),
			'message' => $data['message'] ?? '',
			'title' => $page->getPrefixedText()
		] );
	}
}
