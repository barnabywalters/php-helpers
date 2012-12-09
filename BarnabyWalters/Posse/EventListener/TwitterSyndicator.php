<?php

namespace BarnabyWalters\Posse\EventListener;

use ActivityStreams\ActivityStreams\ObjectInterface;
use ActivityStreams\Event\ActivityEvent;
use ActivityStreams\Event\ActivityEvents;
use BarnabyWalters\Posse\Helpers;
use Guzzle\Http\Client;
use Guzzle\Plugin\Oauth\OauthPlugin;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * TwitterSyndicator
 * 
 * A plugin which uses BarnabyWalters\Posse\Helpers to syndicate an object to
 * Twitter. It listens to the `activitystreams.post.post` event.
 * 
 * ## Activation
 * 
 * Like any plugin, enable TwitterSyndicator for use by adding it’s classname 
 * to the `plugins` array in `config.yml`. It should look something like this:
 * 
 *     plugins:
 *       - BarnabyWalers\Posse\EventListener\TwitterSyndicator
 * 
 * For TwitterSyndicator to work, you must configure it correctly.
 * 
 * ## Configuration
 * 
 * Like any plugin, TwitterSyndicator can be configured by adding values to
 * config.yml under the `BarnabyWalers\Posse\EventListener\TwitterSyndicator`
 * key. There are some examples below if you haven’t configured a plugin before.
 * 
 * TwitterSyndicator syndicates conditionally based on tags. This allows you to
 * set whether or not a post is syndicated to twitter when you’re writing it, by
 * tagging it with a tag of your choice. You can also choose to syndicate all
 * objects *unless* they have a certain tag.
 * 
 * By default, TwitterSyndicator is configured to only syndicate objects tagged
 * with `tweet`. You can set both the strategy and the tag to your liking:
 * 
 * If you want to syndicate objects tagged with `t`, use this configuration:
 * 
 *     BarnabyWalters\Posse\EventListener\TwitterSyndicator:
 *       tag: t
 * 
 * If you want to syndicate eveything to twitter *unless* it’s tagged, configure
 * like this:
 * 
 *     BarnabyWalters\Posse\EventListener\TwitterSyndicator:
 *       tag: notweet
 *       strategy: syndicate unless tagged
 * 
 * ### Twitter Credentials (Required)
 * 
 * Valid Twitter credentials are required for TwitterSyndicator to work properly.
 * They should look something like this in config.yml:
 * 
 *     BarnabyWalters\Posse\EventListener\TwitterSyndicator:
 *       twitterCredentials:
 *         consumerKey: your_consumer_key
 *         consumerSecret: your_consumer_secret
 *         accessToken: your_access_token
 *         accessTokenSecret: your_access_token_secret
 * 
 * ## Usage
 * 
 * Use by tagging your posts with whatever tag you’ve set up. Posts tagged as so
 * will be truncated and syndicated to twitter. If the object supports downstream
 * duplicates, the URL of the syndicated tweet will be added to the list, allowing
 * for various cool stuff.
 * 
 * THE TRUNCENATOR is used internally to intelligently truncate the tweet and add
 * a canonical URL to the end. If no truncation happens, the URL will be in 
 * parentheses so users know there’s no extra text to see.
 * 
 * If the syndicate object has an inReplyTo URL which is a twitter status URL,
 * the status_id will be automatically parsed out of it and added to the syndicated
 * tweet. **BUT**, note that Twitter will only accept replies as replies if they
 * contain the at-name of the user they’re in reply to. If they don’t they will
 * still be tweeted, but won’t show up in conversation views.
 * 
 * @author Barnaby Walters http://waterpigs.co.uk <barnaby@waterpigs.co.uk>
 * @since 0.1.4
 */
class TwitterSyndicator implements EventSubscriberInterface {
    const STRATEGY_SYNDICATE_UNLESS_TAGGED = 'SYNDICATE_UNLESS_TAGGED';
    const STRATEGY_SYNDICATE_IF_TAGGED = 'SYNDICATE_IF_TAGGED';
    
    public $tag = 'tweet';
    public $strategy = self::STRATEGY_SYNDICATE_IF_TAGGED;
    public $twitterApiVersion = '1.1';
    
    private $twitterCredentials;
    private $client;
    /** @var Logger */
    private $logger;
    
    public static function getSubscribedEvents() {
        return array('activitystreams.post.post' => array('syndicateToTwitter', ActivityEvents::POST_POST_GUARANTEE_SYNDICATED + 10));
    }
    
    /**
     * Constructor
     * @param array $config
     * @todo Add support for different twitter API versions?
     */
    public function __construct(array $config = array()) {
        if (array_key_exists('strategy', $config))
                $this->strategy = strtoupper(str_replace (' ', '_', $config['strategy']));
        
        if (array_key_exists('tag', $config))
                $this->tag = strtolower($config['tag']);
        
        if (array_key_exists('twitterCredentials', $config))
            $this->twitterCredentials = $config['twitterCredentials'];
    }
    
    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Set Guzzle
     * 
     * Used internally for testing. Forces `syndicateToTwitter()` to use a mock
     * object implementing Guzzle\Http\ClientInterface as the client.
     * 
     * @param \Guzzle\Http\ClientInterface $client
     * @internal
     */
    public function setGuzzle(\Guzzle\Http\ClientInterface $client) {
        $this->client = $client;
    }
    
    /**
     * Syndicate To Twitter
     * 
     * Checks to see if the content is designated to be syndicated, syndicates 
     * it, then updates it’s downstreamDuplicates property with the new copy on
     * Twitter.
     * 
     * 1. Checks to see if `$event` is an `ActivityEvent`
     * 1. Checks to see if `$event->getObjects()` has `::getTags()`
     * 1. Taking `$this->strategy` into account, determine whether or not the 
     *    object should be syndicate to twitter from it’s tags
     * 1. Syndicate to Twitter
     * 1. Build tweet URL from response JSON
     * 1. If the object supports `::addDownstreamSuplicate()`, call that
     * 1. Otherwise, if the object supports `::get/setDownstreamDuplicate`, get
     *    the duplicates, append the tweet URL and set them again.
     * 
     * @param \ActivityStreams\Event\ActivityEvent $event
     * @return string|null The strings are just for test debugging purposes
     */
    public function syndicateToTwitter(Event $event) {
        if (!$event instanceof ActivityEvent)
            return 'Not an ActivityEvent';
        
        /* @var $object ObjectInterface */
        $object = $event['object'];
        
        if (!array_key_exists('tags', $object))
            return 'Object has tags so cannot determine whether or not to syndicate';
        
        $tags = $object['tags'];
        
        if (!is_array($tags))
            return 'Tags are not an array';
        
        $syndicating = false;
        
        if (($this->strategy == self::STRATEGY_SYNDICATE_IF_TAGGED
            and in_array($this->tag, $tags))
        or ($this->strategy == self::STRATEGY_SYNDICATE_UNLESS_TAGGED)
            and !in_array($this->tag, $tags))
                $syndicating = true;
        
        if (!$syndicating)
            return 'Object is not a syndication candidate';
        
        // Remove syndication tag as it’s transient
        $object['tags'] = array_diff($tags, array($this->tag));
        
        // We’re syndicating!
        $content = $object['content'] ?: $object['summary'];
        $url = $object['url'];
        
        if (array_key_exists('inReplyTo', $object))
            $inReplyTo = $object['inReplyTo'];
        else
            $inReplyTo = null;
        
        $twitterApiQuery = Helpers::prepareForTwitter($content, $url, $inReplyTo);
        
        if ($this->logger !== null)
            $this->logger->info("Built Twitter Query", $twitterApiQuery);
        
        if ($this->client !== null) {
            $client = $this->client;
        } else {
            $client = new Client('https://api.twitter.com/{version}/', array(
                'version' => $this->twitterApiVersion,
                'ssl.certificate_authority' => 'system'
            ));
        }
        
        $oauth = new OauthPlugin(array(
            'consumer_key'    => $this->twitterCredentials['consumerKey'],
            'consumer_secret' => $this->twitterCredentials['consumerSecret'],
            'token'           => $this->twitterCredentials['accessToken'],
            'token_secret'    => $this->twitterCredentials['accessTokenSecret']
        ));
        $client->addSubscriber($oauth);
        
        // Try to syndicate to twitter
        try {
            $request = $client->post('statuses/update.json')
                ->addPostFields($twitterApiQuery);
            $response = $request->send();
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            $this->logger->err('Twitter syndication attempt failed', array(
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ));
            
            return;
        } catch (Guzzle\Http\Exception\CurlException $e) {
            $this->logger->err('Twitter syndication attempt failed with CURL exception', array(
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ));
            
            return;
        }
        
        $tweet = $response->json();
        
        if ($this->logger !== null)
            $this->logger->info('Received response', $tweet);
        
        // Add the id and a generated URL for the tweet to $object.downstreamCopies
        $tweetId = $tweet['id_str'];
        $tweetUserHandle = $tweet['user']['screen_name'];
        
        $tweetUrl = 'https://twitter.com/' . $tweetUserHandle . '/statuses/' . $tweetId;
        
        $object->addDownstreamDuplicate($tweetUrl);
        
        return true;
    }
}

// EOF