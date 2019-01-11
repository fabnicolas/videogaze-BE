<?php
/*
  THIS SCRIPT WILL SELF-DESTRUCT AFTER EXECUTION!
*/
$db = include_once(__DIR__."/include/use_db.php");

$db->getPDO()->query("
CREATE TABLE alias (
  nickname varchar(15) NOT NULL,
  ip varchar(16) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE chunks (
  key_filename varchar(50) NOT NULL,
  chunk_id int(11) NOT NULL,
  total_chunks int(11) NOT NULL,
  lifespan datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE files_uploaded (
  id bigint(20) NOT NULL,
  code varchar(11) NOT NULL,
  key_filename varchar(50) NOT NULL,
  time_creation datetime NOT NULL,
  time_last_seen datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE requests_in_rooms (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  roomcode varchar(22) NOT NULL,
  nickname varchar(15) NOT NULL,
  time_creation datetime NOT NULL,
  request_type varchar(20) NOT NULL,
  request_value varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE rooms (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  roomcode varchar(22) NOT NULL,
  time_creation datetime NOT NULL,
  stream_type varchar(20) NOT NULL DEFAULT 'uploaded_video',
  stream_key varchar(200) NOT NULL,
  stream_ctime decimal(10,6) NOT NULL DEFAULT '0.000000',
  stream_isplaying tinyint(1) NOT NULL DEFAULT '0',
  last_ctime datetime(6) NOT NULL,
  last_isplaying datetime(6) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


ALTER TABLE chunks
  ADD PRIMARY KEY (key_filename,chunk_id);

ALTER TABLE files_uploaded
  ADD PRIMARY KEY (id,code);

ALTER TABLE requests_in_rooms
  ADD PRIMARY KEY (id,roomcode,nickname,request_type);

ALTER TABLE rooms
  ADD PRIMARY KEY (id,roomcode);
");

class SelfDestroy{function __destruct(){unlink(__FILE__);}}
$installation_finished = new SelfDestroy();
?>