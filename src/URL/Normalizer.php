<?php

namespace URL;

/**
 * Syntax based normalization of URI's
 *
 * This normalises URI's based on the specification RFC 3986
 * http://www.apps.ietf.org/rfc/rfc3986.html
 *
 * Example usage:
 * <code>
 * require_once 'Normalizer.php';
 *
 * $url = 'eXAMPLE://a/./b/../b/%63/%7bfoo%7d';
 * $un = new URLNormalizer();
 * $un->setUrl( $url );
 * echo $un->normalize();
 *
 * // result: "example://a/b/c/%7Bfoo%7D"
 * </code>
 *
 * @author Glen Scott <glen@glenscott.co.uk>
 */
class Normalizer {
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
    private $mode;

    public function __construct( $url=null, $mode='normal' ) {
        if ( $url ) {
        	$this->setUrl( $url );
        }
        $this->setMode($mode);
    }

    /*
     * Google Safebrowsing
     * Canonicalization section in https://developers.google.com/safe-browsing/developers_guide_v2
     */
    public function setMode($mode='normal') {
        if($mode == 'normal' || $mode == 'safebrowsing') {
            $this->mode = $mode;
        }
    }

    private function getQuery($query) {
        $qs = array();
        foreach($query as $qk => $qv) {
            if(is_array($qv)) {
                $qs[rawurldecode($qk)] = $this->getQuery($qv);
            }
            else {
                $qs[rawurldecode($qk)] = rawurldecode($qv);
            }
        }
        return $qs;
    }

    public function getUrl() {
        return $this->url;
    }

    public function setUrl( $url ) {
        $this->url = $url;

        // parse URL into respective parts
        $url_components = $this->mb_parse_url( $this->url );

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
                    if($key == 'scheme' || $key == 'port') {
                        $value = trim($value);
                    }
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
                    if($key == 'scheme' && $this->mode == 'safebrowsing') {
                        $this->$key = 'http';
                    } else {
                        $this->$key = '';
                    }
                }
            }

            // remove empty path
            if(empty(trim(str_replace('/', '', $this->path)))) {
                $this->path = '';
            }

            if($this->mode == 'safebrowsing' && empty($this->host) && !empty($this->path)) {
                $this->host = $this->path;
                $this->path = '';
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

            // fix extra slash or dot on the host
            $this->host = $this->removeAdditionalHostChars( $this->host );

            // fix decimal host
            if(is_numeric($this->host)) {
                $this->host = $this->convertDecimalToIPv4($this->host);
            }

            $this->host = $this->fixNonAsciiHost($this->host);

            // Converting the host to lower case
            if ( mb_detect_encoding( $this->host ) == 'UTF-8' ) {
                $authority .= mb_strtolower( $this->host, 'UTF-8' );
            }
            else {
                $authority .= strtolower( $this->host );
            }

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
            $this->path = $this->removeAdditionalPathPrefixSlashes( $this->path );
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
            $query = $this->parseStr( $this->query );

            //encodes every parameter correctly
            $qs = $this->getQuery($query);

            $this->query = '?';
            foreach ($qs as $key => $val) {
                if (strlen($this->query) > 1) {
                    $this->query .= '&';
                }

                if (is_array($val)) {
                    for ($i = 0; $i < count($val); $i++) {
                        if ($i > 0) {
                            $this->query .= '&';
                        }
                        $this->query .= rawurlencode($key) . '=' . rawurlencode($val[$i]);
                    }
                }
                else {
                    $this->query .= rawurlencode($key) . '=' . rawurlencode($val);
                }
            }

            // Fix http_build_query adding equals sign to empty keys
            $this->query = str_replace(array('%2F', '%3B', '%3F'), array('/', ';', '?'), str_replace( '=&', '&', rtrim( $this->query, '=' )));
        }

        // Fragment
        // @link http://www.apps.ietf.org/rfc/rfc3986.html#sec-3.5

        if ( $this->fragment ) {
            $this->fragment = rawurldecode( $this->fragment );
            $this->fragment = rawurlencode( $this->fragment );
            $this->fragment = '#' . $this->fragment;
        }

        if($this->mode == 'safebrowsing') {
            $this->setUrl( $this->scheme . $authority . $this->path . $this->query );
        } else {
            $this->setUrl( $this->scheme . $authority . $this->path . $this->query . $this->fragment );
        }

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
        $string = str_replace( array( "\t", "\n", "\r" ), array ( '', '', '' ), $string);
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
        $c = -1;
        while($c != 0) {
            $string = str_replace( array( '%21', '%24', '%25', '%26', '%27', '%28', '%29', '%2A', '%2B', '%2C', '%3B', '%3D' ), 
                            array( '!', '$', '%', '&', "'", '(', ')', '*', '+', ',', ';', '=' ), $string, $c );
        }
        return $this->mode == 'safebrowsing' ? str_replace("%", "%25", $string) : $string;
    }

    /**
     * Replacement for PHP's parse_string which does not deal with spaces or dots in key names
     *
     * @param string $string URL query string
     * @return array key value pairs
     */
     private function parseStr( $string ) {
        $params = array();
                
        $pairs = explode( '&', $string );

        foreach ( $pairs as $pair ) {
            $var = explode( '=', $pair, 2 );
            $val = ( isset( $var[1] ) ? $var[1] : '' );

            if (isset($params[$var[0]])) {
                if (is_array($params[$var[0]])) {
                    $params[$var[0]][] = $val;
                }
                else {
                    $params[$var[0]] = array($params[$var[0]], $val);
                }
            }
            else {
                $params[$var[0]] = $val;
            }
        }

        return $params;
    }

    private function mb_parse_url($url) {
        $result = false;

        // Build arrays of values we need to decode before parsing
        $entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%24', '%2C', '%2F', '%3F', '%23', '%5B', '%5D');
        $replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "$", ",", "/", "?", "#", "[", "]");

        // Create encoded URL with special URL characters decoded so it can be parsed
        // All other characters will be encoded
        $encodedURL = str_replace($entities, $replacements, urlencode($url));

        // Parse the encoded URL
        $encodedParts = parse_url($encodedURL);

        // Now, decode each value of the resulting array
        if ($encodedParts)
        {
            foreach ($encodedParts as $key => $value)
            {
                $result[$key] = urldecode(str_replace($replacements, $entities, $value));
            }
        }
        return $result;
    }

    /*
     * Converts ////foo to /foo within each path segment
     */
    private function removeAdditionalPathPrefixSlashes($path) {
        return preg_replace( '/(\/)+/', '/', $path );
    }

    /*
     * Remove any remainging slash or dot from the end of host
     */
    private function removeAdditionalHostChars($host) {
        $host = rawurldecode($host);
        return str_replace(array('#', ' '), array('%23', '%20'), preg_replace( '/([\/.])+$/', '', $host ));
    }

    private function convertDecimalToIPv4($host) {
        if(!is_numeric($host)) {
            return $host;
        }
        $oct1 = intval($host / 16777216);
        $rem1 = $host % 16777216;
        $oct2 = intval($rem1 / 65536);
        $rem2 = $rem1 % 65536;
        $oct3 = intval($rem2 / 256);
        $rem3 = $rem2 % 256;
        $oct4 = intval($rem3);
        return "{$oct1}.{$oct2}.{$oct3}.{$oct4}";
    }

    private function fixNonAsciiHost($host) {
        $tmp = $host;
        if(!mb_check_encoding($host, 'ASCII')) {
            $tmp = "";
            $len = mb_strlen($host);
            for($i=0; $i<$len; $i++) {
                $c = ord(mb_substr($host, $i, 1));
                if($c <= 32 || $c >= 127) {
                    $tmp .= sprintf("%%%02X", $c);
                } else {
                    $tmp .= chr($c);
                }
            }
        }
        return $tmp;
    }

}
