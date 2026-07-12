<?php
$files = glob(__DIR__ . '/unit/*_test.php');
$fail = 0;
foreach ($files as $f) {
    echo "== " . basename($f) . " ==\n";
    $php = 'C:/wamp64/bin/php/php8.3.28/php.exe';
    passthru('"' . $php . '" "' . $f . '"', $code);
    if ($code !== 0) { $fail++; echo "FAILED\n"; }
}
echo $fail === 0 ? "\nALL UNIT TESTS PASSED\n" : "\n$fail SUITE(S) FAILED\n";
exit($fail === 0 ? 0 : 1);
