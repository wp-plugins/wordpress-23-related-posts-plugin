<?php

define('WP_RP_STATIC_BASE_URL', 'http://dtmvdvtzf8rz0.cloudfront.net/static/');
define('WP_RP_STATIC_THEMES_PATH', 'css-text/');
define('WP_RP_STATIC_THEMES_THUMBS_PATH', 'css-img/');
define('WP_RP_STATIC_JSON_PATH', 'json/');

define("WP_RP_DEFAULT_CUSTOM_CSS",
".related_post_title {
}
ul.related_post {
}
ul.related_post li {
}
ul.related_post li a {
}
ul.related_post li img {
}");

define('WP_RP_THUMBNAILS_WIDTH', 150);
define('WP_RP_THUMBNAILS_HEIGHT', 150);
define('WP_RP_THUMBNAILS_DEFAULTS_COUNT', 15);

define("WP_RP_CTR_BASE_URL", "http://d.related-posts.com/");
define("WP_RP_CTR_REPORT_URL", "http://t.related-posts.com/pageview/?");
define("WP_RP_CTR_PAGEVIEW_FILE", "js/pageview.js");


global $wp_rp_options, $wp_rp_meta;
$wp_rp_options = false;
$wp_rp_meta = false;

function wp_rp_get_options()
{
	global $wp_rp_options, $wp_rp_meta;
	if($wp_rp_options) {
		return $wp_rp_options;
	}

	$wp_rp_meta = get_option('wp_rp_meta', false);
	if(!$wp_rp_meta || $wp_rp_meta['version'] !== WP_RP_VERSION) {
		wp_rp_upgrade();
		$wp_rp_meta = get_option('wp_rp_meta');
	}

	$wp_rp_options = get_option('wp_rp_options');

	if ($wp_rp_options['ctr_dashboard_enabled'] && !$wp_rp_meta['blog_id']) {
		wp_rp_fetch_blog_credentials();
	}

	return $wp_rp_options;
}

function wp_rp_get_meta() {
	global $wp_rp_meta;

	if (!$wp_rp_meta) {
		wp_rp_get_options();
	}

	return $wp_rp_meta;
}

function wp_rp_activate_hook() {
	wp_rp_get_options();
}

function wp_rp_fetch_blog_credentials() {
	global $wp_rp_meta;

	if (!wp_rp_statistics_supported()) {
		return;
	}

	$options = array(
		'timeout' => 2
	);
	$response = wp_remote_get(WP_RP_CTR_BASE_URL . 'register/?blog_url=' . get_bloginfo('wpurl'), $options);
	if (wp_remote_retrieve_response_code($response) == 200) {
		$body = wp_remote_retrieve_body($response);
		if ($body) {
			$doc = json_decode($body);
			if ($doc && $doc->status === 'ok') {
				$wp_rp_meta['blog_id'] = $doc->data->blog_id;
				$wp_rp_meta['auth_key'] = $doc->data->auth_key;
				update_option('wp_rp_meta', $wp_rp_meta);
			}
		}
	}
}
function wp_rp_statistics_supported() {
	return function_exists('json_decode');  // PHP < 5.2.0
}

function wp_rp_upgrade() {
	$wp_rp_meta = get_option('wp_rp_meta', false);
	$version = false;

	if($wp_rp_meta) {
		$version = $wp_rp_meta['version'];
	} else {
		$wp_rp_old_options = get_option('wp_rp', false);
		if($wp_rp_old_options) {
			$version = '1.4';
		}
	}

	if($version) {
		if(version_compare($version, WP_RP_VERSION, '<')) {
			call_user_func('wp_rp_migrate_' . str_replace('.', '_', $version));
			wp_rp_upgrade();
		}
	} else {
		wp_rp_install();
	}
}

function wp_rp_install() {
	$wp_rp_meta = array(
		'blog_id' => false,
		'auth_key' => false,
		'version' => WP_RP_VERSION,
		'first_version' => WP_RP_VERSION,
		'new_user' => true,
		'show_upgrade_tooltip' => false,
		'show_ctr_banner' => false
	);

	$wp_rp_options = array(
		'related_posts_title'			=> __('Related Posts', 'wp_related_posts'),
		'related_posts_title_tag'		=> 'h3',
		'missing_rp_algorithm'			=> 'random',
		'missing_rp_title'			=> __('Random Posts', 'wp_related_posts'),
		'display_excerpt'			=> false,
		'excerpt_max_length'			=> 200,
		'max_related_posts'			=> 5,
		'not_on_categories'			=> '',
		'on_single_post'			=> true,
		'on_rss'				=> false,
		'display_comment_count'			=> false,
		'display_publish_date'			=> false,
		'display_thumbnail'			=> true,
		'thumbnail_display_title'		=> true,
		'thumbnail_custom_field'		=> false,
		'thumbnail_use_attached'		=> true,
		'thumbnail_use_custom'			=> false,
		'default_thumbnail_path'		=> false,
		'theme_name' 				=> 'vertical-m.css',
		'theme_custom_css'			=> WP_RP_DEFAULT_CUSTOM_CSS,
		'ctr_dashboard_enabled' => wp_rp_statistics_supported()
	);

	update_option('wp_rp_meta', $wp_rp_meta);
	update_option('wp_rp_options', $wp_rp_options);
}

function wp_rp_migrate_1_4() {
	global $wpdb;

	$wp_rp = get_option('wp_rp');

	$wp_rp_options = array();

	////////////////////////////////

	$wp_rp_options['missing_rp_algorithm'] = (isset($wp_rp['wp_no_rp']) && in_array($wp_rp['wp_no_rp'], array('text', 'random', 'commented', 'popularity'))) ? $wp_rp['wp_no_rp'] : 'random';

	if(isset($wp_rp['wp_no_rp_text']) && $wp_rp['wp_no_rp_text']) {
		$wp_rp_options['missing_rp_title'] = $wp_rp['wp_no_rp_text'];
	} else {
		if($wp_rp_options['missing_rp_algorithm'] === 'text') {
			$wp_rp_options['missing_rp_title'] = __('No Related Posts', 'wp_related_posts');
		} else {
			$wp_rp_options['missing_rp_title'] = __('Random Posts', 'wp_related_posts');
		}
	}

	$wp_rp_options['on_single_post'] = isset($wp_rp['wp_rp_auto']) ? !!$wp_rp['wp_rp_auto'] : true;

	$wp_rp_options['display_comment_count'] = isset($wp_rp['wp_rp_comments']) ? !!$wp_rp['wp_rp_comments'] : false;

	$wp_rp_options['display_publish_date'] = isset($wp_rp['wp_rp_date']) ? !!$wp_rp['wp_rp_date'] : false;

	$wp_rp_options['display_excerpt'] = isset($wp_rp['wp_rp_except']) ? !!$wp_rp['wp_rp_except'] : false;

	if(isset($wp_rp['wp_rp_except_number']) && is_numeric(trim($wp_rp['wp_rp_except_number']))) {
		$wp_rp_options['excerpt_max_length'] = intval(trim($wp_rp['wp_rp_except_number']));
	} else {
		$wp_rp_options['excerpt_max_length'] = 200;
	}

	$wp_rp_options['not_on_categories'] = isset($wp_rp['wp_rp_exclude']) ? $wp_rp['wp_rp_exclude'] : '';

	if(isset($wp_rp['wp_rp_limit']) && is_numeric(trim($wp_rp['wp_rp_limit']))) {
		$wp_rp_options['max_related_posts'] = intval(trim($wp_rp['wp_rp_limit']));
	} else {
		$wp_rp_options['max_related_posts'] = 5;
	}

	$wp_rp_options['on_rss'] = isset($wp_rp['wp_rp_rss']) ? !!$wp_rp['wp_rp_rss'] : false;

	$wp_rp_options['theme_name'] = isset($wp_rp['wp_rp_theme']) ? $wp_rp['wp_rp_theme'] : 'plain.css';

	$wp_rp_options['display_thumbnail'] = isset($wp_rp['wp_rp_thumbnail']) ? !!$wp_rp['wp_rp_thumbnail'] : false;

	$custom_fields = $wpdb->get_col("SELECT meta_key FROM $wpdb->postmeta GROUP BY meta_key HAVING meta_key NOT LIKE '\_%' ORDER BY LOWER(meta_key)");
	if(isset($wp_rp['wp_rp_thumbnail_post_meta']) && in_array($wp_rp['wp_rp_thumbnail_post_meta'], $custom_fields)) {
		$wp_rp_options['thumbnail_custom_field'] = $wp_rp['wp_rp_thumbnail_post_meta'];
	} else {
		$wp_rp_options['thumbnail_custom_field'] = false;
	}

	$wp_rp_options['thumbnail_display_title'] = isset($wp_rp['wp_rp_thumbnail_text']) ? !!$wp_rp['wp_rp_thumbnail_text'] : false;

	$wp_rp_options['related_posts_title'] = isset($wp_rp['wp_rp_title']) ? $wp_rp['wp_rp_title'] : '';

	$wp_rp_options['related_posts_title_tag'] = isset($wp_rp['wp_rp_title_tag']) ? $wp_rp['wp_rp_title_tag'] : 'h3';

	$wp_rp_options['default_thumbnail_path'] = (isset($wp_rp['wp_rp_default_thumbnail_path']) && $wp_rp['wp_rp_default_thumbnail_path']) ? $wp_rp['wp_rp_default_thumbnail_path'] : false;

	$wp_rp_options['thumbnail_use_attached'] = isset($wp_rp["wp_rp_thumbnail_extract"]) && ($wp_rp["wp_rp_thumbnail_extract"] === 'yes');

	$wp_rp_options['thumbnail_use_custom'] = $wp_rp_options['thumbnail_custom_field'] && !(isset($wp_rp['wp_rp_thumbnail_featured']) && $wp_rp['wp_rp_thumbnail_featured'] === 'yes');

	$wp_rp_options['theme_custom_css'] = WP_RP_DEFAULT_CUSTOM_CSS;

	$wp_rp_options['ctr_dashboard_enabled'] = false;

	////////////////////////////////

	$wp_rp_meta = array(
		'blog_id' => false,
		'auth_key' => false,
		'version' => '1.5',
		'first_version' => '1.4',
		'new_user' => false,
		'show_upgrade_tooltip' => true,
		'show_ctr_banner' => true
	);

	update_option('wp_rp_meta', $wp_rp_meta);
	update_option('wp_rp_options', $wp_rp_options);
}
