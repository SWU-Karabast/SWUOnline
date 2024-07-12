<?php
require_once '../Assets/patreon-php-master/src/OAuth.php';
require_once '../Assets/patreon-php-master/src/API.php';
require_once '../Assets/patreon-php-master/src/PatreonLibraries.php';
include_once '../Assets/patreon-php-master/src/PatreonDictionary.php';
require_once '../includes/functions.inc.php';
include_once "../includes/dbh.inc.php";
include_once "../Libraries/HTTPLibraries.php";
include_once "../APIKeys/APIKeys.php";


use Patreon\API;
use Patreon\OAuth;

session_start();
SetHeaders();

$client_id = $patreonClientIDReact;
$client_secret = $patreonClientSecretReact;
$redirect_uri = "https://www.karabast.net/SWUOnline/PatreonLogin.php";


// The below code snippet needs to be active wherever the the user is landing in $redirect_uri parameter above. It will grab the auth code from Patreon and get the tokens via the oAuth client

$response = new stdClass();

if (!empty($_GET['code'])) {
  $oauth_client = new OAuth($client_id, $client_secret);

  $tokens = $oauth_client->get_tokens($_GET['code'], $redirect_uri);
  $response->tokens = $tokens;

  if (isset($tokens['access_token']) && isset($tokens['refresh_token'])) {
    $access_token = $tokens['access_token'];
    $refresh_token = $tokens['refresh_token'];
    $response->access_token = $access_token;
    $response->refresh_token = $refresh_token;

    // Here, you should save the access and refresh tokens for this user somewhere. Conceptually this is the point either you link an existing user of your app with his/her Patreon account, or, if the user is a new user, create an account for him or her in your app, log him/her in, and then link this new account with the Patreon account. More or less a social login logic applies here.
    SavePatreonTokens($access_token, $refresh_token);
  }
  $response->message = "ok";
} else {
  $response->error = "no code set";
}

if (isset($access_token)) {
  try {
    PatreonLogin($access_token, true);
  } catch (\Exception $e) {
    $response->error = $e;
  }
}

echo (json_encode($response));
