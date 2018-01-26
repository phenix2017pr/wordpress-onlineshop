<?php
	$steps = $this->wizard_timeline_steps();
	$current_step = $this->step;

	$is_timeline = false;
	if ( isset($steps["$current_step"]) ) {
		if ( isset($steps["$current_step"]['icon']) && ! empty($steps["$current_step"]['icon']) ) {
			$is_timeline = true;
		}
	}
?>
			<!-- TimeLine -->
			<?php if ( $is_timeline ) { ?>
			<div class="wz-content" >
				<ul class="wz-timeline">
				<?php
					$html = array();
					$is_found_current = false;
					foreach ($steps as $step_key => $step_val) {
						$is_current = false;

						if ( ! isset($step_val['icon']) || empty($step_val['icon']) ) {
							continue 1;
						}
						$icon = $step_val['icon'];
						$title = isset($step_val['title']) && ! empty( $step_val['title'] ) ? $step_val['title'] : 'unknown';

						if ( $current_step == $step_key ) {
							$is_current = true;
							$is_found_current = true;
						}

						$css_class = '';
						if ( $is_current ) {
							$css_class = 'wz-current';
						}
						else if ( ! $is_found_current ) {
							$css_class = 'wz-completed';
						}

						$html_step = '<li class="' . $css_class . '">{icon}{title}</li>';
						$html_step = str_replace('{icon}', '<i class="icon ' . $icon . '"> </i>', $html_step);
						$html_step = str_replace('{title}', ($is_current ? '<span>' . $title . '</span>' : ''), $html_step);

						$html[] = $html_step;
					} // end foreach
					$html = implode(PHP_EOL, $html);
					echo $html;
				?>
				</ul>				
			</div>
			<?php } ?>