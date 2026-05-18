<?php

namespace MediaWiki\Extension\NotifyMe\Tests;

use MediaWiki\Title\Title;
use MWStake\MediaWiki\Component\Events\ITitleEvent;

class DummyTitleEvent extends DummyEvent implements ITitleEvent {

	/** @var Title */
	protected $title;

	/**
	 * @return array
	 */
	public function __serialize(): array {
		return parent::__serialize() + [ 'title' => $this->title ];
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function __unserialize( array $data ): void {
		parent::__unserialize( $data );
		$this->title = $data['title'];
	}

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
