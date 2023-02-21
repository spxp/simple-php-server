<?php

if (!defined('CORRECT_ENTRY')) {
    http_response_code(500);
    echo 'Internal server error';
    exit();
}

$tzutc = new DateTimeZone('UTC');

function base64url_encode($data) {
    return sodium_bin2base64($data, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    //return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
function base64url_decode($data) {
    return sodium_base642bin($data, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    // INVALID: return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}
function generateRandomKeyIdShort() {
    return base64url_encode(random_bytes(6));
}
function generateRandomKeyIdLong() {
    return base64url_encode(random_bytes(12));
}
function is_base64urlsafe($str) {
    return true;
}
function nowUtc() {
    global $tzutc;
    return new DateTime('now', $tzutc);
}
function fromTimestampUtc($timestamp) {
    global $tzutc;
    return new DateTime('@'.$timestamp, $tzutc);
}
function spxp_parse_datetime($str) {
    global $tzutc;
    return DateTime::createFromFormat('Y-m-d\TH:i:s.v', $str, $tzutc);
}
function spxp_format_datetime($datetime) {
    return $datetime->format('Y-m-d\TH:i:s.v');
}
function datetime_to_microtime($datetime) {
    return ($datetime->getTimestamp() * 1000) + intval($datetime->format('v'));
}
function microtime_to_datetime($microtime) {
    global $tzutc;
    $timestamp = $microtime / 1000;
    $ms = $microtime % 1000;
    $dt_without_ms = fromTimestampUtc($timestamp);
    return DateTime::createFromFormat('Y-m-d\TH:i:s.v', $dt_without_ms->format('Y-m-d\TH:i:s').'.'.sprintf('%03d', $ms), $tzutc);
}
