<?php

namespace Tests\BarnabyWalters\Posse;

use BarnabyWalters\Posse\Helpers;

/**
 * Description of HelpersTest
 *
 * @author barnabywalters
 */
class HelpersTest extends \PHPUnit_Framework_TestCase {
    
    /**
     * Test Prepare Content For Twitter
     * 
     * This doesn’t deal with generic TRUNCENATOR stuff, see THE TRUNCENATOR 
     * tests for that. Rather, this makes sure that twitter-specific stuff is 
     * dealt with (e.g. in reply to URL), and that the return format is correct.
     */
    public function testPrepareContentForTwitter() {
        $text = 'Lots and lots and lots of very long text Lots and lots and lots of very long text Lots and lots and lots of very long text';
        $url = 'http://example.com';
        $inReplyTo = 'https://twitter.com/someuser/status/100';
        
        $result = Helpers::prepareForTwitter($text, $url, $inReplyTo);
        
        $this->assertTrue(strlen($result['status']) <= 140);
        $this->assertEquals('100', $result['in_reply_to_status_id']);
    }
}

// EOF
