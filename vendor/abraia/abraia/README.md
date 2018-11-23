[![PHP version](https://badge.fury.io/ph/abraia%2Fabraia.svg)](https://badge.fury.io/ph/abraia%2Fabraia)
[![Build Status](https://travis-ci.org/abraia/abraia-php.svg)](https://travis-ci.org/abraia/abraia-php)

# Abraia API client for PHP

PHP client for [Abraia](https://abraia.me) services. It is used to transform
and optimize (compress) images for web. Read more at [https://abraia.me/docs](
https://abraia.me/docs).

## Usage

Most common operations can be easily performed using the fluent API. You just
need to define the API Keys as environment variables (`ABRAIA_API_KEY` and
`ABRAIA_API_SECRET`).

Automatically optimize an image without any parameterization.

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
