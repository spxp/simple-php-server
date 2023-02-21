<?php

if (!defined('CORRECT_ENTRY')) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

$post_data = json_decode(file_get_contents('php://input'), true);
if(!isset($post_data) || !is_array($post_data) || !isset($post_data['token']) || !is_string($post_data['token']) ||
   !isset($post_data['publicKey']) || !is_array($post_data['publicKey']) || !isset($post_data['publicKey']['kid']) ||
   !isset($post_data['publicKey']['kty']) || !isset($post_data['publicKey']['crv']) || !isset($post_data['publicKey']['x']) ||
   $post_data['publicKey']['kty'] !== 'OKP' || $post_data['publicKey']['crv'] !== 'Ed25519' ||
   !is_string($post_data['publicKey']['kid']) || !is_string($post_data['publicKey']['x']) ) {
    http_response_code(400);
    echo 'Bad post data';
    exit();
}

$token = $post_data['token'];
$publicKey_kid = $post_data['publicKey']['kid'];
$publicKey_x = $post_data['publicKey']['x'];
if(strlen($publicKey_kid) > 50) {
    http_response_code(400);
    echo 'public key id too long';
    exit();
}
if(strlen($publicKey_x) > 44) {
    http_response_code(400);
    echo 'Invalid public key';
    exit();
}

$profileStatement = $pdo->prepare('SELECT * FROM profiles WHERE setup_token = ?');
$profileStatement->bindValue(1, $token, PDO::PARAM_STR);
try {
    $profileStatement->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}
$profile = $profileStatement->fetch();
$profileStatement->closeCursor();
if(!$profile) {
    http_response_code(400);
    echo 'Invalid token';
    exit();
}

$bindStatement = $pdo->prepare('UPDATE profiles SET pk_id=?, pk_x=?, state=1, setup_token=null WHERE name=?');
$bindStatement->bindValue(1, $publicKey_kid, PDO::PARAM_STR);
$bindStatement->bindValue(2, $publicKey_x, PDO::PARAM_STR);
$bindStatement->bindValue(3, $profile['name'], PDO::PARAM_STR);
try {
    $bindStatement->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}
$bindResponse = array();
$bindResponse['profileUri'] = $baseUri.'/'.$profile['name'];
header('Content-Type: application/json');
echo json_encode($bindResponse);
