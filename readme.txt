=== HL Xbox ===
Contributors: Dachande663
Donate link: http://hybridlogic.co.uk/code/wordpress-plugins/hl-xbox/
Tags: xbox, xbox360, microsoft, gamertag, xboxlive, live, hybridlogic
Requires at least: 3.0.0
Tested up to: 3.0.1
Stable tag: trunk

WARNING: The API used by HL Xbox is no longer updated. HL Xbox lets you track Xbox Live data for multiple gamertags and display it via an easy to customise widget.

== Description ==

WARNING: As of January 2011 the data source used by HL Xbox has been shut down, rendering this plugin inactive. A request has been made to Microsoft for access to their API, but no word has come yet.

HL Xbox goes above and beyond the normal WordPress Xbox Live Gamertag plugin. It allows you to track multiple gamertags, keeping a local copy of each gamers history including the games they've played, achievements and gamerscore on your WordPress blog. An easy to use widget let's you display this in your sidebar, with customisable options.

== Installation ==

1. Upload hl_xbox directory to your /wp-content/plugins directory
2. Chmod cache, games and users directories so they are writable by server
3. Activate plugin in WordPress admin
4. In WordPress admin, go to Xbox -> Users -> Add new User
5. Add the HL Xbox widget to your sidebar(s)

To modify the widget theme:

1. Copy the hl_xbox_widget.php file from /wp-content/plugins/hl_xbox to /wp-content/themes/*your-current-theme*/
2. Edit the new hl_xbox_widget.php file in your theme directory
3. You can now update the plugin as normal and your changes will not be overwritten

== Frequently Asked Questions ==

= Why do my games not show up straight away? =

HL Xbox relies on a third party API. This API limits requests to once per hour and occasionally suffers delays or outages. Until Microsoft release access to their data however this is the best available.

= Why can't I see all my games? =

The API HL Xbox uses only provides information on the 16 most recently played games for any user. This is a limitation HL Xbox cannot get around unfortunately.

== Screenshots ==

1. Example user list, showing Gamertag, Gamerscore, last played and more. Clicking on a user shows their game history.
2. Default widget styling with the WordPress TwentyTen theme.

== Changelog ==

= 2011.3.1 =
* Added warning message due to Microsoft disabling third-party API.

= 2010.7.3 =
* Initial development

= 2010.7.5 =
* Importer now pulls in asynchronously

= 2010.7.19 =
* Importer now tracks multiple accounts

= 2010.7.20 =
* Added gamerscore history tracking

= 2010.7.28 =
* Major bug fixes, refactored API classes

= 2010.9.3 =

* First public release
* Added widget + controls
* Added WordPress event scheduling handlers

= 2010.9.4 =

* Gamers can now see a graph of their gamerscore over time

= 2010.9.5 =

* The first time a user plays a game is now recorded more accurately

== Upgrade Notice ==

= 2010.9.3 =
First public release