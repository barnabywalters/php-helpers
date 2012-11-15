<?php

namespace BarnabyWalters\Posse;

use BarnabyWalters\Helpers\Helpers as H;
use BarnabyWalters\Posse\Truncenator;

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
        // We'd prefer the raw summary as it has twitter names (not HTML h-cards),
        // but if that doesn't exist just strip tags out of the activity summary
        $tweet['status'] = $text;

        // Run THE TRUNCENATOR using defaults suitable for twitter
        ob_start();
        $tweet['status'] = Truncenator::truncate(
            strip_tags(H::expandImg($tweet['status'])),
            $length = 140,
            $uri = $url,
            $urilen = 20
        );
        ob_end_clean();

        if ($inReplyTo !== null) {
            // Check if there’s a twitter status ID in the URL to use
            $tweetPattern = '/https?:\/\/twitter.com\/[a-zA-Z_]{1,20}\/status\/([0-9]*)/';
            $matches = array();
            preg_match($tweetPattern, $inReplyTo, $matches);
            
            $tweet['in_reply_to_status_id'] = $matches[1];
        }

        return $tweet;
    }
}

// EOF
