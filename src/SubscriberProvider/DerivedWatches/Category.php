<?php

namespace MediaWiki\Extension\NotifyMe\SubscriberProvider\DerivedWatches;

use MediaWiki\Extension\NotifyMe\Hook\NotifyMeWatchlistProviderGetWatchersHook;
use MediaWiki\Extension\NotifyMe\Hook\NotifyMeWatchlistProviderGetWatchSourceHook;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserFactory;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\INotificationEvent;
use MWStake\MediaWiki\Component\Events\ITitleEvent;
use MWStake\MediaWiki\Component\Events\Notification;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\Platform\ISQLPlatform;

class Category implements NotifyMeWatchlistProviderGetWatchersHook, NotifyMeWatchlistProviderGetWatchSourceHook {

	/** @var array */
	private array $categoryWatchers = [];

	/**
	 * @param ILoadBalancer $lb
	 * @param UserFactory $userFactory
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		private readonly ILoadBalancer $lb,
		private readonly UserFactory $userFactory,
		private readonly TitleFactory $titleFactory
	) {
	}

	public function onNotifyMeWatchlistProviderGetWatchSource( Notification $notification, Message &$description ) {
		if ( !( $notification->getEvent() instanceof ITitleEvent ) ) {
			return;
		}
		$title = $notification->getEvent()->getTitle();
		if ( !$title->exists() ) {
			return;
		}
		if ( !isset( $this->categoryWatchers[$title->getArticleID()] ) ) {
			$this->categoryWatchers[$title->getArticleID()] = $this->getCategoryWatchers( $title );
		}

		foreach ( $this->categoryWatchers[$title->getArticleID()] as $watcher ) {
			if ( $watcher->getId() === $notification->getTargetUser()->getId() ) {
				$description = Message::newFromKey( 'notifyme-category-subscription-description' );
				return;
			}
		}
	}

	/**
	 * @param INotificationEvent $event
	 * @param IChannel $channel
	 * @param array &$watchers
	 * @return void
	 */
	public function onNotifyMeWatchlistProviderGetWatchers(
		INotificationEvent $event, IChannel $channel, array &$watchers
	): void {
		if ( !( $event instanceof ITitleEvent ) ) {
			return;
		}
		$title = $event->getTitle();
		if ( !$title->exists() ) {
			return;
		}

		$this->categoryWatchers[$title->getArticleID()] = $this->getCategoryWatchers( $title );
		$watchers = array_merge( $watchers, $this->categoryWatchers[$title->getArticleID()] );
	}

	/**
	 * @param Title $title
	 * @return array
	 */
	private function getCategoryWatchers( Title $title ): array {
		$categories = $this->flatten( $title->getParentCategoryTree() );
		if ( !$categories ) {
			return [];
		}

		$db = $this->lb->getConnection( DB_REPLICA );
		$watchConditions = [];
		foreach ( $categories as $category ) {
			// Drop namespace prefix
			$categoryName = $this->titleFactory->newFromText( $category, NS_CATEGORY )->getDBkey();
			$watchConditions[] = $db->makeList( [
				'wl_namespace' => NS_CATEGORY,
				'wl_title' => $categoryName,
			], ISQLPlatform::LIST_AND );
		}

		$res = $db->newSelectQueryBuilder()
			->from( 'watchlist', 'wl' )
			->select( 'wl_user' )
			->where( $db->makeList( $watchConditions, ISQLPlatform::LIST_OR ) )
			->caller( __METHOD__ )
			->groupBy( 'wl_user' )
			->fetchResultSet();

		$users = [];
		foreach ( $res as $row ) {
			$users[] = $this->userFactory->newFromId( $row->wl_user );
		}
		return $users;
	}

	/**
	 * @param array $parentCategoryTree
	 * @return array
	 */
	private function flatten( array $parentCategoryTree ): array {
		$flat = [];

		foreach ( $parentCategoryTree as $category => $children ) {
			$flat[] = $category;
			$flat = array_merge( $flat, $this->flatten( $children ) );
		}

		return $flat;
	}
}
