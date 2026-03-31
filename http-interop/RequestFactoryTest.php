<?php
/**
 * @author       http-factory-tests Contributors
 * @license      MIT
 * @link         https://github.com/http-interop/http-factory-tests
 */
declare(strict_types=1);

namespace Interop\Http\Factory;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use function class_exists;
use function defined;

final class RequestFactoryTest extends TestCase{

	/** @var string[] */
	public const array HTTP_METHODS = [
		'GET',
		'POST',
		'PATCH',
		'PUT',
		'DELETE',
		'OPTIONS',
		'HEAD',
	];

	protected RequestFactoryInterface $requestFactory;
	protected UriFactoryInterface     $uriFactory;

	/**
	 * @noinspection PhpUndefinedConstantInspection
	 * @phan-suppress PhanTypeMismatchArgumentNullableInternal, PhanNonClassMethodCall, PhanTypeExpectedObjectOrClassName
	 */
	public function setUp():void{

		if(!defined('REQUEST_FACTORY') || !class_exists(REQUEST_FACTORY)){
			self::markTestSkipped('Request factory class name not provided');
		}

		if(!defined('URI_FACTORY') || !class_exists(URI_FACTORY)){
			self::markTestSkipped('URI_FACTORY class name not provided');
		}

		$this->requestFactory = new (REQUEST_FACTORY);
		$this->uriFactory     = new (URI_FACTORY);
	}

	public static function httpMethodProvider():Generator{
		foreach(self::HTTP_METHODS as $method){
			yield $method => [$method, 'https://example.com/'];
		}
	}

	#[Test]
	#[DataProvider('httpMethodProvider')]
	public function createRequest(string $method, string $uri):void{
		$request = $this->requestFactory->createRequest($method, $uri);

		self::assertSame($method, $request->getMethod());
		self::assertSame($uri, (string)$request->getUri());
	}

	#[Test]
	#[DataProvider('httpMethodProvider')]
	public function createRequestWithUri(string $method, string $uri):void{
		$request = $this->requestFactory->createRequest($method, $this->uriFactory->createUri($uri));

		self::assertSame($method, $request->getMethod());
		self::assertSame($uri, (string)$request->getUri());
	}

}
