<?php
function enable_errors(){
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}

function post_parameter($key,$default=null){
	return isset($_POST[$key]) ? $_POST[$key] : $default;
}

function execute_atomically($function_to_execute){
	if($lock=flock('./lockfile', LOCK_EX)){
		try{$function_to_execute();}
		catch(Exception $e){echo $e;}
		finally{flock($lock, LOCK_UN);}
	}
}

function debug_var($var){
	return var_export($var,true);
}

function json($json_object){
	header('Content-Type: application/json');
	return json_encode($json_object);	
}

function rrmdir($dir) { 
	if (is_dir($dir)) { 
	  $objects = scandir($dir); 
	  foreach ($objects as $object) { 
		if ($object != "." && $object != "..") { 
		  if (is_dir($dir."/".$object))
			rrmdir($dir."/".$object);
		  else
			unlink($dir."/".$object); 
		} 
	  }
	  rmdir($dir); 
	} 
}

function custom_rmdir($dir,$del_subdirs=false){
		$di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
		$ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
		foreach ( $ri as $file ) {
			$file->isDir() ? ($del_subdirs==true ? rmdir($file) : NULL) : unlink($file);
		}
}

function unique_random_string($length=16){
	if(function_exists('random_bytes'))
		return bin2hex(random_bytes($length));
	else
		return bin2hex(openssl_random_pseudo_bytes($length));
}

function sql_datetime(){
	return date("Y-m-d H:i:s");
}

// Enable errors
enable_errors();
?>