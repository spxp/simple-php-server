<?php

if (!defined('CORRECT_ENTRY')) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

if(count($pathParts) != 2) {
    http_response_code(404);
    echo 'Object not found';
    exit();
}

if($pathParts[1] == 'register') {
    require_once('spe-register.php');
} elseif($pathParts[1] == 'bind') {
    require_once('spe-bind.php');
} else {
    http_response_code(404);
    echo 'Object not found';
}
