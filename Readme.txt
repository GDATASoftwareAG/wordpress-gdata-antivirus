=== Antivirus with G DATA VaaS Integration ===
Contributors: @gdatavaas
Tags: antivirus, security, vaas, malware, malicious
Requires at least: 6.2
Tested up to: 6.8
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

= 3.2.8 =

* dependency updates

= 3.2.7 =

* dependency updates

= 3.2.6 =

* dependency updates

= 3.2.3 =

* dependency updates

= 3.2.2 =

* dependency updates
* update wordpress version for development and testing to 6.8.2

= 3.2.1 =

* dependency updates

= 3.2.0 =

* tested current wordpress versions

= 3.1.2 =

* checkin composer.lock to actually serve updates

= 3.0.3 =

* Add platform directive (8.1), so we can actually support older php versions.

= 3.0.2 =

* Show error message for wrong credentials

= 3.0.1 =

| Package | Type | Update | Change |
|---|---|---|---|
| [gdata/vaas](https://togithub.com/GDATASoftwareAG/vaas) ([source](https://togithub.com/GDATASoftwareAG/vaas-php)) | require | patch | `11.0.0` -> `11.0.1` |

[Details](https://github.com/GDATASoftwareAG/wordpress-gdata-antivirus/pull/55)

= 3.0.0 =

Major release due to the major update of the VaaS-SDK.

= 2.1.6 =

| Package | Type | Update | Change |
|---|---|---|---|
| [illuminate/container](https://laravel.com) ([source](https://togithub.com/illuminate/container)) | require | minor | `11.40.0` -> `11.41.3` |
| [phpunit/phpunit](https://phpunit.de/) ([source](https://togithub.com/sebastianbergmann/phpunit)) | require-dev | patch | `11.5.3` -> `11.5.7` |

= 2.1.5 = 

| Package | Type | Update | Change |
|---|---|---|---|
| [illuminate/container](https://laravel.com) ([source](https://togithub.com/illuminate/container)) | require | minor | `11.35.1` -> `11.37.0` |
| [phpunit/phpunit](https://phpunit.de/) ([source](https://togithub.com/sebastianbergmann/phpunit)) | require-dev | patch | `11.5.1` -> `11.5.2` |
| [symfony/finder](https://symfony.com) ([source](https://togithub.com/symfony/finder)) | require-dev | patch | `7.2.0` -> `7.2.2` |

= 2.1.2 =
* update illuminate/container from 11.33.2 to 11.34.2
* update phpunit/phpunit from 11.4.3 to 11.4.4
* update symfony/finder from 7.1.6 to 7.2.0

= 2.1.1 =
* updates: update illuminate/container to 11.33.2

= 2.1.0 =
* bugfix: [full scan runs in loop](https://github.com/GDATASoftwareAG/wordpress-gdata-antivirus/issues/37)
* bugifx: fails on duplicate key when detecting the same file twice

= 2.0.9 =
* bugfix: reconnect on long running scans
* add detection and sha256 name to upload detection
* add detection and sha256 to the findings page

= 2.0.8 =
* bugfix: posts could not be saved

= 2.0.7 =
* enable upload scan by removing the nonce check temporary

= 2.0.6 =
* changed the full scan indicator. the counting method was error prone

= 2.0.5 =
* don't connect to the backend for every page load

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