"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var immutable_1 = require("immutable");
function handleServerOption(incoming) {
    var value = incoming.get('server');
    if (value === false) {
        return incoming;
    }
    // server: true
    if (value === true) {
        var obj = {
            baseDir: ["./"]
        };
        return incoming.set('server', immutable_1.fromJS(obj));
    }
    // server: "./app"
    if (typeof value === "string") {
        var obj = {
            baseDir: [value]
        };
        return incoming.set('server', immutable_1.fromJS(obj));
    }
    if (immutable_1.List.isList(value)) {
        var obj = {
            baseDir: value
        };
        return incoming.set('server', immutable_1.fromJS(obj));
    }
    if (immutable_1.Map.isMap(value)) {
        var dirs = immutable_1.List([])
            .concat(value.get("baseDir", "./"))
            .filter(Boolean);
        var merged = value.merge({ baseDir: dirs });
        return incoming.set('server', merged);
    }
    return incoming;
}
exports.handleServerOption = handleServerOption;
//# sourceMappingURL=handleServerOption.js.map