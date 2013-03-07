<?php

add_action('wp_rp_admin_notices', 'wp_rp_display_admin_notices');

// Show connect notice on dashboard and plugins pages
add_action( 'load-index.php', 'wp_rp_prepare_admin_connect_notice' );
add_action( 'load-plugins.php', 'wp_rp_prepare_admin_connect_notice' );

function wp_rp_display_admin_notices() {
	global $wp_rp_admin_notices;

	foreach ((array) $wp_rp_admin_notices as $notice) {
		echo '<div id="message" class="' . $notice[0] . ' below-h2"><p>' . $notice[1] . '</p></div>';
	}
}

function wp_rp_prepare_admin_connect_notice() {
	$meta = wp_rp_get_meta();

	if ($meta['blog_tg'] == 1 && $meta['show_turn_on_button'] && !$meta['turn_on_button_pressed'] && !$meta['blog_id'] && $meta['new_user']) {
		wp_register_style( 'wp_rp_connect_style', plugins_url('static/css/connect.css', __FILE__) );
		wp_register_script( 'wp_rp_connect_js', plugins_url('static/js/connect.js', __FILE__) );
		add_action( 'admin_notices', 'wp_rp_admin_connect_notice' );
	}
}

function wp_rp_admin_connect_notice() {
	wp_enqueue_style( 'wp_rp_connect_style' );
	wp_enqueue_script( 'wp_rp_connect_js' );
	?>
	<div id="wp-rp-message" class="updated wp-rp-connect">
		<div id="wp-rp-dismiss">
			<a id="wp-rp-close-button"></a>
		</div>
		<div id="wp-rp-wrap-container">
			<div id="wp-rp-connect-wrap">
				<form action="<?php echo admin_url('admin.php?page=wordpress-related-posts&ref=turn-on-rp'); ?>" method="post">
					<input type="hidden" value="yes" name="wp_rp_enable_themes" id="wp_rp_enable_themes" />
					<input type="hidden" value="yes" name="wp_rp_ctr_dashboard_enabled" id="wp_rp_ctr_dashboard_enabled" />
					<input type="hidden" value="yes" name="wp_rp_promoted_content_enabled" id="wp_rp_promoted_content_enabled" />
					<input type="hidden" value="yes" name="wp_rp_traffic_exchange_enabled" id="wp_rp_traffic_exchange_enabled" />

					<input type="hidden" value="statistics+thumbnails+promoted" name="wp_rp_turn_on_button_pressed" id="wp_rp_turn_on_button_pressed" />
					<input type="hidden" value="turn-on-banner" name="wp_rp_button_type" id="wp_rp_button_type" />

					<input type="submit" id="wp-rp-login" value="Turn on" />
				</form>
			</div>
			<div id="wp-rp-text-container">
				<h4>WordPress Related Posts are almost ready,</h4>
				<h4>now all you need to do is connect to our service.</h4>
			</div>
		</div>
		<div id="wp-rp-bottom-container">
			<p>You'll get Settings, Themes, Thumbnails, Reader Exchange and Promoted Content.  These features are provided by <a target="_blank" href="http://www.zemanta.com"><b>Zemanta</b></a> as a service.</p>
		</div>
	</div>
	<?php
}

function wp_rp_add_admin_notice($type = 'updated', $message = '') {
	global $wp_rp_admin_notices;
	
	if (strtolower($type) == 'updated' && $message != '') {
		$wp_rp_admin_notices[] = array('updated', $message);
		return true;
	}
	
	if (strtolower($type) == 'error' && $message != '') {
		$wp_rp_admin_notices[] = array ('error', $message);
		return true;
	}
	
	return false;
}
