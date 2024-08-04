<?php
/**
 * Class HTTPFactory
 *
 * @created      02.03.2024
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2024 smiley
 * @license      MIT
 */
declare(strict_types=1);

namespace chillerlan\HTTP\Psr7;

use chillerlan\HTTP\Utils\StreamUtil;
use Fig\Http\Message\{RequestMethodInterface, StatusCodeInterface};
use Psr\Http\Message\{
	RequestFactoryInterface, RequestInterface, ResponseFactoryInterface, ResponseInterface, ServerRequestFactoryInterface,
	ServerRequestInterface, StreamFactoryInterface, StreamInterface, UploadedFileFactoryInterface, UploadedFileInterface,
	UriFactoryInterface, UriInterface
};
use InvalidArgumentException, RuntimeException, Stringable;
use function fseek, is_file, is_readable, is_scalar, stream_copy_to_stream, stream_get_meta_data;
use const UPLOAD_ERR_OK;

/**
 * Implements the PSR-17 HTTP factories
 */
class HTTPFactory implements
	RequestFactoryInterface,
	ResponseFactoryInterface,
	RequestMethodInterface,
	ServerRequestFactoryInterface,
	StatusCodeInterface,
	StreamFactoryInterface,
	UploadedFileFactoryInterface,
	UriFactoryInterface {

	public function createRequest(string $method, $uri):RequestInterface{ // phpcs:ignore
		return new Request($method, $uri);
	}

	public function createResponse(int $code = 200, string $reasonPhrase = ''):ResponseInterface{
		return new Response($code, $reasonPhrase);
	}

	public function createStream(string $content = ''):StreamInterface{
		return static::createStreamFromString(content: $content, rewind: false);
	}

	public function createStreamFromFile(string $filename, string $mode = 'r'):StreamInterface{

		if(empty($filename) || !is_file($filename) || !is_readable($filename)){
			throw new RuntimeException('invalid file');
		}

		return new Stream(StreamUtil::tryFopen($filename, $mode));
	}

	public function createStreamFromResource($resource):StreamInterface{ // phpcs:ignore
		return new Stream($resource);
	}

	public function createUri(string $uri = ''):UriInterface{
		return new Uri($uri);
	}

	/**
	 * @param array<int|string, mixed> $serverParams
	 */
	public function createServerRequest(string $method, $uri, array $serverParams = []):ServerRequestInterface{ // phpcs:ignore
		return new ServerRequest($method, $uri, $serverParams);
	}

	public function createUploadedFile(
		StreamInterface $stream,
		int|null        $size = null,
		int             $error = UPLOAD_ERR_OK,
		string|null     $clientFilename = null,
		string|null     $clientMediaType = null,
	):UploadedFileInterface{
		return new UploadedFile($stream, ($size ?? (int)$stream->getSize()), $error, $clientFilename, $clientMediaType);
	}

	/**
	 * Create a new writable stream from a string.
	 */
	public static function createStreamFromString(string $content = '', string $mode = 'r+', bool $rewind = true):StreamInterface{

		if(!StreamUtil::modeAllowsWrite($mode)){
			throw new InvalidArgumentException('invalid mode for writing');
		}

		$stream = new Stream(StreamUtil::tryFopen('php://temp', $mode));

		if($content !== ''){
			$stream->write($content);
		}

		if($rewind === true){
			$stream->rewind();
		}

		return $stream;
	}

	/**
	 * Creates a StreamInterface from the given source
	 */
	public static function createStreamFromSource(mixed $source = null):StreamInterface{
		$source ??= '';

		if($source instanceof StreamInterface){
			return $source;
		}

		if($source instanceof Stringable || is_scalar($source)){
			return static::createStreamFromString((string)$source);
		}

		$type = gettype($source);

		if($type === 'resource'){
			// avoid using php://input and copy over the contents to a new stream
			if(stream_get_meta_data($source)['uri'] === 'php://input'){
				$stream = StreamUtil::tryFopen('php://temp', 'r+');

				stream_copy_to_stream($source, $stream);
				fseek($stream, 0);

				return new Stream($stream);
			}

			return new Stream($source);
		}

		if($type === 'NULL'){
			return static::createStreamFromString();
		}

		throw new InvalidArgumentException('Invalid resource type: '.$type);
	}

}
