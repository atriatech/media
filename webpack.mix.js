const mix = require('laravel-mix');
const fs = require('fs-extra');
const rimraf = require("rimraf");

mix.setPublicPath('./dist');

rimraf.sync("./dist");

mix.webpackConfig({
    output: {
        libraryTarget: 'var',
        library: 'AtriatechMedia'
    }
});

mix.js(__dirname + '/src/assets/js/app.js', 'js/media.js');
mix.js(__dirname + '/src/assets/js/selector.js', 'js/media-selector.js');

mix.sass( __dirname + '/src/assets/sass/app.scss', 'css/media.css', {
    implementation: require('sass'),
    sassOptions: {
        includePaths: ['./node_modules'],
    }
}).options({
    processCssUrls: false,
});
mix.sass( __dirname + '/src/assets/sass/selector.scss', 'css/media-selector.css', {
    implementation: require('sass'),
    sassOptions: {
        includePaths: ['./node_modules'],
    }
}).options({
    processCssUrls: false,
});

fs.copy(__dirname + '/src/assets/extra', `./dist/extra`);

if (mix.inProduction()) {
    mix.version();
}
