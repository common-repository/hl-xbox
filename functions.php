<?php if(!HL_XBOX_LOADED) die('Direct script access denied.');


/*
	Run cron job
*/
function hl_xbox_init() {
	if($_GET['hl_xbox_cron']!='' and $_GET['hl_xbox_cron']==HL_XBOX_CRON_KEY) {
		echo '<h1>HL Xbox</h1><hr />';
		$result = hl_xbox_api_update_users();

		if($result['status']=='success') {
			echo '<p>Import completed successfully.</p>';
		} else {
			echo '<p>One or more errors were encountered while importing.';
		}
		
		echo '<ul><li>';
			echo implode('</li><li>', $result['lines']);
		echo '</li></ul>';
		
		die();
	}
} // end func: hl_xbox_init



/*
	Called by the WordPress Scheduler
*/
function hl_xbox_cron_handler() {
	hl_xbox_api_update_users();
} // end func: hl_xbox_cron_handler



/*
	Create tables on install
*/
function hl_xbox_install() {
	global $wpdb, $table_prefix;
	
	wp_schedule_event(time(), 'twicedaily', HL_XBOX_SCHEDULED_EVENT_ACTION); # Add cron event handler, default is twicedaily to be run
	
	/* Plugin Dial Home
	 * 
	 * This is a completely anonymous remote call to the original developers server.
	 * The only data tracked is an non-reversible hash of the site URL so duplicates
	 * aren't recorded. It is so installations can be shown on the plugin website.
	 */
	wp_remote_get('http://hybridlogic.co.uk/wp-plugin-activation.php?plugin=hl_xbox&hash='.md5(get_bloginfo('url')));
	
	$sql = "
		CREATE TABLE `".$table_prefix.HL_XBOX_DB_PREFIX."gamerscores` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`user_id` int(11) DEFAULT NULL,
			`gamerscore` int(11) DEFAULT NULL,
			`datetime` datetime DEFAULT NULL,
			PRIMARY KEY (`id`)
		);
	";
	$wpdb->query($sql);
	
	$sql = "	
		CREATE TABLE `".$table_prefix.HL_XBOX_DB_PREFIX."games` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`game_id` varchar(50) DEFAULT NULL,
			`name` varchar(100) DEFAULT NULL,
			`total_achievements` int(11) DEFAULT '0',
			`total_gamerscore` int(11) DEFAULT '0',
			`image_32_url` varchar(255) DEFAULT NULL,
			`image_64_url` varchar(255) DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `game_id` (`game_id`),
			FULLTEXT KEY `name` (`name`)
		);
	";
	$wpdb->query($sql);
	
	$sql = "
		CREATE TABLE `".$table_prefix.HL_XBOX_DB_PREFIX."usergames` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`game_id` int(11) DEFAULT NULL,
			`user_id` int(11) DEFAULT NULL,
			`first_played` datetime DEFAULT NULL,
			`last_played` datetime DEFAULT NULL,
			`current_achievements` int(11) DEFAULT '0',
			`current_gamerscore` int(11) DEFAULT '0',
			PRIMARY KEY (`id`),
			UNIQUE KEY `usergame` (`user_id`,`game_id`)
		);
	";
	$wpdb->query($sql);
	
	$sql = "
		CREATE TABLE `".$table_prefix.HL_XBOX_DB_PREFIX."users` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`account_status` enum('gold','silver') DEFAULT 'silver',
			`info` varchar(50) DEFAULT NULL,
			`last_seen` datetime DEFAULT NULL,
			`title` varchar(50) DEFAULT NULL,
			`gamertag` varchar(50) DEFAULT NULL,
			`profile_url` varchar(255) DEFAULT NULL,
			`tile_url` varchar(255) DEFAULT NULL,
			`reputation` double DEFAULT '0',
			`gamerscore` int(11) DEFAULT '0',
			`zone` varchar(50) DEFAULT NULL,
			`api_last_updated` datetime DEFAULT NULL,
			PRIMARY KEY (`id`),
			FULLTEXT KEY `gamertag` (`gamertag`)
		);
	";
	$wpdb->query($sql);
	
} // end func: hl_xbox_install


/*
	On uninstall...
*/
function hl_xbox_uninstall() {
	# Leave tables alone, just in case
	wp_clear_scheduled_hook(HL_XBOX_SCHEDULED_EVENT_ACTION); # Remove cron
} // end func: hl_xbox_uninstall



/*
	Returns the URL to a local cache copy of the image for this game
		Caches image if it doesn't exist, no expiry
*/
function hl_xbox_get_game_image_url($game_id, $image_size=32) {
	$game_id = intval($game_id);
	$image_size = ($image_size==64)?64:32;
	$image_src = 'games/game_'.$game_id.'_'.$image_size.'.jpg';
	
	if(!file_exists(HL_XBOX_DIR.$image_src)) {
		global $wpdb;
		$img_url = $wpdb->get_var($wpdb->prepare('SELECT image_'.$image_size.'_url FROM '.HL_XBOX_DB_PREFIX.'games WHERE id=%d LIMIT 1',$game_id));
		if($img_url!='') {
			$img_data = wp_remote_get($img_url);
			if($img_data and $img_data['response']['code']==200 and $img_data['body']!='') {
				file_put_contents(HL_XBOX_DIR.$image_src, $img_data['body']);
			}	
		}
	}
	return HL_XBOX_URL.$image_src;
} // end func: hl_xbox_get_game_image_url


/*
	Returns the URL to a local cache copy of the image for this User
		Caches the images if it doesn't exist, expiry as per lifetime
*/
function hl_xbox_get_user_image_url($user_id) {
	$user_id = intval($user_id);
	$image_src = 'users/user_'.$user_id.'_l.jpg';
	if(!file_exists(HL_XBOX_DIR.$image_src) or filemtime(HL_XBOX_DIR.$image_src)+HL_XBOX_AVATAR_CACHE_LIFETIME < time()) {
		global $wpdb;
		$tile_url = $wpdb->get_var($wpdb->prepare('SELECT tile_url FROM '.HL_XBOX_DB_PREFIX.'users WHERE id=%d LIMIT 1',$user_id));
		if($tile_url!='') {
			$img_data = wp_remote_get($tile_url);
			if($img_data and $img_data['response']['code']==200 and $img_data['body']!='') {
				file_put_contents(HL_XBOX_DIR.$image_src, $img_data['body']);
			}	
		}
	}
	return HL_XBOX_URL.$image_src;
} // end func: hl_xbox_get_user_image_url


function hl_xbox_get_xbox_live_game_url($game_id, $gamertag=false) {
	$url = 'http://live.xbox.com/en-US/profile/Achievements/ViewAchievementDetails.aspx?tid='.$game_id;
	if($gamertag!='') $url .= '&amp;compareTo='.$gamertag;
	return $url;
} // end func: hl_xbox_get_xbox_live_game_url







function hl_xbox_api_check_new_gamertag($gamertag) {
	global $wpdb;
	$id = $wpdb->get_var($wpdb->prepare('SELECT id FROM '.HL_XBOX_DB_PREFIX.'users WHERE gamertag=%s LIMIT 1',$gamertag));
	if($id>0) return false;
	$url = HL_XBOX_API_URL.urlencode($gamertag);
	$resp = wp_remote_get($url);
	if(!$resp or $resp->errors or $resp['response']['code']!='200' or $resp['body']=='') return false;
	$data = $resp['body'];
	if(!$data) return false;
	$xml = simplexml_load_string($data);
	if(!$xml) return false;
	if($xml->PresenceInfo->Valid=='true') return true;
	return false;
} // end func: hl_xbox_api_check_new_gamertag






/*
	Calculates percent, divbyzero check
*/
if(!function_exists('hl_percent')) {
	function hl_percent($numerator, $denominator=100) {
		if($denominator==0) return 0;
		return round($numerator/$denominator*100);
	} // end func: hl_percent
}


/*
  print_r() wrapped in <pre> tags
*/
if(!function_exists('hl_print_r')) {
	function hl_print_r() {
		echo '<pre>';
		foreach(func_get_args() as $arg) {
			print_r($arg);
		}
		echo '</pre>';
	} // end func: hl_print_r
}


/*
  var_dump() wrapped in <pre> tags
*/
if(!function_exists('hl_var_dump')) {
	function hl_var_dump() {
		echo '<pre>';
		foreach(func_get_args() as $arg) {
			var_dump($arg);
		}
		echo '</pre>';
	} // end func: hl_var_dump
}


/*
  Escapes output via htmlspecialchars
*/
if(!function_exists('hl_e')) {
	function hl_e($str) {
		return htmlspecialchars($str);
	} // end func: hl_e
}


/*
	Pluralise a word if necessary
*/
if(!function_exists('hl_plural')):
	function hl_plural($num, $single, $plural=false) {
		$num = intval($num);
		if($num==1) return $single;
		if($plural) return $plural;
		return $single.'s';
	} // end func: hl_plural
endif;


/*
  Converts a string to a URL slug like format
  * Does not test for uniqueness  
*/
if(!function_exists('hl_slugify')) {
	function hl_slugify($str) {
		$str = strtolower(trim($str, '-'));
		$str = preg_replace('~[^\\pL\d]+~u', '-', $str);
		$str = preg_replace('~[^-\w]+~', '', $str);
		return $str;
	} // end func: hl_slugify
}


/*
	Display time ago, seconds, minutes etc
*/
if(!function_exists('hl_time_ago')):
	function hl_time_ago($timestamp, $now=false) {
		if(!$now) $now = date_i18n('U');
		$then = (is_integer($timestamp))?$timestamp:strtotime($timestamp);
		$seconds = abs($now-$then);
		if($seconds<60) return $seconds.' '.hl_plural($seconds,'second');
		$minutes = round($seconds/60);
		if($minutes<60) return $minutes.' '.hl_plural($minutes,'minute');
		$hours = round($seconds/3600);
		if($hours<24) return $hours.' '.hl_plural($hours,'hour');
		$days = round($seconds/86400);
		if($days<7) return $days.' '.hl_plural($days,'day');
		$weeks = round($seconds/604800);
		if($weeks<=4) return $weeks.' '.hl_plural($weeks,'week');
		$months = round($seconds/2613600);
		if($months<=12) return $months.' '.hl_plural($months,'month');
		$years = round($seconds/31557600);
		return $years.' '.hl_plural($years,'year');
	} // end func: hl_time_ago
endif;

