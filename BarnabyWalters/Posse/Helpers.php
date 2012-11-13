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
     * Takes an activity and returns an assoc. array suitable for POSTing to statuses/update.
     * 
     * Looks inside `$activity->content` and `$activity -> title` for the text for the status
     * {@todo look elsewhere too}, then truncates it using THE TRUNCENATOR set to maxlen of
     * 140, urilen of 20 and uri of $activity -> object -> url (string so we’re okay). The
     * result is stored in the `status` key of the return array.
     * 
     * Then we check `$activity -> object -> inReplyTo -> url` for a twitter.com status URL. If
     * it is one, we extract the status ID and put it in the `in_reply_to_status_id` key of
     * the return array.
     * 
     * Note that twitter will not process a tweet as a reply correctly if the @name of the
     * target is not included.
     * 
     * @param 
     * @return array An assoc. array ready to pass as POST vars to `statuses/update`
     */
    public static function prepareForTwitter($text, $url = null, $inReplyTo = null) {
        // Create the tweet array
        $tweet = array();
        // We'd prefer the raw summary as it has twitter names (not HTML h-cards),
        // but if that doesn't exist just strip tags out of the activity summary
        $tweet['status'] = $text;

        // Run THE TRUNCENATOR using defaults suitable for twitter
        ob_start();
        $tweet['status'] = H::truncate(
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
            preg_match($tweetPattern, $activity->object->inReplyTo->url, $matches);
            $tweet['in_reply_to_status_id'] = $matches[1];
        }

        return $tweet;
    }
}

// EOF
