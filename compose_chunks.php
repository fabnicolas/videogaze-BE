<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
$config = require ("./include/config.php");

include ("./include/functions.php");

include ("./lib/uploader.class.php");

$chunk_file = $_FILES['chunk_file'];
$key = post_parameter('key', null);
$chunk_number = post_parameter('chunk_number', null);
$max_chunks = post_parameter('max_chunks', null);
/*

// Check if the file is completed.

$statement = $db->getPDO()->prepare("SELECT COUNT(key_filename) AS n_chunks FROM chunks
WHERE key_filename=:key_filename GROUP BY key_filename");
$statement->execute(['key_filename' => $key]);
$result = $statement->fetchColumn(0);
*/

// If file is completed, assemble the whole file.
if ($key != null && $chunk_number != null && $max_chunks != null) {
    if($chunk_number=="1") unlink('log_dechunking.txt');
    
    // Open file in append binary mode.
    $final = fopen('./tmp/assembled_videos/' . $key . ".mp4", 'ab');
    // Make this operation atomic
	if (flock($final, LOCK_EX)) {
		if($chunk_number=="1") file_put_contents('log_dechunking.txt', 'log started,' . PHP_EOL, FILE_APPEND);
        
        $chunk_filename = $key . "_part" . $chunk_number . ".tmp";
        
        $time_start = microtime(true);

		file_put_contents('log_dechunking.txt', 'chunk_filename=' . $chunk_filename . ':read' . PHP_EOL, FILE_APPEND);
        
        $timechunkstart = microtime(true);
		$file = fopen('./tmp/' . $chunk_filename, 'rb');
		file_put_contents('log_dechunking.txt', 'chunk_filename=' . $chunk_filename . ':fopen_chunk=' . (microtime(true) - $timechunkstart) . PHP_EOL, FILE_APPEND);
        
        $timechunkstart = microtime(true);
		$buff = fread($file, 1048576);
		fclose($file);
		unset($file);
		file_put_contents('log_dechunking.txt', 'chunk_filename=' . $chunk_filename . ':readtime=' . (microtime(true) - $timechunkstart) . PHP_EOL, FILE_APPEND);
        
        $timechunkstart = microtime(true);
		$final = fopen('./tmp/assembled_videos/' . $key . ".mp4", 'ab');
		file_put_contents('log_dechunking.txt', 'chunk_filename=' . $chunk_filename . ':fopen_assembled_video=' . (microtime(true) - $timechunkstart) . PHP_EOL, FILE_APPEND);
        
        $timechunkstart = microtime(true);
		$write = fwrite($final, $buff, strlen($buff));
		unset($buff);
		file_put_contents('log_dechunking.txt', 'chunk_filename=' . $chunk_filename . ':writetime=' . (microtime(true) - $timechunkstart) . PHP_EOL . PHP_EOL, FILE_APPEND);
		unlink('./tmp/' . $chunk_filename);

		flock($final, LOCK_UN);
        fclose($final);
        
        if($chunk_number==$max_chunks){
            $db = include_once (__DIR__ . "/include/use_db.php");

            $statement = $db->getPDO()->prepare("DELETE FROM chunks WHERE key_filename=:key_filename");
            $statement->execute(['key_filename' => $key]);
            
            $time_end = microtime(true);
            file_put_contents('log_dechunking.txt', 'log finished,' . ($time_end - $time_start) . ',' . var_export($statement, true) . PHP_EOL, FILE_APPEND);
        }
    }
}
?>