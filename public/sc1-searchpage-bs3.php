<?php /** @noinspection SqlNoDataSourceInspection */
if ( ! defined( 'ABSPATH' ) ) exit;
    /*
        Steps:
        1. Validation:
            a. Check page has been passed
        2. Page Generation
            a. Retrieve the Categories that this page should show along w/ Category params for each Category.
            b. If there are filters active in the categories, set the display of those up.
    */
    global $wpdb;
    // Validate that a valid nonce for viewing this page was passed to prevent the php file being accessed directly.


    if (isset($_GET["page"]) && !empty($_GET["page"])){
        // Page is set correctly. We now proceed to fetch what Categories should be shown on this page
        $pagename = $_GET["page"];


        $pageid = $wpdb->get_var($wpdb->prepare(
            "SELECT id
            FROM " . $wpdb->prefix . "sc1searchpages
            WHERE shortcode= %s",
            $pagename
        ));
        $categoryids = $wpdb->get_col( $wpdb->prepare(
            "
            SELECT categoryid FROM " . $wpdb->prefix . "sc1searchpagecategories
            WHERE searchpageid = %s"
            , $pageid
        ));
        $categorynames = array();
        $categoryopts = array();
        $categoryfacets = array();
        foreach($categoryids as $categoryid)
        {
            $category = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'sc1categories WHERE id= %d',$categoryid));
            $categoryname = $category->name;
            $options = $category->opts;
            array_push($categorynames,$categoryname);
            array_push($categoryopts,$options);
            $facets = $wpdb->get_col($wpdb->prepare("SELECT field FROM " . $wpdb->prefix . "sc1categoryfacets WHERE category= %d",$categoryid));
            array_push($categoryfacets,$facets);
        }

        array_multisort($categorynames,$categoryopts,$categoryfacets,$categoryids);


    } else
    {
        // Page not set.
        ?>
            <!DOCTYPE html>
            <html>
            <head>
            <title> Misconfiguration </title>
            </head>
            <body><h1>This page is not configured correctly. Contact your site administrator</h1></body>
            </html>
        <?php
        exit();
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Page</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo plugins_url('css/search.css', __FILE__); ?>">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/css/bootstrap-select.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/bootstrap-select.min.js"></script>

    <script type='text/javascript' src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.7.0/js/bootstrap-datepicker.min.js"></script>
    <script type='text/javascript'>
    $(function(){
        $('.input-daterange').datepicker({
            autoclose: true
        });
    });
    </script>
</head>
<body>
    <template id="cb_facet">
    <div class="checkbox">
        <label style="width: 100%">
            <input type="checkbox" value="">
            <span class="cr"><i class="cr-icon glyphicon glyphicon-ok"></i></span>
            <span class="field-value"></span>
        </label>
    </div>

    </template>
    <div id="search-options-container">
        <!-- Simple Searchbox -->
        <div class="input-group">
        <?php if (count($categoryids) > 1){ ?>
            <div class="input-group-btn">
                <select class="index-selector selectpicker" name='index_name' id='categoryselect' data-width="150px">
                <?php for($i=0; $i < count($categoryids); $i++){ ?>
                    <option value="<?php echo $categoryids[$i]; ?>">
                    <?php echo $categorynames[$i]; ?>
                    </option>
                <?php } ?>
                </select>
            </div>
        <?php } ?>
          <input type="text" class="form-control" aria-label="Search Box" placeholder="Search" id="sc1-searchbox" data-categoryid="<?php echo $categoryids[0]; ?>">
          <span class="input-group-btn">
            <!-- Buttons -->
              <button class="btn btn-default" type="button" id="btnFilter" data-toggle="collapse" data-target="#filter-panel">
                  <span class="glyphicon glyphicon-filter" aria-hidden="true" aria-label="Filter"></span><span class="badge" id="filterCountBadge" style="display: none"></span>
              </button>
              <button class="btn btn-default" type="button" id="btnSearch" ><span class="glyphicon glyphicon-search" aria-hidden="true" aria-label="Search"> </span></button>
          </span>
        </div>
        <span class="collapse" id="sc1-filterbtn-hide"></span>
        <br>
        <div>
            <label class="checkbox-inline"><input id="check-box-stemming" type="checkbox" checked>Stemming</label>
            <label class="checkbox-inline"><input id="check-box-synonyms" type="checkbox">Synonyms</label>
        </div>

        <br>
        <!-- Advanced Controls -->
        <div id="filter-panel" class="collapse filter-panel well">
            <!-- Begin: Prinout filters -->
            <?php
            for($i = 0; $i < count($categoryids); $i++)
            {
                ?>
            <span class="filter-category" data-category-id="<?php echo $categoryids[$i]; ?>">
                <?php
                // For each filter in the category options, print out the relevant control.
                $options = json_decode($categoryopts[$i]);
                $filters = $options->filters;
                foreach($filters as $filter)
                {
                    $filtername = $filter->name;
                    $datatype = $filter->datatype;
                    // Echo out the title of the filter
                ?>
                <span class="sc1-filter-item" data-filter-name="<?php echo $filtername; ?>" data-filter-datatype="<?php echo $datatype; ?>">
                    <?php
                        $verb = "";
                        switch($datatype)
                        {
                            case "DateTime":
                            case "Date":
                                $verb = "Between";
                                break;
                            default:
                                $verb = "Contains";
                                break;
                        }

                    ?>
                    <h4><?php echo $filtername . ' ' . $verb; ?></h4>
                    <div class="input-group">
                        <div class="input-group-addon">
                            <input type="checkbox" class="filter-checkbox" aria-label="Check Box <?php echo $filtername;?>">
                        </div>
                <?php
                    // Depending on the data type, append a control element for the filter.

                    switch ($datatype) {
                        case "DateTime":
                        case "Date":
                        {
                            // TODO - Add separate 'DateTime' Control.
                            ?>
                            <span class="input-daterange" id="datepicker" data-date-end-date="0d" data-date-format="d M yyyy" data-date-today-highlight=true date-date-today-btn="linked" data-date-immediate-updates=true data-date-assume-nearby-year=true>
                                <span class="input-group" style="border-radius: 0px; width: 100%">
                                    <input type="text" class="form-control sc1-date-control sc1-date-from" name="from" placeholder="From"
                                           style="border-radius: 0px" data-date-end-date="0d" data-date-format="dd/mm/yyyy" data-date-today-highlight=true date-date-today-btn=true data-date-immediate-updates=true data-date-assume-nearby-year=true/>
                                    <i class="glyphicon glyphicon-calendar form-control-feedback"></i>
                                </span>
                                <span class="input-group" style="border-radius: 0px; width: 100%">
                                    <input type="text" class="form-control sc1-date-control sc1-date-to" name="to" placeholder="To"
                                           style="border-radius: 0px" data-date-end-date="0d" data-date-format="dd/mm/yyyy" data-date-today-highlight="true" date-date-today-btn="true" data-date-immediate-updates="true" data-date-assume-nearby-year="true"/>
                                    <i class="glyphicon glyphicon-calendar form-control-feedback"></i>
                                </span>
                            </span>
                            <?php
                            break;
                        }
                        default:
                        {
                            ?>
                                <input type="text" class="form-control filter-textbox" aria-label="<?php echo $filtername;?>">
                            <?php
                            break;
                        }

                    }
                ?>
                    </div>
                </span>
                <br>
                <?php
                }
                ?>

            </span>
            <?php
            }
            ?>
            <!-- End: Printout filters -->
            <br>
            <btn class="btn btn-primary" data-toggle="collapse" data-target="#filter-panel" id="sc1-btn-filter-apply">Apply</btn>
            <btn class="btn btn-default disabled" id="sc1-btn-filter-clear">Clear Filter(s)</btn>
        </div>
    </div>
    <!-- Search Results/Navigation Container -->
    <div id="search-outcome-container" class="frc-limited">
        <div id="facet-refinement-container">
            <?php
            for ($i = 0; $i < count($categoryfacets); $i++){
                echo ("<div class='category-facets' data-catid='" . $categoryids[$i] . "' style='display:none'>");
                for ($ii = 0; $ii < count($categoryfacets[$i]); $ii++)
                {
                    echo("<div class='facet-field hidden' data-fn='" . $categoryfacets[$i][$ii] ."'>");
                    echo("<h4 class='field-name'>");
                    echo($categoryfacets[$i][$ii]);
                    echo("</h4>");
                    echo("<ul class='field-values list-unstyled' data-facet-ul-fn='" . $categoryfacets[$i][$ii] . "'></ul>");
                    echo("<small class='btn-link clickable link-more-fv'>More…</small>");
                    echo ("</div>");
                }

                echo ("</div>");
                echo ("<div id='all-fields-btn' class='btn-link clickable hidden'>All Fields »</div>");
            }
            ?>
        </div>
        <div style="width: 100%; padding-top: 10px">
            <div id="info-sort-zone">
                <div id="info-display" style="height: 40px; line-height: 40px">
                </div>
                <div class="dropdown">
                    <button class="btn btn-default dropdown-toggle collapse" type="button" id="dropdown-sortorder" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                        <span class="glyphicon glyphicon-sort" aria-hidden="true"></span> Sort
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown-sortorder">
                        <li><a href="#" class="sort-item" data-sort="none"><span class="glyphicon glyphicon-arrow-up" aria-hidden="true"></span> By Relevance</a></li>
                        <li><a href="#" class="sort-item" data-sort="a-z"><span class="glyphicon glyphicon-sort-by-alphabet" aria-hidden="true"></span> Alphabetical (A-Z)</a></li>
                        <li><a href="#" class="sort-item" data-sort="z-a"><span class="glyphicon glyphicon-sort-by-alphabet-alt" aria-hidden="true"></span> Alphabetical (Z-A)</a></li>
                    </ul>
                </div>
            </div>
            <div id="search-results-container" style="padding-top: 10px">
                <!-- Search results, and result documents, will be appended here as necessary -->
            </div>
            <nav id="sc1-paginator" class="hidden" aria-label="Results Navigation">
                <ul class="pagination">
                    <li>
                        <a href="#" aria-label="Previous" id="sc1-page-prev">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" aria-label="Next" id="sc1-page-next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div id="doc-window" style="display: none;"></div>
            <div id="extra-whitespace" style="height:50px">
            <!-- Adds some tolerance to the iframe resizer for error in height calculation -->
            </div>
        </div>
    </div>
    <div class="modal" id="modal-facets-lv" tabindex="-1" role="dialog" data-backdrop="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Modal title</h4>
                </div>
                <div class="modal-body">
                    <ul id="ul-facets-modal" class="list-unstyled">
                        <!-- Facets go here -->
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
</body>
<script type="application/javascript">

    // On page load, hide next/prev buttons as no results yet.
    document.getElementById('sc1-page-prev').style.display= 'none';
    document.getElementById('sc1-page-next').style.display= 'none';

    let current_page = 1;

    let sortBy = "none";

    function get_filter_count() {
        return $('.filter-checkbox:checked').length;
    }

    $('#sc1-btn-filter-apply').click(function() {
        apply_filters(true);
    });

    function apply_filters(displayResults) {
        var filterCount = get_filter_count();
        var badge = $('#filterCountBadge');

        if (filterCount > 0)    $(badge).css('display','inline-block').html(filterCount);
        else                    $(badge).css('display','none').html('');
        facets = [];
        if (displayResults) display_results(true);
        update_clear_filters_btn_vis();
    }

    $('#sc1-btn-filter-clear').click(function() {
       clear_filters();
    });

    // Used by both clear filters button and when user changes category
    function clear_filters()
    {
        $('.filter-checkbox').prop('checked',false).trigger('change');
    }

    // Search Box Submit - Enter Key
    $("#sc1-searchbox").keypress(function( event ) {
        if (event.keyCode === 13) {
            current_page = 1;
            clear_facets();
            display_results(true);
        }
    });
    // Search Box Submit - Search Button Click
    $("#btnSearch").click(function() {
       current_page = 1;
       facets = [];
       clear_facets();
       display_results(true);
    });

    $('#sc1-page-next').click(function() {
        current_page++;
        display_results(false);
    });

    $('#sc1-page-prev').click(function() {
        current_page--;
        display_results(false);
    });

    $('.sort-item').click(function() {
        sortBy = $(this).attr('data-sort');
        $('#dropdown-sortorder').html($(this).html());
        current_page = 1;
        display_results(false);
    });


    $('.filter-checkbox').change(function() {
        let dataType = $(this).closest('.sc1-filter-item').attr('data-filter-datatype');
        if ($(this).prop('checked')) {

            switch(dataType.toLowerCase()) {
                case 'date':
                case 'datetime':
                {
                    $(this).closest('.sc1-filter-item').find('.sc1-date-control')[0].focus();
                    break;
                }
                default:
                {
                    $(this).closest('.sc1-filter-item').find('.filter-textbox')[0].focus();
                    break;
                }
            }
        } else {
            // Unchecked
            switch(dataType.toLowerCase()) {
                case 'date':
                case 'datetime':
                {
                    $(this).closest('.sc1-filter-item').find('.sc1-date-control').val('');
                    break;
                }
                default:
                {
                    $(this).closest('.sc1-filter-item').find('.filter-textbox').val('');
                    break;
                }
            }
        }
        update_clear_filters_btn_vis();
    });

    // Category Selection Change Handling
    if ($('#categoryselect').length) {
        // This page has multiple Categories the user can select from assigned.
        $('#categoryselect').change(function() {
            // User chose a new category
            category = $('#categoryselect').val();
            update_filter_display(category);
            // Clear out all of the filters
            clear_filters();
            clear_results();
            apply_filters(false);
            $('#facet-refinement-container').find('.category-facets').each(function() {
               $(this).hide();
            });
            $('#sc1-paginator').addClass('hidden');
            $('#search-outcome-container')
                .removeClass('frc-maximised')
                .addClass('frc-limited');
            display_results(true);

        });
    }

    // Update the available filters, visibility of filter button based on categoryid
    function update_filter_display(categoryid) {

        $('#sc1-searchbox').attr('data-categoryid',categoryid);
        // Clear out old results
        $('#search-results-container').html('');
        $('#page-nav-container').css('display','none');
        // Hide/show the filters as appropriate for the selected category.
        var filter_categories = $('#filter-panel').find('.filter-category');
        for (var i = 0; i < filter_categories.length; i++)
        {

            var filter_categoryid = $(filter_categories[i]).attr('data-category-id');
            var display = "none";
            if (categoryid === filter_categoryid) {
                display = "block";
                var filters_count = $(filter_categories[i]).find('.sc1-filter-item').length;
                var filterDisplay = "inline-block";
                if (filters_count === 0) {
                    filterDisplay = "none";
                    $('#filter-panel').collapse("hide"); // Ensure the filters panel is hidden
                }
                $('#btnFilter')[0].style.display = filterDisplay;
            }
            filter_categories[i].style.display = display;
        }
    }

    // Update the filter display on first draw.
    update_filter_display($('#sc1-searchbox').attr('data-categoryid'));

    // If a filter is active, the button becomes enabled.
    function update_clear_filters_btn_vis()
    {
        var filters_count = get_filter_count();
        if (filters_count > 0) $('#sc1-btn-filter-clear').removeClass('disabled');
        else                   $('#sc1-btn-filter-clear').addClass('disabled');
    }

    $('.sc1-date-control').on('changeDate propertychange input change', function() {
        var bothFilled = true;
        $(this).closest('.input-daterange').find('.sc1-date-control').each( function() {
            var str = $(this).val();
            bothFilled  = !!str.trim();
            if (!bothFilled) return false;
        });
        $(this).closest('.sc1-filter-item').find('.filter-checkbox').prop('checked',bothFilled);
        update_clear_filters_btn_vis();
    });

    $('.filter-textbox').on('propertychange input change', function() {
        var str = $(this).val();
        $(this).closest('.sc1-filter-item').find('.filter-checkbox').prop('checked', !!str.trim()); // If the field is not empty, check the checkbox for the title, else uncheck it
        update_clear_filters_btn_vis();
    });

    /**
    * Clear the display of any previous search results
    */
    function clear_results()
    {
        $('#search-results-container').html('');
        $('#page-nav-container').css('display','none');
    }

    let category = -1;
    let lastQuery = {};

    /**
    * Display a result set for a query
    * query:  What was searched
    * index: Names of which index to be searched
    * page: The page number to be displayed in the result set
    */
    function display_results(refresh_facets)
    {
        if (modalHaltedRefreshing) return; // Prevent refresh from checkbox change events as modal as clicking around.
        try {
            $('#sc1-paginator').addClass('hidden');
            let searchbox = $('#sc1-searchbox');
            let query = $(searchbox).val();
            category = $(searchbox).attr('data-categoryid');
            // Sanity Check 1/2 - Ensure a query was entered
            if (query.trim().length === 0) {
                query = "xfirstword";
            }

            if (get_filter_count() > 0) {
                query = '(' + query + ') ';
                $('.filter-category[data-category-id=' + category + ']').find('.sc1-filter-item').each(function () {
                    let checked = $(this).find('.filter-checkbox').prop('checked');
                    if (checked) {
                        let dataType = $(this).attr('data-filter-datatype');
                        let fieldName = $(this).attr('data-filter-name');
                        fieldName = fieldName.replace(/ /g, '');
                        switch (dataType) {
                            case 'DateTime':
                            case 'Date': {
                                let dateFrom = $(this).find('.sc1-date-from').val();
                                let dateTo = $(this).find('.sc1-date-to').val();

                                query = query + "and (" + fieldName + " contains date(" + dateFrom + " to " + dateTo + "))";
                                break;
                            }
                            default: {
                                let text = $(this).find('.filter-textbox').val();
                                query = query + 'and (' + fieldName + ' contains (' + text + '))';
                                break;
                            }
                        }
                    }
                });
            }

            let current_query = query;

            // Sanity Check 2/2 - Ensure the requested page is greater than 0
            if (current_page < 1) {
                current_page = 1;
            }
            // Perform our query and await a response from the server
            $('#info-display').html('<i>Loading...</i>');
            $('#search-results-container').html('');
            $('#page-nav-container').css('display', 'none');


            let json = {};
            json.performSearch = {};
            json.performSearch.query = query;
            json.performSearch.page = current_page;
            json.performSearch.context = true;
            json.performSearch.categoryid = category;
            json.performSearch.filters = {};
            json.performSearch.sortBy = sortBy;

            json.performSearch.reqFacets = [];
            $('.category-facets[data-catid=' + category + ']').find('.field-name').each(function () {
                json.performSearch.reqFacets.push($(this).html());
            });
            if (refresh_facets) {
                $('.category-facets').hide();
            }


            let facet_filters = get_facet_query();
            if (facet_filters.length > 0) {
                json.performSearch.facetFilters = facet_filters;
            }
            else {
                json.performSearch.facetFilters = false;
            }

            lastQuery = json;

            $.ajax({
                type: "GET",
                url: '<?php echo(rest_url("sc1_client/v1/search"));?>?query=' + encodeURIComponent(JSON.stringify(json)),
                timeout: 10000,
                success: function (data, textStatus, xhr) {
                    window.scrollTo(0, 0);
                    $('#page-nav-container').css('display', 'none');
                    // query success without error
                    // This will respond results xml which needs to be parsed
                    let json = data;
                    let results_container = $('#search-results-container');
                    if (json.Hits === 0) {
                        // No results..
                        $('#dropdown-sortorder').addClass('collapse');
                        $('#info-display').html('No Results.');
                        return;
                    }
                    $('#dropdown-sortorder').removeClass('collapse');
                    // Write out the page navigator
                    let start = ((current_page - 1) * 10) + 1;
                    let end = ((current_page - 1) * 10) + 10;
                    if (end > json.DocCount) end = json.DocCount;
                    $('#info-display').html("<i>Showing Results " + (parseInt(start)) + " - " + end + " of " + json.DocCount + "</i>");


                    let num_pages = Math.ceil(json.DocCount / 10);
                    let page_result_count = json.Results.length;
                    for (let i = 0; i < page_result_count; i++) {
                        let resultDiv_element = document.createElement('div');
                        resultDiv_element.className = "search-result-container";
                        resultDiv_element.setAttribute("style", "padding-top: 5px");

                        let link_element = document.createElement('a');

                        // link generation

                    let searchBox = $('#sc1-searchbox');
                    category = $(searchBox).attr('data-categoryid');
                    let pageLinkJson = lastQuery;

                    pageLinkJson.performSearch.hitViewer = json.Results[i].ResultIndex;
                    let hitViewer_url = '<?php echo(rest_url('sc1_client/v1/hitviewer')); ?>';
                    let hitViewer_iframe_url = '<?php echo(rest_url("sc1_client/v1/search"));?>?query=' + encodeURIComponent(JSON.stringify(pageLinkJson));
                    hitViewer_url = hitViewer_url + '?url=' + encodeURIComponent(hitViewer_iframe_url) + '&title=' + json.Results[i].DocDisplayName + '&fileUUID=' + json.Results[i].FileUUID;
                    link_element.setAttribute("href", hitViewer_url);

                    link_element.setAttribute("target","_blank");
                    link_element.innerHTML = json.Results[i].DocDisplayName;


                    let context_element = document.createElement("p");
                    context_element.innerHTML = json.Results[i].Context;

                        resultDiv_element.appendChild(link_element);
                        resultDiv_element.appendChild(context_element);
                        document.getElementById("search-results-container").appendChild(resultDiv_element);
                    }
                    $('#sc1-paginator').removeClass('hidden');
                    let printed_results = 0;
                    let paginationElement = $('.pagination');
                    let paginationElements = $(paginationElement.find('li'));
                    let firstElem = paginationElements[0]; // Previous Button
                    let lastElem = paginationElements[paginationElements.length - 1]; // Next Button
                    firstElem.remove();
                    lastElem.remove();
                    paginationElement.html('');
                    for (let i = 1; i <= num_pages && printed_results < 10; i++) {
                        printed_results++;
                        if (i < (current_page - 5)) i = current_page - 5;
                        let linkLi = document.createElement('li');
                        if (i === current_page) {
                            linkLi.setAttribute('class', 'active');
                        }
                        let pageLink = document.createElement('a');
                        pageLink.setAttribute('href', '#');
                        pageLink.setAttribute('class', 'pagelink');
                        let action = 'current_page = ' + i + "; display_results(false); return false;";
                        pageLink.setAttribute("onclick", action);
                        pageLink.innerHTML = '' + i;
                        linkLi.appendChild(pageLink);
                        paginationElement.append(linkLi);
                    }

                    paginationElement.prepend(firstElem);
                    paginationElement.append(lastElem);

                    if (current_page > 1) document.getElementById('sc1-page-prev').style.display = 'block';
                    if (current_page === 1) document.getElementById('sc1-page-prev').style.display = 'none';
                    if (current_page === num_pages) document.getElementById('sc1-page-next').style.display = 'none';
                    else document.getElementById('sc1-page-next').style.display = 'block';

                    let fieldValues = json.TopFieldValues;
                    if (refresh_facets) {

                        $('.field-values').html(''); // Clear out all field values.
                        let valuesCount = 0;
                        for (let i = 0; i < fieldValues.length; i++) {
                            let field = fieldValues[i].Field;
                            let values = fieldValues[i].Values;
                            let ul = $('[data-facet-ul-fn="' + field + '"]');
                            ul.html('');
                            for (let ii = 0; ii < values.length; ii++) {
                                let value = values[ii].Value;
                                let count = values[ii].Count;
                                let checkbox_html = $('#cb_facet').html();
                                ul.append('<li>' + checkbox_html + '</li>');
                                let cb = ul.find('.checkbox').last();
                                cb.attr('data-fn', field)
                                    .attr('data-fv', value);
                                cb.find('.field-value').html('<div style="display: flex"><div class="facet-value-dsp">' + value + '</div> <div> (' + count + ')</div></div>');
                                //ul.append('<li class="btn-link clickable"><div style="display: flex"><div class="facet-value-dsp">' + value + '</div> <div> (' + count +')</div></div></li>');
                                valuesCount++;
                                cb.find('input').off().change(checkbox_change_evt);
                            }

                            let facetField = $('.facet-field[data-fn="' + field + '"]');
                            if (values.length > 0) {
                                facetField
                                    .removeClass('hidden')
                                    .addClass('displayed');
                                if (values.length > 3) {
                                    facetField.find('.link-more-fv')
                                        .off()
                                        .click(function () {
                                            // On click, show a dialog displaying all of the possible facet values.
                                            let modalFacets = $('#modal-facets-lv');
                                            let fieldName = $(this).closest('.facet-field').attr('data-fn');
                                            modalFacets.modal('show');
                                            modalFacets.find('.modal-title').html(fieldName);
                                            modalFacets.find('#ul-facets-modal').html($('[data-facet-ul-fn="' + fieldName + '"]').html());
                                            modalFacets.find('.btn-primary').off().click(save_modal_changes);
                                            sync_modal_checkboxes();
                                        });
                                }
                                else {
                                    facetField.find('.link-more-fv').hide();
                                }
                            }
                            else {
                                facetField
                                    .addClass('hidden')
                                    .removeClass('displayed');
                                facetField.find('.link-more-fv').hide();
                            }
                        }
                        if (valuesCount > 0) {
                            $('.category-facets[data-catid=' + category + ']').show();
                        }
                    }
                    update_expand_buttons();
                }, error: function (data, textStatus, xhr) {
                    $('#info-display').html('Something went wrong. Check your connection and try again.');
                }

            });
        } finally {
            refreshing = false;
            update_expand_buttons();
        }
    }

    function save_modal_changes() {
        modalHaltedRefreshing = true;
        let modalFacets = $('#modal-facets-lv');
        let fieldName = modalFacets.find('.modal-title').html();
        let catFacetsDsp = $('.category-facets[data-catid="' + category + '"]');
        let ul = catFacetsDsp.find('.facet-field[data-fn="' + fieldName + '"]').find('ul');
        modalFacets.find('input').each(function() {
            let li = $(this).closest('li');
            let fieldValue = li.find('.checkbox').attr('data-fv');
            let checked = $(this).is(':checked');
            let dspChecked = ul.find('.checkbox[data-fv="' + fieldValue + '"] input').is(':checked');
            if (checked) {
                // Checked in modal
                ul.find('.checkbox[data-fv="' + fieldValue + '"]').closest('li').prependTo(ul);
                if (!dspChecked)
                {
                    ul.find('.checkbox[data-fv="' + fieldValue + '"] input').click();
                }
            } else {
                // Unchecked in modal
                if (dspChecked) {
                    // Checked in display
                    ul.find('.checkbox[data-fv="' + fieldValue + '"] input').click();
                }
            }
        });
        modalFacets.modal('hide');
        modalHaltedRefreshing = false;
        display_results(false);
    }

    let modalHaltedRefreshing = false;

    function checkbox_change_evt() {
        current_page = 1;
        let checked = $(this).is(":checked");
        let field   = $(this).closest('.checkbox').attr('data-fn');
        let value   = $(this).closest('.checkbox').attr('data-fv');
        if (checked) {
            add_facet(field, value);
            $(this).closest('label').css({'font-weight':'600'});
        } else {
            remove_facet(field, value);
            $(this).closest('label').css({'font-weight':'400'});
        }
    }

    /**
     * For each Modal Checkbox, if the facet sidebar checkbox is checked, check on the modal.
     */
    function sync_modal_checkboxes() {
      let modal = $('#modal-facets-lv');
      let fieldName = modal.find('.modal-title').html();
      let facetView = $('.category-facets[data-catid='+ category + ']').find('.facet-field[data-fn="' + fieldName + '"]');
      facetView.find(':checked').each(function() {
         // For each checked facet value, sync up the modal by checking over there too.
         let value = $(this).closest('.checkbox').attr('data-fv');
         modal.find('.checkbox[data-fv="' + value + '"]').find('input').click();
      });

    }

    let facets = [];

    function add_facet(field, value) {
        let facet = {};
        facet.field = field;
        facet.value = value;
        facets.push(facet);
        render_facets();
    }

    function remove_facet(field, value) {
        for (let i = 0; i < facets.length; i++) {
            let facet = facets[i];
            if (facet.field === field && facet.value === value) {
                facets.splice(i,1);
            }
        }
        render_facets();
    }

    function render_facets() {
        display_results(false);
    }

    function clear_facets() {
        facets = [];
        $('#facet-refinement-container').find('input').prop('checked', false);
    }

    function get_facet_query() {

        let query = [];

        $('#facet-refinement-container').find('.facet-field').each(function() {
           let fieldname = $(this).attr('data-fn');
           let values = [];
           $(this).find(':checked').each(function() {
              let value = $(this).closest(".checkbox").attr('data-fv');
              values.push(value);
           });
           if (values.length > 0) {
               let obj = {};
               obj.Field = fieldname;
               obj.Values = values;
               query.push(obj);
           }
        });
        return query;
    }

    let refreshing = false;

    $(window).resize(function() {
        // Only update on resize if we're not currently fetching search results.
        if (!refreshing) {
            update_expand_buttons();
        }
    });

    let allFieldsBtn = $('#all-fields-btn');
    allFieldsBtn
        .off()
        .click(function() {
            let open = ($(this).attr('data-open') === 'true');
            open = !open;
            if (open === true) {
                allFieldsBtn.html('Close [x]');
                $('#search-outcome-container')
                    .removeClass('frc-limited')
                    .addClass('frc-maximised');
                $(this).attr('data-open', 'true');


            } else {
                allFieldsBtn.html('All Fields »');
                $('#search-outcome-container')
                    .addClass('frc-limited')
                    .removeClass('frc-maximised');
                $(this).attr('data-open', 'false');

                $('.field-values').each(function() {
                    let ul = $(this);
                    ul.find(':checked').each(function() {
                        let li = $(this).closest('li');
                        let fieldValue = li.find('.checkbox').attr('data-fv');
                        // Move the selection to the top of the list so it's visible.
                        ul.find('.checkbox[data-fv="' + fieldValue + '"]').prependTo(ul);
                        ul.find('.checkbox').click();
                    });
                });
            }
        });

    /*
    Hides/Shows 'Show More' buttons in Facet Search depending on window size.
     */
    function update_expand_buttons() {
        let width = $(window).width();
        let max_values = 4;
        if (width <= 620) {
            max_values = 3;
        }
        let max_fields = 5;
        if (width <= 620 && width > 450) {
            max_fields = 3;
        }
        if (width <= 450 && width > 300) {
            max_fields = 2;
        }
        if (width <= 300) {
            max_fields = 1;
        }

        let catFacets = $('#facet-refinement-container .category-facets[data-catid="' + category + '"]');
        // 1. Hiding/Showing the 'More' button of Facet Values
        // depending on how many values there are.
        // For every Category Facets, check every field to see
        // if there are enough values to require the 'More...'
        // button to be shown.
        let facetFields = $(catFacets).find('.facet-field.displayed');
        facetFields.each(function() {
            let valueCount = $(this).find('li').length;
            if (valueCount > max_values) {
               $(this).find('.link-more-fv').removeClass('hidden');
            } else {
               $(this).find('.link-more-fv').addClass('hidden');
            }
        });
        // 2. Hiding/Showing the 'More' button of Facet Fields
        // depending on how many fields there are.
        let displayed_results = $('.search-result-container').length;
        let fieldsCount = facetFields.length;
        if (fieldsCount > max_fields && displayed_results > 0) {
            allFieldsBtn.removeClass('hidden')

        } else {
            allFieldsBtn.addClass('hidden');
        }
    }

    /*
    On page load, get some initial results
     */
    $(document).ready(function() {
       display_results(true);
    });

    </script>
<script>
 /*! iFrame Resizer (iframeSizer.contentWindow.min.js) - v3.6.1 - 2018-04-29
 *  Desc: Include this file in any page being loaded into an iframe
 *        to force the iframe to resize to the content size.
 *  Requires: iframeResizer.min.js on host page.
 *  Copyright: (c) 2018 David J. Bradshaw - dave@bradshaw.net
 *  License: MIT
 */
 !function(a){"use strict";function b(a,b,c){"addEventListener"in window?a.addEventListener(b,c,!1):"attachEvent"in window&&a.attachEvent("on"+b,c)}function c(a,b,c){"removeEventListener"in window?a.removeEventListener(b,c,!1):"detachEvent"in window&&a.detachEvent("on"+b,c)}function d(a){return a.charAt(0).toUpperCase()+a.slice(1)}function e(a){var b,c,d,e=null,f=0,g=function(){f=Ha(),e=null,d=a.apply(b,c),e||(b=c=null)};return function(){var h=Ha();f||(f=h);var i=xa-(h-f);return b=this,c=arguments,i<=0||i>xa?(e&&(clearTimeout(e),e=null),f=h,d=a.apply(b,c),e||(b=c=null)):e||(e=setTimeout(g,i)),d}}function f(a){return ma+"["+oa+"] "+a}function g(a){la&&"object"==typeof window.console&&console.log(f(a))}function h(a){"object"==typeof window.console&&console.warn(f(a))}function i(){j(),g("Initialising iFrame ("+location.href+")"),k(),n(),m("background",W),m("padding",$),A(),s(),t(),o(),C(),u(),ia=B(),N("init","Init message from host page"),Da()}function j(){function b(a){return"true"===a}var c=ha.substr(na).split(":");oa=c[0],X=a!==c[1]?Number(c[1]):X,_=a!==c[2]?b(c[2]):_,la=a!==c[3]?b(c[3]):la,ja=a!==c[4]?Number(c[4]):ja,U=a!==c[6]?b(c[6]):U,Y=c[7],fa=a!==c[8]?c[8]:fa,W=c[9],$=c[10],ua=a!==c[11]?Number(c[11]):ua,ia.enable=a!==c[12]&&b(c[12]),qa=a!==c[13]?c[13]:qa,Aa=a!==c[14]?c[14]:Aa}function k(){function a(){var a=window.iFrameResizer;g("Reading data from page: "+JSON.stringify(a)),Ca="messageCallback"in a?a.messageCallback:Ca,Da="readyCallback"in a?a.readyCallback:Da,ta="targetOrigin"in a?a.targetOrigin:ta,fa="heightCalculationMethod"in a?a.heightCalculationMethod:fa,Aa="widthCalculationMethod"in a?a.widthCalculationMethod:Aa}function b(a,b){return"function"==typeof a&&(g("Setup custom "+b+"CalcMethod"),Fa[b]=a,a="custom"),a}"iFrameResizer"in window&&Object===window.iFrameResizer.constructor&&(a(),fa=b(fa,"height"),Aa=b(Aa,"width")),g("TargetOrigin for parent set to: "+ta)}function l(a,b){return-1!==b.indexOf("-")&&(h("Negative CSS value ignored for "+a),b=""),b}function m(b,c){a!==c&&""!==c&&"null"!==c&&(document.body.style[b]=c,g("Body "+b+' set to "'+c+'"'))}function n(){a===Y&&(Y=X+"px"),m("margin",l("margin",Y))}function o(){document.documentElement.style.height="",document.body.style.height="",g('HTML & body height set to "auto"')}function p(a){var e={add:function(c){function d(){N(a.eventName,a.eventType)}Ga[c]=d,b(window,c,d)},remove:function(a){var b=Ga[a];delete Ga[a],c(window,a,b)}};a.eventNames&&Array.prototype.map?(a.eventName=a.eventNames[0],a.eventNames.map(e[a.method])):e[a.method](a.eventName),g(d(a.method)+" event listener: "+a.eventType)}function q(a){p({method:a,eventType:"Animation Start",eventNames:["animationstart","webkitAnimationStart"]}),p({method:a,eventType:"Animation Iteration",eventNames:["animationiteration","webkitAnimationIteration"]}),p({method:a,eventType:"Animation End",eventNames:["animationend","webkitAnimationEnd"]}),p({method:a,eventType:"Input",eventName:"input"}),p({method:a,eventType:"Mouse Up",eventName:"mouseup"}),p({method:a,eventType:"Mouse Down",eventName:"mousedown"}),p({method:a,eventType:"Orientation Change",eventName:"orientationchange"}),p({method:a,eventType:"Print",eventName:["afterprint","beforeprint"]}),p({method:a,eventType:"Ready State Change",eventName:"readystatechange"}),p({method:a,eventType:"Touch Start",eventName:"touchstart"}),p({method:a,eventType:"Touch End",eventName:"touchend"}),p({method:a,eventType:"Touch Cancel",eventName:"touchcancel"}),p({method:a,eventType:"Transition Start",eventNames:["transitionstart","webkitTransitionStart","MSTransitionStart","oTransitionStart","otransitionstart"]}),p({method:a,eventType:"Transition Iteration",eventNames:["transitioniteration","webkitTransitionIteration","MSTransitionIteration","oTransitionIteration","otransitioniteration"]}),p({method:a,eventType:"Transition End",eventNames:["transitionend","webkitTransitionEnd","MSTransitionEnd","oTransitionEnd","otransitionend"]}),"child"===qa&&p({method:a,eventType:"IFrame Resized",eventName:"resize"})}function r(a,b,c,d){return b!==a&&(a in c||(h(a+" is not a valid option for "+d+"CalculationMethod."),a=b),g(d+' calculation method set to "'+a+'"')),a}function s(){fa=r(fa,ea,Ia,"height")}function t(){Aa=r(Aa,za,Ja,"width")}function u(){!0===U?(q("add"),F()):g("Auto Resize disabled")}function v(){g("Disable outgoing messages"),ra=!1}function w(){g("Remove event listener: Message"),c(window,"message",S)}function x(){null!==Z&&Z.disconnect()}function y(){q("remove"),x(),clearInterval(ka)}function z(){v(),w(),!0===U&&y()}function A(){var a=document.createElement("div");a.style.clear="both",a.style.display="block",document.body.appendChild(a)}function B(){function c(){return{x:window.pageXOffset!==a?window.pageXOffset:document.documentElement.scrollLeft,y:window.pageYOffset!==a?window.pageYOffset:document.documentElement.scrollTop}}function d(a){var b=a.getBoundingClientRect(),d=c();return{x:parseInt(b.left,10)+parseInt(d.x,10),y:parseInt(b.top,10)+parseInt(d.y,10)}}function e(b){function c(a){var b=d(a);g("Moving to in page link (#"+e+") at x: "+b.x+" y: "+b.y),R(b.y,b.x,"scrollToOffset")}var e=b.split("#")[1]||b,f=decodeURIComponent(e),h=document.getElementById(f)||document.getElementsByName(f)[0];a!==h?c(h):(g("In page link (#"+e+") not found in iFrame, so sending to parent"),R(0,0,"inPageLink","#"+e))}function f(){""!==location.hash&&"#"!==location.hash&&e(location.href)}function i(){function a(a){function c(a){a.preventDefault(),e(this.getAttribute("href"))}"#"!==a.getAttribute("href")&&b(a,"click",c)}Array.prototype.forEach.call(document.querySelectorAll('a[href^="#"]'),a)}function j(){b(window,"hashchange",f)}function k(){setTimeout(f,ba)}function l(){Array.prototype.forEach&&document.querySelectorAll?(g("Setting up location.hash handlers"),i(),j(),k()):h("In page linking not fully supported in this browser! (See README.md for IE8 workaround)")}return ia.enable?l():g("In page linking not enabled"),{findTarget:e}}function C(){g("Enable public methods"),Ba.parentIFrame={autoResize:function(a){return!0===a&&!1===U?(U=!0,u()):!1===a&&!0===U&&(U=!1,y()),U},close:function(){R(0,0,"close"),z()},getId:function(){return oa},getPageInfo:function(a){"function"==typeof a?(Ea=a,R(0,0,"pageInfo")):(Ea=function(){},R(0,0,"pageInfoStop"))},moveToAnchor:function(a){ia.findTarget(a)},reset:function(){Q("parentIFrame.reset")},scrollTo:function(a,b){R(b,a,"scrollTo")},scrollToOffset:function(a,b){R(b,a,"scrollToOffset")},sendMessage:function(a,b){R(0,0,"message",JSON.stringify(a),b)},setHeightCalculationMethod:function(a){fa=a,s()},setWidthCalculationMethod:function(a){Aa=a,t()},setTargetOrigin:function(a){g("Set targetOrigin: "+a),ta=a},size:function(a,b){N("size","parentIFrame.size("+(a||"")+(b?","+b:"")+")",a,b)}}}function D(){0!==ja&&(g("setInterval: "+ja+"ms"),ka=setInterval(function(){N("interval","setInterval: "+ja)},Math.abs(ja)))}function E(){function b(a){function b(a){!1===a.complete&&(g("Attach listeners to "+a.src),a.addEventListener("load",f,!1),a.addEventListener("error",h,!1),k.push(a))}"attributes"===a.type&&"src"===a.attributeName?b(a.target):"childList"===a.type&&Array.prototype.forEach.call(a.target.querySelectorAll("img"),b)}function c(a){k.splice(k.indexOf(a),1)}function d(a){g("Remove listeners from "+a.src),a.removeEventListener("load",f,!1),a.removeEventListener("error",h,!1),c(a)}function e(b,c,e){d(b.target),N(c,e+": "+b.target.src,a,a)}function f(a){e(a,"imageLoad","Image loaded")}function h(a){e(a,"imageLoadFailed","Image load failed")}function i(a){N("mutationObserver","mutationObserver: "+a[0].target+" "+a[0].type),a.forEach(b)}function j(){var a=document.querySelector("body"),b={attributes:!0,attributeOldValue:!1,characterData:!0,characterDataOldValue:!1,childList:!0,subtree:!0};return m=new l(i),g("Create body MutationObserver"),m.observe(a,b),m}var k=[],l=window.MutationObserver||window.WebKitMutationObserver,m=j();return{disconnect:function(){"disconnect"in m&&(g("Disconnect body MutationObserver"),m.disconnect(),k.forEach(d))}}}function F(){var a=0>ja;window.MutationObserver||window.WebKitMutationObserver?a?D():Z=E():(g("MutationObserver not supported in this browser!"),D())}function G(a,b){function c(a){if(/^\d+(px)?$/i.test(a))return parseInt(a,V);var c=b.style.left,d=b.runtimeStyle.left;return b.runtimeStyle.left=b.currentStyle.left,b.style.left=a||0,a=b.style.pixelLeft,b.style.left=c,b.runtimeStyle.left=d,a}var d=0;return b=b||document.body,"defaultView"in document&&"getComputedStyle"in document.defaultView?(d=document.defaultView.getComputedStyle(b,null),d=null!==d?d[a]:0):d=c(b.currentStyle[a]),parseInt(d,V)}function H(a){a>xa/2&&(xa=2*a,g("Event throttle increased to "+xa+"ms"))}function I(a,b){for(var c=b.length,e=0,f=0,h=d(a),i=Ha(),j=0;j<c;j++)(e=b[j].getBoundingClientRect()[a]+G("margin"+h,b[j]))>f&&(f=e);return i=Ha()-i,g("Parsed "+c+" HTML elements"),g("Element position calculated in "+i+"ms"),H(i),f}function J(a){return[a.bodyOffset(),a.bodyScroll(),a.documentElementOffset(),a.documentElementScroll()]}function K(a,b){function c(){return h("No tagged elements ("+b+") found on page"),document.querySelectorAll("body *")}var d=document.querySelectorAll("["+b+"]");return 0===d.length&&c(),I(a,d)}function L(){return document.querySelectorAll("body *")}function M(b,c,d,e){function f(){da=m,ya=n,R(da,ya,b)}function h(){function b(a,b){return!(Math.abs(a-b)<=ua)}return m=a!==d?d:Ia[fa](),n=a!==e?e:Ja[Aa](),b(da,m)||_&&b(ya,n)}function i(){return!(b in{init:1,interval:1,size:1})}function j(){return fa in pa||_&&Aa in pa}function k(){g("No change in size detected")}function l(){i()&&j()?Q(c):b in{interval:1}||k()}var m,n;h()||"init"===b?(O(),f()):l()}function N(a,b,c,d){function e(){a in{reset:1,resetPage:1,init:1}||g("Trigger event: "+b)}function f(){return va&&a in aa}f()?g("Trigger event cancelled: "+a):(e(),"init"===a?M(a,b,c,d):Ka(a,b,c,d))}function O(){va||(va=!0,g("Trigger event lock on")),clearTimeout(wa),wa=setTimeout(function(){va=!1,g("Trigger event lock off"),g("--")},ba)}function P(a){da=Ia[fa](),ya=Ja[Aa](),R(da,ya,a)}function Q(a){var b=fa;fa=ea,g("Reset trigger event: "+a),O(),P("reset"),fa=b}function R(b,c,d,e,f){function h(){a===f?f=ta:g("Message targetOrigin: "+f)}function i(){var h=b+":"+c,i=oa+":"+h+":"+d+(a!==e?":"+e:"");g("Sending message to host page ("+i+")"),sa.postMessage(ma+i,f)}!0===ra&&(h(),i())}function S(a){function c(){return ma===(""+a.data).substr(0,na)}function d(){return a.data.split("]")[1].split(":")[0]}function e(){return a.data.substr(a.data.indexOf(":")+1)}function f(){return!("undefined"!=typeof module&&module.exports)&&"iFrameResize"in window}function j(){return a.data.split(":")[2]in{true:1,false:1}}function k(){var b=d();b in m?m[b]():f()||j()||h("Unexpected message ("+a.data+")")}function l(){!1===ca?k():j()?m.init():g('Ignored message of type "'+d()+'". Received before initialization.')}var m={init:function(){function c(){ha=a.data,sa=a.source,i(),ca=!1,setTimeout(function(){ga=!1},ba)}"interactive"===document.readyState||"complete"===document.readyState?c():(g("Waiting for page ready"),b(window,"readystatechange",m.initFromParent))},reset:function(){ga?g("Page reset ignored by init"):(g("Page size reset by host page"),P("resetPage"))},resize:function(){N("resizeParent","Parent window requested size check")},moveToAnchor:function(){ia.findTarget(e())},inPageLink:function(){this.moveToAnchor()},pageInfo:function(){var a=e();g("PageInfoFromParent called from parent: "+a),Ea(JSON.parse(a)),g(" --")},message:function(){var a=e();g("MessageCallback called from parent: "+a),Ca(JSON.parse(a)),g(" --")}};c()&&l()}function T(){"loading"!==document.readyState&&window.parent.postMessage("[iFrameResizerChild]Ready","*")}if("undefined"!=typeof window){var U=!0,V=10,W="",X=0,Y="",Z=null,$="",_=!1,aa={resize:1,click:1},ba=128,ca=!0,da=1,ea="bodyOffset",fa=ea,ga=!0,ha="",ia={},ja=32,ka=null,la=!1,ma="[iFrameSizer]",na=ma.length,oa="",pa={max:1,min:1,bodyScroll:1,documentElementScroll:1},qa="child",ra=!0,sa=window.parent,ta="*",ua=0,va=!1,wa=null,xa=16,ya=1,za="scroll",Aa=za,Ba=window,Ca=function(){h("MessageCallback function not defined")},Da=function(){},Ea=function(){},Fa={height:function(){return h("Custom height calculation function not defined"),document.documentElement.offsetHeight},width:function(){return h("Custom width calculation function not defined"),document.body.scrollWidth}},Ga={},Ha=Date.now||function(){return(new Date).getTime()},Ia={bodyOffset:function(){return document.body.offsetHeight+G("marginTop")+G("marginBottom")},offset:function(){return Ia.bodyOffset()},bodyScroll:function(){return document.body.scrollHeight},custom:function(){return Fa.height()},documentElementOffset:function(){return document.documentElement.offsetHeight},documentElementScroll:function(){return document.documentElement.scrollHeight},max:function(){return Math.max.apply(null,J(Ia))},min:function(){return Math.min.apply(null,J(Ia))},grow:function(){return Ia.max()},lowestElement:function(){return Math.max(Ia.bodyOffset()||Ia.documentElementOffset(),I("bottom",L()))},taggedElement:function(){return K("bottom","data-iframe-height")}},Ja={bodyScroll:function(){return document.body.scrollWidth},bodyOffset:function(){return document.body.offsetWidth},custom:function(){return Fa.width()},documentElementScroll:function(){return document.documentElement.scrollWidth},documentElementOffset:function(){return document.documentElement.offsetWidth},scroll:function(){return Math.max(Ja.bodyScroll(),Ja.documentElementScroll())},max:function(){return Math.max.apply(null,J(Ja))},min:function(){return Math.min.apply(null,J(Ja))},rightMostElement:function(){return I("right",L())},taggedElement:function(){return K("right","data-iframe-width")}},Ka=e(M);b(window,"message",S),T()}}();
//# sourceMappingURL=iframeResizer.contentWindow.map
    </script>
</html>
