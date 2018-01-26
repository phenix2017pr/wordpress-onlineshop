<form action="<?php echo add_query_arg( array('step' => 'integrate') ); ?>" method="POST" id="mailchimp-access-token-form">
  <input type="hidden" name="access_token" value="" />
</form>

<div id="mailmunch-demo-video" onclick="hideVideo()">
</div>

<div id="poststuff" class="wrap">
  <div id="post-body" class="metabox-holder columns-2">
    <div id="post-body-content">
      <h2>
        MailChimp Forms
      </h2>

      <table class="wp-list-table widefat fixed posts integration-steps">
        <thead>
          <tr>
            <th class="active">
              <a href="<?php echo add_query_arg( array('step' => 'connect') ); ?>">Connect to MailChimp</a>
            </th>
            <th>Choose MailChimp List</th>
            <th>Create Opt-In Form</th>
          </tr>
        </thead>
        <tbody>
          <tr height="50">
            <td colspan="3" class="inside-container">
              <img id="loader" src="<?php echo plugins_url( 'img/loader.gif', dirname(__FILE__) ) ?>" />

              <a id="connect-mailchimp" href="<?php echo MAILCHIMP_MAILMUNCH_HOME_URL ?>/wordpress/mailchimp/new?token=<?php echo $this->mailmunch_api->getUserToken(); ?>">
                <img src="<?php echo plugins_url( 'img/mailchimp_logo.png', dirname(__FILE__) ) ?>" />
                <span class="button button-primary">Connect to MailChimp</span>
              </a>

              <div class="skip-link-container">
                <a id="skip-onboarding" href="<?php echo add_query_arg( array('step' => 'skip_onboarding') ); ?>">skip this and create a form</a>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div id="postbox-container-1" class="postbox-container">
      <div id="side-sortables" class="meta-box-sortables ui-sortable">
        <div class="postbox">
          <h3><span>Need Support?</span></h3>

          <div class="inside">
            <p>Need Help? <a href="https://mailmunch.zendesk.com/hc" target="_blank">Contact Support</a></p>

            <div class="video-trigger">
              <p>Watch our quick tour video:</p>
              <img src="<?php echo plugins_url( 'img/video.jpg', dirname(__FILE__) ) ?>" onclick="showVideo()" />
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>