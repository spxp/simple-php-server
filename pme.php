<?php

if (!defined('CORRECT_ENTRY')) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

if(count($pathParts) < 2) {
    http_response_code(404);
    echo 'Object not found';
    exit();
}

if($pathParts[1] == 'auth') {
    require_once('pme-auth.php');
    exit();
}

$authorization = empty($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : $_SERVER['HTTP_AUTHORIZATION'];
if(empty($authorization) || !str_starts_with($authorization, 'Bearer ')) {
    http_response_code(401);
    'Authorization header missing or invalid scheme';
    exit();
}
$access_token = substr($authorization, 7);
$jwtParts = explode( '.', $access_token );
if(count($jwtParts) != 2) {
    http_response_code(401);
    echo 'Invalid access_token';
    exit();
}
$jwtData = base64url_decode($jwtParts[0]);
$jwtSig = base64url_decode($jwtParts[1]);
if(!sodium_crypto_sign_verify_detached($jwtSig, $jwtData , sodium_hex2bin($jwt_public))) {
    http_response_code(401);
    echo 'Invalid access_token';
    exit();
}
$jwt = json_decode($jwtData, true);
$exp = spxp_parse_datetime($jwt['exp']);
if(!$exp || $exp->getTimestamp() < $now->getTimestamp()) {
    http_response_code(401);
    echo 'Invalid access_token';
    exit();
}

$profileStatement = $pdo->prepare('SELECT * FROM profiles WHERE name = ?');
$profileStatement->bindValue(1, $jwt['sub'], PDO::PARAM_STR);
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
    http_response_code(401);
    echo 'Invalid access_token';
    exit();
}

if($pathParts[1] == 'profile') {
    require_once('pme-profile.php');
} elseif($pathParts[1] == 'service') {
    require_once('pme-service.php');
} elseif($pathParts[1] == 'keys') {
    require_once('pme-keys.php');
} elseif($pathParts[1] == 'posts') {
    if(count($pathParts) == 2) {
        require_once('pme-posts-add.php');
    } elseif(count($pathParts) == 3) {
        require_once('pme-posts-delete.php');
    } else {
        http_response_code(404);
        echo 'Object not found';
    }
} elseif($pathParts[1] == 'media') {
    if(count($pathParts) == 2) {
        require_once('pme-media-add.php');
    } elseif(count($pathParts) == 3) {
        require_once('pme-media-delete.php');
    } else {
        http_response_code(404);
        echo 'Object not found';
    }
} elseif($pathParts[1] == 'connect' && count($pathParts) >= 3 && $pathParts[2] == 'packages') {
    if(count($pathParts) == 3) {
        require_once('pme-connect-add.php');
    } elseif(count($pathParts) == 4) {
        require_once('pme-connect-delete.php');
    } else {
        http_response_code(404);
        echo 'Object not found';
    }
} else {
    http_response_code(404);
    echo 'Object not found';
}
