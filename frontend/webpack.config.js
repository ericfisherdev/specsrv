const path = require('path');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = (env, argv) => {
  const isDev = argv.mode === 'development';
  const isProd = argv.mode === 'production';
  
  return {
    entry: './src/main.js',
    
    output: {
      path: path.resolve(__dirname, 'dist'),
      filename: isDev ? '[name].js' : '[name].[contenthash].js',
      chunkFilename: isDev ? '[name].js' : '[name].[contenthash].js',
      assetModuleFilename: 'assets/[name].[contenthash][ext]',
      clean: true,
      publicPath: '/',
    },
    
    mode: argv.mode || 'development',
    
    devtool: isDev ? 'eval-source-map' : 'source-map',
    
    devServer: {
      static: {
        directory: path.join(__dirname, 'dist'),
      },
      compress: true,
      port: 3000,
      host: '0.0.0.0',
      allowedHosts: 'all',
      hot: true,
      liveReload: true,
      historyApiFallback: {
        // Support SPA routing
        index: '/index.html',
        rewrites: [
          // Don't fallback for API calls, webpack assets, or other files with extensions
          { from: /^\/api\/.*$/, to: function(context) {
            return '/api' + context.parsedUrl.pathname;
          }},
          { from: /\.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/, to: function(context) {
            return context.parsedUrl.pathname;
          }},
        ],
      },
      proxy: [
        {
          context: ['/api'],
          target: process.env.API_BASE_URL || 'http://localhost:8080',
          changeOrigin: true,
          secure: false,
          logLevel: 'debug',
        },
        {
          context: ['/health'],
          target: process.env.API_BASE_URL || 'http://localhost:8080',
          changeOrigin: true,
          secure: false,
        },
      ],
      watchFiles: {
        paths: ['src/**/*', 'public/**/*'],
        options: {
          usePolling: false,
        },
      },
    },
    
    module: {
      rules: [
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: [
                ['@babel/preset-env', {
                  useBuiltIns: 'usage',
                  corejs: '3.38',
                  targets: {
                    browsers: ['> 1%', 'last 2 versions', 'not dead']
                  }
                }]
              ]
            }
          }
        },
        {
          test: /\.css$/,
          use: [
            isDev ? 'style-loader' : MiniCssExtractPlugin.loader,
            {
              loader: 'css-loader',
              options: {
                importLoaders: 1,
                sourceMap: isDev,
              }
            },
            {
              loader: 'postcss-loader',
              options: {
                sourceMap: isDev,
              }
            }
          ]
        },
        {
          test: /\.(png|jpe?g|gif|svg|ico)$/i,
          type: 'asset/resource',
        },
        {
          test: /\.(woff|woff2|ttf|eot)$/i,
          type: 'asset/resource',
        },
      ]
    },
    
    plugins: [
      new HtmlWebpackPlugin({
        template: './public/index.html',
        filename: 'index.html',
        minify: isProd ? {
          removeComments: true,
          collapseWhitespace: true,
          removeRedundantAttributes: true,
          useShortDoctype: true,
          removeEmptyAttributes: true,
          removeStyleLinkTypeAttributes: true,
          keepClosingSlash: true,
          minifyJS: true,
          minifyCSS: true,
          minifyURLs: true,
        } : false,
      }),
      
      ...(isProd ? [
        new MiniCssExtractPlugin({
          filename: '[name].[contenthash].css',
          chunkFilename: '[id].[contenthash].css',
        }),
      ] : []),
    ],
    
    optimization: {
      splitChunks: {
        chunks: 'all',
        cacheGroups: {
          vendor: {
            test: /[\\/]node_modules[\\/]/,
            name: 'vendors',
            chunks: 'all',
          },
        },
      },
      ...(isProd && {
        minimize: true,
        sideEffects: false,
      }),
    },
    
    resolve: {
      extensions: ['.js', '.json'],
      alias: {
        '@': path.resolve(__dirname, 'src'),
        '@components': path.resolve(__dirname, 'src/components'),
        '@pages': path.resolve(__dirname, 'src/pages'),
        '@services': path.resolve(__dirname, 'src/services'),
        '@utils': path.resolve(__dirname, 'src/utils'),
        '@styles': path.resolve(__dirname, 'src/styles'),
      },
    },
    
    performance: {
      hints: isProd ? 'warning' : false,
      maxEntrypointSize: 500000,
      maxAssetSize: 500000,
    },
  };
};