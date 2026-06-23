<?php
/**
 * Class FactoryUtilsTest
 *
 * @created      31.01.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */
declare(strict_types=1);

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\HTTPFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException, stdClass;
use function fopen, fseek, fwrite, simplexml_load_string;

class FactoryUtilsTest extends TestCase{

	#[Test]
	public function createStream():void{
		$stream = HTTPFactory::createStreamFromString('test');

		$this::assertSame('test', $stream->getContents());
	}

	#[Test]
	public function createStreamInvalidModeException():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('invalid mode for writing');

		HTTPFactory::createStreamFromString('test', 'r');
	}

	public static function streamInputProvider():array{
		$fh = fopen('php://temp', 'r+');

		fwrite($fh, 'resourcetest');
		fseek($fh, 0);

		$xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><root><foo>bar</foo></root>');

		return [
			'string'          => ['stringtest', 'stringtest'],
			'resource'        => [$fh, 'resourcetest'],
			'streaminterface' => [HTTPFactory::createStreamFromString('streaminterfacetest'), 'streaminterfacetest'],
			'tostring'        => [$xml->foo, 'bar'],
		];
	}

	#[Test]
	#[DataProvider('streamInputProvider')]
	public function createStreamFromInput(mixed $input, string $content):void{
		$this::assertSame($content, HTTPFactory::createStreamFromSource($input)->getContents());
	}

	#[Test]
	public function createStreamFromInputException():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid resource type: object');

		HTTPFactory::createStreamFromSource(new stdClass);
	}

}
