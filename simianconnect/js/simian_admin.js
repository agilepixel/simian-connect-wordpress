jQuery(document).ready(function($) {
	$('ul.reel_select a').live('click',
		function () {
			var rid = jQuery(this).attr('id').replace("reel_id_","");	
			tinyMCE.execCommand('mceInsertContent', false, '[swebreel id='+rid+']', {format : 'raw'});
			tb_remove();
			return false;
	});
	
	$('ul.reel_select a').live('mouseenter',
			function () {
				var tempPos = $(this).position();
				$(this).parent('li').addClass('selected');
				$('#simian_reel_hover').css({"left":tempPos.left,"top":tempPos.top});
				//$('#simian_reel_hover').show();
	});
	
	$('ul.reel_select a').live('mouseleave',
			function () {
				$(this).parent('li').removeClass('selected');
	});
	
	$('#simian_select_filter').live('focus',
			function () {
				if($(this).val() == "Filter Reels"){
					$(this).val('');
				}
	});
	
	$('#simian_select_filter').live('blur',
			function () {
				if($(this).val() == ""){
					$(this).val('Filter Reels');
				}
	});
	
	$('#simian_select_filter').live('keyup',
			function () {
				var cond = $(this).val();
				var filterReg = new RegExp(cond,'i');
				$('ul.reel_select li').each(function(){
					if($(this).children('p.reel_title').html().match(filterReg)){
						$(this).show();
					} else {
						$(this).hide();
					}
				});
	});
	
	
	$('#simianCacheForm').submit(function(){
		cache_reel(1,$(this).find('#simianReelMax').first().val());
		return false;
	});

	
});

function cache_reel(reelid,maxid){
	
		jQuery('#simianCacheStatus').html("Caching reel "+reelid+"/"+maxid);
	
		var data = {
				action: "simian_ajax_get_reel",
				reel_id: reelid
		};
		
		jQuery.ajax({
		  type: "POST",
		  url: ajaxurl,
		  data: data,
		  dataType: "json",
		  success: function(response) {
			if(response.status){
				if(reelid+1<=maxid){
					cache_reel(reelid+1,maxid);
				} else {
					jQuery('#simianCacheStatus').html('Caching COMPLETE');
				}
			}
		  }	  
		});

}