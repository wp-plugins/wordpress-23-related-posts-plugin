<script type="text/javascript">
	function wp_rp_missing_rp_algorithm_onchange(){
		var wp_rp_missing_rp_algorithm = document.getElementById('wp_rp_missing_rp_algorithm');
		var wp_rp_missing_rp_title_th = document.getElementById('wp_rp_missing_rp_title_th');
		var wp_rp_missing_rp_title = document.getElementById('wp_rp_missing_rp_title');
		switch(wp_rp_missing_rp_algorithm.value){
		case 'text':
			wp_rp_missing_rp_title_th.innerHTML = '<?php _e("No Related Posts Text:", 'wp_related_posts'); ?>';
			wp_rp_missing_rp_title.value = '<?php _e("No Related Posts", 'wp_related_posts'); ?>';
			break;
		case 'random':
			wp_rp_missing_rp_title_th.innerHTML = '<?php _e("Random Posts Title:", 'wp_related_posts'); ?>';
			wp_rp_missing_rp_title.value = '<?php _e("Random Posts", 'wp_related_posts'); ?>';
			break;
		case 'commented':
			wp_rp_missing_rp_title_th.innerHTML = '<?php _e("Most Commented Posts Title:", 'wp_related_posts'); ?>';
			wp_rp_missing_rp_title.value = '<?php _e("Most Commented Posts", 'wp_related_posts'); ?>';
			break;
		case 'popularity':
			wp_rp_missing_rp_title_th.innerHTML = '<?php _e("Most Popular Posts Title:", 'wp_related_posts'); ?>';
			wp_rp_missing_rp_title.value = '<?php _e("Most Popular Posts", 'wp_related_posts'); ?>';
			break;
		default:
			wp_rp_missing_rp_title_th.innerHTML = '';
		}
		if(wp_rp_missing_rp_algorithm.value == '<?php echo $missing_rp_algorithm;?>') {
			wp_rp_missing_rp_title.value = '<?php echo $options["missing_rp_title"];?>';
		}
	}
	function wp_rp_display_excerpt_onclick(){
		var wp_rp_display_excerpt = document.getElementById('wp_rp_display_excerpt');
		var wp_rp_excerpt_max_length_label = document.getElementById('wp_rp_excerpt_max_length_label');
		if(wp_rp_display_excerpt.checked){
			wp_rp_excerpt_max_length_label.style.display = '';
		} else {
			wp_rp_excerpt_max_length_label.style.display = 'none';
		}
	}
	function wp_rp_display_thumbnail_onclick(){
		var wp_rp_display_thumbnail = document.getElementById('wp_rp_display_thumbnail');
		var wp_rp_thumbnail_span = document.getElementById('wp_rp_thumbnail_span');
		if(wp_rp_display_thumbnail.checked){
			wp_rp_thumbnail_span.style.display = '';
			jQuery('#wp-rp-thumbnails-info').fadeOut();
			if (window.localStorage) {
				window.localStorage.wp_rp_thumbnails_info = "close";
			}
		} else {
			wp_rp_thumbnail_span.style.display = 'none';
		}
	}
</script>
