<?php

namespace MediaWiki\Extension\NotifyMe\Tests;

use MWStake\MediaWiki\Component\Events\ITitleEvent;
use Title;

class DummyTitleEvent extends DummyEvent implements ITitleEvent {
	/** @var Title */
	private $title;

	/**
	 * @return Title
	 */
	public function getTitle(): Title {
		return $this->title;
	}

	/**
	 * @param Title $title
	 *
	 * @return void
	 */
	public function setTitle( Title $title ) {
		$this->title = $title;
	}

}
