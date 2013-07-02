<?php

require_once 'URLNormalizer.php';

class URLNormalizerTest extends PHPUnit_Framework_TestCase
{
    protected $fixture;
    private $test_url = 'http://www.yahoo.com/';
    
    protected function setUp()
    {
        $this->fixture = new URLNormalizer();
        //$this->fixture->setUrl( $this->test_url );
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
    
    public function testSetUrlFromConstructor() {
    	$this->fixture = new URLNormalizer( 'http://www.example.com/' );
    	$this->assertTrue( $this->fixture->getUrl() == 'http://www.example.com/' );
    }
    
    public function testSetUrl() {
        $this->fixture->setUrl( $this->test_url );
        $this->assertTrue( $this->fixture->getUrl() == $this->test_url );
    }
    
    public function testObjectHasGetSchemeMethod() {
        $this->assertTrue( method_exists( $this->fixture, 'getScheme' ) );
    }
    
    public function testSchemeExtractedFromUrl() {
        $this->fixture->setUrl( $this->test_url );
        $this->assertTrue( $this->fixture->getScheme() == 'http' );
    }
    
    /**
     * @dataProvider provider
     */
    public function testUrlsAreNormalised( $url, $normalised_url ) {
        $this->fixture->setUrl( $url );
        
        $this->assertEquals( $normalised_url, $this->fixture->normalize() );
    }
    
    /**
     * @dataProvider provider
     */
    public function testUrlsAreNormalisedAgain( $url, $normalised_url ) {
        $this->fixture->setUrl( $url );
        
        // normalize once
        $this->fixture->normalize();
        
        // then normalize again
        $this->assertEquals( $normalised_url, $this->fixture->normalize() );
    }
    
    public function provider() {
        // tests from http://en.wikipedia.org/wiki/URL_normalization#Normalizations_that_Preserve_Semantics
        return array(
                     array( 'HTTP://www.Example.com/',                     'http://www.example.com/' ),
                     array( 'http://www.example.com/a%c2%b1b',             'http://www.example.com/a%C2%B1b' ),
                     array( 'http://www.example.com/%7Eusername/',         'http://www.example.com/~username/' ),
                     array( 'http://www.example.com',                      'http://www.example.com/' ),
                     array( 'http://www.example.com:80/bar.html',          'http://www.example.com/bar.html' ),
                     array( 'http://www.example.com/../a/b/../c/./d.html', 'http://www.example.com/a/c/d.html' ),
                     array( 'eXAMPLE://a/./b/../b/%63/%7bfoo%7d',          'example://a/b/c/%7Bfoo%7D' ),
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
            //array('/foo/bar/../../../baz' ,        '/../baz'),
            array( 'a/./b/../b/',                        'a/b/' ),
            array( '.',                  '' ),
            array( '..',                 '' ),
        );
    }
    
    public function testDecodingUnreservedUrlChars() {
        $this->assertEquals( 'c', $this->fixture->urlDecodeUnreservedChars( '%63' ) );
        $this->assertEquals( 'c/%7B', $this->fixture->urlDecodeUnreservedChars( '%63/%7b' ) );
        $this->assertEquals( 'eXAMPLE://a/./b/../b/c/%7Bfoo%7D', $this->fixture->urlDecodeUnreservedChars( 'eXAMPLE://a/./b/../b/%63/%7bfoo%7d' ) );
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
	
	/**
	 * @dataProvider schemeDataSSL
	 *
	 * http://www.apps.ietf.org/rfc/rfc3986.html#sec-6.2.3
	 */
	public function testSchemeBasedNormalizationSSL( $url ) {
		$expected_uri = 'https://example.com/';
	
		$this->fixture->setUrl( $url );
		$this->assertEquals( $expected_uri, $this->fixture->normalize() );
	
	}
	
	public function schemeDataSSL() {
		return array( array( 'https://example.com' ),
				array( 'https://example.com/' ),
				array( 'https://example.com:/' ),
				array( 'https://example.com:443/' ), );
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

    public function testPortNumbersArePreserved() {
        $url = 'http://example.com:81/index.html';

        $this->fixture->setUrl( $url );
        $this->assertEquals( $url, $this->fixture->normalize() );
    }
    
    public function testCaseSensitiveElementsArePreserved() {
        $url = 'HtTp://User:Pass@www.ExAmPle.com:80/Blah';

        $this->fixture->setUrl( $url );
        $this->assertEquals( 'http://User:Pass@www.example.com/Blah', $this->fixture->normalize() );
    }

    public function testSetUrlReturnsFalseWithUnparseableUrl() {
        $this->assertFalse( $this->fixture->setUrl( '/test:2/' ) );
    }

    public function testTrailingSlashIsAdded() {
        $url = 'http://example.com';

        $this->fixture->setUrl( $url );
        $this->assertEquals( 'http://example.com/', $this->fixture->normalize() );
    }

    public function testDoubleSlashNotAddedToSchemeIfNoHost() {
        $uri = 'mailto:mail@example.com';

        $this->fixture->setUrl( $uri );
        $this->assertEquals( 'mailto:mail@example.com', $this->fixture->normalize() );
    }

    public function testColonNotAddedToUsernameWhenNoPassword() {
        $uri = 'http://user@example.com/';

        $this->fixture->setUrl( $uri );
        $this->assertEquals( 'http://user@example.com/', $this->fixture->normalize() );
    }

    public function testPortAndFragmentDoNotPersistBetweenCalls() {
        $this->fixture->setUrl( 'http://example.com/path/?query#fragment' );
        $this->fixture->normalize();

        $uri = 'http://example.com:400/';
        $this->fixture->setUrl( $uri );
        $this->assertEquals( $uri, $this->fixture->normalize() );

        $uri = 'http://example.com/';
        $this->fixture->setUrl( $uri );
        $this->assertEquals( $uri, $this->fixture->normalize() );        
    }

    public function testEbayImageUrl() {
        $this->fixture->setUrl( 'http://i.ebayimg.com/t/O05520-Adidas-OM-Olympique-Marseille-Jacket-Hooded-UK-S-/00/s/NDAwWDQwMA==/$(KGrHqF,!lMF!iFJh4nmBQflyg7GSw~~60_12.JPG' );
        $this->assertEquals( 'http://i.ebayimg.com/t/O05520-Adidas-OM-Olympique-Marseille-Jacket-Hooded-UK-S-/00/s/NDAwWDQwMA==/$(KGrHqF,!lMF!iFJh4nmBQflyg7GSw~~60_12.JPG',
                             $this->fixture->normalize() );

    }

    public function testReservedCharactersInPathSegmentAreNotEncoded() {
        $this->fixture->setUrl( "http://www.example.com/!$&'()*+,;=/" );
        $this->assertEquals( "http://www.example.com/!$&'()*+,;=/", $this->fixture->normalize() );
    }

    public function testQueryWithArray() {
        $this->fixture->setUrl('http://www.example.com/?array[key]=value');
        $this->assertEquals('http://www.example.com/?array%5Bkey%5D=value', $this->fixture->normalize() );
    }
}
