<?php

namespace MediaWiki\Extension\NotifyMe\MediaWiki\Hook;

use MediaWiki\Extension\NotifyMe\MediaWiki\Maintenance\AddDefaultMailTemplates;
use MediaWiki\Extension\NotifyMe\MediaWiki\Maintenance\PopulateWikiId;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class RunDatabaseUpdates implements LoadExtensionSchemaUpdatesHook {

	/**
	 * @inheritDoc
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$dbType = $updater->getDB()->getType();
		$dir = dirname( __DIR__, 3 );

		$updater->addExtensionTable(
			'notifications_event',
			"$dir/db/$dbType/notifications_event.sql"
		);
		$updater->addExtensionTable(
			'notifications_instance',
			"$dir/db/$dbType/notifications_instance.sql"
		);
		$updater->addExtensionTable(
			'notifications_web_query_store',
			"$dir/db/$dbType/notifications_web_query_store.sql"
		);

		$updater->dropExtensionIndex(
			'notifications_event',
			'notifications_event_key_timestamp',
			"$dir/db/$dbType/drop_event_index.sql"
		);

		$updater->addExtensionField(
			'notifications_instance',
			'ni_wiki_id',
			"$dir/db/$dbType/notifications_instance_patch_wiki_id.sql"
		);

		$updater->addExtensionField(
			'notifications_web_query_store',
			'nwqs_wiki_id',
			"$dir/db/$dbType/notifications_web_query_store_patch_wiki_id.sql"
		);

		$updater->addPostDatabaseUpdateMaintenance(
			AddDefaultMailTemplates::class
		);
		$updater->addPostDatabaseUpdateMaintenance( PopulateWikiId::class );
		return true;
	}
}
