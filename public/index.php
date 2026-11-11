<?php

declare(strict_types=1);

/**
 * Ziphaven — downloads list (home).
 */

require __DIR__ . '/../src/View.php';
require __DIR__ . '/../src/Files.php';

use Ziphaven\Files;
use Ziphaven\View;

$files = Files::all();

$cards = '';
foreach ($files as $f) {
    $enc = rawurlencode($f['file']);
    $cards .= sprintf(
        '<article class="dl-card">'
            . '<div class="dl-head">%s<span class="dl-ver">v%s</span></div>'
            . '<h3 class="dl-name">%s</h3>'
            . '<p class="dl-file">%s</p>'
            . '<dl class="dl-meta">'
            . '<div><dt>Size</dt><dd>%s</dd></div>'
            . '<div><dt>Released</dt><dd>%s</dd></div>'
            . '<div><dt>Downloads</dt><dd>%s</dd></div>'
            . '</dl>'
            . '<p class="dl-sum"><code>%s</code></p>'
            . '<a class="dl-btn" href="/download.php?file=%s">Download</a>'
            . '</article>',
        View::kindTag($f['kind']),
        View::esc($f['version']),
        View::esc($f['name']),
        View::esc($f['file']),
        View::esc($f['size']),
        View::esc($f['released']),
        View::esc($f['downloads']),
        View::esc($f['checksum']),
        $enc
    );
}

$latest = $files[0];
$latestName = View::esc($latest['name']);
$latestVer = View::esc($latest['version']);
$latestReleased = View::esc($latest['released']);

$body = <<<HTML
<section class="hero">
    <div class="hero-text">
        <p class="eyebrow">Latest release · {$latestReleased}</p>
        <h1>Download Ziphaven {$latestVer}</h1>
        <p class="lede">Fast, verifiable release artefacts for every platform.
            Grab the CLI, the desktop app or the SDK — checksums included.</p>
    </div>
    <div class="hero-card">
        <span class="hero-badge">{$latestName}</span>
        <span class="hero-ver">v{$latestVer}</span>
        <span class="hero-note">Signed &amp; checksummed builds</span>
    </div>
</section>
<section class="downloads">
    <div class="downloads-head">
        <h2>All downloads</h2>
        <p class="sub">Stable channel</p>
    </div>
    <div class="dl-grid">{$cards}</div>
</section>
HTML;

echo View::layout('Downloads', $body);
