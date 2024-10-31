<?php
if ( ! defined( 'ABSPATH' ) ) exit;
ob_start();
// Get the http status code from the response header of a file_get_contents request
function sc1_getHttpCode($http_response_header)
{
    if (is_array($http_response_header))
    {
        $parts=explode(' ',$http_response_header[0]);
        if(count($parts)>1) //HTTP/1.0 <code> <text>
            return intval($parts[1]); //Get code
    }
    return 0;
} ?>
<div class="wrap sc1-admin" style="max-width: 1000px">
    <h1 class="wp-heading-inline">New Category</h1>
    <hr class="wp-header-end">
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label for="sc1-cat-name">Category Name</label>
                </th>
                <td>
                    <input type="text" class="regular-text" id="sc1-cat-name" aria-describedby="catnamedescr">
                    <p id="catnamedescr" class="description">Enter a name for this Category.</p>
                </td>
                <td class="sc1-hideable-hint">
                    <div>
                    <img src="<?php echo plugin_dir_url(__FILE__) . '/img/category-dropdown.png';?>">
                    <p class="description">A Category Menu appears when several Categories are assigned to a Shortcode.</p>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sc1-indexes">Indexes</label>
                </th>
                <td>
                    <?php
                        // Get a list of Indexes this API Key can read, and display them to the user.
                        $apiKey = get_transient('sc1-apiKey');
                        $data = array(
                            'APIKey' => get_transient('sc1-apiKey'),
                            'Action' => 'ListIndexes'
                        );

                        $options = array(
                            'http' => array(
                                'header' => "Content-type: application/json\r\n",
                                'method' => 'POST',
                                'content' => json_encode($data),
                                'ignore_errors'=> true,
                            )
                        );
                        $context = stream_context_create($options);
                        $result = @file_get_contents('https://api.searchcloudone.com/IndexManager',false,$context);
                        $httpCode = sc1_getHttpCode($http_response_header);
                        $success = false;
                        $num_indexes = 0;
                        if ($httpCode >= 200 && $httpCode < 300)
                        {
                            $success = true;
                            // We got our list of indexes
                        }
                        if ($success == false)
                        {
                            ob_clean();
                            status_header(500);
                            echo 'Error - Failed to retrieve Indexes. Check Permissions and try again. ('. $httpCode .')';
                            exit();
                        }
                    ?>
                    <select id="sc1-indexes" class="regular-text" aria-describedby="indexesdescr" multiple>
                    <?php
                        // We got our list of indexes
                        $responseData = json_decode($result, true);

                        foreach($responseData['Indexes'] as $index)
                        {
                            $name = $index["Name"];
                            $id   = $index["IndexID"];
                            $uuid = $index["IndexUUID"];
                            $num_indexes++;
                            echo '<option value="' . $id . ':' . $uuid . '">' . $name . '</option>';
                        }
                    ?>
                    </select>
                    <p id="indexesdescr" class="description">
                        Select one or more Indexes to be included in this Category.
                    </p>
                </td>
                <td class="sc1-hideable-hint">
                    <div>
                        <p class="description">
                            Tip: Hold <code>CTRL</code> to select multiple indexes.
                        </p>
                        <p class="description">
                            Tip: Index Missing? Assign Read Permission on the API Tab in your <a target="_blank" href="https://www.searchcloudone.com/console?tab=api">Console.</a>
                        </p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <h2 class="wp-heading-inline">Filters</h2>
    <a id="linkEnableFilters" href="#">+ Filters...</a> <br>
    <table id="filters-table" class="form-table" style="display: none">
        <tbody>
            <tr>
                <th scope="row">
                    <label for="sc1-filters">Field filters</label><br>
                    <small><a href="#" id="linkRemoveFields">Remove</a></small>
                </th>
                <td>
                    <select id="sc1-filters" class="regular-text" aria-describedby="filtersdescr" multiple>
                        <?php
                        $unique_fieldnames = [];
                        foreach($responseData['Indexes'] as $index)
                        {
                            $id   = $index["IndexID"];
                            $uuid = $index["IndexUUID"];
                            if ($index["HasMetaSpec"] == true)
                            {
                                foreach($index['MetaSpecFields'] as $specField)
                                {
                                    // for every spec field in every index, write out an option for that specific field name.
                                    $fieldName = $specField['DisplayName'];
                                    $dataType = $specField['MetaType'];
                                    echo '<option value="'. $id . ':' . $uuid . ':' . $fieldName . ':' . $dataType . '" data-field-conf="'. htmlspecialchars(json_encode($specField)) .'">' . $fieldName . '</option>';

                                }
                            }
                        }

                        foreach($responseData['Indexes'] as $index)
                        {
                             // for every document field in every index, write out an option for that specific field name, if it's unique
                            $IndexUUIDs = array();
                            array_push($IndexUUIDs, $index['IndexUUID']);
                            $id   = $index["IndexID"];
                            $uuid = $index["IndexUUID"];

                            $apiKey = get_transient('sc1-apiKey');
                            $data = array(
                                'APIKey' =>     get_transient('sc1-apiKey'),
                                'Action' =>     'GetIndexedFields',
                                'IndexUUIDs' => $IndexUUIDs,
                            );


                            $options = array(
                                'http' => array(
                                    'header' => "Content-type: application/json\r\n",
                                    'method' => 'POST',
                                    'content' => json_encode($data),
                                    'ignore_errors'=> true,
                                )
                            );
                            $context = stream_context_create($options);
                            $result = file_get_contents('https://api.searchcloudone.com/IndexManager',false,$context);
                            $responseData = json_decode($result,true);
                            $httpCode = sc1_getHttpCode($http_response_header);
                            $success = false;
                            $num_indexes = 0;
                            if ($httpCode >= 200 && $httpCode < 300)
                            {
                                $success = true;
                                foreach($responseData['Fields'] as $fieldName)
                                {
                                    $dataType = 'String';
                                    echo '<option value="'. $id . ':' . $uuid . ':' . $fieldName . ':' . $dataType . '" data-field-conf="{}">' . $fieldName . '</option>';
                                }
                            }
                            if ($success == false)
                            {

                                ob_clean();
                                status_header(500);
                                echo 'Error - Failed to retrieve fields. Check Permissions and try again. ('. $httpCode .':'. $result.' )';
                                exit();
                            }
                        }
                        ?>
                    </select>
                    <script>
                        function arrayContains(needle, arrhaystack)
                        {
                            return (arrhaystack.indexOf(needle) > -1);
                        }

                        let facetsSelect = jQuery('#sc1-facets');
                        let filterSelect = jQuery('#sc1-filters');
                        jQuery(filterSelect).attr('data-orig-html',filterSelect.html());
                        let indexSelect = jQuery('#sc1-indexes');

                        filterSelect.html('<option value="null">No Indexes Selected</option>');
                        jQuery(filterSelect).prop('disabled', true);

                        jQuery(indexSelect).change(function() {
                                // Restore all options to the field filter select.
                                filterSelect.html(filterSelect.attr('data-orig-html'));
                                // Remove any options that don't belong to the selected indexes
                                let selectedIndexOptions = jQuery(indexSelect).val();
                                if (selectedIndexOptions == null || selectedIndexOptions.length === 0)
                                {
                                    selectedIndexOptions = [];
                                    filterSelect.html('<option value="null">No Indexes Selected</option>');
                                    jQuery(filterSelect).prop('disabled', true);
                                    facetsSelect.prop('disabled', true);
                                    facetsSelect.html('<option value="null">No Indexes Selected</option>');
                                }
                                else
                                {
                                    jQuery(filterSelect).prop('disabled', false);
                                    facetsSelect.prop('disabled', false);
                                }

                                for (let i = 0; i < jQuery(filterSelect).find('option').length; i++)
                                {
                                    let option = jQuery(filterSelect).find('option')[i];
                                    let keepOption = false;

                                    for (let ii = 0; ii < selectedIndexOptions.length; ii++)
                                    {
                                        let selectedIndex = selectedIndexOptions[ii];
                                        if (jQuery(option).attr('value').includes(selectedIndex)) keepOption = true;
                                    }

                                    if (!keepOption)
                                    {
                                        jQuery(option).remove();
                                        i--;
                                    }
                                }
                                // De-duplicate display names,
                                let names = [];
                                for (let i = 0; i < jQuery(filterSelect).find('option').length; i++)
                                {
                                    let option = jQuery(filterSelect).find('option')[i];
                                    let display_name = jQuery(filterSelect).find('option')[i].innerHTML.replace(/ /g, '').toLowerCase();
                                    if (arrayContains(display_name, names))
                                    {
                                        jQuery(option).remove();
                                        i--;
                                    }
                                    else
                                    {
                                        names.push(display_name);
                                    }

                                }

                                let option_count = jQuery(filterSelect).find('option').length;
                                if (option_count == null || option_count === 0)
                                {
                                    filterSelect.html('<option value="null" disabled>No Indexes with fields available are Selected</option>');
                                }

                        });


                    </script>
                    <span class="filtersdescr">
                        <p class="description">
                            Select Fields to appear in the Search Page Filters.
                        </p>
                        <p class="description">
                            Fields are automatically detected in your documents.
                            <br>
                            Add additional fields from your <a target="_blank" href="https://www.searchcloudone.com/console">Console.</a>
                        </p>

                    </span>
                </td>
                <td class="sc1-hideable-hint">
                    <div>
                        <img src="<?php echo plugin_dir_url(__FILE__) . '/img/filters-display.png';?>">
                        <p class="description">Example: Allow users to filter by Title Contains by selecting a field named Title</p>
                        <p class="description">
                            Tip: Hold <code>CTRL</code> to select multiple fields.
                        </p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <br>
    <a id="linkEnableFacets" href="#">+ Facets...</a>
    <br>
    <table id="facets-table" class="form-table" style="display: none">

        <tbody>
            <tr>
                <th scope="row">
                    <label>Facets</label><br>
                    <small><a href="#" id="linkRemoveFacets">Remove</a></small>
                </th>
                <td>
                    <div id="facets-loading-spinner" class="spinner"></div>
                    <div class="notice inline notice-info notice-alt" id="no-enumerable-fields-warning" style="display: none;">
                        <p class="description"><span class="dashicons dashicons-info"></span> No Enumerable Fields</p>
                        <p class="description">Configure <a target="_blank" href="https://searchcloudone.com/enumerable-fields/">Enumerable Fields</a> on the selected indexes to use Facet Searching.</p>
                    </div>
                    <select id="sc1-facets" class="regular-text" multiple></select>
                    <p class="description">
                        Select Fields to be used for Facet Searching.
                    </p>
                </td>
                <td class="sc1-hideable-hint">
                    <div>
                        <img src="<?php echo plugin_dir_url(__FILE__) . '/img/facets-list.png';?>">
                        <p class="description">Enable <a target="_blank" href="https://www.searchcloudone.com/redirects/facet-searching">Faceted Search</a> to search on common field values.</p>
                        <p class="description">
                            Tip: Hold <code>CTRL</code> to select multiple fields.
                        </p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <br>
    <button class="button button-primary" id="sc1-btn-save">Save Category</button><br>
    <span id="sc1-form-errors" style="color:red"><!-- Any validation errors will be displayed here --></span>
    <script>
    jQuery(document).ready(function( $ ) {

        let facetsLoadingSpinner = $('#facets-loading-spinner');
        let facetsSelect = $('#sc1-facets');
        let enumFieldsWarning = $('#no-enumerable-fields-warning');

        $('#sc1-indexes').change(function() {
            enumFieldsWarning.hide();
            facetsLoadingSpinner.addClass('is-active');
            facetsSelect.html('');
            facetsSelect.prop('disabled',true);
           // The Index(es) Selection has changed. For each Index Selected, Retrieve Enumerable Fields and display
           // them for facet search options.
           let select = $(this);
           let values = select.val();
           if (values == null) return;
           let indexUUIDS = [];
           for (let i = 0 ; i < values.length; i++) {
               // For each Index, get the enumerable fields (if any))
               let index = values[i].split(':');
               indexUUIDS.push(index[1]);
           }

           let data = {};
           data.APIKey  = '<?php echo get_transient('sc1-apiKey'); ?>';
           data.Action  = 'GetEnumerableFields';
           data.Indexes = indexUUIDS;

           $.ajax({
               url: "https://api.searchcloudone.com/Indexes",
               method: "POST",
               data: JSON.stringify(data),
               contentType: "application/json",
               timeout: 8000,
               success: function(data, textStatus, xhr){
                   facetsLoadingSpinner.removeClass('is-active');
                   console.info('Retrieved Enumerable Fields');
                   console.info(data);
                   for (let i = 0; i < data.Fields.length; i++) {
                       let field = data.Fields[i];
                       facetsSelect.append('<option value="'+ field +'">' + field + '</option>');
                   }
                   if (data.Fields.length > 0) {
                       facetsSelect.prop('disabled', false);

                   } else {
                       enumFieldsWarning.show();
                   }

               },
               error: function(data, textStatus, xhr) {
                   facetsLoadingSpinner.removeClass('is-active');
                   console.error('Error retrieving Enumerable Fields');
                   console.error(data);
                   facetsSelect.prop('disabled', true);
               }
           });

        });



        let idx_options = $('#sc1-indexes option');
        if (idx_options.length === 1) {
            // If there's only one Index, automatically select it, and enter in the name of the Index as
            // Category name.
            idx_options.prop('selected', true);
            let name = idx_options.html();
            $('#sc1-cat-name').val(name);
            $('#sc1-indexes').change();
        } else {
            // Sometimes, browsers like to remember what was previously selected, without
            // firing select events, causing confused users. Just trigger the silly event yes
            $('#sc1-indexes').change();
        }

        let filters_enabled = false;
        let facets_enabled = false;

        $('#linkEnableFilters').click(function(){
            $('#linkEnableFilters').hide();
            $('#filters-table').show();
            filters_enabled = true;
        });

        $('#linkRemoveFields').click(function() {
            $('#linkEnableFilters').show();
            $('#filters-table').hide();
            filters_enabled = false;
            $('#sc1-form-errors').html('');
        });

        $('#linkEnableFacets').click(function() {
            $('#linkEnableFacets').hide();
            $('#facets-table').show();
            facets_enabled = true;
        });

        $('#linkRemoveFacets').click(function() {
            $('#linkEnableFacets').show();
            $('#facets-table').hide();
            facets_enabled = false;
            $('#sc1-form-errors').html('');
        });

        $('#sc1-btn-save').click(function() {
            // Clear errors
            $('#sc1-form-errors').html('');
            // Check name is present
            let name = $('#sc1-cat-name').val().trim();
            if (name.length == 0)
            {
                jQuery('#sc1-form-errors').html('Please enter a Category name');
                return;
            }
            // Check name is not too long
            if (name.length > 50)
            {
                $('#sc1-form-errors').html('Category name too long (' + name.length + ' characters). <br>Must be below 50 characters.');
                return;
            }
            // Check atleast one or more indexes are selected
            let selected_indexes = jQuery('#sc1-indexes').val();
            if (selected_indexes == null || selected_indexes.length == 0)
            {
                $('#sc1-form-errors').html('Please select one or more indexes');
                return;
            }
            // POST the data to the options endpoint
            let json = {};
            json.createCategory = {};
            json.createCategory.name = name;
            json.createCategory.indexes = selected_indexes;
            json.createCategory.options = {};
            json.createCategory.options.filters = [];
            if (filters_enabled) {
                let filterSelect = jQuery('#sc1-filters');
                let filterSelectOptions = jQuery(filterSelect).val();
                if (filterSelectOptions != null) {
                    for (let f = 0; f < filterSelectOptions.length; f++) {
                        let value = filterSelectOptions[f].split(':');
                        let filter = {};
                        filter.name = value[2];
                        filter.datatype = value[3];
                        json.createCategory.options.filters.push(filter);
                    }
                }
                if (json.createCategory.options.filters.length === 0) {
                    jQuery('#sc1-form-errors').html('No Filter Fields Selected. Select one or more fields or remove filters');
                    return;
                }
            }

            json.createCategory.facets = [];
            if (facets_enabled) {
                let facetValues = facetsSelect.val();
                if (facetValues != null) {
                    for (let i = 0; i < facetValues.length; i++) {
                        json.createCategory.facets.push(facetValues[i]);
                    }
                }
                if (json.createCategory.facets.length === 0) {
                    jQuery('#sc1-form-errors').html('No Facet Fields Selected. Select one or more fields or remove facets.');
                    return;
                }
            }

            $.ajax({
                type: "POST",
                url: '<?php echo(rest_url("sc1_client/v1/options"));?>',
                data: JSON.stringify(json),
                contentType: "application/json",
                timeout: 10000, // 10 s
                success: function() {
                    saving = true;
                    window.location.href = '<?php echo admin_url('admin.php?page=SearchCloudOne/Categories.php'); ?>';
                }, error: function() {
                    jQuery('#sc1-form-errors').html('Something went wrong. <br> Check connection and try again');
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


    });
    </script>

</div>
<?php
ob_end_flush();
?>
