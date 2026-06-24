<?php

namespace MediaWiki\Extension\NotifyMe\SubscriberProvider;

use Exception;
use MediaWiki\Extension\NotifyMe\ISubscriberProvider;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerAwareInterface;

class SubscriberProviderFactory {

	/**
	 * @var array|null
	 */
	private ?array $providers = null;

	/**
	 * @param array $attribute
	 * @param MediaWikiServices $services
	 */
	public function __construct(
		private readonly array $attribute,
		private readonly MediaWikiServices $services
	) {
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getProviders(): array {
		$this->assertLoaded();
		return $this->providers;
	}

	/**
	 * @param ISubscriberProvider $subscriberProvider
	 * @return void
	 */
	public function registerProvider( ISubscriberProvider $subscriberProvider ) {
		$this->providers[$subscriberProvider->getKey()] = $subscriberProvider;
	}

	/**
	 * @param string $key
	 * @return void
	 */
	public function unregisterProvider( string $key ): void {
		if ( isset( $this->providers[$key] ) ) {
			unset( $this->providers[$key] );
		}
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	private function assertLoaded(): void {
		if ( $this->providers === null ) {
			$this->registerFromAttribute();
			$this->services->getHookContainer()->run( 'NotifyMeSubscriberProviderFactory', [ $this ] );
		}
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	private function registerFromAttribute(): void {
		$this->providers = [];
		foreach ( $this->attribute as $provider ) {
			$provider = $this->services->getObjectFactory()->createObject( $provider );
			if ( !( $provider instanceof ISubscriberProvider ) ) {
				continue;
			}
			$this->registerProvider( $provider );
			if ( $provider instanceof LoggerAwareInterface ) {
				$provider->setLogger( $this->services->getService( 'NotifyMe.Logger' ) );
			}
		}
	}
}
