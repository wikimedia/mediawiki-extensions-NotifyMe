<?php

namespace MediaWiki\Extension\NotifyMe;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\Delivery\NotificationStatus;
use MWStake\MediaWiki\Component\Events\INotificationEvent;
use MWStake\MediaWiki\Component\Events\Notification;
use MWStake\MediaWiki\Component\Events\TitleEvent;

class ForeignNotification extends Notification {
	public function __construct(
		INotificationEvent $event, User $targetUser, IChannel $channel,
		NotificationStatus $status, array $sourceProviders,
		private readonly string $sourceWikiId,
		private readonly HookContainer $hookContainer,
		private readonly TitleFactory $titleFactory
	) {
		parent::__construct( $event, $targetUser, $channel, $status, $sourceProviders );
	}

	/**
	 * @return string
	 */
	public function getSourceWikiId(): string {
		return $this->sourceWikiId;
	}

	/**
	 * @return string[]
	 */
	public function getSourceWikiInfo(): array {
		$data = [
			'wiki_id' => $this->getSourceWikiId(),
			'display_text' => $this->getSourceWikiId()
		];
		$this->hookContainer->run( 'GetWikiInfoFromWikiId', [ $this->getSourceWikiId(), &$data ] );
		return $data;
	}

	public function getEvent(): INotificationEvent {
		$event = parent::getEvent();
		if ( !( $event instanceof TitleEvent ) ) {
			return $event;
		}
		$origTitle = $event->getTitle();
		if ( $origTitle->getInterwiki() ) {
			// Already handled or otherwise incompatible
			return $event;
		}
		$iwPrefix = '';
		$this->hookContainer->run( 'GetInterwikiPrefixFromWikiId', [ $this->sourceWikiId, &$iwPrefix ] );
		if ( $iwPrefix ) {
			$iwTitle = $this->titleFactory->newFromText( $iwPrefix . ':' . $origTitle->getPrefixedText() );
			if ( $iwTitle ) {
				$event->setTitle( $iwTitle );
			}
		}
		return $event;
	}
}
