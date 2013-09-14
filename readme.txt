=== Timed Content ===
Contributors: kjvtough
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=5F58ELJ9R3PVL&lc=CA&item_name=Timed%20Content%20Wordpress%20Plugin%20Donation&currency_code=CAD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: marketing, marketing tool, post, page, time, timer, timed, show, hide, content 
Requires at least: 2.0.2
Tested up to: 3.6
Stable tag: 1.2
License: GPL2

Plugin to show or hide portions of a Page or Post at a specified time after loading. 

== Description ==
The Timed Page/Post Content plugin is a marketing tool that allows users to specify that a portion of a Page or Post should appear/be visible or 
disappear/be invisible based on given time characteristics. Suppose, for example, you embed a video into a Post; once the viewer has enough information 
from the video, the request to take a specific action can be set to appear.  

* A "client-side" shortcode that allows the marking of content to appear or disappear after a given time interval; a "fade" 
effect is included.  This functionality is intended to be used for special effects only, as content marked in this manner 
is still visible in the HTML source and, therefore, not a secure method of hiding content.
* A "server-side" shortcode that allows the marking of content to be visible only during a specified date/time interval.  This 
functionality **can** be used as a secure method of hiding content, because the marked content will be included in the Page/Post 
**only** when viewed in the specified date/time interval.

A TinyMCE button is included to help users build the shortcodes. See the Screenshots tab for more info.

For more documentation, visit the [plugin's page][plugin home].

[plugin home]: http://www.notabenemarketing.com/resources/wordpress-plugins/timed-content-plugin
            "Visit the plugin's home page at Nota Bene Marketing"

== Installation ==
**Note:** `XXX` refers to the current version release.
= Automatic method =
1. Click 'Add New' on the 'Plugins' page.
1. Upload `timed-content-XXX.zip` using the file uploader on the page

= Manual method =
1. Unzip `timed-content-XXX.zip` and upload the `timed-content` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

Coming soon

== Screenshots ==

1. An example showing use of the [timed-content-client] shortcode.  The "alarm clock" button on the editor menubar brings up a 
dialog box to help build the Timed Content shortcodes.
2. The "Add Timed Content shortcode" dialog showing the Client tab.  Check the attribute you want to add and fill in the textboxes.
3. The "Add Timed Content shortcode" dialog showing the Server tab.  Check the attribute you want to add, then click on the Date/Time textbox.
4. The date/time picker helps you format a correct date and time.  Don't forget to set the timezone.
5. You can use both shortcodes together and with other shortcodes supported by your Wordpress installation.

== Changelog ==

= 1.2 =
* Upgraded AnyTime JavaScript library
* `timed-content.js` is now always loaded (Size > 1KB, so not a lot of extra overhead); fixes bug when multiple/nested shortcodes are used

= 1.1 =
* Fixed some internal filename discrepancies.

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.2 =
AnyTime JavaScript library was outdated, breaking the Timed Content dialog box. Upgrade now.

= 1.1 =
Fixed some internal filename discrepancies, causing visual editor to break.  Upgrade now.


== Examples ==

`[timed-content-client show="1:00"]Show me after one minute.  Since we don't want a fade-in, we can leave it out of the "show" attribute completely.[/timed-content-client]`

`[timed-content-client show="1:00:1000"]Show me after one minute with a 1000 millisecond (1 second) fade-in.[/timed-content-client]`

`[timed-content-client hide="1:00:1000"]Hide me after one minute with a 1000 millisecond (1 second) fade-out.[/timed-content-client]`

`[timed-content-client show="1:00:500" hide="5:00:2000"]Show me after one minute with a 500 millisecond (a half-second) fade-in, then hide me after five minutes with a 2000 millisecond (2 seconds) fade-out.[/timed-content-client]`

`[timed-content-server show="2013-Sep-13 20:30:00 -0600"]Show me starting at 8:30 PM Central Standard Time on September 13th, 2013. I will not be displayed before then.[/timed-content-server]`

`[timed-content-server hide="2013-Sep-13 20:30:00 -0600"]Hide me starting at 8:30 PM Central Standard Time on September 13th, 2013.  I will not be displayed after then[/timed-content-server]`

`[timed-content-server show="2013-Sep-13 20:30:00 -0600" hide="2013-Sep-13 21:30:00 -0600"]Show me starting at 8:30 PM Central Standard Time on September 13th, 2013, then hide me an hour later. I will not be displayed before or after then.[/timed-content-server]`