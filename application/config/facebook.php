<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Facebook OAuth configuration
$config['facebook_app_id']        = (defined('FACEBOOK_APP_ID') ? FACEBOOK_APP_ID : '1541338137599309'); // replace with your App ID or set FACEBOOK_APP_ID constant
$config['facebook_app_secret']    = (defined('FACEBOOK_APP_SECRET') ? FACEBOOK_APP_SECRET : (getenv('FACEBOOK_APP_SECRET') ?: 'YOUR_APP_SECRET')); // replace with your App Secret or set FACEBOOK_APP_SECRET env/constant
$config['facebook_redirect']      = base_url('index.php/auth/facebook_callback');
$config['facebook_permissions']   = ['email', 'public_profile'];
$config['facebook_graph_version'] = 'v18.0';

/*
Notes:
- Update 'facebook_app_id' and 'facebook_app_secret' with real values.
- Ensure this redirect URI exactly matches the one in Facebook App > Facebook Login > Valid OAuth Redirect URIs.
*/
