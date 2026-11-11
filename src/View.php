<?php

declare(strict_types=1);

/**
 * Tiny view helpers for Ziphaven.
 *
 * Plain PHP rendering (no template engine) — enough for a small download
 * manager UI. The layout gives every page the same chrome: a top navigation
 * bar and a footer, so the app reads like a real release hub.
 */

namespace Ziphaven;

final class View
{
    public static function esc(string $value): string
    {
        return htmlspecialchars($value, \ENT_QUOTES, 'UTF-8');
    }

    /** Render the shared chrome around a page body. */
    public static function layout(string $title, string $body): string
    {
        $t = self::esc($title);

        return <<<HTML
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>{$t} · Ziphaven</title>
            <link rel="icon" type="image/svg+xml" href="/favicon.svg">
            <link rel="stylesheet" href="/style.css">
        </head>
        <body>
            <header class="nav">
                <a class="brand" href="/index.php">
                    <span class="brand-mark" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M12 3v12m0 0 4-4m-4 4-4-4" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span class="brand-name">Ziphaven</span>
                </a>
                <nav class="nav-links">
                    <a href="/index.php" class="active">Downloads</a>
                    <a href="#" aria-disabled="true">Changelog</a>
                    <a href="#" aria-disabled="true">Mirrors</a>
                    <a href="#" aria-disabled="true">Docs</a>
                </nav>
                <a class="nav-cta" href="#" aria-disabled="true">Sign in</a>
            </header>
            <main class="content">{$body}</main>
            <footer class="footer">
                <span><strong>Ziphaven</strong> · release &amp; download hub</span>
                <span class="muted">Files are served via
                    <code>/download.php?file=&lt;name&gt;</code></span>
            </footer>
        </body>
        </html>
        HTML;
    }

    /** Pill tag describing the kind of a release artefact. */
    public static function kindTag(string $kind): string
    {
        $k = self::esc($kind);
        $labels = [
            'linux' => 'Linux',
            'windows' => 'Windows',
            'sdk' => 'SDK',
            'doc' => 'Docs',
        ];
        $label = $labels[$kind] ?? ucfirst($kind);

        return '<span class="tag tag-' . $k . '">' . self::esc($label) . '</span>';
    }
}
