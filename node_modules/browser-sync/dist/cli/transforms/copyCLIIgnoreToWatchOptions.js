"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var immutable_1 = require("immutable");
function copyCLIIgnoreToWatchOptions(incoming) {
    if (!incoming.get("ignore")) {
        return incoming;
    }
    return incoming.updateIn(["watchOptions", "ignored"], function (ignored) {
        var userIgnore = immutable_1.List([]).concat(incoming.get("ignore"));
        return ignored.concat(userIgnore);
    });
}
exports.copyCLIIgnoreToWatchOptions = copyCLIIgnoreToWatchOptions;
//# sourceMappingURL=copyCLIIgnoreToWatchOptions.js.map