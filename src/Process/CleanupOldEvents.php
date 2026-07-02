<?php

namespace MediaWiki\Extension\NotifyMe\Process;

use MediaWiki\Extension\NotifyMe\Storage\NotificationStore;
use MWStake\MediaWiki\Component\ProcessManager\IProcessStep;
use Psr\Log\LoggerInterface;

class CleanupOldEvents implements IProcessStep {

	private const EXPIRATION_PERIOD = 30;

	/**
	 * @param NotificationStore $store
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		private readonly NotificationStore $store,
		private readonly LoggerInterface $logger
	) {
	}

	/**
	 * @param array $data
	 * @return array|string[]
	 * @throws \Exception
	 */
	public function execute( $data = [] ): array {
		$res = $this->store->cleanUpOld( static::EXPIRATION_PERIOD );

		if ( $res ) {
			$this->logger->info( 'Cleaned up {count} old notifications', [
				'count' => $res,
			] );
		}

		return [ 'cleanedUp' => $res ];
	}
}
