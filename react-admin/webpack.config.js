const path = require('path');
const BrowserSyncPlugin = require('browser-sync-webpack-plugin');

// WordPress site URL - change this to your local WordPress URL
const WORDPRESS_SITE_URL = 'http://localhost/your-wordpress-site';

module.exports = {
  entry: './src/index.js', // your main React file
  output: {
    filename: 'backend.js',
    path: path.resolve(__dirname, '../assets/js'), // Adjust this path if needed
    clean: true, // Clean the output directory before emit
  },
  module: {
    rules: [
      {
        test: /\.(js|jsx)$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: [
              ['@babel/preset-env', { targets: "defaults" }],
              ['@babel/preset-react', { runtime: 'automatic' }]
            ],
            plugins: [
              // Add any Babel plugins you need here
            ]
          },
        },
      },
      // Add CSS handling if needed
      {
        test: /\.css$/,
        use: ['style-loader', 'css-loader'],
      },
      // Add file handling if needed
      {
        test: /\.(png|svg|jpg|jpeg|gif)$/i,
        type: 'asset/resource',
        generator: {
          filename: '../images/[name][ext]', // Output relative to your js folder
        },
      },
    ],
  },
  resolve: {
    extensions: ['.js', '.jsx'],
    alias: {
      '@': path.resolve(__dirname, 'src'), // Enable import shortcuts with @
    },
  },
  plugins: [
    // BrowserSync for auto-reloading WordPress
    new BrowserSyncPlugin({
      host: 'localhost',
      port: 3000,
      proxy: WORDPRESS_SITE_URL,
      files: [
        '../assets/js/backend.js', // Watch the compiled JS file
        '../**/*.php', // Watch PHP files for changes
      ],
      injectChanges: true,
      notify: true
    })
  ],
  // Different configurations based on environment
  mode: process.env.NODE_ENV === 'production' ? 'production' : 'development',
  devtool: process.env.NODE_ENV === 'production' ? false : 'source-map',
  // Performance settings
  performance: {
    hints: process.env.NODE_ENV === 'production' ? 'warning' : false,
  },
  // Only watch in development mode
  watch: process.env.NODE_ENV === 'development',
  watchOptions: {
    ignored: /node_modules/,
    aggregateTimeout: 300,
    poll: 1000,
  },
};