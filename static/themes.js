(function ($) {
	$(function () {
		var wrap = $('#wp_rp_theme_options_wrap'),
			json_url = $('#wp_rp_json_url').val(),
			plugin_version = $('#wp_rp_version').val(),
			current_theme = $('#wp_rp_theme_selected').val(),
			themes = null,
			update_themes = function () {
				wrap.empty();
				if ($('#wp_rp_theme_title').length === 0) {
					wrap.append('<th scope="row">Select Theme:</th>');
				}

				var td = $('<td />');
				$.each(themes, function (i, theme) {
					var selected = theme.location === current_theme ? 'checked="checked"' : '';

					td.append('<label><input ' + selected + ' type="radio" name="wp_rp_theme" value="' + theme.location + '" /> ' + theme.name  + '</label><br />');
				});
				wrap.append(td);
			},
			append_get_themes_script = function () {
				var script = document.createElement('script'),
					body = document.getElementsByTagName("body").item(0);
				script.type = 'text/javascript';
				script.src = json_url + 'themes.js?plv=' + plugin_version;

				body.appendChild(script);
			};

		window.wp_rp_themes_cb = function (data) {
			if (data && data.themes) {
				themes = data.themes;

				if(themes) {
					update_themes();
				}
			}
		};

		append_get_themes_script();

		if (!window.localStorage || !window.localStorage.wp_wp_survey_1) {
			$('#wp-rp-survey').show();
		}

		if (!window.localStorage || !window.localStorage.wp_rp_thumbnails_info) {
			$('#wp-rp-thumbnails-info').show();
		}

		$('#wp-rp-survey .close, #wp-rp-survey .link').click(function () {
			$('#wp-rp-survey').fadeOut();
			if (window.localStorage) {
				window.localStorage.wp_wp_survey_1 = "close";
			}
		});

		$('#wp-rp-thumbnails-info .close').click(function () {
			$('#wp-rp-thumbnails-info').fadeOut();
			if (window.localStorage) {
				window.localStorage.wp_rp_thumbnails_info = "close";
			}
		});
	});
}(jQuery));
