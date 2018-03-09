"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var immutable_1 = require("immutable");
var addToFilesOption_1 = require("./transforms/addToFilesOption");
var addDefaultIgnorePatterns_1 = require("./transforms/addDefaultIgnorePatterns");
var copyCLIIgnoreToWatchOptions_1 = require("./transforms/copyCLIIgnoreToWatchOptions");
var handleExtensionsOption_1 = require("./transforms/handleExtensionsOption");
var handleFilesOption_1 = require("./transforms/handleFilesOption");
var handleGhostModeOption_1 = require("./transforms/handleGhostModeOption");
var handlePortsOption_1 = require("./transforms/handlePortsOption");
var handleProxyOption_1 = require("./transforms/handleProxyOption");
var handleServerOption_1 = require("./transforms/handleServerOption");
var appendServerIndexOption_1 = require("./transforms/appendServerIndexOption");
var appendServerDirectoryOption_1 = require("./transforms/appendServerDirectoryOption");
var addCwdToWatchOptions_1 = require("./transforms/addCwdToWatchOptions");
var options_1 = require("../options");
var _ = require("../lodash.custom");
var defaultConfig = require("../default-config");
var immDefs = immutable_1.fromJS(defaultConfig);
/**
 * @param {Object} input
 * @returns {Map}
 */
function merge(input) {
    var merged = immDefs.mergeDeep(input);
    var transforms = [
        addToFilesOption_1.addToFilesOption,
        addCwdToWatchOptions_1.addCwdToWatchOptions,
        addDefaultIgnorePatterns_1.addDefaultIgnorePatterns,
        copyCLIIgnoreToWatchOptions_1.copyCLIIgnoreToWatchOptions,
        handleServerOption_1.handleServerOption,
        appendServerIndexOption_1.appendServerIndexOption,
        appendServerDirectoryOption_1.appendServerDirectoryOption,
        handleProxyOption_1.handleProxyOption,
        handlePortsOption_1.handlePortsOption,
        handleGhostModeOption_1.handleGhostModeOption,
        handleFilesOption_1.handleFilesOption,
        handleExtensionsOption_1.handleExtensionsOption,
        options_1.setMode,
        options_1.setScheme,
        options_1.setStartPath,
        options_1.setProxyWs,
        options_1.setServerOpts,
        options_1.liftExtensionsOptionFromCli,
        options_1.setNamespace,
        options_1.fixSnippetIgnorePaths,
        options_1.fixSnippetIncludePaths,
        options_1.fixRewriteRules,
        options_1.setMiddleware,
        options_1.setOpen,
        options_1.setUiPort
    ];
    var output = transforms.reduce(function (acc, item) {
        return item.call(null, acc);
    }, merged);
    // console.log(output.toJSON());
    return output;
}
exports.merge = merge;
/**
 * @param string
 */
function explodeFilesArg(string) {
    return string.split(",").map(function (item) { return item.trim(); });
}
exports.explodeFilesArg = explodeFilesArg;
/**
 * @param value
 * @returns {{globs: Array, objs: Array}}
 */
function makeFilesArg(value) {
    var globs = [];
    var objs = [];
    if (_.isString(value)) {
        globs = globs.concat(explodeFilesArg(value));
    }
    if (immutable_1.List.isList(value) && value.size) {
        value.forEach(function (value) {
            if (_.isString(value)) {
                globs.push(value);
            }
            else {
                if (immutable_1.Map.isMap(value)) {
                    objs.push(value);
                }
            }
        });
    }
    return {
        globs: globs,
        objs: objs
    };
}
exports.makeFilesArg = makeFilesArg;
//# sourceMappingURL=cli-options.js.map