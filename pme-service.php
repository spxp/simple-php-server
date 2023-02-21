<?php

if (!defined('CORRECT_ENTRY')) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

if(count($pathParts) == 3 && $pathParts[2] == 'info') {
    require_once('pme-service-info.php');
} elseif(count($pathParts) == 3 && $pathParts[2] == 'messages') {
    require_once('pme-service-messages-list.php');
} elseif(count($pathParts) == 4 && $pathParts[2] == 'messages') {
    require_once('pme-service-messages-delete.php');
} else {
    http_response_code(404);
    echo 'Object not found';
}
