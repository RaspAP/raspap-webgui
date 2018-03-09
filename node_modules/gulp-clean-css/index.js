'use strict';

const applySourceMap = require('vinyl-sourcemaps-apply');
const CleanCSS = require('clean-css');
const path = require('path');
const PluginError = require('plugin-error');
const through = require('through2');

module.exports = function gulpCleanCSS(options, callback) {

  options = Object.assign(options || {});

  if (arguments.length === 1 && Object.prototype.toString.call(arguments[0]) === '[object Function]')
    callback = arguments[0];

  let transform = function (file, enc, cb) {

    if (!file || !file.contents)
      return cb(null, file);

    if (file.isStream()) {
      this.emit('error', new PluginError('gulp-clean-css', 'Streaming not supported!'));
      return cb(null, file);
    }

    if (file.sourceMap)
      options.sourceMap = JSON.parse(JSON.stringify(file.sourceMap));

    let contents = file.contents ? file.contents.toString() : '';
    let pass = {[file.path]: {styles: contents}};
    if (!options.rebaseTo && options.rebase !== false) {
      options.rebaseTo = path.dirname(file.path);
    }

    new CleanCSS(options).minify(pass, function (errors, css) {

      if (errors)
        return cb(errors.join(' '));

      if (typeof callback === 'function') {
        let details = {
          'stats': css.stats,
          'errors': css.errors,
          'warnings': css.warnings,
          'path': file.path,
          'name': file.path.split(file.base)[1]
        };

        if (css.sourceMap)
          details['sourceMap'] = css.sourceMap;

        callback(details);
      }

      file.contents = new Buffer(css.styles);

      if (css.sourceMap) {

        let map = JSON.parse(css.sourceMap);
        map.file = path.relative(file.base, file.path);
        map.sources = map.sources.map(function (src) {
          return path.relative(file.base, file.path)
        });

        applySourceMap(file, map);
      }

      cb(null, file);
    });
  };

  return through.obj(transform);
};