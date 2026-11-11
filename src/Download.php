<?php

declare(strict_types=1);

/**
 * Ziphaven download resolution — intentionally vulnerable (CWE-22, level 5).
 *
 * The developer knows about path traversal and rejects any request that still
 * contains "../" or "..\\" after a first look. The fatal mistake is the ORDER
 * of operations: the filter runs FIRST, and only AFTERWARDS does the code
 * urldecode() the value "one more time" (a misguided attempt to be lenient
 * about clients that encode filenames).
 *
 *   request  ?file=%252e%252e%252fflag.txt
 *     - the webserver / PHP decodes the query string once  -> "%2e%2e%2fflag.txt"
 *     - reject() sees NO literal "../" (it is still percent-encoded)   -> PASSES
 *     - urldecode() runs the "extra" pass                   -> "../flag.txt"
 *     - join -> downloads/../flag.txt                       -> ESCAPES
 *
 * So a DOUBLE-URL-ENCODED payload (and even a single-encoded one, see below)
 * slips a "../" past a filter that only ever inspected the still-encoded text.
 * This is a filter-before-decode ordering bug and is the whole point of this
 * lab. Do NOT reuse this pattern in real software.
 */

namespace Ziphaven;

final class Download
{
    /**
     * Resolve the requested download to an on-disk path, applying the
     * (broken) traversal guard.
     *
     * VULNERABLE BY DESIGN: the guard inspects the value BEFORE the final
     * urldecode(), so percent-encoded traversal sequences (``%2e%2e%2f`` or,
     * doubly-encoded, ``%252e%252e%252f``) are invisible to it and are turned
     * back into ``../`` only after the check has already passed.
     *
     * @throws \RuntimeException when the (pre-decode) value looks like traversal
     */
    public static function resolve(string $downloadsDir, string $requested): string
    {
        // --- The guard (runs too early) --------------------------------------
        // Reject anything that ALREADY contains a literal traversal sequence.
        // Looks thorough; it just runs before the decode below, so it never
        // sees the encoded payload.
        if (str_contains($requested, '../') || str_contains($requested, '..\\')) {
            throw new \RuntimeException('Rejected: path traversal detected.');
        }

        // --- The fatal "extra" decode ----------------------------------------
        // Decode "one more time" to be forgiving about encoded filenames. This
        // is what reconstitutes "../" out of "%2e%2e%2f" AFTER the guard.
        $decoded = urldecode($requested);
        // ---------------------------------------------------------------------

        return $downloadsDir . \DIRECTORY_SEPARATOR . $decoded;
    }
}
