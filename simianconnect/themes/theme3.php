<?php
/**
 Theme 3
 **/
?>
<!-- THEME 3 IN USE -->
<div class="<?php echo $dom_id; ?>" id="reel">

<?php if($simian_options['reel_title'] != false): ?>
	<h2 class="reel_title">
	<?php echo $reel->reel_title; ?>
	</h2>
	<?php endif; ?>

	<?php if($simian_options['video_title'] != false): ?>

		<h3 class="current_video_title">
		<?php echo $frontVideo->media_title ?>
		</h3>

		<?php endif; ?>
		
	<?php $dim = parse_dimensions(array(get_option('simian_default_width'),get_option('simian_default_height')),array("width", "height"),array($frontVideo->media_width, $frontVideo->media_height), $atts); ?>

	<div class="current_video" style="width:<?php echo $dim['width'] ?>px;">



		<div class="current_video_player">

		<?php if($simian_options['poster'] != false): ?>
			<a href="<?php echo $simian_url . $frontVideo->media_url ?>"
				rel="qtposter" jscontroller="false"> <img
				src="<?php echo $simian_url . $frontVideo->media_thumb ?>"
				width="<?php echo $dim['width'] ?>"
				height="<?php echo $dim['height'] ?>" /> </a>

				<?php else: ?>

			<div id="<?php echo $dom_id . "_mov"; ?>">
				"
				<?php echo $dom_id . "_mov"; ?>
				"
			</div>

			<?php echo simian_inline_javascript($dom_id . "_mov", $simian_url . $frontVideo->media_url, $dim); ?>

			<?php endif; ?>

		</div>

	</div>

	<?php if($simian_options['show_playlist'] != false && count($playlist) >= 2 ): ?>

	<?php wp_enqueue_script('simian_size',plugin_dir_url(__FILE__).'../js/simian_size.js');
	$thumb_dim = parse_dimensions(array(get_option('simian_default_thumb_width'),get_option('simian_default_thumb_height')),array("thumb_width", "thumb_height"),array(129,96), $atts);
	$size_array = array();
	$firstSelect = true;
	?>

	<ul id="playlist">

	<?php foreach($playlist as $mediaitem): ?>

	<?php if($simian_options['thumb_titles'] != false): ?>

	<?php if($firstSelect): ?>

		<li class="simian_media_<?php echo $mediaitem->media_id; ?> selected hoverOver">

		<?php $firstSelect = false; ?> <?php else: ?>
		
		<li class="simian_media_<?php echo $mediaitem->media_id ?>"><?php endif; ?>

		<?php endif; ?> <?php $video_dim = parse_dimensions(array(get_option('simian_default_width'),get_option('simian_default_height')),array("width", "height"),array($mediaitem->media_width, $mediaitem->media_height), $atts);
		$size_array['simian_media_'.$mediaitem->media_id] = $video_dim;
		?>

			<div class="simian_thumb">

				<a href="<?php echo $simian_url . $mediaitem->media_url ?>"
					rel="<?php echo $dom_id ?>"> <img
					title="<?php echo $mediaitem->media_title ?>"
					src="<?php echo $simian_url. $mediaitem->media_thumb ?>"
					width="<?php echo $thumb_dim['width'] ?>"
					height="<?php echo $thumb_dim['height'] ?>" /> </a>

			</div>

			<?php /*<div class="simian_content">

				<h3 class="thumb_title">
				<?php echo $mediaitem->media_title ?>
				</h3>

			</div> */ ?>

			<div class="cf"></div>
		</li>

		<?php endforeach; ?>

	</ul>

	<?php wp_localize_script('simian_size', $dom_id . '_sizes', $size_array); ?>

	<?php endif; ?>

	<div class='cf'></div>

</div>
