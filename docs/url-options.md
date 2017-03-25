# URL Options

This document lists and describes the full list of options available to be passed as parameters in the URL.

For server options check the [server options](/docs/server-options.md)

## UPLOAD, PATH, GRAB

There are 3 main proceses you can do for images.

The first: **upload**, grabs an image from a URL, transforms it, saves it, and serves it.

The second: **path**, grabs an image from a URL, transforms it, saves it, and returns the absolute path to the image.

The third: **grab**, renders part of a webpage or a full webpage from a URL, saves it, and serves it. This is usefull for overlaying text on an image.

## Transformations options for `upload` and `path`