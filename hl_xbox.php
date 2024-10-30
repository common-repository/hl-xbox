<?php
/*
Plugin Name: HL Xbox
Plugin URI: http://hybridlogic.co.uk/code/wordpress-plugins/hl-xbox/
Description: WARNING The API used by HL Xbox is no longer updated. HL Xbox lets you track Xbox Live data for multiple gamertags and display it via an easy to customise widget.
Author: Luke Lanchester
Version: 2011.3.1
Author URI: http://www.lukelanchester.com/
Created: 2010-07-03
Modified: 2011-03-01
*/

define('HL_XBOX_LOADED', true);
define('HL_XBOX_DB_PREFIX', $table_prefix.'hl_xbox_');
define('HL_XBOX_DIR', plugin_dir_path(__FILE__)); // inc /
define('HL_XBOX_URL', plugin_dir_url(__FILE__)); // inc /
define('HL_XBOX_API_URL', 'http://xboxapi.duncanmackenzie.net/gamertag.ashx?GamerTag=');
define('HL_XBOX_API_CACHE_LIFETIME', 3600); // 1 hour, do not set to less, API itself has a cache life of 1 hour too
define('HL_XBOX_AVATAR_CACHE_LIFETIME', 604800); // Cache User profile image, default 1 week
define('HL_XBOX_CRON_KEY', ''); // ?hl_xbox_cron=KEY, leave blank to disable
define('HL_XBOX_SCHEDULED_EVENT_ACTION', 'hl_xbox_scheduled_event');

require_once HL_XBOX_DIR.'admin.php'; // Admin views + functionality
require_once HL_XBOX_DIR.'api.php'; // Xbox API related methodsality
if(!class_exists('EpiCurl')) require_once HL_XBOX_DIR.'EpiCurl.php'; // Third Part multi_curl library
require_once HL_XBOX_DIR.'functions.php'; // Utility functions + helpers
require_once HL_XBOX_DIR.'widget.php'; // Widget class

add_action('admin_menu', 'hl_xbox_admin_menu'); // Add menu pages
add_action(HL_XBOX_SCHEDULED_EVENT_ACTION, 'hl_xbox_cron_handler'); // Used by the WordPress Event Scheduler
add_action('init', 'hl_xbox_init'); // On load, for GET-like cron jobs
add_action('widgets_init', create_function('', 'return register_widget("hl_xbox_widget");')); // Add widget
register_activation_hook(__FILE__,'hl_xbox_install');
register_deactivation_hook(__FILE__,'hl_xbox_uninstall');
