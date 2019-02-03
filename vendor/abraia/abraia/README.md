[![PHP version](https://badge.fury.io/ph/abraia%2Fabraia.svg)](https://badge.fury.io/ph/abraia%2Fabraia)
[![Build Status](https://travis-ci.org/abraia/abraia-php.svg)](https://travis-ci.org/abraia/abraia-php)

# Abraia API client for PHP

PHP client for [Abraia](https://abraia.me) services. It is used to smartly
[optimize images for web](https://abraia.me/docs/image-optimization).

## Install

Install the PHP client:

```sh
composer install abraia/abraia
```

And configure your [free API key](https://abraia.me/docs/getting-started) as
the `ABRAIA_KEY` environment variable:

```sh
export ABRAIA_KEY=your_api_key
```

## Usage

Most common operations can be easily performed using the fluent API.
Automatically optimize your JPEG, PNG, GIF, SVG, and WebP images without any
parameterization.

```php
$abraia = new Abraia\Abraia();

$abraia->fromFile('images/tiger.jpg')->toFile('images/optimized.jpg')
```

Resize and optimize an image to a maximum size preserving the aspect ratio.

```php
$abraia->fromFile('images/tiger.jpg')->resize(500, 500, 'thumb')->toFile('images/roptim.jpg');
```

![Resized tiger image](https://github.com/abraia/abraia-php/raw/master/images/roptim.jpg)

*Tiger image resized and optimized preserving the aspect ratio*

Smartly crop and optimize an image to change its aspect ratio.

```php
$abraia->fromFile('images/tiger.jpg')->resize(500, 500)->toFile('images/resized.jpg');
```

![Smart cropped tiger](https://github.com/abraia/abraia-php/raw/master/images/resized.jpg)

*Tiger image automatically smart cropped to show a square aspect ratio*

## License

This software is licensed under the MIT License. [View the license](LICENSE).
