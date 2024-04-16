const { watch, src, dest } = require('gulp');
const path = require('path');
const concat = require('gulp-concat');
const terser = require('gulp-terser');
const rename = require('gulp-rename');
const sourcemaps = require('gulp-sourcemaps');
const sri = require('gulp-sri');
const plumber = require('gulp-plumber');
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

const camelToDash = str => str.replace(/([a-z])?([A-Z])/g, (match, p1,p2) => (p1?p1+'-':'')+p2.toLowerCase() );

function js(cb) {
    fs.readdirSync("apps/").forEach(folder => {
        if(fs.lstatSync("apps/"+folder).isDirectory() && fs.existsSync('apps/'+folder+'/source/js')) {
            src('apps/'+folder+'/source/js/*.js',{ sourcemaps: true })
                .pipe(plumber()) // error handling
                .pipe(sourcemaps.init())
                .pipe(concat('scripts.js'))
                .pipe(sourcemaps.write())
                .pipe(rename({ basename: folder }))
                .pipe(dest('apps/'+folder+'/public/js'),{ sourcemaps: true })
                .pipe(terser())
                .pipe(rename({ extname: '.min.js' }))
                .pipe(sourcemaps.write())
                .pipe(dest('apps/'+folder+'/public/js'),{ sourcemaps: true })
                .pipe(sri({
                    "algorithms":["sha384"],
                    "formatter": json => {
                        let newJson={};
                        for(let str in json) {
                            if(str.indexOf('apps/'+folder+'/public') >= 0) {
                                newJson[str.replace('apps/'+folder+'/public', camelToDash(folder))] = json[str];
                            } else if(str.indexOf('module/'+folder+'/public') >= 0) {
                                newJson[str.replace('module/'+folder+'/public', camelToDash(folder))] = json[str];
                            }
                        }
                        return JSON.stringify(newJson);
                    }
                }))
                .pipe(rename({ basename: folder }))
                .pipe(dest('apps/'+folder+'/public/js'))
            ;
        }
    });

    if(cb) {
        cb();
    }
};

function css(cb) {
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

    if(cb) {
        cb();
    }
};

function both(cb) {
    js();
    css();

    cb();
}

function watchFn() {
    watch(['apps/*/source/js/*.js', 'apps/*/source/postcss/**/*.pcss'], both);
};
exports.default = watchFn;
exports.both = both;
exports.js = js;
exports.css = css;
