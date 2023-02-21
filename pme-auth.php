<?php

if (!defined('CORRECT_ENTRY')) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

if(count($pathParts) != 3) {
    http_response_code(404);
    echo 'Object not found';
    exit();
}

if($pathParts[2] == 'device') {
    require_once('pme-auth-device.php');
} elseif($pathParts[2] == 'access_token') {
    require_once('pme-auth-access_token.php');
} else {
    http_response_code(404);
    echo 'Object not found';
}
