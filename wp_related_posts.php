<?php
/*
Plugin Name: WordPress Related Posts
Version: 1.5
Plugin URI: http://wordpress.org/extend/plugins/wordpress-23-related-posts-plugin/
Description: Generate a related posts list via tags of WordPress
Author: Jure Ham
Author URI: http://wordpress.org/extend/plugins/wordpress-23-related-posts-plugin/
*/

define('WP_RP_VERSION', '1.5');

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

function wp_rp_fetch_related_posts($limitclause = '') {
	global $wpdb, $post;
	$options = wp_rp_get_options();

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
		$q = "SELECT p.ID, p.post_title, p.post_content,p.post_excerpt, p.post_date,  p.comment_count, count(t_r.object_id) as cnt FROM $wpdb->term_taxonomy t_t, $wpdb->term_relationships t_r, $wpdb->posts p WHERE t_t.taxonomy ='post_tag' AND t_t.term_taxonomy_id = t_r.term_taxonomy_id AND t_r.object_id  = p.ID AND (t_t.term_id IN ($taglist)) AND p.ID != $post->ID AND p.post_status = 'publish' AND p.post_date_gmt < '$now' GROUP BY t_r.object_id ORDER BY cnt DESC, p.post_date_gmt DESC $limitclause;";

		$related_posts = $wpdb->get_results($q);
	}

	return $related_posts;
}

function wp_rp_fetch_random_posts ($limit = 10, $exclude_ids = array()) {
	global $wpdb, $post;

	array_push($exclude_ids, $post->ID);
	$exclude_ids = array_map('intval', $exclude_ids);

	$q1 = "SELECT ID FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post' AND ID NOT IN(" . implode(', ', $exclude_ids) . ")";
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

function wp_rp_fetch_most_commented_posts($limitclause = '') {
	global $wpdb;
	$q = "SELECT ID, post_title, post_content, post_excerpt, post_date, COUNT($wpdb->comments.comment_post_ID) AS 'comment_count' FROM $wpdb->posts, $wpdb->comments WHERE comment_approved = '1' AND $wpdb->posts.ID=$wpdb->comments.comment_post_ID AND post_status = 'publish' GROUP BY $wpdb->comments.comment_post_ID ORDER BY comment_count DESC $limitclause";
	return $wpdb->get_results($q);
}

function wp_rp_fetch_most_popular_posts ($limitclause = '') {
	global $wpdb, $table_prefix;

	$q = $sql = "SELECT p.ID, p.post_title, p.post_content,p.post_excerpt, p.post_date, p.comment_count FROM ". $table_prefix ."ak_popularity as akpc,".$table_prefix ."posts as p WHERE p.ID = akpc.post_id ORDER BY akpc.total DESC $limitclause";;
	return $wpdb->get_results($q);
}

function wp_rp_fetch_posts_and_title() {
	$options = wp_rp_get_options();

	$limit = $options['max_related_posts'];
	$limitclause = "LIMIT $limit";

	$title = $options["related_posts_title"];
	$related_posts = wp_rp_fetch_related_posts($limitclause);

	if (!$related_posts) {
		$missing_rp_algorithm = $options["missing_rp_algorithm"];
		$title = $options["missing_rp_title"];

		if ($missing_rp_algorithm == "text") {
			$related_posts = false;
		} else if ($missing_rp_algorithm === "commented") {
			$related_posts = wp_rp_fetch_most_commented_posts($limitclause);
		} else if ($missing_rp_algorithm == "popularity" && function_exists('akpc_most_popular')) {
			$related_posts = wp_rp_fetch_most_popular_posts($limitclause);
		} else {
			$related_posts = wp_rp_fetch_random_posts($limit);
		}
	}

	// fill related posts with random posts if there not enough posts found
	if(empty($related_posts) || sizeof($related_posts) < $limit) {
		$src_posts = !is_array($related_posts) ? array() : $related_posts;
		$exclude_ids = array_map(create_function('$p', 'return $p->ID;'), $src_posts);
		$random_posts = wp_rp_fetch_random_posts($limit - sizeof($src_posts), $exclude_ids);

		if(!empty($random_posts))
			$related_posts = array_merge($src_posts, $random_posts);
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

			$output .=  '<a href="' . get_permalink($related_post->ID) . '">' . wptexturize($related_post->post_title) . '</a>';

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

	if (wp_rp_should_exclude())
		return;

	$options = wp_rp_get_options();
	$meta = wp_rp_get_meta();

	$output = "";

	$posts_and_title = wp_rp_fetch_posts_and_title();
	$related_posts = $posts_and_title['posts'];
	$title = $posts_and_title['title'];

	$theme_name = $options['theme_name'];

	if ($options["display_thumbnail"]) {
		$theme_url = WP_RP_STATIC_BASE_URL . WP_RP_STATIC_THEMES_THUMBS_PATH . $theme_name;
	} else {
		$theme_url = WP_RP_STATIC_BASE_URL . WP_RP_STATIC_THEMES_PATH . $theme_name;
	}

	if ($related_posts) {
		$output = wp_rp_generate_related_posts_list_items($related_posts);
	} else {
		$output = '<li>' . $title . '</li>';
		$title = "";
	}

	$output = '<ul class="related_post wp_rp">' . $output . '</ul>';

	$title_tag = $options["related_posts_title_tag"];
	if ($before_title) {
		if ($title != '') {
			$output = $before_title . $title . $after_title . $output;
		}
	} else {
		if ($title != '') {
			$output =  '<' . $title_tag . '  class="related_post_title">' . $title . '</' . $title_tag . '>' . $output;
		}
	}

	if ($theme_name === 'custom.css') {
		$theme_custom_css = $options['theme_custom_css'];

		$output .= '<style>' . $theme_custom_css . '</style>';
	}

 	// figure out how to use wp_enqueue_style
	$output .= '<script type="text/javascript">
		setTimeout(function () {
			var link = document.createElement("link");
			link.rel = "stylesheet";
			link.href= "' . esc_js($theme_url) . '";
			link.type= "text/css";
			document.getElementsByTagName("body")[0].appendChild(link);
		}, 1);
		</script>';

	if ($options['ctr_dashboard_enabled'] && $meta['blog_id'] && $meta['auth_key']) {
		$output .= '<script type="text/javascript">
			window._wp_rp_blog_id = "' . esc_js($meta['blog_id']) . '";
			window._wp_rp_ajax_img_src_url = "' . esc_js(WP_RP_CTR_REPORT_URL) . '";
			window._wp_rp_post_id = ' . esc_js($post->ID) . ';
			window._wp_rp_thumbnails = ' . ($options["display_thumbnail"] ? 'true' : 'false') . ';
		</script>
		<script type="text/javascript" src="' . esc_attr(WP_RP_STATIC_BASE_URL . WP_RP_CTR_PAGEVIEW_FILE) . '"></script>';
	}


	return $output;
}
