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

$seqts = spxp_parse_datetime($pathParts[3]);
if(!$seqts) {
    http_response_code(400);
    echo 'Invalid seqts';
    exit();
}

$deleteStatement = $pdo->prepare('DELETE FROM service_messages WHERE profile=? and seqts=?');
$deleteStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$deleteStatement->bindValue(2, datetime_to_microtime($seqts), PDO::PARAM_INT);
try {
    if($deleteStatement->execute() && $deleteStatement->rowCount() > 0) {
        http_response_code(204);
    } else {
        http_response_code(404);
        echo 'Message not found';
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}
