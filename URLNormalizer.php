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
    private $default_scheme_ports = array( 'http:' => 80, 'https:' => 443, );
    private $components = array( 'scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment', );

    public function __construct( $url=null ) {
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
            // Reset URL
            $this->url = '';

            // Flush properties
            foreach ( $this->components as $key ) {
                if ( property_exists( $this, $key ) ) {
                    $this->$key = '';
                }
            }

            return false;
        }
        else {
            // Update properties
            foreach ( $url_components as $key => $value ) {
                if ( property_exists( $this, $key ) ) {
                    $this->$key = $value;
                }
            }

            // Flush missing components
            $missing_components = array_diff (
                array_values( $this->components ),
                array_keys( $url_components )
            );

            foreach ( $missing_components as $key ) {
                if ( property_exists( $this, $key ) ) {
                    $this->$key = '';
                }
            }

            return true;
        }
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
            if ( isset( $this->default_scheme_ports[$this->scheme] )
                    && $this->port == $this->default_scheme_ports[$this->scheme]) {
                $this->port = '';
            }

            if ( $this->port ) {
                $authority .= ':' . $this->port;
            }
        }

        // Path
        // @link http://www.apps.ietf.org/rfc/rfc3986.html#sec-3.3

        if ( $this->path ) {
            // Removing dot-segments
            $this->path = $this->removeDotSegments( $this->path );
            $this->path = $this->urlDecodeUnreservedChars( $this->path );
            $this->path = $this->urlDecodeReservedSubDelimChars( $this->path );
        }
        // Add default path only when valid URL is present
        elseif ( $this->url ) {
            // Adding trailing /
            $this->path = '/';
        }

        // Query
        // @link http://www.apps.ietf.org/rfc/rfc3986.html#sec-3.4

        if ( $this->query ) {
            parse_str( $this->query, $query );

            $keys = array_map( 'rawurldecode', array_keys( $query ) );
            $values = array_map( 'rawurldecode', array_values( $query ) );
            $query = array_combine( $keys, $values );

            $this->query = '?' . str_replace( '+', '%20', http_build_query( $query, null, '&' ) );
            
            // Fix http_build_query adding equals sign to empty keys
            $this->query = str_replace( '=&', '&', rtrim( $this->query, '=' ));
        }

        // Fragment
        // @link http://www.apps.ietf.org/rfc/rfc3986.html#sec-3.5

        if ( $this->fragment ) {
            $this->fragment = rawurldecode( $this->fragment );
            $this->fragment = rawurlencode( $this->fragment );
            $this->fragment = '#' . $this->fragment;
        }

        $this->setUrl( $this->scheme . $authority . $this->path . $this->query . $this->fragment );

        return $this->getUrl();
    }

    /**
     * Path segment normalization
     * http://www.apps.ietf.org/rfc/rfc3986.html#sec-5.2.4
     */
    public function removeDotSegments( $path ) {
        $new_path = '';

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

    public function getScheme() {
        return $this->scheme;
    }

    /**
     * Decode unreserved characters
     * 
     * @link http://www.apps.ietf.org/rfc/rfc3986.html#sec-2.3
     */
    public function urlDecodeUnreservedChars( $string ) {
        $string = rawurldecode( $string );
        $string = rawurlencode( $string );
        $string = str_replace( array( '%2F', '%3A', '%40' ), array( '/', ':', '@' ), $string );

        return $string;
    }

    /**
     * Decode reserved sub-delims
     *
     * @link http://www.apps.ietf.org/rfc/rfc3986.html#sec-2.2
     */
    public function urlDecodeReservedSubDelimChars( $string ) {
        return str_replace( array( '%21', '%24', '%26', '%27', '%28', '%29', '%2A', '%2B', '%2C', '%3B', '%3D' ), 
                            array( '!', '$', '&', "'", '(', ')', '*', '+', ',', ';', '=' ), $string );
    }
}
