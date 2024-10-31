<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.searchcloudone.com
 * @since             1.0.0
 * @package           Sc1_Search
 *
 * @wordpress-plugin
 * Plugin Name:       Search Cloud One
 * Description:       Search documents on the cloud with Search Cloud One!
 * Version:           2.2.5
 * Author:            Search Cloud One
 * Author URI:        https://www.searchcloudone.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       sc1-search
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SEARCH_CLOUD_ONE_VERSION', '2.2.5' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-sc1-search-activator.php
 */
function sc1_activate_sc1_search() {

    require_once plugin_dir_path( __FILE__ ) . 'includes/class-sc1-search-activator.php';

    set_transient('sc1-display-admin-notice',true,0);
    try
    {
        sc1_db_install();
        set_transient('sc1-install-success',true,0);
        Sc1_Search_Activator::activate();
        sc1_add_menu_item();
    } catch (Exception $e)
    {
        // Something went wrong with the installation/upgrade process.
        // We'll want to notify the users of this.
        error_log('Error whilst installing Search Cloud One Wordpress Plugin:' . $e->getMessage());
        set_transient('sc1-install-success',false,0);
    }
}

// Add a hook for when the admin menu is requested, adding the searchcloudone menu to the admin menu.
add_action( 'admin_menu', 'sc1_add_menu_item' );

// Adds the SearchCloudOne admin area Menu Item and populates it with necessary submenu items
function sc1_add_menu_item() {

    // Base64 encoded sc1-search ico
    $myicon = 'PHN2ZyB2ZXJzaW9uPSIxLjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmlld0JveD0iMCAwIDUwMDAgNTAwMCIgcHJlc2VydmVBc3BlY3RSYXRpbz0ieE1pZFlNaWQgbWVldCI+CiAgICA8cGF0aCBmaWxsPSJibGFjayIgc3Ryb2tlPSJub25lIiBkPSJNMTAyNSA0MDMzIGMtMTMxIC0xOCAtMzE0IC04OSAtNDM1IC0xNjggLTkzIC02MCAtMjUyIC0yMTggLTMwOSAtMzA1IC0xMjcgLTE5NSAtMTgxIC0zNzkgLTE4MSAtNjE1IDAgLTMxMyAxMDEgLTU1NyAzMTkgLTc3NiAxNTIgLTE1MiAzMTggLTI0NiA1MTcgLTI5MiBsOTEgLTIyIDIzIC02MCBjMTA4IC0yODYgMzU5IC01NTkgNjQyIC02OTggMTkyIC05NSAzNzQgLTEzNyA1OTggLTEzNyAyMDggMCAzNTggMzEgNTQzIDExMCBsOTAgMzkgNjYgLTQwIGMzNiAtMjIgMTAyIC01MiAxNDYgLTY2IDczIC0yNSA5MyAtMjcgMjM1IC0yNyAxNDIgMCAxNjIgMiAyMzUgMjcgNDQgMTQgMTA5IDQzIDE0MyA2NCA2OSA0MSAxNjkgMTI3IDIwMyAxNzQgbDIxIDI5IC0xNzkgLTIgLTE3OCAtMyAtNDEgNTUgYy01MiA3MCAtOTAgMTA0IC0xODcgMTczIC0xMTQgODIgLTI5MCAxNjIgLTQzMiAxOTggLTQ0IDExIC05NiAyNCAtMTE1IDI5IGwtMzUgOSAzIDMxMiAzIDMxMSAzNyAtNiBjODAgLTE0IDI1NCAtNzAgNDA4IC0xMzIgODkgLTM1IDE2NCAtNjQgMTY4IC02NCAzIDAgNiAzMDQgNiA2NzUgbDAgNjc1IC0zNTAgMCAtMzUwIDAgMCAyNzAgMCAyNzAgLTgzNyAtMSBjLTQ2MSAtMSAtODUxIC00IC04NjggLTZ6Ii8+CiAgICA8cGF0aCBmaWxsPSJibGFjayIgc3Ryb2tlPSJub25lIiBkPSJNNDIyMCAyNzA1IGMwIC01ODQgMyAtNzk1IDExIC03OTUgNyAwIDUyIDIxIDEwMSA0NyAyNjUgMTQwIDQ2MSAzODcgNTQ1IDY4OSAyNiA5MiAyOCAxMTIgMjggMjg5IDAgMTc3IC0yIDE5NyAtMjcgMjg4IC0xNiA1NCAtNDcgMTM5IC02OSAxODggbC00MiA4OSAtMjczIDAgLTI3NCAwIDAgLTc5NXoiLz4KPC9zdmc+IA==';

    add_menu_page(
        'Search Cloud One',
        'Search Cloud One',
        'manage_options',
        '/SearchCloudOne/Quickstart.php',
        'sc1_quickstart_page',
        'data:image/svg+xml;base64,' . $myicon,
        6
    );
    add_submenu_page(
        '/SearchCloudOne/Quickstart.php',
        'Quick Start',
        'Quick Start',
        'manage_options',
        '/SearchCloudOne/Quickstart.php',
        'sc1_quickstart_page'
    );

    add_submenu_page(
        '/SearchCloudOne/Quickstart.php',
        'Categories',
        'Categories',
        'manage_options',
        '/SearchCloudOne/Categories.php',
        'sc1_categories_page'
    );
    add_submenu_page(
        '/SearchCloudOne/Quickstart.php',
        'New Category',
        'New Category',
        'manage_options',
        '/SearchCloudOne/NewCategory.php',
        'sc1_createnew_category_page'
    );

    add_submenu_page(
        '/SearchCloudOne/Quickstart.php',
        'Shortcodes',
        'Shortcodes',
        'manage_options',
        '/SearchCloudOne/Shortcodes.php',
        'sc1_searchpages_page'
    );

    add_submenu_page(
        '/SearchCloudOne/Quickstart.php',
        'New Shortcode',
        'New Shortcode',
        'manage_options',
        '/SearchCloudOne/NewShortcode.php',
        'sc1_createnew_searchpage'
    );

}

// Registers the shortcode users use to place search interfaces on their website.
add_shortcode('sc1-search','sc1_frontend_searchpage_shortcode');

/*
Called when a shortcode has been placed on the front-end and we need to return the code needed to render the contents of that shortcode
*/
function sc1_frontend_searchpage_shortcode($atts)
{
    ob_start();
    include(plugin_dir_path(__FILE__) . 'public/sc1-searchpage-iframe-bs3.php');
    return ob_get_clean();
}

/*
    On WP REST API Initialisation, we add the following routes under sc1_client/v1

    >   /options:       JSON Endpoint for configuring the plugin options, including ability to:
                        >   Set API Key to let SearchCloudOne WP Plugin interact with user's SearchCloudOne account.
                        >   Create/Delete Search Pages
                        >   Create/Delete Categories

    >   /searchpage:    Endpoint that generates a html search page on frontend.
                        Used by the shortcode [sc1-search page='pageid']

    >   /search:        Endpoint that recieves search requests and serves results.

    >   /hitviewer:     Endpoint that generates a front-end hitviewer for a given search result
*/
add_action('rest_api_init', function()
{
    register_rest_route('sc1_client/v1','/options', array(
        'methods'=>'POST',
        'callback'=>'sc1_options_recieve',
        'permission_callback' => 'sc1_options_permissions',
    ));
    register_rest_route('sc1_client/v1','/searchpage', array(
        'methods'=>'GET',
        'callback'=>'sc1_searchpage',
    ));
    register_rest_route('sc1_client/v1','/pdfviewer', array(
            'methods'=>'GET',
            'callback'=>'sc1_pdfviewer',
    ));
    register_rest_route('sc1_client/v1','/search', array(
        'methods'=>'GET',
        'callback'=>'sc1_search',
    ));
    register_rest_route('sc1_client/v1','/hitviewer', array(
        'methods'=>'GET',
        'callback'=>'sc1_hitviewer',
    ));
    register_rest_route('sc1_client/v1','/download_tokens', array(
        'methods' => 'GET',
        'callback' => 'sc1_get_download_token',
    ));
    register_rest_route('sc1_client/v1', '/file_properties', array(
        'methods' => 'GET',
        'callback' => 'sc1_get_file_properties',
    ));
});

function sc1_get_file_properties($request) {
    try {
        $fileUUID = $request['fileUUID'];

        $data = new StdClass();
        $data->{"APIKey"} = get_transient('sc1-apiKey');
        $data->{"Action"} = "GetFileProperties";
        $data->{"FileUUID"} = $fileUUID;

        $options = array(
            'http' => array(
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data)
            )
        );

        $context = stream_context_create($options);
        $result = file_get_contents('https://api.searchcloudone.com/Files', false, $context);

        if ($result === FALSE) {
            // Failed to get file properties
            error_log('Error Serving Search Request: file_get_contents returned FALSE');
            header("HTTP/1.1 500 Internal Error");
            header('Content-Type: text/plain');
            echo('Something went wrong (Code 15)');
            exit();
        }

        $httpCode = sc1_getHttpCodeTwo($http_response_header);

        if ($httpCode >= 200 && $httpCode < 300) {
            // All good in the hood
            echo($result);
            exit();
        }

        error_log('Error Serving Search Request: file_get_contents gives status ' . $httpCode);
        header("HTTP/1.1 500 Internal Error");
        header('Content-Type: text/plain');
        echo('Something went wrong (Code 16)');
        exit();


    } catch (Exception $e) {
        header("HTTP/1.1 500 Internal Error");
        header('Content-Type: text/plain');
        echo('Something went wrong');
        exit();
    }
}

function sc1_get_download_token($request) {
    try {
        $fileUUID = $request['fileUUID'];
        $apiKey = get_transient('sc1-apiKey');
        $context = stream_context_create(array(
            'http' => array(
                'ignore_errors' => true
            )
        ));


        $data = file_get_contents("https://api.searchcloudone.com/OneUseTokens?APIKey=" . $apiKey . "&Action=ReadFile&Resource=" . $fileUUID, false, $context);
        $httpCode = sc1_getHttpCodeTwo($http_response_header);
        if ($httpCode >= 200 && $httpCode < 300) {
            // Success
            header('Content-Type: text/plain');
            echo($data);
            exit();
        }
        else {
            // Something went wrong
            header("HTTP/1.1 500 Internal Error");
            header('Content-Type: text/plain');
            echo('Something went wrong');
            exit();
        }
    } catch (Exception $e) {
        header("HTTP/1.1 500 Internal Error");
        header('Content-Type: text/plain');
        echo('Something went wrong');
        exit();
    }
}

function sc1_pdfviewer($request) {
    header('Content-Type: text/html');
    set_error_handler("sc1_myErrorHandler");
    try {
        include(plugin_dir_path(__FILE__) . 'public/sc1-pdf-hitviewer.php');
    } catch (Exception $e) {
        echo 'Here\'s where I\'d tell you something went wrong. If I was an intuitive language';
    }
    restore_error_handler();
    exit();
}

function sc1_myErrorHandler($errno, $errstr, $errfile, $errline)
{


    switch ($errno) {
        case E_USER_ERROR:
            echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
            echo "  Fatal error on line $errline in file $errfile";
            echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
            echo "Aborting...<br />\n";
            exit(1);
            break;

        case E_USER_WARNING:
            echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
            break;

        case E_USER_NOTICE:
            echo "<b>My NOTICE</b> [$errno] $errstr<br />\n";
            break;

        default:
            echo "Unknown error type: [$errno] $errstr<br />\n";
            break;
    }

    /* Don't execute PHP internal error handler */
    return true;
}

// Handles search requests from the /search endpoint
function sc1_search($request)
{
    try
    {
//        echo 'query: ' . (string)$request['query'];
//        exit(); // debugging
        $hitViewer = false;
        $params = json_decode(urldecode(urldecode((string)$request['query'])),true);
        // The request has come from the frontend - we're performing a search.
        $query          = $params['performSearch']['query'];
        $page           = $params['performSearch']['page'];
        $categoryid     = $params['performSearch']['categoryid'];
        $context        = $params['performSearch']['context'];
        $sortBy        = $params['performSearch']['sortBy'];
        global $wpdb;
        $indexes = $wpdb->get_results(
            "
            SELECT indexuuid AS IndexUUID, indexid AS IndexID
            FROM " . $wpdb->prefix . "sc1categoryindexes
            WHERE categoryid=" . $categoryid
        );
        $req_facets = $params['performSearch']['reqFacets'];
        $facet_filters = $params['performSearch']['facetFilters'];
        $data = new StdClass();
        $data->{"APIKey"} = get_transient('sc1-apiKey');
        $data->{"Indexes"} = $indexes;
        $data->{"Parameters"} = new StdClass();
        $data->{"Parameters"}->{"Query"} = $query;
        $data->{"Parameters"}->{"Page"} = $page;
        $data->{"Parameters"}->{"IncludeContext"} = $context;
        if ($facet_filters) {
            $data->{"Parameters"}->{"Filters"} = new StdClass();
            $data->{"Parameters"}->{"Filters"}->{"FieldValuesMatchAny"} = $facet_filters;
        }
        if ($sortBy !== "none") {
            // Requested the results to be sorted
            $data->{"Parameters"}->{"Sort"} = new StdClass();
            switch ($sortBy) {
                case "a-z": {
                    $data->{"Parameters"}->{"Sort"}->{"SortBy"} = "Title";
                    $data->{"Parameters"}->{"Sort"}->{"Ascending"} = true;
                    break;
                }
                case "z-a": {
                    $data->{"Parameters"}->{"Sort"}->{"SortBy"} = "Title";
                    $data->{"Parameters"}->{"Sort"}->{"Ascending"} = false;
                }
            }
        }
        if (count($req_facets) > 0) {
            $data->{"Parameters"}->{"GetTopFieldValues"} = new StdClass();
            $data->{"Parameters"}->{"GetTopFieldValues"}->{"Fields"} = $req_facets;
        }
        // Check if the Search should be using any additional fields as title
        $fieldsAsTitle = $wpdb->get_results("
		SELECT setting_value FROM " . $wpdb->prefix . "sc1debugsettings
		WHERE setting_key='additional-dspn-field'");
        if (count ($fieldsAsTitle) > 0) {

            $displaynamefields = [];
            foreach($fieldsAsTitle as $fieldAsTitle) {
                $displaynamefields[] = $fieldAsTitle->setting_value;
            }
            $data->{"Parameters"}->{"UseFieldsAsDocDisplayName"} = $displaynamefields;
        }


        if (isset($params['performSearch']['hitViewer'])) {

            $hitViewer = true;
            $data->{"Parameters"}->{"HitViewer"} = new StdClass();
            $data->{"Parameters"}->{"Version"} = 3;
            $data->{"Parameters"}->{"HitViewer"}->{"DocIndex"} = $params['performSearch']['hitViewer'];
            $data->{"Parameters"}->{"HitViewer"}->{"MultiColorHits"} = true;
        }

        $options = array(
            'http' => array(
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data)
            )
        );

        $context = stream_context_create($options);
        $url = 'https://api.searchcloudone.com/SearchMgr';
        $testMode = $wpdb->get_results("
		SELECT setting_value FROM " . $wpdb->prefix . "sc1debugsettings
		WHERE setting_key='testMode'");
        if (count ($testMode) > 0) {
            $url = 'https://testapi.searchcloudone.com/SearchMgr';
        }

        $result = file_get_contents($url, false, $context);
        if ($result === FALSE)
        {
            error_log('Error Serving Search Request: file_get_contents returned FALSE');
            header("HTTP/1.1 500 Internal Error");
            header('Content-Type: text/plain');
            echo('Something went wrong (Code 9)');
            exit();
        }

        // TODO - Figure out how to share one function between multiple php files to r/m boilerplate
        $httpCode = sc1_getHttpCodeTwo($http_response_header);

        if ($httpCode >= 200 && $httpCode < 300)
        {
            try
            {
                if ($hitViewer !== false)
                {
                    header('Content-Type: text/html');
                    $obj = json_decode($result);
                    $html = $obj->HitViewer->Html;
                    echo $html;
                    exit();
                }
                echo($result);
                exit();
            } catch (Exception $e)
            {
                error_log('Error Serving Search Request: ' . $e->getMessage());
                header("HTTP/1.1 500 Internal Error");
                header('Content-Type: text/plain');
                echo('Something went wrong (Code 11)');
                exit();
            }
        }
        else
        {
            return new WP_ERROR($httpCode . 'Could not fullfill request', $result,  array('status'=>$httpCode));
        }

    } catch (Exception $e) {
        error_log('Error Serving Search Request: ' . $e->getMessage());
        header("HTTP/1.1 500 Internal Error");
        header('Content-Type: text/plain');
        echo('Something went wrong (Code 10)');
        exit();
    }
}

// Generates hitviewers for requests on the /hitviewer endpoint
function sc1_hitviewer($request)
{
    $params = $request->get_params();
    $title = "Untitled Document";
    if (isset($params["title"]))
    {
        $title = urldecode($params["title"]);
    }
    $fileUUID = '[INVALID]';
    if (isset($params["fileUUID"])) {
        $fileUUID = $params["fileUUID"];
    }
    header('Content-Type: text/html');
    ?>
    <!DOCTYPE html>
    <html style="height:100%">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title><?php echo($title); ?></title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
        <script
                src="https://code.jquery.com/jquery-3.3.1.min.js"
                integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
                crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
        <style>
            iframe {
                transition: all .5s;
                top: 100%;
            }
        </style>
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.1/css/all.css" integrity="sha384-5sAR7xN1Nv6T6+dT2mhtzEpVJvfS3NScPQTrOxhwjIuvcA67KV2R5Jz6kr4abQsz" crossorigin="anonymous">
    </head>
    <body style="margin: 0; height: 100%; background-color: gray">
    <nav class="navbar navbar-expand navbar-light" style="background-color: rgb(246, 246, 246)">
        <div class="navbar-collapse collapse">
            <div id="hit-nav" class="navbar-nav" style="display: none">
                <a id="btnFirst" data-toggle="tooltip" title="First Hit" class="nav-item nav-link m-1" href="#">
                    <i class="fas fa-fast-backward"></i>
                </a>
                <a id="btnPrev" data-toggle="tooltip" title="Previous Hit" class="nav-item nav-link m-1" href="#">
                    <i class="fas fa-backward"></i>
                </a>
                <span class="nav-item">
                  <div class="form-inline">
                    Hit
                    <input id="tb_hitnum" type="text" class="form-control m-1" style="width:50px" placeholder="#" value="1">
                    /<span id="hit_count"></span>
                  </div>
                </span>
                <a id="btnNext" data-toggle="tooltip" title="Next Hit" class="nav-item nav-link m-1" href="#">
                    <i class="fas fa-forward"></i>
                </a>
                <a id="btnLast" data-toggle="tooltip" title="Last Hit" class="nav-item nav-link m-1" href="#">
                    <i class="fas fa-fast-forward"></i>
                </a>
            </div>

        </div>
        <div class="navbar-nav" style="float:right;">
            <a id="btnPrint" href="#" data-toggle="tooltip" title="Print Document" class="nav-item nav-link m-1"><i class="fas fa-print"></i></a>
            <span class="nav-item nav-link m-1" data-toggle="dropdown" title="More Options..."><i class="fas fa-ellipsis-v"></i></span>
            <div class="dropdown-menu dropdown-menu-right" style="float: right">
                <a class="dropdown-item" href="#" id="btn-download" data-fileuuid="<?php echo($fileUUID); ?>"><i class="fas fa-download"></i> Download Original</a>
                <a class="dropdown-item" id="btn-properties"><i class="fas fa-info-circle"></i> Properties</a>
            </div>
        </div>
    </nav>
    <div id="loading-container" style="width: 20px; height: 20px; position: absolute; top: 50%; left: 50%;">
        <i class="fas fa-spinner fa-pulse"></i>
    </div>
    <div id="iframe-container" style="position:absolute; left: 0px; right: 0px; top: 65px; bottom: 0px; overflow: hidden">
        <iframe id="docFrame" width="100%" height="100%" src="<?php echo $request['url']; ?>#hit_1" style="display:block; border:none; position: absolute;"></iframe>
    </div>
    <!-- Modal Dialog for File Properties -->
    <div id="filePropsModal" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Properties</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <ul class="list-group list-group-flush"><!-- File Properties go here --></ul>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        <?php
        include plugin_dir_path( __FILE__ ) . "public/js/hit_navigator.js";
        ?>
    </script>
    <script>
        <?php include plugin_dir_path( __FILE__ ) . "public/js/file-download.js.php"; ?>
    </script>
    <script>
        jQuery('#docFrame').on('load', function() {
            console.log('iframe loaded');
            var iframeHead = document.getElementById('docFrame').contentWindow.document.getElementsByTagName("head")[0];
            var scrollScript = document.createElement('script');
            scrollScript.setAttribute('type','text/javascript');
            scrollScript.setAttribute('src', '<?php echo plugin_dir_url( __FILE__ ) ?>/public/js/iframe-scroller.js');
            iframeHead.appendChild(scrollScript);

        });
    </script>
    <?php
    include plugin_dir_path( __FILE__ ) . "public/hitViewer_styler.php";
    ?>
    </body>
    </html>
    <?php
    exit();
}

// Generates searchpage for the /searchpage endpoint
function sc1_searchpage()
{
    header('Content-Type: text/html');
    include(plugin_dir_path(__FILE__). 'public/sc1-searchpage-bs3.php');
    exit();
}

// Called when the Categories page is requested in backend administration
function sc1_categories_page() {
    if (!get_transient('sc1-apiKey'))
    {
        wp_enqueue_script('jquery');
        include(plugin_dir_path(__FILE__)."admin/setup.php");
        exit();
    }
    include(plugin_dir_path( __FILE__ ) . 'admin/categories.php');
}

// Called when the Create New Category page is requested in backend administration
function sc1_createnew_category_page() {
    if (!get_transient('sc1-apiKey')) {
        wp_enqueue_script('jquery');
        include(plugin_dir_path(__FILE__)."admin/setup.php");
        exit();
    }
    include(plugin_dir_path( __FILE__ ) . 'admin/new_category.php');
}

function sc1_options_permissions() {
    if (! current_user_can('manage_options')) {
        return true;
    } else {
        return new WP_Error( 'rest_forbidden', 'You are not authorized to perform this action', array( 'status' => 401 ) );
    }

}

// Called when the user first sets up Search Cloud One - page prompts user to enter their API key.
function sc1_options_recieve(WP_REST_Request $data)
{
    include(plugin_dir_path( __FILE__ ) . 'admin/options_endpoint.php');
}

// Called when the SearchPages page is requested in backend administration
function sc1_searchpages_page() {
    if (!get_transient('sc1-apiKey')) {
        wp_enqueue_script('jquery');
        include(plugin_dir_path(__FILE__)."admin/setup.php");
        exit();
    }
    include(plugin_dir_path(__FILE__) . 'admin/shortcodes.php');
}


// Called when the create new searchpage is called in backend administration
function sc1_createnew_searchpage(){
    if (!get_transient('sc1-apiKey')) {
        wp_enqueue_script('jquery');
        include(plugin_dir_path(__FILE__)."admin/setup.php");
        exit();
    }
    include(plugin_dir_path(__FILE__) . 'admin/new-shortcode.php');
}

// Displays the quickstart page which displays useful setup information to the user.
function sc1_quickstart_page(){
    if (!get_transient('sc1-apiKey')) {
        wp_enqueue_script('jquery');
        include(plugin_dir_path(__FILE__)."admin/setup.php");
        exit();
    }
    include(plugin_dir_path( __FILE__ ) . 'admin/quickstart.php');
}

function sc1_is_db_installed()
{
    // TODO - On plugin upgrade, if the database has been updated need to check version and upgrade db as necessary..
    return false;
}

// Compares the current database version to the latest version and upgrades as necessary.
function sc1_db_perform_upgrades()
{
    // TODO, Check the version here and perform upgrades as necessary should the table change
}

// Creates the Debug Settings Table. Used to serve advanced users who need more granular control of functionality
function sc1_db_install_debug_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "sc1debugsettings";
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  setting_key varchar(60) NOT NULL,
  setting_value varchar(4096) NOT NULL,
  PRIMARY KEY  (id)
  ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function sc1_db_install_categoryFacets()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "sc1categoryfacets";
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
id mediumint(9) NOT NULL AUTO_INCREMENT,
category mediumint(9) NOT NULL,
field varchar(60) NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);
}

// Creates the SearchPages table if it does not already exist.
// SearchPages is a list of Search interfaces the user has created.
// Note, forward facing, this is actually now called 'Shortcodes'
function sc1_db_install_searchpages()
{
    global $wpdb;

    $table_name = $wpdb->prefix . "sc1searchpages";

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      name varchar(60) NOT NULL,
      shortcode varchar(60) NOT NULL,
      css varchar(2048) DEFAULT '/*Insert Custom CSS Rules Below*/' NOT NULL,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// Creates a table representing the index categories that have been assigned to searchpages
// Note: Forward facing, this is the categories used by the shortcode.
function sc1_db_install_searchpagecategories()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'sc1searchpagecategories';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      searchpageid mediumint(9) NOT NULL,
      categoryid mediumint(9) NOT NULL,
      PRIMARY KEY  (searchpageid,categoryid)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

}

// Creates a table representing the categories of indexes of documents created by the user.
function sc1_db_install_categories()
{
    global $wpdb;

    $table_name = $wpdb->prefix . "sc1categories";

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      name varchar(60) NOT NULL,
      opts varchar(2048) DEFAULT '' NOT NULL,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// Creates a table representing the indexes belonging to index categories.
function sc1_db_install_categoryidxs()
{
    global $wpdb;

    $table_name = $wpdb->prefix . "sc1categoryindexes";

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      indexid bigint(20) NOT NULL,
      indexuuid char(36) NOT NULL,
      categoryid mediumint(9) NOT NULL,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// Installs tables on the client's wordpress schema on their database.
// The tables are used to store configuration information for any search
// interfaces the user configures for searching documents on SC1.
function sc1_db_install()
{
    sc1_db_install_searchpages();
    sc1_db_install_categories();
    sc1_db_install_categoryidxs();
    sc1_db_install_searchpagecategories();
    sc1_db_install_debug_table();
    sc1_db_install_categoryFacets();
}

// TODO - Currently does nothing
function sc1_db_uninstall()
{
    // TODO
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-sc1-search-deactivator.php
 */
function sc1_deactivate_sc1_search()  {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-sc1-search-deactivator.php';
    Sc1_Search_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'sc1_activate_sc1_search' );
register_deactivation_hook( __FILE__, 'sc1_deactivate_sc1_search' );

add_action('admin_notices','sc1_admin_notices');


// This is called when wordpress is looking for notices to show globally throughout
// the admin backend including pages not belonging to this plugin. We're just going
// to use it on plugin installation to verify plugin success or tell the user if
// the installation was botched for any reason.
function sc1_admin_notices() {
    if (get_transient('sc1-display-admin-notice'))
    {
        if (get_transient('sc1-install-success'))
        {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>Search Cloud One is installed. Click <a href="admin.php?page=SearchCloudOne/Quickstart.php">here</a> to get started!</p>
            </div>
            <?php
        }
        else
        {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>Installation Failed! Click <a href="https://www.searchcloudone.com/Help">here</a> to troubleshoot.</p>
            </div>
            <?php
        }
        // We've displayed our notice, now clean up
        delete_transient('sc1-install-success');
        delete_transient('sc1-display-admin-notice');
    }
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-sc1-search.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_sc1_search() {

    $plugin = new Sc1_Search();
    $plugin->run();

}

// TODO figure out how to share this function
function sc1_getHttpCodeTwo($http_response_header)
{
    if(is_array($http_response_header))
    {
        $parts=explode(' ',$http_response_header[0]);
        if(count($parts)>1) //HTTP/1.0 <code> <text>
            return intval($parts[1]); //Get code
    }
    return 0;
}

run_sc1_search();
