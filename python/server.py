from bottle import route, run, request, template

import base64
import json
import os
import requests
import urllib

CLIENT_ID = os.environ['CLIENT_ID']
CLIENT_SECRET = os.environ['CLIENT_SECRET']

REDIRECT_URI = 'http://localhost:8080/oauth'
CLEVER_OAUTH_URL = 'https://clever.com/oauth/tokens'
CLEVER_API_BASE = 'https://api.clever.com'

DISTRICT_ID = os.environ['DISTRICT_ID']

@route('/')
def index():
    encoded_string = urllib.urlencode({
        'response_type': 'code',
        'redirect_uri': REDIRECT_URI,
        'client_id': CLIENT_ID,
        'scope': 'read:user_id read:sis',
        'district_id': DISTRICT_ID        
    })
    return template("<h1>Sign In!<br/><br/> \
        <a href='https://clever.com/oauth/authorize?" + encoded_string +
        "'><img src='http://assets.clever.com/sign-in-with-clever/sign-in-with-clever-small.png'/></a></h1>"    
    )


@route('/oauth')
def index():
    code = request.query.code
    scope = request.query.scope

    payload = { 
    'code': code,
        'grant_type': 'authorization_code',
        'redirect_uri': REDIRECT_URI
    }

    headers = {
    	'Authorization': 'Basic ' + base64.b64encode(CLIENT_ID + ':' + CLIENT_SECRET),
        'Content-Type': 'application/json'
    }
    
    resp = requests.post(CLEVER_OAUTH_URL, data=json.dumps(payload), headers=headers).json()
    token = resp['access_token']

    bearer_headers = {
        'Authorization': 'Bearer ' + token
    }

    result = requests.get(CLEVER_API_BASE + '/me', headers=bearer_headers).json()

    nameObject = result['data']['name']
    return template("You are now logged in as {{name}}", name=nameObject['first'] + ' ' + nameObject['last'])

run(host='localhost', port=8080)