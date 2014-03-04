#!/usr/bin/env python
# vim: set ts=4 sw=4 expandtab:

import BaseHTTPServer
import SocketServer
import base64
import json
import os.path
import ssl
import sys
import urllib
import urllib2
import urlparse

client_secret = 'x'
redirect = 'https://localhost:8000/oauth'

CLEVER_API_BASE = 'https://api.clever.com'

class RequestHandler(BaseHTTPServer.BaseHTTPRequestHandler):
    def do_GET(self):
        query = urlparse.urlparse(self.path).query
        try:
          params = urlparse.parse_qs(query, strict_parsing=True)
        except ValueError:
          self.send_response(400)
          self.end_headers()
          print 'failed to parse', self.path
          return
        for k, v in params.iteritems():
            params[k] = v[0]
        self.send_response(200)
        self.send_header('Content-Type', 'text/plain')
        self.end_headers()

        code = params['code']
        self.wfile.write('Code is {}\n'.format(code))
        self.wfile.write('Redeeming code for token.\n')
        req = urllib2.Request(CLEVER_API_BASE + '/oauth/token', urllib.urlencode({
            'code': code,
            'grant_type': 'authorization_code',
            'redirect_uri': redirect,
        }), {
            'Authorization': base64.b64encode(client_secret + ':') # use client_secret as basic auth password
        })
        try:
            resp = json.load(urllib2.urlopen(req))
        except urllib2.HTTPError as e:
            self.wfile.write('error:\n' + e.read())
            return

        access_token = resp['access_token']
        self.wfile.write('Access token is {}\n'.format(access_token))
        self.wfile.write('Using token to look up user information.\n')
        req = urllib2.Request(CLEVER_API_BASE + '/me', headers={
            'Authorization': 'Bearer ' + access_token
        })
        try:
            resp = urllib2.urlopen(req).read()
        except urllib2.HTTPError as e:
            self.wfile.write('error:\n' + e.read())
            return
        self.wfile.write("Here's some information about the user:\n");
        self.wfile.write(resp);

class ThreadedHTTPServer(SocketServer.ThreadingMixIn, BaseHTTPServer.HTTPServer):
    pass

try:
    port = 8000
    keyfile = 'ssl.key'
    certfile = 'ssl.crt'
    if not os.path.exists(keyfile) or not os.path.exists(certfile):
        print 'need both', keyfile, certfile
        sys.exit()
    server = ThreadedHTTPServer(('0.0.0.0', port), RequestHandler)
    server.socket = ssl.wrap_socket(server.socket, certfile=certfile, keyfile=keyfile, server_side=True)
    print 'listening on :{}'.format(port)
    server.timeout = 5
    server.serve_forever()
except KeyboardInterrupt:
    server.socket.close()
