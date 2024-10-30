=== Plugin Name ===
Contributors: timwhitlock
Donate link: http://timwhitlock.info/donate-to-a-project/
Tags: twitter, vine, video, widget, sidebar
Requires at least: 3.5.1
Tested up to: 3.5.1
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Show off your latest Vines on your blog (not maintained)

== Description ==

This plugin is **no longer maintained** and is in the process of being removed from this directory.

Please don’t submit support requests, as they will go unanswered.


== Installation ==

This plugin is no longer maintained and is in the process of being removed from this directory.

Below are the original install instructions:


1. Unzip all files to the `/wp-content/plugins/` directory
2. Log into Wordpress admin and activate the 'Latest Vines' plugin through the 'Plugins' menu

Once the plugin is installed you must bind it to a Twitter account as follows:

3. Register a Twitter application at https://dev.twitter.com/apps
4. Note the Consumer key and Consumer secret under OAuth settings
5. Log into Wordpress admin and go to Settings > Twitter API
6. Enter the consumer key and secret and click 'Save settings'
7. Click the 'Connect to Twitter' button and follow the prompts.

Once your site is authenticated you can configure the widget as follows:

8. Log into Wordpress admin and go to Appearance > Widgets
9. Drag 'Latest Vines' from 'Available widgets' to where you want it. e.g. Main Sidebar
10. Optionally configure the widget title and number of Vines to display.

== Frequently Asked Questions ==

= Is this plugin still maintained? =

No. This plugin is **not maintained** and is  the process of being removed from this directory.



== Changelog ==

= 1.1.0 =
* Vine thumbs play on mouseover and pause on mouseout
* Added Russian translations
* Library updates

= 1.0.5 =
* Critical [bugfix](http://wordpress.org/support/topic/fatal-error-on-version-104-in-frontend) 

= 1.0.4 =
* Library update and added translations

= 1.0.3 =
* Allow library coexist across plugins

= 1.0.2 =
Library update and readme edits

= 1.0.1 =
* Fix for IE 9

= 1.0.0 =
* Initial push to wordpress.org

== Notice ==

This plugin is **no longer maintained** and is in the process of being removed from this directory.

Please don’t submit support requests, as they will go unanswered.


== Theming ==

For starters you can alter some of the HTML using built-in WordPress features.  
See [Widget Filters](http://codex.wordpress.org/Plugin_API/Filter_Reference#Widgets)
and [Widgetizing Themes](http://codex.wordpress.org/Widgetizing_Themes)

**CSS**

This plugin contains no default CSS. That's deliberate, so you can style it how you want.


**Custom HTML**

If you want to override the default markup of the vines, the following filters are also available:

* Add a header between the widget title and the vines with `latest_vines_render_before`
* Render each thumbnail image with `latest_vines_render_thumb`
* Render each composite vine with `latest_vines_render_vine`
* Override the unordered list for vines with `latest_vines_render_list` 
* Add a footer before the end of the widget with `latest_vines_render_after`

Here's an **example** of using some of the above in your theme's functions.php file:

    add_filter('latest_vines_render_thumb', function( $url, $width, $height ){
        return '<img src="'.$url.'" width="145" height="145" class="my-thumb" />';
    }, 10 , 3 );
    
    add_filter('latest_vines_render_vine', function( $url, $img, array $meta ){
        return $url; // <- will use default
    }, 10 , 3 );
    
    add_filter('latest_vines_render_after', function(){
        return '<footer><a href="https://vine.co/me">More from me</a></footer>';
    }, 10, 0 );

Be warned that if you change the default HTML, you may break the JavaScript and have to override that too.


