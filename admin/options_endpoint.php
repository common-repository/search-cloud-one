<?php
// Expects $data to be passed in.
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;
$params = $data->get_json_params();

if (array_key_exists('debug_settings', $params))
{
	header("Content-Type: application/json; charset=UTF-8");
	if (is_array($params['debug_settings']))
	{
		$table  = $wpdb->prefix . 'sc1debugsettings';
		// drop all the debug settings
		$delete = $wpdb->query("DELETE FROM $table WHERE 1=1");
		$settings = $params['debug_settings'];
		foreach($settings as $setting) {
			$key = $setting['key'];
			$value = $setting['value'];

			$wpdb->insert($table, array(
				'setting_key' => $key,
				'setting_value' => $value
			));
		}

		wp_send_json_success();
		return;
	}
}

if (array_key_exists('resetPlugin',$params))
{
	// Request is to reset the plugin
	$tables 	= [];
	$tables[] 	= $wpdb->prefix . 'sc1categories';
	$tables[] 	= $wpdb->prefix . 'sc1categoryindexes';
	$tables[] 	= $wpdb->prefix . 'sc1debugsettings';
	$tables[] 	= $wpdb->prefix . 'sc1searchpagecategories';
	$tables[] 	= $wpdb->prefix . 'sc1searchpages';

	foreach ($tables as $table)
	{
		$wpdb->query("DELETE FROM $table WHERE 1=1");
	}

	delete_transient('sc1-apiKey');
	wp_send_json_success();
	return;
}

if (array_key_exists('apiKey',$params))
{
    // Request is to set the API Key this plugin should be using
    $apiKey = $params['apiKey'];
    if (sc1_is_apiKey_valid($apiKey))
    {
        // apiKey is valid, so we save it and send a success response to let the UI proceed to the user's configuration page
        set_transient('sc1-apiKey',$params['apiKey'],0);
        if ($apiKey === "91dcca63-1842-4852-8347-c4594e2c2299")
        {
            set_transient('demoMode','true', 0);
        }
        else
        {
            set_transient('demoMode','false',0);
        }

        wp_send_json_success();
        return;
    }
    else
    {
        // apiKey is not valid for one reason or another.
        header("HTTP/1.1 500 Internal Error");
        $response = new stdClass();
        $response->success = false;
        $response->message = "API Key Invalid";
        echo json_encode($response);
        exit;
    }
}

if (array_key_exists('newSearchPage',$params))
{
    $name = $params['newSearchPage']['name'];
    $shortcode = $params['newSearchPage']['shortcode'];
    $categoryids = $params['newSearchPage']['categoryids'];

    $wpdb->query('START TRANSACTION');
    $created_page =
        $wpdb->insert($wpdb->prefix . 'sc1searchpages',
                  array(
                    'name' => $name,
                    'shortcode' => $shortcode
                  )
    );
    if ($created_page == false)
    {
        $wpdb->query('ROLLBACK');
        header("HTTP/1.1 500 Internal Error");
        header('Content-Type: text/plain');
        echo('Something went wrong (Code 6)');
        exit;
    }

    $pageid = $wpdb->insert_id;
    foreach($categoryids as $categoryid)
    {
        $created_page_category_assoc =
        $wpdb->insert($wpdb->prefix . 'sc1searchpagecategories',
                     array(
                         'searchpageid' => $pageid,
                         'categoryid' => $categoryid,
                     )
        );

        if ($created_page_category_assoc == false)
        {
            $wpdb->query('ROLLBACK');
            header("HTTP/1.1 500 Internal Error");
            header('Content-Type: text/plain');
            echo('Something went wrong (Code 6)');
            exit;
        }
    }
    $wpdb->query('COMMIT');
    header("HTTP/1.1 200 OK");
    header('Content-Type: text/plain');
    echo('Success');
    exit;


}

if (array_key_exists('deletePage',$params))
{
    // Request is to delete a search page.
    global $wpdb;
    $wpdb->query('START TRANSACTION');
    $pageid = $params['deletePage']['pageid'];
    $result = $wpdb->delete($wpdb->prefix . 'sc1searchpages',
                           array('id'=>$pageid),array('%d'));
    $wpdb->query('COMMIT');
    header("HTTP/1.1 200 OK");
    header('Content-Type: text/plain');
    echo('Deleted Successfully');
    exit;
}

if (array_key_exists('deleteCategory',$params))
{
    // Some notes:
    // When a user wants to delete an index category, this is actually doing several things
    //
    //  1. De-assigning the categorys from Search Pages
    //
    //      >   Categorys in the sc1searchpagecategories mapped to the searchpage will be removed.
    //
    //      >   Note that, this might be the only category assigned to the search page,
    //          In this case, the user needs to be prompted to either delete the search page
    //          or add replacement categorys to the search page.
    //
    //  2. Deleting from the categoryIndexes table
    //
    //      >   Indexes in the sc1categoryindexes table that are mapped to the category will be removed.
    //
    //  3. Deleting from the Index Categorys table.
    //
    //      >   The Index Category record itself needs to be deleted from the sc1categories table.
    //
    global $wpdb;
    $wpdb->query('START TRANSACTION');
    $categoryid = $params['deleteCategory']['categoryid'];

    // 1 deassign from searchpages
    $result = $wpdb->delete($wpdb->prefix . 'sc1searchpagecategories',
                           array( 'categoryid' => $categoryid), array('%d'));
    if ($result === false) // rollback 500
    {
        $wpdb->query('ROLLBACK');
        header("HTTP/1.1 500 Internal Error");
        header('Content-Type: text/plain');
        echo('Something went wrong (Code 3)');
        exit;
    }
    // 2 delete from categoryindexes
    $result = $wpdb->delete( $wpdb->prefix . 'sc1categoryindexes',
                           array( 'categoryid' => $categoryid), array('%d'));
    if ($result === false) // rollback 500
    {
        $wpdb->query('ROLLBACK');
        header("HTTP/1.1 500 Internal Error");
        header('Content-Type: text/plain');
        echo('Something went wrong (Code 4)');
        exit;
    }
    // 3 delete from indexcategorys
    $result = $wpdb->delete( $wpdb->prefix . 'sc1categories',
                           array( 'id' => $categoryid), array('%d'));
    if ($result === false) // rollback 500
    {
        $wpdb->query('ROLLBACK');
        header("HTTP/1.1 500 Internal Error");
        header('Content-Type: text/plain');
        echo('Something went wrong (Code 4)');
        exit;
    }
    $wpdb->query('COMMIT');
    header("HTTP/1.1 200 OK");
    header('Content-Type: text/plain');
    echo('Deleted Successfully');
    exit;

}

if (array_key_exists('createCategory',$params))
{
    //  -- Some Notes: --
    //
    //  Index Categories require the following information:
    //
    //      >   Category Name: Identifies the category of indexes.
    //
    //          Category names are displayed on the front-end search
    //          interface when more than one index category is assigned
    //          to a search page. User's will be presented these categorys
    //          as a drop down list. Can be referred to as 'category searching'
    //
    //      >   Indexes: What this category searches across
    //
    //          An array of one or more indexes, each containing IndexID and IndexUUID
    //          Note that currently, if the selected Indexes were of differing Index
    //          Technology, this would cause a front-end crash as our servers do not
    //          currently support searching across different index technologies.
    //
    //
    //      >   Options: A list of extra filters that the user can choose, when this category
    //          is selected.
    //
    //          Note that filters might exclude documents if several indexes are selected and
    //          one index does not have a matching field name used in the filter.
    $name = $params['createCategory']['name'];
    $indexes = $params['createCategory']['indexes'];
    $options = $params['createCategory']['options'];

    if (count($indexes) == 0)
    {
        header("HTTP/1.1 400 Client Error");
        header('Content-Type: text/plain');
        echo('Indexes not passed or empty');
        exit;
    }
    global $wpdb;
    $wpdb->query('START TRANSACTION');
    $created_category =
        $wpdb->insert($wpdb->prefix . 'sc1categories',
                  array(
                    'name' => $name,
                    'opts' => json_encode($options),
                  )
    );

    if ($created_category == false)
    {
        $wpdb->query('ROLLBACK');
        header("HTTP/1.1 500 Internal Error");
        header('Content-Type: text/plain');
        echo('Something went wrong (Code 1)');
        exit;
    }

    $categoryid = $wpdb->insert_id;

    foreach($indexes as $index)
    {
        $array = explode(":",$index);
        $indexid = $array[0];
        $indexuuid = $array[1];
        $inserted_index =
            $wpdb->insert($wpdb->prefix . 'sc1categoryindexes',
                         array (
                            'indexid' => $indexid,
                            'indexuuid' => $indexuuid,
                            'categoryid' => $categoryid,
                         )
        );

        if ($inserted_index == false)
        {
            $wpdb->query('ROLLBACK');
            header("HTTP/1.1 500 Internal Error");
            header('Content-Type: text/plain');
            echo('Something went wrong (Code 2)');
            exit;
        }
    }

    $facets  = $params['createCategory']['facets'];
    foreach ($facets as $facet) {
        $inserted_facet = $wpdb->insert($wpdb->prefix . 'sc1categoryfacets',
            array (
                'category' => $categoryid,
                'field' => $facet,
            )
        );

        if ($inserted_facet == false)
        {
            $wpdb->query("ROLLBACK");
            header("HTTP/1.1 500 Internal Error");
            header('Content-Type: text/plain');
            echo('Something went wrong (Code 3)');
            exit;
        }
    }

    $wpdb->query('COMMIT');
    echo json_encode($response);
    header("HTTP/1.1 200 OK");
    header('Content-Type: text/plain');
    exit;
}


$response = new stdClass();
$response->success = false;
$response->message = "Missing Information";
echo json_encode($response);
header("HTTP/1.1 400 Client Error");
exit;

// Check against SearchCloudOne server to ensure the API Key passed is valid for this server to use.
function sc1_is_apiKey_valid($apiKey)
{
    // basic validation - is the string 36 characters in length?
    if (strlen($apiKey) != 36)
    {
        return false;
    }
    // advanced validation - does the server respond with a 200 for a list indexes request with this key?
    $data = array("APIKey" => $apiKey, "Action" => "ListIndexes", "Activation" => true, "Version" => SEARCH_CLOUD_ONE_VERSION);
    $options = array(
        'http' => array(
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data)
        )
    );
    $context = stream_context_create($options);
    $result = @file_get_contents('https://api.searchcloudone.com/IndexManager', false, $context);
    $httpCode = sc1_getHttpCode($http_response_header);
    if ($httpCode >= 200 && $httpCode < 300)
    {
        return true;
    }
    if ($httpCode >= 400 && $httpCode < 500)
    {
        return false;
    }
    // Following normally shouldn't happen - indicates a problem on SearchCloudOne side.
    throw new Exception('Unrecognized status code whilst checking api key validity - Possible internal server error? ' . $httpCode);
}

// Get the http status code from the response header of a file_get_contents request
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
