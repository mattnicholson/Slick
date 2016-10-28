# slick
Plug and play full page caching with 1 PHP file. Who needs cloud flare page rules?

## C'est quoi ça?  
Slick.php is a really quick way of adding Cloudflare style page rules to your PHP website (e.g Wordpress, Craft CMS, Expression Engine etc). No DNS changes - you just need FTP access and a rapid website is just a file upload away.

## Installation

1. Download `slick.php`
2. Create a cache folder on your server with `755` permissions
3. Configure `$slick_config` options to set the correct path to your cache folder
4. Set up your page rules in `$slick_config.php` (These are regular expression patterns that will be matched against the URI, a bit like Cloudflare)
5. Upload `slick.php` to your web root
6. Edit your application entry point (e.g. `index.php`) and add `include 'slick.php'` at the top of the file, inside the opening `<?php` tag
7. Enjoy

## Upload slick.php to the server and then add this to the top of your index.php

```php
<?php
  include 'slick.php';
?>

```

## Requirements

- Must be a PHP site
- Assumes a single point of entry file, like an index.php
- a .htaccess rewrite rule should be routing all 404 requests to your single point of entry file, this is probably already part of your application if you're using Craft

# Config

Default setup:

```php
  $slick_config = array(
    'enabled' => TRUE,
		'cachedir' => '../cache/', 
		'rules' => []
	);
```

All options:

```php
  $slick_config = array(
    'enabled' => TRUE, // TRUE: Caching is on || FALSE: Bypass slick so no caches are checkes or stored
		'cachedir' => '../cache/', // Where to store cache files
		'rules' => [], // Array of page rules with caching options
		'flush' => FALSE, // (Optional) FALSE: Ignore || TRUE: Update the cache file for each requested page
		'controller' => FALSE, // (Optional) FALSE: Do not include any files after slick || 'app.php' : Include the file 'app.php' after slick (useful if using slick.php as the point of entry to your application, rather than including slick from your index file)
	);
```

# Rules

Rules are patterns that match agains the URL being requested. This allows you to control cache settings or diferent URL patterns. The rules are matched against the URI (e.g. /news/). They are regular expressions, so be sure to put in a valid regex pattern, and escape special characters needed by regex.

```php
 $sectionPattern = '/section.*';
 
 /* 
  Means:
  ------------------------
  - a slash
  - the word 'section'
  - then zero or more characters
  -------------------------
  Matches:
  -------------------------
  http://website.com/section/section-1/
  http://website.com/section/
  http://website.com/section
 */
 
 $aboutPattern = '/(about|profile)/?';
 
 /* 
  Means:
  ------------------------
  - a slash
  - either the word 'about' or 'profile'
  - then an optional slash
  -------------------------
  Matches:
  -------------------------
  http://website.com/about/
  http://website.com/about
  http://website.com/profile/
  htp://website.com/profile
 */
```

For more info on regex, check out http://php.net/manual/en/regex.examples.php

Rules are matched in order, so put the most specifc rules first and the generic catch all rules at the end.
Expiry time is set in seconds. Each rule can set the following options:

## Rule options

``` 
  'cache' // Whether requests matching this rule should be cached at all. Default: 1. Can be 1 or 0 or TRUE or FALSE
  'expires' // How long to cache these requests for. In seconds. Default: 86400 (1 day) Setting to 0 is equivalent to not caching at all
  'post' // Whether to cache this request is $_POST data is present. Default: 0 (Don't cache if there's $_POST). Can be 1 or 0 or TRUE or FALSE
  
```

## Example Rules

Cache all pages for a day

```php
$slick_config = array(
		
		'enabled' => TRUE, // Bypass slick so no caches are checkes or stored
		'cachedir' => '../cache/', // Where to store cache files
		'rules' => [
			'.*' => [
				
				'expires' => 86400 // 1 Day in seconds
				
			]
		],
		
	);
```

Dont cache /admin requests or /cpresources requests, but cache everything else for 1 day

```php
$slick_config = array(
		
		'enabled' => TRUE, // Bypass slick so no caches are checkes or stored
		'cachedir' => '../cache/', // Where to store cache files
		'rules' => [
			'/(cpresources|admin).*' => [
				
				'cache' => 0
				
			],
			'.*' => [
				
				'expires' => 86400 // 1 Day in seconds
				
			]
		],
		
	);
```

Cache the news page for 50 seconds and everything else for 1 day

```php
$slick_config = array(
		
		'enabled' => TRUE, // Bypass slick so no caches are checkes or stored
		'cachedir' => '../cache/', // Where to store cache files
		'rules' => [
			'/news/' => [
				
				'expires' => 50 // 50 seconds
				
			],
			'.*' => [
				
				'expires' => 86400 // 1 Day in seconds
				
			]
		],
		
	);
```

