<?php

namespace BarnabyWalters\Helpers;

use Carbon\Carbon;
use DateTime;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Functional Helpers
 *
 * In which I define a load of helpful little functions.
 * 
 * Some of these are stolen from elsewhere, credit given where due.
 * 
 * @author Barnaby Walters http://waterpigs.co.uk <barnaby@waterpigs.co.uk>
 */
class Helpers {
	public static function compose(array $funcs, array $apply = null) {
		$funcs = array_reverse(array_filter($funcs, 'is_callable'));
		
		$composition = function () use ($funcs) {
			$args = func_get_args();
			$initial = call_user_func_array(array_shift($funcs), $args);
			
			return array_reduce($funcs, function ($r, $i) {
				return $i($r);
			}, $initial);
		};
		
		return is_array($apply)
			? call_user_func_array($composition, $args[1])
			: $composition;
	}
	
	public static function arrayHead(array $array) {
		return array_slice($array, 0, count($array) - 1);
	}
	
	public static function arrayTail(array $array) {
		return array_slice($array, 1);
	}
	
	/**
	 * Returns the truest of the args presented. This is a dirty shortcut.
	 */
	public static function truest() {
		$args = func_get_args();
		foreach ($args as $arg) {
			if (!empty($arg)) {
				return $arg;
			}
		}

		return $args[0];
	}

	/**
	 * Parse a representation of an author out of a URI
	 * 
	 * Given a URI, returns a plaintext representation of the author of that URI,
	 * nice and ready to be processed/auto linked in whatever way you see fit.
	 * Currently enabled for the following services:
	 * 
	 * * Twitter
	 * * Indiewebsite domain (sans protocol) — assumed if doesn’t fit anything else
	 *
	 * @param string $uri The URI to parse
	 * @return string The parsed author representation, e.g. @barnabywalters or waterpigs.co.uk
	 */
	public static function authorFromUri($uri) {
		$matches = array();
		if (preg_match('|^https?://twitter.com/([a-zA-Z0-9_]{1,20})/|', $uri, $matches)) {
			// It’s a twitter URI, $matches[1] contains the @name of the user in question
			return '@' . strtolower($matches[1]);
		} else {
			// Assume it’s an indieweb URL, so the domain is the name
			return parse_url($uri, PHP_URL_HOST);
		}
	}

	/**
	 * Same Hostname
	 * 
	 * Checks whether or not two given URLs have the same hostname
	 * 
	 * @param string $a
	 * @param string $b
	 * @return bool Whether or not a and b share a hostname
	 */
	public static function sameHostname($a, $b) {
		return (parse_url($a, PHP_URL_HOST) == parse_url($b, PHP_URL_HOST));
	}

	/**
	 * Replace <img> elements with their @href
	 * 
	 * Finds all img elements and replaces them with the value of their @href. 
	 * Very useful for content syndication to services which do not allow HTML
	 * 
	 * @param string $str The string to process
	 * @return string The original $str with all <img> tags replaced by their @href value
	 */
	public static function expandImg($str) {
		return preg_replace('/<img .*src\=\"(\S*)\"+ .* ?\/?>/i', '$1', $str);
	}

	/**
	 * Find the length a string would be if all URLs were a certain length
	 * 
	 * @param string $string The string to process
	 * @param int $urilen The length to treat all URIs in $string as
	 * @return int The length $string would be if all URIs were $urilen long
	 */
	public static function uriMbStrlen($string, $urilen) {
		// Find all urls
		$urls = Helpers::findUrls($string, $tidy = false);

		// Replace them with $urllen chars
		if (is_int($urilen)) {
			foreach ($urls as $url) {
				$string = str_replace($url, str_repeat('X', $urilen), $string);
			}
		}

		// Return strlen
		return mb_strlen($string, 'UTF-8');
	}

	/**
	 * DateTime to <time>
	 * 
	 * Generates a <time> element given a PHP DateTime object
	 * Currently only supports a resolution of YYYY-MM-DD
	 * 
	 * @todo Add support for more precise times
	 * @todo Add support for string dates using strtotime()
	 *
	 * @param DateTime $datetime The datetime to turn into a <time> element
	 * @return string A <time> element representing $datetime
	 */
	public static function timeElement($datetime) {
		$t = '<time datetime="' . $datetime->format('Y-m-d') . '" title="' . $datetime->format('Y-z') . '">' . $datetime->format('Y-m-d') . '</time>';
		return $t;
	}

	/**
	 * Get Relative Time Element
	 * 
	 * @todo write tests
	 * 
	 * @param DateTime $dateTime The datetime in question
	 * @param DateTime $comparison Date to compare with. Defaults to now
	 * @param array $attrs Assoc array of attributes to add to the element
	 * @return string The time HTML element with human value inside
	 */
	public static function relativeTimeElement(
	DateTime $dateTime, DateTime $comparison = null, array $attrs = []) {
		if ($comparison !== null)
			$comparison = Carbon::instance($comparison);

		/** @var Carbon */
		$dateTime = Carbon::instance($dateTime);

		$timeElement = '<time ';

		foreach ($attrs as $attr => $val) {
			$timeElement .= $attr . '="' . htmlspecialchars($val) . '" ';
		}

		$timeElement .= 'datetime="' . $dateTime->toW3CString() . '" >';
		$timeElement .= $dateTime->diffForHumans($comparison) . '</time>';

		return $timeElement;
	}

	/**
	 * Slugify
	 * 
	 * The ultimate safe URL generator, courtesy of 
	 * http://cubiq.org/the-perfect-php-clean-url-generator
	 * 
	 * Given a string, makes it uber-readable and URI safe
	 *
	 * @param string $str The string to process
	 * @param array $replace An array of characters to replace with whitespace
	 * @param string $delimiter The character to use to separate words, defaulting to '-'
	 * @return string The cleaned string
	 */
	public static function toAscii($str, $replace = array(), $delimiter = '-') {
		setlocale(LC_ALL, 'en_US.UTF8');

		if (!empty($replace)) {
			$str = str_replace((array) $replace, ' ', $str);
		}

		$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
		$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
		$clean = strtolower(trim($clean, '-'));
		$clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

		return $clean;
	}

	/**
	 * Humanise String
	 * 
	 * Makes a slugified/ASCIIfied string nice again
	 * 
	 * @todo make this happen for CamelCase too?
	 * @todo add more intelligent dash/underscore selection
	 * 
	 * @param string $string
	 * @return string
	 */
	public static function humanise($string) {
		$spacedOut = str_replace(['-', '_'], ' ', $string);
		$talkingInCaps = ucwords($spacedOut);

		return trim($talkingInCaps);
	}

	/**
	 * Tagstring to Array
	 * 
	 * Takes a comma delimited tag string, returns an array of the tags contained 
	 * within.
	 *
	 * @param string $tagstring The comma delimited string to process
	 * @return array An array of the tags contained within $tagstring
	 */
	public static function tagstringToArray($tagstring) {
		$tags = explode(',', trim($tagstring));
		$tags = array_map(function($string) {
				return htmlspecialchars(trim($string), ENT_QUOTES);
			}, $tags);
		return $tags;
	}

	/**
	 * Get Machine Tags
	 * 
	 * Given an array of opaque string tags, return an array of all the machine
	 * tags of format namespace:predicate=value, optionally filtering by 
	 * namespace.
	 * 
	 * If the tags are filtered by namespace and $trunc is set, the namespace 
	 * and following colon are removed from the results.
	 * 
	 * The return value is always an associative array of namespace:predicate => 
	 * value.
	 * 
	 * @param array $tags
	 * @param string $namespace
	 * @param boolean $trunc 
	 * @return array
	 */
	public static function getMachineTags(array $tags, $namespace = null, $trunc = false) {
		$filter = $namespace == null ? '[a-zA-Z0-9_-]+' : preg_quote($namespace);

		// Get the machine tags out
		$machineTags = array_filter($tags, function ($tag) use ($filter) {
				if (preg_match('/^' . $filter . ':[a-zA-Z0-9_-]+=/', $tag))
					return true;

				return false;
			});

		$parsed = [];

		foreach ($machineTags as $tag) {
			$parts = explode('=', $tag);
			$key = array_shift($parts);

			if ($namespace !== null and $trunc) {
				$key = substr($key, strlen($namespace) + 1);
			}

			$value = implode('=', $parts);

			$parsed[$key] = $value;
		}

		return $parsed;
	}

	/**
	 * Get Plain Tags
	 * 
	 * Returns an array of all the non-machine tags in the passed array.
	 * 
	 * @param array $tags
	 * @return array The input with all machine tags removed
	 */
	public static function getPlainTags(array $tags) {
		$pattern = '/[a-zA-Z0-9_-]+:[a-zA-Z0-9_-]+=.+/';

		return array_filter($tags, function ($tag) use ($pattern) {
				return preg_match($pattern, $tag) == 0 ? true : false;
			});
	}

	/**
	 * Microformats from geo: tags
	 * 
	 * Given an array containing some tags in the geo: namespace, produces a
	 * h-geo and h-adr µf2 array structure from the data available
	 * 
	 * @param array $tags
	 * @return array µf2 array representation of a h-adr composed from the parts
	 */
	public static function mfFromGeotags(array $tags) {
		$tags = self::getMachineTags($tags, 'geo', true);
		$µf2 = ['type' => ['h-adr', 'h-geo']];
		$out = [];

		// h-geo
		// p-latitude and p-longitude
		if (isset($tags['lat']) and isset($tags['lon'])) {
			$out['latitude'] = $tags['lat'];
			$out['longitude'] = $tags['lon'];
		}

		// h-adr
		// p-street-address
		if (isset($tags['road']) and isset($tags['house_number']))
			$out['street-address'] = $tags['house_number'] . ' ' . $tags['road'];
		elseif (isset($tags['road']))
			$out['street-address'] = $tags['road'];

		// p-locality
		if (isset($tags['city']))
			$out['locality'] = $tags['city'];
		elseif (isset($tags['suburb']))
			$out['locality'] = $tags['suburb'];

		// p-region
		if (isset($tags['county']))
			$out['region'] = $tags['county'];
		elseif (isset($tags['state_district']))
			$out['region'] = $tags['state-district'];

		// p-postal-code
		if (isset($tags['postcode']))
			$out['postal-code'] = $tags['postcode'];

		// p-country-name
		if (isset($tags['country']))
			$out['country-name'] = $tags['county'];

		$µf2['properties'] = $out;

		return $µf2;
	}

	/**
	 * Clean Tagstring
	 * 
	 * Normalises a tag string by converting it to an array, then collapsing the 
	 * array into a string.
	 *
	 * @param string $tagstring The comma-delimited string to clean
	 * @return string The cleaned string
	 */
	public static function tagstringClean($tagstring) {
		$tags = Helpers::tagstringToArray($tagstring);
		return implode(',', $tags);
	}

	/**
	 * Auto Link Hashtags
	 * 
	 * Given a string and a baseurl, finds all hashtags matching
	 * `#[\-_a-zA-Z0-9]+` and wraps them in an `a` element with `rel=tag` set
	 * and a `href` of baseurl + '/' + tagname without the #.
	 * 
	 * @todo Improve the regex to limit false matches
	 * 
	 * @param string $text
	 * @param string $baseUrl
	 * @return string The original text with all hashtags auto-linked
	 */
	public static function autolinkHashTags($text, $baseUrl) {
		$baseUrl = rtrim($baseUrl, '/');

		// $replacements = ["#tag" => "<a rel="tag" href="/tags/tag">#tag</a>]
		$replacements = array();
		$matches = array();

		if (preg_match_all('/(?<=^|\s)\#([a-zA-Z0-9\-\_]+)/i', $text, $matches, PREG_PATTERN_ORDER)) {
			// Look up #tags, get Full name and URL
			foreach ($matches[0] as $name) {
				$name = str_replace('#', '', $name);
				$replacements[$name] = '<a rel="tag" href="' . $baseUrl . '/' . $name . '">#' . $name . '</a>';
			}

			// Replace #tags with valid microformat-enabled link
			foreach ($replacements as $name => $replacement) {
				$text = str_replace('#' . $name, $replacement, $text);
			}
		}

		return $text;
	}

	/**
	 * Get Privacy Tags
	 * 
	 * Given an array of the tags associated with an object, find any which are 
	 * in the auth namespace, parse them and return them.
	 * 
	 * ## Multiple Tag Rules
	 * 
	 * Authorization tags are all positive and additive. That is, it is 
	 * impossible for one tag to contradict another.
	 * 
	 * * All auth tags are machine tags of the form `auth:(:any)=(:any)`, where 
	 *   the wildcards must be:
	 *     1. An auth keyword from the list below
	 *     1. A valid hostname, e.g. `example.com`, `waterpigs.co.uk`, 
	 *        `bill.someservice.org`
	 * 
	 * ### Auth Keywords
	 * 
	 * * `private`
	 *     * If an object contains **ANY** `auth:private=*` tags, it is a private
	 *       post and **MUST ONLY** be exposed to users who are authenticated as
	 *       one of the `private-*` domains
	 *     * Multiple `auth:private-*` tags **MAY** be specified, resulting in 
	 *       **ANY** of the specified users being able to view the content
	 * * `editable`
	 *     * `auth:editable=user.com` states that user.com is allowed to edit 
	 *       this object. An edit UI should be made available to them.
	 *     * Users who can edit an object due to `auth:editable` **CANNOT** add 
	 *       or remove `auth:editable` tags **UNLESS** they have 
	 *       `role == super-admin`
	 * Users with `role == 'super-admin'` **MUST** be able to perform **ANY** 
	 * action on **ANY** object. These users **SHOULD** be limited to server 
	 * administrators. AuthTag implementations should be aware of this but MUST 
	 * NOT implement it directly, it must be left to the outer authorization 
	 * code as the super-admin role may have different names on different systems.
	 * 
	 * ## Example Tags
	 * 
	 * All of these examples are comma-separated tagstrings.
	 * 
	 * * `auth:private=example.com` states that the object is private but user 
	 *   example.com can view it
	 * * `auth:private=example.com, auth:private=someguy.org` states that the 
	 *   object is private but both example.com someguy.org can view it
	 * * `auth:private=someguy.org, auth:private=somegirl.com, 
	 *   auth:editable=somegirl.com` states that the object is private but both 
	 *   someguy.org and somegirl.com can view it. somegirl.com can also edit it, 
	 *   but cannot change authtag permissions
	 */
	public static function getAuthTags(array $tags) {
		$authTags = array();

		$keywords = array(
			'private',
			'editable'
		);

		$authTags = array_filter($tags, function ($tag) use ($keywords) {
				foreach ($keywords as $k) {
					if (preg_match('/^auth:' . preg_quote($k) . '=/', $tag))
						return true;
				}

				return false;
			});

		// Parse tags
		$parsedTags = array();

		foreach ($authTags as $tag) {
			$matches = array();
			preg_match('/^auth:(?P<keyword>[a-zA-Z0-9-]+)=(?P<domain>.*)$/i', $tag, $matches);
			$parsedTags[$matches['keyword']][] = $matches['domain'];
		}

		return $parsedTags;
	}

	/**
	 * Date to ATOM Date
	 * 
	 * @param string $date A string representing the date to process
	 * @param string $date formatted as an ATOM date
	 * 
	 * @todo Allow $date to be a DateTime object
	 */
	public static function atomDate($date) {
		return date(DATE_ATOM, strtotime($date));
	}

	/**
	 * Find URLs
	 * 
	 * @param string $text The string to find URLs in
	 * @param bool $tidy Whether or not to tidy the URLs with cassis web_address_to_uri(, true)
	 * @return array An array containing all the URLs found in $text
	 */
	public static function findUrls($text, $tidy = true) {
		// Pattern is from 1 cassis.js, slightly modified to not look for 
		// twitter names E.G. beforehand it would return @tantek for @tantek.com.
		// This function is just interested in addresses, not twitter stuff
		$pattern = '/(?:(?:(?:(?:http|https|irc)?:\\/\\/(?:(?:[!$&-.0-9;=?A-Z_a-z]|(?:\\%[a-fA-F0-9]{2}))+(?:\\:(?:[!$&-.0-9;=?A-Z_a-z]|(?:\\%[a-fA-F0-9]{2}))+)?\\@)?)?(?:(?:(?:[a-zA-Z0-9][-a-zA-Z0-9]*\\.)+(?:(?:aero|arpa|asia|a[cdefgilmnoqrstuwxz])|(?:biz|b[abdefghijmnorstvwyz])|(?:cat|com|coop|c[acdfghiklmnoruvxyz])|d[ejkmoz]|(?:edu|e[cegrstu])|f[ijkmor]|(?:gov|g[abdefghilmnpqrstuwy])|h[kmnrtu]|(?:info|int|i[delmnoqrst])|j[emop]|k[eghimnrwyz]|l[abcikrstuvy]|(?:mil|museum|m[acdeghklmnopqrstuvwxyz])|(?:name|net|n[acefgilopruz])|(?:org|om)|(?:pro|p[aefghklmnrstwy])|qa|r[eouw]|s[abcdeghijklmnortuvyz]|(?:tel|travel|t[cdfghjklmnoprtvwz])|u[agkmsyz]|v[aceginu]|w[fs]|y[etu]|z[amw]))|(?:(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[1-9])\\.(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[0-9])\\.(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[0-9])\\.(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[0-9])))(?:\\:\\d{1,5})?)(?:\\/(?:(?:[!#&-;=?-Z_a-z~])|(?:\\%[a-fA-F0-9]{2}))*)?)(?=\\b|\\s|$)/i';

		$c = preg_match_all($pattern, $text, $m);

		if ($c !== false) {
			// Normalise
			$links = array_values($m[0]);

			ob_start();
			$links = array_map(function($value) use ($tidy) {
					return $tidy ? \web_address_to_uri($value, true) : $value;
				}, $links);
			ob_end_clean();

			// $links = ['http://someurl.tld', •••]

			return $links;
		}

		return array();
	}

	/**
	 * String to Hex Colour
	 * 
	 * Inspired by the Dopplr colours and the work of Brian Suda and Sandeep
	 * Shetty. This differs a little from the md5 technique as it should 
	 * create similar colours for similar sounding words -- only time will 
	 * tell if this is any use and/or completely ineffective.
	 * 
	 * @param string $word The string to calculate the colour for
	 * @return string a css hex colour of the form XXXXXX
	 * @todo Write tests
	 */
	public static function stringToHexColour($word) {
		return substr(bin2hex(metaphone($word, 6)), 0, 6);
	}

	/**
	 * Parse XML-RPC Method Call
	 * 
	 * Given an XML-RPC request body, returns an array like this:
	 * 
	 *     [
	 *         'methodName': 'example.ex',
	 *         'params': ['string1', 'string2]
	 *     ]
	 * 
	 * NOTE: Only currently accepts string parameters.
	 * 
	 * @todo Make this handle param types other than `string`
	 * @param string $request
	 * @return array
	 */
	public static function parseXmlRpcMethodCall($request) {
		$req = new DOMDocument('1.0');
		$req->loadXML($request);

		$q = new DOMXPath($req);
		$methodName = $q->query('/methodCall/methodName')->item(0)->nodeValue;

		$params = $q->query('/methodCall/params/param/value/string');
		$paramArray = [];

		foreach ($params as $node) {
			$paramArray[] = $node->nodeValue;
		}

		return [
			'methodName' => $methodName,
			'params' => $paramArray
		];
	}

	/**
	 * Create XML-RPC Success Response
	 * 
	 * Given the value to return (can only be a string at the mo), creates a 
	 * response.
	 * 
	 * @param string $retVal The string to return
	 * @param bool $asString Whether or not to return the respons as a string or DOMDocument
	 * @return DOMDocument
	 */
	public static function createXmlRpcSuccessResponse($retVal, $asString = true) {
		$response = new DOMDocument('1.0');
		$methodResponse = new DOMElement('methodResponse');
		$response->appendChild($methodResponse);

		$string = new DOMElement('string', $retVal);
		$value = $response->createElement('value');
		$value->appendChild($string);
		$param = $response->createElement('param');
		$param->appendChild($value);
		$params = $response->createElement('params');
		$params->appendChild($param);
		$methodResponse->appendChild($params);

		if (!$asString)
			return $response;
		else
			return $response->saveXML();
	}

	/**
	 * Create XML-RPC Fault Response
	 * 
	 * Creates an XML-RPC Fault response with either an int or a string as the
	 * fault.
	 * 
	 * @param string|int $fault
	 * @param bool $asString
	 * @return \DOMDocument
	 */
	public static function createXmlRpcFaultResponse($fault, $asString = true) {
		if (is_string($fault))
			$type = 'string';
		elseif (is_int($fault))
			$type = 'int';

		$doc = new DOMDocument('1.0');
		$methodResponse = new DOMElement('methodResponse');
		$doc->appendChild($methodResponse);

		$int = $doc->createElement($type, $fault);
		$value = $doc->createElement('value');
		$value->appendChild($int);
		$fault = $doc->createElement('fault');
		$fault->appendChild($value);
		$methodResponse->appendChild($fault);

		if (!$asString)
			return $doc;
		else
			return $doc->saveXML();
	}

	/**
	 * Replace Element With HTML
	 * @param DOMElement $el
	 * @param string $html XML-compatible HTML
	 * @return null
	 */
	public static function replaceElementWithHtml(DOMElement $el, $html) {
		$replacement = $el->ownerDocument->createDocumentFragment();
		$replacement->appendXML($html);
		$el->parentNode->replaceChild($replacement, $el);
	}

	public static function urlHost($url) {
		return parse_url($url, PHP_URL_HOST);
	}

	public static function urlPath($url) {
		return parse_url($url, PHP_URL_PATH);
	}

	public static function urlFilename($url, $noExtension = false) {
		$segments = explode('/', trim(self::urlPath($url), '/'));
		$fullName = array_pop($segments);

		return $noExtension ? pathinfo($fullName, PATHINFO_BASENAME) : $fullName;
	}

	/**
	 * Get Inner HTML
	 * 
	 * @param DOMDocument|DOMNode $doc
	 * @return string
	 */
	public static function innerHtml($doc) {
		if ($doc instanceof \DOMDocument)
			$childNodes = $doc->documentElement->childNodes;
		else
			$childNodes = $doc->childNodes;

		$out = '';
		foreach ($childNodes as $child) {
			$out .= $doc->saveXML($child);
		}

		return $out;
	}

	/**
	 * To x-www-form-urlencoded
	 * 
	 * @todo make this handle lists correctly
	 * @todo possibly handle nested arrays correctly
	 * @param array $data
	 */
	public static function toFormUrlencoded(array $data) {
		$pairs = [];

		foreach ($data as $key => $val) {
			if (is_array($val))
				$pairs[] = self::toFormUrlencoded($val);
			else
				$pairs[] = urlencode($key) . '=' . urlencode($val);
		}

		return implode('&', $pairs);
	}

}
// EOF Helpers.php