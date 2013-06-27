<?php
error_reporting(-1);

// From: https://account.getclever.com/partner/applications
$client_secret = $_ENV["CLEVER_CLIENT_SECRET"];
$client_redirect = $_ENV["CLEVER_CLIENT_REDIRECT"]; // e.g. https://myawesomeapp.com/clever/oauth.php

// Clever sends a code that can be redeemed for a token
$code = $_GET['code'];

echo("<p>Code is " . $code . ".</p>");
echo("<p>Redeeming code for token.</p>");

$ch = curl_init($_ENV["CLEVER_API_BASE"] . "/oauth/token");
$data = array('code'         => $code,
                'grant_type'   => 'authorization_code',
                'redirect_uri' => $client_redirect);

// use client_secret as basic auth password
curl_setopt($ch, CURLOPT_USERPWD, $client_secret . ':');
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
$response = json_decode(curl_exec($ch));

$access_token = $response->access_token;
echo("<p>Access token is " . $access_token . ".</p>");
echo("<p>Using token to look up user information.</p>");

$ch = curl_init($_ENV["CLEVER_API_BASE"] . "/me");
// Use access token from previous call as bearer token to authenticate
$header = array('Authorization: Bearer ' . $access_token);
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
$response = json_decode(curl_exec($ch));

echo("<p>Here's some information about the user:</p>");
echo("<pre>");
print_r ($response);
echo("</pre>");
echo("<p>// Next: Look this user up in my database</p>");
echo("<p>// Next: Create a session for them (or show an error message)</p>");

?>
