"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var immutable_1 = require("immutable");
var cli_options_1 = require("../cli-options");
function handleFilesOption(incoming) {
    var value = incoming.get('files');
    var namespaces = {
        core: {
            globs: [],
            objs: []
        }
    };
    var processed = cli_options_1.makeFilesArg(value);
    if (processed.globs.length) {
        namespaces.core.globs = processed.globs;
    }
    if (processed.objs.length) {
        namespaces.core.objs = processed.objs;
    }
    return incoming.set('files', immutable_1.fromJS(namespaces));
}
exports.handleFilesOption = handleFilesOption;
//# sourceMappingURL=handleFilesOption.js.map