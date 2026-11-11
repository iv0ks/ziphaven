<?php

declare(strict_types=1);

/**
 * Ziphaven — download endpoint (intentionally vulnerable).
 *
 *     GET /download.php?file=ziphaven-sdk-2.9.0.zip   -> serves it
 *     GET /download.php?file=../flag.txt              -> rejected (literal ../)
 *     GET /download.php?file=%252e%252e%252fflag.txt  -> escapes (double encoded)
 *
 * See src/Download.php for the vulnerability writeup (CWE-22, level 5):
 * the traversal guard runs BEFORE an "extra" urldecode(), so a percent-encoded
 * "../" is invisible to the guard and reconstituted only afterwards.
 */

require __DIR__ . '/../src/Config.php';
require __DIR__ . '/../src/Download.php';

use Ziphaven\Config;
use Ziphaven\Download;

$file = $_GET['file'] ?? '';

if (!is_string($file) || $file === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Missing 'file' query parameter.";

    return;
}

try {
    $path = Download::resolve(Config::downloadsDir(), $file);
} catch (\RuntimeException $e) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo $e->getMessage();

    return;
}

if (!is_file($path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Download not found.';

    return;
}

// Serve the artefact as an attachment. Content type is best-effort.
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($path) . '"');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
