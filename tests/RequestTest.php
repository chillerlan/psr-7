<?php
/**
 * Class RequestTest
 *
 * @link https://github.com/guzzle/psr7/blob/4b981cdeb8c13d22a6c193554f8c686f53d5c958/tests/RequestTest.php
 *
 * @created      12.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */
declare(strict_types=1);

namespace chillerlan\HTTPTest\Psr7;

use PHPUnit\Framework\Attributes\Test;
use chillerlan\HTTP\Psr7\{Request, Uri};
use Fig\Http\Message\RequestMethodInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase{

	#[Test]
	public function requestUriMayBeString():void{
		$this::assertSame('/', (string)new Request(RequestMethodInterface::METHOD_GET, '/')->getUri());
	}

	#[Test]
	public function requestUriMayBeUri():void{
		$uri = new Uri('/');

		$this::assertSame($uri, new Request('GET', $uri)->getUri());
	}

	#[Test]
	public function validateRequestUri():void{
		$this->expectException(InvalidArgumentException::class);

		new Request('GET', '///');
	}

	#[Test]
	public function capitalizesMethod():void{
		$this::assertSame('GET', new Request('get', '/')->getMethod());
	}

	#[Test]
	public function capitalizesWithMethod():void{
		$this::assertSame('PUT', new Request('GET', '/')->withMethod('put')->getMethod());
	}

	#[Test]
	public function withUri():void{
		$request = new Request('GET', '/');
		$uri1    = $request->getUri();
		$uri2    = new Uri('https://www.example.com');

		$this::assertSame($uri1, $request->getUri());

		$request->withUri($uri2);

		$this::assertSame($uri2, $request->getUri());
	}

	#[Test]
	public function withRequestTarget():void{
		$request = new Request('GET', '/');

		$this::assertSame('/', $request->getRequestTarget());

		$request->withRequestTarget('*');

		$this::assertSame('*', $request->getRequestTarget());
	}

	#[Test]
	public function requestTargetDoesNotAllowSpaces():void{
		$this->expectException(InvalidArgumentException::class);

		new Request('GET', '/')->withRequestTarget('/foo bar');
	}

	#[Test]
	public function requestTargetDefaultsToSlash():void{
		$request = new Request('GET', '');
		$this::assertSame('/', $request->getRequestTarget());

		$request = new Request('GET', '*');
		$this::assertSame('*', $request->getRequestTarget());

		$request = new Request('GET', 'https://foo.com/bar baz/');
		$this::assertSame('/bar%20baz/', $request->getRequestTarget());
	}

	#[Test]
	public function buildsRequestTarget():void{
		$this::assertSame('/baz?bar=bam', new Request('GET', 'https://foo.com/baz?bar=bam')->getRequestTarget());
	}

	#[Test]
	public function buildsRequestTargetWithFalseyQuery():void{
		$this::assertSame('/baz?0', new Request('GET', 'https://foo.com/baz?0')->getRequestTarget());
	}

	#[Test]
	public function canGetHeaderAsCsv():void{
		$request = new Request('GET', 'https://foo.com/baz?bar=bam')->withHeader('Foo', ['a', 'b', 'c']);

		$this::assertSame('a, b, c', $request->getHeaderLine('Foo'));
		$this::assertSame('', $request->getHeaderLine('Bar'));
	}

	#[Test]
	public function overridesHostWithUri():void{
		$request = new Request('GET', 'https://foo.com/baz?bar=bam');
		$this::assertSame(['Host' => ['foo.com']], $request->getHeaders());

		$request->withUri(new Uri('https://www.baz.com/bar'));
		$this::assertSame('www.baz.com', $request->getHeaderLine('Host'));
	}

	#[Test]
	public function addsPortToHeader():void{
		$this::assertSame('foo.com:8124', new Request('GET', 'https://foo.com:8124/bar')->getHeaderLine('host'));
	}

	#[Test]
	public function addsPortToHeaderAndReplacePreviousPort():void{
		$request = new Request('GET', 'https://foo.com:8124/bar')
			->withUri(new Uri('https://foo.com:8125/bar'));

		$this::assertSame('foo.com:8125', $request->getHeaderLine('host'));
	}

	#[Test]
	public function withMethodEmptyMethod():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('HTTP method must not be empty');

		new Request('GET', '/foo')->withMethod('');
	}

}
