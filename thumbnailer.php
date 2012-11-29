<?php

function wp_rp_upload_default_thumbnail_file() {
	if (!empty($_FILES['wp_rp_default_thumbnail'])) {
		$file = $_FILES['wp_rp_default_thumbnail'];
		if(isset($file['error']) && $file['error'] === UPLOAD_ERR_NO_FILE) {
			return false;
		}

		$upload = wp_handle_upload($file, array('test_form' => false));
		if(isset($upload['error'])) {
			return new WP_Error('upload_error', $upload['error']);
		} else if(isset($upload['file'])) {
			$path = image_resize($upload['file'], WP_RP_THUMBNAILS_WIDTH, WP_RP_THUMBNAILS_HEIGHT, true);
			if (!is_wp_error($path)) {
				$upload_dir = wp_upload_dir();
				return $upload_dir['subdir'] . '/' . wp_basename($path);
			}
			return $path;
		}
	}
	return false;
}

function wp_rp_get_default_thumbnail_url($seed = false) {
	$options = wp_rp_get_options();
	$upload_dir = wp_upload_dir();

	if ($options['default_thumbnail_path']) {
		return $upload_dir['baseurl'] . $options['default_thumbnail_path'];
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
	return $image_id;
}

function wp_rp_get_post_thumbnail_img($related_post) {
    $options = wp_rp_get_options();
    if (!$options["display_thumbnail"]) {
		return false;
	}

    if ($options['thumbnail_use_custom']) {
        $thumbnail_src = get_post_meta($related_post->ID, $options["thumbnail_custom_field"], true);

        if ($thumbnail_src) {
			$img = '<img src="' . esc_attr($thumbnail_src) . '" alt="' . esc_attr(wptexturize($related_post->post_title)) . '" />';
			return $img;
		}
	} else if (has_post_thumbnail($related_post->ID)) {
        $attr = array(
			'alt' => esc_attr(wptexturize($related_post->post_title)),
			'title' => false
		);
		$img = get_the_post_thumbnail($related_post->ID, 'thumbnail', $attr);
        return $img;
	}

    if($options["thumbnail_use_attached"]) {
        $image_id = wp_rp_extract_post_image($related_post->ID);
        if ($image_id !== '-1') {
			$img = wp_get_attachment_image($image_id, 'thumbnail');
			return $img;
		}
	}

	$img = '<img src="'. esc_attr(wp_rp_get_default_thumbnail_url($related_post->ID)) . '" alt="' . esc_attr(wptexturize($related_post->post_title)) . '" />';
	return $img;
}

