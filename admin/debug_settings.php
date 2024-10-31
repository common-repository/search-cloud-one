<?php
if (! defined('ABSPATH')) exit;
?>
<div class="wrap sc1-admin">
	<p>Please do not modify these unless you know what you are doing.</p>
	<button id="btn-sc1-add" class="button">+</button><br>
	<table class="wp-list-table widefat fixed striped pages">
			<tbody id="tbl-sc1-debug-settings">
	<?php
	global $wpdb;
	$settings = $wpdb->get_results(
		"SELECT id, setting_key, setting_value
		FROM " . $wpdb->prefix . "sc1debugsettings
		ORDER BY setting_key ASC");
	if (count($settings) > 0)
	{
		foreach($settings as $setting)
		{
			echo '<tr data-setting-id="' . $setting->id . '">
			<td><input class="sc1-key" 	type="text" value="' . $setting->setting_key . '" disabled style="width:100%"></td>
			<td><input class="sc1-value" type="text" value="' . $setting->setting_value  . '" style="width:100%"></td>
			</tr>';
		}
	}
	?>
	</tbody>
	</table>
	<br>
	<button id="btn-sc1-save" class="button">Save</button>
	<br>
	<h3>Reset Plugin</h3>
	<p>All of your search pages will be lost. You will need to configure the plugin again.</p>
	<button id="btn-sc1-reset" class="button">Reset</button>
	<script type="application/javascript">
	jQuery('#btn-sc1-save').click(function()
	{
		var debug_settings = [];

		for (var i = 0; i < jQuery('#tbl-sc1-debug-settings').find('tr').length; i++)
		{
			var setting = new Object();
			setting.key = 	jQuery(jQuery('#tbl-sc1-debug-settings').find('tr')[i]).find('.sc1-key').val();
			setting.value = jQuery(jQuery('#tbl-sc1-debug-settings').find('tr')[i]).find('.sc1-value').val();
			var id = jQuery(jQuery('#tbl-sc1-debug-settings').find('tr')[i]).attr('data-setting-id');
			if (id != '-1')
			{
				setting.id = id;
			}
			if (setting.key != '' && setting.value != '')
			{
				debug_settings.push(setting);
			}
		}
		var params = new Object();
		params.debug_settings = debug_settings;
		console.log(debug_settings);

		jQuery.ajax({
			type: 'post',
			contentType: 'application/json; charset=utf-8',
			dataType: 'json',
			url: '<?php echo(rest_url("sc1_client/v1/options"));?>',
			data: JSON.stringify(params),
			success: function() {
				location.reload()
			},
			error: function() {
				alert('Something went wrong. Check your connection and try again.');
			}
		});
});

	jQuery('#btn-sc1-add').click(function()
	{
		var row = document.createElement('tr');
		row.setAttribute('data-setting-id','-1');
		var html = '<td><input class="sc1-key" 	type="text" style="width:100%"></td> <td><input class="sc1-value" type="text" style="width:100%"></td>';
		row.innerHTML = html;
		document.getElementById('tbl-sc1-debug-settings').appendChild(row);
});

	jQuery('#btn-sc1-reset').click(function()
	{
		var confirmed = confirm('Are you sure you want to reset the plugin?');
		if (confirmed)
		{
			var params = new Object();
			params.resetPlugin = true;
			jQuery.ajax({
				type: 'post',
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				url: '<?php echo(rest_url("sc1_client/v1/options"));?>',
				data: JSON.stringify(params),
				success: function() {
					location.reload()
				},
				error: function() {
					alert('Something went wrong. Check your connection and try again.');
				}
			});
		}
});
	</script>
</div>
