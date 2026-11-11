<?php

declare(strict_types=1);

/**
 * Tests for Ziphaven's download resolution.
 *
 * These document the behaviour executably (CWE-22, level 5):
 *   - a listed artefact resolves inside the downloads directory,
 *   - a plain "../" payload is rejected by the guard,
 *   - a percent-encoded "../" slips past the guard and is reconstituted by the
 *     "extra" urldecode(), escaping the downloads directory.
 *
 * A note on the two encoding layers. In production the value reaches
 * Download::resolve() AFTER PHP has already decoded the query string once, so:
 *
 *   browser / Burp sends   ?file=%252e%252e%252fflag.txt   (double-encoded)
 *   PHP populates $_GET     => "%2e%2e%2fflag.txt"          (one layer peeled)
 *   resolve() guard sees    "%2e%2e%2f..."  -> no literal ../ -> PASSES
 *   resolve() urldecode()   => "../flag.txt"                 -> ESCAPES
 *
 * The tests call resolve() with the value as it arrives in $_GET (i.e. one
 * layer already removed), which is "%2e%2e%2f...". The exploit test asserts the
 * vulnerability is present: if someone reorders decode-before-filter, it turns
 * red and flags that the lab no longer teaches what it claims to.
 */

namespace Ziphaven\Tests;

use PHPUnit\Framework\TestCase;
use Ziphaven\Download;

final class DownloadTest extends TestCase
{
    private string $dir = '/app/downloads';

    public function testLegitimateFileStaysInsideTheDirectory(): void
    {
        $path = Download::resolve($this->dir, 'ziphaven-sdk-2.9.0.zip');
        self::assertSame('/app/downloads/ziphaven-sdk-2.9.0.zip', $path);
    }

    public function testPlainDotDotSlashIsRejectedByTheGuard(): void
    {
        $this->expectException(\RuntimeException::class);
        Download::resolve($this->dir, '../flag.txt');
    }

    public function testBackslashTraversalIsRejectedByTheGuard(): void
    {
        $this->expectException(\RuntimeException::class);
        Download::resolve($this->dir, '..\\flag.txt');
    }

    public function testEncodedTraversalBypassesTheGuardAndEscapes(): void
    {
        // Value as it arrives in $_GET from a double-encoded request
        // (?file=%252e%252e%252fflag.txt): PHP peeled one layer, leaving the
        // still-encoded "%2e%2e%2f". The guard sees no literal "../"...
        $encoded = '%2e%2e%2fflag.txt';

        // ...so it does NOT throw, and the extra urldecode() rebuilds "../".
        $path = Download::resolve($this->dir, $encoded);

        self::assertSame('/app/downloads/../flag.txt', $path);
        self::assertStringContainsString('/app/downloads/../', $path);
    }

    public function testEncodedSlashFormAlsoEscapes(): void
    {
        // "..%2fflag.txt" (only the slash encoded) likewise hides the literal
        // "../" from the guard and is decoded afterwards.
        $path = Download::resolve($this->dir, '..%2fflag.txt');
        self::assertSame('/app/downloads/../flag.txt', $path);
    }
}
