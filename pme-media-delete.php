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

$mediaid = $pathParts[2];

$deleteStatement = $pdo->prepare('DELETE FROM media WHERE profile=? and media_id=?');
$deleteStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$deleteStatement->bindValue(2, $mediaid, PDO::PARAM_STR);
try {
    if($deleteStatement->execute() && $deleteStatement->rowCount() > 0) {
        if(file_exists(dirname(__FILE__).'/media/'.$mediaid)) {
            unlink(dirname(__FILE__).'/media/'.$mediaid);
        }
        http_response_code(204);
    } else {
        http_response_code(404);
        echo 'Media not found';
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}
