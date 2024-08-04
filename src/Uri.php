<?php
/**
 * Class Uri
 *
 * @created      10.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 *
 * @noinspection RegExpUnnecessaryNonCapturingGroup, RegExpRedundantEscape
 */
declare(strict_types=1);

namespace chillerlan\HTTP\Psr7;

use chillerlan\HTTP\Utils\UriUtil;
use Psr\Http\Message\UriInterface;
use InvalidArgumentException, RuntimeException;
use function explode, filter_var, is_array, is_string, ltrim, mb_strtolower, preg_match,
	preg_replace_callback, property_exists, rawurlencode, str_contains, str_starts_with, trim;
use const FILTER_FLAG_IPV6, FILTER_VALIDATE_IP;

/**
 * Implements an URI object
 *
 * @see https://datatracker.ietf.org/doc/html/rfc3986
 * @see https://datatracker.ietf.org/doc/html/rfc7320
 * @see https://datatracker.ietf.org/doc/html/rfc8820
 *
 * @phpstan-type ParsedUrl array{scheme?: string, host?: string, port?: int|string, user?: string, pass?: string, path?: string, query?: string, fragment?: string}
 */
class Uri implements UriInterface{

	/**
	 * Percent encoded
	 *
	 * @see https://datatracker.ietf.org/doc/html/rfc3986#section-2.1
	 */
	protected const CHAR_PERCENT_HEX = '%(?![a-fA-F0-9]{2})';

	/**
	 * Generic delimiters for use in a regex.
	 *
	 * @see https://datatracker.ietf.org/doc/html/rfc3986#section-2.2
	 */
	protected const CHAR_GEN_DELIMS = ':\/\?#\[\]@';

	/**
	 * Sub delimiters for use in a regex.
	 *
	 * @see https://datatracker.ietf.org/doc/html/rfc3986#section-2.2
	 */
	protected const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

	/**
	 * Unreserved characters for use in a regex.
	 *
	 * @see https://datatracker.ietf.org/doc/html/rfc3986#section-2.3
	 */
	protected const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';

	protected string   $scheme   = '';
	protected string   $user     = '';
	protected string   $pass     = '';
	protected string   $host     = '';
	protected int|null $port     = null;
	protected string   $path     = '';
	protected string   $query    = '';
	protected string   $fragment = '';

	/**
	 * Uri constructor.
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @phpstan-param ParsedUrl|string|null $uri
	 */
	public function __construct(array|string|null $uri = null){

		if($uri !== null){

			if(is_string($uri)){
				$uri = UriUtil::parseUrl($uri);
			}

			if(!is_array($uri)){
				throw new InvalidArgumentException('Unable to parse URI');
			}

			$this->parseUriParts($uri);
		}

	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function __toString():string{

		if(empty($this->scheme) && str_contains(explode('/', $this->path, 2)[0], ':')){
			throw new InvalidArgumentException('A relative URI must not have a path beginning with a segment containing a colon');
		}

		$uri       = '';
		$authority = $this->getAuthority();
		$path      = $this->path;

		if($this->scheme !== ''){
			$uri .= $this->scheme.':';
		}

		// fix "file" scheme (see Guzzle)
		if($authority !== '' || $this->scheme === 'file'){
			$uri .= '//'.$authority;
		}

		// If the path is rootless and an authority is present, the path MUST be prefixed by "/"
		if($authority !== '' && $path !== '' && !str_starts_with($path, '/')){
			$path = '/'.$path;
		}
		// If the path is starting with more than one "/", the starting slashes MUST be reduced to one.
		elseif($authority === '' && str_starts_with($path, '//')){
			$path = '/'.ltrim($path, '/');
		}

		$uri .= $path;

		if($this->query !== ''){
			$uri .= '?'.$this->query;
		}

		if($this->fragment !== ''){
			$uri .= '#'.$this->fragment;
		}

		return $uri;
	}

	/*
	 * Getters
	 */

	public function getScheme():string{
		return $this->scheme;
	}

	public function getUserInfo():string{
		$userinfo = $this->user;

		if($this->pass !== ''){
			$userinfo .= ':'.$this->pass;
		}

		return $userinfo;
	}

	public function getHost():string{
		return $this->host;
	}

	public function getPort():int|null{
		return $this->port;
	}

	public function getAuthority():string{
		$authority = $this->host;
		$userInfo  = $this->getUserInfo();

		if($this->host === '' && ($this->scheme === 'http' || $this->scheme === 'https')){
			$authority = 'localhost';
		}

		if($userInfo !== ''){
			$authority = $userInfo.'@'.$authority;
		}

		if($this->port !== null){
			$authority .= ':'.$this->port;
		}

		return $authority;
	}

	public function getPath():string{
		return $this->path;
	}

	public function getQuery():string{
		return $this->query;
	}

	public function getFragment():string{
		return $this->fragment;
	}

	/*
	 * Setters
	 */

	public function withScheme(string $scheme):static{
		return $this->parseUriParts(['scheme' => $scheme]);
	}

	public function withUserInfo(string $user, string|null $password = null):static{
		return $this->parseUriParts(['user' => $user, 'pass' => ($password ?? '')]);
	}

	public function withHost(string $host):static{
		return $this->parseUriParts(['host' => $host]);
	}

	public function withPort(int|null $port):static{
		return $this->parseUriParts(['port' => $port]);
	}

	public function withPath(string $path):static{
		return $this->parseUriParts(['path' => $path]);
	}

	public function withQuery(string $query):static{
		return $this->parseUriParts(['query' => $query]);
	}

	public function withFragment(string $fragment):static{
		return $this->parseUriParts(['fragment' => $fragment]);
	}

	/*
	 * Filters
	 */

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function filterScheme(string $scheme):string{
		$scheme = mb_strtolower(trim($scheme));

		if(!preg_match('/^[a-z0-9\+\-\.]*$/', $scheme)){
			throw new InvalidArgumentException('scheme contains illegal characters');
		}

		return $scheme;
	}

	protected function filterUserInfo(string $userOrPass):string{
		return $this->replaceChars(
			$userOrPass,
			'/(?:['.self::CHAR_GEN_DELIMS.self::CHAR_SUB_DELIMS.']+|'.self::CHAR_PERCENT_HEX.')/',
		);
	}

	protected function filterHost(string $host):string{
		$filteredIPv6 = filter_var(trim($host, '[]'), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

		if($filteredIPv6 !== false){
			$host = '['.$filteredIPv6.']';
		}

		return mb_strtolower($host);
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function filterPort(int|null $port):int|null{

		if($port === null){
			return null;
		}

		if($port >= 0 && $port <= 0xffff){
			return $port;
		}

		throw new InvalidArgumentException('invalid port: '.$port);
	}

	protected function filterPath(string $path):string{
		return $this->replaceChars(
			$path,
			'/(?:[^'.self::CHAR_UNRESERVED.self::CHAR_SUB_DELIMS.'%:@\/]++|'.self::CHAR_PERCENT_HEX.')/',
		);
	}

	protected function filterQueryOrFragment(string $queryOrFragment):string{
		return $this->replaceChars(
			$queryOrFragment,
			'/(?:[^'.self::CHAR_UNRESERVED.self::CHAR_SUB_DELIMS.'%:@\/\?]++|'.self::CHAR_PERCENT_HEX.')/',
		);
	}

	protected function replaceChars(string $str, string $regex):string{
		$str = preg_replace_callback($regex, fn(array $match):string => rawurlencode($match[0]), $str);

		if(!is_string($str)){
			throw new RuntimeException('preg_replace_callback() did not reurn a string');
		}

		return $str;
	}

	/**
	 * @phpstan-param ParsedUrl $parts
	 */
	protected function parseUriParts(array $parts):static{
		/** @var string $part */
		foreach($parts as $part => $value){

			if(!property_exists($this, $part)){
				continue;
			}

			$this->{$part} = match($part){
				'user', 'pass'      => $this->filterUserInfo($value),
				'scheme'            => $this->filterScheme($value),
				'host'              => $this->filterHost($value),
				'port'              => $this->filterPort($value),
				'path'              => $this->filterPath($value),
				'query', 'fragment' => $this->filterQueryOrFragment($value),
			};

		}

		if(UriUtil::isDefaultPort($this)){
			$this->port = null;
		}

		return $this;
	}

}
