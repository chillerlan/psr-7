<?php
/**
 * @author       http-factory-tests Contributors
 * @license      MIT
 * @link         https://github.com/http-interop/http-factory-tests
 */
declare(strict_types=1);

namespace Interop\Http\Factory;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use function class_exists;
use function defined;
use function strlen;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

final class UploadedFileFactoryTest extends TestCase{

	protected UploadedFileFactoryInterface $uploadedFileFactory;
	protected StreamFactoryInterface       $streamFactory;

	/**
	 * @noinspection PhpUndefinedConstantInspection
	 * @phan-suppress PhanTypeMismatchArgumentNullableInternal, PhanNonClassMethodCall, PhanTypeExpectedObjectOrClassName
	 */
	public function setUp():void{

		if(!defined('UPLOADED_FILE_FACTORY') || !class_exists(UPLOADED_FILE_FACTORY)){
			self::markTestSkipped('UPLOADED_FILE_FACTORY class name not provided');
		}

		if(!defined('STREAM_FACTORY') || !class_exists(STREAM_FACTORY)){
			self::markTestSkipped('STREAM_FACTORY class name not provided');
		}

		$this->uploadedFileFactory = new (UPLOADED_FILE_FACTORY);
		$this->streamFactory       = new (STREAM_FACTORY);
	}

	#[Test]
	public function createUploadedFileWithClientFilenameAndMediaType():void{
		$content         = 'this is your capitan speaking';
		$upload          = $this->streamFactory->createStream($content);
		$error           = UPLOAD_ERR_OK;
		$clientFilename  = 'test.txt';
		$clientMediaType = 'text/plain';

		$file = $this->uploadedFileFactory->createUploadedFile($upload, null, $error, $clientFilename, $clientMediaType);

		self::assertSame($content, (string)$file->getStream());
		self::assertSame(strlen($content), $file->getSize());
		self::assertSame($error, $file->getError());
		self::assertSame($clientFilename, $file->getClientFilename());
		self::assertSame($clientMediaType, $file->getClientMediaType());
	}

	#[Test]
	public function createUploadedFileWithError():void{
		$upload = $this->streamFactory->createStream('foobar');
		$error  = UPLOAD_ERR_NO_FILE;
		$file   = $this->uploadedFileFactory->createUploadedFile($upload, null, $error);

		self::assertSame($error, $file->getError());
	}

}
