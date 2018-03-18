<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
$config = require ("./include/config.php");

include ("./include/functions.php");

$db = include_once (__DIR__ . "/include/use_db.php");

function answer($status,$message){
    echo json(array('status'=>$status,'message'=>$message));
}

$mode = post_parameter('mode',null);
if($mode!=null){
    if($mode=='init_stream'){
        // If a roomcode is provided by client, use it, otherwise use null value.
        $roomcode = post_parameter('roomcode',null);
        $is_valid_roomcode=false;
        $room_data=null;

        if($roomcode!=null){
            // Check if room with provided client roomcode exists already.
            $statement = $db->getPDO()->prepare(
                "SELECT stream_type, stream_key, stream_current_time, stream_isplaying
                FROM rooms WHERE roomcode = :roomcode LIMIT 1;");
            $statement->execute(['roomcode' => $roomcode]);
            $result = $statement->fetch();

            // If it exists, then it's valid. Store room data to send them back to the client later.
            if($result != false){
                $room_data=array(
                    'stream_type'=>$result['stream_type'],
                    'stream_key'=>$result['stream_key'],
                    'stream_current_time'=>$result['stream_current_time'],
                    'stream_isplaying'=>$result['stream_isplaying']
                );
                $is_valid_roomcode=true;
            } // Else, room does not exists... So it's an invalid code.             
        } // Else, roomcode was not provided, so that's an invalid code.

        if($is_valid_roomcode){
            // Room is valid. Return it to the client.
            answer(1,$room_data);
        }else{
            // Room is not valid. Make a new room and generate a new roomcode.
            $roomcode = strtoupper(unique_random_string(11));

            $statement = $db->getPDO()->prepare(
                "INSERT INTO rooms 
                (id, roomcode, time_creation, stream_type, stream_key,
                stream_current_time, stream_isplaying)
                VALUES
                (DEFAULT, :roomcode, :time_creation, :stream_type, :stream_key,
                :stream_current_time, :stream_isplaying);"
            );

            $statement->execute([
                'roomcode'=>$roomcode,
                'time_creation'=>sql_datetime(),
                'stream_type'=>'self_hosted_mp4',
                'stream_key'=>'sample.mp4',
                'stream_current_time'=>0,
                'stream_isplaying'=>0
            ]);
            answer(1,array(
                'roomcode'=>$roomcode,
                'stream_type'=>'self_hosted_mp4',
                'stream_key'=>'sample.mp4',
                'stream_current_time'=>0,
                'stream_isplaying'=>0
            ));
        }
    }elseif($mode=='request'){
        $roomcode = post_parameter('roomcode',null);
        if($roomcode!=null){
            $user_ip=filter_var($_SERVER['REMOTE_ADDR'],FILTER_VALIDATE_IP) ? $_SERVER['REMOTE_ADDR'] : null;
            if($user_ip!=null){
                $request_type=post_parameter('request_type',null);
                if($request_type=='set_current_time'){
                    $request_value=post_parameter('request_value',-1);
                    if(is_numeric($request_value) && $request_value>=0){
                        $statement = $db->getPDO()->prepare(
                            "INSERT INTO requests_in_rooms 
                            (roomcode, nickname, time_creation, request_type, request_value) 
                            VALUES (:roomcode, :nickname, :time_creation, :request_type, :request_value);"
                        );
                        $statement->execute([
                            'roomcode'=>$roomcode,
                            'nickname'=>$user_ip,
                            'time_creation'=>sql_datetime(),
                            'request_type'=>$request_type,
                            'request_value'=>$request_value
                        ]);
                        answer(1,'OK');
                    }else{answer(0,'INVALID_REQUEST_VALUE');}
                }else{answer(0,'INVALID_REQUEST_TYPE');}
            }else{answer(0,'INVALID_IP');}
        }else{answer(0,'INVALID_ROOMCODE');}
    }else{answer(0,'INVALID_MODE');}
}

?>