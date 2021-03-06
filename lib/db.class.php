<?php
// Extending PDO
class DB{
    var $pdo;
    var $connection_params;
    var $admin_params;

    private function pdo_params_composer($array_params){
        $str_params="";
        foreach($array_params as $key=>$value){
            $str_params.=$key."=".$value.";";
        }
        return (substr($str_params,0,strlen($str_params)-1));
    }

    function in_composer($array_params){
        $str_params="";
        foreach($array_params as $key=>$value){
            $str_params.=$value.",";
        }
        return (substr($str_params,0,strlen($str_params)-1));
    }

    function result_no_int_keys(array $result){
        if($result!==false){
            foreach($result as $key => $value) {
                if(is_int($key)) unset($result[$key]);
            }
        }
        return $result;
    }

    function update_preparedstatement_composer($array_params){
        $str_params="";
        foreach($array_params as $key=>$value){
            $str_params.=$key."=:".$key.",";
        }
        return (substr($str_params,0,strlen($str_params)-1));
    }

    function __construct($connection_params, $admin_params, $connect=true){
        $db_selected = $connection_params['dbname'];
        if($db_selected && $connect) unset($connection_params['dbname']);

        $this->connection_params=$connection_params;
        $this->admin_params=$admin_params;
        if($connect){
            $this->pdo = new PDO("mysql:".($this->pdo_params_composer($this->connection_params)), $this->admin_params['username'], $this->admin_params['password']);
            $this->pdo->query("USE ".$db_selected);
        }
    }

    function getPDO(){return $this->pdo;}
}
?>