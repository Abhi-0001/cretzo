<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|==============================================================================
| Twilio credentials - READ FROM THE ENVIRONMENT
|==============================================================================
|
| Used by the seller-signup mobile OTP flow (seller/Auth.php send_otp()/verify_otp()).
|
| These were hardcoded here as literals:
|
|     $config['sid']   = '<the account SID>';
|     $config['token'] = '<a 32-char hex literal - see git history>';
|
| and the comment that used to sit above them already said the right thing - that a
| credential committed to git is compromised regardless of later removal, and that
| these should come from the environment instead. That never happened, so they stayed
| in a tracked file through every commit since.
|
| WHY THIS ONE MATTERS more than it looks: a Twilio auth token is not just read
| access. Whoever holds it can send SMS billed to this account, from this account's
| number. That is a direct cost, and it is a ready-made phishing channel - messages
| arriving from the number Cretzo's own OTPs come from.
|
| ------------------------------------------------------------------------------
| WHAT TO DO
| ------------------------------------------------------------------------------
|  1. Rotate the auth token in the Twilio console (Account > API keys & tokens).
|     The SID is an account identifier rather than a secret, but rotate the token.
|  2. Put the new values in the untracked `.env` file (see .env.example):
|
|         TWILIO_SID=ACxxxxxxxx
|         TWILIO_TOKEN=xxxxxxxx
|         TWILIO_FROM_NUMBER=+1xxxxxxxxxx
|
|  3. Check the Twilio usage log for messages you did not send.
|
| Unset means the OTP send fails and logs why, rather than silently using a
| published token - see the guard in seller/Auth.php::send_otp(). That is the safe
| failure: this site's live OTP channel is Firebase (authentication_settings is
| {"authentication_method":"firebase"}), so the Twilio path is a fallback that is not
| currently the primary route for anyone.
*/

$config['sid'] = getenv('TWILIO_SID') ?: '';
$config['token'] = getenv('TWILIO_TOKEN') ?: '';
$config['from_number'] = getenv('TWILIO_FROM_NUMBER') ?: '';
