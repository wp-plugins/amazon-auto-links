=== Amazon Auto Links ===
Contributors: Michael Uno, miunosoft
Donate link: http://michaeluno.jp/en/donate
Tags: amazon, associate, associates, amazon wordpress plugin, miunosoft, link, links, link cloaking, cloak, cloaking, hyperlink, hyperlinks, ad, ads, advertisement, product, products, widget, sidebar, admin, affiliate, affiliate marketing, ecommerce, internet-marketing, marketing, money, monetization, earn money, page, plugin, post, posts, feed, feeds, rss, revenue, shortcode, image, images, thumbnail, thumbnails
Requires at least: 3.0
Tested up to: 3.5
Stable tag: 1.1.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Generates links of Amazon products just coming out today. You just pick categories and they appear even in JavaScript disabled browsers.

== Description ==

Still manually searching products and pasting affiliate links? What happens if the products get outdated? With this plugin, you do not have to worry about it nor trouble to do such repetitive tasks. Just pick categories which suit your site and it will automatically display the links of decent products just coming out from Amazon today.

The links are tagged with your Amazon Associate ID. The plugin supports 10 Amazon locales and works even on JavaScript disabled browsers. Insert the ads as widget or place generated shortcode or PHP code where the links should appear.

<h4>Features</h4>
* Supports all Amazon locales including Germany, Japan, Italy, Spain, UK, US, Canada, France, Austria, and China.
* Automatic insertion in posts and feeds. You just check the checkboxes where you want the product links to appear.
* Widget. Just put it in the sidebar and select the unit you created. The product links will appear in where you wanted.
* Image Size. The size of thumbnails can be specified. It supports up to 500 pixel large with a clean resolution.
* Works without JavaScript. Some visitors turn off JavaScript for security and most ads including Google Adsense will not show up to them. But this one works.
* Random/Title/Date sort order. It's totally possible to show links in random order. 
* Shortcode to embed the ads into posts and pages. 
* PHP function to insert in the theme.
* Blacklist. If you want certain products not to be shown, the black list can be set by ASIN, substring of title and description.
* URL cloaking. You can obfuscate the link urls so it helps to prevent being blocked by browser Ad-bloking add-ons.
  
== Installation ==

1. Upload **`amazonautolinks.php`** and other files compressed in the zip folder to the **`/wp-content/plugins/`** directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to **Settings** -> **Amazon Auto Links** -> **New Unit**.
1. Configure the options and select categories.
1. After saving the unit option, go to **'Manage Units'** to get the shortcode or if you check one of the insert option, the links will automatically appear in posts or feeds depending on your choice. The widget is avaiable in the **Apparence** -> **Widgets** page as well.

== Frequently asked questions ==

= Do I need Amazon Associate ID to use this plug-in? =

Yes. Otherwise, you don't get any revenue. You can get it by signing up for [Amazon Associates](https://affiliate-program.amazon.com/).  

= What would be the benefit to upgrade to the pro version? =

With the pro version, unlimited numbers of units can be created. Also the number of categories per unit, the number of items to display per unit are unrestriceted as well. Plus, it's possible to change the design of the links by editing the HTML format. Please consider upgrading it. [Amazon Auto Links Pro](http://michaeluno.jp/en/amazon-auto-links/amazon-auto-links-pro)

= I selected the category but it still says "Please select a category from the list on the left." and no preview is shown. What should I do? =

Please try setting the **Prefetch Link Categgories** option to **Off**.

= I get a blank white page after adding a unit to the theme. What is it? What should I do? =

It could be the allocated memory capacity for PHP reached the limit. One way to increase it is to embed the following code in your config.php
`define('WP_MEMORY_LIMIT', '128M');`
The part, 128M, should be changed accordingly.

== Screenshots ==

1. **Setting Page** (Creating New Unit)
2. **Setting Page** (Selecting Categories)
3. **Embedding Links below Post**
4. **Widget Sample**

== Changelog ==

= 1.1.7 =
* Fixed: a bug that caches were not cleared with database tables that have a custom prefix.
* Fixed: a bug that the Prefetch Category Lists option did not take effect.

= 1.1.6 =
* Fixed: a minor bug that an error message did not appear properly when category links cannot be retrieved.
* Added: Blacklist by title and description set in the General Settings page.

= 1.1.5 =

* Changed: to force the unit output to close any unclosed HTML tags.
* Fixed: a bug that the plugin requirement check did not work as of v1.1.3.
* Improved: the response speed when first accessing the setting page.

= 1.1.4 =

* Fixed: a bug that shortcode did not work as of v1.1.3.

= 1.1.3 =

* Supported: WordPress 3.5
* Changed: the preview page not to use iframe so that "Could not locate admin.php" error would not occur.
* Fixed: a bug that the style was not loaded in one of the tab page in the plugin setting page.
* Fixed: a bug that the arrow images which indicate where to click did not appear in the category selection page.
* Added: the ability to delete transients for category caches when the pre-fetch option is set to off.
* Added: the unit memory usage in the unit preview page.
* Added: the ability to remove transients when the plug-in is deactivated. 

= 1.1.2 =
* Fixed: a bug which displayed the plugin memory usage in the page footer.

= 1.1.1 = 
* Added: the prefetch category links option, which helps in some servers which sets a low value to the max simultaneous database connections.

= 1.1.0 =
* Fixed: a bug that url cloak option gets unchecked in the option page.
* Fixed: a bug that credit option gets checked in the option page.
* Fixed: an issue that encryption did not work on servers which disable the mcrypt extension.
* Fixed: an issue that some form elements of the admin page did not appear on servers which disable short_open_tag.
* Fixed: a bug that the AmazonAutoLinks() function did not retrieve the correct unit id. 

= 1.0.9 =
* Added: the link cloaking feature.

= 1.0.8 =
* Fixed: a bug that shorcode fails to display the unit saved in version 1.0.7 or later.
* Added: the title length option.
* Added: the link style option.
* Added: the credit insert option.

= 1.0.7 =
* Fixed: an issue that the widget gets un-associated when the unit label gets changed.
* Fixed: an issue that category caches were saved with the wrong name which resulted on not using the cache when available.
* Fixed: an issue that the format of the img tag gets changed when the validation fails when setting up a unit.
* Added: a donation link in the plugin listing page.

= 1.0.6 =
* Added: the rel attribute, rel="nofollow", in the a tag of product links.
* Re-Added: the widget which enables to add units easily on the sidebar.

= 1.0.5 =
* Improved: the caching method. Now the caches of links are renewed in the background.

= 1.0.4 =
* Added: the settings link in the plugin list page of the administration panel.
* Improved: the page load speed in the category selection page by reducing the cache elements.

= 1.0.3 =
* Fixed: an issue that in below PHP v5.2.4, the link descriptions could not be retrieved properly and the edit and view page links were broken.
* Improved: the page load speed in the category selection page with caches.
* Removed: the widget functionality since it produces a blank page in some systems and the cause and solution have not been discovered.

= 1.0.2 =
* Fixed: an issue that form buttons do not appear in the category selection page in WordPress version 3.1x or ealier.

= 1.0.1 =
* Added: the Widget option.

= 1.0.0 =
* Initial Release
