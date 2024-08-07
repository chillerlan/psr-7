<?php
/**
 * Class DummyStream
 *
 * @created      19.07.2023
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2023 smiley
 * @license      MIT
 */
declare(strict_types=1);

namespace chillerlan\HTTP\Psr7;

use Psr\Http\Message\StreamInterface;
use Closure;
use function array_diff, array_keys, in_array;
use const SEEK_SET;

/**
 * A stream handler that allows to override select methods of the given StreamInterface
 */
class DummyStream implements StreamInterface{

	protected const STREAMINTERFACE_METHODS = [
		'__toString',
		'close',
		'detach',
		'rewind',
		'getSize',
		'tell',
		'eof',
		'isSeekable',
		'seek',
		'isWritable',
		'write',
		'isReadable',
		'read',
		'getContents',
		'getMetadata',
	];

	/** @var array<string, \Closure> */
	protected array           $override = [];
	protected StreamInterface $stream;

	/**
	 * DummyStream constructor
	 *
	 * @param array<string, \Closure>|null $methods
	 */
	public function __construct(StreamInterface|null $stream = null, array|null $methods = null){

		$this
			->dummySetStream($stream ?? HTTPFactory::createStreamFromString())
			->dummyOverrideAll(($methods ?? []))
		;
	}

	/**
	 * Sets a StreamInterface to override
	 */
	public function dummySetStream(StreamInterface $stream):static{
		$this->stream = $stream;

		return $this;
	}

	/**
	 * Sets the override methods
	 *
	 * @param array<string, \Closure> $methods
	 */
	public function dummyOverrideAll(array $methods):static{

		foreach($methods as $name => $fn){
			$this->dummyOverrideMethod($name, $fn);
		}

		foreach(array_diff($this::STREAMINTERFACE_METHODS, array_keys($this->override)) as $name){
			$this->override[$name] = $this->stream->{$name}(...);
		}

		return $this;
	}

	/**
	 * Sets a single override method
	 */
	public function dummyOverrideMethod(string $name, Closure $fn):static{

		if(in_array($name, $this::STREAMINTERFACE_METHODS, true)){
			$this->override[$name] = $fn;
		}

		return $this;
	}

	public function __destruct(){
		$this->override['close']();
	}

	public function __toString():string{
		return $this->override['__toString']();
	}

	public function close():void{
		$this->override['close']();
	}

	public function detach(){
		return $this->override['detach']();
	}

	public function getSize():int|null{
		return $this->override['getSize']();
	}

	public function tell():int{
		return $this->override['tell']();
	}

	public function eof():bool{
		return $this->override['eof']();
	}

	public function isSeekable():bool{
		return $this->override['isSeekable']();
	}

	public function seek(int $offset, int $whence = SEEK_SET):void{
		$this->override['seek']($offset, $whence);
	}

	public function rewind():void{
		$this->override['rewind']();
	}

	public function isWritable():bool{
		return $this->override['isWritable']();
	}

	public function write(string $string):int{
		return $this->override['write']($string);
	}

	public function isReadable():bool{
		return $this->override['isReadable']();
	}

	public function read(int $length):string{
		return $this->override['read']($length);
	}

	public function getContents():string{
		return $this->override['getContents']();
	}

	public function getMetadata(string|null $key = null):mixed{
		return $this->override['getMetadata']($key);
	}

}
