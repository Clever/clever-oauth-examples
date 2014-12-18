<?php
require_once('index.php');

class CleverInstantLoginExample extends PHPUnit_Framework_TestCase {
  use \InterNations\Component\HttpMock\PHPUnit\HttpMockTrait;

  public static function setUpBeforeClass() {
    static::setUpHttpMockBeforeClass('1234', 'localhost');
  }

  public static function tearDownAfterClass() {
    static::tearDownHttpMockAfterClass();
  }

  public function setUp() {
    $this->setUpHttpMock();
  }

  public function tearDown() {
    $this->tearDownHttpMock();
  }

  public function testProcessClientRedirect() {
  }

  public function testExchangeCodeForBearerToken() {
    $mock_request_options = prepare_options_for_clever();
    $expected_bearer_token = 'abcd';
    $json_hash_response = array('access_token' => $expected_bearer_token);
    $json_string_response = json_encode($json_hash_response);
    $this->http->mock->
        when()->methodIs('POST')->pathIs('/oauth/tokens')->
        then()->body($json_string_response)->end();
    $this->http->setUp();
    $bearer_token = exchange_code_for_bearer_token('xyz', $mock_request_options);
    $this->assertEquals($bearer_token, $expected_bearer_token);
  }

  public function testRequestFromClever() {
    $mock_request_options = prepare_options_for_clever();
    $json_string_response = "{'this':'that'}";
    $json_hash_response = json_decode($json_string_response);
    $this->http->mock->
        when()->methodIs('GET')->pathIs('/')->
        then()->body($json_string_response)->end();
    $this->http->setUp();
        
    $response = request_from_clever("http://localhost:1234", array(), $mock_request_options);
    $this->assertEquals($json_string_response, $response['raw_response']);
    $this->assertEquals($json_hash_response, $response['response']);
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

  public function testProcessIncomingRequests() {
    $our_options = prepare_options_for_clever();
  }
}

function prepare_options_for_clever() {
  $options = array(
    "client_redirect_url" => "http://localhost:1234/oauth",
    "client_id" => 'abc',
    "client_secret" => 'def',
    "district_id" => '123',
    "port" => 1234,
    'clever_oauth_tokens_url' => "http://localhost:1234/oauth/tokens",
    'clever_api_me_url' => "http://localhost:1234/me",
    "clever_oauth_authorize_url" => "http://localhost:1234/oauth/authorize",
  );
  return $options;
}

?>