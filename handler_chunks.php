<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


$config = require("./include/config.php");
include("./include/functions.php");
include("./lib/uploader.class.php");

$uploader = new Uploader("./tmp/");

$chunk_file = $_FILES['chunk_file'];
$key=post_parameter('key',null);
$chunk_number=post_parameter('chunk_number',null);
$max_chunks=post_parameter('max_chunks',null);

if($key!=null && $chunk_number!=null && $max_chunks!=null){
    $chunk_complete_filename = $key."_part".$chunk_number.".tmp";

    if(($file_url=($uploader->uploadFile($chunk_file,$chunk_complete_filename)))!=false){
        $db = include_once(__DIR__."/include/use_db.php");
        $statement = $db->getPDO()->prepare(
			"INSERT INTO chunks (key_filename, chunk_id, total_chunks, lifespan) 
             VALUES (:key_filename, :chunk_id, :total_chunks, :lifespan)");
        $params=array();
        $params['key_filename']=$key;
        $params['chunk_id']=$chunk_number;
        $params['total_chunks']=$max_chunks;
        $params['lifespan']=sql_datetime();
        $statement->execute($params);
        file_put_contents('log_upload.txt', "File '".$key.",count=".var_export($result,true).",max_input=".$max_chunks.PHP_EOL, FILE_APPEND);
        echo "Success";
    }else{
        echo "Error";
    }
}
?>