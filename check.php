<?php

namespace root;

use Frlnc\Slack\Core\Commander;
use Frlnc\Slack\Http\CurlInteractor;
use Frlnc\Slack\Http\SlackResponseFactory;

use Aws\S3\S3Client;

use League\Flysystem\Filesystem;
use League\Flysystem\AwsS3v2\AwsS3Adapter;

require 'vendor/autoload.php';
require 'Assert.php';

$config = include 'config.php';

function report($message)
{
    global $config;

    $interactor = new CurlInteractor;
    $interactor->setResponseFactory(new SlackResponseFactory);
    $commander = new Commander($config['slack'], $interactor);
    $commander->execute(
        'chat.postMessage',
        [
            'username' => 'PC Roel',
            'icon_emoji' => ':x:',
            'channel' => '#fake',
            'text' => $message
        ]
    );
}

$client = S3Client::factory($config['s3']);
$adapter = new AwsS3Adapter($client, 'jouwweb-mysql-backups');
$filesystem = new Filesystem($adapter);

$assert = new Assert($filesystem);
$result = $assert->assertBackups(new \DateTime);
if ($result !== true) {
    report($result);
}
