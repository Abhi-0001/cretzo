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

/*
| Allow POST /app/v1/api/send_withdrawal_request (the CUSTOMER wallet withdrawal).
|
| Same class of hole as the seller endpoint above, and it was wide open: no per-user check
| at all, user_id taken from the POST body, so any caller holding the shared app key could
| withdraw another customer's wallet balance to their own payment address. (The website's
| own withdraw_money() had the same defect and additionally no login check whatsoever -
| both now route through Payment_request_model::create_withdrawal_request().)
|
| Defaults to FALSE, unlike the seller flag, because the customer app cannot satisfy the
| per-user check yet: the token is only issued as of this change, so no released build sends
| it. Leaving this false refuses the call with "not available through the app yet"; flipping
| it to true without an updated app would refuse with "Authentication required" instead.
| Neither loses money - and customers can still withdraw on the website, which uses a real
| session and the logged-in user's own id.
|
| TO ENABLE: update the customer app to store `api_token` from the login response and send
| it as an `api_token` POST field on this endpoint, then set this to TRUE.
*/
$config['allow_customer_api_withdrawal_requests'] = false;

/*
|--------------------------------------------------------------------------
| Public token generation
|--------------------------------------------------------------------------
|
| Allow the unauthenticated generate_token() endpoints:
|
|   GET  /app/v1/api/generate_token
|   GET  /seller/app/v1/api/generate_token
|   GET  /admin/app/v1/api/generate_token
|   GET  /delivery_boy/app/v1/api/generate_token
|   GET  /app/v1/chat_api/generate_token
|   GET  /seller/app/v1/chat_api/generate_token
|
| These required NOTHING - no session, no existing key, no rate limit - and returned
| a JWT signed with the JWT_SECRET_KEY constant. That constant was hardcoded in the
| git-tracked application/config/constants.php, so it is public.
|
| Whether that made this a full API bypass hinges on one question, which has to be
| answered against the production database:
|
|     SELECT id, name, status FROM client_api_keys
|      WHERE secret = '<the old JWT literal from git history>';
|
| verify_token() accepts a JWT if it validates against ANY active row in that table.
| If the published constant is one of those secrets, then generate_token() was
| minting valid API credentials for anybody who requested the URL. If it is not, the
| endpoint was issuing tokens that verify_token() would have rejected anyway - inert,
| but still no reason to keep.
|
| Defaults to FALSE either way. A released mobile app carries its own key and does
| not call this; the endpoint is a development convenience. Leaving it off is the
| safe failure: a caller gets 404 rather than a credential.
|
| TO RE-ENABLE (only if a shipped app build turns out to depend on it): set this to
| true AND set a fresh JWT_SECRET_KEY environment variable AND rotate the affected
| client_api_keys row, because the old secret is published.
*/
$config['allow_public_token_generation'] = false;

/*
|--------------------------------------------------------------------------
| Per-user identity on the customer mobile API
|--------------------------------------------------------------------------
|
| THE PROBLEM. verify_token() proves that a legitimate build of the app is calling.
| It proves nothing about WHO is calling: the app key is shared by every install and
| can be extracted from the APK. Every endpoint then reads the user_id it acts on
| from the POST body.
|
| So one extracted key was enough to read and modify ANY customer's orders,
| addresses, cart, favourites, notifications, support tickets and transaction
| history, and to place orders on their account. That is ~100 reads of
| $_POST['user_id'] across app/v1/Api.php, none of them verified. The two withdrawal
| endpoints were fixed in an earlier pass (see the flags above); nothing else was.
|
| THE FIX. A per-user token already exists and is already issued: users.apikey,
| returned as `api_token` in the login response. Api::require_user_identity() now
| guards the 33 endpoints that act on one named user, and compares that token against
| the user_id in the request with hash_equals().
|
| WHY THIS FLAG. The fix needs the CLIENT to cooperate - the app must store
| `api_token` at login and send it on every later request - and released builds do
| not. Enforcing before an app release ships would take the mobile app offline for
| every existing user.
|
| So the three states are:
|
|   flag FALSE (default)  A call WITH a valid token proceeds.
|                         A call with a WRONG token is refused - that is never a
|                         legacy client.
|                         A call with NO token is LOGGED and allowed.
|
|   flag TRUE             A call with no token is refused as well.
|
| HOW TO KNOW WHEN TO FLIP IT. In the default state every tokenless call writes a
| line to application/logs beginning:
|
|     require_user_identity: LEGACY CALL - no api_token sent for user_id ...
|
| Ship the app update, then watch that line. When it stops appearing for a few days,
| every live client is sending a token and this can be set to true with no outage. If
| it never stops, the log names the endpoint and the user, which tells you which build
| is still out there.
|
| Do not leave this false indefinitely. Until it is true, the hole is open - the
| logging makes it visible, not closed.
*/
$config['enforce_api_user_identity'] = false;
