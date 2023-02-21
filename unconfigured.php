<?php

if (!defined('CORRECT_ENTRY')) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

$in_root = !isset($_GET['q']) || $_GET['q'] === '' || count(explode('/', $_GET['q'])) == 1;

?><html>
<body>
    <h1>Simple SPXP Server</h1>
    This server has not yet been configured.<br/>
<?php if($in_root) { ?>
    Please run <a href="setup.php">setup.php</a> to configure this service.
<?php } else { ?>
    Please run setup.php in the root of this server to configure this service.
<?php } ?>
</body>
</html>