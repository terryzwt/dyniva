var fs = require('fs'),
		gulp = require('gulp'),
		del = require('del');

const pg = JSON.parse(fs.readFileSync('./package.json'));

// Clean
gulp.task('base:clean:css', function() {
  return del([pg.dir.app + '/' + pg.source.build.css]);
});