<?php
if ( ! defined( 'ABSPATH' ) ) exit;
wp_enqueue_script('jquery');

if (!get_transient('sc1-apiKey')) {
    // The user has not yet configured the API Key that this plugin will use.
    // The admin page should prompt them to set up their API Key.
    ?>
        <script>
            function sc1_submit_apiKey(btn)
            {
                jQuery('#sc1_apiErrors').html(" ");
                var apiKey = jQuery('#sc1-input-apiKey')[0].value.trim();
                // very basic validation
                if (apiKey.length != 36)
                {
                    jQuery('#sc1_apiErrors').html("Invalid API Key");
                    return;
                }
                var json = new Object();
                json.apiKey = apiKey;
                jQuery.ajax({
                    url: '<?php echo(rest_url("sc1_client/v1/options"));?>',
                    type: "POST",
                    data: JSON.stringify(json),
                    contentType: "application/json",
                    dataType: 'json',
                    timeout: 10000, // 10 seconds
                    success: function(data, textStatus, xhr) {
                        //console.log(data + " :)");
                        window.location.reload(true);
                    }, error: function(data, textStatus, xhr) {
                        console.log(data);
                        jQuery('#sc1_apiErrors').html('Error: ' + data.responseJSON.message + " <br>Please try again.");

                    }
                });
            }
        </script>
        <div class="wrap">
            <h1>Connect to your Search Cloud One account</h1>
            <p>Please go to the API Page on your
                <a target="_blank" href="https://www.searchcloudone.com/console">Search Cloud One Console</a> and click <i>Wordpress Setup</i> to get your API Key.</p>
            <p>Paste the key below to continue</p>
            <input type="text" class="regular-text" id="sc1-input-apiKey">
            <button class="button button-primary" onclick="sc1_submit_apiKey(this)">Submit</button>
            <p id="sc1_apiErrors" style="color:red"><!-- Error Messages go here --></p>
        </div>
    <?php
} else {
    // The user has already configured their API Key
    ?>
    <script src="<?php echo plugin_dir_url(__FILE__) . '/js/clipboard.min.js'; ?>"></script>
        <div class="wrap sc1-admin" style="max-width: 1280px">
            <div class="sc1-2col">
                <div>
                    <h1 class="wp-heading-inline">Shortcodes</h1>
                    <a href="<?php echo admin_url('admin.php?page=SearchCloudOne/NewShortcode.php'); ?>" class="page-title-action">Add New</a>
                    <!-- The following intro text will be hidden when the user already has one shortcode -->
                    <hr class="wp-header-end">
                    <p id="sc1-starter-msg">Add Search functionality anywhere on your site by adding a <b>[Shortcode]</b> into the desired location </p>
                    <table class="wp-list-table widefat fixed striped pages" style="max-width: 600px">
                        <thead>
                            <tr>
                                <th class="manage-column column-primary column-name" scope="col">
                                    Shortcodes
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Retrieve the user's Shortcodes, and print them out as table rows
                            global $wpdb;
                            $searchpages = $wpdb->get_results(
                                "SELECT id, name, shortcode
                                FROM " . $wpdb->prefix . "sc1searchpages
                                ORDER BY name ASC
                                "
                            );
                            if (count($searchpages) > 0)
                            {
                                foreach($searchpages as $searchpage)
                                {
                                    ?>
                                    <tr class="shortcode-row" id="<?php echo $searchpage->id; ?>" data-shortcode-name="<?php echo $searchpage->name; ?>">
                                        <th>
                                            <div>
                                                <input id="input<?php echo $searchpage->id; ?>" type="text" value="[sc1-search page='<?php echo $searchpage->shortcode; ?>']" style="min-height: 28px; min-width: 250px; font-weight: 600" readonly>
                                                <button class="button sc1-copy" data-clipboard-target="#input<?php echo $searchpage->id; ?>">Copy</button>
                                            </div>
                                            <div style="float:right"><button class="button sc1-delete">Delete</button></div>
                                    <?php
                                    $searchpageid = $searchpage->id;
                                    $categoryids = $wpdb->get_col(
                                        "
                                        SELECT categoryid FROM " . $wpdb->prefix . "sc1searchpagecategories
                                        WHERE searchpageid = " . $searchpageid
                                    );
                                    $names = array();
                                    foreach($categoryids as $categoryid)
                                    {
                                        $name = $wpdb->get_var('SELECT name FROM ' . $wpdb->prefix . 'sc1categories WHERE id=' . $categoryid);
                                        array_push($names,$name);
                                    }
                                    echo '<div>Categories: ' . implode(', ', $names) . '</div>';

                                    echo '</th></tr>';
                                }
                                ?>
                                            <script>
                                                jQuery(document).ready(function($) {
                                                    let clipJS = new ClipboardJS('.sc1-copy');
                                                    clipJS.on('success', function(e) {
                                                       let btn = $(e.trigger);
                                                       btn.html('Copied!');
                                                       setTimeout(function() {
                                                           btn.html('Copy');
                                                       }, 3000);
                                                    });
                                                    clipJS.on('error', function(e) {
                                                       let btn = $(e.trigger);
                                                       btn.html('Error: Browser does not support - Copy Manually');
                                                       setTimeout(function() {
                                                           btn.html('Copy');
                                                       }, 10000);
                                                    })
                                                });

                                            </script>
                                            <?php
                            }
                            else
                            {
                                echo '<tr><th>No Shortcodes yet. <a href="' . admin_url("admin.php?page=SearchCloudOne/NewShortcode.php") . '" >Add New Shortcode</a></th></tr>';
                            }

                            ?>
                        </tbody>
                    </table>


                    <?php
                    if (count($searchpages) > 0) {
                        ?>
                        <hr class="wp-header-end">
                        <h1 class="wp-heading-inline">Last Step…</h1>
                        <hr class="wp-header-end">
                        <p>Paste a [Shortcode] onto a <a href="edit.php">Page</a>, <a href="edit.php?post_type=page">Post</a> or <a href="widgets.php">Text Widget</a> to add search to your site.</p>
                        <?php
                    }
                    ?>
                </div>
                <div class="sc1-aside-2col" style="min-width: 250px">
                    <div class="sc1-hideable-hint" style="padding: 5px">
                        <div>
                            <img src="<?php echo plugin_dir_url(__FILE__) . '/img/pasting-shortcode.png';?>">
                            <p class="description">Pasting a [Shortcode] onto a page in the editor.</p>
                        </div>
                    </div>
                    <div class="sc1-hideable-hint" style="padding: 5px">
                        <div>
                            <img src="<?php echo plugin_dir_url(__FILE__) . '/img/rendered-page.png';?>">
                            <p class="description">The Result</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
        jQuery('.sc1-delete').click(function() {
            if (window.confirm('Are you sure you wish to delete ' +
                              jQuery(this).closest('tr').attr('data-shortcode-name') + '?'
                              ))
            {
                var pageid = jQuery(this).closest('tr').attr('id');
                var json = new Object();
                json.deletePage = new Object();
                json.deletePage.pageid = pageid;
                jQuery.ajax({
                    url: '<?php echo(rest_url("sc1_client/v1/options"));?>',
                    type: 'POST',
                    data: JSON.stringify(json),
                    contentType: 'application/json',
                    timeout: 10000,
                    success: function(data, textStatus, xhr) {
                        window.location.reload(true);
                    }, error: function(data, textStatus, xhr) {
                        alert('Something went wrong. Check connection and try again');
                    }
                });
            }
        });
        </script>

    <?php
}
?>
