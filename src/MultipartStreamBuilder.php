<?php
/**
 * Class MultipartStreamBuilder
 *
 * @created      19.07.2023
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2023 smiley
 * @license      MIT
 */
declare(strict_types=1);

namespace chillerlan\HTTP\Psr7;

use chillerlan\HTTP\Utils\{HeaderUtil, MessageUtil, StreamUtil};
use Psr\Http\Message\{MessageInterface, StreamFactoryInterface, StreamInterface};
use InvalidArgumentException;
use function basename, count, implode, ksort, preg_match, random_bytes, sha1, sprintf, str_starts_with, trim;

/**
 * Use PSR-7 MessageInterface to build multipart messages
 *
 * @link https://datatracker.ietf.org/doc/html/rfc2046#section-5.1
 */
class MultipartStreamBuilder{

	/** @var \Psr\Http\Message\MessageInterface[] */
	protected array                  $messages;
	protected string                 $boundary;
	protected StreamInterface        $multipartStream;
	protected StreamFactoryInterface $streamFactory;

	/**
	 * MultipartStreamBuilder constructor
	 */
	public function __construct(StreamFactoryInterface $streamFactory){
		$this->streamFactory = $streamFactory;

		$this->reset();
	}

	/**
	 * Returns the stream content (make sure to save the boundary before!)
	 */
	public function __toString():string{
		/** @phpstan-ignore-next-line build() always returns a StreamInterface here */
		return $this->build()->getContents();
	}

	/**
	 * Clears the MessageInterface array
	 */
	public function reset():static{
		$this->messages = [];
		$this->boundary = $this->getRandomBoundary();

		return $this;
	}

	/**
	 * Sets a boundary string
	 *
	 * permitted characters: DIGIT ALPHA '()+_,-./:=?
	 *
	 * @see https://datatracker.ietf.org/doc/html/rfc2046#section-5.1.1
	 */
	public function setBoundary(string $boundary):static{
		$boundary = trim($boundary);

		if($boundary === ''){
			throw new InvalidArgumentException('The given boundary is empty');
		}

		if(!preg_match('#^[a-z\d\'()+_,-./:=?]+$#i', $boundary)){
			throw new InvalidArgumentException('The given boundary contains illegal characters');
		}

		$this->boundary = $boundary;

		return $this;
	}

	/**
	 * Returns the current boundary string
	 */
	public function getBoundary():string{
		return $this->boundary;
	}

	/**
	 * Generates a random boundary string
	 */
	protected function getRandomBoundary():string{
		return sha1(random_bytes(8192));
	}

	/**
	 * Adds a message with the given content
	 *
	 * @phpstan-param array<string, string>|null $headers
	 */
	public function addString(
		string        $content,
		string|null   $fieldname = null,
		string|null   $filename = null,
		iterable|null $headers = null,
		bool|null     $setContentLength = null,
	):static{
		return $this->addStream($this->streamFactory->createStream($content), $fieldname, $filename, $headers, $setContentLength);
	}

	/**
	 * Adds a StreamInterface
	 *
	 * @phpstan-param array<string, string>|null $headers
	 */
	public function addStream(
		StreamInterface $stream,
		string|null     $fieldname = null,
		string|null     $filename = null,
		iterable|null   $headers = null,
		bool|null       $setContentLength = null,
	):static{
		$message = new Message($stream);

		if($headers !== null){
			foreach($headers as $name => $value){
				$message = $message->withAddedHeader($name, $value);
			}
		}

		return $this->addMessage($message, $fieldname, $filename, $setContentLength);
	}

	/**
	 * Adds a MessageInterface
	 */
	public function addMessage(
		MessageInterface $message,
		string|null      $fieldname = null,
		string|null      $filename = null,
		bool|null        $setContentLength = null,
	):static{
		$setContentLength ??= true;

		// hmm, we don't have a content-type, let's see if we can guess one
		if(!$message->hasHeader('content-type')){
			// let it throw or ignore??
			$message = MessageUtil::setContentTypeHeader($message, $filename);
		}

		// set Content-Disposition
		$message = $this->setContentDispositionHeader($message, $fieldname, $filename);

		// set Content-Length
		// @see https://github.com/guzzle/psr7/pull/581
		if($setContentLength === true){
			$this->messages[] = MessageUtil::setContentLengthHeader($message);
		}

		return $this;
	}

	/**
	 * Builds the multipart content from the given messages.
	 *
	 * If a MessageInterface is given, the body and content type header with the boundary will be set
	 * and the MessageInterface is returned; returns the StreamInterface with the content otherwise.
	 *
	 * @deprecated 1.2.0 The parameter $message and the MessageInterface return type will be removed in the next major version,
	 *             use the method buildMessage() instead.
	 */
	public function build(MessageInterface|null $message = null):StreamInterface|MessageInterface{

		if($message === null){
			return $this->buildStream();
		}

		return $this->buildMessage($message);
	}

	/**
	 * Builds the multipart content from the given messages.
	 *
	 * Returns the StreamInterface with the multipart message content.
	 *
	 * @deprecated 1.2.0 intermediate helper function, will be renamed/moved to build() in the next major version
	 */
	public function buildStream():StreamInterface{
		$this->multipartStream = $this->streamFactory->createStream();

		foreach($this->messages as $part){
			// write boundary before each part
			$this->multipartStream->write(sprintf("--%s\r\n", $this->boundary));
			// write content
			$this->writeHeaders($part->getHeaders());
			$this->writeBody($part->getBody());
		}

		// write final boundary
		$this->multipartStream->write(sprintf("--%s--\r\n", $this->boundary));
		// rewind stream!!!
		$this->multipartStream->rewind();

		return $this->multipartStream;
	}

	/**
	 * Builds the multipart content from the given messages and sets body and content type headerin the given MessageInterface.
	 */
	public function buildMessage(MessageInterface $message):MessageInterface{
		// write a proper multipart header to the given message and add the body
		return $message
			->withHeader('Content-Type', sprintf('multipart/form-data; boundary="%s"', $this->boundary))
			->withBody($this->buildStream())
		;
	}

	/**
	 * Parses and writes the headers from the given message to the multipart stream
	 *
	 * @param array<string, string|string[]> $headers
	 */
	protected function writeHeaders(iterable $headers):void{
		$headers = HeaderUtil::normalize($headers);
		// beautify
		ksort($headers);

		foreach($headers as $name => $value){
			// skip unwanted headers
			if(!str_starts_with($name, 'Content') && !str_starts_with($name, 'X-')){
				continue;
			}

			// special rule to suppress the content type header
			if($name === 'Content-Type' && $value === ''){
				continue;
			}

			// write "Key: Value" followed by a newline
			$this->multipartStream->write(sprintf("%s: %s\r\n", $name, $value));
		}
		// end with newline
		$this->multipartStream->write("\r\n");
	}

	/**
	 * Writes the content of the given StreamInterface to the multipart stream
	 */
	protected function writeBody(StreamInterface $body):void{

		// rewind!!!
		if($body->isSeekable()){
			$body->rewind();
		}

		StreamUtil::copyToStream($body, $this->multipartStream);

		// end with newline
		$this->multipartStream->write("\r\n");
	}

	/**
	 * Sets the "Content-Disposition" header in the given MessageInterface if a name and/or filename are given
	 *
	 * If the header was already set on the message, this one will be used unmodified.
	 */
	protected function setContentDispositionHeader(
		MessageInterface $message,
		string|null      $fieldname,
		string|null      $filename,
	):MessageInterface{
		// oh, you already set the header? okay - at your own risk! bye
		if($message->hasHeader('Content-Disposition')){
			return $message;
		}

		$contentDisposition = ['form-data'];

		if($fieldname !== null){
			$fieldname = trim($fieldname);

			if($fieldname === ''){
				throw new InvalidArgumentException('Invalid form field name');
			}

			$contentDisposition[] = sprintf('name="%s"', $fieldname);
		}

		if($filename !== null){
			$contentDisposition[] = sprintf('filename="%s"', basename($filename));
		}

		if(count($contentDisposition) > 1){
			return $message->withHeader('Content-Disposition', implode('; ', $contentDisposition));
		}

		return $message;
	}

}
