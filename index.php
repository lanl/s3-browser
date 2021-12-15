<?php
require 'config.php';
if (empty($hosts)) {
    echo "Hosts are not set. Exiting";
    die;
}
session_start();
$_SESSION = array();
$params = session_get_cookie_params();
setcookie(
    session_name(),
    '',
    time() - 42000,
    $params["path"],
    $params["domain"],
    $params["secure"],
    $params["httponly"]
);
session_destroy();

$secure = true; // receive the cookie over HTTPS
$httponly = true; // prevent JavaScript access to session cookie
$samesite = 'strict';
if (PHP_VERSION_ID < 70300) {
    session_set_cookie_params('/; samesite=' . $samesite, $_SERVER['HTTP_HOST'], $secure, $httponly);
} else {
    session_set_cookie_params([
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => $samesite
    ]);
    session_start();
}

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />

    <title>S3 Browser Login</title>
    <link rel="icon" href="<?php echo $favicon; ?>" type="image/ico" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <?php echo $bootstrapIcons . $bootstrapCss; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css" integrity="sha512-6S2HWzVFxruDlZxI3sXOZZ4/eJ8AcxkQH1+JjSe/ONCEqR9L4Ysq5JdT5ipqtzU7WHalNwzwBv+iE51gNHJNqQ==" crossorigin="anonymous" />

    <style>
        body {
            min-height: 100%;
            background-image: url("img/bg.jpg");
            background-position: center center;
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .toast {
            opacity: 1;
        }

        .login {
            position: relative;
            max-width: 500px;
            max-height: 25rem;
            margin: 5% auto 0;
            padding: 30px 30px 10px 30px;
            color: #444;
            border: 1px solid #ddd;
            background-color: #fff;
            -webkit-box-shadow: 1px 1px 7px rgba(0, 0, 0, .1);
            box-shadow: 1px 1px 7px rgba(0, 0, 0, .1);
            border-radius: 5px;
        }

        @media (max-width: 500px) {
            .login {
                max-width: 90%;
                max-height: 25rem;
            }
        }

        .text-shadow {
            text-shadow: #000 1px 2px 0.5em;
        }
    </style>
</head>

<body>
    <div class="container mt-3">
        <div id="alert_placeholder"></div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-10 login">
            <a href="" data-bs-toggle="modal" data-bs-target="#help"><i class="bi bi-question-circle h4 d-grid gap-2 float-end"></i></a>
                    <?php 
                    if (isset($_GET['callback'])) {
                        echo '<span class="h3">Need AWS Access ID for <br /></span><small>' . htmlspecialchars($_GET['callback']) . '</small>';
                    } else {
                        echo '<span class="h3">S3 Browser</span>';
                    }
                    ?>
                
                <hr />
                <form id="s3-login" method="post" name="login" action="browser.php<?php if (isset($_GET['callback'])) {echo '?callback=' . htmlspecialchars(urlencode($_GET['callback']));} ?>
                " onsubmit="process()">
                    <div class="input-group mb-2">
                        <div class="input-group-text"><i class="bi bi-cloud"></i></div>
                        <select class="form-select" list="endpoints" name="endpoint" id="endpoint" placeholder="Select an endpoint">
                        <?php 
                        foreach (($endpoints) as $endpoint) {
                            echo "<option value=" . $endpoint . ">$endpoint</option>";
                        } 
                        ?>
                        </select>
                    </div>
                    <div id="bucket">
                        <div class="input-group mb-2">
                            <div class="input-group-text"><i class="bi bi-bucket"></i></div>
                            <input type="text" size="20" name="bucket" placeholder="Bucket" class="form-control" required>
                        </div>
                    </div>
                    <?php 
                    if ($showRGWaccessSecret == "false") {
                        echo '
                        <div id="amazonID">
                            <div class="input-group">
                                <div class="input-group-text"><i class="bi bi-person"></i></div>
                                <input type="password" size="20" name="awsid" placeholder="AWS Access Key ID" class="form-control" required>
                            </div>
                        </div>';
                    } else {
                        echo '
                        <div class="row">
                            <div class="col-md-6" id="amazonID">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="bi bi-person"></i></div>
                                    <input type="text" size="20" name="awsid" placeholder="AWS Access Key ID" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6" id="amazonKeyrgw">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="bi bi-key"></i></div>
                                    <input type="password" size="20" name="awssecret" placeholder="AWS Access Key Secret" class="form-control" required>
                                </div>
                            </div>
                        </div>';
                    }
                    ?>

                    
                    <div class="row">
                        <div class="col-md-6" id="bucketSm">
                            <div class="input-group mb-2">
                                <div class="input-group-text"><i class="bi bi-bucket"></i></div>
                                <input type="text" size="20" name="bucket" placeholder="Bucket" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6" id="amazonRegion">
                            <div class="input-group mb-2">
                                <div class="input-group-text"><i class="bi bi-globe"></i></div>
                                <input type="text" size="20" name="region" placeholder="Region" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6" id="amazonIDsm">
                            <div class="input-group">
                                <div class="input-group-text"><i class="bi bi-person"></i></div>
                                <input type="text" size="20" name="awsid" placeholder="AWS Access Key ID" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6" id="amazonKey">
                            <div class="input-group">
                                <div class="input-group-text"><i class="bi bi-key"></i></div>
                                <input type="password" size="20" name="awssecret" placeholder="AWS Access Key Secret" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="login=true" class="btn btn-primary mt-3">Login <i class="bi bi-box-arrow-in-right"></i></button>
                    </div>
                    <p class="text-center mt-3"><a href="mailto:<?php echo $helpEmail; ?>?subject=I would like a RGW S3 Account." data-toggle="modal">No account? Email us to get one.</a></p>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="help" tabindex="-1" aria-labelledby="help" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">S3 Browser Help</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ol>
                        <li><b>Where can I get a RGW Account? / I need help!</b></li>
                        Email Us: <a href="mailto:<?php echo $helpEmail; ?>?subject=I would like a RGW S3 Account."><?php echo $helpEmail; ?></a>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg==" crossorigin="anonymous"></script>
    <?php echo $bootstrapJs; ?>
    <script>
        function showalert(title, message, alerttype) {

            $('#alert_placeholder').append('<div id="alertdiv" role="alert" class="alert ' + alerttype + '">' + '<strong>' + title + ':</strong> ' + message + '</div>')
            setTimeout(function() {
                $("#alertdiv").remove();
            }, 30000);
        }

        $(document).ready(function() {
            if (location.hash === "#notloggedin") {
                showalert("Login Error", "Access Denied, Did you login here first?", "alert-danger")
            }
            if (location.hash === "#delerror") {
                showalert("Delete Error", "Access Denied, If you\'re sure you have access let me know.", "alert-danger")
            }
            if (location.hash === "#callbackinvalid") {
                showalert("Redirect Error", "Callback domain specifed not defined in hosts", "alert-danger")
            }
        });

        $(document).ready(function() {
            $("#endpoint").on("focus keyup keypress blur change paste cut", endpointcheck);
            $(document).ready(endpointcheck);
        });

        function endpointcheck() {
            const params = new URL(window.location.href).searchParams;
            console.log(params.get('callback'))
            if (params.get('callback') !== null) {
                $('#bucket').show().find('input').prop('disabled', true);
                $('#amazonID').show().find('input').prop('disabled', false);
                $('#amazonKeyrgw').show().find('input').prop('disabled', false);
                $('#bucketSm').hide().find('input').prop('disabled', true);
                $('#amazonIDsm').hide().find('input').prop('disabled', true);
                $('#amazonKey').hide().find('input').prop('disabled', true);
                $('#amazonRegion').hide().find('input').prop('disabled', true);
            } else if (document.getElementById("endpoint").value == "<?php echo $rgwEndpoint ?>") {
                $('#bucket').show().find('input').prop('disabled', false);
                $('#amazonID').show().find('input').prop('disabled', false);
                $('#amazonKeyrgw').show().find('input').prop('disabled', false);
                $('#bucketSm').hide().find('input').prop('disabled', true);
                $('#amazonIDsm').hide().find('input').prop('disabled', true);
                $('#amazonKey').hide().find('input').prop('disabled', true);
                $('#amazonRegion').hide().find('input').prop('disabled', true);
            } else {
                $('#bucket').hide().find('input').prop('disabled', true);
                $('#amazonID').hide().find('input').prop('disabled', true);
                $('#amazonKeyrgw').hide().find('input').prop('disabled', true);
                $('#bucketSm').show().find('input').prop('disabled', false);
                $('#amazonIDsm').show().find('input').prop('disabled', false);
                $('#amazonKey').show().find('input').prop('disabled', false);
                $('#amazonRegion').show().find('input').prop('disabled', false);
            }
        }

        <?php
        if (empty($_GET['callback'])) {
            echo "
            function process() {
                var form = document.getElementById('s3-login');
                var elements = form.elements;
                var values = [''];
                values.push(encodeURIComponent(elements[1].name) + '=' + encodeURIComponent(elements[1].value));
                form.action += '?' + values.join('');
            }";
        }
        
        ?>
    </script>

</body>

</html>