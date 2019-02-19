<?php
/*
  THIS SCRIPT WILL SELF-DESTRUCT AFTER EXECUTION!
*/
$config = require(__DIR__."/config.php");
$db = include_once(__DIR__."/include/use_db.php");
$db_name = $config['db_name'];
$table_prefix = $config['table_prefix'];
$pre_queries = null;
if($db_name){
  $pre_queries = 
    "CREATE DATABASE IF NOT EXISTS ".$db_name.";
    USE ".$db_name.";";
}else{
  $pre_queries="";
}
$db->getPDO()->query($pre_queries."
CREATE TABLE ".$table_prefix."alias (
  nickname varchar(15) NOT NULL,
  ip varchar(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE ".$table_prefix."chunks (
  key_filename varchar(50) NOT NULL,
  chunk_id int(11) NOT NULL,
  total_chunks int(11) NOT NULL,
  lifespan datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE ".$table_prefix."files_uploaded (
  id bigint(20) NOT NULL,
  code varchar(11) NOT NULL,
  key_filename varchar(50) NOT NULL,
  time_creation datetime NOT NULL,
  time_last_seen datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE ".$table_prefix."requests_in_rooms (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  roomcode varchar(22) NOT NULL,
  nickname varchar(15) NOT NULL,
  time_creation datetime NOT NULL,
  request_type varchar(20) NOT NULL,
  request_value varchar(50) NOT NULL,
  PRIMARY KEY (id,roomcode,nickname,request_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE ".$table_prefix."rooms (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  roomcode varchar(22) NOT NULL,
  time_creation datetime NOT NULL,
  stream_type varchar(20) NOT NULL DEFAULT 'uploaded_video',
  stream_key varchar(500) NOT NULL,
  stream_ctime decimal(10,6) NOT NULL DEFAULT '0.000000',
  stream_isplaying tinyint(1) NOT NULL DEFAULT '0',
  last_ctime datetime(6) NOT NULL,
  last_isplaying datetime(6) NOT NULL,
  PRIMARY KEY (id,roomcode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE ".$table_prefix."chunks
  ADD PRIMARY KEY (key_filename,chunk_id);
ALTER TABLE ".$table_prefix."files_uploaded
  ADD PRIMARY KEY (id,code);
");
class SelfDestroy{function __destruct(){unlink(__FILE__);}}
$installation_finished = new SelfDestroy();
?>