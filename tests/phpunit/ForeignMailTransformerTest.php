<?php

namespace MediaWiki\Extension\NotifyMe\Tests;

use MediaWiki\Extension\NotifyMe\Channel\Email\MailContentProvider;
use MediaWiki\Extension\NotifyMe\MediaWiki\ForeignMail\Transformer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\NotifyMe\MediaWiki\ForeignMail\Transformer::onUserMailerTransformMessage
 */
class ForeignMailTransformerTest extends TestCase {

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

		$transformer = new Transformer( $mcpMock );
		$subject = 'Dummy';
		$headers = [
			'Content-type' => 'text/plain; charset=UTF-8'
		];
		$error = '';
		$transformer->onUserMailerTransformMessage(
			[ new \MailAddress( 'foo' ) ],
			new \MailAddress( 'bar' ),
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
