<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap sc1-admin">
    <h1 class="wp-heading-inline">Categories</h1>
    <a href="<?php echo admin_url('admin.php?page=SearchCloudOne/NewCategory.php'); ?>" class="page-title-action">Add New</a>
    <hr class="wp-header-end">
    <p>Categories contain one or more indexes to search documents from.<br>
        To setup search, you need at least one Category.</p>
    <table class="wp-list-table widefat fixed striped pages">
        <thead>
            <tr>
                <th class="manage-column column-primary column-name" scope="col">
                    Categories
                </th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Retrieve the user's Categories, and print them out as table rows
            global $wpdb;
            $categories = $wpdb->get_results(
                "
                SELECT id, name
                FROM " . $wpdb->prefix . "sc1categories
                ORDER BY name ASC
                "
            );
            // If the number of Categories is greater than 0, preemptively download the list of index names from searchcloudone.
            $sc1Response;
            if (count($categories) > 0)
            {
                $apiKey = get_transient('sc1-apiKey');
                $data = array("APIKey" => $apiKey, "Action" => "ListIndexes");
                $options = array(
                    'http' => array(
                        'header' => "Content-type: application/json\r\n",
                        'method' => 'POST',
                        'content' => json_encode($data)
                    )
                );
                $context = stream_context_create($options);
                $sc1Response = @file_get_contents('https://api.searchcloudone.com/IndexManager', false, $context);
                $httpCode = sc1_getHttpCode($http_response_header);
                if ($httpCode >= 200 && $httpCode < 300)
                {
                    $sc1Response = json_decode($sc1Response);

                }
                else
                {
                    echo '<tr><th>Something went wrong. Refresh the page to try again.</th></tr>';
                }
            }
            else
            {
                ?>
                <tr>
                    <th>
                        No Categories yet. <a href="<?php echo admin_url('admin.php?page=SearchCloudOne/NewCategory.php'); ?>">Add New Category</a>
                    </th>
                </tr> <?php
            }
            // Now list out the Categories for the user.
            foreach ($categories as $category) {
                echo '<tr id="' . $category->id . '"><th><div><span class="catname">' . $category->name . '</span><button class="delete-button button" style="float:right">Delete</button></div><div>Indexes: ';
                $names = array();
                $indexes = $wpdb->get_results(
                    "
                    SELECT indexuuid
                    FROM " . $wpdb->prefix . "sc1categoryindexes
                    WHERE categoryid = " . $category->id
                );
                foreach($indexes as $index)
                {
                    $indexuuid = $index->indexuuid;
                    $found = false;
                    foreach($sc1Response->Indexes as $index)
                    {
                        if ($index->IndexUUID == $indexuuid)
                        {
                            array_push($names, $index->Name);
                            $found = true;
                            continue;
                        }

                    }
                    // Didn't find the index name..?
                    if ($found == false)
                    {
                        array_push($names, "Couldn't find name for Index " . $indexuuid);
                    }

                }

                echo implode(', ', $names);
                echo '</div></th>';
            }
            ?>
        </tbody>
    </table>
    <?php
    if (count($categories > 0)) {
        ?>
        <hr class="wp-header-end">
        <h1 class="wp-heading-inline">Next Steps…</h1>
        <hr class="wp-header-end">
        <p><a href="<?php echo admin_url('admin.php?page=SearchCloudOne/NewShortcode.php'); ?>">Create a [Shortcode]</a> to add search to your site.</p>
        <?php
    }
    ?>
    <script>
        var deleteButtons = jQuery('.delete-button');
        deleteButtons.each(function(index){
           jQuery(this).click(function(){
               // Prompt user to confirm they want to delete
               if (window.confirm('Are you sure you wish to delete ' + jQuery(this).closest('th').find('.catname').html() + '?'))
               {
                   var categoryid = jQuery(this).closest('tr').attr('id');
                   var json = new Object();
                   json.deleteCategory = new Object();
                   json.deleteCategory.categoryid = categoryid;
                   jQuery.ajax({
                       url: '<?php echo(rest_url("sc1_client/v1/options"));?>',
                       type: "POST",
                       data: JSON.stringify(json),
                       contentType: "application/json",
                       timeout: 10000,
                       success: function(data, textStatus, xhr) {
                           // Refresh the page
                           window.location.reload(true);
                       }, error: function(data, textStatus, xhr) {
                           alert('Something went wrong. Check connection and try again');
                       }
                   });

               }
           });
        });
    </script>
</div>
<?php
function sc1_getHttpCode($http_response_header)
{
    if(is_array($http_response_header))
    {
        $parts=explode(' ',$http_response_header[0]);
        if(count($parts)>1) //HTTP/1.0 <code> <text>
            return intval($parts[1]); //Get code
    }
    return 0;
}

?>
