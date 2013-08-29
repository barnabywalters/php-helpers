<?php

namespace BarnabyWalters\Posse;

use BarnabyWalters\Helpers\Helpers as H;
use BarnabyWalters\Posse\Truncenator;
use DOMDocument;
use DOMXPath;

/**
 * Description of Helpers
 *
 * @author barnabywalters
 */
class Helpers {

	/**
	 * Prepare For Twitter
	 * 
	 * Takes some parameters and returns an array suitable for conversion to 
	 * a query string and submitting to the twitter API.
	 * 
	 * $text does not have to be under 140 chars, THE TRUNCENATOR is used 
	 * internally to truncate the content and append the $url within 140 chars.
	 * 
	 * If $inReplyTo is set and is a twitter.com status URL, the id will be 
	 * parsed out of it and added to the `in_reply_to_status_id` param of the
	 * return array.
	 * 
	 * Note that twitter will not process a tweet as a reply correctly if the @name of the
	 * target is not included.
	 * 
	 * @param string $text The body text
	 * @param string $url = null A URL to append to the content, if any
	 * @param string $inReplyTo = null A URL the content is in reply to
	 * @return array An assoc. array ready to pass as POST vars to `statuses/update`
	 * 
	 * @todo Add some intelligent blockquote/other markdown handling
	 */
	public static function prepareForTwitter($text, $url = null, $inReplyTo = null) {
		// Create the tweet array
		$tweet = array();

		$tweet['status'] = self::convertHtmlToTwitterSyntax($text);

		// Run THE TRUNCENATOR using defaults suitable for twitter
		ob_start();
		$tweet['status'] = Truncenator::truncate(
				strip_tags(H::expandImg($tweet['status'])), $length = 140, $uri = $url, $urilen = 22
		);
		ob_end_clean();

		// Handle inReplyTo possibly being a URL or a flatformat
		// TODO: encapsulate this in an easily testable function
		if (!empty($inReplyTo)) {
			if (is_string($inReplyTo))
				$inReplyTo = [$inReplyTo];
			elseif (is_array($inReplyTo) and isset($inReplyTo['type']))
				$inReplyTo = isset($inReplyTo['properties']['syndication'])
					? $inReplyTo['properties']['syndication']
					: [$inReplyTo['properties']['url']] ?: [];
			
			$tweetPattern = '/https?:\/\/twitter.com\/[a-zA-Z_]{1,20}\/status\/([0-9]*)/';
			
			foreach ($inReplyTo as $irt):
				$matches = [];
				if (preg_match($tweetPattern, $inReplyTo, $matches))
					$tweet['in_reply_to_status_id'] = $matches[1];
					break;
			endforeach;
		}

		return $tweet;
	}
	
	// TODO: make this not have escaping problems
	public static function convertHtmlToTwitterSyntax($text) {
		libxml_use_internal_errors(true);
		$dom = new DOMDocument('1.0', 'utf-8');
		$dom->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'));
		$xpath = new DOMXPath($dom);

		$atNamedElements = $xpath->query('//*[@data-at-name]');
		foreach ($atNamedElements as $e) {
			$atName = $e->getAttribute('data-at-name');
			$e->nodeValue = '@' . ltrim($atName, ' @');
		}

		$emElements = $xpath->query('//em');
		foreach ($emElements as $e) {
			$e->nodeValue = '*' . $e->nodeValue . '*';
		}

		$strongElements = $xpath->query('//strong');
		foreach ($strongElements as $e) {
			$e->nodeValue = '**' . $e->nodeValue . '**';
		}

		$blockquoteSmallElements = $xpath->query('//blockquote/small');
		foreach ($blockquoteSmallElements as $e) {
			$e->nodeValue = '— ' . ltrim($e->nodeValue);
		}

		return $dom->C14N(true, false, array('query' => '//text()'));
	}

}
