<?php
# A sample Clever Instant Login implementation.

// error_reporting(-1);

if($_SERVER && array_key_exists('REQUEST_URI', $_SERVER)) {
  process_incoming_requests($_SERVER['REQUEST_URI'], set_options());
}
  
function set_options($override_options = array()) {
  $options = array(
    // # Obtain your Client ID and secret from your Clever developer dashboard at https://account.clever.com/partner/applications
    'client_id' => $_ENV["CLEVER_CLIENT_ID"],
    'client_secret' => $_ENV["CLEVER_CLIENT_SECRET"],
    'port' => $_ENV=["CLEVER_PORT"] || 2587,
    'district_id' => $_ENV['DISTRICT_ID'],
    'clever_oauth_base' => 'https://clever.com/oauth',
    'clever_api_base' => 'https://api.clever.com',
  );
  array_merge($options, $override_options);
  $options['clever_oauth_tokens_url'] = $options['clever_oauth_base'] . "/tokens";
  $options['clever_oauth_authorize_url'] = $options['clever_oauth_base'] . "/authorize";
  $options['clever_api_me_url'] = $options['clever_api_base'] . '/me';
  # Clever redirect URIs must be preregistered on your developer dashboard.
  # If using the default PORT set above, make sure to register "http://localhost:2587/oauth"
  $options['client_redirect_url'] = "http://localhost" . $options['port'] . "/oauth";
  return $options;
}


function process_incoming_requests($incoming_request_uri, $options) {
  # Decide how to service incoming requests based on the path
  switch ($incoming_request_uri) {
    case preg_match('/oauth/', $a):
      $me = process_client_redirect($_GET['code'], $options);
      echo("<p>Here's some information about the user:</p>");
      echo("<pre>");
      print_r ($me);
      echo("</pre>");
      break;

    default:
      # Our home page route will create a Clever Instant Login button for users from the district our $district_id is set to.
      $sign_in_link = generate_sign_in_with_clever_link($options);
      echo("<h1>Login!</h1>");
      echo('<p>' . $sign_in_link . '</p>');
      break;
  }
}

# Processes incoming requests to our $client_redriect
function process_client_redirect($code, $options) {
  $bearer_token = exchange_code_for_bearer_token($code, $options);
  $request_options = array('method' => 'GET', 'bearer_token' => $bearer_token);
  $me_response = request_from_clever($options['clever_api_me_url'], $request_options, $options);
  return $me_response['response'];
}

# Exchanges a $code value received in a $client_redirect for a bearer token
function exchange_code_for_bearer_token($code, $options) {
  $data = array('code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $options['client_redirect_url']);
  $request_options = array('method' => 'POST', 'data' => $data);
  $response = request_from_clever($options['clever_oauth_tokens_url'], $request_options, $options);
  $bearer_token = $response['response']['access_token'];
  return $bearer_token;
}

# General purpose HTTP wrapper for working with the Clever API
function request_from_clever($url, $request_options, $clever_options) {
  $ch = curl_init($url);
  $request_headers = array('Accept: application/json');
  if ($request_options && array_key_exists('bearer_token', $request_options)) {
    $auth_header = 'Authorization: Bearer ' . $request_options['bearer_token'];
    $request_headers[] = $auth_header;
  } else {
    # When we don't have a bearer token, assume we're performing client auth.
    curl_setopt($ch, CURLOPT_USERPWD, $clever_options['client_id'] . ':' . $clever_options['client_secret']);
  }
  if ($request_options && array_key_exists('method', $request_options) && $request_options['method'] == 'POST') {
    curl_setopt($ch, CURLOPT_POST, 1);
    if ($request_options['data']) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $request_options['data']);
    }
  }
  # Set prepared HTTP headers
  curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $raw_response = curl_exec($ch);
  $parsed_response = json_decode($raw_response, true);
  $curl_info = curl_getinfo($ch);
  # Prepare the parsed and raw repsonse for further use.
  return array('response' => $parsed_response, 'raw_response' => $raw_response, 'curl_info' => $curl_info);
}

function generate_sign_in_with_clever_url($options) {
  $request_params = array(
    'response_type' => 'code',
    'redirect_uri' => $options['client_redirect_url'],
    'client_id' => $options['client_id'],
    'scope' => 'read:user_id read:sis',
    'district_id' => $options['district_id']
  );
  $querystring = http_build_query($request_params);
  return ($options['clever_oauth_authorize_url'] . '?' . $querystring);
}

function generate_sign_in_with_clever_link($options) {
  return "<a href='" . generate_sign_in_with_clever_url($options) . "'><img src='http://assets.clever.com/sign-in-with-clever/sign-in-with-clever-small.png'/></a>";
}

?>