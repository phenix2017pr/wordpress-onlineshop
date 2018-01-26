<form method="POST" id="mailchimp-settings">
<?php
  $autoEmbed = $this->mailmunch_api->getSetting('auto_embed');
  $landingPagesEnabled = $this->mailmunch_api->getSetting('landing_pages_enabled');
?>
<div id="poststuff" class="wrap">
  <div id="post-body" class="metabox-holder columns-2">
    <div id="post-body-content">
      <div style="margin-bottom: 10px;">
        <h1 class="wp-heading-inline">
          Settings | <?php echo $this->plugin_name; ?>
        </h1>
      </div>

      <table class="wp-list-table widefat fixed posts settings-table">
        <tbody>
          <tr>
            <td class="inside-container" width="30%">
              <h3>Auto Embedding</h3>
              <p>If enabled, it will add blank div tags in your posts and pages so you can embed forms more easily.</p>
            </td>
            <td class="setting">
              <select name="auto_embed">
                <option value="yes"<?php if ($autoEmbed == 'yes' || empty($autoEmbed)) echo "selected=\"selected\""; ?>>Yes</option>
                <option value="no"<?php if ($autoEmbed == 'no') echo "selected=\"selected\""; ?>>No</option>
              </select>
            </td>
          </tr>
          <tr>
            <td class="inside-container" width="30%">
              <h3>Enable Landing Pages</h3>
              <p>If enabled, you will be able to use MailMunch landing pages and create pages from within your Wordpress admin.</p>
            </td>
            <td class="setting">
              <select name="landing_pages_enabled">
                <option value="yes"<?php if ($landingPagesEnabled == 'yes' || empty($landingPagesEnabled)) echo "selected=\"selected\""; ?>>Yes</option>
                <option value="no"<?php if ($landingPagesEnabled == 'no') echo "selected=\"selected\""; ?>>No</option>
              </select>
            </td>
          </tr>          
          <tr>
            <td colspan="2">
              <input type="submit" name="Save" value="Save Settings" class="button button-primary" />
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
</form>
