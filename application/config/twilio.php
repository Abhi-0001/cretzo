<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| Twilio credentials for seller-signup mobile OTP verification
| (application/controllers/seller/Auth.php send_otp()/verify_otp()).
|
| These are the same credentials that were previously hardcoded directly in
| seller/Auth.php and committed to source control. Moving them here does not
| by itself make them safe - a credential that has ever been committed to git
| history is compromised regardless of later removal. ROTATE these in the
| Twilio console and replace the values below (ideally via environment
| variables / a git-ignored file rather than a committed config file).
*/
$config['sid'] = 'AC98662e8c1491ef426a93b295856918dc';
$config['token'] = '27acec586b41c090c0f71690425e1341';
$config['from_number'] = '+18573424919';
