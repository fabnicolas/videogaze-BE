<?php
$config = require("./include/config.php");
include("./include/functions.php");

$db = include_once(__DIR__."/include/use_db.php");
$statement = $db->getPDO()->prepare("TRUNCATE TABLE chunks");
$statement->execute();

//custom_rmdir('./tmp/');
foreach(glob("./tmp/*.tmp") as $f) {
    unlink($f);
}
//rrmdir('./tmp/');
//mkdir('./tmp/', 0755, true);
//mkdir('./tmp/assembled_videos/', 0755, true);

echo "Deleted";
?>