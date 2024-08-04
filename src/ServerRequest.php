<?php
/**
 * Class ServerRequest
 *
 * @created      11.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */
declare(strict_types=1);

namespace chillerlan\HTTP\Psr7;

use Psr\Http\Message\{ServerRequestInterface, UriInterface};
use InvalidArgumentException;
use function array_key_exists, is_array, is_object;

/**
 * Implements a server-side incoming HTTP request
 */
class ServerRequest extends Request implements ServerRequestInterface{

	/** @var array<int|string, mixed> */
	protected array             $serverParams;
	/** @var array<string, string> */
	protected array             $cookieParams  = [];
	/** @var array<string, string> */
	protected array             $queryParams   = [];
	/** @var array<string, mixed> */
	protected array             $attributes    = [];
	/** @var array{tmp_name: string[], size: int[], error: int[], name: string[], type: string[]} */
	protected array             $uploadedFiles = [];
	/** @var mixed[]|object|null */
	protected array|object|null $parsedBody    = null;

	/**
	 * ServerRequest constructor.
	 *
	 * @param array<int|string, mixed>|null $serverParams
	 */
	public function __construct(string $method, UriInterface|string $uri, array|null $serverParams = null){
		parent::__construct($method, $uri);

		$this->serverParams = ($serverParams ?? []);
	}

	/**
	 * @return array<int|string, mixed>
	 */
	public function getServerParams():array{
		return $this->serverParams;
	}

	/**
	 * @return array<string, string>
	 */
	public function getCookieParams():array{
		return $this->cookieParams;
	}

	/**
	 * @param array<string, string> $cookies
	 */
	public function withCookieParams(array $cookies):static{
		$this->cookieParams = $cookies;

		return $this;
	}

	/**
	 * @return array<string, string>
	 */
	public function getQueryParams():array{
		return $this->queryParams;
	}

	/**
	 * @param array<string, string> $query
	 */
	public function withQueryParams(array $query):static{
		$this->queryParams = $query;

		return $this;
	}

	/**
	 * @return array{tmp_name: string[], size: int[], error: int[], name: string[], type: string[]}
	 */
	public function getUploadedFiles():array{
		return $this->uploadedFiles;
	}

	/**
	 * @param array{tmp_name: string[], size: int[], error: int[], name: string[], type: string[]} $uploadedFiles
	 */
	public function withUploadedFiles(array $uploadedFiles):static{
		$this->uploadedFiles = $uploadedFiles;

		return $this;
	}

	/**
	 * @return mixed[]|object|null
	 */
	public function getParsedBody():array|object|null{
		return $this->parsedBody;
	}

	/**
	 * @param mixed[]|object|null $data
	 */
	public function withParsedBody(mixed $data):static{

		if($data !== null && !is_object($data) && !is_array($data)){
			throw new InvalidArgumentException('parsed body value must be an array, object or null');
		}

		$this->parsedBody = $data;

		return $this;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getAttributes():array{
		return $this->attributes;
	}

	public function getAttribute(string $name, mixed $default = null):mixed{

		if(!array_key_exists($name, $this->attributes)){
			return $default;
		}

		return $this->attributes[$name];
	}

	public function withAttribute(string $name, mixed $value):static{
		$this->attributes[$name] = $value;

		return $this;
	}

	public function withoutAttribute(string $name):static{

		if(array_key_exists($name, $this->attributes)){
			unset($this->attributes[$name]);
		}

		return $this;
	}

}
