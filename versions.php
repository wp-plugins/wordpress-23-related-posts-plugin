<?php


/* ************************* */
/*        Version 1.3        */
/* ************************* */

/* Settings:

	wp_no_rp			what to do if there are no related posts (`text`, `random`, most `commented` posts)
	wp_no_rp_text			text to show if there are no realted posts
	wp_rp_auto			auto insert related posts
	wp_rp_comments			display number of comments
	wp_rp_date			display publish date
	wp_rp_except			display post excerpt
	wp_rp_except_number		maximum number of characters to display for excerpt
	wp_rp_exclude			categories to exclude
	wp_rp_limit			maximum number of related posts
	wp_rp_rss			display related posts in RSS feed
	wp_rp_template			unused
	wp_rp_theme			related posts display theme
	wp_rp_thumbnail			display thumbnail for related posts
	wp_rp_thumbnail_post_meta	meta field to use for thumbnail
	wp_rp_thumbnail_text		show post title under thumbnails
	wp_rp_title			related posts title
	wp_rp_title_tag			related posts title tag (e.g. h2, h3, div, ...)

*/

/* Compatibility with wp_rp 1.3 - FIXME */
$wp_rp = get_option("wp_rp");
if (!isset($wp_rp['wp_rp_thumbnail_extract']) && $wp_rp['wp_rp_thumbnail_post_meta'] === 'wprp_featured_image') {
	$wp_rp['wp_rp_thumbnail_extract'] = 'yes';

	update_option( "wp_rp", $wp_rp );
}
