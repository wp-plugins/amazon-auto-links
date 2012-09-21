=== Amazon Auto Links ===
Contributors: Michael Uno, miunosoft
Donate link: http://michaeluno.jp/en/donate
Tags: amazon, affiliate, miunosoft, links, ads
Requires at least: 3.0
Tested up to: 3.4.2
Stable tag: 1.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Generates links of Amazon products just coming out today. You just pick categories and they appear even in JavaScript disabled browsers.

== Description ==

Still manually searching products and pasting affiliate links? What happens if the products get outdated? With this plugin, you do not have to worry about it nor trouble to do such repetitive tasks. Just pick categories which suit your site and it will automatically displays the links of decent products just coming out from Amazon today.

The links are tagged with your Amazon Associate ID. The plugin supports 10 Amazon locales and works even on JavaScript disabled browsers. Insert the ads as widget or place generated shortcode or PHP code where the links should appear.

The features include:

* Supports all Amazon locales including Germany, Japan, Italy, Spain, UK, US, Canada, France, Austria, and China.
* Automatic insertion in posts and feeds. You just check the checkboxes where you want the links to appear.
* Image Size. The size of thumbnails can be specified. It supports up to 500 pixel large with a clean resolution.
* Works without JavaScript. Some visitors turn off JavaScript for security and most ads including Google Adsense will not show up to them. But this one works.
* Random/Title/Date sort order. It's totally possible to show links in random order. 
* Shortcode to embed the ads into posts and pages. 
* PHP function to insert in the theme.
* Blacklist. If you want certain products not to be shown, the black list can be set by ASIN.
  
== Installation ==

1. Upload `amazonautolinks.php` and other files compressed in the zip folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to Settings -> Amazon Auto Link -> New Unit.
1. Configure the options and select categories.
1. After saving the unit option, go to 'Manage Units' to get the shortcode or if you check one of the insert option, the links will automatically appear in posts or feeds depending on your choice. 

== Frequently asked questions ==

= Do I need Amazon Associate ID to use this plug-in? =

Yes. Otherwise, you don't get any revenue. You can get it by signing up for [Amazon Associates](https://affiliate-program.amazon.com/).  

= What would be the benefit to upgrade to the pro version? =

With the pro version, unlimited numbers of units can be created. Also the number of categories to be added per unit, the number of items to show in a unit are unrestriceted as well. Plus, it's possible to change the design of the links by editing the HTML format.

== Screenshots ==

1. Setting Page (Overview)
2. Setting Page (Selecting Categories)

== Changelog ==

= 1.0.5 =
* Improved: the caching method. Now the caches of links are renewed in the background.

= 1.0.4 =
* Added: the settings link in the plugin list page of the administration panel.
* Improved: the browsing speed in the category selection page by reducing the cache elements.

= 1.0.3 =
* Fixed: an issue that in below PHP v5.2.4, the link descriptions could not be retrieved properly and the edit and view page links were broken.
* Improved: the browsing speed in the category selection page with caches.
* Removed: the widget functionality since it produces a blank page in some systems and the cause and solution have not been discovered.

= 1.0.2 =
* Fixed: an issue that form buttons do not appear in the category selection page in WordPress version 3.1x or ealier.

= 1.0.1 =
* Added: the Widget option.

= 1.0.0 =
* Initial Release
