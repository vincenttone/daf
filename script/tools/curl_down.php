#!/usr/bin/env php
<?php
require_once(dirname(dirname(dirname(__FILE__))).'/app/init.php');
$callback = function($info) {
    $code = $info['http_code'];
    $content_length = $info['content_length'];
    $size_download = $info['download_size'];
    $download_speed = $info['download_speed'];
    $remote = $info['remote'];
    $file = $info['file'];
    $download_percent = '0.00%';
    ($content_length == 0) || $download_percent = sprintf("%.2f%%", $size_download * 100 / $content_length);
    echo $code.' URL: '.$remote.' FILE: '.$file.' ['.$download_percent.'] Speed: '.$download_speed.PHP_EOL;
};

print_r(Lib_Curl::download($argv[1], $argv[2], $callback));
/*
function download($remote, $local, $info_callback = null)
{
    if (file_exists($local)) @unlink($local);
    $fp = fopen($local, "a");
    $write_func = function($cp, $content) use ($fp, $info_callback, $remote, $local) {
        //($content_length === 0) || $download_percent = sprintf("%.2f%%", $size_download * 100 / $content_length);
        empty($content) || fwrite($fp, $content);
        $len = strlen($content);
        if (is_callable($info_callback)) {
            $info = [
                'http_code' => curl_getinfo($cp, CURLINFO_HTTP_CODE),
                'total_time' => curl_getinfo($cp, CURLINFO_TOTAL_TIME),
                'namelook_time' => curl_getinfo($cp, CURLINFO_NAMELOOKUP_TIME),
                'connect_time' => curl_getinfo($cp, CURLINFO_CONNECT_TIME),
                'content_length' => curl_getinfo($cp, CURLINFO_CONTENT_LENGTH_DOWNLOAD),
                'download_speed' => curl_getinfo($cp, CURLINFO_SPEED_DOWNLOAD),
                'download_size' => curl_getinfo($cp, CURLINFO_SIZE_DOWNLOAD),
                'remote' => $remote,
                'file' => $local,
            ];
            call_user_func($info_callback, $info);
        }
        return $len;
    };
    $ua = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36';
    $cp = curl_init($remote);
    curl_setopt($cp, CURLOPT_AUTOREFERER, 1);
    curl_setopt($cp, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($cp, CURLOPT_WRITEFUNCTION, $write_func);
    curl_setopt($cp, CURLOPT_MAXREDIRS, 20);
    curl_setopt($cp, CURLOPT_USERAGENT, $ua);
    $exec_result = curl_exec($cp);
    $code = curl_getinfo($cp, CURLINFO_HTTP_CODE);
    $return = ['errno' => 0, 'msg' => '['.$remote.'] download success!'];
    if ($code != 200)  {
        $return = ['errno' => -1, 'msg' => '['.$remote.'] download failed!，http code:'.$code];
    } 
    if (!$exec_result) {
        $return['errno'] = -1;
        $return['msg'] = curl_error($cp);
    }
    curl_close($cp);
    fclose($fp);
    return $return;
}
*/