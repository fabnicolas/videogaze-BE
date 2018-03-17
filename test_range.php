<?php
$range = '60-120';
$host = "finalgalaxy.unaux.com/videogaze/";
$socket = fsockopen($host,80);
$packet = "GET /path/to/some/image.png HTTP/1.1\r\nHost: $host\r\nRange:bytes=$range\r\nAccept-Encoding: gzip\r\nConnection: close\r\n\r\n";
fwrite($socket,$packet);
echo fread($socket,2048);
?>