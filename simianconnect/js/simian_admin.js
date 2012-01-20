jQuery(document).ready(
		function($) {
			$('ul.reel_select a').live(
					'click',
					function() {
						var rid = jQuery(this).attr('id').replace("reel_id_",
								"");
						tinyMCE.execCommand('mceInsertContent', false,
								'[swebreel id=' + rid + ']', {
									format : 'raw'
								});
						tb_remove();
						return false;
					});

			$('ul.reel_select a').live('mouseenter', function() {
				var tempPos = $(this).position();
				$(this).parent('li').addClass('selected');
			});

			$('ul.reel_select a').live('mouseleave', function() {
				$(this).parent('li').removeClass('selected');
			});

			$('#simian_select_filter').live('focus', function() {
				if ($(this).val() == "Filter Reels") {
					$(this).val('');
				}
			});

			$('#simian_select_filter').live('blur', function() {
				if ($(this).val() == "") {
					$(this).val('Filter Reels');
				}
			});

			$('#simian_select_filter').live(
					'keyup',
					function() {
						var cond = $(this).val();
						var filterReg = new RegExp(cond, 'i');
						$('ul.reel_select li').each(
								function() {
									if ($(this).children('p.reel_title').html()
											.match(filterReg)) {
										$(this).show();
									} else {
										$(this).hide();
									}
								});
					});


			$('#simianCacheForm').submit(function() {
				cache_reel(1, $(this).find('#simianReelMax').first().val());
				return false;
			});

		});

function cache_reel_adhoc() {
	//TODO currently grabs arbitary number of reels to start someone off - needs to be smarter
	cache_reel(1, 64);
}

function cache_reel(reelid, maxid) {

	jQuery('#simianCacheStatus').html("Caching reel " + reelid + "/" + maxid);

	var data = {
		action : "simian_ajax_get_reel",
		reel_id : reelid
	};

	jQuery.ajax({
		type : "POST",
		url : ajaxurl,
		data : data,
		dataType : "json",
		success : function(response) {
			if (response.status) {
				if (response.status == "OK"
						&& jQuery('.reel_select').length > 0) {
					var newReel = '<li><a href="#" id="reel_id_'
							+ response.details.reel_id + '"><img src="'
							+ response.details.reel_thumb + '"></a><h4>'
							+ response.details.reel_id
							+ '</h4><p class="reel_title">'
							+ response.details.reel_name + '</p></li>';
					jQuery('.reel_select').append(newReel);
				}
				if (reelid + 1 <= maxid) {
					cache_reel(reelid + 1, maxid);
				} else {
					jQuery('#simianCacheStatus').html('Caching COMPLETE');
				}
			}
		}
	});

}