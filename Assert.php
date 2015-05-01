<?php

namespace root;

use League\Flysystem\FilesystemInterface;

class Assert
{
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @param FilesystemInterface $filesystem
     */
    function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Return string error message if not found to be correct
     *
     * @return string|bool
     */
    public function assertBackups($today)
    {
        $day = $today;
        $sizes = [];
        for ($i = 0; $i < 2; $i++) {
            $expected = 'server77-' . $day->format('Ymd') . '.tgz';

            try {
                $info = $this->filesystem->getMetadata($expected);
                if ($info === false) {
                    return "Could not obtain meta-data from database export '{$expected}'";
                }

                $sizes[] = $info['size'];
                if ($info['size'] < 2547421976) {
                    return "Database export '{$expected}' is only {$info['size']} in size";
                }

                if ($info['mimetype'] !== 'application/x-gzip') {
                    return "Database file type for '{$expected}' is not application/x-gzip";
                }
            } catch (\League\Flysystem\FileNotFoundException $e) {
                return "Could not find database export '{$expected}'";
            }

            $day = $day->sub(new \DateInterval('P1D'));
        }

        if (count(array_unique($sizes)) === 1) {
            return "Last two database are equal in size";
        }

        return true;
    }
}
