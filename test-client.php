<?php

echo '<pre>';

require_once 'src/URL/Normalizer.php';

$un = new URL\Normalizer();

test('eXAMPLE://a/./b/../b/%63/%7bfoo%7d', 'example://a/b/c/%7Bfoo%7D');
test('http://www.example.com', 'http://www.example.com/');
test('http://www.yahoo.com/%a1', 'http://www.yahoo.com/%A1');

test('HTTP://www.Example.com/', 'http://www.example.com/');
test('http://www.example.com/a%c2%b1b', 'http://www.example.com/a%C2%B1b');
test('http://www.example.com/%7Eusername/', 'http://www.example.com/~username/');
test('http://www.example.com:80/bar.html', 'http://www.example.com/bar.html');

test('http://www.example.com/../a/b/../c/./d.html', 'http://www.example.com/a/c/d.html');
test('../', '' );
test('./', '' );
test('/./', '/' );
test('/.', '/' );
test('/a/b/c/./../../g', '/a/g' );
test('mid/content=5/../6', 'mid/6' );
test('/foo/bar/.', '/foo/bar/' );
test('/foo/bar/./', '/foo/bar/' );
test('/foo/bar/..', '/foo/' );
test('/foo/bar/../', '/foo/' );
test('/foo/bar/../baz', '/foo/baz' );
test('/foo/bar/../..', '/');
test('/foo/bar/../../' , '/');
test('/foo/bar/../../baz' , '/baz');
//test('/foo/bar/../../../baz' , '/../baz');
test('a/./b/../b/', 'a/b/' );
test('.', '' );
test('..', '' );

test('%63', 'c');
test('%63/%7b', 'c/%7B');

test('http://example.com', 'http://example.com/');
test('http://example.com/', 'http://example.com/');
test('http://example.com:/', 'http://example.com/');
test('http://example.com:80/', 'http://example.com/');

test('https://example.com', 'https://example.com/');
test('https://example.com/', 'https://example.com/');
test('https://example.com:/', 'https://example.com/');
test('https://example.com:443/', 'https://example.com/');

test('http://fancysite.nl/links/doit.pl?id=2029', 'http://fancysite.nl/links/doit.pl?id=2029');
test('http://example.com/index.html#fragment', 'http://example.com/index.html#fragment');
test('http://example.com:81/index.html', 'http://example.com:81/index.html');
test('HtTp://User:Pass@www.ExAmPle.com:80/Blah', 'http://User:Pass@www.example.com/Blah');
test('/test:2/', '');
test('mailto:mail@example.com', 'mailto:mail@example.com');
test('http://user@example.com/', 'http://user@example.com/');
test('http://example.com/path/?query#fragment', 'http://example.com/path/?query#fragment');
test('http://example.com/path/?q1&q2&q3&q4', 'http://example.com/path/?q1&q2&q3&q4');
test('http://example.com:400/', 'http://example.com:400/');
test('http://example.com/', 'http://example.com/');

test('http://example.com/path/?query=space value', 'http://example.com/path/?query=space%20value');

test('http://www.example.com/?array[key]=value', 'http://www.example.com/?array%5Bkey%5D=value');

/**
 * Test URL Normalization
 *
 * @param string $input URL to normalize
 * @param string $expected Anticipated result of normalization
 * @return void
 * @author emojka
 **/
function test($input, $expected) {
    global $un;
    $un->setUrl($input);
    $result = $un->normalize();
    if ($result === $expected) {
        printf("✔ %s → %s\n", $input, $result);
    } else {
        printf("%s ✘ %s → %s\n", $expected, $input, $result);
    }
}
