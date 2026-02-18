<?php

namespace MediaWiki\Extension\NotifyMe\Storage\FilterBucket;

use MediaWiki\Extension\NotifyMe\Storage\WebNotificationQueryStore;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Language\RawMessage;
use MediaWiki\Message\Message;
use MediaWiki\User\UserIdentity;
use MediaWiki\WikiMap\WikiMap;

class SourceWikiBucket extends FilterBucket {

	/** @var string */
	private string $wikiId;

	/**
	 * @param HookContainer $hookContainer
	 * @param WebNotificationQueryStore $store
	 * @param UserIdentity $forUser
	 * @param string $forStatus
	 */
	public function __construct(
		private readonly HookContainer $hookContainer,
		WebNotificationQueryStore $store,
		UserIdentity $forUser,
		string $forStatus = 'all'
	) {
		parent::__construct( $store, $forUser, $forStatus );
		$this->wikiId = WikiMap::getCurrentWikiId();
	}

	/**
	 * @inheritDoc
	 */
	public function getLabel(): Message {
		return Message::newFromKey( 'notifyme-notification-center-filter-label-source' );
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return 'source';
	}

	/**
	 * @return FilterBucketOption[]
	 */
	public function getOptions(): array {
		$res = [];
		$values = $this->query();
		if ( count( $values ) === 1 && isset( $values[$this->wikiId] ) ) {
			// Don't show the filter if there are no notifications from other wikis
			return [];
		}
		foreach ( $values as $key => $count ) {
			$res[] = $this->makeOption( $key, $count );
		}

		return array_filter( $res );
	}

	/**
	 * @param string $key
	 * @param int $count
	 * @return FilterBucketOption|null
	 */
	protected function makeOption( string $key, int $count ): ?FilterBucketOption {
		if ( !$count ) {
			return null;
		}
		if ( $key === $this->wikiId ) {
			return new FilterBucketOption(
				Message::newFromKey( 'notifyme-notification-center-filter-label-source-local' ),
				$this->wikiId,
				$count
			);
		}
		$wikiData = [
			'wiki_id' => $key,
			'display_text' => $key,
		];
		$this->hookContainer->run( 'GetWikiInfoFromWikiId', [ $key, &$wikiData ] );
		return new FilterBucketOption(
			new RawMessage( $wikiData['display_text'] ),
			$key,
			$count
		);
	}

	/**
	 * @return array
	 */
	protected function query(): array {
		$res = $this->store->rawQuery( $this->forUser, $this->forStatus, [ 'nwqs_wiki_id' ] );

		$counts = [ $this->wikiId => 0 ];
		foreach ( $res as $row ) {
			if ( !$row->nwqs_wiki_id ) {
				$counts[$this->wikiId]++;
				continue;
			}
			if ( !isset( $counts[$row->nwqs_wiki_id] ) ) {
				$counts[$row->nwqs_wiki_id] = 0;
			}
			$counts[$row->nwqs_wiki_id]++;
		}

		return $counts;
	}
}
