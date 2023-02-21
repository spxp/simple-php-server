<?php

if (!defined('CORRECT_ENTRY')) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo 'Method not allowed';
    exit();
}

$establishId = $pathParts[3];

$deletePreparedKeysStatement = $pdo->prepare('DELETE FROM `prepared_keys` WHERE `profile` = ? and `establish_id` = ?');
$deletePreparedKeysStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$deletePreparedKeysStatement->bindValue(2, $establishId , PDO::PARAM_STR);
try {
    $deletePreparedKeysStatement->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

$deleteSPreparedConnectionStatement = $pdo->prepare('DELETE FROM `prepared_connections` WHERE `profile` = ? and `establish_id` = ?');
$deleteSPreparedConnectionStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$deleteSPreparedConnectionStatement->bindValue(2, $establishId , PDO::PARAM_STR);
try {
    if($deleteSPreparedConnectionStatement->execute() && $deleteSPreparedConnectionStatement->rowCount() > 0) {
        http_response_code(204);
    } else {
        http_response_code(404);
        echo 'Prepared connection not found';
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}
