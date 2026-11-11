<?php

declare(strict_types=1);

/**
 * Ziphaven demo catalogue.
 *
 * A small, in-memory set of downloadable releases so the download manager
 * behaves like a real product. Each entry's ``file`` maps to a file under the
 * downloads directory (see Config::downloadsDir()); they are fetched through
 * download.php.
 */

namespace Ziphaven;

final class Files
{
    /**
     * @return list<array{
     *   file:string, name:string, version:string, size:string,
     *   released:string, kind:string, downloads:string, checksum:string
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'file' => 'ziphaven-cli-3.4.1-linux-x64.tar.gz',
                'name' => 'Ziphaven CLI',
                'version' => '3.4.1',
                'size' => '18.2 MB',
                'released' => '2024-07-01',
                'kind' => 'linux',
                'downloads' => '42,118',
                'checksum' => 'sha256:9f1c…a7e3',
            ],
            [
                'file' => 'ziphaven-desktop-3.4.1-win.zip',
                'name' => 'Ziphaven Desktop',
                'version' => '3.4.1',
                'size' => '96.7 MB',
                'released' => '2024-07-01',
                'kind' => 'windows',
                'downloads' => '128,904',
                'checksum' => 'sha256:4b20…11cd',
            ],
            [
                'file' => 'ziphaven-sdk-2.9.0.zip',
                'name' => 'Ziphaven SDK',
                'version' => '2.9.0',
                'size' => '7.4 MB',
                'released' => '2024-06-18',
                'kind' => 'sdk',
                'downloads' => '9,377',
                'checksum' => 'sha256:c3aa…9f80',
            ],
            [
                'file' => 'release-notes-3.4.1.txt',
                'name' => 'Release notes',
                'version' => '3.4.1',
                'size' => '3.1 KB',
                'released' => '2024-07-01',
                'kind' => 'doc',
                'downloads' => '5,540',
                'checksum' => 'sha256:77de…02ab',
            ],
        ];
    }
}
