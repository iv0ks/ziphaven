<?php

declare(strict_types=1);

/**
 * Ziphaven — styled 404 page (wired as Apache's ErrorDocument 404).
 */

require __DIR__ . '/../src/View.php';

use Ziphaven\View;

http_response_code(404);

$body = <<<HTML
<div class="error-box">
    <h1>404</h1>
    <p class="sub">That page or file does not exist.</p>
    <p><a class="back" href="/index.php">← Back to downloads</a></p>
</div>
HTML;

echo View::layout('Not found', $body);
