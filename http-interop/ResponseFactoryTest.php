<?php
/**
 * @author       http-factory-tests Contributors
 * @license      MIT
 * @link         https://github.com/http-interop/http-factory-tests
 */
declare(strict_types=1);

namespace Interop\Http\Factory;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use function class_exists;
use function defined;

final class ResponseFactoryTest extends TestCase{

	protected ResponseFactoryInterface $responseFactory;

	/**
	 * @noinspection PhpUndefinedConstantInspection
	 * @phan-suppress PhanTypeMismatchArgumentNullableInternal, PhanNonClassMethodCall, PhanTypeExpectedObjectOrClassName
	 */
	protected function setUp():void{

		if(!defined('RESPONSE_FACTORY') || !class_exists(RESPONSE_FACTORY)){
			self::markTestSkipped('RESPONSE_FACTORY class name not provided');
		}

		$this->responseFactory = new (RESPONSE_FACTORY);
	}

	public static function httpResponseCodeProvider():array{
		return [
			'HTTP/200' => [200],
			'HTTP/301' => [301],
			'HTTP/404' => [404],
			'HTTP/500' => [500],
		];
	}

	#[Test]
	#[DataProvider('httpResponseCodeProvider')]
	public function createResponse(int $code):void{
		$response = $this->responseFactory->createResponse($code);

		self::assertSame($code, $response->getStatusCode());
	}

}
