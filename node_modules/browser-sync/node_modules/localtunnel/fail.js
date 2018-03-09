var http = require('http');
var fs = require('fs');
var https = require('https');
var url = require('url');
var assert = require('assert');

var localtunnel = require('./');

var server = http.createServer();
server.on('request', function(req, res) {
    console.log('request', req.headers.host);
    req.on('data', (chunk) => {
        console.log(chunk);

        process.exit(process.pid, 'SIGSEGV');

    });
});

server.listen(8081, function() {
    var port = server.address().port;
    console.log('local http on:', port);
    create_tunnel(port);
});

function create_tunnel(port) {
    const opt = {
        subdomain: 'foobar',
        host: 'http://local.dev:8080',
    };

    localtunnel(port, opt, function(err, tunnel) {
        assert.ifError(err);
        console.log(tunnel.url);
        console.log('ready');
    });
}
