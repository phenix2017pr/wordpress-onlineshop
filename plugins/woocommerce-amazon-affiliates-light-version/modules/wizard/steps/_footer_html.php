<?php
$current_step = $this->step;
?>
			<div class="wz-log-status"></div>

			<?php require( $this->module_folder_path . 'steps/_nextprev.php' ); ?>

			</form>

	 		<?php
	 			// only for NON index step
	 			if ( ! in_array($current_step, array('index', 'finished')) ) {
	 		?>

			<div class="wz-return-to-dashboard">
				<a href="<?php echo $this->plugin_dashboard_url; ?>"><?php esc_html_e('Return to the WooZoneLite Dashboard', $this->localizationName); ?></a>
			</div>

	 		<?php
	 			} // end index step
	 		?>

		 </div><!-- end col-lg-12 -->
	</div><!-- end row -->

</div>