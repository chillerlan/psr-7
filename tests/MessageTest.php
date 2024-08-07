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
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class MessageTest extends TestCase{

	public function testNullBody():void{
		$message = new Message;

		$this::assertInstanceOf(StreamInterface::class, $message->getBody());
		$this::assertSame('', (string)$message->getBody());
	}

	public function testReturnsEmptyHeadersArray():void{
		$message = new Message;

		$this::assertEmpty($message->getHeaders());
	}

	public function testWithHeader():void{
		$message  = (new Message)->withHeader('Foo', 'Bar');

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());

		$message = $message->withHeader('baZ', 'Bam');

		$this::assertSame(['Foo' => ['Bar'], 'baZ' => ['Bam']], $message->getHeaders());
		$this::assertSame('Bam', $message->getHeaderLine('baz'));
		$this::assertSame(['Bam'], $message->getHeader('baz'));
	}

	public function testWithHeaderAsArray():void{
		$message  = (new Message)->withHeader('Foo', 'Bar');

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());

		$message = $message->withHeader('baZ', ['Bam', 'Bar']);

		$this::assertSame(['Foo' => ['Bar'], 'baZ' => ['Bam', 'Bar']], $message->getHeaders());
		$this::assertSame('Bam, Bar', $message->getHeaderLine('baz'));
		$this::assertSame(['Bam', 'Bar'], $message->getHeader('baz'));
	}

	public function testWithHeaderReplacesDifferentCase():void{
		$message  = (new Message)->withHeader('Foo', 'Bar');

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());

		$message = $message->withHeader('foO', 'Bam');

		$this::assertSame(['foO' => ['Bam']], $message->getHeaders());
		$this::assertSame('Bam', $message->getHeaderLine('foo'));
		$this::assertSame(['Bam'], $message->getHeader('foo'));
	}

	public function testWithAddedHeader():void{
		$message  = (new Message)->withHeader('Foo', 'Bar');

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());

		$message = $message->withAddedHeader('foO', 'Baz');

		$this::assertSame(['Foo' => ['Bar', 'Baz']], $message->getHeaders());
		$this::assertSame('Bar, Baz', $message->getHeaderLine('foo'));
		$this::assertSame(['Bar', 'Baz'], $message->getHeader('foo'));
	}

	public function testWithAddedHeaderAsArray():void{
		$message  = (new Message)->withHeader('Foo', 'Bar');

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());

		$message = $message->withAddedHeader('foO', ['Baz', 'Bam']);

		$this::assertSame(['Foo' => ['Bar', 'Baz', 'Bam']], $message->getHeaders());
		$this::assertSame('Bar, Baz, Bam', $message->getHeaderLine('foo'));
		$this::assertSame(['Bar', 'Baz', 'Bam'], $message->getHeader('foo'));
	}

	public function testWithAddedHeaderThatDoesNotExist():void{
		$message = (new Message)->withHeader('Foo', 'Bar');

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());

		$message = $message->withAddedHeader('nEw', 'Baz');

		$this::assertSame(['Foo' => ['Bar'], 'nEw' => ['Baz']], $message->getHeaders());
		$this::assertSame('Baz', $message->getHeaderLine('new'));
		$this::assertSame(['Baz'], $message->getHeader('new'));
	}

	public function testWithoutHeaderThatExists():void{

		$message  = (new Message)
			->withHeader('Foo', 'Bar')
			->withHeader('Baz', 'Bam')
		;

		$this::assertTrue($message->hasHeader('foo'));
		$this::assertSame(['Foo' => ['Bar'], 'Baz' => ['Bam']], $message->getHeaders());

		$message = $message->withoutHeader('foO');

		$this::assertFalse($message->hasHeader('foo'));
		$this::assertSame(['Baz' => ['Bam']], $message->getHeaders());
	}

	public function testWithoutHeaderThatDoesNotExist():void{

		$message = (new Message)
			->withHeader('Baz', 'Bam')
			->withoutHeader('foO')
		;

		$this::assertSame($message, $message);
		$this::assertFalse($message->hasHeader('foo'));
		$this::assertSame(['Baz' => ['Bam']], $message->getHeaders());
	}

	public function testHeaderValuesAreTrimmed():void{
		$message1 = (new Message)->withHeader('Bar', " \t \tFoo\t \t ");
		$message2 = (new Message)->withAddedHeader('Bar', " \t \tFoo\t \t ");

		foreach([$message1, $message2] as $message){
			$this::assertSame(['Bar' => ['Foo']], $message->getHeaders());
			$this::assertSame('Foo', $message->getHeaderLine('Bar'));
			$this::assertSame(['Foo'], $message->getHeader('Bar'));
		}
	}

	public function testSupportNumericHeaderValues():void{
		/** @phan-suppress-next-line PhanTypeMismatchArgumentProbablyReal */
		$message = (new Message)->withHeader('Content-Length', 69);

		$this::assertSame(['Content-Length' => ['69']], $message->getHeaders());
		$this::assertSame('69', $message->getHeaderLine('Content-Length'));
	}

	public function testHeaderNameAndValueDoesNotContainCRLF():void{

		$message  = (new Message)
			->withHeader("\rF\n\ro\r\n\r\no", "\rB\r\n\r\na\n\rr")
			->withAddedHeader("\rB\r\n\r\na\n\rr", "\rF\n\ro\r\n\r\no")
		;

		$this::assertSame('Bar', $message->getHeaderLine('Foo'));
		$this::assertSame('Foo', $message->getHeaderLine('Bar'));
	}

}
