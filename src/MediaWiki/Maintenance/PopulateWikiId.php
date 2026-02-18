<?php

namespace MediaWiki\Extension\NotifyMe\MediaWiki\Maintenance;

use Exception;
use MediaWiki\Maintenance\LoggedUpdateMaintenance;
use MediaWiki\WikiMap\WikiMap;

require_once __DIR__ . '/../../../../../maintenance/Maintenance.php';

class PopulateWikiId extends LoggedUpdateMaintenance {

	/**
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'notifications-populate-wiki-id3';
	}

	/**
	 * @return bool|void
	 * @throws Exception
	 */
	protected function doDBUpdates() {
		$this->output( "...Populate wiki_id for NotifyMe..." );

		$wikiId = WikiMap::getCurrentWikiId();
		$dbw = $this->getDB( DB_PRIMARY );

		$dbw->newUpdateQueryBuilder()
			->update( 'notifications_instance' )
			->set( [ 'ni_wiki_id' => $wikiId ] )
			->where( [ 'ni_wiki_id' => null ] )
			->caller( 'popuplateWikiId' )
			->execute();

		$dbw->newUpdateQueryBuilder()
			->update( 'notifications_web_query_store' )
			->set( [ 'nwqs_wiki_id' => $wikiId ] )
			->where( [ 'nwqs_wiki_id' => '' ] )
			->caller( 'popuplateWikiId' )
			->execute();

		$this->output( 'done.' );
		return true;
	}
}

$maintClass = PopulateWikiId::class;
require_once RUN_MAINTENANCE_IF_MAIN;
