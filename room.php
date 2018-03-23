<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
$config = require ("./config.php");

include ("./include/functions.php");

$db = include_once (__DIR__ . "/include/use_db.php");

function answer($status,$message){
    echo json(array('status'=>$status,'message'=>$message));
}

$mode = post_parameter('mode',null);
if($mode==null && $config['http_sync_mode']=='server_sent_events')
    $mode = get_parameter('mode',null); // For SSE

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

        // Eventually client has sent the stream type and stream key values. Let's use them.
        $stream_type = post_parameter('stream_type','local');
        $stream_key = post_parameter('stream_key','sample.mp4');

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
            'stream_type'=>$stream_type,
            'stream_key'=>$stream_key,
            'stream_current_time'=>0,
            'stream_isplaying'=>0
        ]);
        answer(1,array(
            'roomcode'=>$roomcode,
            'stream_type'=>$stream_type,
            'stream_key'=>$stream_key,
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
            $request_value=null;
            $is_valid_request=false;

            // Data validation
            $extra=array();

            if($request_type=='set_stream'){
                $request_value=post_parameter('request_value',null);
                if($request_value!=null && strripos($request_value,';key=')!=false) $is_valid_request=true;
            }elseif($request_type=='set_isplaying'){
                $request_value=(int)post_parameter('request_value',1);
                if(is_numeric($request_value) && ($request_value==0 || $request_value==1)){
                    $extra['videotime']=(float)post_parameter('request_videotime',-1);
                    if($extra['videotime']>=0) $is_valid_request=true;
                }
            }elseif($request_type=='set_time'){
                $request_value=(int)post_parameter('request_value',-1);
                if(is_numeric($request_value) && $request_value>=0) $is_valid_request=true;
            }elseif($request_type=='set_current_time'){
                $request_value=(float)post_parameter('request_value',-1);
                if(is_numeric($request_value) && $request_value>=0) $is_valid_request=true;
            }elseif($request_type=='chat'){
                $request_value=post_parameter('request_value',null);
                if($request_value!=null && mb_strlen($request_value)>0) $is_valid_request=true;
            }

            if($is_valid_request){
                function insert_request($db,$roomcode,$user_ip,$request_type,$request_value){
                    $statement = $db->getPDO()->prepare(
                        "INSERT INTO requests_in_rooms 
                        (id, roomcode, nickname, time_creation, request_type, request_value) 
                        VALUES (DEFAULT, :roomcode, :nickname, :time_creation, :request_type, :request_value) 
                        ON DUPLICATE KEY UPDATE time_creation=:time_creation,
                                                request_value=:request_value;"
                    );
                    $statement->execute([
                        'roomcode'=>$roomcode,
                        'nickname'=>$user_ip,
                        'time_creation'=>sql_datetime(),
                        'request_type'=>$request_type,
                        'request_value'=>$request_value
                    ]);
                }
                insert_request($db,$roomcode,$user_ip,$request_type,$request_value);
                if($request_type=='set_isplaying'){
                    insert_request($db,$roomcode,$user_ip,'set_current_time',$extra['videotime']);
                }


                /**
                *   Sync room data for faster access.
                *   Retrieve last requests. We use INNER JOIN with MAX(time_creation) subquery to retrieve type and value
                *   by simulating "DISTINCT request_type", because we cannot distinguish a single column
                *   and retrieve all informations in one query.
                */
                $statement = $db->getPDO()->prepare(
                    "SELECT x.request_type, x.request_value
                    FROM 
                        requests_in_rooms AS x
                        INNER JOIN
                        (
                            (SELECT MAX(time_creation) AS tc
                            FROM requests_in_rooms
                            WHERE request_type IN ('set_stream', 'set_isplaying', 'set_current_time')
                            GROUP BY request_type) AS y
                        )
                    ON x.time_creation=y.tc");
                $statement->execute(['roomcode' => $roomcode]);
                $result = $statement->fetchAll(PDO::FETCH_ASSOC);

                // Parse requests into a handy array.
                $last_requests=array();
                foreach($result as $key=>$record){
                    $last_requests[$record['request_type']]=$record['request_value'];
                }

                $statement_params=array();
                if(isset($last_requests['set_stream'])){
                    $stream_data=explode(";key=",$last_requests['set_stream']);
                    $statement_params['stream_type']=$stream_data[0];
                    $statement_params['stream_key']=$stream_data[1];
                }
                if(isset($last_requests['set_current_time'])){
                    $statement_params['stream_current_time']=$last_requests['set_current_time'];
                    $statement_params['time_last_current_time']=sql_datetime(6);
                }
                if(isset($last_requests['set_isplaying'])){
                    $statement_params['stream_isplaying']=$last_requests['set_isplaying'];
                }

                if(!empty($statement_params)){
                    $statement = $db->getPDO()->prepare(
                        "UPDATE rooms SET ".$db->update_preparedstatement_composer($statement_params).
                        " WHERE roomcode = :roomcode"
                    );
                    $statement_params['roomcode']=$roomcode;
                    $statement->execute($statement_params);
                }

                answer(1,"OK");
            }else{answer(0,'INVALID_REQUEST');}
        }else{answer(0,'INVALID_IP');}
    }else{answer(0,'INVALID_ROOMCODE');}
}elseif($mode=='sync'){
    $roomcode = post_parameter('roomcode',null);
    if($roomcode==null && $config['http_sync_mode']=='server_sent_events')
        $roomcode = get_parameter('roomcode',null);

    if($roomcode!=null){
        // Use SHORT POLLING
        if($config['http_sync_mode']=='short_polling'){
            $statement = $db->getPDO()->prepare(
                "SELECT stream_type, stream_key, stream_current_time, stream_isplaying
                FROM rooms WHERE roomcode = :roomcode LIMIT 1;");
            $statement->execute(['roomcode' => $roomcode]);
            $result = $statement->fetch();

            // If it exists, then it's valid. Store room data to send them back to the client later.
            if($result != false){
                answer(1,array(
                    'stream_type'=>$result['stream_type'],
                    'stream_key'=>$result['stream_key'],
                    'stream_current_time'=>$result['stream_current_time'],
                    'stream_isplaying'=>$result['stream_isplaying']
                ));
            }else{answer(0,'INVALID_ROOMCODE');}
        // Use SSE
        }else if($config['http_sync_mode']=='server_sent_events'){
            require_once(__DIR__.'/lib/sse.class.php');

            $last_sent_message=null;
            (new SSE_Manager())->start(function() use($db,$roomcode,&$last_sent_message){
                $statement = $db->getPDO()->prepare(
                    "SELECT stream_type, stream_key, stream_current_time, stream_isplaying, time_last_current_time
                    FROM rooms WHERE roomcode = :roomcode LIMIT 1;");
                $statement->execute(['roomcode' => $roomcode]);
                $result = $statement->fetch();

                if($result !== false){
                    $status=false;
                    if($last_sent_message!=null){
                        if(!array_equals($last_sent_message,$db->result_no_int_keys($result))/*['stream_type']!=$result['stream_type'] ||
                            $last_sent_message['stream_key']!=$result['stream_key'] ||
                            $last_sent_message['stream_current_time']!=$result['stream_current_time'] ||
                            $last_sent_message['stream_isplaying']!=$result['stream_isplaying']*/)
                        {
                            $status=true;
                        }else{
                            $status=false;
                        }
                    }else{
                        $status=true;
                    }

                    $new_message=null;
                    if($status==true){
                        $new_message=array(
                            'stream_type'=>$result['stream_type'],
                            'stream_key'=>$result['stream_key'],
                            'stream_current_time'=>$result['stream_current_time'],
                            'stream_isplaying'=>$result['stream_isplaying'],
                            'time_last_current_time'=>$result['time_last_current_time'],
                            'last_sent_message'=>var_export($last_sent_message,true),
                            'result'=>var_export($result,true)
                        );
                        $last_sent_message=array(
                            'stream_type'=>$result['stream_type'],
                            'stream_key'=>$result['stream_key'],
                            'stream_current_time'=>$result['stream_current_time'],
                            'stream_isplaying'=>$result['stream_isplaying'],
                            'time_last_current_time'=>$result['time_last_current_time']
                        );
                    }
                }
                

                if(!empty($new_message))    return array('status'=>1, 'message'=>$new_message);
                else                        return array('status'=>0, 'message'=>'no_data');
            },250,'sync-messages');
        }
    }
}else{answer(0,'INVALID_MODE');}

?>