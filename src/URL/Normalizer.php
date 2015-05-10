<?php

namespace URL;

/**
 * Syntax based normalization of URI's
 *
 * This normalises URI's based on the specification RFC 3986
 * https://tools.ietf.org/html/rfc3986
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
class Normalizer
{
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
    private $remove_empty_delimiters;
    private $sort_query_params;

    /**
     * Does the original URL have a ? query delimiter
     */
    private $query_delimiter;

    public function __construct($url = null, $remove_empty_delimiters = false, $sort_query_params = false)
    {
        if ($url) {
            $this->setUrl($url);
        }

        $this->remove_empty_delimiters = $remove_empty_delimiters;
        $this->sort_query_params       = $sort_query_params;
    }

    private function getQuery($query)
    {
        $qs = array();
        foreach ($query as $qk => $qv) {
            if (is_array($qv)) {
                $qs[rawurldecode($qk)] = $this->getQuery($qv);
            } else {
                $qs[rawurldecode($qk)] = rawurldecode($qv);
            }
        }
        return $qs;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;

        if (strpos($this->url, '?') !== false) {
            $this->query_delimiter = true;
        } else {
            $this->query_delimiter = false;
        }

        // parse URL into respective parts
        $url_components = $this->mbParseUrl($this->url);

        if (! $url_components) {
            // Reset URL
            $this->url = '';

            // Flush properties
            foreach ($this->components as $key) {
                if (property_exists($this, $key)) {
                    $this->$key = '';
                }
            }

            return false;
        } else {
            // Update properties
            foreach ($url_components as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }

            // Flush missing components
            $missing_components = array_diff(
                array_values($this->components),
                array_keys($url_components)
            );

            foreach ($missing_components as $key) {
                if (property_exists($this, $key)) {
                    $this->$key = '';
                }
            }

            return true;
        }
    }

    public function normalize()
    {

        // URI Syntax Components
        // scheme authority path query fragment
        // @link https://tools.ietf.org/html/rfc3986#section-3

        // Scheme
        // @link https://tools.ietf.org/html/rfc3986#section-3.1

        if ($this->scheme) {
            // Converting the scheme to lower case
            $this->scheme = strtolower($this->scheme) . ':';
        }

        // Authority
        // @link https://tools.ietf.org/html/rfc3986#section-3.2

        $authority = '';
        if ($this->host) {
            $authority .= '//';

            // User Information
            // @link https://tools.ietf.org/html/rfc3986#section-3.2.1

            if ($this->user) {
                if ($this->pass) {
                    $authority .= $this->user . ':' . $this->pass . '@';
                } else {
                    $authority .= $this->user . '@';
                }
            }

            // Host
            // @link https://tools.ietf.org/html/rfc3986#section-3.2.2

            // Converting the host to lower case
            if (mb_detect_encoding($this->host) == 'UTF-8') {
                $authority .= mb_strtolower($this->host, 'UTF-8');
            } else {
                $authority .= strtolower($this->host);
            }

            // Port
            // @link https://tools.ietf.org/html/rfc3986#section-3.2.3

            // Removing the default port
            if (isset($this->default_scheme_ports[$this->scheme] )
                    && $this->port == $this->default_scheme_ports[$this->scheme]) {
                $this->port = '';
            }

            if ($this->port) {
                $authority .= ':' . $this->port;
            }
        }

        // Path
        // @link https://tools.ietf.org/html/rfc3986#section-3.3

        if ($this->path) {
            $this->path = $this->removeAdditionalPathPrefixSlashes($this->path);
            $this->path = $this->removeDotSegments($this->path);
            $this->path = $this->urlDecodeUnreservedChars($this->path);
            $this->path = $this->urlDecodeReservedSubDelimChars($this->path);
        } elseif ($this->url) {
            // Add default path only when valid URL is present
            // Adding trailing /
            $this->path = '/';
        }

        // Query
        // @link https://tools.ietf.org/html/rfc3986#section-3.4

        if ($this->query) {
            $query = $this->parseStr($this->query);

            //encodes every parameter correctly
            $qs = $this->getQuery($query);

            $this->query = '?';

            if ($this->sort_query_params) {
                ksort($qs);
            }

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
                } else {
                    $this->query .= rawurlencode($key) . '=' . rawurlencode($val);
                }
            }

            // Fix http_build_query adding equals sign to empty keys
            $this->query = str_replace('=&', '&', rtrim($this->query, '='));
        } else {
            if ($this->query_delimiter && ! $this->remove_empty_delimiters) {
                $this->query = '?';
            }
        }

        // Fragment
        // @link https://tools.ietf.org/html/rfc3986#section-3.5

        if ($this->fragment) {
            $this->fragment = rawurldecode($this->fragment);
            $this->fragment = rawurlencode($this->fragment);
            $this->fragment = '#' . $this->fragment;
        }

        $this->setUrl($this->scheme . $authority . $this->path . $this->query . $this->fragment);

        return $this->getUrl();
    }

    /**
     * Path segment normalization
     * https://tools.ietf.org/html/rfc3986#section-5.2.4
     */
    public function removeDotSegments($path)
    {
        $new_path = '';

        while (! empty($path)) {
             // A
            $pattern_a   = '!^(\.\./|\./)!x';
            $pattern_b_1 = '!^(/\./)!x';
            $pattern_b_2 = '!^(/\.)$!x';
            $pattern_c   = '!^(/\.\./|/\.\.)!x';
            $pattern_d   = '!^(\.|\.\.)$!x';
            $pattern_e   = '!(/*[^/]*)!x';

            if (preg_match($pattern_a, $path)) {
                // remove prefix from $path
                $path = preg_replace($pattern_a, '', $path);
            } elseif (preg_match($pattern_b_1, $path, $matches) || preg_match($pattern_b_2, $path, $matches)) {
                $path = preg_replace("!^" . $matches[1] . "!", '/', $path);
            } elseif (preg_match($pattern_c, $path, $matches)) {
                $path = preg_replace('!^' . preg_quote($matches[1], '!') . '!x', '/', $path);

                // remove the last segment and its preceding "/" (if any) from output buffer
                $new_path = preg_replace('!/([^/]+)$!x', '', $new_path);
            } elseif (preg_match($pattern_d, $path)) {
                $path = preg_replace($pattern_d, '', $path);
            } else {
                if (preg_match($pattern_e, $path, $matches)) {
                    $first_path_segment = $matches[1];

                    $path = preg_replace('/^' . preg_quote($first_path_segment, '/') . '/', '', $path, 1);

                    $new_path .= $first_path_segment;
                }
            }
        }

        return $new_path;
    }

    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Decode unreserved characters
     *
     * @link https://tools.ietf.org/html/rfc3986#section-2.3
     */
    public function urlDecodeUnreservedChars($string)
    {
        $string = rawurldecode($string);
        $string = rawurlencode($string);
        $string = str_replace(array( '%2F', '%3A', '%40' ), array( '/', ':', '@' ), $string);

        return $string;
    }

    /**
     * Decode reserved sub-delims
     *
     * @link https://tools.ietf.org/html/rfc3986#section-2.2
     */
    public function urlDecodeReservedSubDelimChars($string)
    {
        return str_replace(
            array( '%21', '%24', '%26', '%27', '%28', '%29', '%2A', '%2B', '%2C', '%3B', '%3D' ),
            array( '!', '$', '&', "'", '(', ')', '*', '+', ',', ';', '=' ),
            $string
        );
    }

    /**
     * Replacement for PHP's parse_string which does not deal with spaces or dots in key names
     *
     * @param string $string URL query string
     * @return array key value pairs
     */
    private function parseStr($string)
    {
        $params = array();
                
        $pairs = explode('&', $string);

        foreach ($pairs as $pair) {
            if (! $pair) {
                continue;
            }

            $var = explode('=', $pair, 2);
            $val = ( isset( $var[1] ) ? $var[1] : '' );

            if (isset($params[$var[0]])) {
                if (is_array($params[$var[0]])) {
                    $params[$var[0]][] = $val;
                } else {
                    $params[$var[0]] = array($params[$var[0]], $val);
                }
            } else {
                $params[$var[0]] = $val;
            }
        }

        return $params;
    }

    private function mbParseUrl($url)
    {
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
        if ($encodedParts) {
            foreach ($encodedParts as $key => $value) {
                $result[$key] = urldecode(str_replace($replacements, $entities, $value));
            }
        }
        return $result;
    }

    /*
     * Converts ////foo to /foo within each path segment
     */
    private function removeAdditionalPathPrefixSlashes($path)
    {
        return preg_replace('/(\/)+/', '/', $path);
    }
}
