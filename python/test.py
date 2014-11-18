import json
import os
import unittest

from bottle import request, BottleException, HTTPResponse
from requests import get, post
import responses

#Set environment variables before importing server
os.environ['CLIENT_ID'] = 'foo'
os.environ['CLIENT_SECRET'] = 'foo'
os.environ['DISTRICT_ID'] = 'foo'

import server

ACCESS_TOKEN_RESPONSE = {
  "access_token": "abc123"
}

NAME_RESPONSE = {
  "data": {
    "name": {
      "first": "Aaron",
      "middle": "B",
      "last": "McClure"
    }
  }
}

class TestServer(unittest.TestCase):
  def testIndex(self):
    assert 'https://clever.com/oauth/authorize' in server.index()

  @responses.activate
  def testOauth(self):

    responses.add(responses.POST, 'https://clever.com/oauth/tokens',
                      body=json.dumps(ACCESS_TOKEN_RESPONSE), status=200,
                      content_type='application/json')
    responses.add(responses.GET, 'https://api.clever.com/me',
                      body=json.dumps(NAME_RESPONSE), status=200,
                      content_type='application/json')

    server.request.query['code'] = 'foo'
    server.request.environ['beaker.session'] = {}

    # Bottle redirects are raised BottleExceptions, for some reason.
    try:
      server.oauth()
    except BottleException, e:
      assert type(e) == HTTPResponse

  def testApp(self):
    server.request.environ['beaker.session'] = {}
    assert "You must be logged in" in server.app()
    server.request.environ['beaker.session'] = {
      'nameObject': NAME_RESPONSE['data']['name']
    }
    assert "Aaron McClure" in server.app()


if __name__ == '__main__':
    unittest.main()
