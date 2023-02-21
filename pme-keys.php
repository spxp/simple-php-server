<?php

if (!defined('CORRECT_ENTRY')) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

if(count($pathParts) == 2) {
    require_once('pme-keys-add.php');
} elseif(count($pathParts) > 2 && count($pathParts) < 6) {
    require_once('pme-keys-delete.php');
} else {
    http_response_code(404);
    echo 'Object not found';
}
