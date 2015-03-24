Clever Instant Login PHP Example
==========================

This sample code demonstrates how to perform token exchange with Clever's Instant Login API.

This example was built using procedural PHP. The sample code itself relies on no external PHP dependencies. It has been fully tested with PHP 5.5.

When run in a server context, `process_incoming_requests` will listen for incoming HTTP requests on the specified host and port after evaluating pertinent configuration options.

Requests to "/" or any paths besides "/oauth" will be served a simple HTML page with a "Log in with Clever" button. When that link is followed and authentication is completed on clever.com, the user will be redirected to this script's OAuth 2.0 redirect flow.

Requests to "/oauth" will be treated as OAuth 2.0 client redirects and passed to our `process_client_redirect` function to perform a few brief steps:

* The exchange code value passed to our redirect URI is exchanged for an OAuth 2.0 bearer token representing the current user (in `exchange_code_for_bearer_token`)
* The bearer token will be used in a request to Clever's "/me" API resource to discover additional information about the current user (in `retrieve_me_response_for_bearer_token`)
* The current user's information will be displayed in HTML.

The `request_from_clever` function may be used as a light wrapper around cURL for issuing requests to the Clever API on behalf of a bearer token.

### Running the sample code
The sample code relies on some environment variables to easily set state. Use more robust configuration schemes for production applications.

Use `bin/start_sample_server.sh` to easily start serving on `http://localhost:2587`.

`CLEVER_CLIENT_ID=abc CLEVER_CLIENT_SECRET=xyz CLEVER_REDIRECT_BASE=http://localhost:2587 ./bin/start_sample_server.sh`

Set `CLEVER_REDIRECT_BASE` to use an alternate host and port combination for your server.

Remember to register your redirect URI on your Clever developer dashboard. If `CLEVER_REDIRECT_BASE` is set to `http://localhost:2587`, the redirect URI to register would be `http://localhost:2587/oauth`.

### Tests
To run this example's tests, you will need to install the [phpunit](https://phpunit.de/) and [http-mock](https://github.com/InterNations/http-mock).

Installing these dependencies is easy with [Composer](https://getcomposer.org/).

`composer install`

To then execute the tests:

`./vendor/bin/phpunit`
