<?php
function add_home_javascript() {
	?>
		<script src="<?php echo get_home_url()?>/wp-content/themes/HatzarHayeshuvMuseum/assets/js/home.js"></script>
	<?php
}
function add_exhibitions_javascript() {
	?>
		<script src="<?php echo get_home_url()?>/wp-content/themes/HatzarHayeshuvMuseum/assets/js/exhibitions.js"></script>
	<?php
}
function add_javascript_post_data() {
	?>
		<script>
			window.WP_POST_TYPE = `<?php echo get_post_type() ?>`;
			window.WP_POST = JSON.parse(atob(`<?php echo base64_encode(json_encode(get_post()));?>`));
			window.WP_POST_ACF = JSON.parse(atob(`<?php 
				$acf_obj = get_fields(get_the_ID());
				echo base64_encode(json_encode($acf_obj));
			?>`));
			window.WP_POST_IMAGE = `<?php 
				$images = wp_get_attachment_image_src( get_post_thumbnail_id(get_the_ID()), 'full' );
				echo $images? $images[0] : null;
			?>`;
			window.WP_MOVEMENTS = JSON.parse(atob(`
				<?php 
					echo base64_encode(json_encode(get_posts(array(
						'posts_per_page'    => -1,
						'post_type'     => 'movement',
					))));
				?>`));
			
		</script>
		<script src="<?php echo get_home_url()?>/wp-content/themes/HatzarHayeshuvMuseum/assets/js/single.js"></script>
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

global $wp;
$home_url = home_url( $wp->request );
$current_url = home_url($_SERVER['REQUEST_URI']);
add_action('wp_head', 'add_custom_metatags');
if ($current_url == $home_url || $current_url == ($home_url . '/') ) {
	add_action('wp_head', 'add_home_javascript');
}
else if ($current_url == ($home_url . '/exhibitions/') ) {
	add_action('wp_head', 'add_exhibitions_javascript');
}
add_action('wp_head', 'add_custom_css');
add_action('wp_head', 'add_javascript_post_data');

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
    if ( isset($object['featured_media']) && $object['featured_media'] ) {
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

	if (!$values) return;

	if ($values['characteristics'] && $values['characteristics']['colors']) {
		$new_color = in_array("אחר", $values['characteristics']['colors'])? $values['characteristics']['צבע_אחר'] : false;
		if ($new_color) add_new_option_to_field_choices('colors', $new_color);
	}

	if ($values['characteristics'] && $values['characteristics']['field']) {
		$new_field = $values['characteristics']['field'] == "אחר"? $values['characteristics']['תחום_אחר'] : false;
		if ($new_field) add_new_option_to_field_choices('field', $new_field);
	}

	if ($values['characteristics'] && $values['characteristics']['ethnicity']) {
		$new_ethnicity = $values['characteristics']['ethnicity'] == "אחר"? $values['characteristics']['עדה_אחר'] : false;
		if ($new_ethnicity) add_new_option_to_field_choices('ethnicity', $new_ethnicity);
	}

	if ($values['characteristics'] && $values['characteristics']['materials']) {
		$new_material = in_array("אחר", $values['characteristics']['materials'])? $values['characteristics']['חומרים_אחר'] : false;
		if ($new_material) add_new_option_to_field_choices('materials', $new_material);
	}

	if ($values['characteristics'] && $values['characteristics']['techniques']) {
		$new_technique = in_array("אחר", $values['characteristics']['techniques'])? $values['characteristics']['טכניקות_אחר'] : false;
		if ($new_technique) add_new_option_to_field_choices('techniques', $new_technique);
	}

	if ($values['status']) {
		$new_status = $values['status'] == "אחר"? $values['סטטוס_אחר'] : false;
		if ($new_status) add_new_option_to_field_choices('status', $new_status);
	}

	if ($values['תקופה']) {
		$new_period = $values['תקופה'] == "אחר"? $values['תקופה_אחר'] : false;
		if ($new_period) add_new_option_to_field_choices('תקופה', $new_period);
	}

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

function create_my_post( $value ) {
	
	$post_id = get_the_ID();
	$title = get_the_title($post_id);
	$new_title = trim(preg_replace('/\s+/',' ', $title));
	$new_slug = sanitize_title( $new_title ) . "-" . $post_id;

	$postdata = array(
		'ID'          => $post_id,
		'post_title'  => $new_title,
		'post_name'   => $new_slug,
	);	

	if ( get_post_type() === 'item' ) {
		remove_action('save_post', 'create_my_post');
		wp_update_post( $postdata );
		add_action('save_post', 'create_my_post');
	}	

	return $value;
}

add_filter( 'acf/update_value', 'create_my_post', 10, 3);

add_action('admin_head', 'add_id_to_acf_relation');
function add_id_to_acf_relation() {
	echo '<style>
		.acf-relationship .list .acf-rel-item::after {
			content: " - " attr(data-id);
		}
	</style>';

	?>
		<script>
			window.NEXT_CATALOG_NUMBER = <?php echo getNextItemCatalogNumber(); ?>;
			window.itemIDsToCatalogNumbersMap = JSON.parse(`<?php echo json_encode(getItemIDsToCatalogNumbersMap()); ?>`);
		</script>
	<?php
}

function getItemIDsToCatalogNumbersMap() {
	$all_items = get_posts(array(
			'posts_per_page'    => -1,
			'post_type'     => 'item',
		));
	// Exclude current post
	$all_items = array_filter($all_items, function($item) {
		return $item->ID !== get_the_ID();
	}, ARRAY_FILTER_USE_BOTH);

	$map = array();
	foreach ($all_items as &$item) {
		$map[$item->ID] = itemToCatalogNumber($item);
	}
	return $map;
}

function my_admin_enqueue_scripts() {
    wp_enqueue_script( 'my-admin-js', get_template_directory_uri() . '/assets/js/admin.js', array(), '1.0.0', true );
}

add_action('acf/input/admin_enqueue_scripts', 'my_admin_enqueue_scripts');

function consolelog($data) {
    $output = json_encode($data);

    echo "<script>console.log(JSON.parse(`" . $output . "`));</script>";
}

define( 'DISALLOW_FILE_EDIT', true );
// add validate value filter for all acf fields with the field name `registration_pin`
// you can use `acf/validate_value/key=` to target a specific acf field by key if you have multiple fields with the same name `registration_pin` in different field groups
add_filter('acf/validate_value/name=current_catalog_number', 'acf_validate_current_catalog_number', 10, 4);

// function used by the above `add_filter`
function acf_validate_current_catalog_number($valid, $value, $field, $input_name) {

	$value = strtoupper($value);
	$has_matches = preg_match("/^C\d*$/", $value);
    if(!$has_matches) return "ערך לא מתאים";

	if (isCatalogNumberExistsAlready($value)) return "ערך כבר קיים במערכת";

    // return field as valid, if none of the above conditions are true
    return $valid;
}

function catalogNumberToNumber($catalogNumber) {
	return (int)end(explode("C", $catalogNumber));
}

function itemToCatalogNumber($item) {
	return get_field('current_catalog_number', $item->ID);
}

function isCatalogNumberExistsAlready($value) {
	$all_items = get_posts(array(
			'posts_per_page'    => -1,
			'post_type'     => 'item',
		));
	// Exclude current post
	$all_items = array_filter($all_items, function($item) {
		return $item->ID !== get_the_ID();
	}, ARRAY_FILTER_USE_BOTH);

	foreach ($all_items as &$item) {
		if ($value == itemToCatalogNumber($item)) return true;
	}
	return false;
}

function getNextItemCatalogNumber() {
	$all_items = get_posts(array(
			'posts_per_page'    => -1,
			'post_type'     => 'item',
		));
	// Exclude current post
	$all_items = array_filter($all_items, function($item) {
		return $item->ID !== get_the_ID();
	}, ARRAY_FILTER_USE_BOTH);
	
	$all_catalog_numbers = array_map('itemToCatalogNumber', $all_items);

	$all_numbers = array_map('catalogNumberToNumber', $all_catalog_numbers);

	$next_number = 10000;
	while (in_array($next_number, $all_numbers)) {
		$next_number++;
	}

	return $next_number;
}