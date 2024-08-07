<?php
/**
 * Class Request
 *
 * @created      11.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */
declare(strict_types=1);

namespace chillerlan\HTTP\Psr7;

use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\{RequestInterface, StreamInterface, UriInterface};
use InvalidArgumentException;
use function preg_match, strtoupper, trim;

/**
 * Implements a HTTP request message
 */
class Request extends Message implements RequestInterface, RequestMethodInterface{

	protected UriInterface $uri;
	protected string       $method;
	protected string|null  $requestTarget = null;

	/**
	 * Request constructor.
	 */
	public function __construct(string $method, UriInterface|string $uri, StreamInterface|string|null $body = null){
		parent::__construct($body);

		$this->method = strtoupper(trim($method));

		if($this->method === ''){
			throw new InvalidArgumentException('HTTP method must not be empty');
		}

		$this->uri = ($uri instanceof UriInterface) ? $uri : new Uri($uri);

		$this->updateHostFromUri();
	}

	public function getRequestTarget():string{

		if($this->requestTarget !== null){
			return $this->requestTarget;
		}

		$target = $this->uri->getPath();
		$query  = $this->uri->getQuery();

		if($target === ''){
			$target = '/';
		}

		if($query !== ''){
			$target .= '?'.$query;
		}

		return $target;
	}

	public function withRequestTarget(string $requestTarget):static{

		if(preg_match('#\s#', $requestTarget)){
			throw new InvalidArgumentException('Invalid request target provided; cannot contain whitespace');
		}

		$this->requestTarget = $requestTarget;

		return $this;
	}

	public function getMethod():string{
		return $this->method;
	}

	public function withMethod(string $method):static{
		$method = strtoupper(trim($method));

		if($method === ''){
			throw new InvalidArgumentException('HTTP method must not be empty');
		}

		$this->method = $method;

		return $this;
	}

	public function getUri():UriInterface{
		return $this->uri;
	}

	public function withUri(UriInterface $uri, bool $preserveHost = false):static{

		if($uri !== $this->uri){
			$this->uri = $uri;

			if(!$preserveHost){
				$this->updateHostFromUri();
			}
		}

		return $this;
	}

	protected function updateHostFromUri():void{
		$host = $this->uri->getHost();

		if($host !== ''){
			$port = $this->uri->getPort();

			if($port !== null){
				$host .= ':'.$port;
			}

			// Ensure Host is the first header.
			// See: http://tools.ietf.org/html/rfc7230#section-5.4
			$this->headers = (['host' => ['name' => 'Host', 'value' => [$host]]] + $this->headers);
		}

	}

}
