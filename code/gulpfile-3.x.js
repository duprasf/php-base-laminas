var gulp     = require('gulp'),
    postcss  = require('gulp-postcss'),
    gulpnano = require('gulp-cssnano')
    gutil    = require('gulp-util'),
    concat   = require('gulp-concat'),
    rename   = require("gulp-rename")
;

var autoprefixer   = require('autoprefixer'),
    precss         = require('precss'),
    colorfunctions = require('postcss-color-function'),
    cssnano        = require('cssnano')
;
//var cssnext = require('cssnext');

var sourcePath = './apps/CharacterSheets/post-css/',
    destinationPath = './apps/CharacterSheets/public/css';


gulp.task('css', function () {
  var processors = [
      precss(),
      autoprefixer(),
      colorfunctions()
  ];

  return gulp.src([sourcePath+'*.css', '!'+sourcePath+'_*.css'])
    .pipe(postcss(processors))
    .on('error', gutil.log)
    .pipe(concat('postcss.css'))
    .pipe(gulp.dest(destinationPath))
    .pipe(rename({
        extname: ".min.css"
    }))
    .pipe(gulpnano({
        zindex:false,
        discardUnused:false,
        reduceIdents:false,
        mergeIdents:false
    }))
    .pipe(gulp.dest(destinationPath));
});

gulp.task('watch', function() {
    gulp.watch(sourcePath+'**/*.css', ['css']);
});


gulp.task('default', ['css', 'watch']);
