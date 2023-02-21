<?php

if (!defined('CORRECT_ENTRY')) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo ';ethod not allowed';
    exit();
}

$poststr = file_get_contents('php://input');
$post_data = json_decode($poststr, true);
if(!isset($post_data) || !is_array($post_data) ||
   !isset($post_data['type']) || !is_string($post_data['type']) ||
   !isset($post_data['ver']) || !is_string($post_data['ver']) ) {
    http_response_code(400);
    echo 'Bad request';
    exit();
}

$type = $post_data['type'];
$ver = $post_data['ver'];

if($ver != '0.3' && $ver != '0.4') {
    http_response_code(400);
    echo 'Bad request';
    exit();
}

if($type == 'prepare_post') {
    require_once('publish-prepare.php');
} elseif($type == 'post') {
    require_once('publish-post.php');
} else {
    http_response_code(400);
    echo 'Bad request';
}
