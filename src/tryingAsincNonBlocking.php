<?php
require_once 'aws_credentials.php';
require_once __DIR__.'../../vendor/autoload.php';


use Aws\Result;
use Aws\Sdk;
use GuzzleHttp\HandlerStack;
use React\EventLoop\Factory;
use WyriHaximus\React\GuzzlePsr7\HttpClientAdapter;

$loop    = Factory::create();
$adapter = new HttpClientAdapter($loop);
$handler = new HandlerStack($adapter);


$sdk = new Sdk(
    ['credentials'=>['key'=>$queueKeyId,'secret'=>$queueSecretKeyId],
     'region'=>$queueRegion,'version'=>$queueVersion,'http_handler'=>$handler
    ]);

$parameters = [
'QueueUrl'          => $queueUrl,
'VisibilityTimeout' => 10,
'WaitTimeSeconds'   => 5,
'MaxNumberOfMessages' => 10
];

$sqsClient = $sdk->createSqs();

for ($index=0; $index < 100; $index++) {
    $sqsClient->receiveMessageAsync($parameters)
        ->then(
            function (Result $messages) {
                foreach ($messages->get('Messages') as $message) {
                    print_r(json_decode($message['Body']));
                }
            })
        ->otherwise(function ($rejected) {
            print_r($rejected);
        });

    $loop->futureTick(function () use ($handler,$loop) {
        \GuzzleHttp\Promise\queue()->run();
    });
}

$loop->run();