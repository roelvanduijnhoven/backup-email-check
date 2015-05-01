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
        $this->fs->getMetadata('server77-20150430.tgz')->willReturn($this->getMeta(123123123123));
        $this->fs->getMetadata('server77-20150501.tgz')->willReturn($this->getMeta(123123123923));

        $result = $this->assert->assertBackups(new \DateTime('2015-05-01'));

        $this->assertTrue($result);
    }

    public function testBackupTodayMissing()
    {
        $this->fs->getMetadata('server77-20150422.tgz')->willThrow(new FileNotFoundException('server77-20150422.tgz'));

        $result = $this->assert->assertBackups(new \DateTime('2015-04-22'));

        $this->assertEquals("Could not find database export 'server77-20150422.tgz'", $result);
    }

    public function testInvalidFileType()
    {
        $this->fs->getMetadata('server77-20150422.tgz')->willReturn($this->getMeta(323123123123));
        $this->fs->getMetadata('server77-20150423.tgz')->willReturn($this->getMeta(123123123123, 'text/plain'));

        $result = $this->assert->assertBackups(new \DateTime('2015-04-23'));

        $this->assertEquals("Database file type for 'server77-20150423.tgz' is not application/x-gzip", $result);
    }

    public function testTooSmall()
    {
        $this->fs->getMetadata('server77-20150422.tgz')->willReturn($this->getMeta(123));
        $this->fs->getMetadata('server77-20150423.tgz')->willReturn($this->getMeta(345));

        $result = $this->assert->assertBackups(new \DateTime('2015-04-23'));

        $this->assertEquals("Database export 'server77-20150423.tgz' is only 345 in size", $result);
    }

    public function testCouldNotReadMeta()
    {
        $this->fs->getMetadata('server77-20150422.tgz')->willReturn(false);
        $this->fs->getMetadata('server77-20150423.tgz')->willReturn($this->getMeta(123123123123));

        $result = $this->assert->assertBackups(new \DateTime('2015-04-23'));

        $this->assertEquals("Could not obtain meta-data from database export 'server77-20150422.tgz'", $result);
    }

    public function testLastBackupsIdentical()
    {
        $meta = $this->getMeta(123123123123);
        $this->fs->getMetadata('server77-20150422.tgz')->willReturn($meta);
        $this->fs->getMetadata('server77-20150421.tgz')->willReturn($meta);

        $result = $this->assert->assertBackups(new \DateTime('2015-04-22'));

        $this->assertEquals("Last two database are equal in size", $result);
    }

    protected function getMeta($size, $mimetype = 'application/x-gzip')
    {
        return $meta = [
            'size' => $size,
            'mimetype' => $mimetype
        ];
    }
}
