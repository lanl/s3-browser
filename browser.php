<?php
require 'config.php';
if (empty($hosts)) {
    echo "Hosts is not set. Exiting";
    die;
}
session_start();

if (empty($_SESSION['post'])) {
    $_SESSION['post'] = $_POST;
}

if (empty($_SESSION["post"]["awsid"])) {
    if (!empty($path)) {
        $callback = urlencode('https://' . $_SERVER['HTTP_HOST'] . '/' . $path . $_SERVER['REQUEST_URI']);
    } else {
        $callback = urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    }
    header("Location: .?callback=$callback");
    die();
}

if (!isset($_SESSION["post"]["region"])) {
    $_SESSION["post"]["region"] = "";
}


if (isset($_GET['callback'])) {
    $callback = $_GET['callback'];
    $hosts = preg_split('/\,/', $hosts);
    foreach ($hosts as $host) {
        if (parse_url($callback, PHP_URL_HOST) == $host) {
            header("Location: {$_GET['callback']}");
            die();
        }
    }
    header("Location: .#callbackinvalid");
    die();
}

use Aws\Exception\AwsException;

require 'vendor/autoload.php';
$baseurl = "https://{$_SESSION["post"]["endpoint"]}";
function generate_secretKey()
{
    return "42"; // chosen by fair dice roll.
    // guaranteed to be random.
}

if (isset($_POST['changeuser'])) {
    $_SESSION['post']['awsid'] = $_POST['changeuser'];
    $_SESSION["post"]["awssecret"] = $_POST['changeuserkey'];
}

if (!defined('awsAccessKey')) define('awsAccessKey', "{$_SESSION["post"]["awsid"]}");
if (isset($_SESSION["post"]["awssecret"])) {
    define('awsSecretKey', "{$_SESSION["post"]["awssecret"]}");
} else {
    define('awsSecretKey', generate_secretKey());
}

$credjson = base64_decode(awsAccessKey);
$credarray = json_decode($credjson);

if (isset($_GET['bucket'])) {
    $url = strtok($url, '?');
    $_SESSION['post']['bucket'] = $_GET['bucket'];
}


$config = [
    's3-access' => [
        'key' => awsAccessKey,
        'secret' => awsSecretKey,
        'bucket' => "{$_SESSION["post"]["bucket"]}",
        'region' => "{$_SESSION["post"]["region"]}",
        'version' => 'latest',
        'endpoint' => "https://{$_SESSION["post"]["endpoint"]}"
    ]
];

$s3 = new Aws\S3\S3Client([
    'credentials' => [
        'key' => $config['s3-access']['key'],
        'secret' => $config['s3-access']['secret']
    ],
    'use_path_style_endpoint' => true,
    'force_path_style' => true,
    'endpoint' => $config['s3-access']['endpoint'],
    'version' => 'latest',
    'region' => $config['s3-access']['region']
]);

$url = "$baseurl/{$config['s3-access']['bucket']}";

if (isset($_GET['prefix'])) {
    $prefix = $_GET['prefix'];
} else {
    $prefix = '';
}

?>

<!DOCTYPE html>
<html lang="en-US">

<head>
    <meta charset="UTF-8">
    <title>S3 Browser</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="S3 File Browser">
    <?php echo $bootstrapIcons . $bootstrapCss; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.7.2/min/dropzone.min.css" integrity="sha512-bbUR1MeyQAnEuvdmss7V2LclMzO+R9BzRntEE57WIKInFVQjvX7l7QZSxjNDt8bg41Ww05oHSh0ycKFijqD7dA==" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/codemirror.min.css" integrity="sha512-xIf9AdJauwKIVtrVRZ0i4nHP61Ogx9fSRAkCLecmE2dL/U8ioWpDvFCAy4dcfecN72HHB9+7FfQj3aiO68aaaw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/addon/lint/lint.min.css" integrity="sha512-6Owk90V+dmnBh35Q/OWxqfmLXExGMWDwb7tijRebrz7lLkDxZ7RS+eiNQmpUPrlWtpQulb/BkatkyPwPkMhpUQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="icon" href="<?php echo $favicon; ?>" type="image/ico" />
    <style>
        .center {
            margin-right: auto;
            margin-left: auto;
            text-align: center;
        }

        .modal-body {
            overflow-x: auto;
        }

        th {
            cursor: pointer;
        }

        table.table .sort:after {
            font-family: bootstrap-icons;
            font-size: 0.75em;
            color: #333;
            font-weight: lighter;
            content: "";
            padding-right: 0.7em;
        }

        table.table .sort.desc:after {
            content: " \f235";
        }

        table.table .sort.asc:after {
            content: " \f229";
        }

        .center {
            margin-right: auto;
            margin-left: auto;
            text-align: center;
            display: block;
        }

        .form-control {
            width: inherit;
        }

        .CodeMirror-lint-tooltip { 
            z-index: 1070;
        }
    </style>
</head>

<body class="p-3">
    <div class="row">
        <div class="col-md-2">
            <a href=".?logout=true"><span class="btn btn-danger text-light mb-3"><i class="bi bi-box-arrow-in-left"></i> Logout</span></a>
        </div>
        <div class="col-md-6 center">
            <form action="browser.php?" method='GET'>
                <span class="h4"><?php echo 'Current S3 Bucket: ' . htmlspecialchars($config['s3-access']['bucket']) ?></span>
                <div class="input-group mt-1">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="bucketAdmin" data-bs-toggle="dropdown" aria-expanded="false">
                            Bucket Admin
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="bucketAdmindropdown">
                            <li><a href="" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#policyview">View Bucket Policy</a></li>
                            <li><a href="" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#acl">View Bucket ACL</a></li>
                            <li><a href="" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#BucketOwnershipControls">View Bucket Ownership Controls</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a href="" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#policyedit">Put Bucket Policy</a></li>
                            <li><a href="" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#policydelete">Delete Bucket Policy</a></li>
                        </ul>
                    </div>
                    <input type="text" size="10" name="bucket" placeholder="Change Bucket" class="form-control" required>
                    <button class="btn btn-outline-success" type="submit"><i class="bi bi-arrow-right"></i></button>
                </div>
            </form>
        </div>
        <div class="col-md-2">
            <span><?php echo 'Current Endpoint: ' . htmlspecialchars($config['s3-access']['endpoint']) ?><br /></span>
            <?php 
            if (empty($credarray->RGW_TOKEN->id)) {
                echo 'Current User: <b>Invalid User</b>';
            } else {
                echo 'Current User:<br /><b>' . htmlspecialchars($credarray->RGW_TOKEN->id) . '</b>';
            }
            ?>
            
            <a href="" data-bs-toggle="modal" data-bs-target="#changeuser">Change User</a>
        </div>
    </div>
    <hr />
    <div id="browser-table" class="container-fluid">
        <form action="delete.php?prefix=<?php echo $prefix; ?>" method='POST'>
            <div id="alert_placeholder"></div>
            <div id="action-btns">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <input class="search form-control" placeholder="Search Table" onkeypress="return event.keyCode != 13;" />
                    <span class="btn btn-primary float-right mr-3" data-bs-toggle="modal" data-bs-target="#upload">Upload <i class="bi bi-cloud-upload-fill"></i></span>
                    <span class="btn btn-success float-right mr-3" data-bs-toggle="modal" data-bs-target="#new-folder">New Folder <i class="bi bi-plus h5"></i></span>
                    <button id="delete-button" class="btn btn-danger float-right mr-3" type="submit">Delete Selected <i class="bi bi-trash-fill"></i></button>
                </div>
            </div>
            <div id="loading" class="container-fluid center">
                <div class="spinner-border" role="status"></div>
                Loading... Please Wait.
            </div>
            <table id="table" class="table table-hover" style="display:none">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col"><input class="form-check-input" type="checkbox" name="delete-list-all[]" id="delete-list-all" />&nbsp<label class='form-check-label' for='delete-list-all'>Select</label></th>
                        <th class="sort" data-sort="filename" scope="col">Filename</th>
                        <th class="sort" data-sort="size" scope="col">Size</th>
                        <th class="sort" data-sort="last_modified" scope="col">Last Modified</th>
                        <th class="sort" data-sort="pub_link" scope="col">Public Link</th>
                    </tr>
                </thead>
                <tbody class="list" id="browser-tbody">
                    <?php
                    $objectNumber = 0;
                    if (!empty($prefix)) {
                        $back = preg_replace('/^\.*$/', '', dirname($prefix)); 
                        echo
                        '<tr><td></td>',
                        "<td><a href=?bucket={$config['s3-access']['bucket']}&prefix=$back" . ((dirname($prefix) !== '.') ? "/" : "") .  "><i class='bi bi-arrow-90deg-up'></i> ../</a>",
                        '</td><td></td><td></td><td></td>',
                        '</tr>';
                    }
                    try {
                        $output = $s3->getIterator(
                            'ListObjects',
                            array(
                                'Bucket' => $config['s3-access']['bucket'],
                                'Prefix' => "$prefix",
                                'Delimiter' => '/'
                            )
                        );
                        $results = $s3->getPaginator('ListObjects', [
                            'Bucket'    => $config['s3-access']['bucket'],
                            'Prefix' => "$prefix",
                            'Delimiter' => '/'
                        ]);
                        $expression = '[CommonPrefixes[].Prefix][]';
                        foreach ($results->search($expression) as $prefix) {
                            $objectNumber++;
                            echo
                            '<tr>',
                            '<td>',
                            "<div class='form-check'><input class='form-check-input' type='checkbox' name='delete-list-prefix[]' disabled value='' id='$prefix'><label class='form-check-label' for='$prefix'></label></div>",
                            '</td>',
                            "<td class='filename'><a href=?bucket={$config['s3-access']['bucket']}&prefix={$prefix}>$prefix</a>",
                            '</td>',
                            '<td class="size">',
                            "Folder",
                            '</td>',
                            '<td class="last_modified">',
                            '¯\_(ツ)_/¯',
                            '</td>',
                            '<td class="pub_link">',
                            "<a href='{$config['s3-access']['endpoint']}/{$config['s3-access']['bucket']}/{$prefix}' target='_blank'>{$config['s3-access']['endpoint']}/{$config['s3-access']['bucket']}/{$prefix}</a>",
                            '</td>',
                            '</tr>';
                        }
                        $totalSize = '0';
                        #echo '<pre>' . var_dump($output) . '</pre>';
                        foreach (($output) as $object) {
                            global $totalSize;
                            $objectNumber++;
                            if (empty($object['Size'])) {
                                $totalSize = '0';
                            } else {
                                $totalSize += $object['Size'];
                            }
                            //echo '<pre>' . var_dump($object['Prefix']) . '</pre>';
                            if (isset($object['Key'])) {
                                echo
                                '<tr>',
                                '<td>',
                                "<div class='form-check'><input class='form-check-input' type='checkbox' name='delete-list[]' value='{$object['Key']}' id='{$object['Key']}'><label class='form-check-label' for='{$object['Key']}'></label></div>",
                                '</td>',
                                "<td class='filename'><a href=s3.php?bucket={$config['s3-access']['bucket']}&file=" . rawurlencode($object['Key']) . " target='_blank'>{$object['Key']}</a>",
                                '</td>',
                                '<td class="size">',
                                number_format($object['Size'] / 1048576, 6),
                                ' MB',
                                '</td>',
                                '<td class="last_modified">',
                                $object['LastModified'],
                                '</td>',
                                '<td class="pub_link">',
                                "<a href='{$config['s3-access']['endpoint']}/{$config['s3-access']['bucket']}/{$object['Key']}' target='_blank'>{$config['s3-access']['endpoint']}/{$config['s3-access']['bucket']}/{$object['Key']}</a>",
                                '</td>',
                                '</tr>';
                            }
                        }
                    } catch (AWSException $e) {
                        echo '<div class="alert alert-danger alert-dismissible mt-3 mb-3"><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                        echo "<b>" . $e->getStatusCode() . "\n" .  $e->getAwsErrorCode() . "</b>";
                        echo explode(';', $e->getMessage())[1];
                        echo "</div></div><script>document.getElementById('loading').style.visibility = 'hidden';</script>";
                    }
                    if (isset($_POST["policyput"])) {
                        try {
                            $resp = $s3->putBucketPolicy([
                                'Bucket' => $config['s3-access']['bucket'],
                                'Policy' => "{$_POST['policyput']}",
                            ]); 
                            #header("Location: {$_SERVER['HTTP_REFERER']}");
                            echo '<div class="alert alert-success alert-dismissible mt-3 mb-3"><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                            echo 'Policy Put Successfully';
                            echo "</div></div><script>document.getElementById('loading').style.visibility = 'hidden';</script>";
                        } catch (AWSException $ex) {
                            echo '<div class="alert alert-danger alert-dismissible mt-3 mb-3"><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                            echo "<b>" . $e->getStatusCode() . "\n" .  $e->getAwsErrorCode() . "</b>";
                            echo explode(';', $ex->getMessage())[1];
                            echo "</div></div><script>document.getElementById('loading').style.visibility = 'hidden';</script>";
                        }
                    }
                    if (!empty($_POST["policydelete"])) {
                        try {
                            $resp = $s3->deleteBucketPolicy([
                                'Bucket' => $config['s3-access']['bucket'],
                            ]); 
                            echo '<div class="alert alert-success alert-dismissible mt-3 mb-3"><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                            echo 'Policy Deleted';
                            echo "</div></div><script>document.getElementById('loading').style.visibility = 'hidden';</script>";
                        } catch (AWSException $ex) {
                            echo '<div class="alert alert-danger alert-dismissible mt-3 mb-3"><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                            echo "<b>" . $e->getStatusCode() . "\n" .  $e->getAwsErrorCode() . "</b>";
                            echo explode(';', $ex->getMessage())[1];
                            echo "</div></div><script>document.getElementById('loading').style.visibility = 'hidden';</script>";
                        }
                    }
                    echo '<div class="row mt-2">';
                    echo '<div class="col-9">';
                    echo "<a href=?bucket={$config['s3-access']['bucket']}><i class='bi bi-house'></i></a>";
                    $path = $_GET['prefix'];
                    if ($path != '') {
                        $exploded = explode('/', $path);
                        $count = count($exploded);
                        $array = array();
                        $parent = '';
                        for ($i = 0; $i < $count; $i++) {
                            $parent = trim($parent . '/' . $exploded[$i], '/');
                            echo "/";
                            echo "<a href='?prefix={$parent}/'>" . $exploded[$i] . "</a>";
                        }
                    }
                    echo 
                    "</div>",
                    "<div class='col-3 d-flex justify-content-end gap-1'>";
                    $FriendlytotalSize = $totalSize / 1024 / 1024 / 1024 . ' GB';
                    echo '<b>Size:</b> ' . bcdiv(floatval(preg_replace("/[^-0-9\.]/", "", $FriendlytotalSize)), 1, 3) . ' GB,';
                    if ($objectNumber >= '1000') {
                        echo ' <b>Number of Objects:</b>' . $objectNumber . ' <i class="text-warning bi bi-exclamation-triangle-fill" data-bs-toggle="tooltip" data-bs-placement="top" title="Lots of objects, filter and sort may be slow."></i>';
                    } else {
                        echo ' <b>Number of Objects:</b> ' . $objectNumber;
                    }
                    echo "</div>";
                    echo "</div>";
                    ?>
                </tbody>
            </table>
        </form>
    </div>

    <div class="modal fade" id="upload" tabindex="-1" aria-labelledby="uploader" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Files</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <p>Select files, when you're finished click "Upload"</p>
                    <div class="dropzone"></div>
                </div>
                <div class="modal-footer">
                    <button id="cancelUpload" class="btn btn-danger">Cancel Upload <i class="bi bi-x"></i></button>
                    <button id="startUpload" class="btn btn-primary">Upload <i class="bi bi-arrow-right"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="changeuser" tabindex="-1" aria-labelledby="changeuser" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="browser.php" method='POST'>
                    <div class="modal-header">
                        <h5 class="modal-title">Change User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body">
                    <?php 
                    if ($showRGWaccessSecret == "false") {
                        echo '<input type="password" name="changeuser" placeholder="Access Key ID" class="form-control w-100  me-2" required>';
                    } else {
                        echo '
                        <input type="text" name="changeuser" placeholder="Access Key ID" class="form-control w-100 me-2 mb-1" required>
                        <input type="password" name="changeuserkey" placeholder="Access Key Secret" class="form-control w-100  me-2" required>
                        ';
                    }
                    ?>
                        
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" type="submit">Change User <i class="bi bi-arrow-right"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="new-folder" tabindex="-1" aria-labelledby="new-folder" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="s3.php?prefix=<?php echo $prefix; ?>" method='POST'>
                    <div class="modal-header">
                        <h5 class="modal-title">Add Folder / Prefix</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="text" name="new-folder" placeholder="Folder/Prefix Name" class="form-control w-100  me-2" required>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" type="submit">Add Folder / Prefix <i class="bi bi-arrow-right"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="policydelete" tabindex="-1" aria-labelledby="policydelete" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method='POST'>
                    <div class="modal-header">
                        <h5 class="modal-title">Are you sure you want to delete this bucket policy?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" aria-label="Close">No
                        </button>
                        <input class="btn btn-primary" value="Delete Policy" type="submit" name="policydelete"></input>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="policyedit" tabindex="-1" aria-labelledby="policyedit" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="policyput" action="" method='POST'>
                    <div class="modal-header">
                        <h5 class="modal-title">Edit / Put Policy</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Help: <a href="https://docs.ceph.com/en/latest/radosgw/bucketpolicy/" target="_blank">https://docs.ceph.com/en/latest/radosgw/bucketpolicy/</a></p>
                        <textarea id="code" name="policyput" style="height: 400px; width: 600px;" required ><?php try{$resp=$s3->getBucketPolicy(['Bucket'=>$config['s3-access']['bucket'],]);echo (string) filter_var($resp->get('Policy'), FILTER_SANITIZE_SPECIAL_CHARS);}catch(AWSException $e){if ($e->getStatusCode () == '403'){echo "Access Denied. You do not have access to edit policy.";}} ?></textarea>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" id="policyputsubmit">Put Policy <i class="bi bi-arrow-right"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="policyview" tabindex="-1" aria-labelledby="policyview" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bucket Policy Viewer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <?php
                    // Get the policy of a specific bucket
                    try {
                        $resp = $s3->getBucketPolicy([
                            'Bucket'    => $config['s3-access']['bucket'],
                        ]);
                        echo '<pre>';
                        echo htmlspecialchars($resp->get('Policy'));
                        echo "\n";
                        echo '</pre>';
                    } catch (AWSException $e) {
                        // Display error message
                        echo '<b>' . $e->getStatusCode() . "</b>\n";
                        echo $e->getAwsErrorCode ();
                        echo "<br /><br />";
                        echo $e->getMessage();
                        
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="acl" tabindex="-1" aria-labelledby="acl" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bucket ACL Viewer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <?php
                    try {
                        $resp = $s3->getBucketAcl([
                            'Bucket'    => $config['s3-access']['bucket'],
                        ]);
                        echo '<pre>';
                        echo htmlspecialchars($resp);
                        echo '</pre>';
                    } catch (AWSException $e) {
                        // output error message if fails
                        echo '<b>' . $e->getStatusCode() . "</b>\n";
                        echo $e->getAwsErrorCode ();
                        echo "<br /><br />";
                        echo $e->getMessage();
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="BucketOwnershipControls" tabindex="-1" aria-labelledby="acl" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bucket Ownership Controls</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <?php
                    try {
                        $resp = $s3->getBucketOwnershipControls([
                            'Bucket'    => $config['s3-access']['bucket'],
                        ]);
                        echo '<pre>';
                        echo htmlspecialchars($resp);
                        echo '</pre>';
                    } catch (AWSException $e) {
                        // output error message if fails
                        echo '<b>' . $e->getStatusCode() . "</b>\n";
                        echo $e->getAwsErrorCode ();
                        echo "<br /><br />";
                        echo $e->getMessage();
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg==" crossorigin="anonymous"></script>
    <?php echo $bootstrapJs; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.7.2/min/dropzone.min.js" integrity="sha512-9WciDs0XP20sojTJ9E7mChDXy6pcO0qHpwbEJID1YVavz2H6QBz5eLoDD8lseZOb2yGT8xDNIV7HIe1ZbuiDWg==" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.js" integrity="sha512-bvQAGUdz84PpeKWrShm1eEp20Fkcv7PJespsVWVtkTB74C8rNmg75Hru8w1AXnfiNHXnT/XF2jqZskyU3bIaMQ==" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/codemirror.min.js" integrity="sha512-ZTpbCvmiv7Zt4rK0ltotRJVRaSBKFQHQTrwfs6DoYlBYzO1MA6Oz2WguC+LkV8pGiHraYLEpo7Paa+hoVbCfKw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/mode/javascript/javascript.min.js" integrity="sha512-cMW1RqDC6+KwVloyQoUjqgmM5B0QGZcpZEAHJsab2WrCBmuyqoojv6cQ8O7KtAYtPym2vCooftLPeGzf0klXyA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/addon/edit/matchbrackets.min.js" integrity="sha512-03Ei8A+mDhwF6O/CmXM47U4A9L7TobAxMbPV2Wn5cEbY76lngHQRyvvmnqhJ8IthfoxrRqmtoBxQCxOC7AOeKw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/addon/display/autorefresh.min.js" integrity="sha512-vAsKB7xXQAWMn5kcwda0HkFVKUxSYwrmrGprVhmbGFNAG1Ij+2epT3zzdwjHTJyDsKXsiEdrUdhIxh7loHyX+A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsonlint/1.6.0/jsonlint.min.js" integrity="sha512-6qbnCNQe7wVcBDvhNJT6lZsbDKHCQQBk7yOBQg4s/9GG812CknK0EEIqG2IS10XuNxIQNWjMJd6VLwwezICz6w==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/addon/lint/lint.min.js" integrity="sha512-S0hgTp+xia7BWDYrlUFrlBrrrilXjrX+LJz+v+3yuO7r4nGc/FL0E92BJFCsUTmEn6xwkQ95IrWgEq9dy7gIiQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.61.1/addon/lint/json-lint.min.js" integrity="sha512-40xVcCik6TlUiZadnRc6ZM0BN65s7F+C3K7eBqGRf8dmjKApjzoiT/GB1GJmdICOZbXjJCiTBbVlsIvFs8A/+Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        var options = {
            valueNames: ['filename', 'size', 'last_modified', 'pub_link']
        };
        var tableList = new List('browser-table', options);
    </script>
    <script>
        $('#loading').hide();
        $('#table').show();
    </script>
    <script>
        function showalert(title, message, alerttype) {

            $('#alert_placeholder').append('<div id="alertdiv" role="alert" class="alert alert-dismissible mt-3 mb-3 ' + alerttype + '"><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' + '<strong>' + title + ':</strong> ' + message + '</div>')
            setTimeout(function() {
                $("#alertdiv").remove();
            }, 20000);
        }

        if (location.hash === "#delerror") {
            showalert("Delete Error", "Access Denied, If you\'re sure you have access let me know.", "alert-danger")
        }
        if (location.hash === "#delempty") {
            showalert("Delete Error", "Nothing to delete. (Did you select anything?) Also, you can't delete folders.", "alert-danger")
        }
        if (location.hash === "#newfoldererror") {
            showalert("Create Error", "Error creating new folder / prefix (Check permissions)", "alert-danger")
        }
        if (location.hash === "#newfolderempty") {
            showalert("Create Error", "Prefix / Folder name is empty.", "alert-danger")
        }
    </script>
    <script>
        Dropzone.autoDiscover = false;

        $(function() {
            //Dropzone class
            var myDropzone = new Dropzone(".dropzone", {
                url: "s3.php?prefix=<?php echo $prefix; ?>",
                paramName: "file",
                maxFiles: 10,
                parallelUploads: 10,
                autoProcessQueue: false,

            });
            myDropzone.on("queuecomplete", function(file) {
                window.location.reload(true);
            });
            $('#startUpload').click(function() {
                myDropzone.processQueue();
            });
            $('#cancelUpload').click(function() {
                myDropzone.removeAllFiles();
            });

        });

        $('#delete-list-all').click(function(event) {
            if (this.checked) {
                // Iterate each checkbox
                $(':checkbox').each(function() {
                    this.checked = true;
                });
            } else {
                $(':checkbox').each(function() {
                    this.checked = false;
                });
            }
        });
    </script>
    <script>
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
    <script>
        var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
            mode: "application/json",
            lineNumbers: true,
            matchBrackets: true,
            continueComments: "Enter",
            autoRefresh: true,
            gutters: ["CodeMirror-lint-markers"],
            lint: true
        });
        $(function() {
            $('#policyputsubmit').click(function(e) {
                e.preventDefault();
                $("#policyput").submit();
            });
        });
    </script>

</body>

</html>