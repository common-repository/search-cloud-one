<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/*
API Key Setup Page
*/
?>
<script>
function sc1_submit_apiKey(btn) {
    jQuery('#sc1_apiErrors').html(" ");
    var apiKey = jQuery('#sc1-input-apiKey')[0].value.trim();
    // very basic validation
    if (apiKey.length != 36) {
        jQuery('#sc1_apiErrors').html("Invalid API Key");
        return;
    }
    set_key(apiKey)
}

function set_key(apiKey) {
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
            console.error('Failed to set API Key');
            console.error(data);
            try {
                // In the event of a server error, responseJSON might cause null ref exception, but normally would send a human readable message.
                jQuery('#sc1_apiErrors').html('Error: ' + data.responseJSON.message + " <br>Please try again.");
            } catch (e) {
                jQuery('#sc1_apiErrors').html('Something went wrong.<br> Please check your connection and try again');
            }
        }
    });
}
</script>
<div class="wrap sc1-admin">
    <h1>Connect to your Search Cloud One account</h1>
    <p>Please go to the API Page on your
        <a target="_blank" href="https://www.searchcloudone.com/console?tab=api">Search Cloud One Console</a> and click <i>Wordpress Setup</i> to get your API Key.</p>
    <p>Paste the key below to continue</p>
    <input type="text" class="regular-text" id="sc1-input-apiKey">
    <button class="button button-primary" onclick="sc1_submit_apiKey(this)">Submit</button>
    <p id="sc1_apiErrors" style="color:red"><!-- Error Messages go here --></p>
    <p>Or, try the demo:</p>
    <button class="button button-primary" onclick="set_key('91dcca63-1842-4852-8347-c4594e2c2299');">Demo</button>
</div>
