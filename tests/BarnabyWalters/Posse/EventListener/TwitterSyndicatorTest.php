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
        $this->assertFalse($t->syndicateToTwitter(new Event()));
    }
    
    public function testsReturnsFalseIfObjectHasNoTags() {
        $t = new TwitterSyndicator();
        $object = new Object();
        $object->setTags(array());
        $event = new ActivityEvent('post', $object);
        
        $this->assertFalse($t->syndicateToTwitter($event));
    }
}

// EOF
