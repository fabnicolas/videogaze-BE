<?php
/*
  THIS SCRIPT WILL SELF-DESTRUCT AFTER EXECUTION!
*/
$db = include_once(__DIR__."/include/use_db.php");

$db->getPDO()->query("
CREATE TABLE IF NOT EXISTS alias (
    nickname varchar(15) NOT NULL,
    ip varchar(16) NOT NULL
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
  
  CREATE TABLE IF NOT EXISTS chunks (
    key_filename varchar(50) NOT NULL,
    chunk_id int(11) NOT NULL,
    total_chunks int(11) NOT NULL,
    lifespan datetime NOT NULL,
    PRIMARY KEY (key_filename,chunk_id)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
  
  CREATE TABLE IF NOT EXISTS files_uploaded (
    id bigint(20) NOT NULL,
    code varchar(11) NOT NULL,
    key_filename varchar(50) NOT NULL,
    time_creation datetime NOT NULL,
    time_last_seen datetime NOT NULL,
    PRIMARY KEY (id,code)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
  
  CREATE TABLE IF NOT EXISTS requests_in_rooms (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    roomcode varchar(22) NOT NULL,
    nickname varchar(15) NOT NULL,
    time_creation datetime NOT NULL,
    request_type varchar(20) NOT NULL,
    request_value varchar(50) NOT NULL,
    PRIMARY KEY (id,roomcode,nickname),
    UNIQUE KEY index_ifEthenUelseI (roomcode,nickname,request_type)
  ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=38 ;
  
  CREATE TABLE IF NOT EXISTS rooms (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    roomcode varchar(22) NOT NULL,
    time_creation datetime NOT NULL,
    stream_type varchar(20) NOT NULL DEFAULT 'uploaded_video',
    stream_key varchar(50) NOT NULL,
    stream_current_time smallint(6) NOT NULL DEFAULT '0',
    stream_isplaying tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (id,roomcode)
  ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=30 ;  
");

class SelfDestroy{function __destruct(){unlink(__FILE__);}}
$installation_finished = new SelfDestroy();
?>