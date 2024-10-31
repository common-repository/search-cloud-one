<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap sc1-admin" style="max-width: 1000px">
    <h1 class="wp-heading-inline">New Shortcode</h1>
    <hr class="wp-header-end">
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">Shortcode</th>
                <td>
                    <?php
                        // Find the default next Search Page name value
                        $shortcode_prefix = "search-";
                        $shortcode_name = "spaghetti";
                        $num = 0;
                        $found_free_name = false;
                        global $wpdb;
                        while (! $found_free_name) {
                            $num = $num + 1;
                            $shortcode_name = $shortcode_prefix . $num;
                            $exists = $wpdb->get_var( $wpdb->prepare(
                                    "SELECT COUNT(*) FROM " . $wpdb->prefix . "sc1searchpages WHERE name= %s"
                                    , $shortcode_name
                            ));
                            if ($exists == 0) {
                                $found_free_name = true;
                            }
                        }
                    ?>
                    <input id="sc1-pagename" class="regular-text" type="text" value="<?php echo $shortcode_name; ?>"><br>
                    <p class="description">[sc1-search page="<span id='sc1-shortcode'></span>"]</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Categories</th>
                <td>
                    <select class="regular-text" id="categories-select" multiple>
                        <?php
                        // Retrieve the list of Categories, and display them as options here
                        global $wpdb;
                        $categories = $wpdb->get_results(
                            "
                            SELECT id, name
                            FROM " . $wpdb->prefix . "sc1categories
                            ORDER BY name ASC
                            "
                        );

                        foreach($categories as $category)
                        {
                            echo "<option value='$category->id'>$category->name</option>";
                        }
                        ?>
                    </select>
                    <p class="description">
                        Select one or more Categories.<br>
                    </p>

                </td>
                <td class="sc1-hideable-hint">
                    <div>
                        <img src="<?php echo plugin_dir_url(__FILE__) . '/img/category-dropdown.png';?>">
                        <p class="description">Select several Categories for a Category Menu.</p>
                        <p class="description">
                            Tip: Hold <code>CTRL</code> to select multiple Categories.
                        </p>
                    </div>
                </td>
            </tr>

        </tbody>
    </table>

    <button class="button button-primary" id="sc1-btn-save">Save Shortcode</button><br><br>
    <span id="sc1-formerrors" style="color:red"><!-- Any validation errors will be displayed here --></span>
    <script>

        jQuery('#sc1-pagename').on('change textInput input', function(){
            update_shortcode();
        });

        update_shortcode(); // Update the shortcode immediately on load.

        function update_shortcode() {
            var shortcode = jQuery('#sc1-pagename').val().trim().toLowerCase().replace(new RegExp('"', 'g'),"").replace(new RegExp("'", 'g'),"").replace(new RegExp(" ", 'g'), "-");
            jQuery('#sc1-shortcode').html(shortcode);
        }

        let catSelectOptions = jQuery('#categories-select option');
        if (catSelectOptions.length === 0) {
            alert("Can't create a Shortcode yet. Please make at least one Category first.");
            window.location = '<?php echo admin_url('admin.php?page=SearchCloudOne/NewCategory.php'); ?>';
        }
        if (catSelectOptions.length === 1) {
            // Automatically Select the Category
            catSelectOptions.prop('selected', true);
            jQuery('#categories-select').change();
        } else {
            // Trigger the change event just in case the browser cached a refresh.
            jQuery('#categories-select').change();
        }

        jQuery('#sc1-btn-save').click(function(){
            jQuery('#sc1-formerrors').html('');
            // Check searchpage name is present
            var name = jQuery('#sc1-shortcode').html().trim();
            if (name.length == 0)
            {
                jQuery('#sc1-formerrors').html('Please enter a page name');
                return;
            }
            // Check name is not too long
            if (name.length > 50)
            {
                jQuery('#sc1-formerrors').html('Page name too long (' + name.length + ')<br>'
                                              + 'Must be below 50 characters.');
                return;
            }
            if (!isAlphaNumeric(name))
            {
                jQuery('#sc1-formerrors').html('Shortcode name may not contain special characters. Only A-Z and 0-9 are allowed.');
                return;
            }

            var shortcode = jQuery('#sc1-shortcode').html().trim();
            // Check atleast one Category is selected
            var selected_categories = jQuery('#categories-select').val();
            if (selected_categories == null || selected_categories.length == 0)
            {
                jQuery('#sc1-formerrors').html('Please select at least one Category.');
                return;
            }
            // Client side validation complete, send data to endpoint
            var json = new Object();
            json.newSearchPage = new Object();
            json.newSearchPage.name  = name;
            json.newSearchPage.categoryids = selected_categories;
            json.newSearchPage.shortcode = shortcode
            json.newSearchPage.css = '';
            jQuery.ajax({
                type:"POST",
                url: '<?php echo(rest_url("sc1_client/v1/options"));?>',
                data: JSON.stringify(json),
                contentType: "application/json",
                timeout: 10000, // 10s
                success: function(data,textStatus, xhr) {
                    saving = true;
                    var url = '<?php echo admin_url('admin.php?page=SearchCloudOne/Shortcodes.php'); ?>';
                    window.location.href = url;
                }, error: function(data, textStatus, xhr) {
                    jQuery('#sc1-formerrors').html('Something went wrong. <br> Check connection and try again');
                }

            });
        });

        let saving = false;

        jQuery(window).bind('beforeunload', function(e) {
            if (!saving) {
                let confMessage = 'Leave page without saving?\n';
                (e || window.event).returnValue = confMessage; // Gecko + IE
                return confMessage; // Everything else
            }
        });

        function isAlphaNumeric(str) {
          var code, i, len;

          for (i = 0, len = str.length; i < len; i++) {
            code = str.charCodeAt(i);
            if (!(code === 45) && // dash -
                !(code > 47 && code < 58) && // numeric (0-9)
                !(code > 64 && code < 91) && // upper alpha (A-Z)
                !(code > 96 && code < 123)) { // lower alpha (a-z)
              return false;
            }
          }
          return true;
        };

    </script>

</div>
<?php ?>
