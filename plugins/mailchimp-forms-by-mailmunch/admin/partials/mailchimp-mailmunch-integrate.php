<div id="mailmunch-demo-video" onclick="hideVideo()">
</div>

<div id="poststuff" class="wrap">
  <div id="post-body" class="metabox-holder columns-2">
    <div id="post-body-content">
      <h2>
        MailChimp Forms
      </h2>

      <table class="wp-list-table widefat fixed posts integration-steps integrate-step">
        <thead>
          <tr>
            <th>
              <a href="<?php echo add_query_arg( array('step' => 'connect') ); ?>">
                <img src="<?php echo plugins_url( 'img/smallcheck.png', dirname(__FILE__) ) ?>" />
                Connect to MailChimp
              </a>
            </th>
            <th class="active">
              <a href="<?php echo add_query_arg( array('step' => 'integrate') ); ?>">Choose MailChimp List</a>
            </th>
            <th>Create Opt-In Form</th>
          </tr>
        </thead>
        <tbody>
          <tr height="50">
            <td colspan="3">
              <div class="inside-container">
                <?php if (count($lists) > 0) { ?>
                <p>Choose a list to save your subscribers in:</p>
                <form action="<?php echo add_query_arg( array('step' => 'final') ); ?>" method="POST">
                  <select name="list_id">
                <?php foreach ($lists as $list) { ?>
                    <option value="<?php echo $list['id']; ?>"><?php echo $list['name']; ?> (<?php echo $list['stats']['member_count'] ?> members)</option>
                <?php } ?>
                  </select>
                  <input type="submit" name="action" value="Choose List" class="button button-primary" />
                </form>
                <?php } else { ?>
                <img src="<?php echo plugins_url( 'img/warning.png', dirname(__FILE__) ) ?>" />
                <div class="warning">You do not have a list on MailChimp. Please create one and refresh this page.</div>
                <?php } ?>

                <div class="skip-link-container">
                  <a id="skip-onboarding" href="<?php echo add_query_arg( array('step' => 'skip_onboarding') ); ?>">skip this and create a form</a>
                </div>
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
