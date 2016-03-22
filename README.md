# Fly-Image
Image resizing and cropping on the fly base on ImageMagick+MozJPEG runs inside a Docker container



Build the images:

.. code-block:: console

    $ docker-compose build

Up the containers:

.. code-block:: console

    $ docker-compose up -d

If you running docker-machine, get the VM ip:

.. code-block:: console

    $ docker-machine ip xxx


Access to the server: xxx.xxx.xxx.xxx:8080


TODO
----
Implement PHP logic to manipulate images

Example:
--------
http://192.168.99.100:8080/upload/w_500,h_500,q_10/https://www.google.com/images/branding/googlelogo/2x/googlelogo_color_272x92dp.png

