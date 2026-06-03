<?php
$target = __DIR__ . '/api/composer.phar';
$ok = @copy('https://getcomposer.org/composer-stable.phar', $target);
if (!$ok || !file_exists($target)) {
    fwrite(STDERR, "DOWNLOAD_FAIL\n");
    exit(1);
}
echo "DOWNLOAD_OK\n";
