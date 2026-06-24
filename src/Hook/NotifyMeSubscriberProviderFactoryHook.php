<?php

namespace MediaWiki\Extension\NotifyMe\Hook;

use MediaWiki\Extension\NotifyMe\SubscriberProvider\SubscriberProviderFactory;

interface NotifyMeSubscriberProviderFactoryHook {

	/**
	 * @param SubscriberProviderFactory $factory
	 * @return mixed
	 */
	public function onNotifyMeSubscriberProviderFactory( SubscriberProviderFactory $factory );
}
