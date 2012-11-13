<?php

namespace Tests\BarnabyWalters\Posse;

use BarnabyWalters\Posse\Truncenator;

/**
 * Description of TruncenatorTest
 *
 * @author Barnaby Walters
 */
class TruncenatorTest extends \PHPUnit_Framework_TestCase {
    /**
     * @group unit
     * @group text
     * @group helpers
     * @group truncenator
     */
    public function testTruncateRespectsLength() {
        $input = 'Here is some text which is over 140 characters long Here is some text which is over 140 characters long Here is some text which is over 140 characters long';
        $expected = 'Here is some text which is over 140 characters long Here is some text which is over 140 characters long Here is some text which is over 140…';
        $this->assertEquals($expected, Truncenator::truncate($input, $length = 140));
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
        $this->assertEquals($expected, Truncenator::truncate($input, $length = 140));
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
        $this->assertEquals($expected, Truncenator::truncate($input, $length = 140, $uri = $uri));
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
        $this->assertEquals($expected, Truncenator::truncate($input, $length = 140, $uri = $uri));
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
        $this->assertEquals($expected, Truncenator::truncate($input, $length = 140, $uri = $uri));
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
        $this->assertEquals($expected, Truncenator::truncate($input, $length = 140, $uri = $uri, $urilen = $urilen));
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
        $this->assertEquals($expected, Truncenator::truncate($input, $length = 140, $uri = $uri, $urilen = $urilen));
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
        $this->assertEquals($expected, Truncenator::truncate($input, $length = 140, $uri = $uri));
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
        $this->assertEquals($expected, Truncenator::truncate($input, $length = 140, $uri = $uri));
    }
}

// EOF
