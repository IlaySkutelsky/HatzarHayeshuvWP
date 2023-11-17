=== Quick and Easy Post creation for ACF Relationship Fields ===
Contributors: cyrilbatillat
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LU5K2FXVMYCUS
Tags:  acf, advanced custom fields, relationship, post object, field, post creation, shortcut, workflow, admin, administration
Requires at least: 4.5
Tested up to: 4.6
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Quick & Easy Post creation on your Advanced Custom Fields (ACF) 'Relationship' & 'Post Object' Fields

== Description ==

This plugin is a excellent companion of ACF (Advanced Custom Fields).

When dealing with 'Relationship' or 'Post Object' fields (which links a post to one or multiple other posts), you often stumble on having to link to a post that does not exist yet. This is a frustrating and time-consuming experience: you have to save the content you were working on, then create the new post, and finally reload your primary content to be able to link to the newly created post.

This plugin simplifies this process by allowing you to create the related posts on the fly.

> #### PRO version
> This plugin is only compatible with the **free** version of Advanced Custom Fields.
> [A PRO version](https://codecanyon.net/item/quick-and-easy-post-creation-for-acf-relationship-fields-pro/17201274), compatible with ACF PRO, [can be purchased here](https://codecanyon.net/item/quick-and-easy-post-creation-for-acf-relationship-fields-pro/17201274).

= Translations =
This plugin is actually translated in the following languages:

* English
* French

Feel free to help me to enhance existing translations or to propose other languages.

= Support =
Please use [the dedicated forum](https://wordpress.org/support/plugin/quick-and-easy-post-creation-for-acf-relationship-fields) for any bug or improvement suggestion.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress

= Once activated =
You can enable on-the-fly post creation on a field basis.

1. In your ACF Field Groups, locate the field you want to enable on-the-fly post creation.
1. Check "Yes" on the field setting "Display a link to create content on the fly?"
1. That's all. Now, while editing your content, you'll see a button next to your field to create a new post (see Screenshots section)


== Frequently Asked Questions ==

= Is it compatible with Advanced Custom Fields PRO version? =

No. A PRO version of `Quick and Easy Post creation for ACF Relationship Fields` plugin, compatible with ACF PRO, [can be purchased here](https://codecanyon.net/item/quick-and-easy-post-creation-for-acf-relationship-fields-pro/17201274).

= Can we make the lightbox wider? =

Unfortunately not for the moment. This will be a possible enhancement when [this bug](https://core.trac.wordpress.org/ticket/27473 "See the bug ticket") will be fixed.

= Does this add-on handle bidirectional relationships? =

Bidirectional relationships are out of the scope of this add-on. However, [the ACF documentation](https://www.advancedcustomfields.com/resources/bidirectional-relationships/) gives a great example to achieve this.

= Is it possible to pre-populate a child field depending on the parent from where it’s been created? =

Yes it is. This add-on loads the child post form in an iframe with some additional URL params:

1. acf_rc_from_content_type: the post type of your parent post.
1. acf_rc_from_content_ID: the ID of your parent post.

You can use these URL params to pre-populate an ACF field on your child post. Example:

    <?php
    add_filter('acf/load_field/name=${NAME_OF_YOUR_ACF_FIELD}', 'populate_acf_field' );
    function populate_acf_field( $field ) {
        if( !empty( $_REQUEST['acf_rc_from_content_type'] ) && $_REQUEST['acf_rc_from_content_type'] == '${YOUR_PARENT_CONTENT_TYPE}' && !empty( $_REQUEST['acf_rc_from_content_ID'] ) ) {
            $field['value'] = 'whatever you want';
        }
        return $field;
    }


== Screenshots ==

1. Enable post creation on your 'Relationship' or 'Post Object' Field, in ACF settings

2. Notice the button that allows you to create a new content (in this case, a new album)

3. The new post can be created in a dedicated popup. Fill the fields as you would have done normally, and publish the post.

4. The new post is added in your relationship field


== Changelog ==

= 2.2 =
Compatibility with translate.wordpress.org

= 2.1 =
* Plugin review invitation

= 2.0 =
* Now supporting 'Post Object' fields ! :)
* Fix CSS bugs in tooltip positioning

= 1.2 =
* Core: Add a link to the PRO version of this plugin when detecting ACF PRO version.

= 1.1 =
* Bug fix: plugin was not working with relationship fields on attachments (in media modal)

= 1.0 =
* First release


== Credits ==
Thanks to Elliot Condon for his amazing plugin Advanced Custom Fields