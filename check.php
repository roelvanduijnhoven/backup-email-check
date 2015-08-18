<?php

namespace root;

use Frlnc\Slack\Core\Commander;
use Frlnc\Slack\Http\CurlInteractor;
use Frlnc\Slack\Http\SlackResponseFactory;

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Ftp;

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
            'username' => 'Backup validator',
            'icon_emoji' => ':x:',
            'channel' => '#server',
            'text' => $message
        ]
    );
}

$filesystem = new Filesystem(new Ftp($config['ftp']));

$assert = new Assert($filesystem);
$result = $assert->assertBackups(new \DateTime);
if ($result !== true) {
    report('Waarschuwing: ' . $result);
}
