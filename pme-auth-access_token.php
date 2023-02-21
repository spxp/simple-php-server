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
   !isset($post_data['device_token']) || !is_string($post_data['device_token']) ||
   !isset($post_data['timestamp']) || !is_string($post_data['timestamp']) ) {
    http_response_code(403);
    echo 'Bad post data';
    exit();
}

$device_token = $post_data['device_token'];
$timestamp = $post_data['timestamp'];
$request_timestamp = spxp_parse_datetime($timestamp);
if(!$request_timestamp) {
    http_response_code(403);
    echo 'Invalid timestamp';
    exit();
}

$request_age = $now->getTimestamp() - $request_timestamp->getTimestamp();
if($request_age < -1*60 || $request_age > 5*60) {
    http_response_code(403);
    echo 'Request timeout elapsed';
    exit();
}

$deviceStatement = $pdo->prepare('SELECT * FROM devices WHERE device_token = ?');
$deviceStatement->bindValue(1, $device_token, PDO::PARAM_STR);
try {
    $deviceStatement->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}
$device = $deviceStatement->fetch();
$deviceStatement->closeCursor();
if(!$device) {
    http_response_code(403);
    echo 'Invalid device_token';
    exit();
}

$profileStatement = $pdo->prepare('SELECT * FROM profiles WHERE name = ?');
$profileStatement->bindValue(1, $device['profile'], PDO::PARAM_STR);
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
    http_response_code(403);
    echo 'Invalid device_token';
    exit();
}

// check signature
if(!isset($post_data['signature']) || !is_array($post_data['signature']) ||
   !isset($post_data['signature']['key']) || !is_string($post_data['signature']['key']) ||
   !isset($post_data['signature']['sig']) || !is_string($post_data['signature']['sig']) ) {
    http_response_code(403);
    echo 'Invalid signature';
    exit();
}
$signature_key = $post_data['signature']['key'];
$signature_sig = $post_data['signature']['sig'];
if($signature_key !== $profile['pk_id']) {
    http_response_code(403);
    echo 'Invalid signature';
    exit();
}
unset($post_data['signature']);
ksort($post_data);
$signStr = json_encode($post_data, JSON_UNESCAPED_SLASHES);
$pub = base64url_decode($profile['pk_x']);
$sig = base64url_decode($signature_sig);
if(!sodium_crypto_sign_verify_detached($sig, $signStr, $pub)) {
    http_response_code(403);
    echo 'Invalid signature';
    exit();
}

$jwt = array();
$jwt['sub'] = $profile['name'];
$exp = fromTimestampUtc($now->getTimestamp() + 3600);
$jwt['exp'] = spxp_format_datetime($exp);
$jwtStr = json_encode($jwt);
$signature = sodium_crypto_sign_detached($jwtStr, sodium_hex2bin($jwt_secret));
$access_token = base64url_encode($jwtStr).'.'.base64url_encode($signature);

$response = array();
$response['token_type'] = 'access_token';
$response['access_token'] = $access_token;
$response['expires_in'] = 3600;
header('Content-Type: application/json');
echo json_encode($response);
