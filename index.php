<html>
    <head>
        <meta charset="utf-8">
    </head>
<pre>
<?php
/**
 * This example assumes that you already have the refresh token
 * and want to use the refresh token to get information about
 * the user and its data.
 *
 */

require_once("CurlBrowser.php");

$url = "https://app.energimolnet.se/";
$refresh_token = "enter-your-token-here";
$curl = new CurlBrowser();


?>
<h1>Request for an access_token.</h1>
    <?php

$params = array(
    'grant_type' => 'refresh_token',
    'client_id' => 'your-client-id',
    'client_secret' => 'your-client-secret',
    'refresh_token' => 'your-refresh-token',
);

var_dump($json = $curl->post($url . 'oauth2/grant', $params));

$access_token = $json['access_token'];

/*
 * Use the access token to get information about the user.
 */


?>
    <h1>Information about the user</h1>
    <?php var_dump($curl->get($url . "api/1.1/users/me?access_token=$access_token"));?>

    <h1>All meters the user has access to</h1>
    <?php var_dump($meters = $curl->get($url . "api/1.1/users/me/meters?access_token=$access_token")); ?>

    <h1>Monthly consumption for 2013 for <?php echo $meters[0]['address']; ?></h1>
    <?php
    $meter_id = $meters[0]['_id'];
    var_dump($meters = $curl->get($url . "api/1.1/series/$meter_id?query=2013&access_token=$access_token")); ?>
</pre>
</html>