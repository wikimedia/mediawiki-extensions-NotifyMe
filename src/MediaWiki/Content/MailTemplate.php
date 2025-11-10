<?php

namespace MediaWiki\Extension\NotifyMe\MediaWiki\Content;

use Exception;
use MediaWiki\Content\TextContent;

class MailTemplate extends TextContent {

	/**
	 * @param string $text
	 *
	 * @throws Exception
	 */
	public function __construct( $text ) {
		parent::__construct( $text, 'mail_template' );
	}
}
