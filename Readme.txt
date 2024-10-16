=== Antivirus with G DATA VaaS Integration ===
Contributors: @gdatavaas
Tags: antivirus, security, vaas, malware, malicious
Requires at least: 6.2
Tested up to: 6.6
Requires PHP: 8.1
Stable tag: 2.0.2
License: GNU General Public License v3.0
License URI: https://github.com/GDATASoftwareAG/vaas/blob/main/LICENSE

Welcome to the WordPress Antivirus Plugin with G DATA VaaS integration! It adds an additional layer of security to your WordPress installation.

== Description ==

This plugin is freely available for individual and small business users, providing a high level of security for your WordPress site. For commercial entities, we offer the opportunity to secure your customers' sites. This partnership not only enhances your security offerings but also demonstrates your commitment to customer safety. [You can get your credentials via our landing page](https://vaas.gdata.de/login)

Interested organizations are encouraged to [contact us for more details](mailto:oem@gdata.de) on how to leverage this powerful antivirus solution. This integration ensures that your WordPress website remains protected from potential threats and malware.

== Features ==

- Real-time Scanning: The plugin performs real-time scanning of file uploads through WordPress upload forms (media, plugin or theme uploads), preventing malicious content from entering your website.
  
- Full WordPress Scan: Conduct a comprehensive scan of your entire WordPress installation to identify and eliminate any existing malware.

- Post Scans: Scan blog posts done by your site authors.

- Comment Scans: Even the comments of your users can be checked for viruses.

- Full WordPress Scan: Conduct a comprehensive scan of your entire WordPress installation to identify and eliminate any existing malware.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/gdata-antivirus` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the VaaS menu item to configure the plugin.

== Frequently Asked Questions ==

= Is this plugin free to use for individual and small business users? =

Yes, this plugin is freely available for individual and small business users.

= How can commercial entities leverage this antivirus solution? =

Commercial entities are encouraged to contact us via vaas@gdata.de for more details on partnership opportunities.

= Want to contribute? =

While the released code is hosted on the WordPress svn, we develop the plugin on github. [Here you can find the repository for the plugin development](https://github.com/GDATASoftwareAG/wordpress-gdata-antivirus).

== Screenshots ==  
1. Enter your credentials.
2. Shows the image upload scan.
3. Full OnDemand Scan.
4. Scan on Upload.

== Changelog ==

= 2.0.2 =
* also fix the bug for another failure case

= 2.0.1 =
* fix a bug with the live view

= 2.0.0 =
* scope dependencies to avoid dependency conditions

= 1.0.1 =
* add the link to the VaaS onboarding page to the admin menu
* add a link to the github repository to the readme.txt

= 1.0.0 =
* Initial release

= 0.1.3 =
* Initial upload for review.