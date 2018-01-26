<h2>Hello <?php echo get_option('WooZoneLite_register_buyer');?>,</h2>
<p>
Thank you for buying and validating <a target="_blank" href="http://codecanyon.net/item/plugin/<?php echo get_option('WooZoneLite_register_item_id');?>"><?php echo get_option('WooZoneLite_register_item_name');?></a>. <br />

You will be receiving bugfixes / updates of the plugin at the following email adress: <strong><?php echo get_option('WooZoneLite_register_email');?></strong> <br />

Plugin Validated at: <strong><?php echo date('l jS \of F Y h:i:s A', get_option('WooZoneLite_register_timestamp'));?></strong>  <br />

Installed on domain: <strong><?php 
$_domain = parse_url(get_option('siteurl'));
echo $_domain['host'];?></strong><br />

Licence : <a target="_blank" href="http://codecanyon.net/licenses/regular_extended"><?php echo get_option('WooZoneLite_register_licence');?></a><br /><br />

EnyoY! <br /> AA-Team
</p>