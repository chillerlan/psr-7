<?php
/**
 * Class Stream
 *
 * @created      11.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */
declare(strict_types=1);

namespace chillerlan\HTTP\Psr7;

use chillerlan\HTTP\Utils\StreamUtil;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException, RuntimeException;
use function clearstatcache, fclose, feof, fread, fstat, ftell, fwrite, is_resource, stream_get_meta_data;
use const SEEK_SET;

/**
 * Implements a data stream object
 */
class Stream implements StreamInterface{

	/** @var resource|null */
	protected             $stream = null;
	protected bool        $seekable;
	protected bool        $readable;
	protected bool        $writable;
	protected string|null $uri    = null;
	protected int|null    $size   = null;

	/**
	 * Stream constructor.
	 *
	 * @param resource $stream
	 */
	public function __construct($stream){

		if(!is_resource($stream)){
			throw new InvalidArgumentException('Stream must be a resource');
		}

		$this->stream   = $stream;
		$meta           = $this->getMetadata();
		$mode           = ($meta['mode'] ?? '');
		$this->seekable = ($meta['seekable'] ?? false);
		$this->readable = StreamUtil::modeAllowsRead($mode);
		$this->writable = StreamUtil::modeAllowsWrite($mode);
		$this->uri      = ($meta['uri'] ?? null);
	}

	/**
	 * Closes the stream when the destructed
	 *
	 * @return void
	 */
	public function __destruct(){
		$this->close();
	}

	public function __toString():string{

		if(!is_resource($this->stream)){
			return '';
		}

		if($this->isSeekable()){
			$this->seek(0);
		}

		return $this->getContents();
	}

	public function close():void{

		if(is_resource($this->stream)){
			fclose($this->stream);
		}

		$this->detach();
	}

	public function detach(){
		$oldResource = $this->stream;

		$this->stream   = null;
		$this->size     = null;
		$this->uri      = null;
		$this->readable = false;
		$this->writable = false;
		$this->seekable = false;

		return $oldResource;
	}

	public function getSize():int|null{

		if(!is_resource($this->stream)){
			return null;
		}

		// Clear the stat cache if the stream has a URI
		if($this->uri){
			clearstatcache(true, $this->uri);
		}

		$stats = fstat($this->stream);

		if(isset($stats['size'])){
			$this->size = $stats['size'];

			return $this->size;
		}

		if($this->size !== null){
			return $this->size;
		}

		return null; // @codeCoverageIgnore
	}

	public function tell():int{

		if(!is_resource($this->stream)){
			throw new RuntimeException('Invalid stream'); // @codeCoverageIgnore
		}

		$result = ftell($this->stream);

		if($result === false){
			throw new RuntimeException('Unable to determine stream position'); // @codeCoverageIgnore
		}

		return $result;
	}

	public function eof():bool{
		return !$this->stream || feof($this->stream);
	}

	public function isSeekable():bool{
		return $this->seekable;
	}

	public function seek(int $offset, int $whence = SEEK_SET):void{

		if(!is_resource($this->stream)){
			throw new RuntimeException('Invalid stream'); // @codeCoverageIgnore
		}

		if(!$this->seekable){
			throw new RuntimeException('Stream is not seekable');
		}

		if(fseek($this->stream, $offset, $whence) === -1){
			throw new RuntimeException('Unable to seek to stream position '.$offset.' with whence '.$whence);
		}

	}

	public function rewind():void{
		$this->seek(0);
	}

	public function isWritable():bool{
		return $this->writable;
	}

	public function write(string $string):int{

		if(!is_resource($this->stream)){
			throw new RuntimeException('Invalid stream'); // @codeCoverageIgnore
		}

		if(!$this->writable){
			throw new RuntimeException('Cannot write to a non-writable stream');
		}

		// We can't know the size after writing anything
		$this->size = null;
		$result     = fwrite($this->stream, $string);

		if($result === false){
			throw new RuntimeException('Unable to write to stream'); // @codeCoverageIgnore
		}

		return $result;
	}

	public function isReadable():bool{
		return $this->readable;
	}

	public function read(int $length):string{

		if(!is_resource($this->stream)){
			throw new RuntimeException('Invalid stream'); // @codeCoverageIgnore
		}

		if(!$this->readable){
			throw new RuntimeException('Cannot read from non-readable stream');
		}

		if($length < 0){
			throw new RuntimeException('Length parameter cannot be negative');
		}

		if($length === 0){
			return '';
		}

		$string = fread($this->stream, $length);

		if($string === false){
			throw new RuntimeException('Unable to read from stream'); // @codeCoverageIgnore
		}

		return $string;
	}

	public function getContents():string{

		if(!is_resource($this->stream)){
			throw new RuntimeException('Invalid stream'); // @codeCoverageIgnore
		}

		if(!$this->readable){
			throw new RuntimeException('Cannot read from non-readable stream');
		}

		return StreamUtil::tryGetContents($this->stream);
	}

	public function getMetadata(string|null $key = null):mixed{

		if(!is_resource($this->stream)){
			return ($key) ? null : [];
		}

		$meta = stream_get_meta_data($this->stream);

		if($key === null){
			return $meta;
		}

		return ($meta[$key] ?? null);
	}

}
