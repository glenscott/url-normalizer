<?php

require_once 'URLNormalizer.php';

$url = 'eXAMPLE://a/./b/../b/%63/%7bfoo%7d';

$un = new URLNormalizer();
$un->setUrl( $url );

echo $un->normalize();
echo "\n";

// result: "example://a/b/c/%7Bfoo%7D"
