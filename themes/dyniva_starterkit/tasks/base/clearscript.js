var fs = require('fs'),
		gulp = require('gulp'),
		del = require('del');

const pg = JSON.parse(fs.readFileSync('./package.json'));

// Clean
gulp.task('base:clean:script', function() {
  return del([pg.dir.app]);
});