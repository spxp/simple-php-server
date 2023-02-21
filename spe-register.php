<?php

if (!defined('CORRECT_ENTRY')) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

if(!isset($_GET['return_scheme']) && !isset($_POST['return_scheme'])) {
    http_response_code(400);
    echo 'Missing return_scheme';
    exit();
}
$returnScheme = isset($_POST['return_scheme']) ? $_POST['return_scheme'] : $_GET['return_scheme'];
if(!preg_match('/^[a-z]+$/', $returnScheme)) {
    http_response_code(400);
    echo 'Bad return_scheme';
    exit();
}

if(isset($_POST['slug'])) {
    $slug = $_POST['slug'];
    if(strlen($slug) > 50) {
        $slugError = 'Max 50 characters allowed';
    } elseif(preg_match('/[^a-z0-9\._]/', $slug)) {
        $slugError = 'Invalid characters in slug. Only a-z, 0-9, . and _ allowed';
    } elseif(isSlugUsed($slug)) {
        $slugError = 'Already used. Please chose another one.';
    } else {
        $token = generateRandomKeyIdLong();
        $createStatement = $pdo->prepare('INSERT INTO profiles (name,state,registered,setup_token) VALUES (?,?,?,?)');
        $createStatement->bindValue(1, $slug, PDO::PARAM_STR);
        $createStatement->bindValue(2, 0, PDO::PARAM_INT);
        $createStatement->bindValue(3, datetime_to_microtime($now), PDO::PARAM_INT);
        $createStatement->bindValue(4, $token, PDO::PARAM_STR);
        try {
            $createStatement->execute();
        } catch (PDOException $e) {
            http_response_code(500);
            echo 'Internal server error';
            exit();
        }
        $returnUri = $returnScheme.':'.$token;
        echo '<html><body><script type="text/javascript">window.onload=function(){document.getElementById("continue").click();};</script><a id="continue" href="'.$returnUri.'">Continue</a></body></html>';
        exit();
    }
} else {
    if(isset($_GET['name'])) {
        $slug = strtolower($_GET['name']);
        $slug = preg_replace('/[^a-z0-9]/', '.', $slug);
        $slug = preg_replace('/\.\.+/', '.', $slug);
    } else {
        $slug = '';
    }
}

function isSlugUsed($slug) {
    global $pdo;
    $profileStatement = $pdo->prepare('SELECT name FROM profiles WHERE name = ?');
    $profileStatement->bindValue(1, $slug, PDO::PARAM_STR);
    if(!$profileStatement->execute()) {
        http_response_code(500);
        echo 'Internal server error';
        exit();
    }
    $profile = $profileStatement->fetch();
    $profileStatement->closeCursor();
    return $profile !== false;
}

?><!DOCTYPE html>
<html>
  <head>
    <title>Register your profile</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, minimum-scale=1, maximum-scale=1" />
  </head>
  <body>
    <form id="registerform" action="<?php echo $baseUri; ?>/.spxp-spe/register" method="post">
      <input type="hidden" name="return_scheme" value="<?php echo $returnScheme; ?>" />
      <p>Chose your profile URI:</p>
      <label id="slug" for="slug"><?php echo $baseUri; ?>/</label>
      <input type="text" name="slug" id="slug" value="<?php echo $slug; ?>" placeholder="Slug" required/>
      <br/><?php
if(isset($slugError)) {
    echo '<span style=\'color:red;\'>'.$slugError.'</span><br/>';
}
?>
      <button type="submit">Register</button>
    </form>
  </body>
</html>