<?php

/**
* Tooltips
**/

function wp_rp_display_tooltips() {
	$meta = wp_rp_get_meta();

	if ($meta['show_upgrade_tooltip']) {
		$meta['show_upgrade_tooltip'] = false;
		wp_rp_update_meta($meta);

		add_action('admin_enqueue_scripts', 'wp_rp_load_upgrade_tooltip');
	} else if ($meta['show_install_tooltip']) {
		$meta['show_install_tooltip'] = false;
		wp_rp_update_meta($meta);

		add_action('admin_enqueue_scripts', 'wp_rp_load_install_tooltip');
	}
}
function wp_rp_load_upgrade_tooltip() {
	if (version_compare(get_bloginfo('version'), '3.3', '<')) {
		return;
	}

    wp_enqueue_style('wp-pointer');
    wp_enqueue_script('wp-pointer');
    add_action('admin_print_footer_scripts', 'wp_rp_print_upgrade_tooltip');
}
function wp_rp_print_upgrade_tooltip() {
	$content = "<h3>Thanks for updating Related Posts plugin!</h3><p>We've added some new stuff to the Settings, go check them out. Let us know what you think.</p>";
	wp_rp_print_tooltip($content);
}

function wp_rp_load_install_tooltip() {
	if (version_compare(get_bloginfo('version'), '3.3', '<')) {
		return;
	}

    wp_enqueue_style('wp-pointer');
    wp_enqueue_script('wp-pointer');
    add_action('admin_print_footer_scripts', 'wp_rp_print_install_tooltip');
}
function wp_rp_print_install_tooltip() {
	$content = "<h3>Thanks for installing Related Posts plugin!</h3><p>To experience the full power of Related Posts, go to settings and turn Statistics on!</p>";
	wp_rp_print_tooltip($content);
}

function wp_rp_print_tooltip($content) {
	?>
	<script type="text/javascript">
		jQuery(function ($) {
			var body = $(document.body),
				collapse = $('#collapse-menu'),
				target = $("#toplevel_page_wordpress-related-posts"),
				collapse_handler = function (e) {
					body.pointer('reposition');
				},
				options = {
					content: "<?php echo $content; ?>",
					position: {
						edge: 'left',
						align: 'center',
						of: target
					},
					open: function () {
						collapse.bind('click', collapse_handler);
					},
					close: function() {
						collapse.unbind('click', collapse_handler);
					}
				};

			if (target.length) {
				body.pointer(options).pointer('open');
			}
		});
	</script>
	<?php
}

/**
* Place menu icons at admin head
**/
add_action('admin_head', 'wp_rp_admin_head');
function wp_rp_admin_head() {
	$menu_icon = plugins_url('static/img/menu_icon.png', __FILE__);
	$menu_icon_retina = plugins_url('static/img/menu_icon_2x.png', __FILE__);
?>
<style type="text/css">
#toplevel_page_wordpress-related-posts .wp-menu-image {
	background: url('<?php echo $menu_icon; ?>') 7px 6px no-repeat;
}
@media only screen and (-webkit-min-device-pixel-ratio: 1.5) {
	#toplevel_page_wordpress-related-posts .wp-menu-image {
		background-image: url('<?php echo $menu_icon_retina; ?>');
		background-size: 16px 16px;
	}
}
</style>
<?php
}

/**
* Settings
**/

add_action('admin_menu', 'wp_rp_settings_admin_menu');

function wp_rp_settings_admin_menu() {
	$page = add_menu_page(__('Related Posts', 'wp_related_posts'), __('Related Posts', 'wp_related_posts'), 
						'manage_options', 'wordpress-related-posts', 'wp_rp_settings_page', 'div');

	add_action('admin_print_styles-' . $page, 'wp_rp_settings_styles');
	add_action('admin_print_scripts-' . $page, 'wp_rp_settings_scripts');

	wp_rp_display_tooltips();
}

function wp_rp_settings_scripts() {
	wp_enqueue_script('wp_rp_themes_script', plugins_url('static/js/themes.js', __FILE__), array('jquery'));
	wp_enqueue_script("wp_rp_dashboard_script", plugins_url("static/js/dashboard.js", __FILE__), array( 'jquery' ) );
}
function wp_rp_settings_styles() {
	wp_enqueue_style("wp_rp_dashaboard_style", plugins_url("static/css/dashboard.css", __FILE__));
}

function wp_rp_register_blog() {
	$meta = wp_rp_get_meta();

	$req_options = array(
		'timeout' => 30
	);

	$response = wp_remote_get(WP_RP_CTR_DASHBOARD_URL . 'register/?blog_url=' . get_bloginfo('wpurl') . ($meta['new_user'] ? '&new' : ''), $req_options);
	if (wp_remote_retrieve_response_code($response) == 200) {
		$body = wp_remote_retrieve_body($response);
		if ($body) {
			$doc = json_decode($body);
			if ($doc && $doc->status === 'ok') {
				$meta['blog_id'] = $doc->data->blog_id;
				$meta['auth_key'] = $doc->data->auth_key;
				$meta['new_user'] = false;
				wp_rp_update_meta($meta);

				return true;
			}
		}
	}
	return false;
}

function wp_rp_ajax_invite_friends_callback() {
	$postdata = stripslashes_deep($_POST);

	$meta = wp_rp_get_meta();

	if(isset($postdata['name'])) {
		$meta['name'] = $postdata['name'];
	}
	if(isset($postdata['email'])) {
		$meta['email'] = $postdata['email'];
	}
	if(isset($postdata['show'])) {
		$meta['show_invite_friends_form'] = true;
	}
	if(isset($postdata['hide'])) {
		$meta['show_invite_friends_form'] = false;
	}
	if(isset($postdata['recommend'])) {
		$meta['remote_recommendations'] = true;
	}

	wp_rp_update_meta($meta);


	echo 'ok';
	die();
}

add_action('wp_ajax_rp_invite_friends', 'wp_rp_ajax_invite_friends_callback');

function wp_rp_settings_page()
{
	$title_tags = array('h2', 'h3', 'h4', 'p', 'div');

	$options = wp_rp_get_options();
	$meta = wp_rp_get_meta();

	$postdata = stripslashes_deep($_POST);

	if(sizeof($_POST))
	{
		$message = __('WordPress Related Posts Setting Updated', 'wp_related_posts');

		$old_options = $options;
		$new_options = array(
			'missing_rp_algorithm' => isset($postdata['wp_rp_missing_rp_algorithm']) ? trim($postdata['wp_rp_missing_rp_algorithm']) : 'random',
			'missing_rp_title' => isset($postdata['wp_rp_missing_rp_title']) ? ($postdata['wp_rp_missing_rp_title']) : __('Random Posts', 'wp_related_posts'),
			'on_single_post' => isset($postdata['wp_rp_on_single_post']),
			'display_comment_count' => isset($postdata['wp_rp_display_comment_count']),
			'display_publish_date' => isset($postdata['wp_rp_display_publish_date']),
			'display_excerpt' => isset($postdata['wp_rp_display_excerpt']),
			'excerpt_max_length' => (isset($postdata['wp_rp_excerpt_max_length']) && is_numeric(trim($postdata['wp_rp_excerpt_max_length']))) ? intval(trim($postdata['wp_rp_excerpt_max_length'])) : 200,
			'max_related_posts' => (isset($postdata['wp_rp_max_related_posts']) && is_numeric(trim($postdata['wp_rp_max_related_posts']))) ? intval(trim($postdata['wp_rp_max_related_posts'])) : 5,
			'on_rss' => isset($postdata['wp_rp_on_rss']),
			'display_thumbnail' => isset($postdata['wp_rp_display_thumbnail']),
			'thumbnail_custom_field' => isset($postdata['wp_rp_thumbnail_custom_field']) ? trim($postdata['wp_rp_thumbnail_custom_field']) : false,
			'thumbnail_display_title' => isset($postdata['wp_rp_thumbnail_display_title']),
			'related_posts_title' => isset($postdata['wp_rp_related_posts_title']) ? trim($postdata['wp_rp_related_posts_title']) : '',
			'related_posts_title_tag' => isset($postdata['wp_rp_related_posts_title_tag']) ? $postdata['wp_rp_related_posts_title_tag'] : 'h3',
			'thumbnail_use_attached' => isset($postdata['wp_rp_thumbnail_use_attached']),
			'thumbnail_use_custom' => isset($postdata['wp_rp_thumbnail_use_custom']) && $postdata['wp_rp_thumbnail_use_custom'] === 'yes',
			'ctr_dashboard_enabled' => isset($postdata['wp_rp_ctr_dashboard_enabled']),
			'include_promotionail_link' => isset($postdata['wp_rp_include_promotionail_link']),
			'enable_themes' => isset($postdata['wp_rp_enable_themes']),
			'scroll_up_related_posts' => isset($postdata['wp_rp_scroll_up_related_posts'])
		);

		if(!isset($postdata['wp_rp_not_on_categories'])) {
			$new_options['not_on_categories'] = '';
		} else if(is_array($postdata['wp_rp_not_on_categories'])) {
			$new_options['not_on_categories'] = join(',', $postdata['wp_rp_not_on_categories']);
		} else {
			$new_options['not_on_categories'] = trim($postdata['wp_rp_not_on_categories']);
		}

		if(isset($postdata['wp_rp_theme_name'])) {		// If this isn't set, maybe the AJAX didn't load...
			$new_options['theme_name'] = trim($postdata['wp_rp_theme_name']);

			if(isset($postdata['wp_rp_theme_custom_css'])) {
				$new_options['theme_custom_css'] = $postdata['wp_rp_theme_custom_css'];
			} else {
				$new_options['theme_custom_css'] = '';
			}
		} else {
			$new_options['theme_name'] = $old_options['theme_name'];
			$new_options['theme_custom_css'] = $old_options['theme_custom_css'];
		}

		$default_thumbnail_path = wp_rp_upload_default_thumbnail_file();
		if($default_thumbnail_path) {
			$new_options['default_thumbnail_path'] = $default_thumbnail_path;
		} else {
			if(isset($postdata['wp_rp_default_thumbnail_remove'])) {
				$new_options['default_thumbnail_path'] = false;
			} else {
				$new_options['default_thumbnail_path'] = $old_options['default_thumbnail_path'];
			}
		}

		if (((array) $old_options) != $new_options) {
			if($new_options['ctr_dashboard_enabled'] && $new_options['display_thumbnail'] && $meta['show_turn_on_button']) {
				$meta['show_turn_on_button'] = false;
				wp_rp_update_meta($meta);
			}

			if(!wp_rp_update_options($new_options)) {
				$message = __('Update Failed', 'wp_related_posts');
			}
		}
	}

	if($options['ctr_dashboard_enabled'] && (!$meta['blog_id'] || !$meta['auth_key'])) {
		wp_rp_register_blog();
	}

?>

	<div class="wrap" id="wp_rp_wrap">
	<?php
		$missing_rp_algorithm = $options['missing_rp_algorithm'];
		$related_posts_title_tag = $options['related_posts_title_tag'];
		$theme_name = $options['theme_name'];
		$theme_custom_css = $options['theme_custom_css'];

		include(dirname(__FILE__) . '/static/settings.js.php');
	?>

		<input type="hidden" id="wp_rp_json_url" value="<?php esc_attr_e(WP_RP_STATIC_BASE_URL . WP_RP_STATIC_JSON_PATH); ?>" />
		<input type="hidden" id="wp_rp_version" value="<?php esc_attr_e(WP_RP_VERSION); ?>" />
		<input type="hidden" id="wp_rp_theme_selected" value="<?php esc_attr_e($theme_name); ?>" />
		<input type="hidden" id="wp_rp_dashboard_url" value="<?php esc_attr_e(WP_RP_CTR_DASHBOARD_URL); ?>" />

		<?php if ($options['ctr_dashboard_enabled']):?>
		<input type="hidden" id="wp_rp_blog_id" value="<?php esc_attr_e($meta['blog_id']); ?>" />
		<input type="hidden" id="wp_rp_auth_key" value="<?php esc_attr_e($meta['auth_key']); ?>" />
		<?php endif; ?>

		<div class="header">
			<div class="support">
				<h4><?php _e("Awesome support", 'wp_related_posts'); ?></h4>
				<p>
					<?php _e("If you have any questions please contact us at",'wp_related_posts');?> <a target="_blank" href="mailto:relatedpostsplugin@gmail.com"><?php _e("support", 'wp_related_posts');?></a>.
				</p>
			</div>
			<h2 class="title"><?php _e("Related Posts",'wp_related_posts');?></h2>
			<p class="desc"><?php _e("WordPress Related Posts Plugin places a list of related articles via WordPress tags at the bottom of your post.",'wp_related_posts');?></p>
		</div>
		<div id="wp-rp-survey" class="updated highlight" style="display:none;"><p><?php _e("Please fill out",'wp_related_posts');?> <a class="link" target="_blank" href="http://wprelatedposts.polldaddy.com/s/new-features"><?php _e("a quick survey", 'wp_related_posts');?></a>.<a href="#" class="close" style="float: right;">x</a></p></div>

		<?php if (isset($message)): ?>
		<div id="message" class="updated fade"><p><?php echo $message ?>.</p></div>
		<?php endif; ?>

		<?php if(!$meta['show_turn_on_button']): ?>
		<form action="https://docs.google.com/a/zemanta.com/spreadsheet/formResponse?formkey=dHhqdWtyZHIwN0Z5R2ZEel9oZVBidEE6MQ&amp;ifq" method="POST" id="wp_rp_invite_friends_form" target="wp_rp_invite_friends_hidden_iframe" <?php if(!$meta['show_invite_friends_form']) { ?>class="up"<?php } ?>>
			<input type="hidden" name="pageNumber" value="0">
			<input type="hidden" name="backupCache" value="">
			<input type="hidden" name="entry.6.single" value="<?php echo get_bloginfo('wpurl'); ?>">

			<a href="#" id="wp_rp_invite_friends_slide"><img src="<?php echo plugins_url('/static/img/up.png', __FILE__); ?>" width="17" height="6" border="0" class="up" /><img src="<?php echo plugins_url('/static/img/down.png', __FILE__); ?>" width="17" height="6" border="0" class="down" /></a>
			<h2>Invite friends</h2>

			<div class="slide-down" <?php if(!$meta['show_invite_friends_form']) { ?>style="display: none"<?php } ?>>
				<p>Get your friends to use WordPress Related Posts and instantly exchange traffic with them. Your posts will appear on their site and vice versa.</p>

				<?php if(!$meta['name'] || !$meta['email']): ?>
				<table class="form-table" id="wp_rp_invite_friends_name_table"><tbody>
					<tr valign="top">
						<th scope="row"><label for="wp_rp_invite_friends_blogger_name">Your name</label></th>
						<td width="1%"><input type="text" name="entry.0.single" value="" id="wp_rp_invite_friends_blogger_name" tabindex="1" requred="required" /></td>
						<td rowspan="2"></td>
					</tr><tr valign="top">
						<th scope="row"><label for="wp_rp_invite_friends_blogger_email">Your email</label></th>
						<td><input type="email" name="entry.1.single" value="" id="wp_rp_invite_friends_blogger_email" tabindex="2" requred="required" /><br/></td>
					</tr>
				</tbody></table>
				<div class="hr" id="wp_rp_confirmation_hr"></div>
				<? else: ?>
				<input type="hidden" name="entry.0.single" value="<?php echo $meta['name']; ?>">
				<input type="hidden" name="entry.1.single" value="<?php echo $meta['email']; ?>">
				<div class="hr" id="wp_rp_confirmation_hr" style="display: none;"></div>
				<?php endif; ?>

				<table class="form-table"><tbody>
					<tr valign="top">
						<th scope="row"><label for="wp_rp_invite_friends_friend_url">Your friend's blog</label></th>
						<td width="1%"><input type="text" name="entry.2.single" value="" id="wp_rp_invite_friends_friend_url" tabindex="3" required="required" /></td>
						<td rowspan="2" valign="middle"><input type="submit" name="submit" value="Invite" id="wp_rp_invite_friends_submit" tabindex="5" /></td>
					</tr><tr valign="top">
						<th scope="row"><label for="wp_rp_invite_friends_friend_email">Your friend's email</label></th>
						<td><input type="email" name="entry.4.single" value="" id="wp_rp_invite_friends_friend_email" tabindex="4" required="required" /></td>
					</tr>
				</tbody></table>
			</div>

			<script type="text/javascript">
jQuery(function($) {
	var submit = $('#wp_rp_invite_friends_submit'),
		form = $('#wp_rp_invite_friends_form'),
		blogger_name = $('#wp_rp_invite_friends_blogger_name'),
		blogger_email = $('#wp_rp_invite_friends_blogger_email'),
		friend_url = $('#wp_rp_invite_friends_friend_url'),
		friend_email = $('#wp_rp_invite_friends_friend_email'),
		slide_div = form.find('.slide-down'),
		submitted = false;
		email_regex = /^[^@]+@[^@]+$/;
	$('#wp_rp_invite_friends_form').submit(function(event) {
			var valid = true;
			if(!email_regex.test(friend_email.val())) {
				valid = false;
				friend_email.animate({backgroundColor: '#faa'}).focus();
			} else {
				friend_email.css({backgroundColor: ''});
			}
			if(!friend_url.val()) {
				valid = false;
				friend_url.animate({backgroundColor: '#faa'}).focus();
			} else {
				friend_url.css({backgroundColor: ''});
			}
			if(blogger_email.length && !email_regex.test(blogger_email.val())) {
				valid = false;
				blogger_email.animate({backgroundColor: '#faa'}).focus();
			} else {
				blogger_email.css({backgroundColor: ''});
			}
			if(blogger_name.length && !blogger_name.val()) {
				valid = false;
				blogger_name.animate({backgroundColor: '#faa'}).focus();
			} else {
				blogger_name.css({backgroundColor: ''});
			}
			if(!valid) {
				event.preventDefault();
				return;
			}

			submitted = true;
			submit.addClass('disabled');
			setTimeout(function() { submit.attr('disabled', true); }, 0);
		});

	$('#wp_rp_invite_friends_hidden_iframe').load(function() {
			if(!submitted) { return; } else { submitted = false; }

			setTimeout(function() {
				submit.attr('disabled', false).removeClass('disabled');
				var confirmation = $('<div class="confirmation">An invitation was sent to <span>' + friend_email.val() + '</span>.</div>'),
					hr = form.find('#wp_rp_confirmation_hr'),
					name_table = $('#wp_rp_invite_friends_name_table');

				name_table.hide();
				hr.fadeIn();
				confirmation.hide().insertBefore(hr).fadeIn();

				var data = {	action: 'rp_invite_friends',
						recommend: true };

				if(blogger_name.length && blogger_email.length) {
					form.append('<input type="hidden" name="entry.0.single" value="' + blogger_name.val() + '">');
					form.append('<input type="hidden" name="entry.1.single" value="' + blogger_email.val() + '">');

					data['name'] = blogger_name.val();
					data['email'] = blogger_email.val();

					blogger_name.remove();
					blogger_email.remove();
				}
				$.post(ajaxurl, data);

				form[0].reset();
			}, 100);
		});

	$('#wp_rp_invite_friends_slide').click(function (event) {
			event.preventDefault();
			if(form.hasClass('up')) {
				slide_div.slideDown();
				form.removeClass('up');
				$.post(ajaxurl, { action: 'rp_invite_friends', show: true });
			} else {
				slide_div.slideUp();
				form.addClass('up');
				$.post(ajaxurl, { action: 'rp_invite_friends', hide: true });
			}
		});
});
			</script>
		</form>
		<iframe id="wp_rp_invite_friends_hidden_iframe" name="wp_rp_invite_friends_hidden_iframe" style="display: none"></iframe>
		<?php endif; ?>

		<?php if($meta['show_turn_on_button']): ?>
		<div id="wp_rp_turn_on_statistics">
			<table cellspacing="0" cellpadding="0"><tbody><tr>
				<td>
					<h2>Turn on Statistics & Thumbnails</h2>
					<p>Real time traffic analytics are provided via third party service.</p>
				</td><td>
					<a href="#">Turn on</a>
				</td>
			</tr></tbody></table>
		</div>
		<?php endif; ?>

		<form method="post" enctype="multipart/form-data" action="" id="wp_rp_settings_form">
		<?php if ($options['ctr_dashboard_enabled']): ?>
			<h2><?php _e("Statistics",'wp_related_posts');?></h2>
			<div id="wp_rp_statistics_wrap">
				<div class="message unavailable"><?php _e("Statistics currently unavailable",'wp_related_posts');?></div>
			</div>
		<?php endif; ?>

			<h2><?php _e("Settings",'wp_related_posts');?></h2>
			<h3><?php _e("Basic Settings",'wp_related_posts');?></h3>

			<table class="form-table">
			  <tr valign="top">
				<th scope="row"><?php _e('Related Posts Title:', 'wp_related_posts'); ?></th>
				<td>
				  <input name="wp_rp_related_posts_title" type="text" id="wp_rp_related_posts_title" value="<?php esc_attr_e($options['related_posts_title']); ?>" class="regular-text" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Number of Posts:', 'wp_related_posts');?></th>
				<td>
				  <input name="wp_rp_max_related_posts" type="number" step="1" id="wp_rp_max_related_posts" class="small-text" min="1" value="<?php esc_attr_e($options['max_related_posts']); ?>" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Categories to Exclude:', 'wp_related_posts'); ?></th>
				<td>
					<?php
					$exclude = explode(',', $options['not_on_categories']);
					$args = array(
						'orderby' => 'name',
						'order' => 'ASC',
						'hide_empty' => false
						);

					foreach (get_categories($args) as $category) :
					?>
					<label>
						<input name="wp_rp_not_on_categories[]" type="checkbox" id="wp_rp_not_on_categories" value="<?php esc_attr_e($category->cat_ID); ?>"<?php checked(in_array($category->cat_ID, $exclude)); ?> />
						<?php esc_html_e($category->cat_name); ?>
						<br />
					</label>
					<?php endforeach; ?>
				</td>
			</tr>
			</table>

			<h3>Theme Settings</h3>
			<table class="form-table">
				<tr id="wp_rp_theme_options_wrap">
					<th scope="row">Select Theme:</th>
					<td>
						<label>
							<input name="wp_rp_enable_themes" type="checkbox" id="wp_rp_enable_themes" value="yes"<?php checked($options["enable_themes"]); ?> />
							<?php _e("Enable Themes",'wp_related_posts'); ?>*
						</label>
						<div class="theme-list"></div>
					</td>
				</tr>
				<tr id="wp_rp_theme_custom_css_wrap" style="display: none; ">
					<th scope="row"></th>
					<td>
						<textarea style="width: 300px; height: 215px;" name="wp_rp_theme_custom_css" class="custom-css"><?php echo htmlspecialchars($theme_custom_css, ENT_QUOTES); ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e("Thumbnail Options:",'wp_related_posts'); ?></th>
					<td>
						<label>
							<input name="wp_rp_display_thumbnail" type="checkbox" id="wp_rp_display_thumbnail" value="yes"<?php checked($options["display_thumbnail"]); ?> onclick="wp_rp_display_thumbnail_onclick();" />
							<?php _e("Display Thumbnails For Related Posts",'wp_related_posts');?>
						</label>
						<br />
						<span id="wp_rp_thumbnail_span" style="<?php echo $options["display_thumbnail"] ? '' : 'display:none;'; ?>">
						<label>
							<input name="wp_rp_thumbnail_display_title" type="checkbox" id="wp_rp_thumbnail_display_title" value="yes"<?php checked($options["thumbnail_display_title"]); ?> />
							<?php _e('Display Post Titles', 'wp_related_posts');?>
						</label>
						<br />

						<?php
						global $wpdb;

						$custom_fields = $wpdb->get_col( "SELECT meta_key FROM $wpdb->postmeta GROUP BY meta_key HAVING meta_key NOT LIKE '\_%' ORDER BY LOWER(meta_key)" );

						if($custom_fields) :
						?>
						<label><input name="wp_rp_thumbnail_use_custom" type="radio" value="no" <?php checked(!$options['thumbnail_use_custom']); ?>> Use featured image</label>&nbsp;&nbsp;&nbsp;&nbsp;
						<label><input name="wp_rp_thumbnail_use_custom" type="radio" value="yes" <?php checked($options['thumbnail_use_custom']); ?>> Use custom field</label>

						<select name="wp_rp_thumbnail_custom_field" id="wp_rp_thumbnail_custom_field"  class="postform">
						
						<?php foreach ( $custom_fields as $custom_field ) : ?>
							<option value="<?php esc_attr_e($custom_field); ?>"<?php selected($options["thumbnail_custom_field"], $custom_field); ?>><?php esc_html_e($custom_field);?></option>
						<?php endforeach; ?>

						</select>
						<br />
						<?php endif; ?>

						<label>
							<input name="wp_rp_thumbnail_use_attached" type="checkbox" value="yes" <?php checked($options["thumbnail_use_attached"]); ?>>
							<?php _e("If featured image is missing, show the first uploaded image of the post",'wp_related_posts');?>
						</label>
						<br />


						<br />
						<label>
							<?php _e('For posts without images, a default image will be shown.<br/>
							You can upload your own default image here','wp_related_posts');?>
							<input type="file" name="wp_rp_default_thumbnail"  />
						</label>
						<?php if($options['default_thumbnail_path']) : ?>
							<span style="display: inline-block; vertical-align: top; *display: inline; zoom: 1;">
								<img style="padding: 3px; border: 1px solid #DFDFDF; border-radius: 3px;" valign="top" width="80" height="80" src="<?php esc_attr_e(wp_rp_get_default_thumbnail_url()); ?>" alt="selected thumbnail" />
								<br />
								<label>
									<input type="checkbox" name="wp_rp_default_thumbnail_remove" value="yes" />
									<?php _e("Remove selected",'wp_related_posts');?>
								</label>
							</span>
						<?php endif; ?>
						</span>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e("Display Options:",'wp_related_posts'); ?></th>
					<td>
						<label>
						<input name="wp_rp_display_comment_count" type="checkbox" id="wp_rp_display_comment_count" value="yes" <?php checked($options["display_comment_count"]); ?>>
						<?php _e("Display Number of Comments",'wp_related_posts');?>
						</label><br />
						<label>
						<input name="wp_rp_display_publish_date" type="checkbox" id="wp_rp_display_publish_date" value="yes" <?php checked($options["display_publish_date"]); ?>>
						<?php _e("Display Publish Date",'wp_related_posts');?>
						</label><br />
						<label>
							<input name="wp_rp_display_excerpt" type="checkbox" id="wp_rp_display_excerpt" value="yes"<?php checked($options["display_excerpt"]); ?> onclick="wp_rp_display_excerpt_onclick();" >
							<?php _e("Display Post Excerpt",'wp_related_posts');?>
						</label>
						<label id="wp_rp_excerpt_max_length_label"<?php echo $options["display_excerpt"] ? '' : ' style="display: none;"'; ?>>
							<input name="wp_rp_excerpt_max_length" type="text" id="wp_rp_excerpt_max_length" class="small-text" value="<?php esc_attr_e($options["excerpt_max_length"]); ?>" /> <span class="description"><?php _e('Maximum Number of Characters.', 'wp_related_posts'); ?></span>
						</label><br/>
						<label for="wp_rp_related_posts_title_tag">
							<?php _e('Related Posts Title Tag', 'wp_related_posts'); ?>
							<select name="wp_rp_related_posts_title_tag" id="wp_rp_related_posts_title_tag" class="postform">
							<?php
							foreach ($title_tags as $tag) :
							?>
								<option value="<?php esc_attr_e($tag); ?>"<?php selected($related_posts_title_tag, $tag); ?>>&lt;<?php esc_html_e($tag); ?>&gt;</option>
							<?php endforeach; ?>
							</select>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e("Experimental:",'wp_related_posts'); ?></th>
					<td>
						<label>
						<input name="wp_rp_scroll_up_related_posts" type="checkbox" id="wp_rp_scroll_up_related_posts" value="yes" <?php checked($options["scroll_up_related_posts"]); ?>>
						<?php _e("Scroll Up Related Posts",'wp_related_posts');?>*
						</label><br />
					</td>
				</tr>
			</table>

			<h3><?php _e("If there are no related posts",'wp_related_posts');?></h3>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e("Display:",'wp_related_posts'); ?></th>
					<td>
						<select name="wp_rp_missing_rp_algorithm" id="wp_rp_missing_rp_algorithm" onchange="wp_rp_missing_rp_algorithm_onchange();"  class="postform">
							<option value="text"<?php selected($missing_rp_algorithm, 'text'); ?> ><?php _e("Text: 'No Related Posts'",'wp_related_posts'); ?></option>
							<option value="random"<?php selected($missing_rp_algorithm, 'random'); ?>><?php _e("Random Posts",'wp_related_posts'); ?></option>
							<option value="commented"<?php selected($missing_rp_algorithm, 'commented'); ?>><?php _e("Most Commented Posts",'wp_related_posts'); ?></option>
							<?php if(function_exists('akpc_most_popular')) : ?>
							<option value="popularity" <?php selected($missing_rp_algorithm, 'popularity'); ?>><?php _e("Most Popular Posts",'wp_related_posts'); ?></option>
							<?php endif; ?> 
						</select>
					</td>
				</tr>
				<tr valign="top" scope="row">
					<th id="wp_rp_missing_rp_title_th" scope="row">
					<?php 
					switch($missing_rp_algorithm) {
						case 'text':
							_e('No Related Posts Text:', 'wp_related_posts'); 
							break;
						case 'random':
							_e('Random Posts Title:', 'wp_related_posts'); 
							break;
						case 'commented':
							_e('Most Commented Posts Title:', 'wp_related_posts'); 
							break;
						case 'popularity':
							_e('Most Popular Posts Title:', 'wp_related_posts'); 
							break;
					}
					?>
					</th>
					<td>
						<input name="wp_rp_missing_rp_title" type="text" id="wp_rp_missing_rp_title" value="<?php esc_attr_e($options['missing_rp_title']); ?>" class="regular-text" />
					</td>
				</tr>
			</table>

			<h3><?php _e("Other Settings:",'wp_related_posts'); ?></h3>
			<table class="form-table">
				<tr valign="top">
					<td>
						<label>
							<input name="wp_rp_on_single_post" type="checkbox" id="wp_rp_on_single_post" value="yes" <?php checked($options['on_single_post']); ?>>
							<?php _e("Auto Insert Related Posts",'wp_related_posts');?>
						</label>
						(or add <pre style="display: inline">&lt;?php wp_related_posts()?&gt;</pre> to your single post template)
						<br />
						<label>
							<input name="wp_rp_on_rss" type="checkbox" id="wp_rp_on_rss" value="yes"<?php checked($options['on_rss']); ?>>
							<?php _e("Display Related Posts in Feed",'wp_related_posts');?>
						</label>
						<br />
						<label>
							<input name="wp_rp_include_promotionail_link" type="checkbox" id="wp_rp_include_promotionail_link" value="yes"<?php checked($options['include_promotionail_link']); ?> />
							<?php _e('Help Promote This Plugin', 'wp_related_posts'); ?>*
						</label>
						<br />
						<label>
							<input name="wp_rp_ctr_dashboard_enabled" type="checkbox" id="wp_rp_ctr_dashboard_enabled" value="yes" <?php checked($options['ctr_dashboard_enabled']); ?> />
							<?php _e("Turn statistics on",'wp_related_posts');?>*
						</label>
					</td>
				</tr>
			</table>
			<p class="submit"><input type="submit" value="<?php _e('Save changes', 'wp_related_posts'); ?>" class="button-primary" /></p>

		</form>
		<div>
			* Provided via third party service.
		</div>
	</div>
<?php }
