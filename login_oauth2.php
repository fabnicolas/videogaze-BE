<?php
session_start();
$config = require_once './config.php';

include_once('./lib/google_oauth2/Google_Client.php');
include_once('./lib/google_oauth2/contrib/Google_Oauth2Service.php');

$googleClient = new Google_Client();
$googleClient->setApplicationName('VideoGaze');
$googleClient->setClientId($config['google_app_id']);
$googleClient->setClientSecret($config['google_app_secret']);
$googleClient->setRedirectUri($config['redirect_URL']);
$google_oauthV2 = new Google_Oauth2Service($googleClient);

$authUrl = $googleClient->createAuthUrl();
$loginURL = filter_var($authUrl, FILTER_SANITIZE_URL);
header('Location: '.$loginURL);
?>