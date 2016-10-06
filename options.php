<div class="wrap">
   <h2>Addressix OAuth2</h2>
   <form method="post" action="options.php">
   <?php 
   settings_fields('aix_oauth_settings');
do_settings_sections('addressixoauth');
submit_button();
?>
</form>
</div>