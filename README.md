# Fly-Image

[![Build Status](https://travis-ci.org/sadok-f/fly-image.svg?branch=master)](https://travis-ci.org/sadok-f/fly-image)

Image resizing and cropping on the fly based on ImageMagick + MozJPEG runs with Docker containers.

Docker compose create the following containers:
- **nginx** : Nginx 1.9
- **fpm** : PHP 7 fpm
- **redis**: Redis server
- **redis-commander**: Redis-commander to help visualize data stored in Redis server



Build the images:

```sh
    $ docker-compose build
```
Up the containers:

```sh
    $ docker-compose up -d
```

Storage:
--------
Storage files based on [Flysystem](http://flysystem.thephpleague.com/) which is `a filesystem abstraction allows you to easily swap out a local filesystem for a remote one. Technical debt is reduced as is the chance of vendor lock-in.`

Default storage is Local, but you can use other Adapters like AWS S3, Azure, FTP, Dropbox, ... 

Caching:
--------
Use Redis for meta-data caching


Options keys:
-------------

```yml
options_keys:
  moz: mozjpeg
  q: quality
  unsh: unsharp
  w: width
  h: height
  c: crop
  bg: background
  st: strip
  rz: resize
  g: gravity
  th: thread
  thb: thumbnail
  f: filter
  sc: scale
  sf: sampling-factor
  rf: refresh
```

Default options values:
-----------------------

```yml
default_options:
  mozjpeg: 1
  quality: 90
  unsharp: null
  width: null
  height: null
  crop: null
  background: null
  strip: 1
  resize: null
  gravity: Center
  thread: 1
  thumbnail: null
  filter: Lanczos
  scale: null
  sampling-factor: 1x1
  refresh: false
```



Example of using AWS S3 Adapter:
--------------------------------
in app.php:

```php
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
if (getenv('cache') == 0 || !$app['params']['cache']) {
    $adapter = 'League\Flysystem\AwsS3v3\AwsS3Adapter';
    $args = [$s3Client, 'your-bucket-name'];
} else {
    $client = new Client('tcp://redis-service:6379');
    $s3Client = S3Client::factory([
        'credentials' => [
            'key'    => 'your-key',
            'secret' => 'your-secret',
        ],
        'region' => 'your-region',
        'version' => 'latest|version',
    ]);
    
    $adapter = 'League\Flysystem\Cached\CachedAdapter';
     $args = [
            new  AwsS3Adapter($s3Client, 'your-bucket-name'),
            new Cache($client)
        ];
}
```
 

Unable Restricted Domains:
--------------------------

Restricted domains disabled by default, to enable it change in config/parameters.yml
```yml
restricted_domains: true
```

After you need to put the white list domains
```yml
whitelist_domains:
    - www.domain-1.org
    - www.domain-2.org
```

Demo:
-----
restricted_domains is activated, only images from www.mozilla.org domain is accepted

- Quality 90%
http://176.31.121.161:8080/upload/w_500,h_500,q_90/https://www.mozilla.org/media/img/firefox/firefox-256.e2c1fc556816.jpg

- Quality 10%
http://176.31.121.161:8080/upload/w_500,h_500,q_10/https://www.mozilla.org/media/img/firefox/firefox-256.e2c1fc556816.jpg


Redis-commander (Disabled for now):

http://176.31.121.161:8090