<?php

if (!defined('CORRECT_ENTRY')) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

if(!isset($post_data['post']) || !is_array($post_data['post']) || 
   !isset($post_data['token']) || !is_string($post_data['token'])) {
    http_response_code(403);
    echo 'Bad post data';
    exit();
}
$post = $post_data['post'];
$token = $post_data['token'];
if($post === array_values($post)) {
    http_response_code(400);
    echo 'Bad request';
    exit();
}

$tokenStatement = $pdo->prepare('SELECT `scope` FROM `publish` WHERE `profile` = ? and `token` = ?');
$tokenStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$tokenStatement->bindValue(2, $token, PDO::PARAM_STR);
try {
    $tokenStatement->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}
$tokenRow = $tokenStatement->fetch();
$tokenStatement->closeCursor();
if(!$tokenRow) {
    http_response_code(403);
    echo 'Invalid token';
    exit();
}
$scope = $tokenRow['scope'];

if($scope == 'public') {
    // TODO verify public post
} elseif($scope == 'private') {
    // TODO verify private post
} else {
    http_response_code(403);
    echo 'Invalid scope';
    exit();
} 

$revokeTokenStatement = $pdo->prepare('DELETE `publish` WHERE `profile` = ? and `token` = ?');
$revokeTokenStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$revokeTokenStatement->bindValue(2, $token, PDO::PARAM_STR);
try {
    $tokenStatement->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

$createStatement = $pdo->prepare('INSERT INTO posts (profile,seqts,data) VALUES (?,?,?)');
$createStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$createStatement->bindValue(2, datetime_to_microtime($now), PDO::PARAM_INT);
$createStatement->bindValue(3, json_encode($post), PDO::PARAM_STR);
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
