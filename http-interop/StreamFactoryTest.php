<?php
/**
 * @author       http-factory-tests Contributors
 * @license      MIT
 * @link         https://github.com/http-interop/http-factory-tests
 */
declare(strict_types=1);

namespace Interop\Http\Factory;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use function class_exists;
use function defined;
use function fclose;
use function file_exists;
use function file_put_contents;
use function fopen;
use function fseek;
use function ftell;
use function fwrite;
use function rewind;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;
use const SEEK_END;
use const SEEK_SET;

final class StreamFactoryTest extends TestCase{

	protected StreamFactoryInterface $streamFactory;
	/** @var string[] */
	protected static array           $tempFiles = [];

	/**
	 * @noinspection PhpUndefinedConstantInspection
	 * @phan-suppress PhanTypeMismatchArgumentNullableInternal, PhanNonClassMethodCall, PhanTypeExpectedObjectOrClassName
	 */
	protected function setUp():void{

		if(!defined('STREAM_FACTORY') || !class_exists(STREAM_FACTORY)){
			self::markTestSkipped('Stream factory class name not provided');
		}

		$this->streamFactory = new (STREAM_FACTORY);
	}

	public static function tearDownAfterClass():void{
		foreach(self::$tempFiles as $tempFile){
			if(file_exists($tempFile)){
				unlink($tempFile);
			}
		}
	}

	protected function createTemporaryFile():string{
		$file = tempnam(sys_get_temp_dir(), 'http_factory_tests_');

		if($file === false){
			throw new RuntimeException('could not create temp file');
		}

		self::$tempFiles[] = $file;

		return $file;
	}

	/**
	 * @return resource
	 */
	protected function createTemporaryResource(string|null $content = null){
		$file     = $this->createTemporaryFile();
		$resource = fopen($file, 'r+');

		if($content){
			fwrite($resource, $content);
			rewind($resource);
		}

		return $resource;
	}

	#[Test]
	public function createStreamWithoutArgument():void{
		$stream = $this->streamFactory->createStream();

		self::assertSame('', (string)$stream);
	}

	#[Test]
	public function createStreamWithEmptyString():void{
		$string = '';
		$stream = $this->streamFactory->createStream($string);

		self::assertSame($string, (string)$stream);
	}

	#[Test]
	public function createStreamWithASCIIString():void{
		$string = 'would you like some crumpets?';
		$stream = $this->streamFactory->createStream($string);

		self::assertSame($string, (string)$stream);
	}

	#[Test]
	public function createStreamWithMultiByteMultiLineString():void{
		$string = "would you\r\nlike some\n\u{1F950}?";
		$stream = $this->streamFactory->createStream($string);

		self::assertSame($string, (string)$stream);
	}

	#[Test]
	public function createStreamCursorPosition():void{
		$string = 'would you like some crumpets?';
		$stream = $this->streamFactory->createStream($string);

		self::assertSame(29, $stream->tell());

		$this->markTestIncomplete('This behaviour has not been specified by PHP-FIG yet.');
	}

	#[Test]
	public function createStreamFromFile():void{
		$string   = 'would you like some crumpets?';
		$filename = $this->createTemporaryFile();

		file_put_contents($filename, $string);

		$stream = $this->streamFactory->createStreamFromFile($filename);

		self::assertSame($string, (string)$stream);
	}

	#[Test]
	public function createStreamFromNonExistingFile():void{
		$filename = $this->createTemporaryFile();

		unlink($filename);

		$this->expectException(RuntimeException::class);
		$this->streamFactory->createStreamFromFile($filename);
	}

	#[Test]
	public function createStreamFromInvalidFileName():void{
		$this->expectException(RuntimeException::class);
		$this->streamFactory->createStreamFromFile('');
	}

	#[Test]
	public function createStreamFromFileIsReadOnlyByDefault():void{
		$string   = 'would you like some crumpets?';
		$filename = $this->createTemporaryFile();
		$stream   = $this->streamFactory->createStreamFromFile($filename);

		$this->expectException(RuntimeException::class);
		$stream->write($string);
	}

	#[Test]
	public function createStreamFromFileWithWriteOnlyMode():void{
		$filename = $this->createTemporaryFile();
		$stream   = $this->streamFactory->createStreamFromFile($filename, 'w');

		$this->expectException(RuntimeException::class);
		$stream->read(1);
	}

	#[Test]
	public function createStreamFromFileWithNoMode():void{
		$filename = $this->createTemporaryFile();

		$this->expectException(Exception::class);
		$this->streamFactory->createStreamFromFile($filename, '');
	}

	#[Test]
	public function createStreamFromFileWithInvalidMode():void{
		$filename = $this->createTemporaryFile();

		$this->expectException(Exception::class);
		$this->streamFactory->createStreamFromFile($filename, "\u{2620}");
	}

	#[Test]
	public function createStreamFromFileCursorPosition():void{
		$string   = 'would you like some crumpets?';
		$filename = $this->createTemporaryFile();

		file_put_contents($filename, $string);

		$resource  = fopen($filename, 'r');
		$fopenTell = ftell($resource);

		fclose($resource);

		$stream = $this->streamFactory->createStreamFromFile($filename);

		self::assertSame($fopenTell, $stream->tell());
	}

	#[Test]
	public function createStreamFromResource():void{
		$string   = 'would you like some crumpets?';
		$resource = $this->createTemporaryResource($string);
		$stream   = $this->streamFactory->createStreamFromResource($resource);

		self::assertSame($string, (string)$stream);
	}

	public static function cursorPositionProvider():array{
		$string = 'would you like some crumpets?';

		return [
			'SEEK_SET, 0'  => [$string, 0, SEEK_SET, 0],
			'SEEK_END, 0'  => [$string, 0, SEEK_END, 29],
			'SEEK_SET, 15' => [$string, 15, SEEK_SET, 15],
		];
	}

	#[Test]
	#[DataProvider('cursorPositionProvider')]
	public function createStreamFromResourceCursorPosition(string $string, int $seek, int $whence, int $expected):void{
		$resource = $this->createTemporaryResource($string);

		fseek($resource, $seek, $whence);

		$stream = $this->streamFactory->createStreamFromResource($resource);

		self::assertSame($expected, $stream->tell());
	}

}
