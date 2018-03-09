"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var immutable_1 = require("immutable");
function addToFilesOption(incoming) {
    if (!incoming.get("watch")) {
        return incoming;
    }
    var serverPaths = [];
    var fromServeStatic = incoming
        .get("serveStatic", immutable_1.List([]))
        .toArray();
    var ssPaths = fromServeStatic
        .reduce(function (acc, ss) {
        if (typeof ss === "string") {
            return acc.concat(ss);
        }
        if (ss.dir && typeof ss.dir === "string") {
            return acc.concat(ss);
        }
        return acc;
    }, []);
    ssPaths.forEach(function (p) { return serverPaths.push(p); });
    var server = incoming.get("server");
    if (server) {
        if (server === true) {
            serverPaths.push(".");
        }
        if (typeof server === "string") {
            serverPaths.push(server);
        }
        if (immutable_1.List.isList(server) &&
            server.every(function (x) { return typeof x === "string"; })) {
            server.forEach(function (s) { return serverPaths.push(s); });
        }
        if (immutable_1.Map.isMap(server)) {
            var baseDirProp = server.get("baseDir");
            var baseDirs = immutable_1.List([]).concat(baseDirProp).filter(Boolean);
            baseDirs.forEach(function (s) { return serverPaths.push(s); });
        }
    }
    var output = incoming.update("files", function (files) {
        return immutable_1.List([])
            .concat(files, serverPaths)
            .filter(Boolean);
    });
    return output;
}
exports.addToFilesOption = addToFilesOption;
//# sourceMappingURL=addToFilesOption.js.map