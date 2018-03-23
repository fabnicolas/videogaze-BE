<?php
/** Server-Sent Events (SSE) library.
 * 
 * @author Fabio Crispino
 */
class SSE_Event{
    private $event;

    public function __construct(array $event){
        $parameters=['comment','id','retry','type','data'];
        foreach($parameters as $param){
            $this->event[$param]=$this->parametize($event,$param);
        }
    }

    public function __toString(){
        $event = [];
        $event[]=$this->concat_param('comment');
        $event[]=$this->concat_param('id');
        $event[]=$this->concat_param('retry');
        $event[]=$this->concat_param('type');
        $event[]=$this->concat_param('data');
        unset($event[0]);
        return implode("\n", $event) . "\n\n";
    }

    private function parametize($event,$var,$default=null){
        return (isset($event[$var])?$event[$var]:null);
    }

    private function has($var){
        if($this->parametize($this->event,$var)==null) return false;
        return strlen($this->event[$var])>0;
    }

    private function concat_param($param){
        if($this->has($param)){
            return sprintf('%s: %s',($param!='comment'?$param:''),$this->event[$param]);
        }else{
            return '';
        }
    }
}

class SSE_Update{
    private $callback,$delay;
    public function __construct(callable $callback, $delay=3000){
        $this->callback = $callback;
        $this->delay = $delay;
    }
    public function setDelay($delay){$this->delay=$delay;}
    public function onUpdates(callable $callback){$this->callback=$callback;}
    public function getDelay(){return $this->delay;}
    public function getUpdates(){return call_user_func($this->callback);}
}

class SSE_Manager{
    private $start_time,$max_execution_time;

    public function __construct($set_headers=true){
        if($set_headers==true){
            header('Content-Type: text/event-stream');  // Set SSE.
            header('Cache-Control: no-cache');  // Disable cache.
            header('Connection: keep-alive'); // Maintain connection-
            header('X-Accel-Buffering: no'); // For nginx servers: disable cache and buffering.
        }
        $this->start_time=time();
        $this->max_time=(int)ini_get('max_execution_time');
        if($this->max_time>0)  $this->max_time=$this->max_time-5;
        else                   $this->max_time=null;
    }

    private function send_event($event){
        echo new SSE_Event($event);
        ob_flush();
        flush();
    }

    public function start_advanced(SSE_Update $update,$event_type=null,$enable_comments=false){
        while(true){
            $event=null;
            if(($data=$update->getUpdates())['status'] == 1){
                $event = [
                    'id'    => uniqid(),
                    'type'  => $event_type,
                    'data'  => json_encode($data),
                    'retry' => $update->getDelay()
                ];
            }else if($enable_comments==true){$event = ['comment' => json_encode($data)];}
            if($event!=null) $this->send_event($event);
            
            usleep($update->getDelay()*1000);
            if($this->max_time!=null && ((time() - ($this->start_time)) >= ($this->max_time))){
                $this->send_event([
                    'id'=>uniqid(),
                    'type'=>$event_type,
                    'data'=>json_encode(array('status'=>0, 'message'=>'SSE_CLOSE_CONNECTION')),
                    'retry'=>2000
                ]);
                break;
            }
        }
    }

    public function start(callable $callback,$delay,$event_type=null){
        return $this->start_advanced(new SSE_Update($callback,$delay),$event_type);
    }
}
