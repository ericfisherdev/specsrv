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

    // Displays build status system notifications to the user
    .enableBuildNotifications()

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

    // Production optimizations
    .configureOptimization((options) => {
        if (Encore.isProduction()) {
            // Enable minification
            options.minimize = true;
            
            // Split vendor dependencies into separate chunk
            options.splitChunks = {
                chunks: 'all',
                cacheGroups: {
                    vendor: {
                        test: /[\\/]node_modules[\\/]/,
                        name: 'vendors',
                        chunks: 'all',
                        priority: 10
                    },
                    common: {
                        name: 'common',
                        minChunks: 2,
                        chunks: 'all',
                        priority: 5,
                        reuseExistingChunk: true
                    }
                }
            };
        }
    })

    // Configure image optimization
    .configureLoaderRule('images', (config) => {
        config.test = /\.(png|jpg|jpeg|gif|ico|svg|webp)$/;
        if (Encore.isProduction()) {
            // Optimize images in production
            config.type = 'asset';
            config.parser = {
                dataUrlCondition: {
                    maxSize: 4 * 1024 // 4kb - inline small images
                }
            };
            config.generator = {
                filename: 'images/[name].[hash:8][ext]'
            };
        }
    })

    // Configure font optimization  
    .configureLoaderRule('fonts', (config) => {
        config.test = /\.(woff|woff2|eot|ttf|otf)$/;
        config.type = 'asset/resource';
        config.generator = {
            filename: 'fonts/[name].[hash:8][ext]'
        };
    })

    // enables Sass/SCSS support
    //.enableSassLoader()

    // Enable PostCSS loader for Tailwind CSS with optimization
    .enablePostCssLoader((options) => {
        if (Encore.isProduction()) {
            // Add CSS optimization for production
            options.postcssOptions = {
                plugins: [
                    require('tailwindcss'),
                    require('autoprefixer'),
                    require('cssnano')({
                        preset: ['default', {
                            discardComments: { removeAll: true },
                            normalizeWhitespace: true,
                            mergeLonghand: true,
                            mergeRules: true
                        }]
                    })
                ]
            };
        } else {
            options.postcssOptions = {
                plugins: [
                    require('tailwindcss'),
                    require('autoprefixer')
                ]
            };
        }
    })

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()

    // Configure dev server for hot reloading
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
    })
;

module.exports = Encore.getWebpackConfig();
