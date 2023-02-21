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

if(!isset($_FILES['source']) || !isset($_FILES['source']['error']) || $_FILES['source']['error'] != UPLOAD_ERR_OK) {
    http_response_code(400);
    echo 'Invalid file upload';
    exit();
}

$mediaid = generateRandomKeyIdLong().generateRandomKeyIdLong();
$createStatement = $pdo->prepare('INSERT INTO media (profile,media_id,published) VALUES (?,?,?)');
$createStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
$createStatement->bindValue(2, $mediaid, PDO::PARAM_STR);
$createStatement->bindValue(3, datetime_to_microtime($now), PDO::PARAM_INT);
try {
    if($createStatement->execute() && $createStatement->rowCount() > 0) {
        move_uploaded_file($_FILES['source']['tmp_name'], dirname(__FILE__).'/media/'.$mediaid);
        header('Content-Type: application/json');
        echo json_encode(array(
            'mediaId' => $mediaid,
            'uri' => $baseUri.'/media/'.$mediaid
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
