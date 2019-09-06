# Basic Authentication handler
This plugin adds Basic Authentication to a WordPress site.

Note that this plugin requires sending your username and password with every
request, and should only be used over SSL-secured connections or for local
development and testing. Without SSL we strongly recommend using the
[OAuth 1.0a][oauth] authentication handler in production environments.

## Fork Changes
This fork of the Basic-Auth plugin differs from the original project in the following ways:
- A new endpoint is created (`/wp-json/basic-auth/v1/check-auth`) that gives a simple response if verification succeeds
- Basic authentication will only work for the newly created endpoint
- If authentication explicitly fails, the request will hang for a few seconds before returning a response
- Multiple authentication attempts can not be made by the same user simultaneously

## Installing
1. Download the plugin into your plugins directory
2. Enable in the WordPress admin

## Using
This plugin adds support for Basic Authentication, as specified in [RFC2617][].
Most HTTP clients will allow you to use this authentication natively. Some
examples are listed below.

### cURL

```sh
curl --user admin:password https://example.com/wp-json/
```

### WP_Http

```php
$args = array(
	'headers' => array(
		'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
	),
);
```

### [node-wpapi][]

```js
const WPAPI = require('./wpapi')
const wp = new WPAPI({
    endpoint: 'https://example.com/wp-json',
    username: 'editor',
    password: 'password'
});
```

[oauth]: https://github.com/WP-API/OAuth1
[RFC2617]: https://tools.ietf.org/html/rfc2617
[node-wpapi]: http://wp-api.org/node-wpapi/
