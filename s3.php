<?php
require 'config.php';
if (empty($hosts)) {
    echo "Hosts is not set. Exiting";
    die;
}
session_start();
use Aws\Exception\AwsException;

if (empty($_SESSION["post"]["awsid"])) {
    if (!empty($path)) {
        $callback = urlencode('https://' . $_SERVER['HTTP_HOST'] . '/' . $path . $_SERVER['REQUEST_URI']);
    } else {
        $callback = urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    }
    header("Location: .?callback=$callback");
    die();
}

if (isset($_POST['changeuser'])) {
    $_SESSION['post']['awsid'] = $_POST['changeuser'];
    $_SESSION["post"]["awssecret"] = $_POST['changeuserkey'];
}

require 'vendor/autoload.php';
if (!defined('awsAccessKey')) define('awsAccessKey', "{$_SESSION["post"]["awsid"]}");
if (isset($_SESSION["post"]["awssecret"])) {
    define('awsSecretKey', "{$_SESSION["post"]["awssecret"]}");
} else {
    define('awsSecretKey', "1");
}

$credjson = base64_decode(awsAccessKey);
$credarray = json_decode($credjson);

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
    'endpoint'         => "https://{$_SESSION["post"]["endpoint"]}",
    'version'          => 'latest',
    'region' => $config['s3-access']['region'],
    'use_path_style_endpoint' => true,
    'force_path_style' => true
]);

if (isset($_POST["new-folder"])) {
    if (empty($_POST["new-folder"])) {
        header("Location: ./browser.php#newfolderempty");
        die;
    } else {
        $prefix_name = "{$_GET['prefix']}{$_POST["new-folder"]}";
        $prefix_name = str_replace(' ', '_', $prefix_name);
        $prefix_name = preg_replace('/[^a-z0-9\_\-\.\/]/i', '', $prefix_name);
        if (empty($prefix_name)) {
            header("Location: ./browser.php#newfolderempty");
            die;
        }
        try {
            $request_status = $s3->putObject([
                'Bucket' => $config['s3-access']['bucket'],
                'Key' => $prefix_name . '/',
            ]); 
            header("Location: {$_SERVER['HTTP_REFERER']}");
        } catch (AWSException $e) {
            header("Location: ./browser.php#newfoldererror");
            die;
        }
    }
}

if (isset($_GET["bucket"])) {
    $file = $_GET["file"];
    try {
        $result = $s3->getObject([
            'Bucket' => $_GET["bucket"],
            'Key'    => $file
        ]);
        $re = '/[^\/]*$/m';
        preg_match_all($re, $file, $matches, PREG_SET_ORDER, 0);
        if (empty($matches[0][0])) {
            header("Content-Disposition: attachment; filename=file");
        } else {
            header("Content-Disposition: attachment; filename={$matches[0][0]}");
        }
        echo $result['Body'];
    } catch (AWSException $e) {
        echo '
        <!DOCTYPE html>
        <html>  
        <head>
            <meta charset="utf-8" />
            <title>S3 Error</title>
            <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />';
        echo "$bootstrapCss
        </head>
        <body>
        <div class=\"container mt-3\">"; 
            echo "<h1>" . $e->getStatusCode() . "\n" . $e->getAwsErrorCode() . "</h1><h2>" . htmlspecialchars($_GET["bucket"]) .  " bucket</h2>
            <p class='lead'><strong><a href='' data-bs-toggle='modal' data-bs-target='#changeuser'>Change User</a></strong></p>";
            echo "<hr />";
            if (empty($credarray->RGW_TOKEN->id)) {
                echo '<h3><b>Invalid User</b>. Check your credentials.</h3>';
            } else {
                echo '<h3>Current User: <b>' . htmlspecialchars($credarray->RGW_TOKEN->id) . '</b></h3>';
            }
            echo "<br />";
            echo explode( ';', $e->getMessage())[0];
            echo "<br />";
            echo "<br />";
            echo explode( ';', $e->getMessage())[1];
            echo '
        <div class="mb-3"><br /></div>
        <img src="https://media0.giphy.com/media/QbumCX9HFFDQA/giphy.gif" /> 
        </div>
        <div class="modal fade" id="changeuser" tabindex="-1" aria-labelledby="changeuser" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Change User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                            </button>
                        </div>
                        <div class="modal-body">';
                        if ($showRGWaccessSecret == "false") {
                            echo '<input type="password" name="changeuser" placeholder="Access Key ID" class="form-control w-100  me-2" required>';
                        } else {
                            echo '
                            <input type="text" name="changeuser" placeholder="Access Key ID" class="form-control w-100 me-2 mb-1" required>
                            <input type="password" name="changeuserkey" placeholder="Access Key Secret" class="form-control w-100  me-2" required>
                            ';
                        } 
                        echo '
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-primary" type="submit">Change User <i class="bi bi-arrow-right"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';
        echo "$bootstrapJs
        </body>
        </html>"; 
    }
}

if (isset($_FILES["file"])) {
    try {
        $file_name = "{$_GET['prefix']}{$_FILES['file']['name']}";
        $file_name = str_replace(' ', '_', $file_name);
        $file_name = preg_replace('/[^a-z0-9\_\-\.\/]/i', '', $file_name);
        $size = $_FILES['file']['size'];
        $tmp = $_FILES['file']['tmp_name'];
        $type = $_FILES['file']['type'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $request_status = $s3->putObject([
            'Bucket' => $config['s3-access']['bucket'],
            'ContentType' => $type,
            'Key' => $file_name,
            'Body' => fopen($tmp, 'rb'), //rb to open binary file (same as c)
        ]); 
    } catch (AWSException $e) {
        header("HTTP/1.0 400 Bad Request");
        echo $e->getMessage();
    }
}
?>
