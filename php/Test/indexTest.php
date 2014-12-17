<?php
require_once('index.php');

class CleverInstantLoginExample extends PHPUnit_Framework_TestCase {
    public function testProcessClientRedirect() {
    }
    
    public function testExchangeCodeForBearerToken() {
      
    }
    
    public function testRequestFromClever() {
      
    }
    
    public function testGenerateSignInWithCleverUrl() {
      $our_options = prepare_options_for_clever();
      $wanted_redirect_url = preg_quote(urlencode($our_options['client_redirect_url']));
      $wanted_client_id = preg_quote($our_options['client_id']);
      $wanted_district_id = preg_quote($our_options['district_id']);
      $wanted_authorize_url = $our_options['clever_oauth_authorize_url'];
      $url = generate_sign_in_with_clever_url($our_options);
      $this->assertStringStartsWith($wanted_authorize_url, $url);
 $this->assertRegExp("@redirect_uri=$wanted_redirect_url@", $url);
      $this->assertRegExp(("@district_id=$wanted_district_id@"), $url);
      $this->assertRegExp(("@client_id=$wanted_client_id@"), $url);
    }
    
    public function testGenerateSignInWithCleverLink() {
      $our_options = prepare_options_for_clever();
      $url = generate_sign_in_with_clever_url($our_options);
      $link = generate_sign_in_with_clever_link($our_options);
      $this->assertStringStartsWith("<a href='$url'", $link);
      $this->assertStringEndsWith("</a>", $link);
    }    
}

function prepare_options_for_clever() {      
  $options = array(
    "client_redirect_url" => "http://example.com",
    "client_id" => 'abc',
    "client_secret" => 'def',
    "district_id" => '123',
    "clever_oauth_authorize_url" => "https://clever.com/oauth/authorize",
    "port" => 1234,
    'clever_oauth_base' => 'https://clever.com/oauth',
    'clever_api_base' => 'https://api.clever.com',        
  );
  return $options;
}

?>