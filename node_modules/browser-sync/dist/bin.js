#!/usr/bin/env node
"use strict";
var __assign = (this && this.__assign) || Object.assign || function(t) {
    for (var s, i = 1, n = arguments.length; i < n; i++) {
        s = arguments[i];
        for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
            t[p] = s[p];
    }
    return t;
};
Object.defineProperty(exports, "__esModule", { value: true });
var startOpts = require("../cli-options/opts.start.json");
var reloadOpts = require("../cli-options/opts.reload.json");
var recipeOpts = require("../cli-options/opts.recipe.json");
var pkg = require("../package.json");
var utils = require("./utils");
var path_1 = require("path");
var fs_1 = require("fs");
var logger_1 = require("./logger");
var eazy_logger_1 = require("eazy-logger");
var BsErrorLevels;
(function (BsErrorLevels) {
    BsErrorLevels["Fatal"] = "Fatal";
})(BsErrorLevels = exports.BsErrorLevels || (exports.BsErrorLevels = {}));
var BsErrorTypes;
(function (BsErrorTypes) {
    BsErrorTypes["PathNotFound"] = "PathNotFound";
})(BsErrorTypes = exports.BsErrorTypes || (exports.BsErrorTypes = {}));
/**
 * Handle cli input
 */
if (!module.parent) {
    runFromCli();
}
function runFromCli() {
    var yargs = require("yargs")
        .command("start", "Start the server")
        .command("init", "Create a configuration file")
        .command("reload", "Send a reload event over HTTP protocol")
        .command("recipe", "Generate the files for a recipe")
        .version(function () { return pkg.version; })
        .epilogue([
        "For help running a certain command, type <command> --help",
        "  $0 start --help",
        "",
        "You can run a static server by providing a path(s) directly",
        "  $0 app/src app/tmp",
        "",
        "If the directory contains a 'index.html' file, you can omit any input",
        "  $0",
        "",
        "You can run the proxy in this manner too",
        "  $0 https://example.com",
        "",
        "To run a proxy, whilst also serving static files",
        eazy_logger_1.compile("  $0 https://example.com htdocs/themes/example")
    ].join("\n"));
    var argv = yargs.argv;
    var input = argv._;
    var command = input[0];
    var valid = ["start", "init", "reload", "recipe"];
    if (valid.indexOf(command) > -1) {
        return handleIncoming(command, yargs.reset());
    }
    if (input.length) {
        return handleNoCommand(argv, input, yargs);
    }
    if (fs_1.existsSync("index.html")) {
        return handleNoCommand(argv, ["."], yargs);
    }
    yargs.showHelp();
}
/**
 * Feature: If no command was specified, try to do the 'right thing'
 *
 * If paths were given, start the server
 *          eg: browser-sync app/code app/design
 * is equal to: browser-sync start --server app/code app/design
 *
 *           eg: browser-sync http://example.com
 * is equal to: browser-sync start --proxy http://example.com
 *
 *           eg: browser-sync http://example.com themes/example
 * is equal to: browser-sync start --proxy http://example.com --ss themes/example
 *
 * @param argv
 * @param input
 * @returns {any}
 */
function handleNoCommand(argv, input, yargs) {
    var processed = processStart(yargs);
    var paths = input.map(function (path) {
        var resolved = path_1.resolve(path);
        var isUrl = /^https?:\/\//.test(path);
        return {
            isUrl: isUrl,
            userInput: path,
            resolved: resolved,
            errors: isUrl ? [] : pathErrors(path, resolved)
        };
    });
    var withErrors = paths.filter(function (item) { return item.errors.length; });
    var withoutErrors = paths.filter(function (item) { return item.errors.length === 0; });
    if (withErrors.length) {
        withErrors.forEach(function (item) {
            logger_1.logger.unprefixed("error", printErrors(item.errors));
        });
        return process.exit(1);
    }
    var serveStaticPaths = withoutErrors
        .filter(function (item) { return item.isUrl === false; })
        .map(function (item) { return item.resolved; });
    var urls = withoutErrors
        .filter(function (item) { return item.isUrl === true; })
        .map(function (item) { return item.userInput; });
    /**
     * If a URL was given, switch to proxy mode and use
     * any other paths as serveStatic options
     */
    if (urls.length) {
        var proxy = urls[0];
        var config_1 = __assign({}, processed, { proxy: proxy, serveStatic: serveStaticPaths });
        return handleCli({ cli: { flags: config_1, input: ["start"] } });
    }
    /**
     * if NO urls were given switch directly to server mode
     * @type {{server: {baseDir: any}}}
     */
    var config = __assign({}, processed, { server: { baseDir: serveStaticPaths } });
    return handleCli({ cli: { flags: config, input: ["start"] } });
}
/**
 * @param {{cli: object, [whitelist]: array, [cb]: function}} opts
 * @returns {*}
 */
function handleCli(opts) {
    opts.cb = opts.cb || utils.defaultCallback;
    var m = require("./cli/command." + opts.cli.input[0]);
    if (m.default) {
        return m.default(opts);
    }
    return m(opts);
}
exports.default = handleCli;
function processStart(yargs) {
    return yargs
        .usage("Usage: $0 start [options]")
        .options(startOpts)
        .example("$0 start -s app", "- Use the App directory to serve files")
        .example("$0 start -p www.bbc.co.uk", "- Proxy an existing website")
        .default('cwd', function () { return process.cwd(); })
        .help().argv;
}
/**
 * @param {string} command
 * @param {object} yargs
 * @param cwd
 */
function handleIncoming(command, yargs) {
    var out;
    if (command === "start") {
        out = processStart(yargs);
    }
    if (command === "init") {
        out = yargs
            .usage("Usage: $0 init")
            .example("$0 init")
            .default('cwd', function () { return process.cwd(); })
            .help().argv;
    }
    if (command === "reload") {
        out = yargs
            .usage("Usage: $0 reload")
            .options(reloadOpts)
            .example("$0 reload")
            .example("$0 reload --port 4000")
            .default('cwd', function () { return process.cwd(); })
            .help().argv;
    }
    if (command === "recipe") {
        out = yargs
            .usage("Usage: $0 recipe <recipe-name>")
            .option(recipeOpts)
            .example("$0 recipe ls", "list the recipes")
            .example("$0 recipe gulp.sass", "use the gulp.sass recipe")
            .default('cwd', function () { return process.cwd(); })
            .help().argv;
    }
    if (out.help) {
        return yargs.showHelp();
    }
    handleCli({ cli: { flags: out, input: out._ } });
}
function pathErrors(input, resolved) {
    if (!fs_1.existsSync(resolved)) {
        return [
            {
                type: BsErrorTypes.PathNotFound,
                level: BsErrorLevels.Fatal,
                errors: [
                    {
                        error: new Error("Path not found: " + input),
                        meta: function () {
                            return [
                                "Your Input:    {yellow:" + input + "}",
                                "CWD:           {yellow:" + process.cwd() + "}",
                                "Resolved to:   {yellow:" + resolved + "}"
                            ];
                        }
                    }
                ]
            }
        ];
    }
    return [];
}
function printErrors(errors) {
    return errors
        .map(function (error) {
        return [
            "Error Type:    {bold:" + error.type + "}",
            "Error Level:   {bold:" + error.level + "}",
            error.errors.map(function (item) {
                return [
                    "Error Message: " + item.error.message,
                    item.meta ? item.meta().join("\n") : ""
                ]
                    .filter(Boolean)
                    .join("\n");
            })
        ].join("\n");
    })
        .join("\n\n");
}
//# sourceMappingURL=bin.js.map