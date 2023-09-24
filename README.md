# Introduction

This URL normalizer is fork from [glenscott/url-normalizer](https://github.com/glenscott/url-normalizer) with some changes:
- upgrade PHPUnit to v9.x
- remove tracking parameter as an option (`utm_source`, `fbclid`, etc)


# Syntax based normalization of URI's

This normalizes URI's based on the specification RFC 3986 
https://tools.ietf.org/html/rfc3986

### Example usage

```php
require_once 'vendor/autoload.php';

$url = 'eXAMPLE://a/./b/../b/%63/%7bfoo%7d';
$un = new URL\Normalizer( $url );
echo $un->normalize();

// Result: 'example://a/b/c/%7Bfoo%7D'
```

### The normalization process preserves semantics

So, for example, the following URL's are all equivalent.

- `HTTP://www.Example.com/` and `http://www.example.com/`
- `http://www.example.com/a%c2%b1b` and `http://www.example.com/a%C2%B1b`
- `http://www.example.com/%7Eusername/` and `http://www.example.com/~username/`
- `http://www.example.com` and `http://www.example.com/`
- `http://www.example.com:80/bar.html` and `http://www.example.com/bar.html`
- `http://www.example.com/../a/b/../c/./d.html` and `http://www.example.com/a/c/d.html`
- `http://www.example.com/?array[key]=value` and `http://www.example.com/?array%5Bkey%5D=value`

### Normalizations performed

1. Converting the scheme and host to lower case
1. Capitalizing letters in escape sequences
1. Decoding percent-encoded octets of unreserved characters
1. Adding trailing `/`
1. Removing the default port
1. Removing dot-segments

For more information about these normalizations, please see the following Wikipedia article:

http://en.wikipedia.org/wiki/URL_normalization#Normalizations_that_Preserve_Semantics

For license information, please see LICENSE file.

### Options

Two options are available when normalizing URLs which are disabled by default:

1. Remove empty delimiters.  Enabling this option would normalize `http://www.example.com/?` to `http://www.example.com/`  Currently, only the query string delimiter (`?`) is supported by this option.
2. Sort query parameters.  Enabling this option sorts the query parameters by key alphabetically.  For example, `http://www.example.com/?c=3&b=2&a=1` becomes `http://www.example.com/?a=1&b=2&c=3`
3. Remove tracking parameters. For examplem `https://example.com/?fbclid=xxxxx` becomes `https://example.com/?`
