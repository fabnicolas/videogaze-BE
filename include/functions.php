<?php
/**
 * Utility functions class that can be used anywhere by including this file.
 *
 * Handy for a lot of tasks that are repeated over time.
 *
 * @author Fabio Crispino
 */


function enable_errors()
{
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

function get_parameter($key, $default=null)
{
    return isset($_GET[$key]) ? $_GET[$key] : $default;
}

function post_parameter($key, $default=null)
{
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

function execute_atomically($function_to_execute)
{
    if ($lock=flock('./lockfile', LOCK_EX)) {
        try {
            $function_to_execute();
        } catch (Exception $e) {
            echo $e;
        } finally {
            flock($lock, LOCK_UN);
        }
    }
}

function debug_var($var)
{
    return var_export($var, true);
}

function json($json_object)
{
    header('Content-Type: application/json');
    return json_encode($json_object);
}

function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir."/".$object)) {
                    rrmdir($dir."/".$object);
                } else {
                    unlink($dir."/".$object);
                }
            }
        }
        rmdir($dir);
    }
}

function custom_rmdir($dir, $del_subdirs=false)
{
    $di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
    $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($ri as $file) {
        $file->isDir() ? ($del_subdirs==true ? rmdir($file) : null) : unlink($file);
    }
}

function unique_random_string($length=16)
{
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($length));
    } else {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }
}

function sql_datetime($precision=0)
{
    $timestamp_micro = microtime();
    list($msec, $sec) = explode(' ', $timestamp_micro);
    $msec = explode(".", $msec);
    $msec = $msec[1];
    $msec = substr($msec, 0, $precision);
    $append='';
    if ($precision>0) {
        $append=".".$msec;
    }
    return date("Y-m-d H:i:s".$append);
}

function array_equals(array $array1, array $array2)
{
    $are_equals=true;
    if (count($array1) != count($array2)) {
        return false;
    } else {
        foreach ($array1 as $key=>$value) {
            if (!isset($array2[$key]) || $array1[$key]!=$array2[$key]) {
                $are_equals=false;
                break;
            }
        }
    }
    return $are_equals;
}

function log_println($filename, $text)
{
    return log_print($filename, $text.PHP_EOL);
}

function log_print($filename, $text)
{
    return file_put_contents($filename, $text, FILE_APPEND);
}

function cors_allow_all()
{
    header("Access-Control-Allow-Origin: *");
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 86400');
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
}

// Enable errors
enable_errors();
