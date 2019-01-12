<?php
function tor($url)
{
    $ua = array('Mozilla','Opera','Microsoft Internet Explorer','ia_archiver');
    $op = array('Windows','Windows XP','Linux','Windows NT','Windows 2000','OSX');
    $agent  = $ua[rand(0,3)].'/'.rand(1,8).'.'.rand(0,9).' ('.$op[rand(0,5)].' '.rand(1,7).'.'.rand(0,9).'; en-US;)';
    // Tor 地址与端口
    $tor = '127.0.0.1:9050';
    // 连接超时设置
    $timeout = 300;
    $ack = curl_init();
    curl_setopt($ack, CURLOPT_PROXY, $tor);
    curl_setopt($ack, CURLOPT_URL, $url);
    curl_setopt($ack, CURLOPT_HEADER, 0);
    curl_setopt($ack, CURLOPT_USERAGENT, $agent);
    curl_setopt($ack, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ack, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ack, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ack, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
    $result = curl_exec($ack);
    curl_close($ack);
    return $result;
}

