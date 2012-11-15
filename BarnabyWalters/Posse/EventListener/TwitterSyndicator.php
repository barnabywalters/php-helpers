<?php

namespace BarnabyWalters\Posse\EventListener;

use BarnabyWalters\Posse\Helpers;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use ActivityStreams\Event\ActivityEvent;
use ActivityStreams\ActivityStreams\ObjectInterface;
use Guzzle\Http\Client;
use Guzzle\Plugin\Oauth\OauthPlugin;

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
 * with `t`. You can set both the strategy and the tag to your liking:
 * 
 * If you want to syndicate objects tagged with `tweet`, use this configuration:
 * 
 *     BarnabyWalters\Posse\EventListener\TwitterSyndicator:
 *       tag: tweet
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
    
    public $tag = 't';
    public $strategy = self::STRATEGY_SYNDICATE_IF_TAGGED;
    public $twitterApiVersion = '1.1';
    
    private $twitterCredentials;
    private $client;
    
    /**
     * Constructor
     * @param array $config
     * @todo Add support for different twitter API versions?
     */
    public function __construct(array $config) {
        if (array_key_exists('strategy', $config))
                $this->strategy = strtoupper(str_replace (' ', '_', $config['strategy']));
        
        if (array_key_exists('tag', $config))
                $this->tag = strtolower($config['tag']);
        
        $this->twitterCredentials = $config['twitterCredentials'];
    }
    
    public static function getSubscribedEvents() {
        return ['activitystreams.post.post' => 'syndicateToTwitter'];
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
     * @return boolean
     */
    public function syndicateToTwitter(Event $event) {
        if (!$event instanceof ActivityEvent)
            return false;
        
        /* @var $object ObjectInterface */
        $object = $event->getObject();
        
        if (method_exists($object, 'getTags'))
            return false;
        
        $tags = $object->getTags();
        
        if (!is_array($tags))
            return false;
        
        $syndicating = false;
        
        if (($this->strategy == self::STRATEGY_SYNDICATE_IF_TAGGED
                and in_array($this->tag, $tags))
        or ($this->strategy == self::STRATEGY_SYNDICATE_UNLESS_TAGGED)
                and !in_array($this->tag, $tags))
                $syndicating = true;
        
        if (!$syndicating)
            return false;
        
        // We’re syndicating!
        $content = $object->getContent() ?: $object->getSummary();
        $url = $object->getUrl();
        
        if (method_exists($object, 'getInReplyTo'))
            $inReplyTo = $object->getInReplyTo();
        
        $twitterApiQuery = Helpers::prepareForTwitter($content, $url, $inReplyTo);
        
        if ($this->client !== null) {
            $client = $this->client;
        } else {
            $client = new Client('http://api.twitter.com/{version}', [
                'version' => $this->twitterApiVersion,
                'ssl.certificate_authority' => 'system',
            ]);
        }
        
        $oauth = new OauthPlugin(array(
            'consumer_key'    => $this->twitterCredentials['consumerKey'],
            'consumer_secret' => $this->twitterCredentials['consumerSecret'],
            'token'           => $this->twitterCredentials['accessToken'],
            'token_secret'    => $this->twitterCredentials['accessTokenSecret']
        ));
        $client->addSubscriber($oauth);
        
        // Syndicate to twitter
        $request = $client->post('statuses/update', null, $twitterApiQuery);
        $response = $request->send();
        $tweet = $response->json();
        
        // Add the id and a generated URL for the tweet to $object.downstreamCopies
        $tweetId = $tweet['id_str'];
        $tweetUserHandle = $tweet['user']['screen_name'];
        
        $tweetUrl = 'https://twitter.com/' . $tweetUserHandle . '/statuses/' . $tweetId;
        
        if (method_exists($object, 'addDownstreamDuplicate')) {
            $object->addDownstreamCopy($tweetUrl);
            return;
        }
        
        // Otherwise, manually add it if permitted
        if (method_exists($object, 'getDownstreamDuplicates')
        and method_exists($object, 'setDownstreamDuplicates')) {
            $copies = $object->getDownstreamCopies();
            array_push($copies, $tweetUrl);
            $object->setDownstreamCopies($copies);
            return;
        }
    }
}

// EOF