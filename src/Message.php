<?php
/**
 * Class Message
 *
 * @created      11.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTP\Psr7;

use chillerlan\HTTP\Utils\HeaderUtil;
use Psr\Http\Message\{MessageInterface, StreamInterface};
use function array_column, array_combine, array_merge, implode, is_array, strtolower,  str_replace, trim;

/**
 * Implements a HTTP message
 *
 * @see https://datatracker.ietf.org/doc/html/rfc7230
 * @see https://datatracker.ietf.org/doc/html/rfc7231
 * @see https://datatracker.ietf.org/doc/html/rfc9110
 * @see https://datatracker.ietf.org/doc/html/rfc9112
 */
class Message implements MessageInterface{

	protected StreamInterface $body;
	protected array           $headers = [];
	protected string          $version = '1.1';

	/**
	 * Message constructor.
	 */
	public function __construct(StreamInterface|string|null $body = null){
		$this->body = HTTPFactory::createStreamFromSource($body);
	}

	/**
	 * @inheritDoc
	 */
	public function getProtocolVersion():string{
		return $this->version;
	}

	/**
	 * @inheritDoc
	 */
	public function withProtocolVersion(string $version):static{
		$this->version = $version;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function getHeaders():array{
		return array_combine(array_column($this->headers, 'name'), array_column($this->headers, 'value'));
	}

	/**
	 * @inheritDoc
	 */
	public function hasHeader(string $name):bool{
		return isset($this->headers[strtolower($this->checkName($name))]);
	}

	/**
	 * @inheritDoc
	 */
	public function getHeader(string $name):array{
		$name = $this->checkName($name);

		if(!$this->hasHeader($name)){
			return [];
		}

		return $this->headers[strtolower($name)]['value'];
	}

	/**
	 * @inheritDoc
	 */
	public function getHeaderLine(string $name):string{
		return implode(', ', $this->getHeader($name));
	}

	/**
	 * @inheritDoc
	 */
	public function withHeader(string $name, mixed $value):static{
		$name = $this->checkName($name);

		$this->headers[strtolower($name)] = ['name' => $name, 'value' => $this->checkValue($value)];

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function withAddedHeader(string $name, mixed $value):static{
		$name   = $this->checkName($name);
		$lcName = strtolower($name);

		/** @phan-suppress-next-line PhanTypeMismatchArgumentInternal */
		$this->headers[$lcName] = [
			'name'  => ($this->headers[$lcName]['name'] ?? $name),
			'value' => array_merge(($this->headers[$lcName]['value'] ?? []), $this->checkValue($value)),
		];

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function withoutHeader(string $name):static{
		$name   = $this->checkName($name);
		$lcName = strtolower($name);

		if(!isset($this->headers[$lcName])){
			return $this;
		}

		unset($this->headers[$lcName]);

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function getBody():StreamInterface{
		return $this->body;
	}

	/**
	 * @inheritDoc
	 */
	public function withBody(StreamInterface $body):static{
		$this->body = $body;

		return $this;
	}

	/**
	 * checks/cleans a header name
	 *
	 * @see https://github.com/advisories/GHSA-wxmh-65f7-jcvw
	 */
	protected function checkName(string $name):string{
		return trim(str_replace(["\r", "\n", ' '], '', $name));
	}

	/**
	 * @see https://github.com/advisories/GHSA-wxmh-65f7-jcvw
	 *
	 * @return string[]
	 */
	protected function checkValue(mixed $value):array{

		if(!is_array($value)){
			$value = [$value];
		}

		/**
		 * @noinspection PhpIncompatibleReturnTypeInspection
		 * @phan-suppress-next-next-line PhanTypeMismatchReturn
		 */
		return HeaderUtil::trimValues($value);
	}

}
