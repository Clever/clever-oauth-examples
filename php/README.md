Clever Instant Login PHP Example
==========================

This sample code demonstrates how to perform token exchange with Clever's Instant Login API.

This example was built using procedural PHP. The sample code itself relies on no external PHP dependencies.

### Running the sample code
The sample code relies on some environment variables to easily set state. Productinoized applications should use more robust configuration schemes.

Use `bin/start_sample_server.sh` to easily start serving on localhost:2587.

`CLEVER_CLIENT_ID=abc CLEVER_CLIENT_SECRET=xyz DISTRICT_ID=hjkl ./bin/start_sample_server.sh`

### Tests
To run this example's tests, you will need to install the phpunit and http-mock.

Installing these dependencies is easy with [Composer](https://getcomposer.org/).

`composer install`

To then execute the tests:

`./vendor/bin/phpunit test`
