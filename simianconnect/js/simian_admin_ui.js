jQuery(document).ready(function($) {
	$('#simianReelMin').val(1000).attr('disabled','disabled');
	$('#simianReelMax').val(2000).attr('disabled','disabled');
	$(".simian-cache-range").slider({
		range: true,
		min: 0,
		max: 9999,
		values: [1000, 2000],
		slide: function(event, ui) {
			console.log(ui.values);
			$('#simianReelMin').val(ui.values[0]);
			$('#simianReelMax').val(ui.values[1]);
		}
	});
});