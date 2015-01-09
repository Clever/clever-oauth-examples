Clever Instant Login PHP Example
==========================

This sample code demonstrates how to perform token exchange with Clever's Instant Login API.

This example was built using procedural PHP. The sample code itself relies on no external PHP dependencies. It has been fully tested with PHP 5.5.

### Running the sample code
The sample code relies on some environment variables to easily set state. Use more robust configuration schemes for production applications.

Use `bin/start_sample_server.sh` to easily start serving on localhost:2587.

`CLEVER_CLIENT_ID=abc CLEVER_CLIENT_SECRET=xyz CLEVER_DISTRICT_ID=hjkl CLEVER_PORT=2587 ./bin/start_sample_server.sh`

A CLEVER_DISTRICT_ID is not required to handle incoming OAuth 2.0 redirects, but must be provided to generate Sign in with Clever links.

### Tests
To run this example's tests, you will need to install the phpunit and http-mock.

Installing these dependencies is easy with [Composer](https://getcomposer.org/).

`composer install`

To then execute the tests:

`./vendor/bin/phpunit`
