<?php

if(php_sapi_name() !== 'cli') {
    echo '<pre>';
}

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
