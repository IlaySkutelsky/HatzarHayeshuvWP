<?php
function add_custom_javascript() {
	?>
		<script src="<?php echo get_home_url()?>/wp-content/themes/HatzarHayeshuvMuseum/assets/js/index.js"></script>
		<link rel="stylesheet" href="<?php echo get_home_url()?>/wp-content/themes/HatzarHayeshuvMuseum/style.css" media="all">
	<?php
}
add_action('wp_head', 'add_custom_javascript');

function clear_styles_and_scripts() {
  global $wp_scripts;
  global $wp_styles;
  // $styles_to_keep = array("wp-admin", "admin-bar", "dashicons", "open-sans");
  $styles_to_keep = array("wp-admin", "admin-bar");

  foreach( $wp_styles ->queue as $handle ) :
		if ( in_array($handle, $styles_to_keep) ) continue;
    wp_dequeue_style( $handle );
    wp_deregister_style( $handle );

	endforeach;

}
add_action( 'wp_enqueue_scripts', 'clear_styles_and_scripts', 100 );


function create_ACF_meta_in_REST() {
	$postypes_to_exclude = ['acf-field-group','acf-field'];
	$extra_postypes_to_include = ["page"];
	$post_types = array_diff(get_post_types(["_builtin" => false], 'names'),$postypes_to_exclude);

	array_push($post_types, $extra_postypes_to_include);

	foreach ($post_types as $post_type) {
			register_rest_field( $post_type, 'ACF', [
					'get_callback'    => 'expose_ACF_fields',
					'schema'          => null,
		 ]
	 );
	}

}

function expose_ACF_fields( $object ) {
	$ID = $object['id'];
	return get_fields($ID);
}

add_action( 'rest_api_init', 'create_ACF_meta_in_REST' );

function register_rest_images() {
	$postypes_to_exclude = ['acf-field-group','acf-field'];
	$extra_postypes_to_include = ["page"];
	$post_types = array_diff(get_post_types(["_builtin" => false], 'names'),$postypes_to_exclude);

	array_push($post_types, $extra_postypes_to_include);

	foreach ($post_types as $post_type) {
		register_rest_field( $post_type,
			'fimg_url',
			array(
				'get_callback'    => 'get_rest_featured_image',
				'update_callback' => null,
				'schema'          => null,
			)
		);
	}
}

function get_rest_featured_image( $object, $field_name, $request ) {
    if ( $object['featured_media'] ) {
        $img = wp_get_attachment_image_src( $object['featured_media'] , 'full' );
        if ( empty( $img ) ) {
            return false;
        }
        return $img[0];
    }
    return false;
}

add_action( 'rest_api_init', 'register_rest_images' );