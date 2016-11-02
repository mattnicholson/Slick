<?php
/**
 * Slick
 * Quick and simple page rules for full page caching
 *
 *
 * PHP version 5
 *

 * @author     Matt Nicholson <matt@archivestudio.co.uk>
 * @link       https://github.com/mattnicholson/Slick

 */

/**
 * Config
 * Configure these and then add `include 'slick.php'` to your index file
 *
*/
	
$slick_config = array(

	'enabled' => TRUE, // Bypass slick so no caches are checkes or stored
	'flush' => FALSE, // Update the cache for each request page
	'controller' => FALSE, // File to include to carry on with the application if file isn't in cache
	'cachedir' => 'cache/', // Where to store cache files
	'rules' => [
		'/(cpresources|admin).*' => [

			'cache' => 0 // Don't cache admin or cpresources requests

		],
		'^/$' => [

			'expires' => -1 // Never expire the homepage, has to be flushed to clear

		],
		'.*' => [

			'expires' => 86400 // All other pages, cache for 1 day

		]
	],

);
	
	
/**
 * Slick logic
 * Don't edit below here...
 *
*/
	
$uri = $_SERVER['REQUEST_URI'];

/*

Check if a flush flag has been set in the URI
	
*/

$get_flush = (isset($_GET['SLICK_FLUSH']) && $_GET['SLICK_FLUSH'] == '1');
if($get_flush){
	
	$uri = preg_replace("|(\?)?SLICK_FLUSH=1&?|","$1",$uri);
	$uri = preg_replace("|\?$|","",$uri); // If there's just a query string left, get rid of it
}

$md5 = md5($uri);

$cachefile = $slick_config['cachedir'].$md5.'.html';

if(isset($_SERVER['HTTP_X_SLICK']) || isset($_SERVER['HTTP_X_SLICK_FLUSH']) || (isset($slick_config['flush']) && $slick_config['flush']) || $get_flush):
	$flush = 1;
else:
	$flush= 0;
endif;

$uri = trim($uri);

$rules = (isset($slick_config['rules']) && is_array($slick_config['rules'])) ? $slick_config['rules'] : array();


foreach($rules as $pattern => $rule):
	
	if(preg_match('`'.$pattern.'`', $uri)):

		$cache = (array_key_exists('cache',$rule) && (!$rule['cache'])) ? 0 : 1; // Default to cache
		$expires = (array_key_exists('expires',$rule)) ? $rule['expires'] : 86400; // Default 1 day
		$post = (array_key_exists('post',$rule) && ($rule['post'])) ? 1 : 0;  // Default to ignore if there's $_POST

		if(!$post && !empty($_POST)) $cache = 0;
		
		break;

	endif;

endforeach;

// Cache file is there, not flushing
if($slick_config['enabled'] && !$flush && $cache && file_exists($cachefile) && (($expires < 0) || time() < (filemtime($cachefile)+ $expires))):
	
	header("X-SLICK-STATUS: HIT");
	if($expires > 0):
	$expires = filemtime($cachefile) + $expires;
	header("X-SLICK-EXPIRES: ".date('Y-m-d H:i:s',$expires));
	else:
	header("X-SLICK-EXPIRES: Never");
	endif;
	$html = file_get_contents($cachefile);

	echo $html;

	// Stop execution
	exit;
else:
	
	
	
	if(isset($_SERVER['HTTP_X_SLICK'])):
		$slick = 1;
	else:
		$slick = 0;
	endif;

	// Slick is not set, so this is a natural request.
	// Re-request the page with SLICK header
	if(!$slick && $slick_config['enabled'] && $cache):

	

		$host =   $_SERVER['HTTP_HOST'];
		$url = 'http://'.$host.$uri;

		$ch = curl_init($url);
		curl_setopt_array($ch, array(
		    CURLOPT_HTTPHEADER  => array('X-SLICK: TRUE'),
		    CURLOPT_RETURNTRANSFER  =>true,
		    CURLOPT_VERBOSE     => 0,
		    CURLOPT_HEADER => 1
		));



		$response = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$html = substr($response, $header_size);

		curl_close($ch);

		// Response codes we are happy to cache...
		$cacheable_responses = array(200);

		// Headers we want to keep
		$return_headers = array('Content-Type','Location');

		// Only cache if it's a valid response code...
		if(in_array($httpcode,$cacheable_responses)) file_put_contents($cachefile,$html);

		// Retain headers...
		$headers = explode("\r\n", $header);
		foreach($headers as $header):
			if(!empty($header)){
				$h = explode(':',$header);
				$k = $h[0];
				//echo $k.':'.$h[1].'<br>';
				if(in_array($k,$return_headers)) header($header);
			}
		endforeach;
		//echo $html;

		// Custom headrrs
		header("X-SLICK-STATUS: MISS");
		if(!in_array($httpcode,$cacheable_responses)) header("X-SLICK-CODE: ".$httpcode);

		// Output the HTML that came back...
		echo $html;

		// Stop execution
		exit;

	else:

		// Not caching this request, include the application entry point
		header("X-SLICK-STATUS: DISABLED");
		// If a controller is set, include it. This allows slick to be the point of entry file if desired
		if(isset($slick_config['controller']) && $slick_config['controller']) include $slick_config['controller'];

		// Don't exit, allow this file to be includable

	endif;

// Don't exit - allow this file to be includable

endif;
?>
