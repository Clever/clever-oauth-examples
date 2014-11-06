import base64
import json
import os
import requests
import urllib

from bottle import app, redirect, request, route, run, template
from beaker.middleware import SessionMiddleware

CLIENT_ID = os.environ['CLIENT_ID']
CLIENT_SECRET = os.environ['CLIENT_SECRET']

if 'PORT' in os.environ:
    PORT = os.environ['PORT']
else:
    PORT = 2587

REDIRECT_URI = 'http://localhost:{port}/oauth'.format(port=PORT)
CLEVER_OAUTH_URL = 'https://clever.com/oauth/tokens'
CLEVER_API_BASE = 'https://api.clever.com'

DISTRICT_ID = os.environ['DISTRICT_ID']

# Use the bottle session middleware to store an object to represent a "logged in" state.
session_opts = {
    'session.type': 'memory',
    'session.cookie_expires': 300,
    'session.auto': True
}
myapp = SessionMiddleware(app(), session_opts)

@route('/')
def index():
    encoded_string = urllib.urlencode({
        'response_type': 'code',
        'redirect_uri': REDIRECT_URI,
        'client_id': CLIENT_ID,
        'scope': 'read:user_id read:sis',
        'district_id': DISTRICT_ID
    })
    return template("<h1>Login!<br/><br/> \
        <a href='https://clever.com/oauth/authorize?" + encoded_string +
        "'><img src='http://assets.clever.com/sign-in-with-clever/sign-in-with-clever-small.png'/></a></h1>"
    )

@route('/oauth')
def oauth():
    code = request.query.code
    scope = request.query.scope

    payload = {
        'code': code,
        'grant_type': 'authorization_code',
        'redirect_uri': REDIRECT_URI
    }

    headers = {
    	'Authorization': 'Basic {base64string}'.format(base64string = base64.b64encode(CLIENT_ID + ':' + CLIENT_SECRET)),
        'Content-Type': 'application/json'
    }

    response = requests.post(CLEVER_OAUTH_URL, data=json.dumps(payload), headers=headers).json()

    token = response['access_token']

    bearer_headers = {
        'Authorization': 'Bearer {token}'.format(token=token)
    }

    result = requests.get(CLEVER_API_BASE + '/me', headers=bearer_headers).json()

    nameObject = result['data']['name']
    session = request.environ.get('beaker.session')
    session['nameObject'] = nameObject

    redirect('/app')


@route('/app')
def app():
    session = request.environ.get('beaker.session')
    if 'nameObject' in session:
        nameObject = session['nameObject']
        return template("You are now logged in as {{name}}", name=nameObject['first'] + ' ' + nameObject['last'])
    else:
        return "You must be logged in to see this page! Click <a href='/'>here</a> to log in."


if __name__ == '__main__':
    run(app=myapp, host='localhost', port=PORT)

