<?php

/**
 * Promotional Campaign Management Plugin for WooCommerce
 *
 * @package   L2_AiMaBiet_Promotion
 * @author    L2_AiMaBiet Group in HCMUS
 * @license   MIT
 *
 * @wordpress-plugin
 * Plugin Name:       L2-AiMaBiet-Promotion
 * Plugin URI:        
 * Description:       Promotional Campaign Management Plugin for WooCommerce.
 * Version:           1.0.0
 * Author:            L2_AiMaBiet Group in HCMUS
 * Author URI:        https://github.com/HCMUS-OSSD/L2_AIMABIET
 * Text Domain:       l2-aimabiet-promotion
 * License:           MIT
 * License URI:       http://opensource.org/licenses/MIT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function l2_aimabiet_promotion_init() {
	function l2_aimabiet_promotion_meta_box_callback($post) {
		wp_nonce_field( 'l2_aimabiet_promotion_meta_box', 'l2_aimabiet_promotion_meta_box_nonce' );

		$value = get_post_meta( $post->ID, '_coupon_code', true );

		$args = array(
		    'posts_per_page'   => -1,
		    'orderby'          => 'title',
		    'order'            => 'asc',
		    'post_type'        => 'shop_coupon',
		    'post_status'      => 'publish',
		);

		$coupons = get_posts( $args );

		echo '<select name="_meta_box_coupon_code" id="l2_aimabiet_promotion_coupon_code">';
		foreach ($coupons as $coupon) {
			$c = new WC_Coupon($coupon->post_title);
			var_dump($c);
			echo '<option value="' . $coupon->post_title . '" ' . ($coupon->post_title == $value ? 'selected' : '') . '>' . $coupon->post_title . '</option>';
		}
		echo '</select>';
	}

	function l2_aimabiet_promotion_meta_box_save_callback($post_id) {
		// Check if our nonce is set.
		if ( ! isset( $_POST['l2_aimabiet_promotion_meta_box_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['l2_aimabiet_promotion_meta_box_nonce'], 'l2_aimabiet_promotion_meta_box' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}

		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		/* OK, it's safe for us to save the data now. */
	
		// Make sure that it is set.
		if ( ! isset( $_POST['_meta_box_coupon_code'] ) ) {
			return;
		}

		$coupon_code = sanitize_text_field( $_POST['_meta_box_coupon_code'] );

		update_post_meta( $post_id, '_coupon_code', $coupon_code );
	}

	function add_promotion_metaboxes() {
		add_meta_box('promotion_coupon_code', 'Coupon Code', 'l2_aimabiet_promotion_meta_box_callback', 'promotion', 'side', 'default');
	}

	// Register Custom Post Type
	function promotion_post_type() {

		$labels = array(
			'name'                => _x( 'Promotions', 'Post Type General Name', 'l2_aimabiet_promotion' ),
			'singular_name'       => _x( 'Promotion', 'Post Type Singular Name', 'l2_aimabiet_promotion' ),
			'menu_name'           => __( 'Promotions', 'l2_aimabiet_promotion' ),
			'name_admin_bar'      => __( 'Promotion', 'l2_aimabiet_promotion' ),
			'parent_item_colon'   => __( 'Parent Promotion:', 'l2_aimabiet_promotion' ),
			'all_items'           => __( 'All Promotions', 'l2_aimabiet_promotion' ),
			'add_new_item'        => __( 'Add New Promotion', 'l2_aimabiet_promotion' ),
			'add_new'             => __( 'Add New', 'l2_aimabiet_promotion' ),
			'new_item'            => __( 'New Promotion', 'l2_aimabiet_promotion' ),
			'edit_item'           => __( 'Edit Promotion', 'l2_aimabiet_promotion' ),
			'update_item'         => __( 'Update Promotion', 'l2_aimabiet_promotion' ),
			'view_item'           => __( 'View Promotion', 'l2_aimabiet_promotion' ),
			'search_items'        => __( 'Search Promotion', 'l2_aimabiet_promotion' ),
			'not_found'           => __( 'Not found', 'l2_aimabiet_promotion' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'l2_aimabiet_promotion' ),
		);
		$args = array(
			'label'               => __( 'promotion', 'l2_aimabiet_promotion' ),
			'description'         => __( 'Promotional Campaign Management', 'l2_aimabiet_promotion' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail'),
			'hierarchical'        => false,
			'public'              => true,
			'rewrite'			=> array('slug' => 'promotion'),
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-megaphone',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'register_meta_box_cb' => 'add_promotion_metaboxes'
		);
		register_post_type( 'promotion', $args );
	}

	function l2_aimabiet_products_shortcode( $atts ) {
		global $woocommerce_loop;

		if ( empty( $atts ) ) {
			return '';
		}

		$atts = shortcode_atts( array(
			'columns' => '4',
			'orderby' => 'title',
			'order'   => 'asc',
			'code'		=> ''
		), $atts );

		$meta_query = WC()->query->get_meta_query();

		$args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'orderby'             => $atts['orderby'],
			'order'               => $atts['order'],
			'posts_per_page'      => -1,
			'meta_query'          => $meta_query
		);

		$coupon = null;
		$expired = false;

		if ( ! empty( $atts['code'] ) ) {
			if ($coupon = new WC_Coupon($atts['code'])) {
				if ($coupon->expiry_date && current_time( 'timestamp' ) > $coupon->expiry_date) {
					$expired = true;
				} elseif ( !empty($coupon->product_ids) ) {
					$product_ids = array_map( 'trim', $coupon->product_ids );
					$args['post__in'] = $product_ids;
				} elseif ( !empty($coupon->exclude_product_ids) ) {
					$exclude_product_ids = $coupon->exclude_product_ids;
					$args['post__not_in'] = $exclude_product_ids;
				} elseif ( !empty($coupon->product_categories) ) {
					$product_categories = array_map( 'trim', $coupon->product_categories );
					$args['tax_query'] = array(
						array(
							'taxonomy' 		=> 'product_cat',
							'terms' 		=> $product_categories,
							'field' 		=> 'id',
							'operator' 		=> 'IN'
						)
					);
				} elseif ( !empty($coupon->exclude_product_categories) ) {
					$exclude_product_categories = $coupon->exclude_product_categories;
					$args['tax_query'] = array(
						array(
							'taxonomy' 		=> 'product_cat',
							'terms' 		=> $exclude_product_categories,
							'field' 		=> 'id',
							'operator' 		=> 'NOT IN'
						)
					);
				}
			}
		}

		if (!$coupon) {
			return;
		}

		ob_start();

		echo '<p style="text-align: center; border: 1px dashed black; margin: 10px 0 20px 0; padding: 10px 30px; color : red; ' . ($expired ? 'text-decoration:line-through;' : '') . '">Mã Khuyến Mãi <strong>' . $coupon->code . '</strong></p>';

		if (!$expired) {
			$products = new WP_Query( apply_filters( 'woocommerce_shortcode_products_query', $args, $atts ) );

			$woocommerce_loop['columns'] = $atts['columns'];

			if ( $products->have_posts() ) : ?>

				<?php woocommerce_product_loop_start(); ?>

					<?php while ( $products->have_posts() ) : $products->the_post(); ?>

						<?php wc_get_template_part( 'content', 'product' ); ?>

					<?php endwhile; // end of the loop. ?>

				<?php woocommerce_product_loop_end(); ?>

			<?php endif;

			wp_reset_postdata();

			
		}
		return '<div class="woocommerce columns-' . $atts['columns'] . '">' . ob_get_clean() . '</div>';
	}

	promotion_post_type();
	add_action( 'save_post', 'l2_aimabiet_promotion_meta_box_save_callback' );
	add_shortcode( apply_filters( "coupon_products_shortcode_tag", 'coupon_products' ), 'l2_aimabiet_products_shortcode' );
	add_image_size('promotions', 1150, 500, true);
}

// Check if woocommerce is installed
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	add_action('init', 'l2_aimabiet_promotion_init', 0);
}