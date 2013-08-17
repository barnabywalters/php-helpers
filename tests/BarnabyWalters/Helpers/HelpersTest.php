<?php

namespace Tests\BarnabyWalters\Helpers;

use BarnabyWalters\Helpers\Helpers as H;

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
    public function testGetMachineTagsIgnoresNonMachineTags() {
        $arr = [
            'machine:tag=yes!',
            'non-machine-tag'
        ];
        
        $result = H::getMachineTags($arr);
        
        $this->assertNotContains('non-machine-tag', $result);
    }
    
    /**
     * @group unit
     * @group text
     * @group helpers
     */
    public function testGetMachineTagsReturnsAssocArray() {
        $arr = [
            'machine:tag=yes!',
            'another:tag=BOO'
        ];
        
        $expected = [
            'machine:tag' => 'yes!',
            'another:tag' => 'BOO'
        ];
        
        $result = H::getMachineTags($arr);
        
        $this->assertEquals($result, $expected);
    }

    /**
     * @group unit
     * @group text
     * @group helpers
     */
    public function testGetMachineTagsFiltersByNamespace() {
        $arr = [
            'machine:tag=yes!',
            'another:tag=BOO'
        ];
        
        $expected = [
            'machine:tag' => 'yes!'
        ];
        
        $result = H::getMachineTags($arr, 'machine');
        
        $this->assertEquals($result, $expected);
    }
    
    /**
     * @group unit
     * @group text
     * @group helpers
     */
    public function testGetMachineTagsTruncatesNamespace() {
        $arr = [
            'machine:tag=yes!',
            'another:tag=BOO'
        ];
        
        $expected = [
            'tag' => 'yes!'
        ];
        
        $result = H::getMachineTags($arr, 'machine', true);
        
        $this->assertEquals($result, $expected);
    }
    
    /**
     * @group unit
     * @group text
     * @group helpers
     */
    public function testGetMachineTagsHandlesEqualsSignsInValue() {
        $arr = [
            'machine:tag=yes is = to something!',
        ];
        
        $expected = [
            'machine:tag' => 'yes is = to something!'
        ];
        
        $result = H::getMachineTags($arr);
        
        $this->assertEquals($result, $expected);
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
    public function testFindUrlsFindsUrlsInPlaintext() {
        $testString = 'Okay, so this string contains some URLs. http://waterpigs.co.uk, identi.ca, aaron.pk';
        $testArray = array('http://waterpigs.co.uk', 'http://identi.ca', 'http://aaron.pk');
        
        $this->assertEquals($testArray, H::findUrls($testString));
    }
    
    public function testFindUrlsFindsUrlsInAHref() {
        $html = '<div class="p-name entry-title p-summary summary e-content entry-content">
            <p>T5 <a rel="tag" href="/tags/pingback">#pingback</a> mentioning <a href="http://localhost.com/notes/711/">another note</a> <a rel="tag" href="/tags/test">#test</a> <a rel="tag" href="/tags/notweet">#notweet</a></p>
        </div>';
        $expected = ['http://localhost.com/notes/711'];
        $result = H::findUrls($html);
        
        $this->assertEquals($expected, $result);
    }
    
    public function testFindUrlsFindsIpAddresses() {
        $html = 'Blah blah blah 127.0.0.1/notes/711';
        $expected = ['http://127.0.0.1/notes/711'];
        $result = H::findUrls($html);
        
        $this->assertEquals($expected, $result);
    }
    
    public function testToFormUrlEncodedHandlesAssocArray() {
        $data = ['thing' => 'name', 'another' => 'BLAH'];
        $expected = 'thing=name&another=BLAH';
        $result = H::toFormUrlencoded($data);
        
        $this->assertEquals($expected, $result);
    }
		
		public function testComposeReturnsFunction() {
			$trimmedStrlen = H::compose(['strlen', 'trim']);
			$this->assertEquals(1, $trimmedStrlen(' h '));
		}
		
		public function testComposeApplysComposition() {
			$this->assertEquals(1, H::compose(['strlen', 'trim'], [' h ']));
		}
}
