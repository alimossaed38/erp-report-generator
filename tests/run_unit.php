<?php
$files = glob(__DIR__ . '/unit/*_test.php');
$fail = 0;
$skipped = 0;
$hasSqlite = in_array('sqlite', PDO::getAvailableDrivers(), true);

foreach ($files as $file) {
    $name = basename($file);
    echo '== ' . $name . " ==\n";

    if (!$hasSqlite && $name !== 'router_test.php') {
        $skipped++;
        echo "SKIPPED: PDO_SQLite غير مفعّل في بيئة PHP الحالية.\n";
        continue;
    }

    $command = escapeshellarg(PHP_BINARY)
        . ' -d zend.assertions=1 -d assert.exception=1 '
        . escapeshellarg($file);
    passthru($command, $code);
    if ($code !== 0) {
        $fail++;
        echo "FAILED\n";
    }
}

if ($fail === 0) {
    echo "\nTESTS COMPLETED" . ($skipped ? " — $skipped SKIPPED" : '') . "\n";
} else {
    echo "\n$fail SUITE(S) FAILED" . ($skipped ? " — $skipped SKIPPED" : '') . "\n";
}
exit($fail === 0 ? 0 : 1);
