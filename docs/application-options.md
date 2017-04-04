# Application Options

Here are the app options you can configure with the [config/parameters.yml](https://github.com/flyimg/flyimg/blob/master/config/parameters.yml) these options operate at runtime, you don't need to rebuil the container or restart any service, all requests<sup><a name="footnote1">1</a></sup> will check this config. 

### application_name
*Defaults to:* `Flyimg.io`
*Description:* Seems to do nothing, I propose to delete it.

### debug
*Defaults to:* `true`
*Description:* Enables debug mode, currently is used only for the tests, so there's no harm in leaving it as it is.

### webp_enabled
*Defaults to:* `false`
*Description:* WebP support Enabled

### header_cache_days
*Defaults to:* `365`
*Description:* Number of days for header cache expires `max_age`

### options_separator
*Defaults to:* `,`
*Description:* options separator

### restricted_domains
*Defaults to:* `false`
*Description:* restrict domains, false by default

### whitelist_domains:
#if restricted_domains is enabled, put whitelist domains here
    - domain-1.com
    - domain-2.com

### storage_system
*Defaults to:* `local`
*Description:* Default storage system is local, to use use AWS S3, change this param to s3

### aws_s3:
#In case storage_system
*Defaults to:* `s3, you need to add those AWS S3 parameters:`
*Description:*  access_id: ""
  secret_key: ""
  region: ""
  bucket_name: ""

## Footnotes

1. All request will check your settings at `config/parameters.yml` but if you are heavily requests caching before the application, there will not affect the response, but if you are caching responses you already knew that ;)
