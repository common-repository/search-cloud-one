<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Renders the PDF HitViewer in full screen.
 */
// Step 1, get a Download Token for the given PDF File so that we don't have to expose our API Key on the frontend.
$params = $request->get_params();
$apiKey = get_transient('sc1-apiKey');
if (isset($params["title"]))
{
    $title = urldecode($params["title"]);
}
$fileUUID = '[INVALID]';
if (isset($params["fileUUID"])) {
    $fileUUID = $params["fileUUID"];
}
global $wpdb;
$testMode = $wpdb->get_results("
		SELECT setting_value FROM " . $wpdb->prefix . "sc1debugsettings
		WHERE setting_key='testMode'");
if (count ($testMode) > 0) {
    $url = 'https://testapi.searchcloudone.com/SearchMgr';
}
$reqUrl = 'https://api.searchcloudone.com/OneUseTokens?APIKey=' . $apiKey . '&Action=ReadFile&Resource=' . $fileUUID;
if ($testMode) {
    $reqUrl = 'https://testapi.searchcloudone.com/OneUseTokens?APIKey=' . $apiKey . '&Action=ReadFile&Resource=' . $fileUUID;
}
$context = stream_context_create(array(
    'http' => array(
        'ignore_errors' => true
    )
));
$token = file_get_contents($reqUrl, false, $context);
$httpCode = sc1_getHttpCodeTwo($http_response_header);
if ($httpCode < 200 && $httpCode >= 300) {
    // Failed to get token.
    // Something went wrong
    header("HTTP/1.1 500 Internal Error");
    header('Content-Type: text/plain');
    echo('Something went wrong');
    exit();
}
$fileResource = urlencode('/Files?FileUUID=' . $fileUUID . '&Token=' . $token);
$iframeSrc = 'https://api.searchcloudone.com/pdf/viewer.html?file=' . $fileResource;
if ($testMode) {
    $iframeSrc = 'https://testapi.searchcloudone.com/pdf/viewer.html?file=' . $fileResource;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo($title); ?></title>
    <script
        src="https://code.jquery.com/jquery-3.3.1.min.js"
        integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
        crossorigin="anonymous"></script>
</head>
<body>
yaya
<script>
    function receiveMessage(event) {
        console.info('received message:');
        console.info(event);
    }
    addEventListener("message", receiveMessage, false);
    console.info('hello');
    let frame = $('#pdf-frame');
    console.log(frame);
    frame.attr('src',"<?php echo $iframeSrc; ?>");
    console.info('yes i got this far');
</script>
FILE
<?php echo $fileUUID; ?>
eNDFILE
TOKEN
<?php echo $token; ?>
ENDTOKEN
SOURCE
<?php echo $iframeSrc; ?>
ENDSOURCE
<iframe id="pdf-frame" style="width: 100%; height: 100%" src="<?php echo $iframeSrc; ?>">
</iframe>
</body>
</html>

