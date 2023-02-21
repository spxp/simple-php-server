<?php

if (!defined('CORRECT_ENTRY')) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo 'Method not allowed';
    exit();
}

header('Content-Type: application/json');

echo json_encode(array(
    'server' => array(
        'vendor' => 'spxp.org',
        'product' => 'Simple SPXP Server',
        'version' => '0.1'
    ),
    'endpoints' => array(
        'friendsEndpoint' => $profile['name'].'/friends',
        'postsEndpoint' => $profile['name'].'/posts',
        'keysEndpoint' => $profile['name'].'/keys',
        'connectEndpoint' => $profile['name'].'/connect',
        'connectResponseEndpoint' => $profile['name'].'/connectResponse',
        'publishEndpoint' => $profile['name'].'/publish',
    ),
    'limits' => array(
        'maxMediaSize' => 10485760
    )
));
