<?php

namespace MediaWiki\Extension\NotifyMe\Tests;

use MailAddress;
use MediaWiki\Extension\NotifyMe\Channel\Email\MailContentProvider;
use MediaWiki\Extension\NotifyMe\Channel\Email\ReplaceCID;
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

	/**
	 * @covers \MediaWiki\Extension\NotifyMe\Channel\Email\ReplaceCID::onUserMailerTransformMessage
	 *
	 * @return void
	 */
	public function testAttachesCidImagesToMimeBody(): void {
		$tempFile = tempnam( sys_get_temp_dir(), 'notifyme-cid-' );
		$this->assertNotFalse( $tempFile );
		file_put_contents( $tempFile, 'cid-image-data' );
		$file = new class ( $tempFile ) {
			/** @var string */
			private $path;

			/**
			 * @param string $path
			 */
			public function __construct( string $path ) {
				$this->path = $path;
			}

			/**
			 * @return string
			 */
			public function getLocalRefPath(): string {
				return $this->path;
			}

			/**
			 * @return string
			 */
			public function getMimeType(): string {
				return 'image/png';
			}

			/**
			 * @return string
			 */
			public function getName(): string {
				return 'Some Image.png';
			}
		};

		$mcpMock = $this->createMock( MailContentProvider::class );
		$mcpMock->method( 'getImages' )->willReturn( [ 'notifyme-cid-1' => $file ] );
		$replaceCid = new ReplaceCID( $mcpMock );

		$subject = 'Dummy';
		$headers = [
			'Content-type' => 'text/html; charset=UTF-8',
			'Content-transfer-encoding' => '8bit',
		];
		$error = '';
		$body = '<p><img src="cid:notifyme-cid-1"></p>';
		$replaceCid->onUserMailerTransformMessage(
			[ new MailAddress( 'foo', self::USERNAME ) ],
			new MailAddress( 'bar' ),
			$subject,
			$headers,
			$body,
			$error
		);

		unlink( $tempFile );

		$contentType = $this->getHeaderValue( $headers, 'Content-Type' );
		$this->assertNotNull( $contentType );
		$this->assertStringContainsString( 'multipart/related', $contentType );
		$this->assertStringContainsString( 'Content-ID: <notifyme-cid-1>', $body );
		$this->assertStringContainsString( base64_encode( 'cid-image-data' ), $body );
	}

	private function getText( string $string ) {
		return trim( file_get_contents( __DIR__ . '/data/' . $string ) );
	}

	/**
	 * @param array $headers
	 * @param string $headerName
	 *
	 * @return string|null
	 */
	private function getHeaderValue( array $headers, string $headerName ): ?string {
		foreach ( $headers as $name => $value ) {
			if ( strcasecmp( $name, $headerName ) === 0 ) {
				return $value;
			}
		}
		return null;
	}
}
