<?php

function wp_rp_upload_default_thumbnail_file() {
	if (!empty($_FILES['wp_rp_default_thumbnail'])) {
		$file   = $_FILES['wp_rp_default_thumbnail'];
		$upload = wp_handle_upload($file, array('test_form' => false));

		if(!isset($upload['error']) && isset($upload['file'])) {
			$path = image_resize($upload['file'], WP_RP_THUMBNAILS_WIDTH, WP_RP_THUMBNAILS_HEIGHT, true);
			if (!is_wp_error($path)) {
				$upload_dir = wp_upload_dir();
				return $upload_dir['subdir'] . '/' . wp_basename($path);
			}
		}
	}
	return false;
}

function wp_rp_get_default_thumbnail_url($seed=false) {
	$wp_rp = get_option("wp_rp");
	$upload_dir = wp_upload_dir();

	if (isset($wp_rp['wp_rp_default_thumbnail_path']) && $wp_rp['wp_rp_default_thumbnail_path']) {
		return $upload_dir['baseurl'] . $wp_rp['wp_rp_default_thumbnail_path'];
	} else {
		if ($seed) {
			$next_seed = rand();
			srand($seed);
		}
		$file = rand(0, WP_RP_THUMBNAILS_DEFAULTS_COUNT - 1) . '.jpg';
		if ($seed) {
			srand($next_seed);
		}
		return plugins_url('/static/thumbs/' . $file, __FILE__);
	}
}

function wp_rp_extract_post_image($post_id) {
	// We don't have an image stored for this post yet - find the first uploaded image and save it
	$args = array(
			'post_type' => 'attachment',
			'numberposts' => 1,
			'post_status' => null,
			'post_parent' => $post_id,
			'orderby' => 'id',
			'order' => 'ASC',
		);
	$attachments = get_posts($args);
	$image_id = '-1';
	if ( $attachments ) {
		foreach ( $attachments as $attachment ) {
			$img = wp_get_attachment_image($attachment->ID, 'thumbnail');
			if($img) {
				$image_id = $attachment->ID;
				break;
			}
		}
	}

	add_post_meta($post_id, '_wp_rp_image_id', $image_id);
	return $image_id;
}

function wp_rp_show_custom_thumbnails($wp_rp) {
	// If user has thumbnails on, ALWAYS start with featured image, UNLESS the following conditions hold:
	//  `wp_rp_thumbnail_post_meta` is set and is not "wprp_featured_image"
	// AND
	//  `wp_rp_thumbnail_featured` is not "yes"
	return isset($wp_rp["wp_rp_thumbnail_post_meta"]) && $wp_rp["wp_rp_thumbnail_post_meta"] &&
			$wp_rp["wp_rp_thumbnail_post_meta"] !== 'wprp_featured_image' &&
			$wp_rp["wp_rp_thumbnail_post_meta"] !== 'wp_rp_thumbnail_ignore_field'
		&&
		(!isset($wp_rp["wp_rp_thumbnail_featured"]) || $wp_rp["wp_rp_thumbnail_featured"] !== "yes");
}

function wp_get_post_thumbnail_img($related_post) {
	$wp_rp = get_option("wp_rp");

	if (!isset($wp_rp["wp_rp_thumbnail"]) || !$wp_rp["wp_rp_thumbnail"]) {
		return false;
	}

	if (wp_rp_show_custom_thumbnails($wp_rp)) {
		$thumbnail_src = get_post_meta($related_post->ID, $wp_rp["wp_rp_thumbnail_post_meta"], true);
		if ($thumbnail_src) {
			$img = '<img src="' . esc_attr($thumbnail_src) . '" alt="' . esc_attr(wptexturize($related_post->post_title)) . '" />';
			return $img;
		}
	} else if (function_exists('has_post_thumbnail') && has_post_thumbnail($related_post->ID)) {
		$img = get_the_post_thumbnail($related_post->ID, 'thumbnail');
		return $img;
	}

	if (isset($wp_rp["wp_rp_thumbnail_extract"]) && $wp_rp["wp_rp_thumbnail_extract"] === 'yes') {
		$image_id = get_post_meta($related_post->ID, '_wp_rp_image_id', true);
		if ($image_id === '') {
			$image_id = wp_rp_extract_post_image($related_post->ID);
		}
		if ($image_id !== '-1') {
			$img = wp_get_attachment_image($image_id, 'thumbnail');
			return $img;
		}
	}

	$img = '<img src="'. esc_attr(wp_rp_get_default_thumbnail_url($related_post->ID)) . '" alt="' . esc_attr(wptexturize($related_post->post_title)) . '" />';
	return $img;
}

