;(function($, acf_relationship_create, undefined) {

    // Bail here if acf is not defined
    if( typeof acf === 'undefined' ) return;


    /**
     * Common function to close tooltip on Relationship & Post Object fields
     **/
    var close_tooltip = function() {
        setTimeout(function(){
            $('#acf-rc-popup').remove();
        }, 200);
    };

    var increment_user_uses = function() {
        if(
            typeof window.acf_relationship_create_field != 'undefined' &&
            typeof window.acf_relationship_create_field.ajax_url != 'undefined' &&
            typeof window.acf_relationship_create_field.current_user != 'undefined' &&
            !isNaN( window.acf_relationship_create_field.current_user )
        ) {
            $.ajax({
                url: window.acf_relationship_create_field.ajax_url,
                method: "POST",
                data: {
                    action: 'acf_rc_increment',
                    user: window.acf_relationship_create_field.current_user
                },
                dataType: 'json'
            }).done(function( msg ) {
                if( typeof msg.success == 'undefined' ) return;
                if( !msg.success ) return;

                if( typeof msg.data.review_msg_dismissed == 'undefined' ) return;
                if( msg.data.review_msg_dismissed ) return;

                if( typeof msg.data.uses == 'undefined' ) return;
                if( isNaN(msg.data.uses) ) return;

                if( typeof msg.data.rating_message_recurrence == 'undefined' ) return;
                if( isNaN(msg.data.rating_message_recurrence) ) return;

                if( msg.data.uses % msg.data.rating_message_recurrence == 0 ) {
                    if( confirm( window.acf_relationship_create_field.i18n.please_review ) ) { // Clic sur OK
                        window.open( 'https://wordpress.org/support/plugin/quick-and-easy-post-creation-for-acf-relationship-fields/reviews/', '_blank' );
                    }
                }
            });
        }
    };

    /**
     * Open a tooltip to choose which kind of CPT to create
     * Inspired from flexible_content field
     *
     * @see acf.fields.flexible_content._open
     * @event click .acf-relationship-create-link
     *
     * @param e Event
     */
    acf.fields.relationship.acf_rc_tooltip_open = function(e) {
        e.preventDefault();

        // Eventually close other tooltips
        $('#acf-rc-popup').remove();

        var $event_target = $(e.target);
        if( !$event_target.is('.acf-relationship-create-link') )
            $event_target = $event_target.parents('.acf-relationship-create-link:first');

        if( $event_target.length != 1 ) return;

        var $field_wrapper = acf.fields.relationship.acf_rc_get_field_wrapper( $event_target );
        if( $field_wrapper.length != 1 ) {
            console.warn('[acf-relationship-create | acf_rc_tooltip_open ] No wrapper found', $event_target);
            return;
        }

        // Get ACF $field for Relationship field
        var $el = $field_wrapper.find('.acf_relationship');

        // Get ACF $field for Post object field
        if( $el.length != 1 ) {
            $el = $field_wrapper.find('select.post_object');
            if( $el.length != 1 )
                return;
        }

        // Make ACF focus on this field
        acf.fields.relationship.set({ $el : $el });

        // Prevent the user to create a new content
        // if the maximum number of items is already reached.
        if( typeof acf.fields.relationship.o.max != 'undefined' ) {
            if (acf.fields.relationship.$right.find('a').length >= acf.fields.relationship.o.max) {
                alert(acf.l10n.relationship.max.replace('{max}', acf.fields.relationship.o.max));
                return false;
            }
        }

        var $tooltip = $( $field_wrapper.find('.acf-rc-popup-wrapper').html() );

        // Position tooltip
        var $media_modal = $event_target.parents('.media-modal:first');
        var context_media_modal = $media_modal.length == 1;
        if( context_media_modal ) {
            $media_modal.find('div.media-frame-content div.settings').prepend($tooltip);
            $tooltip.css({
                'top': $event_target.position().top - $tooltip.height() - 6 + ( $event_target.outerHeight(true) - $event_target.innerHeight() ),
                'left': $event_target.position().left - ( $tooltip.width() / 2 ) + $event_target.outerWidth( true ) / 2
            });
        } else {
            $('body').prepend($tooltip);
            $tooltip.css({
                'top': $event_target.offset().top - $tooltip.height() - 6,
                'left': $event_target.offset().left - ( $tooltip.width() / 2 ) + ( $event_target.outerWidth( true ) /2 )
            });
        }

        // Take focus
        var $tooltip_focus = $tooltip.children('.focus');
        $tooltip_focus.trigger('focus');

        // Event to close tooltip
        $tooltip_focus.on('blur', function() {
            close_tooltip();
        });

        // Handle click on a tooltip link
        $tooltip.on('click', 'a', function(e) {
            e.preventDefault();

            // Store link data into ACF field
            $field_wrapper.data(
                'acfRcOpenUrl',
                {
                    'url':$(this).attr('data-create-url'),
                    'title':$(this).attr('title')
                }
            );

            // Trigger lightbox opening
            acf.fields.relationship.acf_rc_lightbox_open();
        });
    };

    // Custom event to open tooltip
    $(document).on('click', '.acf-relationship-create-link', function(e){
        acf.fields.relationship.acf_rc_tooltip_open(e);
    });



    /**
     * Open post creation in UI, in an iframe, in a lightbox
     *
     * @event click .acf-fc-popup a
     *
     * @param e Event
     */
    acf.fields.relationship.acf_rc_lightbox_open = function(e) {
        var $field_wrapper = acf.fields.relationship.acf_rc_get_field_wrapper( this.$el );
        if( $field_wrapper.length != 1 ) {
            console.warn('[acf-relationship-create | acf_rc_lightbox_open ] No wrapper found', $(e.target));
            return;
        }

        var url = $field_wrapper.data('acfRcOpenUrl').url.replace(
            '__acf_rc_original_field_uniqid__',
            $field_wrapper.attr('data-acf-rc-uniqid')
        );

        // Check whether we are in a media modal
        var $media_modal = $field_wrapper.parents('.media-modal:first');
        if( $media_modal.length == 1 ) { // Yes we are!
            url = url.replace(
                '__acf_rc_from_content_type__',
                'attachment'
            );
            url = url.replace(
                '__acf_rc_from_content_ID__',
                $media_modal.find('div.media-frame-content div.attachment-details').attr('data-id')
            );
        } else {
            url = url.replace(
                '__acf_rc_from_content_type__',
                $('form#post input[name="post_type"]').val()
            );
            url = url.replace(
                '__acf_rc_from_content_ID__',
                $('form#post input[name="post_ID"]').val()
            );
        }

        tb_show( $field_wrapper.data('acfRcOpenUrl').title, url );
    };


    /**
     * Fetch newly-created content
     *
     * @param e
     * @param field_uniq_id
     * @param post_data
     */
    acf.fields.relationship.acf_rc_on_content_created = function(e, field_uniq_id, post_data) {
        if( typeof field_uniq_id == 'undefined' ) return;

        var $field_wrapper = $('[data-acf-rc-uniqid="' + field_uniq_id + '"]');
        if( $field_wrapper.length != 1 ) {
            return;
        }

        if( typeof post_data.post_title == 'undefined' || post_data.post_title == '' ) {
            post_data.post_title = window.acf_relationship_create_field.i18n.no_title;
        }

        // Relationship field
        var field_type = 'relationship';
        var $el = $field_wrapper.find('.acf_relationship');

        // Post Object field
        if( $el.length != 1 ) {
            $el = $field_wrapper.find('select.post_object');
            if( $el.length != 1 )
                return;

            field_type = 'post_object';
        }

        switch( field_type ) {
            case 'relationship':
                // data-* attributes are automatically added to AJAX params
                $el.attr('data-acf_relationship_created_post_id', post_data.post_id + '-' + field_uniq_id);

                // fetch
                acf.fields.relationship.set({ $el : $el }).fetch();
                break;
            case 'post_object':
                // Create DOM <option> for newly created content
                var $new_option = $('<option></option>').val(post_data.post_id).text( post_data.post_title );
                $el.find('optgroup[label="' + acf_relationship_create.get_post_type_label( post_data.post_type ) + '"]')
                    .prepend( $new_option );

                // Select new option
                $new_option.prop('selected', 'selected');

                $el.trigger('change');

                // Increment user uses
                increment_user_uses();
                break;
        }
    };


    /**
     * Helper method to retrieve the field wrapper
     *
     * @param $elt
     * @returns {*}
     */
    acf.fields.relationship.acf_rc_get_field_wrapper = function( $elt ) {

        if( $elt.is('[data-acf-relationship-create-enabled="true"]') )
            return $elt;

        // Special treatment when working with relationship fields on attachments
        var $field_wrapper = $elt.parents('tr[class^="compat-field-fields"]:first');

        // Relationship field
        if( $field_wrapper.length != 1 ) {
            $field_wrapper = $elt.parents('.field_type-relationship:first');
        }

        // Post Object field
        if( $field_wrapper.length != 1 ) {
            $field_wrapper = $elt.parents('.field_type-post_object:first');
        }

        return $field_wrapper;
    };


    // Default AJAX callback for completed AJAX requests on Relationship fields
    $(document).ajaxComplete(function(event, xhr, ajaxOptions, data) {
        var ajax_request_data = acf_relationship_create.parse_query_string( 'foobar?' + decodeURIComponent(ajaxOptions.data) );
        if( typeof ajax_request_data.acf_relationship_created_post_id == 'undefined' ) {
            return;
        }
        var splitted_value = ajax_request_data.acf_relationship_created_post_id.split('-');
        if( typeof splitted_value[1] == 'undefined' ) return;

        var $field_wrapper = $('[data-acf-rc-uniqid="' + splitted_value[1] + '"]');
        if( $field_wrapper.length != 1 ) {
            console.warn('[acf-relationship-create | ajaxComplete ] No wrapper found', splitted_value[1]);
            return;
        }

        // Relationship fields only. We don't need to do anything here for Post Object fields
        var $el = $field_wrapper.find('.acf_relationship');
        if( $el.length != 1 ) return;

        // Auto-select newly created post
        var $choices = $el.find('.relationship_left .relationship_list li:not(.hide) a');
        if( $choices.length == 1 ) {
            $choices.trigger('click');
        }

        // Reset our custom filter
        $el.attr('data-acf_relationship_created_post_id', '');
        acf.fields.relationship.set({ $el : $el }).fetch();

        // Increment user uses
        increment_user_uses();
    });



    /**
     * Listen to the event triggered from the `create-on-the-fly` iframe
     */
    $(document).on('acf-relationship-create/created', function(e, field_uniq_id, post_data ) {
        // Dispatch the event
        acf.fields.relationship.acf_rc_on_content_created( e, field_uniq_id, post_data );

        // Close Thickbox
        tb_remove();
    });



    $(document).ready(function() {

        /**
         * Hide admin bar and admin menu if we're in an iframe
         */
        if( acf_relationship_create.get_parent_iframe() !== false ) {
            acf_relationship_create.hide_admin_bar();
            acf_relationship_create.hide_admin_menu();
        }

        /**
         * Perform some stuff on relationship field
         * as soon as they are created
         */
        function on_acf_relationship_field_ready($el) {
            var $link = $el.find('a.acf-relationship-create-link');
            if( $link.length != 1 ) return;

            // Add a unique ID for the field
            $el.attr('data-acf-rc-uniqid', acf_relationship_create.generate_random_id() );

            // Add a custom attr (mainly for CSS purpose)
            $el.attr('data-acf-relationship-create-enabled', true);

            // Move the `Create` link next to field label
            $link.detach().appendTo( $el.find('.label') );
        }

        $(document).on('acf/setup_fields', function(e, el){
            $(el).find('.acf_relationship, select.post_object').each(function(){
                acf.fields.relationship.set({ $el : $(this) }).init();

                var $field_wrapper = acf.fields.relationship.acf_rc_get_field_wrapper( $( this ) );
                if( $field_wrapper.length != 1 ) {
                    console.warn('[acf-relationship-create | acf/setup_fields ] No wrapper found for this relationship field', $(this));
                    return;
                }

                on_acf_relationship_field_ready( $field_wrapper );
            });
        });
    });
})(jQuery, window.acf_relationship_create || {});