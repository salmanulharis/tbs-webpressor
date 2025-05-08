# WebPressor

A WordPress plugin to convert images to WebP format and serve them to compatible browsers.

## Description

WebPressor automatically converts your JPEG and PNG images to the WebP format, which provides superior lossless and lossy compression for images on the web. WebP images are typically 25-35% smaller than comparable JPEG images at equivalent visual quality, helping your website load faster.

The plugin will automatically detect browser support and serve WebP images only to compatible browsers, ensuring backward compatibility with older browsers.

## Features

- Automatic conversion of JPEG and PNG images to WebP format
- Batch processing of existing media library images
- Automatic detection of browser WebP support
- Compatible with all WordPress themes and plugins
- Easy to use dashboard for conversion management
- Configurable WebP quality settings

## Installation

1. Upload the `tbs-webpressor` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to the WebPressor menu in your WordPress admin area
4. Click "Start Conversion" to convert your existing images

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- GD library with WebP support

## Frequently Asked Questions

### Does this plugin replace original images?

No, this plugin keeps your original images intact. It creates WebP versions alongside your original files and serves them only to browsers that support WebP.

### Will this plugin slow down my site?

No, quite the opposite! WebP images are significantly smaller than JPEG and PNG equivalents, which should improve your page load times.

### What happens if a browser doesn't support WebP?

For browsers that don't support WebP, the plugin will automatically serve the original JPEG or PNG images.

### Can I disable WebP conversion for specific images?

Currently, the plugin converts all JPG, JPEG, and PNG images. A future update may include selective conversion options.

### Does this plugin work with CDNs?

Yes, the plugin should work with most CDNs that properly cache and serve different file extensions.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
* Initial release