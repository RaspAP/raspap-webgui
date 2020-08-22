"use strict";

// Load plugins
const autoprefixer = require("gulp-autoprefixer");
const browsersync = require("browser-sync").create();
const cleanCSS = require("gulp-clean-css");
const del = require("del");
const gulp = require("gulp");
const header = require("gulp-header");
const merge = require("merge-stream");
const plumber = require("gulp-plumber");
const rename = require("gulp-rename");
const sass = require("gulp-sass");
const uglify = require("gulp-uglify");

// Load package.json for banner
const pkg = require('./package.json');

// Set the banner content
const banner = ['/*!\n',
  ' * RaspAP - <%= pkg.title %> v<%= pkg.version %> (<%= pkg.homepage %>)\n',
  ' * Copyright 2013-' + (new Date()).getFullYear(), ' <%= pkg.author %>\n',
  ' * Licensed under <%= pkg.license %> (https://github.com/raspap-webgui/<%= pkg.name %>/blob/master/LICENSE)\n',
  ' */\n',
  '\n'
].join('');

// BrowserSync
function browserSync(done) {
  browsersync.init({
    server: {
      baseDir: "./"
    },
    port: 3000
  });
  done();
}

// BrowserSync reload
function browserSyncReload(done) {
  browsersync.reload();
  done();
}

// Clean vendor
function clean() {
  return del(["./dist/"]);
}

// Bring third party dependencies from node_modules into dist directory
function modules() {
  // Bootstrap JS
  var bootstrapJS = gulp.src('./node_modules/startbootstrap-sb-admin-2/vendor/bootstrap/js/*')
    .pipe(gulp.dest('./dist/bootstrap/js'));
  // Bootstrap SCSS
  var bootstrapSCSS = gulp.src('./node_modules/startbootstrap-sb-admin-2/vendor/bootstrap/scss/*')
    .pipe(sass().on('error', sass.logError))
    .pipe(gulp.dest('./dist/bootstrap/css'));
  // Chart JS
  var chartJS = gulp.src('./node_modules/startbootstrap-sb-admin-2/vendor/chart.js/*')
    .pipe(gulp.dest('./dist/chart.js'));
  // dataTables
  var dataTables = gulp.src('./node_modules/startbootstrap-sb-admin-2/vendor/datatables/*')
    .pipe(gulp.dest('./dist/datatables'));
  // Font Awesome
  var fontAwesome = gulp.src('./node_modules/startbootstrap-sb-admin-2/vendor/fontawesome-free/**/*')
    .pipe(gulp.dest('./dist/fontawesome-free'));
  // jQuery Easing
  var jqueryEasing = gulp.src('./node_modules/startbootstrap-sb-admin-2/vendor/jquery-easing/*.js')
    .pipe(gulp.dest('./dist/jquery-easing'));
  // jQuery
  var jquery = gulp.src('./node_modules/startbootstrap-sb-admin-2/vendor/jquery/*')
    .pipe(gulp.dest('./dist/jquery'));
  // SB Admin 2 JS
  var sbadmin2JS = gulp.src('./node_modules/startbootstrap-sb-admin-2/js/*')
    .pipe(gulp.dest('./dist/sb-admin-2/js'));
  // SB Admin2 CSS
  var sbadmin2CSS = gulp.src('./node_modules/startbootstrap-sb-admin-2/css/*')
    .pipe(gulp.dest('./dist/sb-admin-2/css'));
  // Huebee
  var huebee = gulp.src('./node_modules/huebee/dist/*')
    .pipe(gulp.dest('./dist/huebee'));
  return merge(bootstrapJS, bootstrapSCSS, chartJS, dataTables, fontAwesome, jquery, jqueryEasing, sbadmin2JS, sbadmin2CSS, huebee);
}

// CSS task
function css() {
  return gulp
    .src("./scss/**/*.scss")
    .pipe(plumber())
    .pipe(sass({
      outputStyle: "expanded",
      includePaths: "./node_modules",
    }))
    .on("error", sass.logError)
    .pipe(autoprefixer({
      cascade: false
    }))
    .pipe(header(banner, {
      pkg: pkg
    }))
    .pipe(gulp.dest("./css"))
    .pipe(rename({
      suffix: ".min"
    }))
    .pipe(cleanCSS())
    .pipe(gulp.dest("./css"))
    .pipe(browsersync.stream());
}

// JS task
function js() {
  return gulp
    .src([
      './js/*.js',
      '!./js/*.min.js',
    ])
    .pipe(uglify())
    .pipe(header(banner, {
      pkg: pkg
    }))
    .pipe(rename({
      suffix: '.min'
    }))
    .pipe(gulp.dest('./js'))
    .pipe(browsersync.stream());
}

// Watch files
function watchFiles() {
  gulp.watch("./scss/**/*", css);
  gulp.watch(["./js/**/*", "!./js/**/*.min.js"], js);
  gulp.watch("./**/*.html", browserSyncReload);
}

// Define complex tasks
const vendor = gulp.series(clean, modules);
const build = gulp.series(vendor, gulp.parallel(css, js));
const watch = gulp.series(build, gulp.parallel(watchFiles, browserSync));

// Export tasks
exports.css = css;
exports.js = js;
exports.clean = clean;
exports.vendor = vendor;
exports.build = build;
exports.watch = watch;
exports.default = build;
