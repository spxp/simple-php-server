<?php

if (!defined('CORRECT_ENTRY')) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
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

if($type == 'connection_discovery') {
    header('Content-Type: application/json');
    echo json_encode(array(
        'type' => 'connection_discovery',
        'ver' => '0.3'
    ));
} elseif($type == 'connection_request') {
    if(!isset($post_data['msg']) || !is_array($post_data['msg'])) {
         http_response_code(400);
         echo 'Bad request';
         exit();
    }
    $msg = $post_data['msg'];
    if($msg === array_values($msg)) {
        http_response_code(400);
        echo 'Bad request';
        exit();
    }
    $data = json_encode(array(
        'received' => spxp_format_datetime($now),
        'ver' => $ver,
        'msg' => $msg
    ));

    $createStatement = $pdo->prepare('INSERT INTO service_messages (profile,seqts,type,data) VALUES (?,?,1,?)');
    $createStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
    $createStatement->bindValue(2, datetime_to_microtime($now), PDO::PARAM_INT);
    $createStatement->bindValue(3, $data, PDO::PARAM_STR);
    try {
        if($createStatement->execute() && $createStatement->rowCount() > 0) {
            http_response_code(204);
        } else {
            http_response_code(500);
            echo 'Internal server error';
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'Internal server error';
        exit();
    }
} else {
    http_response_code(400);
    echo 'Bad request';
}