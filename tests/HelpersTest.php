<?php

namespace BarnabyWalters\Helpers\Test;

use BarnabyWalters\Helpers\Helpers as H;

// Load files
$vendorPath = realpath(dirname(__DIR__) . '/vendor/autoload.php');
if (file_exists($vendorPath)) {
    // If the vendor dir exists (the user is in a testing environment) load the autoloader
    ob_start();
    require $vendorPath;
    ob_end_clean();
} else {
    die('Cannot run tests as you haven’t installed the required dependencies');
}

/**
 * A test suite for barnabywalters/helpers
 * 
 * Contains tests for all my helper functions
 * 
 * @author Barnaby Walters http://waterpigs.co.uk
 * @autor app\helpers\tests
 * @todo Move all traces of THE TRUNCENATOR out of here and into their own package
 */
class HelpersTest extends \PHPUnit_Framework_TestCase {
    // !Logic

    /**
     * Test the truest() function
     * 
     * @group unit
     * @group logic
     * @group helpers
     */
    public function testTruestReturnsTrue() {
        // Should return the first true-ish value in args
        $this->assertTrue(H::truest(false, '', true));
    }

    /**
     * @group unit
     * @group logic
     */
    public function testTruestReturnFalseIfNoneTrue() {
        $this->assertFalse(H::truest(false, '', ''));
    }

    // !Text

    /**
     * @group text
     */
    public function testExpandImgExpandsImages() {
        $test = 'blah blah <img src="thevalue" />';
        $expected = 'blah blah thevalue';
        $this->assertEquals($expected, H::expandImg($test));
    }

    /**
     * @group text
     */
    public function testToAsciiSanitizes() {
        $test = 'QQQ!’^*+MOREQ';
        $expected = 'qqq-moreq';
        $this->assertEquals($expected, H::toAscii($test));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     */
    public function testAuthorFromUriTwitter() {
        $input = 'https://twitter.com/BarnabyWalters/status/254199790307524610';
        $expected = '@barnabywalters';
        $this->assertEquals($expected, H::authorFromUri($input));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     */
    public function testAuthorFromUriIndieweb() {
        $input = 'http://waterpigs.co.uk/notes/254';
        $expected = 'waterpigs.co.uk';
        $this->assertEquals($expected, H::authorFromUri($input));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     * @group truncenator
     */
    public function testTruncateRespectsLength() {
        $input = 'Here is some text which is over 140 characters long Here is some text which is over 140 characters long Here is some text which is over 140 characters long';
        $expected = 'Here is some text which is over 140 characters long Here is some text which is over 140 characters long Here is some text which is over 140…';
        $this->assertEquals($expected, H::truncate($input, $length = 140));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     * @group truncenator
     */
    public function testTruncatePreservesWords() {
        $input = 'Here is some text which is over 140 characters long Here is some text which is over 140 characters long Here is some text which is over longlonglongword';
        $expected = 'Here is some text which is over 140 characters long Here is some text which is over 140 characters long Here is some text which is over…';
        $this->assertEquals($expected, H::truncate($input, $length = 140));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     * @group truncenator
     */
    public function testTruncateAppendsURI() {
        $input = 'Here is some text which is over 140 characters long Here is some text which is over 140 characters long Here is some text which is over 140 characters long';
        $uri = 'http://example.org/notes/14';
        $expected = 'Here is some text which is over 140 characters long Here is some text which is over 140 characters long Here is… http://example.org/notes/14';
        $this->assertEquals($expected, H::truncate($input, $length = 140, $uri = $uri));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     * @group truncenator
     */
    public function testTruncateAppendsURIPreservingWords() {
        $input = 'Here is some text which is over 140 characters long Here is some text which is over 140 characters longwordlongwordlongwordlongword';
        $uri = 'http://example.org/notes/15';
        $expected = 'Here is some text which is over 140 characters long Here is some text which is over 140 characters… http://example.org/notes/15';
        $this->assertEquals($expected, H::truncate($input, $length = 140, $uri = $uri));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     * @group truncenator
     */
    public function testTruncateParenthesisesURIIfNotTruncated() {
        $input = 'Here is some really short text';
        $uri = 'http://example.org/notes/16';
        $expected = 'Here is some really short text (http://example.org/notes/16)';
        $this->assertEquals($expected, H::truncate($input, $length = 140, $uri = $uri));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     * @group truncenator
     */
    public function testTruncateRespectsURILength() {
        $input = 'Here is some text which is over 140 characters long Here is some text which is over 140 characters long Here is some text which is over 140 characters long';
        $uri = 'http://example.org/notes/14';
        $urilen = 20;
        $expected = 'Here is some text which is over 140 characters long Here is some text which is over 140 characters long Here is some… http://example.org/notes/14';
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     * @group truncenator
     */
    public function testTruncateRespectsTextURILength() {
        $input = 'Here is some text with a URI in: http://example.com and one without a protocol: example.org long Here is some text which is over 140 characters long Here is some text which is over 140 characters long';
        $uri = 'http://example.org/notes/14';
        $urilen = 20;
        $expected = 'Here is some text with a URI in: http://example.com and one without a protocol: example.org long Here is… http://example.org/notes/14';
        $this->assertEquals($expected, H::truncate($input, $length = 140, $uri = $uri, $urilen = $urilen));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     * @group truncenator
     */
    public function testTruncateURILenOverloadsAppendURILength() {
        $input = 'Here is some text over 140 characters long Here is some text over 140 characters long Here is some text over 140 characters long Here is some text over 140 characters long';
        $uri = 'http://example.org/notes/14';
        $urilen = 30; // over-engineering
        $expected = 'Here is some text over 140 characters long Here is some text over 140 characters long Here is some text over… http://example.org/notes/14';
        $this->assertEquals($expected, H::truncate($input, $length = 140, $uri = $uri, $urilen = $urilen));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     * @group truncenator
     */
    public function testTruncatePreservesHashtags() {
        $input = 'Here is some text with #hashtags in! Woo! And it’s really long really long really long really long really long really long really long with #morehashtags at the #end';
        $uri = 'http://example.com/notes/34';
        $expected = 'Here is some text with #hashtags in! Woo! And it’s really long really long really long really… #morehashtags http://example.com/notes/34';
        $this->assertEquals($expected, H::truncate($input, $length = 140, $uri = $uri));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     * @group truncenator
     */
    public function testTruncateNoParenthesisLengthOkay() {
        $input = 'Here is a piece of text 109 characters long Here is a piece of text 109 characters long there is piece text';
        $uri = 'http://example.com/articles/34'; // 30 long
        $expected = 'Here is a piece of text 109 characters long Here is a piece of text 109 characters long there is piece text (http://example.com/articles/34)';
        $this->assertEquals($expected, H::truncate($input, $length = 140, $uri = $uri));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     */
    public function testTimeElement() {
        $testTime = new \DateTime();
        $result = '<time datetime="' . $testTime->format('Y-m-d') . '" title="' . $testTime->format('Y-z') . '">' . $testTime->format('Y-m-d') . '</time>';
        $this->assertEquals($result, H::timeElement($testTime));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     */
    public function testTagstringToArray() {
        $testTagstring = 'a tag, anothertag, <sometag>';
        $testResult = array('a tag', 'anothertag', '&lt;sometag&gt;');
        $this->assertEquals($testResult, H::tagstringToArray($testTagstring));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     */
    public function testTagstringClean() {
        $testTagstring = 'a tag,   anothertag,	 <sometag>';
        $testResult = 'a tag,anothertag,&lt;sometag&gt;';
        $this->assertEquals($testResult, H::tagstringClean($testTagstring));
    }
    
    /**
     * @group unit
     * @group text
     */
    public function testAutolinkHashTags() {
        $testText = 'Hey there, #this tag should be auto-linked, as should #this';
        $expected = 'Hey there, <a rel="tag" href="/my/tags/this">#this</a> tag should be auto-linked, as should <a rel="tag" href="/my/tags/this">#this</a>';
        
        $this->assertEquals($expected, H::autolinkHashTags($testText, '/my/tags/'));
    }
    
    /**
     * @group unit
     * @group text
     * @group helpers
     */
    public function testGetPrivacyTagsIgnoresNonAuthTags() {
        $tags = array('sometag', 'someothertag');
        $expected = array();

        $this->assertEquals($expected, H::getAuthTags($tags));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     */
    public function testGetPrivacyTagsParsesMultiplePrivateTagsCorrectly() {
        $tags = array('auth:private=domain.com', 'auth:private=someotherdomain.com');
        $expected = array(
            'private' => array(
                'domain.com',
                'someotherdomain.com'
            )
        );

        $this->assertEquals($expected, H::getAuthTags($tags));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     */
    public function testGetPrivacyTagsParsesMultiplePrivateAndEditableTagsCorrectly() {
        $tags = array('auth:private=domain.com', 'auth:private=someotherdomain.com', 'auth:editable=someotherdomain.com');
        $expected = array(
            'private' => array(
                'domain.com',
                'someotherdomain.com'
            ),
            'editable' => array(
                'someotherdomain.com'
            )
        );

        $this->assertEquals($expected, H::getAuthTags($tags));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     */
    public function testAtomDate() {
        $this->assertEquals('2012-10-15T22:46:00+00:00', H::atomDate('15th October 2012 22:46'));
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     */
    public function testFindURLs() {
        $testString = 'Okay, so this string contains some URLs. http://waterpigs.co.uk, identi.ca, aaron.pk';
        $testArray = array('http://waterpigs.co.uk', 'http://identi.ca', 'http://aaron.pk');
        $this->assertEquals($testArray, H::findUrls($testString));
    }

}

// EOF