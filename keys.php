<?php

if (!defined('CORRECT_ENTRY')) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo 'Method not allowed';
    exit();
}

if(!isset($_GET['reader'])) {
    http_response_code(400);
    echo 'Missing reader';
    exit();
}
$reader = explode( ',', $_GET['reader'] );
if(count($reader) == 0) {
    http_response_code(400);
    echo 'Missing reader';
    exit();
}
if(isset($_GET['request'])) {
    $requested = explode( ',', $_GET['request'] );
}

$collectedKeys = array();
if(!isset($requested) || count($requested) == 0) {
    foreach($reader as $readerKey) {
        $groupResult = enumerate_latest_rounds_iterative($readerKey);
        if($groupResult) {
            $collectedKeys = array_merge($collectedKeys, $groupResult);
        }
    }
} else {
    foreach($requested as $requestedKey) {
        $requestedKeyParts = explode('.', $requestedKey);
        if(count($requestedKeyParts) != 2 || !is_base64urlsafe($requestedKeyParts[0]) || !is_base64urlsafe($requestedKeyParts[1])) {
            continue;
        }
        $requestedKeyGroup = $requestedKeyParts[0];
        $requestedKeyRound = $requestedKeyParts[1];
        foreach($reader as $readerKey) {
            $groupResult = find_key_path_iterative($readerKey, $requestedKeyGroup, $requestedKeyRound);
            if($groupResult) {
                $collectedKeys = array_merge($collectedKeys, $groupResult['keys']);
                continue;
            }
        }
    }
}

$response = array();
foreach($collectedKeys as $key) {
    $audience = $key['enc_with_grp'];
    $group = $key['grp'];
    $round = $key['rnd'];
    $data = $key['data'];
    if(!isset($response[$audience])) {
        $response[$audience] = array();
    }
    if(!isset($response[$audience][$group])) {
        $response[$audience][$group] = array();
    }
    $response[$audience][$group][$round] = $data;
}
header('Content-Type: application/json');
echo json_encode($response, JSON_FORCE_OBJECT);

function find_key_path_iterative($knownKeyGroup, $requestedKeyGroup, $requestedKeyRound) {
    $allAccessibleGroups = get_reachable_groups($knownKeyGroup);
    if(in_array($requestedKeyGroup, $allAccessibleGroups)) {
        $key = get_single_key($requestedKeyGroup, $requestedKeyRound, $knownKeyGroup);
        if($key) {
            return array(
                'keys' => array($key),
                'required_rnd' => $key['enc_with_rnd']
            );
        }
    }
    foreach($allAccessibleGroups as $accessibleGroup) {
        $groupResult = find_key_path_iterative($accessibleGroup, $requestedKeyGroup, $requestedKeyRound);
        if($groupResult) {
            $key = get_single_key($accessibleGroup, $groupResult['required_rnd'], $knownKeyGroup);
            if($key) {
                $keys = $groupResult['keys'];
                $keys[] = $key;
                return array(
                    'keys' => $keys,
                    'required_rnd' => $key['enc_with_rnd']
                );
            }
        }
    }
    return false;
}

function enumerate_latest_rounds_iterative($knownKeyGroup) {
    $allAccessibleGroups = get_reachable_groups($knownKeyGroup);
    if(count($allAccessibleGroups) == 0) {
        return false;
    }
    $result = array();
    foreach($allAccessibleGroups as $accessibleGroup) {
        $groupResult = enumerate_latest_rounds_iterative($accessibleGroup);
        if(!$groupResult) {
            $key = get_latest_key($accessibleGroup, $knownKeyGroup);
            $result[] = $key;
        } else {
            foreach($groupResult as $encKey) {
                if($encKey['enc_with_grp'] == $accessibleGroup) {
                    $key = get_single_key($encKey['enc_with_grp'], $encKey['enc_with_rnd'], $knownKeyGroup);
                    if($key) {
                        $result[] = $encKey;
                        $result[] = $key;
                    }
                } else {
                    $result[] = $encKey;
                }
            }
        }
    }
    return $result;
}

function get_reachable_groups($group) {
    global $profile;
    global $pdo;
    $reachableKeyGroupsStatement = $pdo->prepare('SELECT DISTINCT grp FROM profile_keys WHERE profile=? and enc_with_grp=?');
    $reachableKeyGroupsStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
    $reachableKeyGroupsStatement->bindValue(2, $group, PDO::PARAM_STR);
    try {
        $reachableKeyGroupsStatement->execute();
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'Internal server error';
        exit();
    }
    $result = array();
    while($row = $reachableKeyGroupsStatement->fetch()) {
        $result[] = $row['grp'];
    }
    $reachableKeyGroupsStatement->closeCursor();
    return $result;
}

function get_single_key($grp, $rnd, $enc_with_grp) {
    global $profile;
    global $pdo;
    $keyRoundStatement = $pdo->prepare('SELECT * FROM profile_keys WHERE profile=? and grp=? and rnd=? and enc_with_grp=?');
    $keyRoundStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
    $keyRoundStatement->bindValue(2, $grp, PDO::PARAM_STR);
    $keyRoundStatement->bindValue(3, $rnd, PDO::PARAM_STR);
    $keyRoundStatement->bindValue(4, $enc_with_grp, PDO::PARAM_STR);
    try {
        $keyRoundStatement->execute();
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'Internal server error';
        exit();
    }
    $key = $keyRoundStatement->fetch();
    $keyRoundStatement->closeCursor();
    return $key;
}

function get_latest_key($grp, $enc_with_grp) {
    global $profile;
    global $pdo;
    $keyRoundStatement = $pdo->prepare('SELECT * FROM profile_keys WHERE profile=? and grp=? and enc_with_grp=? ORDER BY published desc LIMIT 1');
    $keyRoundStatement->bindValue(1, $profile['name'], PDO::PARAM_STR);
    $keyRoundStatement->bindValue(2, $grp, PDO::PARAM_STR);
    $keyRoundStatement->bindValue(3, $enc_with_grp, PDO::PARAM_STR);
    try {
        $keyRoundStatement->execute();
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'Internal server error';
        exit();
    }
    $key = $keyRoundStatement->fetch();
    $keyRoundStatement->closeCursor();
    return $key;
}
