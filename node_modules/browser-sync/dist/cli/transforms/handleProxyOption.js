"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var url = require("url");
var immutable_1 = require("immutable");
function handleProxyOption(incoming) {
    var value = incoming.get('proxy');
    var mw;
    var target;
    if (!value || value === true) {
        return incoming;
    }
    if (typeof value !== "string") {
        target = value.get("target");
        mw = value.get("middleware");
    }
    else {
        target = value;
        value = immutable_1.Map({});
    }
    if (!target.match(/^(https?):\/\//)) {
        target = "http://" + target;
    }
    var parsedUrl = url.parse(target);
    if (!parsedUrl.port) {
        parsedUrl.port = "80";
    }
    var out = {
        target: parsedUrl.protocol + "//" + parsedUrl.host,
        url: immutable_1.Map(parsedUrl)
    };
    if (mw) {
        out.middleware = mw;
    }
    var proxyOutput = value.mergeDeep(out);
    return incoming.set('proxy', proxyOutput);
}
exports.handleProxyOption = handleProxyOption;
//# sourceMappingURL=handleProxyOption.js.map