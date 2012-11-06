<?php

/* ***************************** */
/* WP RP backwards compatibility */
/* ***************************** */

function wp_random_posts ($number = 10){
	$random_posts = wp_get_random_posts ($number);

	foreach ($random_posts as $random_post ){
		$output .= '<li>';

		$output .=  '<a href="' . get_permalink($random_post->ID) . '" title="' . esc_attr(wptexturize($random_post->post_title)) . '">' . wptexturize($random_post->post_title) . '</a></li>';
	}

	$output = '<ul class="randome_post">' . $output . '</ul>';

	echo $output;
}

function wp_most_popular_posts ($number = 10){
	$most_popular_posts = wp_get_most_popular_posts ($number);

	foreach($most_popular_posts as $most_popular_post) {
		$output .=  '<li><a href="' . get_permalink($most_popular_post->ID) . '" title="' . esc_attr(wptexturize($most_popular_post->post_title)) . '">' . wptexturize($most_popular_post->post_title) . '</a></li>';
	}

	$output = '<ul class="most_popular_post">' . $output . '</ul>';

	echo $output;
}

function wp_most_commented_posts ($number = 10){
	$most_commented_posts = wp_get_most_commented_posts ($number);

	foreach($most_commented_posts as $most_commented_post) {
		$output .=  '<li><a href="'.get_permalink($most_commented_post->ID).'" title="' . esc_attr(wptexturize($most_commented_post->post_title)) . '">' . wptexturize($most_commented_post->post_title) . '</a></li>';
	}

	$output = '<ul class="most_commented_post">' . $output . '</ul>';

	echo $output;
}

function wp23_related_posts() {
	wp_related_posts();
}

function wp_related_posts() {
	$output = wp_get_related_posts();
	echo $output;
}

function wp_get_random_posts ($limitclause = '') {
	$limit = filter_var($limitclause, FILTER_SANITIZE_NUMBER_INT);
	return wp_rp_fetch_random_posts($limit);
}

function wp_get_most_commented_posts($limitclause = '') {
	$limit = filter_var($limitclause, FILTER_SANITIZE_NUMBER_INT);
	return wp_rp_fetch_most_commented_posts($limit);
}

function wp_get_most_popular_posts ($limitclause = '') {
	$limit = filter_var($limitclause, FILTER_SANITIZE_NUMBER_INT);
	return wp_rp_fetch_most_popular_posts($limit);
}

function wp_fetch_related_posts($limitclause = '') {
	$limit = filter_var($limitclause, FILTER_SANITIZE_NUMBER_INT);
	return wp_rp_fetch_related_posts($limit);
}

function wp_fetch_random_posts($limit = 10) {
	return wp_rp_fetch_random_posts($limit);
}

function wp_fetch_most_commented_posts($limitclause = '') {
	$limit = filter_var($limitclause, FILTER_SANITIZE_NUMBER_INT);
	return wp_rp_fetch_most_commented_posts($limit);
}

function wp_fetch_most_popular_posts($limitclause = '') {
	$limit = filter_var($limitclause, FILTER_SANITIZE_NUMBER_INT);
	return wp_rp_fetch_most_popular_posts($limit);
}

function wp_fetch_content() {
	return wp_rp_fetch_posts_and_title();
}

function wp_generate_related_posts_list_items($related_posts) {
	return wp_rp_generate_related_posts_list_items($related_posts);
}

function wp_should_exclude() {
	return wp_rp_should_exclude();
}

function wp_get_related_posts($before_title = '', $after_title = '') {
	return wp_rp_get_related_posts($before_title, $after_title);
}

?>
