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
   !isset($post_data['establishId']) || !is_string($post_data['establishId']) ||
   !isset($post_data['expires']) || !is_string($post_data['expires']) ||
   !isset($post_data['package']) || !is_array($post_data['package']) ||
   !isset($post_data['keys']) || !is_array($post_data['keys']) ) {
    http_response_code(400);
    echo 'Bad request';
    exit();
}

$establishId = $post_data['establishId'];
$expires = $post_data['expires'];
$package = $post_data['package'];
$keys = $post_data['keys'];
$expires_datetime = spxp_parse_datetime($expires);
if(!$expires_datetime) {
    http_response_code(400);
    echo 'Bad request';
    exit();
}
if(!is_base64urlsafe($establishId)) {
    http_response_code(400);
    echo 'Bad request';
    exit();
}
if(strlen($establishId) < 6 || strlen($establishId) > 50) {
    http_response_code(400);
    echo 'Bad request';
    exit();
}
if($package === array_values($package)) {
    http_response_code(400);
    echo 'Bad request';
    exit();
}

$packageJson = json_encode($package);

$createStatement = $pdo->prepare('INSERT INTO prepared_connections (profile, establish_id, expires, published, package) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE expires=VALUES(expires), published=VALUES(published), package=VALUES(package)');
$createStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$createStatement->bindValue(2, $establishId, PDO::PARAM_STR);
$createStatement->bindValue(3, datetime_to_microtime($expires_datetime), PDO::PARAM_INT);
$createStatement->bindValue(4, datetime_to_microtime($now), PDO::PARAM_INT);
$createStatement->bindValue(5, $packageJson, PDO::PARAM_STR);
try {
    $createResult = $createStatement->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}
if(!$createResult || $createStatement->rowCount() <= 0) {
    http_response_code(400);
    echo 'Bad request';
    exit();
}

foreach($keys as $audience => $groups) {
    if(!is_array($groups)) {
        http_response_code(400);
        echo 'Invalid keys object structure';
        exit();
    }
    $groups_response = array();
    foreach($groups as $group_name => $rounds) {
        if(!is_array($rounds)) {
            http_response_code(400);
            echo 'Invalid keys object structure';
            exit();
        }
        $rounds_response = array();
        foreach($rounds as $round_name => $jwk) {
            $result = process_prepared_key($establishId, $audience, $group_name, $round_name, $jwk);
            if($result != 'ok' && $result != 'err_exists') {
                http_response_code(400);
                echo 'Invalid keys object structure';
                exit();
            }
        }
        $groups_response[$group_name] = $rounds_response;
    }
    $response[$audience] = $groups_response;
}

http_response_code(204);

function process_prepared_key($establishId, $audience, $group_name, $round_name, $jwk) {
    global $now;
    global $profile;
    global $pdo;
    if($audience != '@publish@') {
        if(!is_base64urlsafe($audience)) {
            return 'error: invalid character in group name (audience)';
        }
        if(strlen($audience) < 5 || strlen($audience) > 50) {
            return 'error: group name length must be between 5 and 50 (audience)';
        }
    }
    if(!is_base64urlsafe($group_name)) {
        return 'error: invalid character in group name (group)';
    }
    if(strlen($group_name) < 5 || strlen($group_name) > 50) {
        return 'error: group name length must be between 5 and 50 (group)';
    }
    if(!is_base64urlsafe($round_name)) {
        return 'error: invalid character in round name';
    }
    if(strlen($round_name) < 5 || strlen($round_name) > 10) {
        return 'error: round name length must be between 5 and 10';
    }
    if($audience != '@publish@') {
        $jwk_parts = explode( '.', $jwk );
        if(count($jwk_parts) != 5) {
            return 'err_invalid_jwk: expected 5 parts';
        }
        if(strlen($jwk_parts[1]) > 0) {
            return 'err_invalid_jwk: cek must be empty';
        }
        $header = json_decode(base64url_decode($jwk_parts[0]), true);
        if(!isset($header['alg']) || $header['alg'] != 'dir' || !isset($header['enc']) || $header['enc'] != 'A256GCM') {
            return 'err_invalid_jwk: expected alg=dir and enc=A256GCM';
        }
        if(!isset($header['kid'])) {
            return 'err_invalid_jwk: missing kid';
        }
        if($header['kid'] != $audience) {
            if(!str_starts_with($header['kid'], $audience.'.')) {
                return 'err_invalid_jwk: invalid kid';
            }
            $audience_round = substr($header['kid'], strlen($audience)+1);
        }
        if(!is_base64urlsafe($jwk_parts[2]) || !is_base64urlsafe($jwk_parts[3]) || !is_base64urlsafe($jwk_parts[4])) {
            return 'err_invalid_jwk: invalid base64  encoding';
        }
    }
    if(isset($audience_round)) {
        if(!is_base64urlsafe($audience_round)) {
            return 'err_invalid_jwk: invalid character in round name (audience round)';
        }
        if(strlen($audience_round) < 5 || strlen($audience_round) > 10) {
            return 'err_invalid_jwk: round name length must be between 5 and 10 (audience round)';
        }
    } else {
        $audience_round = null;
    }
    $createStatement = $pdo->prepare('INSERT INTO prepared_keys (profile, establish_id, grp, rnd, enc_with_grp, enc_with_rnd, data, published) VALUES (?,?,?,?,?,?,?,?)');
    $createStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
    $createStatement->bindValue(2, $establishId, PDO::PARAM_STR);
    $createStatement->bindValue(3, $group_name, PDO::PARAM_STR);
    $createStatement->bindValue(4, $round_name, PDO::PARAM_STR);
    $createStatement->bindValue(5, $audience, PDO::PARAM_STR);
    $createStatement->bindValue(6, $audience_round, PDO::PARAM_STR);
    $createStatement->bindValue(7, $jwk, PDO::PARAM_STR);
    $createStatement->bindValue(8, datetime_to_microtime($now), PDO::PARAM_INT);
    try {
        $createStatement->execute();
    } catch (PDOException $e) {
        if( $e->getCode() == 23000 || $e->getCode() == 1062 ) {
            return 'err_exists';
        } else {
            return 'err_retry';
        }
    }
    return 'ok';
}
