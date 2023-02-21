<?php

define('CORRECT_ENTRY', true);

require_once('utils.php');
$now = nowUtc();

if(!file_exists('config.php')) {
    require_once('unconfigured.php');
    exit();
}

require_once('config.php');
try {
    $pdo = new PDO('mysql:host='.$db_hostname.';dbname='.$db_database.';port='.$db_port.';charset=utf8mb4', $db_username, $db_password);
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

if(!isset($_GET['q']) || $_GET['q'] === '') {
    require_once('root.php');
    exit();
}

$pathParts = explode( '/', $_GET['q'] );

if($pathParts[0] == '.spxp-spe') {
    require_once('spe.php');
    exit();
}
if($pathParts[0] == '.spxp-pme') {
    require_once('pme.php');
    exit();
}

if(count($pathParts) > 2) {
    http_response_code(404);
    echo 'Object not found';
    exit();
}

$profileStatement = $pdo->prepare('SELECT * FROM profiles WHERE name = ?');
$profileStatement->bindValue(1, $pathParts[0], PDO::PARAM_STR);
try {
    $profileStatement->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}
$profile = $profileStatement->fetch();
$profileStatement->closeCursor();

if(!$profile || $profile['state'] != 1) {
    http_response_code(404);
    echo 'Profile not found';
    exit();
}

if(count($pathParts) == 1) {
    $rootStatement = $pdo->prepare('SELECT * FROM roots WHERE name = ?');
    $rootStatement->bindValue(1, $pathParts[0], PDO::PARAM_STR);
    try {
        $rootStatement->execute();
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'Internal server error';
        exit();
    }
    $root = $rootStatement->fetch();
    $rootStatement->closeCursor();
    if($root) {
        header('Content-Type: application/json');
        echo $root['data'];
    } else {
        http_response_code(404);
        echo 'Profile not found';
    }
} elseif(count($pathParts) == 2 && $pathParts[1] === 'friends') {
    $friendsStatement = $pdo->prepare('SELECT * FROM friends WHERE name = ?');
    $friendsStatement->bindValue(1, $pathParts[0], PDO::PARAM_STR);
    try {
        $friendsStatement->execute();
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'Internal server error';
        exit();
    }
    $friends = $friendsStatement->fetch();
    $friendsStatement->closeCursor();
    if($friends) {
        header('Content-Type: application/json');
        echo $friends['data'];
    } else {
        http_response_code(404);
        echo 'Profile not found';
    }
} elseif(count($pathParts) == 2 && $pathParts[1] === 'posts') {
    $before = isset($_GET['before']) ? spxp_parse_datetime($_GET['before']) : null;
    $after = isset($_GET['after']) ? spxp_parse_datetime($_GET['after']) : null;
    $max = 50;
    if(isset($_GET['max']) && is_numeric($_GET['max'])) {
      $max = intval($_GET['max']);
      if($max < 1)
        $max = 1;
      if($max > 100)
        $max = 100;
    }
    if( (isset($before) && !$before) || (isset($after) && !$after) ) {
        http_response_code(400);
        echo 'Bad request';
        exit();
    } 
    if(isset($before) && isset($after) && datetime_to_microtime($before) < datetime_to_microtime($after)) {
        http_response_code(400);
        echo 'Bad request';
        exit();
    }
    $postsSql = 'SELECT seqts, data FROM posts WHERE profile=?';
    if(isset($before)) {
        $postsSql .= ' AND seqts < ?';
    }
    if(isset($after)) {
        $postsSql .= ' AND seqts > ?';
    }
    $postsSql .= ' ORDER BY seqts DESC LIMIT ?';
    $postsStatement = $pdo->prepare($postsSql);
    $nextValuePos = 1;
    $postsStatement->bindValue($nextValuePos++, $pathParts[0], PDO::PARAM_STR);
    if(isset($before)) {
        $postsStatement->bindValue($nextValuePos++, datetime_to_microtime($before), PDO::PARAM_INT);
    }
    if(isset($after)) {
        $postsStatement->bindValue($nextValuePos++, datetime_to_microtime($after), PDO::PARAM_INT);
    }
    $postsStatement->bindValue($nextValuePos++, $max+1, PDO::PARAM_INT);
    try {
        $postsStatement->execute();
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'Internal server error';
        exit();
    }
    $postsResponse = array(
        'data' => array(),
        'more' => false
    );
    $postsCount = 0;
    while($post = $postsStatement->fetch()) {
        if($postsCount >= $max) {
            $postsResponse['more'] = true;
            break;
        }
        $elem = json_decode($post['data'], true);
        $seqts = microtime_to_datetime($post['seqts']);
        if(isset($elem)) {
            $elem['seqts'] = spxp_format_datetime($seqts);
            $postsResponse['data'][] = $elem;
        }
        $postsCount++;
    }
    $postsStatement->closeCursor();
    header('Content-Type: application/json');
    echo json_encode($postsResponse);
} elseif(count($pathParts) == 2 && $pathParts[1] === 'keys') {
    require_once('keys.php');
} elseif(count($pathParts) == 2 && $pathParts[1] === 'connect') {
    require_once('connect.php');
} elseif(count($pathParts) == 2 && $pathParts[1] === 'connectResponse') {
    require_once('connectResponse.php');
} elseif(count($pathParts) == 2 && $pathParts[1] === 'publish') {
    require_once('publish.php');
} else {
    http_response_code(404);
    echo 'Object not found';
}
