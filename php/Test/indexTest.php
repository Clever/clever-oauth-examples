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

  public function testSetOptionsRequiresCriticalConfiguration() {
    try {
      putenv("CLEVER_CLIENT_ID");
      putenv("CLEVER_CLIENT_SECRET");
      $options = set_options();
      $this->fail("Expected exception not thrown");
    } catch(Exception $e) {
      $this->assertRegExp("@Cannot communicate with Clever without configuration@", $e->getMessage());
    }
  }

  public function testProcessClientRedirect() {
    $mock_request_options = prepare_options_for_clever();
    $token_hash_response = array('access_token' => 'abcd');
    $token_string_response = json_encode($token_hash_response);
    $this->http->mock->
        when()->methodIs('POST')->pathIs('/oauth/tokens')->
        then()->body($token_string_response)->end();
    $me_hash_response = prepare_me_response_hash();
    $me_string_response = json_encode($me_hash_response);
    $this->http->mock->
        when()->methodIs('GET')->pathIs('/me')->
        then()->body($me_string_response);
    $this->http->setUp();
    $me_response = process_client_redirect('xyz', $mock_request_options);
    $this->assertEquals($me_response, $me_hash_response);
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

    $request = $this->http->requests->latest();
    $this->assertSame(
        'application/x-www-form-urlencoded',
        (string) $request->getHeader('Content-Type'),
        'Client should send application/x-www-form-urlencoded'
    );

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

  public function testErrorHandlingFromClever() {
    $mock_request_options = prepare_options_for_clever();
    $this->http->mock->
    when()->methodIs('GET')->pathIs('/')->
    then()->statusCode(500)->end();
    $this->http->setUp();
    $response = request_from_clever("http://localhost:1234", array(), $mock_request_options);
    $this->assertEquals(500, $response['response_code']);
  }

  public function testErrorHandlingFromCurl() {
    $mock_request_options = prepare_options_for_clever();
    try {
      $response = request_from_clever("httpl://explorer:1234", array(), $mock_request_options);
      $this->fail("Expected exception not thrown");
    } catch(Exception $e) {
      $this->assertRegExp("@cURL failure@", $e->getMessage());
    }
  }

  public function testGenerateSignInWithCleverUrl() {
    $our_options = prepare_options_for_clever();
    $wanted_redirect_url = preg_quote(urlencode($our_options['client_redirect_url']));
    $wanted_client_id = preg_quote($our_options['client_id']);
    $wanted_authorize_url = $our_options['clever_oauth_authorize_url'];
    $url = generate_sign_in_with_clever_url($our_options);
    $this->assertStringStartsWith($wanted_authorize_url, $url);
    $this->assertRegExp("@redirect_uri=$wanted_redirect_url@", $url);
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

// Instructs our API requests to use mock environment
function prepare_options_for_clever() {
  $options = array(
    'client_id' => 'abc',
    'client_secret' => 'def',
    'client_redirect_url' => 'http://localhost:1234/oauth',
    'clever_redirect_base' => 'http://localhost:1234',
    'clever_oauth_tokens_url' => 'http://localhost:1234/oauth/tokens',
    'clever_api_me_url' => 'http://localhost:1234/me',
    "clever_oauth_authorize_url" => 'http://localhost:1234/oauth/authorize',
  );
  return $options;
}

// Prepares a consistent response to /me
function prepare_me_response_hash() {
  return array(
    'data' => array(
      "district" => "4fd43cc56d11340000000005",
      "id" => "4fee004cca2e43cf2700028b",
      "type" => "student"
    ),
    'links' => array(
      array(
        'uri' => "/me",
        'rel' => "self"
      )
    )
  );
}
