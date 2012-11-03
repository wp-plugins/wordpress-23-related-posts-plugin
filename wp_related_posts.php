<?php
/*
Plugin Name: WordPress Related Posts
Version: 1.6
Plugin URI: http://wordpress.org/extend/plugins/wordpress-23-related-posts-plugin/
Description: Generate a related posts list via tags of WordPress
Author: Jure Ham
Author URI: http://wordpress.org/extend/plugins/wordpress-23-related-posts-plugin/
*/

define('WP_RP_VERSION', '1.6');

include_once(dirname(__FILE__) . '/config.php');

register_activation_hook(__FILE__, 'wp_rp_activate_hook');

include_once(dirname(__FILE__) . '/widget.php');
include_once(dirname(__FILE__) . '/thumbnailer.php');
include_once(dirname(__FILE__) . '/settings.php');
include_once(dirname(__FILE__) . '/compatibility.php');

add_action('init', 'wp_rp_init_hook');
add_filter('the_content', 'wp_rp_add_related_posts_hook', 99);

function wp_rp_init_hook() {
	load_plugin_textdomain('wp_related_posts', false, dirname(plugin_basename (__FILE__)) . '/lang');
}

function wp_rp_add_related_posts_hook($content) {
	$options = wp_rp_get_options();

	if ((is_single() && $options["on_single_post"]) || (is_feed() && $options["on_rss"])) {
		$output = wp_rp_get_related_posts();
		$content = $content . $output;
	}

	return $content;
}

function wp_rp_fetch_related_posts($limit = 10, $exclude_ids = array()) {
	global $wpdb, $post;
	$options = wp_rp_get_options();

	$exclude_ids_str = wp_rp_get_exclude_ids_list_string($exclude_ids);

	if(!$post->ID){return;}
	$now = current_time('mysql', 1);
	$tags = wp_get_post_tags($post->ID);

	$tagcount = count($tags);
	$taglist = false;
	if ($tagcount > 0) {
		$taglist = "'" . $tags[0]->term_id. "'";
		for ($i = 1; $i < $tagcount; $i++) {
			$taglist = $taglist . ", '" . $tags[$i]->term_id . "'";
		}
	}

	$related_posts = false;
	if ($taglist) {
		$q = "SELECT p.ID, p.post_title, p.post_content,p.post_excerpt, p.post_date,  p.comment_count, count(t_r.object_id) as cnt FROM $wpdb->term_taxonomy t_t, $wpdb->term_relationships t_r, $wpdb->posts p WHERE t_t.taxonomy ='post_tag' AND t_t.term_taxonomy_id = t_r.term_taxonomy_id AND t_r.object_id  = p.ID AND (t_t.term_id IN ($taglist)) AND p.ID NOT IN ($exclude_ids_str) AND p.post_status = 'publish' AND p.post_date_gmt < '$now' GROUP BY t_r.object_id ORDER BY cnt DESC, p.post_date_gmt DESC LIMIT $limit;";

		$related_posts = $wpdb->get_results($q);
	}

	return $related_posts;
}

function wp_rp_get_exclude_ids_list_string($exclude_ids = array()) {
	global $post;

	array_push($exclude_ids, $post->ID);
	$exclude_ids = array_map('intval', $exclude_ids);
	$exclude_ids_str = implode(', ', $exclude_ids);

	return $exclude_ids_str;
}

function wp_rp_fetch_random_posts($limit = 10, $exclude_ids = array()) {
	global $wpdb, $post;

	$exclude_ids_str = wp_rp_get_exclude_ids_list_string($exclude_ids);

	$q1 = "SELECT ID FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post' AND ID NOT IN ($exclude_ids_str)";
	$ids = $wpdb->get_col($q1, 0);
	$count = count($ids);
	if($count <= 1) {
		if($count === 0) return false;
		if($count === 1) $rnd = $ids;
	} else {
		$display_number = min($limit, $count);

		$next_seed = rand();
		$t = time();
		$seed = $t - $t % 300 + $post->ID << 4;		// We keep the same seed for 5 minutes, so MySQL can cache the `q2` query.
		srand($seed);

		$rnd = array_rand($ids, $display_number);	// This is an array of random indexes, sorted
		if ($display_number == 1) {
			$ids = array($ids[$rnd]);
		} else {
			shuffle($rnd);
			foreach ($rnd as &$i) {		// Here, index is passed by reference, so we can modify it
				$i = $ids[$i];		// Replace indexes with corresponding IDs
			}
			$ids = $rnd;
		}
		srand($next_seed);
	}
	$q2 = "SELECT ID, post_title, post_content, post_excerpt, post_date, comment_count FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post' AND ID IN (" . implode(',', $ids) . ")";
	return $wpdb->get_results($q2);
}

function wp_rp_fetch_most_commented_posts($limit = 10, $exclude_ids = array()) {
	global $wpdb;

	$exclude_ids_str = wp_rp_get_exclude_ids_list_string($exclude_ids);

	$q = "SELECT ID, post_title, post_content, post_excerpt, post_date, COUNT($wpdb->comments.comment_post_ID) AS 'comment_count' FROM $wpdb->posts, $wpdb->comments WHERE comment_approved = '1' AND $wpdb->posts.ID=$wpdb->comments.comment_post_ID AND post_status = 'publish' AND ID NOT IN ($exclude_ids_str) GROUP BY $wpdb->comments.comment_post_ID ORDER BY comment_count DESC LIMIT $limit";
	return $wpdb->get_results($q);
}

function wp_rp_fetch_most_popular_posts ($limit = 10, $exclude_ids = array()) {
	global $wpdb, $table_prefix;

	$exclude_ids_str = wp_rp_get_exclude_ids_list_string($exclude_ids);

	$q = $sql = "SELECT p.ID, p.post_title, p.post_content,p.post_excerpt, p.post_date, p.comment_count FROM ". $table_prefix ."ak_popularity as akpc,".$table_prefix ."posts as p WHERE p.ID = akpc.post_id AND p.ID NOT IN ($exclude_ids_str) ORDER BY akpc.total DESC LIMIT $limit";;
	return $wpdb->get_results($q);
}

function wp_rp_fetch_posts_and_title() {
	$options = wp_rp_get_options();

	$limit = $options['max_related_posts'];

	$title = $options["related_posts_title"];
	$related_posts = wp_rp_fetch_related_posts($limit);
	$missing_rp_algorithm = $options["missing_rp_algorithm"];

	if (!$related_posts) {
		$title = $options["missing_rp_title"];
	}

	if (!$related_posts && $missing_rp_algorithm === "text") {
		$related_posts = false;
	} else if (!$related_posts || sizeof($related_posts) < $limit) {
		$related_posts = !is_array($related_posts) ? array() : $related_posts;
		$exclude_ids = array_map(create_function('$p', 'return $p->ID;'), $related_posts);

		$num_missing_posts = $limit - sizeof($related_posts);

		$other_posts = false;
		if ($missing_rp_algorithm === "commented") {
			$other_posts = wp_rp_fetch_most_commented_posts($num_missing_posts, $exclude_ids);
		} else if ($missing_rp_algorithm === "popularity" && function_exists('akpc_most_popular')) {
			$other_posts = wp_rp_fetch_most_popular_posts($num_missing_posts, $exclude_ids);
		} else if ($missing_rp_algorithm === "random") {
			$other_posts = wp_rp_fetch_random_posts($num_missing_posts, $exclude_ids);
		}

		if ($other_posts) {
			$related_posts = array_merge($related_posts, $other_posts);
		}
	}

	return array(
		"posts" => $related_posts,
		"title" => $title
	);
}

function wp_rp_generate_related_posts_list_items($related_posts) {
	$options = wp_rp_get_options();
	$output = "";
	$i = 0;

	foreach ($related_posts as $related_post ) {
		$output .= '<li position="' . $i++ . '">';

		$img = wp_rp_get_post_thumbnail_img($related_post);
		if ($img) {
			$output .=  '<a href="' . get_permalink($related_post->ID) . '">' . $img . '</a>';
		}

		if (!$options["display_thumbnail"] || ($options["display_thumbnail"] && ($options["thumbnail_display_title"] || !$img))) {
			if ($options["display_publish_date"]){
				$dateformat = get_option('date_format');
				$output .= mysql2date($dateformat, $related_post->post_date) . " -- ";
			}

			$output .= '<a href="' . get_permalink($related_post->ID) . '">' . wptexturize($related_post->post_title) . '</a>';

			if ($options["display_comment_count"]){
				$output .=  " (" . $related_post->comment_count . ")";
			}

			if ($options["display_excerpt"]){
				$excerpt_max_length = $options["excerpt_max_length"];
				if($related_post->post_excerpt){
					$output .= '<br /><small>' . (mb_substr(strip_shortcodes(strip_tags($related_post->post_excerpt)), 0, $excerpt_max_length)) . '...</small>';
				} else {
					$output .= '<br /><small>' . (mb_substr(strip_shortcodes(strip_tags($related_post->post_content)), 0, $excerpt_max_length)) . '...</small>';
				}
			}
		}
		$output .=  '</li>';
	}

	return $output;
}

function wp_rp_should_exclude() {
	global $wpdb, $post;
	$options = wp_rp_get_options();

	if($options['not_on_categories'] === '') { return false; }

	$exclude = explode(",", $options["not_on_categories"]);
	$q = 'SELECT tt.term_id FROM '. $wpdb->term_taxonomy.'  tt, ' . $wpdb->term_relationships.' tr WHERE tt.taxonomy = \'category\' AND tt.term_taxonomy_id = tr.term_taxonomy_id AND tr.object_id = '.$post->ID;

	$cats = $wpdb->get_results($q);

	foreach($cats as $cat) {
		if (in_array($cat->term_id, $exclude) != false){
			return true;
		}
	}
	return false;
}

function wp_rp_get_related_posts($before_title = '', $after_title = '') {
	global $wpdb, $post;

	if (wp_rp_should_exclude()) {
		return;
	}

	$options = wp_rp_get_options();
	$meta = wp_rp_get_meta();

	$output = "";
	$output_script = "";

	$promotional_link = '';

	$posts_and_title = wp_rp_fetch_posts_and_title();
	$related_posts = $posts_and_title['posts'];
	$title = $posts_and_title['title'];

	$statistics_enabled = $options['ctr_dashboard_enabled'] && $meta['blog_id'] && $meta['auth_key'];
	$remote_recommendations = $meta['remote_recommendations'] && $statistics_enabled;


	$output_script .= "window._wp_rp_static_base_url = \"" . WP_RP_STATIC_BASE_URL . "\";\n";

	if ($related_posts) {
		$output = wp_rp_generate_related_posts_list_items($related_posts);
		$output = "<ul class=\"related_post wp_rp\" style=\"visibility:" . ($remote_recommendations ? 'hidden' : 'visible') . ";\">" . $output . "</ul>\n";

		if ($options['include_promotionail_link']) {
			$promotional_link = ' <a target="_blank" rel="nofollow" title="WordPress Related Posts" href="http://related-posts.com/welcome2/" class="wp_rp_welcome" onclick="return window._wp_rp_show_banner && window._wp_rp_show_banner(event)">[?]</a>';
			$output .= "<div id=\"wp_rp_popup_holder\"></div>\n";

			wp_enqueue_script('wp_rp_welcome', WP_RP_STATIC_BASE_URL . WP_RP_STATIC_BANNER_FILE);
		}
	}

	if ($title != '') {
		if ($before_title) {
			$output = $before_title . $title . $after_title . $output;
		} else {
			$title_tag = $options["related_posts_title_tag"];
			$output =  '<' . $title_tag . '  class="related_post_title">' . $title . $promotional_link . '</' . $title_tag . '>' . $output;
		}
	}

	if ($options['enable_themes']) {
		$theme_name = $options['theme_name'];

		if ($options["display_thumbnail"]) {
			wp_enqueue_style('wp_rp_theme', WP_RP_STATIC_BASE_URL . WP_RP_STATIC_THEMES_THUMBS_PATH . $theme_name);
		} else {
			wp_enqueue_style('wp_rp_theme', WP_RP_STATIC_BASE_URL . WP_RP_STATIC_THEMES_PATH . $theme_name);
		}

		if ($theme_name === 'custom.css') {
			$theme_custom_css = $options['theme_custom_css'];

			$output .= '<style>' . $theme_custom_css . '</style>';
		}
	}

	if ($statistics_enabled) {
		$post_tags = '[' . implode(', ', array_map(create_function('$v', 'return "\'" . urlencode($v) . "\'";'), wp_get_post_tags($post->ID, array('fields' => 'names')))) . ']';

		$output_script .= 'window._wp_rp_blog_id = \'' . esc_js($meta['blog_id']) . '\';
			window._wp_rp_ajax_img_src_url = "' . esc_js(WP_RP_CTR_REPORT_URL) . '";
			window._wp_rp_post_id = \'' . esc_js($post->ID) . '\';
			window._wp_rp_thumbnails = ' . ($options["display_thumbnail"] ? 'true' : 'false') . ';
			window._wp_rp_post_title = \'' . urlencode($post->post_title) . '\';
			window._wp_rp_post_tags = ' . $post_tags . ';';

		wp_enqueue_script('wp_rp_pageview', WP_RP_STATIC_BASE_URL . WP_RP_STATIC_CTR_PAGEVIEW_FILE);
	}

	if ($remote_recommendations) {
		wp_enqueue_script('wp_rp_recommendations', WP_RP_STATIC_BASE_URL . WP_RP_STATIC_RECOMMENDATIONS_JS_FILE);
		wp_enqueue_style('wp_rp_recommendations', WP_RP_STATIC_BASE_URL . WP_RP_STATIC_RECOMMENDATIONS_CSS_FILE);
	}

	if ($options['scroll_up_related_posts']) {
		wp_enqueue_script('wp_rp_scrollup', WP_RP_STATIC_BASE_URL . WP_RP_STATIC_SCROLLUP_JS_FILE, array('jquery'));
		wp_enqueue_style('wp_rp_scrollup', WP_RP_STATIC_BASE_URL . WP_RP_STATIC_SCROLLUP_CSS_FILE);
	}

	if ($output_script) {
		$output_script = "<script type=\"text/javascript\">\n" . $output_script . "\n</script>";
	}

	return "\n" . $output_script  . "\n" . $output . "\n";
}
