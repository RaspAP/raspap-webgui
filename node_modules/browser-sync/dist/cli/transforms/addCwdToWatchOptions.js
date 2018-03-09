"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
function addCwdToWatchOptions(incoming) {
    return incoming.updateIn(['watchOptions', 'cwd'], function (watchCwd) {
        return watchCwd || incoming.get('cwd');
    });
}
exports.addCwdToWatchOptions = addCwdToWatchOptions;
//# sourceMappingURL=addCwdToWatchOptions.js.map