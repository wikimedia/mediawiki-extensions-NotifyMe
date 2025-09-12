<?php

namespace MediaWiki\Extension\NotifyMe\Tests;

use MailAddress;
use MediaWiki\Extension\NotifyMe\Channel\Email\MailContentProvider;
use MediaWiki\Extension\NotifyMe\MediaWiki\ForeignMail\Transformer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \MediaWiki\Extension\NotifyMe\MediaWiki\ForeignMail\Transformer::onUserMailerTransformMessage
 */
class ForeignMailTransformerTest extends TestCase {

	private const USERNAME = 'Test.User';

	/**
	 * @covers \MediaWiki\Extension\NotifyMe\MediaWiki\ForeignMail\Transformer::onUserMailerTransformMessage
	 *
	 * @dataProvider provideData
	 *
	 * @return void
	 */
	public function testOnUserMailerTransformMessage( $input, $expected ) {
		$mcpMock = $this->createMock( MailContentProvider::class );
		$mcpMock->method( 'wrap' )->willReturnCallback( static function ( $body, $user ) {
			return $body;
		} );
		$loggerpMock = $this->createMock( LoggerInterface::class );

		$transformer = new Transformer( $mcpMock, $loggerpMock );
		$subject = 'Dummy';
		$headers = [
			'Content-type' => 'text/plain; charset=UTF-8'
		];
		$error = '';
		$transformer->onUserMailerTransformMessage(
			[ new MailAddress( 'foo', self::USERNAME ) ],
			new MailAddress( 'bar' ),
			$subject, $headers, $input, $error
		);

		$this->assertSame( $expected, $input );
	}

	public function provideData() {
		return [
			'generic' => [
				'input' => $this->getText( 'plaintextGeneric.txt' ),
				'expected' => $this->getText( 'htmlGeneric.html' ),
			],
			'reset-pass' => [
				'input' => $this->getText( 'plaintextResetPass.txt' ),
				'expected' => $this->getText( 'htmlResetPass.html' ),
			],
		];
	}

	private function getText( string $string ) {
		return trim( file_get_contents( __DIR__ . '/data/' . $string ) );
	}
}
