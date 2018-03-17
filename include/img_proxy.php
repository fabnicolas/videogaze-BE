<?php
function doomURL($url){
    if (substr($url, 0, 8) == 'https://') {
        $url = substr($url, 8);
    }
    if (substr($url, 0, 7) == 'http://') {
        $url = substr($url, 7);
    }
    if (substr($url, 0, 4) == 'www.') {
        $url = substr($url, 4);
    }
    if (strpos($url, '/') !== false) {
        $explode = explode('/', $url);
        $url     = $explode[0];
    }
    if(strpos($url, '..') !== false){
        $url = str_replace("..", "", $url);
    }
    if(strpos($url,'.')!==false){
        $extension = explode('.', $url);
        $extension = $extension[1];
        if($extension != 'png') die();
    }
    return $url;
}

$url = isset($_GET['url']) ? $_GET['url'] : null;
if (!$url) {
    die();
}

$url = doomURL($url);
$url = "../tmp/".$url;

$imgInfo = getimagesize($url);
if (stripos($imgInfo['mime'], 'image/') === false) {
    die();
}
header("Content-type: " . $imgInfo['mime']);
readfile($url);
?>