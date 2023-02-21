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
   !isset($post_data['profile_uri']) || !is_string($post_data['profile_uri']) ||
   !isset($post_data['device_id']) || !is_string($post_data['device_id']) ||
   !isset($post_data['timestamp']) || !is_string($post_data['timestamp']) ) {
    http_response_code(403);
    echo 'Bad post data';
    exit();
}

$profile_uri = $post_data['profile_uri'];
$device_id = $post_data['device_id'];
$timestamp = $post_data['timestamp'];
if(!str_starts_with($profile_uri, $baseUri.'/')) {
    http_response_code(403);
    echo 'Invalid profile_uri';
    exit();
}
$profile_name = substr($profile_uri, strlen($baseUri)+1 );
if(strlen($profile_name) > 50) {
    http_response_code(403);
    echo 'Invalid profile_uri';
    exit();
}
if(strlen($device_id) > 50) {
    http_response_code(403);
    echo 'device_id too long';
    exit();
}
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

$profileStatement = $pdo->prepare('SELECT * FROM profiles WHERE name = ?');
$profileStatement->bindValue(1, $profile_name, PDO::PARAM_STR);
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
    echo 'Invalid profile_uri';
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

$token = generateRandomKeyIdLong().generateRandomKeyIdLong();
$createStatement = $pdo->prepare('INSERT INTO devices (profile,device_id,device_token,registered) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE device_token=VALUES(device_token),registered=VALUES(registered)');
$createStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$createStatement->bindValue(2, $device_id, PDO::PARAM_STR);
$createStatement->bindValue(3, $token, PDO::PARAM_STR);
$createStatement->bindValue(4, datetime_to_microtime($now), PDO::PARAM_INT);
try {
    $createStatement->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

$registerResponse = array();
$registerResponse['token_type'] = 'device_token';
$registerResponse['device_token'] = $token;
header('Content-Type: application/json');
echo json_encode($registerResponse);
