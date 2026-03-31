<?php
/**
 * @author       http-factory-tests Contributors
 * @license      MIT
 * @link         https://github.com/http-interop/http-factory-tests
 */
declare(strict_types=1);

namespace Interop\Http\Factory;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriFactoryInterface;
use function class_exists;
use function defined;

final class UriFactoryTest extends TestCase{

	protected UriFactoryInterface $uriFactory;

	/**
	 * @noinspection PhpUndefinedConstantInspection
	 * @phan-suppress PhanTypeMismatchArgumentNullableInternal, PhanNonClassMethodCall, PhanTypeExpectedObjectOrClassName
	 */
	protected function setUp():void{

		if(!defined('URI_FACTORY') || !class_exists(URI_FACTORY)){
			self::markTestSkipped('URI_FACTORY class name not provided');
		}

		$this->uriFactory = new (URI_FACTORY);
	}

	#[Test]
	public function createUri():void{
		$uriString = 'https://example.com/';
		$uri       = $this->uriFactory->createUri($uriString);

		self::assertSame($uriString, (string)$uri);
	}

	#[Test]
	public function exceptionWhenUriIsInvalid():void{
		$this->expectException(InvalidArgumentException::class);
		$this->uriFactory->createUri(':');
	}

}
