<?php
defined('BASEPATH') or exit('No direct script access allowed');
defined('JWT_SECRET_KEY') or define('JWT_SECRET_KEY', '68f05dec6014f68e760c5c5fa3e31bcf391a2e10');

/*
|--------------------------------------------------------------------------
| Social Authentication Configuration
|--------------------------------------------------------------------------
|
| Facebook and other social authentication API credentials
|
*/
defined('FACEBOOK_APP_ID') or define('FACEBOOK_APP_ID', '1541338137599309');
defined('FACEBOOK_APP_SECRET') or define('FACEBOOK_APP_SECRET', '42d7c2bed5fc50f9f6c6906e5053084f');
/*
|--------------------------------------------------------------------------
| Display Debug backtrace
|--------------------------------------------------------------------------
|
| If set to TRUE, a backtrace will be displayed along with php errors. If
| error_reporting is disabled, the backtrace will not display, regardless
| of this setting
|
*/
defined('SHOW_DEBUG_BACKTRACE') or define('SHOW_DEBUG_BACKTRACE', FALSE);

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
defined('FILE_READ_MODE')  or define('FILE_READ_MODE', 0644);
defined('FILE_WRITE_MODE') or define('FILE_WRITE_MODE', 0666);
defined('DIR_READ_MODE')   or define('DIR_READ_MODE', 0755);
defined('DIR_WRITE_MODE')  or define('DIR_WRITE_MODE', 0755);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/
defined('FOPEN_READ')                           or define('FOPEN_READ', 'rb');
defined('FOPEN_READ_WRITE')                     or define('FOPEN_READ_WRITE', 'r+b');
defined('FOPEN_WRITE_CREATE_DESTRUCTIVE')       or define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
defined('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE')  or define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
defined('FOPEN_WRITE_CREATE')                   or define('FOPEN_WRITE_CREATE', 'ab');
defined('FOPEN_READ_WRITE_CREATE')              or define('FOPEN_READ_WRITE_CREATE', 'a+b');
defined('FOPEN_WRITE_CREATE_STRICT')            or define('FOPEN_WRITE_CREATE_STRICT', 'xb');
defined('FOPEN_READ_WRITE_CREATE_STRICT')       or define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

/*
|--------------------------------------------------------------------------
| Exit Status Codes
|--------------------------------------------------------------------------
|
| Used to indicate the conditions under which the script is exit()ing.
| While there is no universal standard for error codes, there are some
| broad conventions.  Three such conventions are mentioned below, for
| those who wish to make use of them.  The CodeIgniter defaults were
| chosen for the least overlap with these conventions, while still
| leaving room for others to be defined in future versions and user
| applications.
|
| The three main conventions used for determining exit status codes
| are as follows:
|
|    Standard C/C++ Library (stdlibc):
|       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
|       (This link also contains other GNU-specific conventions)
|    BSD sysexits.h:
|       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
|    Bash scripting:
|       http://tldp.org/LDP/abs/html/exitcodes.html
|
*/
defined('EXIT_SUCCESS')        or define('EXIT_SUCCESS', 0); // no errors
defined('EXIT_ERROR')          or define('EXIT_ERROR', 1); // generic error
defined('EXIT_CONFIG')         or define('EXIT_CONFIG', 3); // configuration error
defined('EXIT_UNKNOWN_FILE')   or define('EXIT_UNKNOWN_FILE', 4); // file not found
defined('EXIT_UNKNOWN_CLASS')  or define('EXIT_UNKNOWN_CLASS', 5); // unknown class
defined('EXIT_UNKNOWN_METHOD') or define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     or define('EXIT_USER_INPUT', 7); // invalid user input
defined('EXIT_DATABASE')       or define('EXIT_DATABASE', 8); // database error
defined('EXIT__AUTO_MIN')      or define('EXIT__AUTO_MIN', 9); // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      or define('EXIT__AUTO_MAX', 125); // highest automatically-assigned error code

// Custom Constant Variables
define('FORMS', 'forms/');

// define('USER_DATA',$allow_modification);
// define('ALLOW_MODIFICATION',0);
define('IS_ALLOWED_MODIFICATION',1);
define('DEMO_VERSION_MSG', 'Modification in demo version is not allowed');
define('SEMI_DEMO_MODE', 1);

if (!function_exists('detect_app_url')) {
    function detect_app_url() {
        $is_https = (
            (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
        );

        // Hosts this app may answer as. HTTP_HOST is client-controlled, so an
        // unrecognised value falls back to the canonical domain rather than being
        // echoed into every generated link (password-reset URLs included).
        // X-Forwarded-Host is deliberately NOT trusted: no reverse proxy in front
        // of this app overwrites it, so it arrives straight from the client.
        $allowed = ['cretzo.com', 'www.cretzo.com', 'localhost', '127.0.0.1'];

        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? $allowed[0];
        // The port is preserved on purpose - staging and Docker serve on
        // non-default ports, and stripping it breaks every asset URL there.
        if (!in_array(preg_replace('/:\d+$/', '', $host), $allowed, TRUE)) {
            $host = $allowed[0];
        }

        $script_name = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $base_path = str_replace(basename($script_name), '', $script_name);
        $base_path = rtrim($base_path, '/');

        return ($is_https ? 'https' : 'http') . '://' . $host . $base_path . '/';
    }
}

define('APP_URL', detect_app_url());
define('SEMI_DEMO_MODE_MSG', 'Modification in semi demo version is not allowed');
define('TABLES', 'tables/');
define('VIEW', 'view/');
define('CATEGORY_IMG_PATH', 'uploads/category_image/');
define('SUBCATEGORY_IMG_PATH', 'uploads/subcategory_image/');
define('PRODUCT_IMG_PATH', 'uploads/product_image/');
define('SLIDER_IMG_PATH', 'uploads/slider_image/');
define('OFFER_IMG_PATH', 'uploads/offer_image/');
define('NOTIFICATION_IMG_PATH', 'uploads/notifications/');
define('USER_IMG_PATH', 'uploads/user_image/');
define('UPDATE_PATH', 'update/');
define('MEDIA_PATH', 'uploads/media/');
define('CHAT_MEDIA_PATH', 'uploads/chat_media/');
define('NO_IMAGE', 'assets/no-image.png');
define('NO_USER_IMAGE', 'assets/no-user-img.png');
define('EMAIL_ORDER_SUCCESS_IMG_PATH', 'assets/admin/images/order-success.png');
define('REVIEW_IMG_PATH', 'uploads/review_image/');
define('TICKET_IMG_PATH', 'uploads/tickets/');
define('DIRECT_BANK_TRANSFER_IMG_PATH', 'uploads/bank_transfer/');
define('SELLER_DOCUMENTS_PATH', 'uploads/seller/');
define('DELIVERY_BOY_DOCUMENTS_PATH', 'uploads/delivery_boy/');
define('ORDER_ATTACHMENTS', 'uploads/order_attachments/');
define('APP_CODE', '34108271');
define('WEB_CODE', '34380052');

//Thumbnail paths
define('THUMB_MD', 'thumb-md/');
define('THUMB_SM', 'thumb-sm/');
define('CROPPED_MD', 'cropped-md/');
define('CROPPED_SM', 'cropped-sm/');

define('PERMISSION_ERROR_MSG', ' You are not authorize to operate on the module ');

// ticket status 
define('PENDING', '1');
define('OPENED', '2');
define('RESOLVED', '3');
define('CLOSED', '4');
define('REOPEN', '5');

// direct bank transfer

define('BANK_TRANSFER', 'Direct Bank Transfer');

// pincode delivarable type

define('NONE', '0');
define('ALL', '1');
define('INCLUDED', '2');
define('EXCLUDED', '3');
// Layout of the seller "simple" bulk upload sheet: a block of product columns followed by one
// repeating block of variant columns. A valid file is SIMPLE_FIXED_COLUMNS + (n * SIMPLE_VARIANT_COLUMNS)
// wide. The settings that used to be numeric-coded columns (cod_allowed, is_returnable,
// deliverable_type, indicator, is_prices_inclusive_tax) are written as words in columns 20-25,
// pre-filled by the template the upload page generates from its own inputs, so a seller never has
// to type or decode them. A blank cell falls back to the form's setting.
// 26 -> 25: the cancellable_until column is gone. Cancellation is an admin policy per product,
// not a seller setting, so the seller sheet no longer carries it (the admin sheet still does).
// A template downloaded before that change is one column too wide and has to be re-downloaded.
defined("SIMPLE_FIXED_COLUMNS") || define("SIMPLE_FIXED_COLUMNS", 25);
defined("SIMPLE_VARIANT_COLUMNS") || define("SIMPLE_VARIANT_COLUMNS", 5);
// The admin sheet is the same layout with seller_id as its first column, because an admin is
// importing on behalf of a seller rather than as one.
defined("ADMIN_SIMPLE_FIXED_COLUMNS") || define("ADMIN_SIMPLE_FIXED_COLUMNS", 27);
// The update sheet leads with product_id instead of seller_id (an update never changes who owns a
// product) and each variant block leads with the variant_id it is updating, so the block is one
// column wider than the upload one.
defined("UPDATE_SIMPLE_FIXED_COLUMNS") || define("UPDATE_SIMPLE_FIXED_COLUMNS", 27);
defined("UPDATE_SIMPLE_VARIANT_COLUMNS") || define("UPDATE_SIMPLE_VARIANT_COLUMNS", 6);

defined("WORD_LIMIT") || define("WORD_LIMIT", 12);
defined("DESCRIPTION_WORD_LIMIT") || define("DESCRIPTION_WORD_LIMIT", 150);
defined("SHORT_DESCRIPTION_WORD_LIMIT") || define("SHORT_DESCRIPTION_WORD_LIMIT", 22);

/*
| Fallback parcel weight (kg) for Shiprocket when a product carries none. Shiprocket rejects
| a zero/empty weight outright with "Weight Required", and most of this catalogue predates
| the product shipping fields. See shiprocket_parcel_weight() in function_helper.php.
*/
defined('SHIPROCKET_NOMINAL_WEIGHT_KG') or define('SHIPROCKET_NOMINAL_WEIGHT_KG', 0.5);

/*
| Fallback parcel dimension (cm) per axis, for the same reason as the weight above: Shiprocket
| rejects a shipment whose length, breadth or height is 0. See shiprocket_parcel_dimension().
*/
defined('SHIPROCKET_NOMINAL_DIMENSION_CM') or define('SHIPROCKET_NOMINAL_DIMENSION_CM', 10);

/*
| Support WhatsApp number of last resort, used only when neither `system_settings.whatsapp_number`
| nor either `support_number` holds a usable one. The owner confirmed this number on 2026-08-20
| (migration 052 settled it across the settings rows); keeping it here means the WhatsApp support
| buttons keep working on a store whose settings row has been saved with the WhatsApp toggle off,
| which blanks the configured number. See support_whatsapp_number() in function_helper.php.
*/
defined('SUPPORT_WHATSAPP_DEFAULT') or define('SUPPORT_WHATSAPP_DEFAULT', '7290024349');
