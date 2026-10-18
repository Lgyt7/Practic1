<?php
declare(strict_types=1);

/** Проверка кодировки файлов проекта: всё должно быть валидным UTF-8. */

$root = dirname(__DIR__);

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

$files = [];

foreach ($it as $f) {
    if (!$f->isFile()) {
        continue;
    }

    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1));

    if (preg_match('#\.(php|html|css|js|ps1|sql|mmd|md)$#', $rel)) {
        $files[] = $root . '/' . $rel;
    }
}

sort($files);

$bad = [];

foreach ($files as $path) {
    $content = file_get_contents($path);

    if ($content === false || !mb_check_encoding($content, 'UTF-8')) {
        $bad[] = $path;
    }
}

echo 'Файлов проверено: ' . count($files) . PHP_EOL;

echo $bad === []
    ? 'Все файлы в UTF-8.' . PHP_EOL
    : 'С битой кодировкой: ' . implode(', ', $bad) . PHP_EOL;

exit($bad === [] ? 0 : 1);