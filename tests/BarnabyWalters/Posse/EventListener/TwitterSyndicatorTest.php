<?php

namespace Tests\BarnabyWalters\Posse\EventListener;

use BarnabyWalters\Posse\EventListener\TwitterSyndicator;
use Symfony\Component\EventDispatcher;
use Guzzle\Http\Client;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;

/**
 * TwitterSyndicatorTest
 *
 * @author barnabywalters
 */
class TwitterSyndicatorTest extends \PHPUnit_Framework_TestCase {
	public function testsReturnsErrorIfObjectHasNoTags() {
		$t = new TwitterSyndicator();
		$event = new EventDispatcher\GenericEvent;
		$event['object'] = ['tags' => 'non-array'];
		
		$this->assertEquals('Tags are not an array', $t->syndicateToTwitter($event));
	}
	
	public function testDoesntSyndicateForIfStrategyAndNotTagged() {
		$t = new TwitterSyndicator();
		$event = new EventDispatcher\GenericEvent;
		$event['object'] = ['tags' => ['random tag', 'another tag']];
		
		$this->assertEquals('Object is not a syndication candidate', $t->syndicateToTwitter($event));
	}
	
	public function testDoesntSyndicateForUnlessStrategyAndTagged() {
		$t = new TwitterSyndicator(array('strategy' => 'syndicate unless tagged'));
		$event = new EventDispatcher\GenericEvent;
		$event['object'] = ['tags' => ['tweet']];
		
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
		
		$object = [];
		$object['tags'] = $tags;
		$object['content'] = 'Dummy content';
		$object['url'] = 'http://example.org/dummy/url';
		$event = new EventDispatcher\GenericEvent;
		$event['object'] = $object;
		
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
		
		$object = [];
		$object['tags'] = array('tweet');
		$object['content'] = 'Dummy content';
		$object['url'] = 'http://example.org/dummy/url/again';
		$event = new EventDispatcher\GenericEvent;
		$event['object'] = $object;
		
		$t->syndicateToTwitter($event);
		
		$tweetUrl = 'https://twitter.com/username/status/12345678';
		
		$this->assertContains($tweetUrl, $event['object']['syndication']);
	}
}
