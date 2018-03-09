"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var immutable_1 = require("immutable");
var cli_options_1 = require("../cli-options");
var _ = require("../../lodash.custom");
function handleExtensionsOption(incoming) {
    var value = incoming.get('extensions');
    if (_.isString(value)) {
        var split = cli_options_1.explodeFilesArg(value);
        if (split.length) {
            return incoming.set('extensions', immutable_1.List(split));
        }
    }
    if (immutable_1.List.isList(value)) {
        return incoming.set('extensions', value);
    }
    return incoming;
}
exports.handleExtensionsOption = handleExtensionsOption;
//# sourceMappingURL=handleExtensionsOption.js.map