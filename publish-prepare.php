<?php

if (!defined('CORRECT_ENTRY')) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

if(!isset($post_data['timestamp']) || !is_string($post_data['timestamp']) ) {
    http_response_code(403);
    echo 'Bad post data';
    exit();
}
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

if(!isset($post_data['signature']) || !is_array($post_data['signature']) ||
   !isset($post_data['signature']['key']) || !is_string($post_data['signature']['key']) ||
   !isset($post_data['signature']['sig']) || !is_string($post_data['signature']['sig']) ) {
    http_response_code(403);
    echo 'Invalid signature';
    exit();
}
$signature_key = $post_data['signature']['key'];
$signature_sig = $post_data['signature']['sig'];
if(!is_base64urlsafe($signature_key) || strlen($signature_key) < 5 || strlen($signature_key) > 50) {
    http_response_code(403);
    echo 'Invalid signature';
    exit();
}

$publishKeyStatement = $pdo->prepare('SELECT rnd, data FROM profile_keys WHERE profile=? and grp=? and enc_with_grp=? and enc_with_rnd is null');
$publishKeyStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$publishKeyStatement->bindValue(2, $signature_key, PDO::PARAM_STR);
$publishKeyStatement->bindValue(3, '@publish@', PDO::PARAM_STR);
try {
    $publishKeyStatement->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}
$publishKey = $publishKeyStatement->fetch();
$publishKeyStatement->closeCursor();
if(!$publishKey) {
    http_response_code(403);
    echo 'Invalid signature';
    exit();
}
$publishJwk = json_decode($publishKey['data'], true);
if(!isset($publishJwk) || !is_array($publishJwk) || !isset($publishJwk['x']) || !is_string($publishJwk['x'])) {
    http_response_code(403);
    echo 'Invalid signature';
    exit();
}

unset($post_data['signature']);
ksort($post_data);
$signStr = json_encode($post_data, JSON_UNESCAPED_SLASHES);
$pub = base64url_decode($publishJwk['x']);
$sig = base64url_decode($signature_sig);
if(!sodium_crypto_sign_verify_detached($sig, $signStr, $pub)) {
    http_response_code(403);
    echo 'Invalid signature';
    exit();
}

$token = generateRandomKeyIdLong();
$createStatement = $pdo->prepare('INSERT INTO `publish` (`profile`,`key_id`,`last`,`token`,`scope`) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE `last`=VALUES(`last`), `token`=VALUES(`token`), `scope`=VALUES(`scope`)');
$createStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$createStatement->bindValue(2, $signature_key, PDO::PARAM_STR);
$createStatement->bindValue(3, datetime_to_microtime($now), PDO::PARAM_INT);
$createStatement->bindValue(4, $token, PDO::PARAM_STR);
$createStatement->bindValue(5, $publishKey['rnd'], PDO::PARAM_STR);
try {
    $createStatement->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

$response = array();
$response['token'] = $token;
if(isset($post_data['group']) && is_string($post_data['group']) ) {
    $latestRoundStatement = $pdo->prepare('SELECT `rnd` FROM `profile_keys` WHERE `profile`=? and `grp`=? ORDER BY `published` desc LIMIT 1');
    $latestRoundStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
    $latestRoundStatement->bindValue(2, $post_data['group'], PDO::PARAM_STR);
    try {
        $latestRoundStatement->execute();
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'Internal server error';
        exit();
    }
    $latestRound = $latestRoundStatement->fetch();
    $latestRoundStatement->closeCursor();
    if($latestRound) {
        $response['groupRound'] = $latestRound['rnd'];
    }
}
header('Content-Type: application/json');
echo json_encode($response);
