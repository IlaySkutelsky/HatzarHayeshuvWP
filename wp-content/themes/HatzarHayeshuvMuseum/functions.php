<?php
function add_custom_javascript() {
	?>
		<script src="<?php echo get_home_url()?>/wp-content/themes/HatzarHayeshuvMuseum/assets/js/index.js"></script>
	<?php
}
function add_custom_css() {
	?>
		<link rel="stylesheet" href="<?php echo get_home_url()?>/wp-content/themes/HatzarHayeshuvMuseum/style.css" media="all">
	<?php
}
function add_custom_metatags() {
	?>
		<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
		<meta http-equiv="Pragma" content="no-cache" />
		<meta http-equiv="Expires" content="0" />
	<?php
}
function redirect_to_home() {
	?>
		<script>
			window.location.replace("<?php echo get_home_url()?>");
		</script>
	<?php
}

global $wp;
$home_url = home_url( $wp->request );
$current_url = home_url($_SERVER['REQUEST_URI']);
if ($current_url == $home_url || $current_url == ($home_url . '/') ) {
	add_action('wp_head', 'add_custom_metatags');
	add_action('wp_head', 'add_custom_javascript');
	add_action('wp_head', 'add_custom_css');
} else {
	add_action('wp_head', 'add_custom_css');
}

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

wp_using_ext_object_cache(false);
wp_cache_flush();
wp_cache_init();



add_action('acf/save_post', 'add_other_selected_options_to_fields');
function add_other_selected_options_to_fields( $post_id ) {

    // Get newly saved values.
    $values = get_fields( $post_id );
// 	consolelog($values);
	
	// https://support.advancedcustomfields.com/forums/topic/updating-field-settings-in-php/

	$new_color = in_array("אחר", $values['characteristics']['colors'])? $values['characteristics']['צבע_אחר'] : false;
	if ($new_color) add_new_option_to_field_choices('colors', $new_color);

	$new_field = $values['characteristics']['field'] == "אחר"? $values['characteristics']['תחום_אחר'] : false;
	if ($new_field) add_new_option_to_field_choices('field', $new_field);

	$new_ethnicity = $values['characteristics']['ethnicity'] == "אחר"? $values['characteristics']['עדה_אחר'] : false;
	if ($new_ethnicity) add_new_option_to_field_choices('ethnicity', $new_ethnicity);

	$new_material = in_array("אחר", $values['characteristics']['materials'])? $values['characteristics']['חומרים_אחר'] : false;
	if ($new_material) add_new_option_to_field_choices('materials', $new_material);

	$new_technique = in_array("אחר", $values['characteristics']['techniques'])? $values['characteristics']['טכניקות_אחר'] : false;
	if ($new_technique) add_new_option_to_field_choices('techniques', $new_technique);

	$new_status = $values['status'] == "אחר"? $values['סטטוס_אחר'] : false;
	if ($new_status) add_new_option_to_field_choices('status', $new_status);

	$new_period = $values['תקופה'] == "אחר"? $values['תקופה_אחר'] : false;
	if ($new_period) add_new_option_to_field_choices('תקופה', $new_period);

	if ($new_color || $new_field || $new_ethnicity || $new_material || $new_technique) {

		if ($new_color) {
			$new_color_array = $values['characteristics']['colors'];
			array_push($new_color_array, $new_color);
			// $new_color = [...$values['characteristics']['colors'], $new_color];
			$new_color_array = array_filter($new_color_array, fn($n)=> $n !== "אחר");
			$new_color = $new_color_array;
		}
		if ($new_material) {
			$new_material_array = $values['characteristics']['materials'];
			array_push($new_material_array, $new_material);
			// $new_material = [...$values['characteristics']['materials'], $new_materials];
			$new_material_array = array_filter($new_material_array, fn($n) => $n !== "אחר");
			$new_material = $new_material_array;
		}
		if ($new_technique) {
			$new_technique_array = $values['characteristics']['techniques'];
			array_push($new_technique_array, $new_technique);
			// $new_ethnicity = [...$values['characteristics']['ethnicity'], $new_ethnicity];
			$new_technique_array = array_filter($new_technique_array, fn($n) => $n !== "אחר");
			$new_technique = $new_technique_array;
		}

		$charects = Array(
	       'field_6533cbd4c6d1c' => $new_color? $new_color : $values['characteristics']['colors'],
	       'field_6533cc47c6d1d' => $new_field? $new_field : $values['characteristics']['field'],
	       'field_6533cc77c6d1e' => $new_ethnicity? $new_ethnicity : $values['characteristics']['ethnicity'],
	       'field_6533ccadc6d20' => $new_material? $new_material : $values['characteristics']['materials'],
	       'field_6533ccccc6d21' => $new_technique? $new_technique : $values['characteristics']['techniques'],

	       'field_6533cd22c6d22' => $values['characteristics']['size'],

		   'field_6554ea526be07' => '',
		   'field_65524e61c9fa8' => '',
		   'field_65524e85c9fa9' => '',
		   'field_6554e9efd57d9' => '',
		   'field_65524e99c9faa' => ''
		);
	    update_field('field_6533cb66c6d18', $charects, $post_id);
	}

	if ($new_status) {
	    update_field('field_653406eef9701', $new_status, $post_id);
	    update_field('field_65524e25c9fa7', '', $post_id);
	}
	
	if ($new_period) {
	    update_field('field_6533ac6e66ff3', $new_period, $post_id);
	    update_field('field_65524df8c9fa6', '', $post_id);
	}
	
 	echo "<script>history.go(-2);</script>";
}

function add_new_option_to_field_choices($field_name, $new_value) {
	$field = acf_get_field($field_name, true);
	if( !isset($field['choices'][$new_value]) ) {
		$field['choices'][$new_value] = $new_value;
		acf_update_field($field);
	}

}

add_action('acf/save_post', 'insert_new_movement');
function insert_new_movement( $post_id ) {
    // Get newly saved values.
    $values = get_fields( $post_id );
    
	if (!$values['new_movement'] || !$values['new_movement']['has_new_movement']) return;
    consolelog('got here');

	$createPage = array(
		"post_title" => $values['new_movement']['new_movement_name'],
		"post_content" => '',
		"post_status" => "publish",
		"post_type" => 'movement',
		"post_name" => $values['new_movement']['new_movement_name'],
	);

	// Insert the post into the database
	$new_movement_id = wp_insert_post( $createPage );
	if (!$new_movement_id) return;
    consolelog('saved new movement');

    update_field('field_6533c9b9ea456', $values['new_movement']['new_movement_type'], $new_movement_id);
    update_field('field_6533caf5ea457', $values['new_movement']['new_movement_date'], $new_movement_id);
    update_field('field_6533cb1aea458', $values['new_movement']['new_movement_to'], $new_movement_id);
    update_field('field_6533cb21ea459', [ $post_id ], $new_movement_id);
    consolelog('updated new movement fields');
    
	// Add new movement to item movements
	$current_item_movements = $values['movements'];
	if ($current_item_movements === '') $current_item_movements = [];
 	$current_item_movements[] = $new_movement_id;
 	update_field('field_6533d051394c8', $current_item_movements, $post_id);
    consolelog('Added new movement to items movements');
 	
 	// Empty new movement fields on post
	$charects = Array(
		'field_65578bfbba16c' => false,
		'field_65578c6307e8f' => '',
		'field_65578c8407e90' => 'השאלה',
		'field_65578ca807e91' => '',
		'field_65578cc007e92' => ''
	);
	update_field('field_65578c1fba16d', $charects, $post_id);
    consolelog('emptied item new movement fields');
}

add_filter( 'rest_post_collection_params', function ( $params, WP_Post_Type 
$post_type ) {
    if ( 'item' === $post_type->name && isset( $params['per_page'] ) ) {
        $params['per_page']['maximum'] = PHP_INT_MAX;
    }
    return $params;
}, 10, 2 );


function consolelog($data) {
    $output = json_encode($data);

    echo "<script>console.log('Debug Objects:');console.log(JSON.parse(`" . $output . "`.replace(/[\u0000-\u001F\u007F-\u009F]/g, '')));</script>";
}

define( 'DISALLOW_FILE_EDIT', true );