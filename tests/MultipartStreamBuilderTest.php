<?php
/**
 * Class MultipartStreamBuilderTest
 *
 * @created      19.07.2023
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2023 smiley
 * @license      MIT
 */
declare(strict_types=1);

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\MultipartStreamBuilder;
use chillerlan\PHPUnitHttp\HttpFactoryTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class MultipartStreamBuilderTest extends TestCase{
	use HttpFactoryTrait;

	protected MultipartStreamBuilder $multipartStreamBuilder;

	protected function setUp():void{
		$this->initFactories();

		$this->multipartStreamBuilder = new MultipartStreamBuilder($this->streamFactory);
	}

	#[Test]
	public function createsDefaultBoundary():void{
		$this::assertMatchesRegularExpression('/^[a-f\d]{40}$/', $this->multipartStreamBuilder->getBoundary());
	}

	#[Test]
	public function setBoundary():void{
		$boundary = "0-9a-zA-Z'()+_,-./:=?";
		$this->multipartStreamBuilder->setBoundary($boundary);

		$this::assertSame($boundary, $this->multipartStreamBuilder->getBoundary());
	}

	#[Test]
	public function setBoundaryEmptyException():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The given boundary is empty');

		$this->multipartStreamBuilder->setBoundary('');
	}

	#[Test]
	public function setBoundaryInvalidCharException():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The given boundary contains illegal characters');

		$this->multipartStreamBuilder->setBoundary('foo#');
	}

	#[Test]
	public function reset():void{

		$this->multipartStreamBuilder
			->setBoundary('boundary')
			->addString('content a', 'a')
		;

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"a\"\r\n".
			"Content-Length: 9\r\n".
			"Content-Type: text/plain\r\n".
			"\r\n".
			"content a\r\n".
			"--boundary--\r\n",
			(string)$this->multipartStreamBuilder,
		);

		$this->multipartStreamBuilder->reset();

		$boundary = $this->multipartStreamBuilder->getBoundary();

		// phpcs:ignore
		$this::assertSame("--$boundary--\r\n", $this->multipartStreamBuilder->build()->getContents());
	}

	#[Test]
	public function canCreateEmptyBody():void{
		$this::assertMatchesRegularExpression("/--[a-f\d]{40}--\r\n/", $this->multipartStreamBuilder->build()->getContents());
	}

	#[Test]
	public function addFields():void{

		$this->multipartStreamBuilder
			->setBoundary('boundary')
			->addString('content a', 'a')
			->addString('content b', 'b')
		;

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"a\"\r\n".
			"Content-Length: 9\r\n".
			"Content-Type: text/plain\r\n".
			"\r\n".
			"content a\r\n".
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"b\"\r\n".
			"Content-Length: 9\r\n".
			"Content-Type: text/plain\r\n".
			"\r\n".
			"content b\r\n".
			"--boundary--\r\n",
			(string)$this->multipartStreamBuilder,
		);
	}

	#[Test]
	public function addStreams():void{

		$this->multipartStreamBuilder
			->setBoundary('boundary')
			->addStream($this->streamFactory->createStream('filestream a'), 'a', '/dir/a.txt')
			->addStream($this->streamFactory->createStream('filestream b'), 'b', '/foo/b.jpg')
		;

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"a\"; filename=\"a.txt\"\r\n".
			"Content-Length: 12\r\n".
			"Content-Type: text/plain\r\n".
			"\r\n".
			"filestream a\r\n".
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"b\"; filename=\"b.jpg\"\r\n".
			"Content-Length: 12\r\n".
			"Content-Type: image/jpeg\r\n".
			"\r\n".
			"filestream b\r\n".
			"--boundary--\r\n",
			(string)$this->multipartStreamBuilder,
		);
	}

	#[Test]
	public function addFieldWithSameName():void{

		$this->multipartStreamBuilder
			->setBoundary('boundary')
			->addString('aaa', 'samename', 'a.txt')
			->addString('bbb', 'samename', 'b.jpg')
		;

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"samename\"; filename=\"a.txt\"\r\n".
			"Content-Length: 3\r\n".
			"Content-Type: text/plain\r\n".
			"\r\n".
			"aaa\r\n".
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"samename\"; filename=\"b.jpg\"\r\n".
			"Content-Length: 3\r\n".
			"Content-Type: image/jpeg\r\n".
			"\r\n".
			"bbb\r\n".
			"--boundary--\r\n",
			(string)$this->multipartStreamBuilder,
		);
	}

	#[Test]
	public function givenFieldnameCannotBeEmptyException():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid form field name');

		$this->multipartStreamBuilder->addString('content', '');
	}

	#[Test]
	public function customHeaders():void{

		$this->multipartStreamBuilder
			->setBoundary('boundary')
			->addStream($this->streamFactory->createStream('filestream a'), 'a', '/dir/a.txt', [
				'x-foo'               => 'bar',
				'content-disposition' => 'custom',
			])
		;

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: custom\r\n".
			"Content-Length: 12\r\n".
			"Content-Type: text/plain\r\n".
			"X-Foo: bar\r\n".
			"\r\n".
			"filestream a\r\n".
			"--boundary--\r\n",
			(string)$this->multipartStreamBuilder,
		);
	}

	#[Test]
	public function customHeadersAndMultipleValues():void{

		$this->multipartStreamBuilder
			->setBoundary('boundary')
			->addStream($this->streamFactory->createStream('filestream a'), 'a', '/dir/a.txt', [
				'x-foo'               => 'bar',
				'content-disposition' => 'custom',
			])
			->addStream($this->streamFactory->createStream('filestream b'), 'b', '/dir/b.jpg', [
				'cOntenT-Type' => 'custom',
			])
		;

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: custom\r\n".
			"Content-Length: 12\r\n".
			"Content-Type: text/plain\r\n".
			"X-Foo: bar\r\n".
			"\r\n".
			"filestream a\r\n".
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"b\"; filename=\"b.jpg\"\r\n".
			"Content-Length: 12\r\n".
			"Content-Type: custom\r\n".
			"\r\n".
			"filestream b\r\n".
			"--boundary--\r\n",
			(string)$this->multipartStreamBuilder,
		);
	}

	#[Test]
	public function suppressContentTypeHeader():void{

		$this->multipartStreamBuilder
			->setBoundary('boundary')
			->addString(content: 'content a', fieldname: 'a', headers: ['Content-Type' => ''])
		;

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"a\"\r\n".
			"Content-Length: 9\r\n\r\nc".
			"ontent a\r\n".
			"--boundary--\r\n",
			(string)$this->multipartStreamBuilder,
		);

	}

	#[Test]
	public function ignoresNonContentNonCustomHeaders():void{

		$this->multipartStreamBuilder
			->setBoundary('boundary')
			->addString(content: 'content a', fieldname: 'a', headers: [
				'content-whatever' => 'yay',
				'nope'             => 'nah',
				'x-what'           => 'omg',
				'this'             => 'absolutely not',
			])
		;

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"a\"\r\n".
			"Content-Length: 9\r\n".
			"Content-Type: text/plain\r\n".
			"Content-Whatever: yay\r\n".
			"X-What: omg\r\n".
			"\r\n".
			"content a\r\n".
			"--boundary--\r\n",
			(string)$this->multipartStreamBuilder,
		);

	}

	#[Test]
	public function nesting():void{

		$mp1 = (clone $this->multipartStreamBuilder)
			->setBoundary('boundary-a')
			->addString('content a1', 'a1', 'a1.txt')
			->addString('content a2', 'a2', 'a2.jpg')
		;

		$mp2 = (clone $this->multipartStreamBuilder)
			->setBoundary('boundary-b')
			->addString('content b1', 'b1', 'b1.txt')
			->addStream(stream: $mp1->build(), headers: ['Content-Type' => 'multipart/form-data; boundary="boundary-a"'])
			->addString('content b2', 'b2', 'b2.jpg')
		;

		$this::assertSame(
			"--boundary-b\r\n".
			"Content-Disposition: form-data; name=\"b1\"; filename=\"b1.txt\"\r\n".
			"Content-Length: 10\r\n".
			"Content-Type: text/plain\r\n".
			"\r\n".
			"content b1\r\n".
			"--boundary-b\r\n".
			"Content-Length: 288\r\n".
			"Content-Type: multipart/form-data; boundary=\"boundary-a\"\r\n".
			"\r\n".
			"--boundary-a\r\n".
			"Content-Disposition: form-data; name=\"a1\"; filename=\"a1.txt\"\r\n".
			"Content-Length: 10\r\n".
			"Content-Type: text/plain\r\n".
			"\r\n".
			"content a1\r\n".
			"--boundary-a\r\n".
			"Content-Disposition: form-data; name=\"a2\"; filename=\"a2.jpg\"\r\n".
			"Content-Length: 10\r\n".
			"Content-Type: image/jpeg\r\n".
			"\r\n".
			"content a2\r\n".
			"--boundary-a--\r\n".
			// does this extra newline bother anyone or can we just ignore it??
			"\r\n".
			"--boundary-b\r\n".
			"Content-Disposition: form-data; name=\"b2\"; filename=\"b2.jpg\"\r\n".
			"Content-Length: 10\r\n".
			"Content-Type: image/jpeg\r\n".
			"\r\n".
			"content b2\r\n".
			"--boundary-b--\r\n",
			(string)$mp2,
		);

	}

	#[Test]
	public function buildWithMessageInterface():void{

		$request = $this->multipartStreamBuilder
			->setBoundary('boundary')
			->addStream($this->streamFactory->createStream('filestream a'), 'a', '/foo/a.jpg')
			->buildMessage($this->requestFactory->createRequest('POST', 'https://example.com/api/media'))
		;

		$this::assertTrue($request->hasHeader('content-type'));
		$this::assertSame('multipart/form-data; boundary="boundary"', $request->getHeaderLine('content-type'));

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"a\"; filename=\"a.jpg\"\r\n".
			"Content-Length: 12\r\n".
			"Content-Type: image/jpeg\r\n".
			"\r\n".
			"filestream a\r\n".
			"--boundary--\r\n",
			(string)$request->getBody(),
		);

	}

	#[Test]
	public function overwritesContentTypeHeaderInMessage():void{

		$originalRequest = $this->requestFactory
			->createRequest('POST', 'https://example.com/api/media')
			->withHeader('Content-Type', 'whatever')
		;

		$this::assertTrue($originalRequest->hasHeader('content-type'));
		$this::assertSame('whatever', $originalRequest->getHeaderLine('content-type'));

		$modifiedRequest = $this->multipartStreamBuilder
			->setBoundary('boundary')
			->addStream($this->streamFactory->createStream('filestream a'), 'a', '/foo/a.jpg')
			->buildMessage($originalRequest)
		;

		$this::assertTrue($modifiedRequest->hasHeader('content-type'));
		$this::assertSame('multipart/form-data; boundary="boundary"', $modifiedRequest->getHeaderLine('content-type'));
	}

}
