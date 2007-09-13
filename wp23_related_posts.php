<?php
/*
Plugin Name: WP 2.3 Related Posts
Version: 0.1
Plugin URI: http://fairyfish.net/2007/09/12/wordpress-23-related-posts-plugin/
Description: Generate a related posts list via tags of WorPdress 2.3
Author: Denis Deng
Author URI: http://fairyfish.net/


Copyright (c) 2007
Released under the GPL license
http://www.gnu.org/licenses/gpl.txt

    This file is part of WordPress.
    WordPress is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

	INSTALL: 
	Just install the plugin in your blog and activate

*/

function wp23_get_related_posts($limit = 5) {
	global $wpdb, $post;
	
	$now = current_time('mysql', 1);

	$tags = wp_get_post_tags($post->ID);
	
	//print_r($tags);
	
	$taglist = "'" . str_replace("'",'',str_replace('"','',urldecode($tags[0]->term_id))). "'";
	$tagcount = count($tags);
	if ($tagcount > 1) {
		for ($i = 1; $i <= $tagcount; $i++) {
			$taglist = $taglist . ", '" . str_replace("'",'',str_replace('"','',urldecode($tags[$i]->term_id))) . "'";
		}
	}
	

	if ($limit != 0) {
		$limitclause = "LIMIT $limit";
	}

	$q = <<<SQL
	SELECT DISTINCT p.*, count(t_r.object_id) as cnt
		 FROM $wpdb->term_taxonomy t_t, $wpdb->term_relationships t_r, $wpdb->posts p
		 WHERE t_t.taxonomy = 'post_tag'
		 AND t_t.term_taxonomy_id = t_r.term_taxonomy_id
		 AND t_r.object_id  = p.ID
		 AND (t_t.term_id IN ($taglist))
		 AND p.ID != $post->ID
		 AND post_date_gmt < '$now'
		 AND p.ID != $post->ID 
		 GROUP BY t_r.object_id
		 ORDER BY cnt DESC, post_date_gmt DESC $limitclause;
SQL;
	//echo $q;

	return $wpdb->get_results($q);
}

function wp23_related_posts($limit = 5){
	$relate_posts = wp23_get_related_posts($limit) ;
	echo '<h3>Related Post</h3>';
	echo '<ul class="related_post">';
	foreach ($relate_posts as $relate_post ){
		echo '<li><a href="'.get_permalink($relate_post->ID).'" title="'.$relate_post-> post_title .'">'.$relate_post-> post_title.'</a></li>';
	}
	echo '</ul>';
}

function wp23_related_posts_for_feed($content=""){
	if ( ! is_feed() ) return $content;

	$relate_posts = wp23_get_related_posts() ;
	$content = $content . '<h3>Related Post</h3><ul class="related_post">';
	foreach ($relate_posts as $relate_post ){
		$content = $content . '<li><a href="'.get_permalink($relate_post->ID).'" title="'.$relate_post-> post_title .'">'.$relate_post-> post_title.'</a></li>';
	}
	$content = $content . '</ul>';
	
	return $content;
}

add_filter('the_content', 'wp23_related_posts_for_feed',99);

?>