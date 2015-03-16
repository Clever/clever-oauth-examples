# Bell Schedule - A simple demo of the Login With Clever button. 

## To install:
`npm install`

## To run:

Using account credentials with read:sis scope:
`PORT=5000 APP_URL=http://wherever.this.is.deployed CLIENT_ID=YourClientID CLIENT_SECRET=YourClientSecret node server.js`

The app utilizes an in-memory hash called DISTRICT_DATA to load a map of district IDs and district tokens your app has access to upon starting the server (district IDs being keys, district tokens being values).  

Once a user logs in by selecting their school via Clever Instant Login, the app's redirect_uri (/oauth) will exchange a code, along with a client_id and client_secret, for a user's token.  That token is used to access the `api.clever.com/me` endpoint to retrieve a district ID (along with other information) to determine what district the user belongs to (hence which district token to use to pull down the user's roster data).

## Steps to demo:

1. Click the Login With Clever Button
2. Paste district ID 544982fbc7da940010000036 into the District picker to select a demo district.
3. Use username 'aaronm38' and password 'dah633Veuj' to log in.

