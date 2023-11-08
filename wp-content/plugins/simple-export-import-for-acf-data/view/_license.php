<?php
if ( ! defined( 'ABSPATH' ) ) {exit;}
?>

<div class="seip_row">
	<div class="seip_col-md-6">
		<div class="card">
			<h4>License Key</h4>

			<div class="license_wrapper">
				<input type="password" class="form-control" value="<?php echo esc_attr(get_option('seip_license_key')) ?>" name="seip_license_key">
				<input type="submit" class="button button-primary save-license-key" value="Save">
				<div><small>After purchasing the pro plugin you will get a license key sent by email or you can see <a href="https://opcodespace.com/my-account/licenses/" target="_blank">HERE</a></small></div>
				<div class="alert"></div>
				<?php
				wp_nonce_field( 'seip_save_license_key');
				?>
			</div>

		</div>
	</div>
</div>


<script>

	jQuery(function($){
		$('.save-license-key').click(function(){
			$.ajax({
				method: 'POST',
				url: frontend_form_object.ajaxurl,
				data: { action: 'seip_save_license_key', _wpnonce: $('#_wpnonce').val(), seip_license_key: $('[name="seip_license_key"]').val()}
			})
			.done(function( response ) {
				if(response.success){
					$('.license_wrapper .alert').html(`<p style='color: green'>${response.data.message}</p>`);
				}
				else{
					$('.license_wrapper .alert').html(`<p style='color: red'>${response.data.message}</p>`);
				}
			});
		})
	})
</script>