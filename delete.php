<?php
session_start();
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;

if (empty($_SESSION["post"]["awsid"])) {
    header("Location: .#notloggedin");
    die();
}

require 'vendor/autoload.php';
if (!defined('awsAccessKey')) define('awsAccessKey', "{$_SESSION["post"]["awsid"]}");
if (isset($_SESSION["post"]["awssecret"])) {
    define('awsSecretKey', "{$_SESSION["post"]["awssecret"]}");
} else {
    define('awsSecretKey', "1");
}


$config = [
    's3-access' => [
        'key' => awsAccessKey,
        'secret' => awsSecretKey,
        'bucket' => "{$_SESSION["post"]["bucket"]}",
        'region' => "{$_SESSION["post"]["region"]}",
        'version' => 'latest',
    ]
];

$s3 = new Aws\S3\S3Client([
    'credentials' => [
        'key' => $config['s3-access']['key'],
        'secret' => $config['s3-access']['secret']
    ],
    'endpoint' => "https://{$_SESSION["post"]["endpoint"]}",
    'version'          => 'latest',
    'region' => $config['s3-access']['region'],
    'use_path_style_endpoint' => true,
    'force_path_style' => true
]);

if (!empty($_POST['delete-list'])) {
    for ($i = 0; $i < count($_POST['delete-list']); $i++) {
        try {
            $s3->deleteObject([
                'Bucket' => $config['s3-access']['bucket'],
                'Key' => "{$_POST['delete-list'][$i]}",
            ]);
            header("Location: {$_SERVER['HTTP_REFERER']}");
        } catch (S3Exception $e) {
            header("Location: browser.php#delerror");
            exit('Error deleting file');
        }
    }
}

if (empty($_POST['delete-list'])) {
    header("Location: browser.php#delempty");
    exit('Error deleting file');
}
