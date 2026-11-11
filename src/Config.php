<?php

declare(strict_types=1);

/**
 * Ziphaven configuration.
 *
 * Runtime paths depend on the DATA_ROOT environment variable (the Docker image
 * sets DATA_ROOT=/app). Nothing here has side effects on include; callers ask
 * for a value through the helper functions below.
 *
 * Runtime layout:
 *   <data_root>/               <- DATA_ROOT        (holds flag.txt, the target)
 *   <data_root>/downloads/     <- DOWNLOADS dir    (the served release files)
 */

namespace Ziphaven;

final class Config
{
    /** Sub-directory (under DATA_ROOT) that downloads are served from. */
    public const DOWNLOADS_DIRNAME = 'downloads';

    /** Name of the challenge flag file (the path-traversal target). */
    public const FLAG_FILENAME = 'flag.txt';

    /** Default flag value; overridable via the FLAG env var / build arg. */
    public const DEFAULT_FLAG = 'FLAG{ZIPHAVEN_UNZIPPED}';

    /** Root that holds both the downloads dir and the flag. */
    public static function dataRoot(): string
    {
        $root = getenv('DATA_ROOT');
        if ($root === false || $root === '') {
            // Fall back to the repo root (one level above this src/ file).
            $root = \dirname(__DIR__);
        }

        return $root;
    }

    /** Absolute path of the directory downloads are served from. */
    public static function downloadsDir(): string
    {
        return self::dataRoot() . \DIRECTORY_SEPARATOR . self::DOWNLOADS_DIRNAME;
    }
}
