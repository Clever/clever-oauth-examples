/**
 * Dependencies
 */
var express = require('express');
var serveStatic = require('serve-static');
var expressHbs = require('express-handlebars');

var session = require('express-session')
var request = require('request');
var crypto = require('crypto');
//

/**
 * Useful Constants
 */
var PORT = parseInt(process.env.PORT) || 5000;
var APP_URL = process.env.APP_URL || 'http://localhost:' + PORT;

var CLIENT_ID = process.env.CLIENT_ID;
var CLIENT_SECRET = process.env.CLIENT_SECRET;

var API_PREFIX = 'https://api.clever.com'
var OAUTH_TOKEN_URL = 'https://clever.com/oauth/tokens'

// A mapping of district IDs to district tokens
var DISTRICT_DATA = {};
//

/**
 * App and middleware
 */
var app = express();
app.use(serveStatic(__dirname + '/public'));
app.engine('handlebars', expressHbs());
app.set('view engine', 'handlebars');
app.use(session({secret: 'somekindasecret'}));
//

/**
 * A helper function to make external REST requests.
 * @param {hash} option - options hash passed to the request lib
 * @param {function} cb - A callback function with err, body as params
 */
var makeRequest = function (options, cb){
    request(options, function(err, response, body){
        if(!err){            
            if(response.statusCode != 200){
                var errorMsg = body['error'];
                console.error('Non-200 status code: ', response.statusCode, ' with error ' + errorMsg);
                cb(errorMsg);
            }else{            
                cb(null, body);
            }
        }else{
            console.error('Something broke: ' + err);            
            cb(err);
        }
    });
};

//Load a map of district IDs and corresponding tokens.
var options = {
    'url': OAUTH_TOKEN_URL + '/?owner_type=district',
    'method': 'GET',        
    'headers' : {
        'Authorization': 'Basic ' + new Buffer(CLIENT_ID + ':' + CLIENT_SECRET).toString('base64')
    }
}
makeRequest(options, function(err, result){
    if(!err){
        var districts = JSON.parse(result)['data'];
        for(var i =0; i < districts.length; i++){                        
            var id = districts[i]['owner']['id'];
            var token = districts[i]['access_token'];
            DISTRICT_DATA[id] = token;        
        }    
    }else{
        console.log("Unable to retrieve district tokens: ", err);
    }    
});

/**
 * Homepage
 */
app.get('/', function(req, res){
    res.render('index', {
        'redirect_uri': encodeURIComponent(APP_URL + '/oauth'),
        'client_id': CLIENT_ID        
    });    
});

/**
 * OAuth 2.0 endpoint
 */
app.get('/oauth', function(req, res){        
    if(!req.query.code){
        res.redirect('/');
    } else if(!req.query.state){
        req.session.state = crypto.randomBytes(32).toString("hex");
        var url = "https://clever.com/oauth/authorize?response_type=code&redirect_uri=" + encodeURIComponent(APP_URL + '/oauth') + "&client_id=" + CLIENT_ID + "&state=" + req.session.state;
        res.redirect(url);
    } else if(req.session.state != req.query.state){
        var err = "Bad state"
        console.error('Something broke: ' + err);
        res.status(500).send(err);
    }else{
        var body = {
            'code': req.query.code,
            'grant_type': 'authorization_code',
            'redirect_uri': APP_URL + '/oauth'
        };

        var options = {
            'url': OAUTH_TOKEN_URL,
            'method': 'POST',
            'json': body,            
            'headers' : {
                'Authorization': 'Basic ' + new Buffer(CLIENT_ID + ':' + CLIENT_SECRET).toString('base64')
            }
        }

        makeRequest(options, function(err, result){
            if(!err){                                
                var token = result['access_token'];
                var options = {
                    'url': API_PREFIX + '/me',
                    'json': true,            
                    'headers' : {
                        'Authorization': 'Bearer ' + token
                    }
                }
                makeRequest(options, function(err, result){
                    if(!err){                        
                        //Store the user data returned from Clever in a 'user' session variable and redirect to the app
                        req.session.user = result['data'];                        
                        res.redirect('/app');
                    }else{
                        console.error('Something broke: ' + err);
                        res.status(500).send(err);
                    }
                });                
            }else{
                console.error('Something broke: ' + err);
                res.status(500).send(err);
            }
        });        
    }    
});

/**
 * The main app!
 */
app.get('/app', function(req, res){
    if(!req.session.user){
        res.redirect('/');  //If we're not logged in, redirect to the homepage
    }else{
        var userType = req.session.user.type + 's'; //studentS vs teacherS        
        var options = {
            'url': API_PREFIX + '/v1.1/' + userType + '/' + req.session.user.id + '/sections',
            'json': true,            
            'headers' : {
                //Use a district token to pull roster information (instead of a user token)
                'Authorization': 'Bearer ' + DISTRICT_DATA[req.session.user.district] 
            }
        }
        
        makeRequest(options, function(err, result){            
            if(!err){                
                var data = result['data'];
                res.render('schedule', {
                    'data': data.sort(function(a, b) {
                        var x = parseInt(a['data']['period']); var y = parseInt(b['data']['period']);                
                        return ((x < y) ? -1 : ((x > y) ? 1 : 0));
                    }),
                    'name': req.session.user.name
                });
            }else{
                console.error('Something broke: ' + err);
                res.status(500).send(err);
            }
        });
    }    
});

/**
 * A simple logout route.
 */
app.get('/logout', function(req, res){
    if(!req.session.user){
        res.redirect('/');  //If we're not logged in, redirect to the homepage
    }else{
        delete req.session.user;
        res.redirect('/');
    }    
});

/**
 * Fire up the server!
 */
app.listen(PORT, function() {
  console.log('Bell Schedule Demo now running on port ' + PORT);
});
