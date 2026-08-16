<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Seller API security
|--------------------------------------------------------------------------
|
| The seller app API authenticates with a SHARED, APP-LEVEL JWT (see
| Api::verify_token). That token proves "a legitimate build of the app is calling",
| and nothing more - it carries no user identity. Every endpoint therefore takes the
| user_id it acts on straight from the POST body.
|
| For read endpoints that is a data-exposure problem. For the withdrawal endpoints it
| is worse: anybody holding the app key - which ships inside the mobile app and can be
| extracted from it - can post ANOTHER user's user_id together with their own payment
| address, and drain that user's wallet balance to themselves.
|
| Fixing this properly needs per-user authentication (a token issued at login and
| verified on every request), which changes the app's API contract and so cannot be
| done from the server alone. Until that exists, the money endpoints fail closed.
|
| The seller web panel is unaffected: it authenticates with a real session and always
| uses the logged-in seller's own id.
*/

/*
| Allow POST /seller/app/v1/api/send_withdrawal_request and get_withdrawal_request.
|
| These are now protected by a PER-USER token (users.apikey), issued by the seller API's
| login endpoint and returned as `api_token` in the login response. Both endpoints require
| it and check it against the user_id being acted on, so a caller holding only the shared
| app key can no longer act as another user.
|
| THE MOBILE APP MUST BE UPDATED: store `api_token` from the login response and send it as
| an `api_token` POST field on both endpoints. Until the app does that, these calls will be
| refused with "Authentication required. Please sign in again." - which is the safe failure,
| not a regression: before this they were disabled outright.
|
| Set to FALSE to disable the endpoints entirely again.
*/
$config['allow_api_withdrawal_requests'] = true;
