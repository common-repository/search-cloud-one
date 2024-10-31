<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if (!get_transient('sc1-apiKey')) {
    wp_enqueue_script('jquery');
    include(plugin_dir_path(__FILE__)."setup.php");
    exit();
}
?>
<div class="sc1-admin wrap" style="max-width: 650px">
    <h1>Quick Start</h1>
    <p>Thanks for installing the Search Cloud One Wordpress Plugin!</p>
    <h2>Introduction</h2>
    <p>This plugin searches the contents of documents uploaded to your Search Cloud One account.</p>
    <h2>Step 1. Create one or more Categories</h2>
    <p>Categories contain one or more indexes to search documents from.<br>
        To setup search, you need at least one Category.</p>
    <a href="<?php echo admin_url('admin.php?page=SearchCloudOne/NewCategory.php'); ?>" target="_blank" class="button button-primary">Create Category</a>
    <br><br>
    <h2>Step 2. Create a [Shortcode]</h2>
    <a href="<?php echo admin_url('admin.php?page=SearchCloudOne/NewShortcode.php'); ?>" target="_blank" class="button button-primary">Create Shortcode</a>
    <br><br>
    <h2>Step 3. Add Search functionality to your site</h2>
    <p>Paste the generated <b>[Shortcode]</b> wherever you want the Search Bar to appear.<br>
    Place Shortcode(s) on Pages, Blog articles, Widgets - Wherever you'd like users to search for documents!</p>
    <div class="card">
        <h2 class="title">Learn more</h2>
        <p>Search Cloud One has many more advanced features such as date and field filters, a fully fledged API and more!</p>
        <a href="https://www.searchcloudone.com/redirects/wordpress_features" target="_blank" class="button">Learn More</a>
        <h3>Useful Links</h3>
        <p><a href="https://www.searchcloudone.com/console/" target="_blank">Search Cloud One Console</a> Use the console to manage documents and your account</p>
        <p><a href="https://www.searchcloudone.com/support/" target="_blank">Support</a> For help regarding Search Cloud One features</p>
    </div>
	<div class="card">
		<button id="sc1-btn-advanced" class="button" style="float:right; margin-top: 12px">View</button>
		<h2 class="title">Advanced</h2>
		<span id="sc1-advanced-settings" style="display:none">
		<?php
		include plugin_dir_path(__FILE__) . 'debug_settings.php';
		?>
		</span>
		<script>
		var visible = false;
		jQuery('#sc1-btn-advanced').click(function() {
			visible = !visible;
			if (visible)
			{
				jQuery('#sc1-advanced-settings').attr('style','display:block');
				jQuery(this).html('Hide');
			}
			else
			{
				jQuery('#sc1-advanced-settings').attr('style','display:none');
				jQuery(this).html('View');
			}
		});
		</script>
	</div>
</div>
