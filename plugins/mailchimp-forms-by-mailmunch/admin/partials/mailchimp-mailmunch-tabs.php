<?php $landingPagesEnabled = get_option(MAILCHIMP_MAILMUNCH_PREFIX. '_landing_pages_enabled'); ?>
<br />
<h2 class="nav-tab-wrapper">
  <a href="<?php echo admin_url('admin.php?page='.MAILCHIMP_MAILMUNCH_SLUG) ?>" class="nav-tab<?php if ($currentStep == 'forms') { echo " nav-tab-active"; } ?>">Forms & Popups</a>
  <?php if (empty($landingPagesEnabled) || $landingPagesEnabled == 'yes') { ?>
  <a href="<?php echo admin_url('edit.php?post_type='.MAILCHIMP_MAILMUNCH_POST_TYPE) ?>" class="nav-tab<?php if ($currentStep == 'landingpages') { echo " nav-tab-active"; } ?>">Landing Pages</a>
  <?php } ?>
  <a href="<?php echo admin_url('admin.php?page='.MAILCHIMP_MAILMUNCH_SLUG.'-autoresponders') ?>" class="nav-tab<?php if ($currentStep == 'autoresponders') { echo " nav-tab-active"; } ?>">Autoresponders</a>
</h2>
<?php if ($currentStep == 'landingpages') { ?><br /><?php } ?>
