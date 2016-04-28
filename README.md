# Fly-Image
Image resizing and cropping on the fly base on ImageMagick+MozJPEG runs inside a Docker container



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


TODO
----
Implement PHP logic to manipulate images

Example:
--------
http://192.168.99.100:8080/upload/w_500,h_500,q_10/https://www.google.com/images/branding/googlelogo/2x/googlelogo_color_272x92dp.png


Options keys:
-------------

```sh
options_keys:
  q: quality
  sh: unsharp
  c: crop
  bg: background
  st: strip
  rz: resize
  unsh: unsharp
  moz: mozjpeg
  h: height
  w: width
  g: gravity
  th: thread
  thb: thumbnail
  f: filter
  sc: scale
  sf: sampling-factor
```

Default options values:
-----------------------

```sh
default_options:
  mozjpeg: 1
  quality: 90
  unsharp: null
  width: null
  height: null
  crop: null
  background: null
  resize: null
  strip: 1
  gravity: Center
  thread: 1
  thumbnail: null
  filter: Lanczos
  scale: null
  sampling-factor: 1x1
```