=== Simple Export Import for ACF Data ===
Contributors: opcodespace
Tags: Export, Import, Page, Post, Custom Post Type, Advanced custom field, Field Value
Requires at least: 5.4.0
Tested up to: 6.3.2
Requires PHP: 7.0
Stable tag: 1.3.12
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

With this plugin you simply export and import page, post and custom post. This plugin supports ACF fields.

== Description ==
This "Simple Export Import for ACF Data" plugin is very helpful for developers who develop a theme in a staging website with ACF and deploy it to live.  You must have ACF installed and activated, before using this plugin.

**Important Note:** Both sites must have the same version of "Simple Export Import for ACF Data".

= Supported Post/Page data in FREE Plugin =
* Title
* Slug
* Content (*Importing Image, File from source site to target site is supported in paid plugin.)
* Featured Image
* Excerpt
* Status of Post / Page including password

= Supported ACF fields in FREE Plugin =
* Image (* only you can import 10 images in total)
* Text
* Text Area
* Number
* Email
* Url
* Password
* Wysiwyg Editor
* Choice (Select, Multi-select, Checkbox, Radio, Button Group, True / False)
* User
* Google Map
* Date Picker
* Date Time Picker
* Time Picker

= Supported ACF layout in FREE Plugin =
* Repeater
* Group
* Clone

= Supported ACF fields and layout in Paid Plugin =
* Flexible Content
* Image
* Gallery
* File
* Link (Source domain will be replaced with target domain)
* Bulk Export/Import
* ACF Options
* Taxonomy (Category, Tag, Custom Taxonomy) of Post / Custom Post Type.
* Content (*Importing Image, File from source site to target site is supported. Links are replaced with new domain)

[Simple Export Import PRO for ACF](https://opcodespace.com/product/simple-export-import-pro-for-acf/)
[Simple Export Import PRO for ACF – Unlimited](https://opcodespace.com/product/simple-export-import-pro-for-acf-unlimited/)

**Notes:** Image, Gallery will work if your website is public. From localhost to live website will not work as localhost is not publicly accessible. If you can set your website public, image, gallery fields will work.

**Notes for Taxonomy:** If you have already related terms of post, this plugin can import and attach terms to the post or custom post type. If you have hierarchical taxonomies, you must have taxonomies in your destination site. If slug of term is matched, it attaches to post. Otherwise, it creates a new term, but does not maintain hierarchy.

*If you want new features or have bugged, please email support@opcodespace.com or send a request [here](https://opcodespace.com/contact-us/)*

= Tutorial =
[vimeo https://vimeo.com/748514481]

= Privacy Policy =
We are not disclosing or storing any data outside your website. Moreover, we are not storing data in browser cookie as well. So, it is safe to use. While you insert License key, we are storing only your domain in our system.

= Terms & Condition =
We strongly recommend that you should keep your website backup before importing data. If your site get broken due to importing data or using this plugin, we cannot compensate or fix your site. This plugin depends on [Advanced Custom Fields plugin](https://www.advancedcustomfields.com/). If ACF updates its field structure, it could be affected. OP Code Space LLC has right to terminate this plugin if it is found that it is not working as expected or if Op Code Space LLC is unable to support anymore or if it is found that it is not compatible with ACF. We do not guarantee that this plugin will work on all websites.

While you are using this plugin, you agree to these terms & condition.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the `Settings->Simple Export Import` screen to export & Import data

== Frequently Asked Questions ==

= Does it support fields of `Advanced Custom Field` plugin? =
Yes, free plugin is supporting many fields including repeater, groups. Please see description in details.

== Screenshots ==
1. Export
2. Import

== Changelog ==
= 1.3.13 =
* Bug Fixed: Import Post type filtering fixed.

= 1.3.12 =
* Bug Fixed: Saving license key

= 1.3.11 =
* Bug Fixed: sanitize json file

= 1.3.9 =
* Bug Fixed: undefined destroy fixed in js.
* Enhancement: Filtering options (Taxonomies and Terms) added on Import.

= 1.3.8 =
* Enhancement: Filtering options (Taxonomies and Terms) added on Export

= 1.3.7 =
* Enhancement: Free Plugin is supporting to import max 10 Images.

= 1.3.6 =
* Bug Fixed: Preventing to display warning message

= 1.3.5 =
* Bug Fixed: Fatal Error fixed

= 1.3.4 =
* Bug Fixed: If empty image or file, throwing fatal error

= 1.3.3 =
* Enhancement: Published Date preserved
* Enhancement: Editor Image, Object file, link are replaced with new domain.

= 1.3.2 =
* Enhancement: Subscription modal added.

= 1.3.1 =
* Bug Fixed: Incorrect post type on bulk importing

= 1.3.0 =
* New Feature: Link Field is supported (Source domain will be replaced with target domain)

= 1.2.5 =
* New Feature: Clone layout

= 1.2.4 =
* Bug Fix: Regenerate thumbnail Image. This process is slower for bulk import.

= 1.2.3 =
* Bug Fix: Empty file is attaching to post.

= 1.2.2 =
* Bug Fix: Fatal Error on importing featured Image

= 1.2.1 =
* Bug Fix: Meta data of media file

= 1.2.0 =
* New Feature: Taxonomy (Category, Tag, Custom Taxonomy) of Post / Custom Post Type

= 1.1.0 =
* New Feature: Flexible Content Layout supported
* New Feature: File field supported
* New Feature: Multiple select supported

= 1.0.1 =
* Bug Fix: Bulk Export

= 1.0.2 =
* New Feature: Featured Image added