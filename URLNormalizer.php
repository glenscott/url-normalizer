<?php

/**
 * Syntax based normalization of URI's
 *
 * This normalises URI's based on the specification RFC 3986
 * http://www.apps.ietf.org/rfc/rfc3986.html
 *
 * Example usage:
 * <code>
 * require_once 'URLNormalizer.php';
 *
 * $url = 'eXAMPLE://a/./b/../b/%63/%7bfoo%7d';
 * $un = new URLNormalizer();
 * $un->setUrl( $url );
 * echo $un->normalize();
 *
 * // result: "example://a/b/c/%7Bfoo%7D"
 * </code>
 *
 * @author Glen Scott <glen_scott@yahoo.co.uk>
 */
class URLNormalizer {
    private $url;
    private $scheme;
    private $host;
    private $port;
    private $user;
    private $pass;
    private $path;
    private $query;
    private $fragment;
    private $default_scheme_ports = array( 'http' => 80, 'https' => 443, );

    public function __construct( $url=null ) {
        $this->scheme   = '';
        $this->host     = '';
        $this->port     = '';
        $this->user     = '';
        $this->pass     = '';
        $this->path     = '';
        $this->query    = '';
        $this->fragment = '';

        if ( $url ) {
        	$this->setUrl( $url );
        }
    }

    public function getUrl() {
        return $this->url;
    }

    public function setUrl( $url ) {
        $this->url = $url;

        // parse URL into respective parts
        $url_components = parse_url( $this->url );

        if ( ! $url_components ) {
            return false;
        }
        else {
            foreach ( $url_components as $key => $value ) {
                if ( property_exists( $this, $key ) ) {
                    $this->$key = $value;
                }
            }

            return true;
        }
    }

    public function getScheme() {
        return $this->scheme;
    }

    public function normalize() {

        // URI Syntax Components
        // scheme authority path query fragment
        // @link http://www.apps.ietf.org/rfc/rfc3986.html#sec-3

        // Scheme
        // @link http://www.apps.ietf.org/rfc/rfc3986.html#sec-3.1

        if ( $this->scheme ) {
            // Converting the scheme to lower case
            $this->scheme = strtolower( $this->scheme ) . ':';
        }

        // Authority
        // @link http://www.apps.ietf.org/rfc/rfc3986.html#sec-3.2

        $authority = '';
        if ( $this->host ) {
            $authority .= '//';

            // User Information
            // @link http://www.apps.ietf.org/rfc/rfc3986.html#sec-3.2.1

            if ( $this->user ) {
                if ( $this->pass ) {
                    $authority .= $this->user . ':' . $this->pass . '@';
                }
                else {
                    $authority .= $this->user . '@';
                }
            }

            // Host
            // @link http://www.apps.ietf.org/rfc/rfc3986.html#sec-3.2.2

            // Converting the host to lower case
            $authority .= strtolower( $this->host );

            // Port
            // @link http://www.apps.ietf.org/rfc/rfc3986.html#sec-3.2.3

            // Removing the default port
            $this->schemeBasedNormalization();

            if ( $this->port ) {
                $authority .= ':' . $this->port;
            }
        }

        // Path
        // @link http://www.apps.ietf.org/rfc/rfc3986.html#sec-3.3

        if ( $this->path ) {
            // Capitalizing letters in escape sequences
            $this->path = preg_replace( '/(%([0-9abcdef][0-9abcdef]))/ex', "'%'.strtoupper('\\2')", $this->path );

            // Decoding percent-encoded octets of unreserved characters
            $this->path = $this->urlDecodeUnreservedChars( $this->path );

            // Removing dot-segments
            $this->path = $this->removeDotSegments( $this->path );
        }
        else {
            // Adding trailing /
            $this->path = '/';
        }

        // Query
        // @link http://www.apps.ietf.org/rfc/rfc3986.html#sec-3.4

        if ( $this->query ) {
            $this->query = '?' . $this->query;
        }

        // Fragment
        // @link http://www.apps.ietf.org/rfc/rfc3986.html#sec-3.5

        if ( $this->fragment ) {
            $this->fragment = '#' . $this->fragment;
        }

        return $this->scheme . $authority . $this->path . $this->query . $this->fragment;
    }

    /**
     * Decode unreserved characters
     * http://www.apps.ietf.org/rfc/rfc3986.html#sec-2.3
     */
    public function urlDecodeUnreservedChars( $string ) {
        $unreserved = array();

        for ( $octet = 65; $octet <= 90; $octet++ ) {
            $unreserved[] = dechex( $octet );
        }

        for ( $octet = 97; $octet <= 122; $octet++ ) {
            $unreserved[] = dechex( $octet );
        }

        for ( $octet = 48; $octet <= 57; $octet++ ) {
            $unreserved[] = dechex( $octet );
        }

        $unreserved[] = dechex( ord( '-' ) );
        $unreserved[] = dechex( ord( '.' ) );
        $unreserved[] = dechex( ord( '_' ) );
        $unreserved[] = dechex( ord( '~' ) );

        $unreserved_char_func = create_function( '$str', 'return "/%" . strtoupper( $str ) . "/x";' );
        $decode_char_func     = create_function( '$matches', 'return chr( hexdec( $matches[0] ));' );

        return preg_replace_callback( array_map( $unreserved_char_func, $unreserved ),
                                      $decode_char_func,
                                      $string );
    }

    /**
     * Path segment normalization
     * http://www.apps.ietf.org/rfc/rfc3986.html#sec-5.2.4
     */
    public function removeDotSegments( $path ) {
        $new_path = '';

        $iteration = 0;
        $step      = ' ';

        while ( ! empty( $path ) ) {
             // A
            $pattern_a   = '!^(\.\./|\./)!x';
            $pattern_b_1 = '!^(/\./)!x';
            $pattern_b_2 = '!^(/\.)$!x';
            $pattern_c   = '!^(/\.\./|/\.\.)!x';
            $pattern_d   = '!^(\.|\.\.)$!x';
            $pattern_e   = '!(/*[^/]*)!x';

            if ( preg_match( $pattern_a, $path ) ) {
                // remove prefix from $path
                $path = preg_replace( $pattern_a, '', $path );
            }
            elseif ( preg_match( $pattern_b_1, $path, $matches ) || preg_match( $pattern_b_2, $path, $matches ) ) {
                $path = preg_replace( "!^" . $matches[1] . "!", '/', $path );
            }
            elseif ( preg_match( $pattern_c, $path, $matches ) ) {
                $path = preg_replace( '!^' . preg_quote( $matches[1], '!' ) . '!x', '/', $path );

                // remove the last segment and its preceding "/" (if any) from output buffer
                $new_path = preg_replace( '!/([^/]+)$!x', '', $new_path );
            }
            elseif ( preg_match( $pattern_d, $path ) ) {
                $path = preg_replace( $pattern_d, '', $path );
            }
            else {
                if ( preg_match( $pattern_e, $path, $matches ) ) {
                    $first_path_segment = $matches[1];

                    $path = preg_replace( '/^' . preg_quote( $first_path_segment, '/' ) . '/', '', $path, 1 );

                    $new_path .= $first_path_segment;
                }
            }
        }

        return $new_path;
    }

    private function schemeBasedNormalization() {
        if ( isset( $this->default_scheme_ports[$this->scheme] ) && $this->default_scheme_ports[$this->scheme] == $this->port ) {
            $this->port = '';
        }
    }
}
