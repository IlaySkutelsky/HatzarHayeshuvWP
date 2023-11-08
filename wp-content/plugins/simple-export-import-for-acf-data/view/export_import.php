<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<style>
    .chosen-container-single .chosen-single {
        height: 30px;
        border: 1px solid #c3c4c7;
        border-radius: 4px;
        background: transparent;
        box-shadow: none;
        line-height: 24px;
    }

    .submin_btn_wrapper {
        text-align: center;
        margin-top: 20px;
    }

    select {
        min-width: 190px;
    }

    td {
        min-width: 166px;
    }

    tr {
        margin-bottom: 10px;
        display: block;
    }

    .export_import_wrapper {
        margin-top: 30px;
    }

    .paid_text {
        color: red;
    }

    .formkit-powered-by-convertkit-container {
        display: none !important;
    }

    .formkit-guarantee {
        display: none !important;
    }

    .formkit-submit {
        background: #f0ad4e !important;
    }

    /* Tablet Layout: 768px. */
    @media only screen and (min-width: 768px) and (max-width: 991px) {
        select {
            min-width: 210px;
        }
    }

    /* Mobile Layout: 320px. */
    @media only screen and (max-width: 767px) {
        td {
            min-width: 100%;
            display: block;
        }

        select {
            min-width: 100%;
            display: block;
        }

    }
</style>
<?php wp_nonce_field('seip_export_import', 'seip_export_import_nonce') ?>
<div class="export_import_wrapper">
    <h5 class="main_title">Simple Export Import for ACF Data</h5>
    <?php $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'export' ?>
    <nav class="nav-tab-wrapper">
        <a class="nav-tab <?php echo $tab === 'export' || empty($tab) ? 'nav-tab-active' : '' ?>"
           href="<?php echo esc_url(admin_url('options-general.php?page=seip-simple-export-import&tab=export')) ?>">Export
            (JSON)</a>
        <a class="nav-tab <?php echo $tab === 'import' ? 'nav-tab-active' : '' ?>"
           href="<?php echo esc_url(admin_url('options-general.php?page=seip-simple-export-import&tab=import')) ?>">Import
            (JSON)</a>
        <a class="nav-tab <?php echo $tab === 'license' ? 'nav-tab-active' : '' ?>"
           href="<?php echo esc_url(admin_url('options-general.php?page=seip-simple-export-import&tab=license')) ?>">License</a>
    </nav>
    <div class="tap-contet-wrapper">
        <?php
        switch ($tab) {
            case 'import':
                $path = '_import.php';
                break;
            case 'license':
                $path = '_license.php';
                break;
            default:
                $path = '_export.php';
                break;
        }
        include $path;
        ?>
    </div>
    <?php
    $seip_settings = get_option('seip_settings');

    if (!SeipOpcodespace::isPaid() && (!isset($seip_settings['visible_subscription_date']) || $seip_settings['visible_subscription_date'] < time())): ?>
        <?php include_once '_modal.php' ?>
        <?php
        $seip_settings['visible_subscription_date'] = strtotime('+1 month');
        update_option('seip_settings', $seip_settings);
    endif; ?>

</div>


<script>
    jQuery(function ($) {

        $('.close_subscibe_modal').on('click', function () {
            $('.subscription_modal_wrapper').removeClass('active');
        });

        $("#export_option_data").click(function () {
            if ($('#export_option_data').is(':checked')) {
                $('.block_exports').slideUp();
            } else {
                $('.block_exports').slideDown();
            }
        });
        $("#import_option_data").click(function () {
            if ($('#import_option_data').is(':checked')) {
                $('.block_imports').slideUp();
            } else {
                $('.block_imports').slideDown();
            }
        });

        $('.bulk_export_visible').slideUp();
        $("#bulk_export").click(function () {
            if ($('#bulk_export').is(':checked')) {
                $('.bulk_export_block').slideUp();
                $('.bulk_export_visible').slideDown();
            } else {
                $('.bulk_export_block').slideDown();
                $('.bulk_export_visible').slideUp();
            }
        });

        $("#bulk_import").click(function () {
            if ($('#bulk_import').is(':checked')) {
                $('.bulk_import_block').slideUp();
            } else {
                $('.bulk_import_block').slideDown();
            }
        });
        VirtualSelect.init({
            ele: '#export_mulit_pages',
            multiple: true,
            optionHeight: 36,
            minWidth: 250
        });


        function seip_get_all_posts(_this) {
            let _this_parent = _this.parents('form');
            $.ajax({
                method: "POST",
                url: "<?php echo esc_url(admin_url('/admin-ajax.php')); ?>",
                data: {
                    action: "seip_get_all_posts",
                    post_type: $('.post_type').val(),
                    terms: $('#seip_terms').val() || '',
                    taxonomy: $('#seip_taxonomies').val() || '',
                    _wpnonce: $('#seip_export_import_nonce').val()
                }
            })
                .done(function (response) {
                    if (response.success) {
                        let options = '';
                        let options_arr = [];

                        response.data.posts.map(post => {
                            options += `<option value="${post.ID}">${post.post_name}</option>`;
                            options_arr.push({
                                label: post.post_name,
                                value: post.ID
                            });
                        })
                        $('[name="post_id"]').siblings('.chosen-container').find('.chosen-single').attr('style', '');
                        _this_parent.find('[name="post_id"]').html(options).trigger("chosen:updated");

                        if($('#export_mulit_pages').length > 0){
                            $('#export_mulit_pages').html(options);

                            document.querySelector('#export_mulit_pages').destroy();
                            VirtualSelect.init({
                                ele: '#export_mulit_pages',
                                multiple: true,
                                optionHeight: 36,
                                minWidth: 250,
                                options: options_arr

                            });
                        }


                    }
                }, _this_parent);
        }

        function get_all_taxonomies(post_type) {
            $.ajax({
                method: "POST",
                url: "<?php echo esc_url(admin_url('/admin-ajax.php')); ?>",
                data: {
                    action: "seip_get_all_taxonomies",
                    _wpnonce: $('#seip_export_import_nonce').val(),
                    post_type: post_type
                }
            })
                .done(function (response) {
                    if (response.success) {
                        let options = '<option></option>';
                        Object.values(response.data.taxonomies).forEach(taxonomy => {
                            options += `<option value="${taxonomy.name}">${taxonomy.labels.name}</option>`;
                        })

                        $('#seip_taxonomies').html(options).trigger("chosen:updated");
                    }

                });
        }

        function get_all_terms(taxonomy) {
            $.ajax({
                method: "POST",
                url: "<?php echo esc_url(admin_url('/admin-ajax.php')); ?>",
                data: {
                    action: "seip_get_all_terms",
                    _wpnonce: $('#seip_export_import_nonce').val(),
                    taxonomy: taxonomy
                }
            })
                .done(function (response) {
                    if (response.success) {
                        let options = '<option></option>';
                        Object.values(response.data.terms).forEach(term => {
                            options += `<option value="${term.slug}">${term.name}</option>`;
                        })

                        $('#seip_terms').html(options).trigger("chosen:updated");
                    }

                });
        }

        $('[name="terms[]"]').on('change',function () {
            seip_get_all_posts($(this));
        })

        $('[name="taxonomies"]').change(function () {
            get_all_terms($(this).val());
            seip_get_all_posts($(this));
        })

        $('[name="post_type"]').change(function () {
            get_all_taxonomies($(this).val())
            seip_get_all_posts($(this));
        })
    })
</script>