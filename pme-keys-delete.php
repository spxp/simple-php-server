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
if(count($pathParts) == 3) {
    $audience = $pathParts[2];
    $deleteStatement = $pdo->prepare('DELETE FROM profile_keys WHERE profile=? and enc_with_grp=?');
    $deleteStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
    $deleteStatement->bindValue(2, $audience, PDO::PARAM_STR);
    try {
        if($deleteStatement->execute() && $deleteStatement->rowCount() > 0) {
            http_response_code(204);
        } else {
            http_response_code(404);
            echo 'Keys not found';
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'Internal server error';
        exit();
    }
} elseif(count($pathParts) == 4) {
    $audience = $pathParts[2];
    $group = $pathParts[3];
    $deleteStatement = $pdo->prepare('DELETE FROM profile_keys WHERE profile=? and enc_with_grp=? and grp=?');
    $deleteStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
    $deleteStatement->bindValue(2, $audience, PDO::PARAM_STR);
    $deleteStatement->bindValue(3, $group, PDO::PARAM_STR);
    try {
        if($deleteStatement->execute() && $deleteStatement->rowCount() > 0) {
            http_response_code(204);
        } else {
            http_response_code(404);
            echo 'Keys not found';
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'Internal server error';
        exit();
    }
} elseif(count($pathParts) == 5) {
    $audience = $pathParts[2];
    $group = $pathParts[3];
    $round = $pathParts[4];
    $deleteStatement = $pdo->prepare('DELETE FROM profile_keys WHERE profile=? and enc_with_grp=? and grp=? and rnd=?');
    $deleteStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
    $deleteStatement->bindValue(2, $audience, PDO::PARAM_STR);
    $deleteStatement->bindValue(3, $group, PDO::PARAM_STR);
    $deleteStatement->bindValue(4, $round, PDO::PARAM_STR);
    try {
        if($deleteStatement->execute() && $deleteStatement->rowCount() > 0) {
            http_response_code(204);
        } else {
            http_response_code(404);
            echo 'Keys not found';
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'Internal server error';
        exit();
    }
} else {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}
