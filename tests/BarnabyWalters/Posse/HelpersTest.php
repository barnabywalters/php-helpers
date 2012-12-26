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
        $inReplyTo = 'https://twitter.com/someuser/statuses/100';
        
        $result = Helpers::prepareForTwitter($text, $url, $inReplyTo);
        
        $this->assertTrue(strlen($result['status']) <= 140);
        $this->assertEquals('100', $result['in_reply_to_status_id']);
    }
    
    public function testFullConvertHtmlToTwitterSyntaxExample() {
        $text = <<<EOD
<p>Let’s try them out: <em>emphasised</em>, <strong>strong</strong>,</p>

<blockquote>“Some quote” <small><a class="auto-link" href="http://twitter.com/barnabywalters"><a class="h-card" rel="me" href="http://waterpigs.co.uk" data-at-name="barnabywalters">Barnaby Walters</a></a></small></blockquote>

<p><a rel="tag" href="/tags/indieweb">#indieweb</a></p>
EOD;
        $expected = <<<EOD
Let’s try them out: *emphasised*, **strong**,

“Some quote” — @barnabywalters

#indieweb
EOD;
        
        
        $this->assertEquals($expected, Helpers::convertHtmlToTwitterSyntax($text));
    }
    
    public function testRevertsAtNamedElements() {
        $text = 'Here is some text with an auto-h-carded element: <span class="h-card" data-at-name="someguy">Some Guy’s Name</span>';
        $expected = 'Here is some text with an auto-h-carded element: @someguy';
        
        $this->assertEquals($expected, Helpers::convertHtmlToTwitterSyntax($text));
    }
    
    public function testReplacesEmWithAsterisks() {
        $text = 'Here is some text with an <em>emphasised</em> word';
        $expected = 'Here is some text with an *emphasised* word';
        
        $this->assertEquals($expected, Helpers::convertHtmlToTwitterSyntax($text));
    }
    
    public function testReplacesStrongWithAsterisks() {
        $text = 'Here is some text with an <strong>emphasised</strong> word';
        $expected = 'Here is some text with an **emphasised** word';
        
        $this->assertEquals($expected, Helpers::convertHtmlToTwitterSyntax($text));
    }
    
    public function testAddsHyphenToBlockquoteSmallPattern() {
        $text = 'OH <blockquote>“This is some quote”<small>Name</small></blockquote>';
        $expected = 'OH “This is some quote” — Name';
        
        $this->assertEquals($expected, Helpers::convertHtmlToTwitterSyntax($text));
    }
}

// EOF
