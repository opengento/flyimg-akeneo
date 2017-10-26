# Application Options

Here are the app options you can configure with the [config/parameters.yml](https://github.com/flyimg/flyimg/blob/master/config/parameters.yml) these options operate at runtime, you don't need to rebuild the container or restart any service, all requests<sup><a name="footnote1">1</a></sup> will check this config. 

### application_name
*Defaults to:* `Flyimg.io`
*Description:* Seems to do nothing, I propose to delete it.

### debug
*Defaults to:* `true`
*Description:* Enables debug mode, currently is used only for the tests, so there's no harm in leaving it as it is.

### auto_webp_enabled
*Defaults to:* `false`
*Description:* Serve WebP automatically to Browsers supporting it. You can always request an image in webP format pasing the `o=webp` [URL option key](https://github.com/flyimg/flyimg/blob/master/docs/url-options.md).

### header_cache_days
*Defaults to:* `365`
*Description:* Number of days for header cache expires `max_age`, this is the header sent to the client or browser requesting the resource. You can pass cache busting parameters to the URL which will break cache in all modern proxies and Browsers.

### options_separator
*Defaults to:* `,`
*Description:* URL options are separated by default by comas `,` but you can change that to some other character, like `._~:[]@!$'()*+;` just be carefull that it doesn't conflict with the sintaz of options you are passing to the URL, there is no strict checking of separating characters.

### restricted_domains
*Defaults to:* `false`
*Description:* This restricts fetching images for transformations only from *whitelisted domains* (see `whitelist_domains`). A good measure of safety and to prevent abuse of your app from third parties is to set `restricted_domains` to `true`, this way the app will download and try to transform resources only from domains you trust or have control of.

### whitelist_domains:
*Defaults to:*
```
    - domain-1.com
    - domain-2.com
```
*Description:* If `restricted_domains` is enabled, put your whitelisted domains in this list, subdomains are also ok. For the [Digital Ocean Provisioning Script](https://github.com/flyimg/DigitalOcean-provision) you can set the restricted domains at the droplet provisioning step.

### storage_system
*Defaults to:* `local`
*Description:* You can store the transformed images in many different ways taking advantage of the [Flysystem](http://flysystem.thephpleague.com/) file system, like FTP, Dropbox, or whatever, although currently the only two easy options are `local` (the default) and `s3` to use an AWS S3 bucket. 

### aws_s3:
*Description:* In case `storage_system` is set to `s3` you need to pass your AWS S3 Bucket credentials, do it here. Read more below at [Abstract storage with Flysystem](#abstract-storage-with-flysystem).
*Defaults to:* 
```
  access_id: ""
  secret_key: ""
  region: ""
  bucket_name: ""
```

## Abstract storage with Flysystem:

Storage files based on [Flysystem](http://flysystem.thephpleague.com/) which is `a filesystem abstraction allows you to easily swap out a local filesystem for a remote one. Technical debt is reduced as is the chance of vendor lock-in.`

Default storage is Local, but you can use other Adapters like AWS S3, Azure, FTP, Dropbox, ... 

Currently, only the local and S3 are implemented as Storage Provider in Flyimg application, but you can add your specific one easily in `src/Core/Provider/StorageProvider.php` 

### Using AWS S3 as Storage Provider

in parameters.yml change the `storage_system` option from local to s3, and fill in the aws_s3 options :

```yml
storage_system: s3

aws_s3:
  access_id: "s3-access-id"
  secret_key: "s3-secret-id"
  region: "s3-region"
  bucket_name: "s3-bucket-name"
```

The rest of the options are pased to the URL, check the [URL option keys document](https://github.com/flyimg/flyimg/blob/master/docs/url-options.md) .

## Footnotes

1. All request will check your settings at `config/parameters.yml` but if you are heavily requests caching before the application, there will not affect the response, but if you are caching responses you already knew that ;)
