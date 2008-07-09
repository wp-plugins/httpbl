=== Project Honey Pot Http:BL ===
Tags: anti-spam, spam, harvester, crawler, comment, filter, block, project honey pot, httpbl, security
Contributors: Thaya Kareeson
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=madeinthayaland@gmail.com&currency_code=USD&amount=&return=&item_name=Donate+a+cup+of+coffee+or+two+for+Project+Honey+Pot+HttpBL+WordPress+Plugin
Requires at least: 2.5
Tested up to: 2.5.1
Stable Tag: 1.0.1

Project Honey Pot Http:BL allows you to verify IP addresses of clients connecting to your blog against the <a href="http://www.projecthoneypot.org/?rf=45626">Project Honey Pot</a> database.

== Description ==

Project Honey Pot Http:BL allows you to verify visitors' IP address against the
<a href="http://www.projecthoneypot.org/?rf=45626">Project Honey Pot</a>
database.  Using the Http:BL API, this plugin flags, logs, and blocks visitors
with a high threat score, helping you prevent harvesters, spammers, or other
suspicious bots from abusing your blog.

This plugin requires you to sign up for a free account at Project Honey Pot
(http://www.projecthoneypot.org/?rf=45626) so that you can use their Http:BL
API to verify your visitors.

This plugin is based on Jan Stepien's http:BL version 1.4
(http://wordpress.org/extend/plugins/httpbl/) which is no longer being
supported.  This version of the plugin fixes a lot of database bugs and
usability issues that the original plugin had.

== Installation ==

1. Upload the plugin to your plugins folder: `wp-content/plugins/`.
2. Activate the 'Project Honey Pot Http:BL' plugin from the Plugins admin panel.
3. Go to the Options -> PHP Http:BL admin panel to configure the plugin.
4. Enter your Project Honey Pot Access Key.  (If you've already signed up for a
   free account at projecthoneypot.org, you can get the access key from
   http://www.projecthoneypot.org/httpbl_configure.php.
5. (optional) Change Personal Honey Pot link.  This is the link that *blocked*
   visitors get redirected to.  Optimally, you would want to put some kind of
   bot trap URL here.
6. (optional) Disable logging or 'Log only blocked visitors' if you want to save
   database space.
7. (optional) Enable statistics display and place php_httpbl_stats() in your theme.

== Frequently Asked Questions ==

= What is Project Honey Pot? =
Project Honey Pot is the first and only distributed system for identifying
spammers and the spambots they use to scrape addresses from your website. Using
the Project Honey Pot system you can install addresses that are custom-tagged to
the time and IP address of a visitor to your site. If one of these addresses
begins receiving email we not only can tell that the messages are spam, but also
the exact moment when the address was harvested and the IP address that gathered
it. <a href="http://projecthoneypot.org/about_us.php">More information here.</a>

= Does this work with cache plugins? =
Not yet.  It is do-able but it requires .htaccess hacking and folder relocation.
I would rather make this plugin cacheable and plug-and-play than release a hack.

== Changelog ==

1.0.1
- Fixed readme.txt typo to use php_httpbl_stats() instead of httpbl_stats()
1.0
- Initial release
- Fixed database bugs
- Fixed usability
- Improved documentation

== Screenshots ==

None Yet
