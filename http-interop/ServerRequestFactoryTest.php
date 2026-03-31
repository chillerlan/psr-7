<?php
/**
 * @author       http-factory-tests Contributors
 * @license      MIT
 * @link         https://github.com/http-interop/http-factory-tests
 */
declare(strict_types=1);

namespace Interop\Http\Factory;

use Generator;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use function class_exists;
use function defined;
use const UPLOAD_ERR_OK;

final class ServerRequestFactoryTest extends TestCase{

	protected ServerRequestFactoryInterface $serverRequestFactory;
	protected UriFactoryInterface           $uriFactory;

	/**
	 * @noinspection PhpUndefinedConstantInspection
	 * @phan-suppress PhanTypeMismatchArgumentNullableInternal, PhanNonClassMethodCall, PhanTypeExpectedObjectOrClassName
	 */
	public function setUp():void{

		if(!defined('SERVER_REQUEST_FACTORY') || !class_exists(SERVER_REQUEST_FACTORY)){
			self::markTestSkipped('Server Request factory class name not provided');
		}

		if(!defined('URI_FACTORY') || !class_exists(URI_FACTORY)){
			self::markTestSkipped('URI factory class name not provided');
		}

		$this->serverRequestFactory = new (SERVER_REQUEST_FACTORY);
		$this->uriFactory           = new (URI_FACTORY);
	}

	public static function serverParamProvider():Generator{
		foreach(RequestFactoryTest::HTTP_METHODS as $method){
			yield $method => [$method, 'https://example.org/test?foo=1&bar=true', 'example.org', '/test', 'foo=1&bar=true'];
		}
	}

	#[Test]
	#[DataProvider('serverParamProvider')]
	public function createServerRequest(string $method, string $uri, string $host, string $path, string $query):void{
		$request = $this->serverRequestFactory->createServerRequest($method, $uri);

		self::assertSame($method, $request->getMethod());
		self::assertSame($uri, (string)$request->getUri());
	}

	#[Test]
	#[DataProvider('serverParamProvider')]
	public function createServerRequestFromArray(string $method, string $uri, string $host, string $path, string $query):void{

		$serverParams = [
			'REQUEST_METHOD' => $method,
			'HTTP_HOST'      => $host,
			'REQUEST_URI'    => $path,
			'QUERY_STRING'   => $query,
		];

		$request = $this->serverRequestFactory->createServerRequest($method, $uri, $serverParams);

		self::assertSame($method, $request->getMethod());
		self::assertSame($uri, (string)$request->getUri());
	}

	#[Test]
	#[DataProvider('serverParamProvider')]
	public function createServerRequestWithUriObject(string $method, string $uri, string $host, string $path, string $query):void{
		$request = $this->serverRequestFactory->createServerRequest($method, $this->uriFactory->createUri($uri));

		self::assertSame($method, $request->getMethod());
		self::assertSame($uri, (string)$request->getUri());
	}

	#[Test]
	#[BackupGlobals(true)]
	public function createServerRequestDoesNotReadServerSuperglobal():void{
		$_SERVER = ['HTTP_X_FOO' => 'bar']; // phpcs:ignore

		$serverParams = [
			'REQUEST_METHOD' => 'PUT',
			'REQUEST_URI'    => '/test',
			'QUERY_STRING'   => 'super=0',
			'HTTP_HOST'      => 'example.org',
		];

		$request      = $this->serverRequestFactory->createServerRequest('PUT', '/test', $serverParams);
		$serverParams = $request->getServerParams();

		self::assertNotEquals($_SERVER, $serverParams); // phpcs:ignore
		self::assertArrayNotHasKey('HTTP_X_FOO', $serverParams);
	}

	#[Test]
	public function createServerRequestDoesNotReadCookieSuperglobal():void{
		$_COOKIE = ['foo' => 'bar']; // phpcs:ignore

		$request = $this->serverRequestFactory->createServerRequest('POST', 'https://example.org/test');

		self::assertEmpty($request->getCookieParams());
	}

	#[Test]
	public function createServerRequestDoesNotReadGetSuperglobal():void{
		$_GET = ['foo' => 'bar']; // phpcs:ignore

		$request = $this->serverRequestFactory->createServerRequest('POST', 'https://example.org/test');

		self::assertEmpty($request->getQueryParams());
	}

	#[Test]
	public function createServerRequestDoesNotReadFilesSuperglobal():void{

		$_FILES = [ // phpcs:ignore
			[
				'name'     => 'foobar.dat',
				'type'     => 'application/octet-stream',
				'tmp_name' => '/tmp/php45sd3f',
				'error'    => UPLOAD_ERR_OK,
				'size'     => 4,
			],
		];

		$request = $this->serverRequestFactory->createServerRequest('POST', 'https://example.org/test');

		self::assertEmpty($request->getUploadedFiles());
	}

	#[Test]
	public function createServerRequestDoesNotReadPostSuperglobal():void{
		$_POST = ['foo' => 'bar']; // phpcs:ignore

		$request = $this->serverRequestFactory->createServerRequest('POST', 'https://example.org/test');

		self::assertEmpty($request->getParsedBody());
	}

}
