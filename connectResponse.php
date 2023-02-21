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
   !isset($post_data['ver']) || !is_string($post_data['ver']) ||
   !isset($post_data['establishId']) || !is_string($post_data['establishId']) ||
   !isset($post_data['package']) || !is_array($post_data['package']) ) {
    http_response_code(400);
    echo 'Bad request';
    exit();
}

$type = $post_data['type'];
$ver = $post_data['ver'];
$establishId = $post_data['establishId'];
$package = $post_data['package'];

if($ver != '0.3' && $ver != '0.4') {
    http_response_code(400);
    echo 'Bad request';
    exit();
}

if($package === array_values($package)) {
    http_response_code(400);
    echo 'Bad request';
    exit();
}

if($type != 'connection_accept') {
    http_response_code(400);
    echo 'Bad request';
    exit();
}

$packageStatement = $pdo->prepare('SELECT * FROM prepared_connections WHERE profile = ? and establish_id = ?');
$packageStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$packageStatement->bindValue(2, $establishId, PDO::PARAM_STR);
try {
    $packageStatement->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}
$packageRow = $packageStatement->fetch();
$packageStatement->closeCursor();
if(!$packageRow) {
    http_response_code(404);
    echo 'Invalid establishId';
    exit();
}

if($packageRow['expires'] < datetime_to_microtime($now)) {
    http_response_code(404);
    echo 'Invalid establishId';
    exit();
}

$data = json_encode(array(
    'received' => spxp_format_datetime($now),
    'ver' => $ver,
    'establishId' => $establishId,
    'package' => $package
));

$createStatement = $pdo->prepare('INSERT INTO service_messages (profile,seqts,type,data) VALUES (?,?,2,?)');
$createStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$createStatement->bindValue(2, datetime_to_microtime($now), PDO::PARAM_INT);
$createStatement->bindValue(3, $data, PDO::PARAM_STR);
try {
    if(!($createStatement->execute() && $createStatement->rowCount() > 0)) {
        http_response_code(500);
        echo 'Internal server error';
        exit();
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

$activateKeysStatement = $pdo->prepare('INSERT IGNORE INTO profile_keys (profile, grp, rnd, enc_with_grp, enc_with_rnd, data, published) SELECT profile, grp, rnd, enc_with_grp, enc_with_rnd, data, published FROM prepared_keys WHERE profile = ? and establish_id = ?');
$activateKeysStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$activateKeysStatement->bindValue(2, $establishId, PDO::PARAM_STR);
try {
    $activateKeysStatement->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

$deletePreparedKeysStatement = $pdo->prepare('DELETE FROM prepared_keys WHERE profile = ? and establish_id = ?');
$deletePreparedKeysStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$deletePreparedKeysStatement->bindValue(2, $establishId, PDO::PARAM_STR);
try {
    $deletePreparedKeysStatement->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

$deletePreparedPackageStatement = $pdo->prepare('DELETE FROM prepared_connections WHERE profile = ? and establish_id = ?');
$deletePreparedPackageStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$deletePreparedPackageStatement->bindValue(2, $establishId, PDO::PARAM_STR);
try {
    $deletePreparedPackageStatement->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

$responsePackage = json_decode($packageRow['package']);

header('Content-Type: application/json');
echo json_encode(array(
    'type' => 'connection_finish',
    'ver' => '0.3',
    'establishId' => $establishId,
    'package' => $responsePackage,
));
