<?php
session_start();
$config = require_once('./config.php');
$db = require_once('./include/use_db.php');

$table_prefix = $config['table_prefix'];

include_once('./lib/google_oauth2/Google_Client.php');
include_once('./lib/google_oauth2/contrib/Google_Oauth2Service.php');

$google_client = new Google_Client();
$google_client->setApplicationName('VideoGaze');
$google_client->setClientId($config['google_app_id']);
$google_client->setClientSecret($config['google_app_secret']);
$google_client->setRedirectUri($config['redirect_URL']);
$google_oauthV2 = new Google_Oauth2Service($google_client);


if(isset($_GET['code'])){
	$google_client->authenticate($_GET['code']);
	$_SESSION['token'] = $google_client->getAccessToken();
	header('Location: '.filter_var($redirectURL, FILTER_SANITIZE_URL));
}
if(isset($_SESSION['token'])){
	$google_client->setAccessToken($_SESSION['token']);
}

if($google_client->getAccessToken()){
	try{
		$gpUserProfile = $google_oauthV2->userinfo->get();
	}catch(\Exception $e){
		echo "GP user profile error: ".$e->getMessage();
		session_destroy();
		header("Location: ./");
		exit;
  }
	$oauth2_id = $gpUserProfile['id'] ?? 0;
	$email = $gpUserProfile['email'] ?? '';
	$locale = $gpUserProfile['locale'] ?? 'en';

	$sql = "SELECT COUNT(oauth2_id) FROM ".$table_prefix."users_google_oauth2 WHERE oauth2_id='".$gpUserProfile['id']."'";
	$num_rows = $db->getPDO()->query($sql)->fetchColumn();
	if($num_rows > 0){
	  $db->getPDO()->query("UPDATE ".$table_prefix."users_google_oauth2 SET locale='".$locale."' WHERE oauth2_id='".$oauth2_id."'");
	}else{
		$db->getPDO()->query("INSERT INTO ".$table_prefix."users (user_id, auth_type, email) VALUES (DEFAULT, 'google_oauth2', '".$email."');");
		$user_id = $db->getPDO()->lastInsertId();
		$db->getPDO()->query("INSERT INTO ".$table_prefix."users_google_oauth2 (user_id, oauth2_id, locale) VALUES ('".$user_id."', '".$oauth2_id."', '".$locale."');");
	}
	$statement = $db->getPDO()->prepare($sql);
	$statement->execute();
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);

	$_SESSION['user_data'] = $result;
}


header("Location: ../");