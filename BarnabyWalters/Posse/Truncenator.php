<?php

namespace BarnabyWalters\Posse;

use BarnabyWalters\Helpers\Helpers as H;

/**
 * THE TRUNCENATOR
 *
 * @author Barnaby Walters
 */
class Truncenator {
    
    /**
     * ~ THE TRUNCENATOR ~
     * 
     * Takes a string (tweet-like note) and some config params, produces a truncated version to spec.
     * 
     * @param string	$string		The string to be truncated
     * @param int		$length		The maximum length of the output
     * @param string	$ellipsis	The string to append in the case of truncation
     * @param string	$uri		The canonical URI of the post, to be added to the end
     * @param int		$urilen		Treat any URLs as if they were this length
     * @param bool		$parens		If trucation is not required, surround the canon. link with parens (())
     * @param int		$hashtags	The number of hashtags present in the text to preserve if trucation occurs
     * 
     * @return string The truncated string
     * @todo A lot of this functionality is not properly implemented
     */
    public static function truncate($string, $length = 140, $uri = null, $urilen = null, $parens = true, $ellipsis = '…', $hastags = 1) {
        mb_internal_encoding('UTF-8');

        // Figure out total append length if truncation occurs
        $append = $ellipsis;
        if (!empty($uri))
            $append .= ' ' . $uri;

        // if $urilen is set, create array of URIs within the text and replace them with dummy text @ $urilen chars
        if (is_int($urilen)) {
            $uris = array();
            foreach (H::findUrls($string, $tidy = false) as $key => $url) {
                $dummy = 'URL' . $key;
                $dummy .= str_repeat('X', $urilen - mb_strlen($dummy));
                $uris[$dummy] = $url;
                $string = str_replace($url, $dummy, $string);
            }
        }

        // Truncate string to nearest WB below that length
        $matches = array();
        $words = array();
        preg_match_all('/\b\w+\b/', $string, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $match) {
            // For each match
            $words[] = array($match[1], $match[0]);
        }
        // $words = {[offset, 'string'], [offset, 'string'] •••}

        $maxplainlen = $length - H::uriMbStrlen($append, $urilen);

        // See if truncation will happen
        if (H::uriMbStrlen($string, $urilen) > $maxplainlen) {
            foreach ($words as $key => $word) {
                // Is the current word the first to cross $maxplainlen?
                if ($word[0] > $maxplainlen or $word[0] + mb_strlen($word[1]) > $maxplainlen) {
                    // Yes. The current word and all words after it must be removed
                    $plaintargetlen = $words[$key - 1][0] + mb_strlen($words[$key - 1][1]);
                    break;
                }
            }

            if (!isset($plaintargetlen))
                $plaintargetlen = $maxplainlen;

            // Truncate string
            $truncatedplain = mb_substr($string, 0, $plaintargetlen);

            // Add the append
            $trunc = $truncatedplain . $append;
        }
        else {
            // If no trucation required, just append the URL
            // TODO: if adding the space and brackets will push over the edge, remove enough words to compensate
            // TODO: write edge-case test to cover that scenario
            $trunc = $string . ' (' . $uri . ')';
        }

        // if $urilen set, expand dummies into full URIs
        if (is_int($urilen)) {
            foreach ($uris as $dummy => $uri) {
                $trunc = str_replace($dummy, $uri, $trunc);
            }
        }

        return $trunc;
    }
}

// EOF
