const Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or subdirectory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/app.js')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()

    // Disable build notifications to prevent filesystem events
    // .enableBuildNotifications()

    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // configure Babel
    // .configureBabel((config) => {
    //     config.plugins.push('@babel/a-babel-plugin');
    // })

    // enables and configure @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.38';
    })

    


    // enables Sass/SCSS support
    //.enableSassLoader()

    // Enable PostCSS loader for Tailwind CSS
    .enablePostCssLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()

    // Configure watch options to prevent excessive recompilation
    .configureWatchOptions(watchOptions => {
        watchOptions.ignored = ['**/node_modules/**', '**/public/build/**', '**/public/**'];
        watchOptions.poll = 1000;
        watchOptions.aggregateTimeout = 500;
    })

    // Configure dev server for hot reloading with PHP proxy
    .configureDevServerOptions(options => {
        options.allowedHosts = 'all';
        options.host = '0.0.0.0';
        options.port = 8080;
        options.hot = true;
        options.liveReload = true;
        options.watchFiles = [
            'templates/**/*.twig',
            'src/**/*.php'
        ];
        
        // Configure watcher to reduce unnecessary recompilation
        options.watchOptions = {
            ignored: ['**/node_modules/**', '**/public/build/**'],
            poll: 1000,
            aggregateTimeout: 500
        };
        
        // Proxy PHP requests to the PHP container
        if (process.env.WEBPACK_DEV_SERVER) {
            options.proxy = [
                {
                    context: ['/login', '/register', '/logout', '/api', '/dashboard', '/kanban', '/health'],
                    target: 'http://specsrv-app-dev:8080',
                    changeOrigin: true,
                    secure: false
                },
                // Proxy all PHP files
                {
                    context: ['**/*.php'],
                    target: 'http://specsrv-app-dev:8080',
                    changeOrigin: true,
                    secure: false
                },
                // Proxy root requests that might be handled by PHP
                {
                    context: (pathname, req) => {
                        // Don't proxy webpack dev server assets
                        if (pathname.startsWith('/build/') || 
                            pathname.startsWith('/__webpack') || 
                            pathname.startsWith('/webpack-dev-server/') ||
                            pathname.match(/\.(js|css|png|jpg|jpeg|gif|ico|svg)$/)) {
                            return false;
                        }
                        // Proxy everything else to PHP
                        return true;
                    },
                    target: 'http://specsrv-app-dev:8080',
                    changeOrigin: true,
                    secure: false
                }
            ];
        }
    })
;

module.exports = Encore.getWebpackConfig();
