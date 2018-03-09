var http = require('http');
var url = require('url');
var fs = require('fs');

function make_request(uri) {
    var parsed = url.parse(uri);

    var opt = {
        host: parsed.hostname,
        port: parsed.port,
        method: 'POST',
        headers: {
            host: parsed.hostname
        },
        path: '/'
    };

    var req = http.request(opt, function(res) {
        res.setEncoding('utf8');
        var body = '';

        res.on('data', function(chunk) {
            body += chunk;

        });

        res.on('end', function() {
            console.log(body);
            //process.exit(0);
        });
    });
    req.on('error', (err) => {
        console.error(err);
    });

    const stream = fs.createReadStream('/Users/dz/Downloads/planet.earth.ii.s01e01.720p.hdtv.x264-c4tv.mkv')
    stream.pipe(req);
}

make_request('http://foobar.local.dev:8080/');
