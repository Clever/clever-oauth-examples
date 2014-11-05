import os
import unittest

os.environ['CLIENT_ID'] = 'foo'
os.environ['CLIENT_SECRET'] = 'foo'
os.environ['DISTRICT_ID'] = 'foo'

import server

class TestServer(unittest.TestCase):

  def test_webapp_index(self):
    assert 'https://clever.com/oauth/authorize' in server.index()

if __name__ == '__main__':
    unittest.main()
