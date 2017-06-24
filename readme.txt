=== Super Static Cache ===
Contributors: Hito
Donate link: http://www.hitoy.org/super-static-cache-for-wordperss.html#Donations
Tags: Wordpress Static Cache, WP Cache Plugin, Website caching plugin
Requires at least: 3.0.1
Tested up to: 4.8
Stable tag: 3.3.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A fast and simple cache plugin for wordpress, can create static html files. It provides three cache mode, make your web site faster than ever before.

== Description ==
Confused with the Complex settings of wp super cache or cos-html-cache not work on your blog? This is a cache Plugin for WordPress with simple configuration and more efficient caching Efficiency. Your blog will not shut down cause high pressure of databases;

This plugin is especially suitable for site which have more than 2000 posts;

After you install this plugin,  do not forget to enable the cache function in  the setting->Super Static Cache

== Installation ==

1. Upload `Super Static Cache` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==
1. Admin Panel

== Upgrade Notice ==
= 3.3.4 =
* cache build action bug fixed
* purge cache bug fixed

= 3.3.4 =
* Support Cache File Compress, save 50% disk space at least
* fix some bugs

= 3.3.3 =
* fix bug that cache robots.txt

= 3.3.2 =
* bug fixed

= 3.3.1 =
* Add functionality to specify a page that does not cache

= 3.3.0 =
* Next And previous Post Auto Renew Cache
* Program Optimization

= 3.2.9 =
* cache file File permissions func update
* bug fixed

= 3.2.8 =
* Bug Fixed
* UI Update

= 3.2.6 =
* Bug Fixed

= 3.2.5 =
* Security level upgrade, Prevent other contents from being deleted
* qiniu CDN function support

= 3.2.4 =
* Bug Fixed

= 3.2.3 =
* Except Cache Function fixed

= 3.2.2 = 
* Bug Fixed


= 3.2.1 =
* Bug Fixed
* Support utf-8 cachefile name in *nix


= 3.2.0 =
* Large update
* Management interface upgrade
* add cache management function


= 3.1.3 =
* Fixed Bug document root test error


= 3.1.2 =
* Fixed Bug When update a page that cache not update


= 3.1.1 =
* Bug Fixed


= 3.1.0 =
* Function enhancement


= 3.0.9 =
* Bug fixed


= 3.0.8 =
* Bug fixed of notice in background management page


= 3.0.7 =
* Nginx & Apache Rewrite Rule Update
* Fix Bug Search function not work when home page is cached


= 3.0.6 =
* translate imporve
* fix bug when in rewrite mode, search function are not work

= 3.0.5 =
* fix bug sometimes a trackback can be cached


= 3.0.4 =
* fix expet cache rule bug

= 3.0.3 =
* fix cache delete bug


= 3.0.2 =
* fix strict mode cache bug 
* fix rewrite rule on apache

= 3.0.1 =
* fix bug sometimes cache null content

= 3.0.0 =
* add php cache mode
* tag and category auto update
* bug fixed

= 2.0.5 =
*Nginx rewrite rule fixed

= 2.0.4 =
* auto update and auto delete cache function bug fixed with unicode url

= 2.0.3 =
* Purge cache functional upgrading
* Apache Rewrite Rules update

= 2.0.2 =
* add non ASCII characters URL cache support

= 2.0.1 =
* Nginx Rewrite Rules bug Fixed 

= 2.0.0 =
* Bug Fixed
* Support rewrite mode
* Support post publish to auto build cache
* Support post update to auto rebuild cache
* Support delete post to delete cache
* English Support

= 1.0.3 =
* Login user visit will also toggle the cache function,if the admin bar are not showing. 

= 1.0.2 =
* Login user visit will not be toggle the cache function to prevent some themes show different Appearance of logged in and non logged in user.


== Frequently Asked Questions ==

= 缓存的加载速度快不快？ =
缓存好之后，下次访问次文章时，web服务器会直接访问这个缓存文件(PHP模式和Rewrite模式)，并不通过wordpress，也不会查询数据库，这样节省了很多资源。即使这样，加载速度也会和您的web服务器有关系。

= super static cache适用于哪些场合？ =
super static cache的诞生起初是为了满足我个人的工作需要，和其它缓存插件一样，有自己适用的场合。 如果你的网站内容很多，访问量大，数据库服务器压力巨大，但是磁盘空间充足，可以选择super static cache，反过来，如果您的网站内容少，流量低，更新频繁,不推荐使用super static cache。

= 如何清除缓存文件？ =
如果你使用的Direct模式，有两种清除方式方式：一：通过FTP或者其它文件管理工具，二：通过Super Static Cache设置页面清除；但是这两种方式只能清除单个页面，如果你的缓存页面很多，推荐使用Rewrite模式，除了上面的方式外，插件在停用的时候就能自动清除所有缓存。

= 严格缓存模式和非严格缓存模式是什么？ =
严格模式和非严格模式仅针对Direct模式，它要解决的是关于URL的问题，严格模式下,类似www.example.com/archives/1这种url是不能被缓存的，非严格模式下，这类的url会被缓存，但是第二次访问服务器一般会重定向到www.example.com/archives/1/(注意后面的斜杠)。如果你使用PHP模式或者Rewrite模式，忽略这项配置即可。

= Why FAQ In Chinese? =
Need Someone Help me to translate Chinese to English
