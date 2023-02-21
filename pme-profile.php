<?php

if (!defined('CORRECT_ENTRY')) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo 'Method not allowed';
    exit();
}

if(count($pathParts) != 3 || ($pathParts[2] != 'root' && $pathParts[2] != 'friends')) {
    http_response_code(404);
    echo 'Object not found';
    exit();
}

$tablename = $pathParts[2] == 'root' ? 'roots' : 'friends';

$createStatement = $pdo->prepare('INSERT INTO '.$tablename.' (name,data,published) VALUES (?,?,?) ON DUPLICATE KEY UPDATE data=VALUES(data),published=VALUES(published)');
$createStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$createStatement->bindValue(2, file_get_contents('php://input'), PDO::PARAM_STR);
$createStatement->bindValue(3, datetime_to_microtime($now), PDO::PARAM_INT);
try {
    $createStatement->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

http_response_code(204);
