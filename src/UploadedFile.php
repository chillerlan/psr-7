<?php
/**
 * Class UploadedFile
 *
 * @created      11.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */
declare(strict_types=1);

namespace chillerlan\HTTP\Psr7;

use Psr\Http\Message\{StreamFactoryInterface, StreamInterface, UploadedFileInterface};
use InvalidArgumentException, RuntimeException;
use function in_array, is_file, is_string, is_writable, move_uploaded_file, rename;
use const PHP_SAPI, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION, UPLOAD_ERR_FORM_SIZE, UPLOAD_ERR_INI_SIZE,
	UPLOAD_ERR_NO_FILE, UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_OK, UPLOAD_ERR_PARTIAL;

/**
 * Implements an uploaded file object
 */
class UploadedFile implements UploadedFileInterface{

	/** @var int[] */
	public const UPLOAD_ERRORS = [
		UPLOAD_ERR_OK,
		UPLOAD_ERR_INI_SIZE,
		UPLOAD_ERR_FORM_SIZE,
		UPLOAD_ERR_PARTIAL,
		UPLOAD_ERR_NO_FILE,
		UPLOAD_ERR_NO_TMP_DIR,
		UPLOAD_ERR_CANT_WRITE,
		UPLOAD_ERR_EXTENSION,
	];

	protected StreamFactoryInterface $streamFactory;
	protected StreamInterface|null   $stream;
	protected int                    $size;
	protected int                    $error;
	protected string|null            $filename = null;
	protected string|null            $mediaType = null;
	protected string|null            $file  = null;
	protected bool                   $moved = false;

	/**
	 * @throws \InvalidArgumentException
	 */
	public function __construct(
		mixed                  $file,
		int                    $size,
		int                    $error = UPLOAD_ERR_OK,
		string|null            $filename = null,
		string|null            $mediaType = null,
		StreamFactoryInterface $streamFactory = new HTTPFactory,
	){
		$this->size          = $size;
		$this->error         = $error;
		$this->filename      = $filename;
		$this->mediaType     = $mediaType;
		$this->streamFactory = $streamFactory;

		if(!in_array($this->error, $this::UPLOAD_ERRORS, true)){
			throw new InvalidArgumentException('Invalid error status for UploadedFile');
		}

		if($this->error === UPLOAD_ERR_OK){

			if(is_string($file)){
				$this->file = $file;
			}
			else{
				$this->stream = HTTPFactory::createStreamFromSource($file);
			}

		}

	}

	public function getStream():StreamInterface{
		$this->validateActive();

		if($this->stream instanceof StreamInterface){
			return $this->stream;
		}

		if(is_file($this->file)){
			return $this->streamFactory->createStreamFromFile($this->file, 'r+');
		}

		return $this->streamFactory->createStream($this->file);
	}

	public function moveTo(string $targetPath):void{
		$this->validateActive();

		if(empty($targetPath)){
			throw new InvalidArgumentException('Invalid path provided for move operation; must be a non-empty string');
		}

		if(!is_writable($targetPath)){
			throw new RuntimeException('Directory is not writable: '.$targetPath);
		}

		if($this->file !== null){
			$this->moved = (PHP_SAPI === 'cli')
				? rename($this->file, $targetPath)
				: move_uploaded_file($this->file, $targetPath);
		}
		else{
			$this->copyToStream($this->streamFactory->createStreamFromFile($targetPath, 'r+'));
			$this->moved = true;
		}

		if($this->moved === false){
			throw new RuntimeException('Uploaded file could not be moved to '.$targetPath); // @codeCoverageIgnore
		}

	}

	public function getSize():int|null{
		return $this->size;
	}

	public function getError():int{
		return $this->error;
	}

	public function getClientFilename():string|null{
		return $this->filename;
	}

	public function getClientMediaType():string|null{
		return $this->mediaType;
	}

	/**
	 * @throws RuntimeException if is moved or not ok
	 */
	protected function validateActive():void{

		if($this->error !== UPLOAD_ERR_OK){
			throw new RuntimeException('Cannot retrieve stream due to upload error');
		}

		if($this->moved){
			throw new RuntimeException('Cannot retrieve stream after it has already been moved');
		}

	}

	/**
	 * Copy the contents of a stream into another stream until the given number
	 * of bytes have been read.
	 *
	 * @author Michael Dowling and contributors to guzzlehttp/psr7
	 *
	 * @throws \RuntimeException on error
	 */
	protected function copyToStream(StreamInterface $dest):void{
		$source = $this->getStream();

		if($source->isSeekable()){
			$source->rewind();
		}

		while(!$source->eof()){

			if(!$dest->write($source->read(1048576))){
				break; // @codeCoverageIgnore
			}

		}

	}

}
