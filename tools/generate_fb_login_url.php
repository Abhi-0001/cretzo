<?php
require __DIR__ . '/../vendor/autoload.php';

use Facebook\Facebook;

$appId = '1541338137599309';
$appSecret = '42d7c2bed5fc50f9f6c6906e5053084f';
$redirect = 'http://localhost/cretzo/index.php/auth/facebook_callback';

$fb = new Facebook([
    'app_id' => $appId,
    'app_secret' => $appSecret,
    'default_graph_version' => 'v18.0',
]);

$helper = $fb->getRedirectLoginHelper();
$permissions = ['email', 'public_profile'];
$loginUrl = $helper->getLoginUrl($redirect, $permissions);

echo "Facebook Login URL (open in browser):\n" . (string)$loginUrl . "\n";
echo "Redirect URI used: $redirect\n";

// Short curl-friendly output
echo "\nCurl test (follow redirect):\n";
echo "curl -i -L '" . (string)$loginUrl . "'\n";

?>
