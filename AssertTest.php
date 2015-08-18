<?php

namespace root;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;

require 'vendor/autoload.php';
require 'Assert.php';

class AssertTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FilesystemInterface
     */
    protected $fs;

    /**
     * @var Assert
     */
    protected $assert;

    public function setUp()
    {
        $this->fs = $this->prophesize('League\Flysystem\FilesystemInterface');
        $this->assert = new Assert($this->fs->reveal());
    }

    public function testValidBackups()
    {
        $this->fs->getSize('s77_mail_2015-04-30.tar.gz')->willReturn($this->getMeta(123123123123));
        $this->fs->getSize('s77_mail_2015-05-01.tar.gz')->willReturn($this->getMeta(123123123923));

        $result = $this->assert->assertBackups(new \DateTime('2015-05-01'));

        $this->assertTrue($result);
    }

    public function testBackupTodayMissing()
    {
        $this->fs->getSize('s77_mail_2015-04-22.tar.gz')->willThrow(new FileNotFoundException('s77_mail_20150422.tar.gz'));

        $result = $this->assert->assertBackups(new \DateTime('2015-04-22'));

        $this->assertEquals("Kon email backup niet vinden: 's77_mail_2015-04-22.tar.gz'", $result);
    }

    public function testTooSmall()
    {
        $this->fs->getSize('s77_mail_2015-04-22.tar.gz')->willReturn($this->getMeta(123));
        $this->fs->getSize('s77_mail_2015-04-23.tar.gz')->willReturn($this->getMeta(345));

        $result = $this->assert->assertBackups(new \DateTime('2015-04-23'));

        $this->assertEquals("Email backup lijkt te klein (0.000345 MB)", $result);
    }

    public function testCouldNotReadFileSize()
    {
        $this->fs->getSize('s77_mail_2015-04-22.tar.gz')->willReturn(false);
        $this->fs->getSize('s77_mail_2015-04-23.tar.gz')->willReturn($this->getMeta(123123123123));

        $result = $this->assert->assertBackups(new \DateTime('2015-04-23'));

        $this->assertEquals("Kan bestandsgrootte niet ophalen van 's77_mail_2015-04-22.tar.gz'", $result);
    }

    public function testLastBackupsIdentical()
    {
        $meta = $this->getMeta(123123123123);
        $this->fs->getSize('s77_mail_2015-04-22.tar.gz')->willReturn($meta);
        $this->fs->getSize('s77_mail_2015-04-21.tar.gz')->willReturn($meta);

        $result = $this->assert->assertBackups(new \DateTime('2015-04-22'));

        $this->assertEquals("Laatste twee e-mail backups zijn even groot.", $result);
    }

    protected function getMeta($size, $mimetype = 'application/x-gzip')
    {
        return $size;
    }
}
