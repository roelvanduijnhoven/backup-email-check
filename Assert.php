<?php

namespace root;

use League\Flysystem\FilesystemInterface;
use League\Flysystem\Plugin\ListWith;

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
        $this->filesystem->addPlugin(new ListWith);
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
            $expected = 's77_mail_' . $day->format('Y-m-d') . '.tar.gz';

            try {
                $size = $this->filesystem->getSize($expected);
                if ($size === false) {
                  return "Kan bestandsgrootte niet ophalen van '{$expected}'";
                }

                $sizes[] = $size;
                if ($size < 3.4 * 1000 * 1000 * 1000) {
                    $humanSize = $size / 1000 / 1000;
                    return "Email backup lijkt te klein ({$humanSize} MB)";
                }
            } catch (\League\Flysystem\FileNotFoundException $e) {
                return "Kon email backup niet vinden: '{$expected}'";
            }

            $day = $day->sub(new \DateInterval('P1D'));
        }

        if (count(array_unique($sizes)) === 1) {
            return "Laatste twee e-mail backups zijn even groot.";
        }

        return true;
    }
}
