<?php

require_once 'URLNormalizer.php';

class URLNormalizerTest extends PHPUnit_Framework_TestCase
{
    protected $fixture;
    private $test_url = 'http://www.yahoo.com/';
    
    protected function setUp()
    {
        $this->fixture = new URLNormalizer();
        $this->fixture->setUrl( $this->test_url );
    }
    
    public function testClassCanBeInstantiated() {
        $this->assertTrue( is_object( $this->fixture ) );
    }
    
    public function testObjectIsOfCorrectType() {
        $this->assertTrue( get_class( $this->fixture ) == 'URLNormalizer' );
    }
    
    public function testObjectHasGetUrlMethod() {
        $this->assertTrue( method_exists( $this->fixture, 'getUrl' ) );
    }
    
    public function testSetUrl() {
        $this->assertTrue( $this->fixture->getUrl() == $this->test_url );
    }
    
    public function testObjectHasGetSchemeMethod() {
        $this->assertTrue( method_exists( $this->fixture, 'getScheme' ) );
    }
    
    public function testSchemeExtractedFromUrl() {
        $this->assertTrue( $this->fixture->getScheme() == 'http' );
    }
    
    /**
     * @dataProvider provider
     */
    public function testUrlsAreNormalised( $url, $normalised_url ) {
        $this->fixture->setUrl( $url );
        
        $this->assertEquals( $normalised_url, $this->fixture->normalize() );
    }
    
    public function provider() {
        // tests from http://en.wikipedia.org/wiki/URL_normalization
        return array(
            array( 'HTTP://www.Example.com/',            'http://www.example.com/' ),  # converting the scheme and host to lowercase
            array( 'http://www.example.com',             'http://www.example.com/' ),  # add trailing /
            array( 'eXAMPLE://a/./b/../b/%63/%7bfoo%7d', 'example://a/b/c/%7Bfoo%7D' ),
        );
    }
    
    public function testCaseIsNormalization() {
        $this->fixture->setUrl( 'http://www.yahoo.com/%a1' );
        $this->assertEquals( 'http://www.yahoo.com/%A1', $this->fixture->normalize() );
    }

    /**
     * @dataProvider dotSegmentProvider
     *
     * http://www.apps.ietf.org/rfc/rfc3986.html#sec-5.2.4
     */
    public function testRemoveDotSegments( $path, $normalised_path ) {
        $this->assertEquals( $normalised_path, $this->fixture->removeDotSegments( $path ) );
    }
    
    public function dotSegmentProvider() {
        return array(
            array( '../',                '' ),
            array( './',                 '' ),
            array( '/./',                '/' ),
            array( '/.',                 '/' ),
            array( '/a/b/c/./../../g',   '/a/g' ),
            array( 'mid/content=5/../6', 'mid/6' ),
            array( '/foo/bar/.',         '/foo/bar/' ),
            array( '/foo/bar/./',        '/foo/bar/' ),
            array( '/foo/bar/..',        '/foo/' ),
            array( '/foo/bar/../',       '/foo/' ),
            array( '/foo/bar/../baz',    '/foo/baz' ),
            array('/foo/bar/../..',              '/'),
            array('/foo/bar/../../'  ,             '/'),
            array('/foo/bar/../../baz'  ,          '/baz'),
            #array('/foo/bar/../../../baz' ,        '/../baz'),
            array( 'a/./b/../b/',                        'a/b/' ),

        );
    }
    
    public function testDecodingUnreservedUrlChars() {
        $this->assertEquals( 'c', $this->fixture->urlDecodeUnreservedChars( '%63' ) );
        $this->assertEquals( 'c/%7b', $this->fixture->urlDecodeUnreservedChars( '%63/%7b' ) );
        $this->assertEquals( 'eXAMPLE://a/./b/../b/c/%7bfoo%7d', $this->fixture->urlDecodeUnreservedChars( 'eXAMPLE://a/./b/../b/%63/%7bfoo%7d' ) );
    }

	/**
	 * @dataProvider schemeData
	 *
	 * http://www.apps.ietf.org/rfc/rfc3986.html#sec-6.2.3
	 */
	public function testSchemeBasedNormalization( $url ) {
		$expected_uri = 'http://example.com/';
		
		$this->fixture->setUrl( $url );
		$this->assertEquals( $expected_uri, $this->fixture->normalize() );

	}
	
	public function schemeData() {
		return array( array( 'http://example.com' ),
					  array( 'http://example.com/' ),
					  array( 'http://example.com:/' ),
					  array( 'http://example.com:80/' ), );
	}
	
	public function testQueryParametersArePreserved() {
	    $url = 'http://fancysite.nl/links/doit.pl?id=2029';
	    
	    $this->fixture->setUrl( $url );
	    $this->assertEquals( $url, $this->fixture->normalize() );
	}
	
	public function testFragmentIdentifiersArePreserved() {
	    $url = 'http://example.com/index.html#fragment';
	    
	    $this->fixture->setUrl( $url );
	    $this->assertEquals( $url, $this->fixture->normalize() );
	}
}
