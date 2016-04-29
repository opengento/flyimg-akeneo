# Fly-Image
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
If you running docker-machine, get the VM ip:

```sh
    $ docker-machine ip xxx
```

Access to the server: xxx.xxx.xxx.xxx:8080

Example:
--------
http://192.168.99.100:8080/upload/w_500,h_500,q_90/https://www.mozilla.org/media/img/firefox/firefox-256.e2c1fc556816.jpg


Redis-commander:
http://192.168.99.100:8090


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