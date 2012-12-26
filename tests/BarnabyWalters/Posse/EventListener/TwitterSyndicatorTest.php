<?php

namespace Tests\BarnabyWalters\Posse\EventListener;

use BarnabyWalters\Posse\EventListener\TwitterSyndicator;
use Symfony\Component\EventDispatcher\Event;
use ActivityStreams\Event\ActivityEvent;
use ActivityStreams\ActivityStreams\Object;
use Guzzle\Http\Client;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;

/**
 * TwitterSyndicatorTest
 *
 * @author barnabywalters
 */
class TwitterSyndicatorTest extends \PHPUnit_Framework_TestCase {
    public function testReturnsFalseIfNotActivityEvent() {
        $t = new TwitterSyndicator();
        $this->assertEquals('Not an ActivityEvent', $t->syndicateToTwitter(new Event()));
    }
    
    public function testsReturnsErrorIfObjectHasNoTags() {
        $t = new TwitterSyndicator();
        $object = new Object('note');
        $object['tags'] = 'non-array';
        $event = new ActivityEvent('post', $object);
        
        $this->assertEquals('Tags are not an array', $t->syndicateToTwitter($event));
    }
    
    public function testDoesntSyndicateForIfStrategyAndNotTagged() {
        $t = new TwitterSyndicator();
        $object = new Object('note');
        $object['tags'] = array('random tag', 'another tag');
        $event = new ActivityEvent('post', $object);
        
        $this->assertEquals('Object is not a syndication candidate', $t->syndicateToTwitter($event));
    }
    
    public function testDoesntSyndicateForUnlessStrategyAndTagged() {
        $t = new TwitterSyndicator(array('strategy' => 'syndicate unless tagged'));
        $object = new Object('note');
        $object['tags'] = array('tweet');
        $event = new ActivityEvent('post', $object);
        
        $this->assertEquals('Object is not a syndication candidate', $t->syndicateToTwitter($event));
    }
    
    /**
     * @dataProvider configProvider
     */
    public function testAcceptsTagStrategyConfig($config, $tags, $expected) {
        $t = new TwitterSyndicator($config);
        
        $mockClient = new Client();
        $mock = new MockPlugin();
        $mock->addResponse(new Response(
                '201',
                array('Content-type: application/json'),
                '{"id_str":"12345678","user":{"screen_name":"username"}}'
        ));
        
        $mockClient->addSubscriber($mock);
        $t->setGuzzle($mockClient);
        
        $object = new Object('note');
        $object['tags'] = $tags;
        $object['content'] = 'Dummy content';
        $object['url'] = 'http://example.org/dummy/url';
        $event = new ActivityEvent('post', $object);
        
        $this->assertSame($expected, $t->syndicateToTwitter($event));
    }
    
    public function configProvider() {
        return array(
            array(
                array('strategy' => 'syndicate if tagged', 'tag' => 'tweetme'),
                'tags' => array('tweetme'),
                true
            ),
            array(
                array('strategy' => 'syndicate unless tagged', 'tag' => 'notweet'),
                'tags' => array('blah'),
                true
            ),
            array(
                array('strategy' => 'syndicate unless tagged', 'tag' => 'notweet'),
                'tags' => array('notweet'),
                'Object is not a syndication candidate'
            )
        );
    }
    
    public function testDownstreamDuplicateAddedAfterSyndication() {
        $t = new TwitterSyndicator();
        
        $mockClient = new Client();
        $mock = new MockPlugin();
        $mock->addResponse(new Response(
                '201',
                array('Content-type: application/json'),
                '{"id_str":"12345678","user":{"screen_name":"username"}}'
        ));
        
        $mockClient->addSubscriber($mock);
        $t->setGuzzle($mockClient);
        
        $object = new Object('note');
        $object['tags'] = array('tweet');
        $object['content'] = 'Dummy content';
        $object['url'] = 'http://example.org/dummy/url/again';
        $event = new ActivityEvent('post', $object);
        
        $t->syndicateToTwitter($event);
        
        $tweetUrl = 'https://twitter.com/username/status/12345678';
        
        $this->assertContains($tweetUrl, $object['downstreamDuplicates']);
    }
}

// EOF
