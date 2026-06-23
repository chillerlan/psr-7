<?php
/**
 * Class MessageTest
 *
 * @created      26.07.2023
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2023 smiley
 * @license      MIT
 */
declare(strict_types=1);

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\Message;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class MessageTest extends TestCase{

	#[Test]
	public function nullBody():void{
		$message = new Message;

		$this::assertInstanceOf(StreamInterface::class, $message->getBody());
		$this::assertSame('', (string)$message->getBody());
	}

	#[Test]
	public function returnsEmptyHeadersArray():void{
		$message = new Message;

		$this::assertEmpty($message->getHeaders());
	}

	#[Test]
	public function withHeader():void{
		$message = new Message()->withHeader('Foo', 'Bar');

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());

		$message = $message->withHeader('baZ', 'Bam');

		$this::assertSame(['Foo' => ['Bar'], 'baZ' => ['Bam']], $message->getHeaders());
		$this::assertSame('Bam', $message->getHeaderLine('baz'));
		$this::assertSame(['Bam'], $message->getHeader('baz'));
	}

	#[Test]
	public function withHeaderAsArray():void{
		$message = new Message()->withHeader('Foo', 'Bar');

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());

		$message = $message->withHeader('baZ', ['Bam', 'Bar']);

		$this::assertSame(['Foo' => ['Bar'], 'baZ' => ['Bam', 'Bar']], $message->getHeaders());
		$this::assertSame('Bam, Bar', $message->getHeaderLine('baz'));
		$this::assertSame(['Bam', 'Bar'], $message->getHeader('baz'));
	}

	#[Test]
	public function withHeaderReplacesDifferentCase():void{
		$message = new Message()->withHeader('Foo', 'Bar');

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());

		$message = $message->withHeader('foO', 'Bam');

		$this::assertSame(['foO' => ['Bam']], $message->getHeaders());
		$this::assertSame('Bam', $message->getHeaderLine('foo'));
		$this::assertSame(['Bam'], $message->getHeader('foo'));
	}

	#[Test]
	public function withAddedHeader():void{
		$message = new Message()->withHeader('Foo', 'Bar');

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());

		$message = $message->withAddedHeader('foO', 'Baz');

		$this::assertSame(['Foo' => ['Bar', 'Baz']], $message->getHeaders());
		$this::assertSame('Bar, Baz', $message->getHeaderLine('foo'));
		$this::assertSame(['Bar', 'Baz'], $message->getHeader('foo'));
	}

	#[Test]
	public function withAddedHeaderAsArray():void{
		$message = new Message()->withHeader('Foo', 'Bar');

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());

		$message = $message->withAddedHeader('foO', ['Baz', 'Bam']);

		$this::assertSame(['Foo' => ['Bar', 'Baz', 'Bam']], $message->getHeaders());
		$this::assertSame('Bar, Baz, Bam', $message->getHeaderLine('foo'));
		$this::assertSame(['Bar', 'Baz', 'Bam'], $message->getHeader('foo'));
	}

	#[Test]
	public function withAddedHeaderThatDoesNotExist():void{
		$message = new Message()->withHeader('Foo', 'Bar');

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());

		$message = $message->withAddedHeader('nEw', 'Baz');

		$this::assertSame(['Foo' => ['Bar'], 'nEw' => ['Baz']], $message->getHeaders());
		$this::assertSame('Baz', $message->getHeaderLine('new'));
		$this::assertSame(['Baz'], $message->getHeader('new'));
	}

	#[Test]
	public function withoutHeaderThatExists():void{

		$message = new Message()
			->withHeader('Foo', 'Bar')
			->withHeader('Baz', 'Bam')
		;

		$this::assertTrue($message->hasHeader('foo'));
		$this::assertSame(['Foo' => ['Bar'], 'Baz' => ['Bam']], $message->getHeaders());

		$message = $message->withoutHeader('foO');

		$this::assertFalse($message->hasHeader('foo'));
		$this::assertSame(['Baz' => ['Bam']], $message->getHeaders());
	}

	#[Test]
	public function withoutHeaderThatDoesNotExist():void{

		$message = new Message()
			->withHeader('Baz', 'Bam')
			->withoutHeader('foO')
		;

		$this::assertSame($message, $message);
		$this::assertFalse($message->hasHeader('foo'));
		$this::assertSame(['Baz' => ['Bam']], $message->getHeaders());
	}

	#[Test]
	public function headerValuesAreTrimmed():void{
		$message1 = new Message()->withHeader('Bar', " \t \tFoo\t \t ");
		$message2 = new Message()->withAddedHeader('Bar', " \t \tFoo\t \t ");

		foreach([$message1, $message2] as $message){
			$this::assertSame(['Bar' => ['Foo']], $message->getHeaders());
			$this::assertSame('Foo', $message->getHeaderLine('Bar'));
			$this::assertSame(['Foo'], $message->getHeader('Bar'));
		}
	}

	#[Test]
	public function supportNumericHeaderValues():void{
		/** @phan-suppress-next-line PhanTypeMismatchArgumentProbablyReal */
		$message = new Message()->withHeader('Content-Length', 69);

		$this::assertSame(['Content-Length' => ['69']], $message->getHeaders());
		$this::assertSame('69', $message->getHeaderLine('Content-Length'));
	}

	#[Test]
	public function headerNameAndValueDoesNotContainCRLF():void{

		$message = new Message()
			->withHeader("\rF\n\ro\r\n\r\no", "\rB\r\n\r\na\n\rr")
			->withAddedHeader("\rB\r\n\r\na\n\rr", "\rF\n\ro\r\n\r\no")
		;

		$this::assertSame('Bar', $message->getHeaderLine('Foo'));
		$this::assertSame('Foo', $message->getHeaderLine('Bar'));
	}

}
