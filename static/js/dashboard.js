(function(a){var d=function(b,d){a.each(d,function(a,d){b=b.replace(RegExp("{{ *"+a+" *}}"),d)});return b};a(function(){var b=a("#wp_rp_statistics_wrap"),i=a("#wp_rp_dashboard_url").val(),g=a("#wp_rp_blog_id").val(),h=a("#wp_rp_auth_key").val();update_interval=req_timeout=null;update_interval_sec=2E3;update_interval_error_sec=3E4;updating=!1;ul=null;set_update_interval=function(a){a||(a=update_interval_sec);clearInterval(update_interval);0<a&&(update_interval=setInterval(update_dashboard,a))};display_error=
function(e){var f=a("#wp_rp_statistics_wrap");e||f.find(".unavailable").slideDown();set_update_interval(update_interval_error_sec);updating=!1};create_dashboard=function(){ul=a('<ul class="statistics" />');b.find(".unavailable").slideUp();ul.append('<li class="title"><div class="desktop">Desktop</div><div class="mobile">Mobile</div></li>');ul.append(d('<li description="{{description}}" class="{{class}} stats"><p class="num mobile"></p><p class="num all"></p><h5>{{ title}}<span>{{range}}</span></h5></li>',
{"class":"ctr",title:"click-through rate",description:"Number of clicks on a Related Post divided by the number of times the post was shown to readers. Tip: Using thumbnails will generally rise Click-through Rates.",range:"last 30 days"}));ul.append(d('<li description="{{description}}" class="{{class}} stats"><p class="num mobile"></p><p class="num all"></p><h5>{{ title}}<span>{{range}}</span></h5></li>',{"class":"pageviews",title:"page views",description:"Number of times the page (usually post) was loaded to readers.",
range:"last 30 days"}));ul.append(d('<li description="{{description}}" class="{{class}} stats"><p class="num mobile"></p><p class="num all"></p><h5>{{ title}}<span>{{range}}</span></h5></li>',{"class":"clicks",title:"clicks",description:"Number of times a reader has clicked on one of the Related Posts.",range:"last 30 days"}));b.append('<div class="description"/>');var e=b.find(".description");a("#wp_rp_settings_form").length&&(ul.on("mouseenter","li.stats",function(){var f=a(this),c=f.offset().top-
f.parent().offset().top;e.text(f.attr("description")).css("margin-top",c+"px").show()}),b.on("mouseleave",function(){e.text("").hide()}));b.append(ul)};update_dashboard=function(e){updating||(updating=!0,req_timeout=setTimeout(function(){display_error(!e)},2E3),a.getJSON(i+"pageviews/?callback=?",{blog_id:g,auth_key:h},function(a){var c=a.data;clearTimeout(req_timeout);if(!a||"ok"!==a.status||!a.data)display_error(!e);else{ul||create_dashboard();set_update_interval(a.data.update_interval);var b=c.pageviews-
c.mobile_pageviews,c=c.clicks-c.mobile_clicks,d=0<b?(100*(c/b)).toFixed(1):0;ul.find(".ctr .num.all").html(d+"%");ul.find(".pageviews .num.all").html(b);ul.find(".clicks .num.all").html(c);ul.find(".ctr .num.mobile").html(a.data.mobile_ctr.toFixed(1)+"%");ul.find(".pageviews .num.mobile").html(a.data.mobile_pageviews);ul.find(".clicks .num.mobile").html(a.data.mobile_clicks);updating=!1}}))};g&&h&&(update_dashboard(!0),update_interval=setInterval(update_dashboard,2E3));a("#wp_rp_turn_on_statistics a.turn-on").click(function(e){e.preventDefault();
var e=a("#wp_rp_static_base_url").val(),b=!1,c=function(){b||(a("#wp_rp_settings_form").submit(),b=!0)};a("#wp_rp_ctr_dashboard_enabled, #wp_rp_display_thumbnail, #wp_rp_enable_themes, #wp_rp_promoted_content_enabled").prop("checked",!0);a("#wp_rp_settings_form").append('<input type="hidden" value="statistics+thumbnails+promoted" name="wp_rp_turn_on_button_pressed" id="wp_rp_turn_on_button_pressed">');a("<img />").load(c).error(c).attr("src",e+"stats.gif?action=turn_on_button&ads=1&nc="+(new Date).getTime());
setTimeout(c,1E3)});a(".wp_rp_notification .close").on("click",function(b){a.ajax({url:a(this).attr("href"),data:{noredirect:!0}});a(this).parent().slideUp(function(){a(this).remove()});b.preventDefault()});a("#wp_rp_wrap .collapsible .collapse-handle").on("click",function(b){var f=a(this).closest(".collapsible"),c=f.find(".container"),d=f.hasClass("collapsed"),g=f.attr("block");d?(c.slideDown(),a.post(ajaxurl,{action:"rp_show_hide_"+g,show:!0})):(c.slideUp(),a.post(ajaxurl,{action:"rp_show_hide_"+
g,hide:!0}));f.toggleClass("collapsed");b.preventDefault()})})})(jQuery);
