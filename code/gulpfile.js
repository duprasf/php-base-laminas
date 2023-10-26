const { watch, src, dest } = require('gulp');
const path = require('path');
const concat = require('gulp-concat');
const terser = require('gulp-terser');
const rename = require('gulp-rename');
const sourcemaps = require('gulp-sourcemaps');
const postcss = require('gulp-postcss');
const cssImport = require('postcss-import');
const partialImport = require('postcss-partial-import');
const colorFunction = require('postcss-color-function');
const colorMod = require('postcss-color-mod-function');
const simpleVar = require('postcss-simple-vars');
const discardComments = require('postcss-discard-comments');
const customMedia = require('postcss-custom-media');
const nested = require('postcss-nested');
const each = require('postcss-each');
const eachVariables = require('postcss-each-variables');
const cssFor = require('postcss-for');
const fs = require("fs");

function js() {
    fs.readdirSync("apps/").forEach(folder => {
        if(fs.lstatSync("apps/"+folder).isDirectory() && fs.existsSync('apps/'+folder+'/source/js')) {
            src('apps/'+folder+'/source/js/*.js',{ sourcemaps: true })
                .pipe(sourcemaps.init())
                .pipe(concat('scripts.js'))
                .pipe(sourcemaps.write())
                .pipe(dest('apps/'+folder+'/public/js'),{ sourcemaps: true })
                .pipe(terser())
                .pipe(rename({ extname: '.min.js' }))
                .pipe(sourcemaps.write())
                .pipe(dest('apps/'+folder+'/public/js'),{ sourcemaps: true })
            ;
        }
    });
};

function css() {
    let plugins = [
        cssImport,
        partialImport({ prefix: '_' }),
        colorFunction,
        colorMod,
        simpleVar,
        discardComments,
        customMedia,
        nested,
        each,
        eachVariables,
        cssFor
    ];

    fs.readdirSync("apps/").forEach(folder => {
        if(fs.lstatSync("apps/"+folder).isDirectory() && fs.existsSync('apps/'+folder+'/source/postcss/main.pcss')) {
            src('apps/'+folder+'/source/postcss/main.pcss')
                .pipe(postcss(plugins))
                .pipe(rename({ extname: '.css' }))
                .pipe(dest('apps/'+folder+'/public/css'))
            ;
        }
    });
};

function both(cb) {
    js();
    css();

    cb();
}

function watchFn() {
    watch(['apps/*/source/js/*.js', 'apps/*/source/postcss/*.pcss'], both);
};
exports.default = watchFn;
exports.both = both;
