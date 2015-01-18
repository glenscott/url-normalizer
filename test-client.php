<?php

//echo '<pre>';

require_once 'src/URL/Normalizer.php';

$un = new URL\Normalizer();
$un->setMode('safebrowsing');

$c=0; $t=0;

test('http://host/%25%32%35', 'http://host/%25');
test('http://host/%25%32%35%25%32%35', 'http://host/%25%25');
test('http://host/%2525252525252525', 'http://host/%25');
test('http://host/asdf%25%32%35asd', 'http://host/asdf%25asd');
test('http://host/%%%25%32%35asd%%', 'http://host/%25%25%25asd%25%25');
test('http://www.google.com/', 'http://www.google.com/');
test('http://%31%36%38%2e%31%38%38%2e%39%39%2e%32%36/%2E%73%65%63%75%72%65/%77%77%77%2E%65%62%61%79%2E%63%6F%6D/', 'http://168.188.99.26/.secure/www.ebay.com/');
test('http://195.127.0.11/uploads/%20%20%20%20/.verify/.eBaysecure=updateuserdataxplimnbqmn-xplmvalidateinfoswqpcmlx=hgplmcx/', 'http://195.127.0.11/uploads/%20%20%20%20/.verify/.eBaysecure=updateuserdataxplimnbqmn-xplmvalidateinfoswqpcmlx=hgplmcx/');  
test('http://host%23.com/%257Ea%2521b%2540c%2523d%2524e%25f%255E00%252611%252A22%252833%252944_55%252B', 'http://host%23.com/~a!b@c%23d$e%25f^00&11*22(33)44_55+');
test('http://3279880203/blah', 'http://195.127.0.11/blah');
test('http://www.google.com/blah/..', 'http://www.google.com/');
test('www.google.com/', 'http://www.google.com/');
test('www.google.com', 'http://www.google.com/');
test('http://www.evil.com/blah#frag', 'http://www.evil.com/blah');
test('http://www.GOOgle.com/', 'http://www.google.com/');
test('http://www.google.com.../', 'http://www.google.com/');
test("http://www.google.com/foo\tbar\rbaz\n2", 'http://www.google.com/foobarbaz2');
test('http://www.google.com/q?', 'http://www.google.com/q?');
test('http://www.google.com/q?r?', 'http://www.google.com/q?r?');
test('http://www.google.com/q?r?s', 'http://www.google.com/q?r?s');
test('http://evil.com/foo#bar#baz', 'http://evil.com/foo');
test('http://evil.com/foo;', 'http://evil.com/foo;');
test('http://evil.com/foo?bar;', 'http://evil.com/foo?bar;');
test("http://\x01\x80.com/", 'http://%01%80.com/');
test('http://notrailingslash.com', 'http://notrailingslash.com/');
test('http://www.gotaport.com:1234/', 'http://www.gotaport.com:1234/');
test('  http://www.google.com/  ', 'http://www.google.com/');
test('  http://www.google.com/', 'http://www.google.com/');
test('http:// leadingspace.com/', 'http://%20leadingspace.com/');
test('http://%20leadingspace.com/', 'http://%20leadingspace.com/');
test('%20leadingspace.com/', 'http://%20leadingspace.com/');
test('https://www.securesite.com/', 'https://www.securesite.com/');
test('http://host.com/ab%23cd', 'http://host.com/ab%23cd');
test('http://host.com//twoslashes?more//slashes', 'http://host.com/twoslashes?more//slashes');

$un->setMode('normal');

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

echo "Did {$c}/{$t}\n";

/**
 * Test URL Normalization
 *
 * @param string $input URL to normalize
 * @param string $expected Anticipated result of normalization
 * @return void
 * @author emojka
 **/
function test($input, $expected) {
    global $un, $c, $t;
    $un->setUrl($input);
    $result = $un->normalize();
    $t++;
    if ($result === $expected) {
        printf("✔ %s → %s\n", $input, $result);
        $c++;
    } else {
        printf("✘ %s → %s <> %s\n", $input, $result, $expected);
    }
}
