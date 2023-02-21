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
$messagesSql = 'SELECT seqts, type, data FROM service_messages WHERE profile=?';
if(isset($before)) {
    $messagesSql .= ' AND seqts < ?';
}
if(isset($after)) {
    $messagesSql .= ' AND seqts > ?';
}
$messagesSql .= ' ORDER BY seqts DESC LIMIT ?';
$messagesStatement = $pdo->prepare($messagesSql);
$nextValuePos = 1;
$messagesStatement->bindValue($nextValuePos++, $profile['name'], PDO::PARAM_STR);
if(isset($before)) {
    $messagesStatement->bindValue($nextValuePos++, datetime_to_microtime($before), PDO::PARAM_INT);
}
if(isset($after)) {
    $messagesStatement->bindValue($nextValuePos++, datetime_to_microtime($after), PDO::PARAM_INT);
}
$messagesStatement->bindValue($nextValuePos++, $max+1, PDO::PARAM_INT);
try {
    $messagesStatement->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}
$response = array(
    'data' => array(),
    'more' => false
);
$count = 0;
while($message = $messagesStatement->fetch()) {
    if($count >= $max) {
        $response['more'] = true;
        break;
    }
    $seqts = microtime_to_datetime($message['seqts']);
    $elem = json_decode($message['data'], true);
    switch($message['type']) {
        case 0: // provider_message
            $response['data'][] = array(
                'seqts' => spxp_format_datetime($seqts),
                'type' => 'provider_message',
                'message' => empty($elem['message']) ? '???' : $elem['message'],
                'link' => empty($elem['link']) ? null : $elem['link']
            );
            break;
        case 1: // connection_request
            $response['data'][] = array(
                'seqts' => spxp_format_datetime($seqts),
                'type' => 'connection_request',
                'received' => empty($elem['received']) ? null : $elem['received'],
                'ver' => empty($elem['ver']) ? null : $elem['ver'],
                'establishId' => empty($elem['establishId']) ? null : $elem['establishId'],
                'msg' => empty($elem['msg']) ? null : $elem['msg']
            );
            break;
        case 2: // connection_package
            $response['data'][] = array(
                'seqts' => spxp_format_datetime($seqts),
                'type' => 'connection_package',
                'received' => empty($elem['received']) ? null : $elem['received'],
                'ver' => empty($elem['ver']) ? null : $elem['ver'],
                'establishId' => empty($elem['establishId']) ? null : $elem['establishId'],
                'package' => empty($elem['package']) ? null : $elem['package']
            );
            break;
    }
    $count++;
}
header('Content-Type: application/json');
echo json_encode($response);
