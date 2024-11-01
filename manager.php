<?php
$settings_url = '//cms.vicomi.com?token='.get_option('vicomi_feelbacks_api_key');
?>

<div class="wrap">
	<h2>Vicomi Dashbord</h2>
    <iframe src="<?php echo $settings_url ?>" style="width: 100%; height: 80%; min-height: 600px;"></iframe>
</div>


