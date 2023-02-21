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

$createStatement = $pdo->prepare('INSERT INTO posts (profile,seqts,data) VALUES (?,?,?)');
$createStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$createStatement->bindValue(2, datetime_to_microtime($now), PDO::PARAM_INT);
$createStatement->bindValue(3, file_get_contents('php://input'), PDO::PARAM_STR);
try {
    if($createStatement->execute() && $createStatement->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(array(
            'seqts' => spxp_format_datetime($now)
        ));
    } else {
        http_response_code(500);
        echo 'Internal server error';
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

