const { src, dest, parallel, series, watch } = require('gulp');
const postcss        = require('gulp-postcss');
const gulpnano       = require('gulp-cssnano');
const gutil          = require('gulp-util');
const concat         = require('gulp-concat');
const rename         = require("gulp-rename");
const flatten        = require("gulp-flatten");
const header         = require("gulp-header");
const autoprefixer   = require('autoprefixer');
const precss         = require('precss');
const colorfunctions = require('postcss-color-function');
const cssnano        = require('cssnano');

const sourcePath = '/apps/**/postcss/';
const destinationPath = '../public/css';

// using data from package.json
const pkg = require('./package.json');
const banner = ['/**',
// TODO: change this to your name
        '* Francois Dupras 2021 (francois.dupras@canada.ca)',
        '* <%= pkg.name %>',
        '* @version v<%= pkg.version %>',
        '* @license <%= pkg.license %>',
        '* ',
// TODO: and type your own message
        '* This is a CSS file made with PostCSS and PreCSS.',
        '* The source file in each /apps/[modulename]/postcss',
        '*/',
    ''].join('\n');


function css(done) {
    src([sourcePath+'*.css', '!'+sourcePath+'_*.css'])
        .pipe(postcss([
            precss(),
            autoprefixer(),
            colorfunctions()
        ]))
        .on('error', gutil.log)
        // in HTTP2 we should not be using only one stylesheets, we should use many small files
        //.pipe(concat('stylesheet.css'))
        .pipe(flatten())
        .pipe(header(banner, { pkg : pkg }))
        .pipe(rename({
            extname: ".css"
        }))
        .pipe(dest(destinationPath, true))
        .pipe(rename({
            extname: ".min.css"
        }))
        .pipe(gulpnano({
            zindex:false,
            discardUnused:false,
            reduceIdents:false,
            mergeIdents:false
        }))
        .pipe(header(banner, { pkg : pkg }))
        .pipe(dest(destinationPath, true))
    ;
    done();
}

const myWatch = (done) => {
    watch("./apps/**/postcss/*.css")
        .on('change', series(css))
        .on('unlink', series(css))
    ;
    done();
}


exports.css = css;
exports.default = series(css, myWatch);


