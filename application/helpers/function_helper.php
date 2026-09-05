<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
	1. create_unique_slug($string,$table,$field='slug',$key=NULL,$value=NULL)
	2. ($type = 'store_settings', $is_json = false)
	3. get_logo()
	4. fetch_details($where = NULL,$table,$fields = '*')
	5. fetch_product($user_id = NULL, $filter = NULL, $id = NULL, $category_id = NULL, $limit = NULL, $offset = NULL, $sort = NULL, $order = NULL, $return_count = NULL)
	6. update_details($set,$where,$table)
	7. delete_image($id,$path,$field,$img_name,$table_name,$isjson = TRUE)
	8. delete_details($where,$table)
	9. is_json($data=NULL)
   10. validate_promo_code($promo_code,$user_id,$final_total)
   11. update_wallet_balance($operation,$user_id,$amount,$message="Balance Debited")
   12. send_notification($fcmMsg, $registrationIDs_chunks)
   13. get_attribute_values_by_pid($id)
   14. get_attribute_values_by_id($id)
   15. get_variants_values_by_pid($id)
   16. update_stock($product_variant_ids, $qtns)
   17. validate_stock($product_variant_ids, $qtns)
   18. stock_status($product_variant_id)
   19. verify_user($data)
   20. edit_unique($field,$table,$except)
   21. validate_order_status($order_ids, $status, $table = 'order_items', $user_id = null)
   22. is_exist($where,$table) 
   23. get_categories_option_html($categories, $selected_vals = null)
   24. get_subcategory_option_html($subcategories, $selected_vals)
   25. get_cart_total($user_id,$product_variant_id)
   26. get_frontend_categories_html()
   27. get_frontend_subcategories_html($subcategories)
   28. resize_image($image_data, $source_path, $id = false)
   29. has_permissions($role,$module) 
   30. print_msg($error,$message)
   31. get_system_update_info()
   32. send_mail($to,$subject,$message)
   33. fetch_orders($order_id = NULL, $user_id = NULL, $status = NULL, $delivery_boy_id = NULL, $limit = NULL, $offset = NULL, $sort = NULL, $order = NULL, $download_invoice = false)
   34. find_media_type($extenstion)
   35. formatBytes($size, $precision = 2)
   36. delete_images($subdirectory, $image_name)
   37. get_image_url($path, $image_type = '', $image_size = '')
   38. fetch_users($id)
   39. escape_array($array)
   40. allowed_media_types()
   41. get_current_version()
   42. resize_review_images($image_data, $source_path, $id = false)
   43. get_invoice_html($order_id)
   44. is_modification_allowed($module)
   45. output_escaping($array)
   46. get_min_max_price_of_product($product_id = '')
   47. find_discount_in_percentage($special_price, $price)
   48. get_attribute_ids_by_value($values,$names)
   49. insert_details($data,$table)
   50. get_category_id_by_slug($slug)
   51. get_variant_attributes($product_id)
   52. get_product_variant_details($product_variant_id)
   53. get_cities($id = NULL, $limit = NULL, $offset = NULL)
   54. get_favorites($user_id, $limit = NULL, $offset = NULL)
   55. current_theme($id='',$name='',$slug='',$is_default=1,$status='')
   56. get_languages($id='',$language_name='',$code='',$is_rtl='')
   60. verify_payment_transaction($txn_id,$payment_method)
   61. process_referral_bonus($user_id, $order_id, $status)
   62. process_refund($id, $status, $type = 'order_items')
   63. get_user_balance($id)
   64. get_stock()
   65. get_delivery_charge($address_id)
   66. validate_otp($otp, $order_item_id = NULL, $order_id = NULL, $seller_id = NULL)
   67. is_product_delivarable($type, $type_id, $product_id)
   68. check_cart_products_delivarable($area_id, $user_id)
   69. orders_count($status = "")
   70. curl($url, $method = 'GET', $data = [], $authorization = "")
   71. get_seller_permission($seller_id, $permit = NULL)
   72. get_price($type = "max")
   73. check_for_parent_id($category_id)
   74. update_balance($amount, $delivery_boy_id, $action)
*/

function create_unique_slug($string, $table, $field = 'slug', $key = NULL, $value = NULL)
{
    $t = &get_instance();
    $slug = url_title($string ?? '', '-', TRUE);
    $slug = strtolower($slug);
    $i = 0;
    $params = array();
    $params[$field] = $slug;

    if ($key) $params["$key !="] = $value;

    while ($t->db->where($params)->get($table)->num_rows()) {
        if (!preg_match('/-{1}[0-9]+$/', $slug))
            $slug .= '-' . ++$i;
        else
            $slug = preg_replace('/[0-9]+$/', ++$i, $slug);

        $params[$field] = $slug;
    }
    return $slug;
}

/**
 * Storefront URL for one seller.
 *
 * Every "View Seller Profile" / seller-card link used to be built by concatenating
 * seller_data.slug straight into the URL, so a seller whose slug was never filled in
 * produced ".../sellers/seller_details/" - which Sellers::seller_details() can only
 * answer by redirecting to the whole seller listing, i.e. exactly the wrong seller.
 * Falling back to the numeric user id keeps the link pointing at the right storefront;
 * seller_details() accepts either form and redirects an id to the slug once it exists.
 */
function seller_profile_url($slug = '', $user_id = '')
{
    $slug = is_string($slug) ? trim($slug) : '';
    if ($slug !== '') {
        return base_url('sellers/seller_details/' . rawurlencode($slug));
    }
    if (!empty($user_id)) {
        return base_url('sellers/seller_details/' . (int) $user_id);
    }
    return base_url('sellers');
}

/**
 * Identity / bank fields that may only ever belong to one seller account.
 *
 * PAN, GSTIN, GST Enrollment ID and the bank account number each identify a single
 * real-world entity: two seller accounts sharing one means duplicate KYC, and in the
 * bank-account case it means payouts for two storefronts landing in the same account.
 * Nothing enforced this, so a seller could open a second account and re-enter the
 * identifiers from their first one.
 *
 * $values is a column => posted-value map; blank/omitted values are skipped so a
 * partially filled form only checks what it actually supplied. Comparison is
 * trimmed and case-insensitive, since PAN/GSTIN get retyped in mixed case.
 * $exclude_user_id keeps a seller from colliding with their own saved row on edit;
 * pass 0 when creating.
 *
 * Removed sellers (status 7) are ignored, so a deleted account's PAN/GSTIN/account
 * number goes back into circulation and the same person can register again. Admin's
 * "Delete Seller" drops the seller_data row outright and so frees them anyway; this
 * covers "Remove Seller", which only flips the status and leaves the row behind.
 * Deactive (0) and Not-Approved (2) sellers still block - those accounts are live and
 * can be switched back on, which would leave two active sellers sharing one identity.
 *
 * Returns the human-readable labels of whichever fields are already taken, in
 * $values order, or an empty array when everything is free.
 */
function duplicate_seller_identifiers($values, $exclude_user_id = 0)
{
    $t = &get_instance();
    $labels = [
        'pan' => 'PAN Number',
        'gst' => 'GST Number',
        'gst_enrollment_number' => 'GST Enrollment ID',
        'account_number' => 'Account Number',
    ];

    $duplicates = [];
    foreach ($values as $column => $value) {
        if (!isset($labels[$column])) {
            continue;
        }
        $value = trim((string) $value);
        if ($value === '') {
            continue;
        }
        $taken = $t->db
            ->where('user_id !=', (int) $exclude_user_id)
            ->where('status !=', 7)
            ->where('UPPER(TRIM(' . $column . '))', strtoupper($value))
            ->get('seller_data')
            ->num_rows() > 0;
        if ($taken) {
            $duplicates[] = $labels[$column];
        }
    }
    return $duplicates;
}

/**
 * Rejection message for duplicate_seller_identifiers() output, so the seller form and
 * the admin form phrase the same failure identically.
 */
function duplicate_seller_identifiers_message($duplicate_labels)
{
    $count = count($duplicate_labels);
    if ($count === 0) {
        return '';
    }
    $list = ($count === 1)
        ? $duplicate_labels[0]
        : implode(', ', array_slice($duplicate_labels, 0, -1)) . ' and ' . end($duplicate_labels);
    return $list . ' ' . ($count === 1 ? 'is' : 'are') . ' already registered with another seller account. Each seller account must use its own ' . ($count === 1 ? 'value' : 'values') . '.';
}

/**
 * Contact-field format rules shared by the seller profile form and the admin seller form.
 * Returns an array of human-readable error messages (empty array = everything is fine).
 *
 * Only fields present in $values are checked, so callers can validate a single field
 * (the live availability check) or the whole form with the same rules.
 *
 * Phone rules: exactly 10 digits, first digit 6-9 (Indian mobile series). Anything
 * shorter/longer, or carrying spaces, +91, dashes etc., is rejected rather than silently
 * trimmed - the columns feed Shiprocket and OTP delivery, both of which need a clean 10-digit number.
 */
function seller_contact_format_errors($values)
{
    $labels = [
        'phone' => 'Phone Number',
        'shop_phone' => 'Shop Phone Number',
        'email' => 'Email ID',
    ];
    $errors = [];

    foreach (['phone', 'shop_phone'] as $field) {
        if (!array_key_exists($field, $values)) {
            continue;
        }
        $value = trim((string) $values[$field]);
        if ($value === '') {
            continue; // "required" is handled by the form validation rules, not here
        }
        if (!preg_match('/^[0-9]{10}$/', $value)) {
            $errors[] = $labels[$field] . ' must be exactly 10 digits (no spaces, +91 or symbols).';
        } elseif (!preg_match('/^[6-9]/', $value)) {
            $errors[] = $labels[$field] . ' must be a valid mobile number starting with 6, 7, 8 or 9.';
        }
    }

    if (array_key_exists('email', $values)) {
        $email = trim((string) $values['email']);
        if ($email !== '') {
            if (strlen($email) > 254) {
                $errors[] = $labels['email'] . ' must be 254 characters or less.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[^\s@]+@[^\s@]+\.[A-Za-z]{2,}$/', $email)) {
                $errors[] = 'Enter a valid ' . $labels['email'] . ' (example: name@example.com).';
            }
        }
    }

    return $errors;
}

/**
 * Cross-account uniqueness for the seller contact fields.
 *
 * A seller may reuse their OWN personal number as the shop number (or use a different one),
 * so numbers are only ever compared against OTHER accounts - never against the seller's own
 * rows. Returns an array of human-readable messages (empty = no clash).
 *
 * $values keys: phone, shop_phone, email. $exclude_user_id is the users.id of the account
 * being edited (0 when adding a brand-new seller, which excludes nothing).
 */
function duplicate_seller_contacts($values, $exclude_user_id = 0)
{
    $t = &get_instance();
    $exclude_user_id = (int) $exclude_user_id;
    $messages = [];

    $phone = isset($values['phone']) ? trim((string) $values['phone']) : '';
    $shop_phone = isset($values['shop_phone']) ? trim((string) $values['shop_phone']) : '';
    $email = isset($values['email']) ? trim((string) $values['email']) : '';

    // A number is "taken" if any other login uses it as its identity (users.mobile is the
    // login identity and is UNIQUE), or if any other seller has it as their personal or
    // shop number. Removed sellers (status 7) release their numbers.
    $number_taken = function ($number) use ($t, $exclude_user_id) {
        if ($number === '') {
            return false;
        }
        $in_users = $t->db
            ->where('id !=', $exclude_user_id)
            ->where('mobile', $number)
            ->count_all_results('users') > 0;
        if ($in_users) {
            return true;
        }
        return $t->db
            ->where('user_id !=', $exclude_user_id)
            ->where('status !=', 7)
            ->group_start()
            ->where('TRIM(phone)', $number)
            ->or_where('TRIM(shop_phone)', $number)
            ->group_end()
            ->count_all_results('seller_data') > 0;
    };

    if ($phone !== '' && $number_taken($phone)) {
        $messages[] = 'Phone Number ' . $phone . ' is already registered with another account. Please use a different number.';
    }
    // Only flag the shop number against other accounts - matching this seller's own personal
    // number is explicitly allowed.
    if ($shop_phone !== '' && $shop_phone !== $phone && $number_taken($shop_phone)) {
        $messages[] = 'Shop Phone Number ' . $shop_phone . ' is already registered with another account. Please use a different number.';
    }

    if ($email !== '') {
        // Compared against other SELLER accounts only (users.email is not unique app-wide:
        // customers and delivery boys may legitimately share an address with a seller).
        $email_taken = $t->db
            ->where('user_id !=', $exclude_user_id)
            ->where('status !=', 7)
            ->where('LOWER(TRIM(email))', strtolower($email))
            ->count_all_results('seller_data') > 0;
        if (!$email_taken) {
            $email_taken = $t->db
                ->from('users u')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', 4) // seller group
                ->where('u.id !=', $exclude_user_id)
                ->where('LOWER(TRIM(u.email))', strtolower($email))
                ->count_all_results() > 0;
        }
        if ($email_taken) {
            $messages[] = 'Email ID ' . $email . ' is already registered with another seller account. Please use a different email.';
        }
    }

    return $messages;
}

/**
 * Format + uniqueness in one call, for the two save paths (seller profile save and admin
 * seller add/edit) so both reject exactly the same inputs. Returns '' when valid.
 */
function seller_contact_validation_message($values, $exclude_user_id = 0)
{
    $errors = seller_contact_format_errors($values);
    if (!empty($errors)) {
        return implode(' ', $errors);
    }
    $duplicates = duplicate_seller_contacts($values, $exclude_user_id);
    return empty($duplicates) ? '' : implode(' ', $duplicates);
}

/**
 * Format check for the two tax identifiers that decide what the marketplace withholds.
 *
 * These were NOT validated anywhere. The regexes existed but were commented out in
 * seller/Auth.php (lines 474-475 and 532-533) and seller/Login.php never had them, so both
 * fields were free text limited only by a maxlength on the input. The live consequences:
 *
 *   - seller 7's GSTIN was the literal string "THE DEVILS NUMBER FKN 6 I AM SO COOL BWAHHAH"
 *     and their PAN was blank;
 *   - 5 of 8 seller profiles carried a PAN that is not a PAN ("HGFHF7657657",
 *     "2323232323232323");
 *   - Tax_compliance_model reads those fields to pick the statutory rate, so it correctly
 *     concluded "no valid PAN" and applied the 5% s.206AA penalty rate instead of 0.1%. The
 *     same order item that settled at Rs. 1.10 TDS in August settles at Rs. 54.95 today -
 *     fifty times more - purely because the identifier stopped validating. TCS collapsed to
 *     zero for the same reason: no GSTIN validates, so nothing is collected from anybody.
 *
 * The identifier is the whole basis of the deduction, so a wrong one is not a cosmetic data
 * problem - it is money taken off the wrong seller at the wrong rate and deposited against an
 * identity that does not exist.
 *
 * PAN: 5 letters, 4 digits, 1 letter. The 4th letter is the holder class and the 5th is the
 * first letter of the surname / entity name, which is why the shape alone is meaningful -
 * Tax_compliance_model::classify_pan() reads position 4 to decide whether the Rs. 5 lakh
 * s.194-O threshold applies.
 *
 * GSTIN: 2-digit state code, the holder's 10-character PAN, an entity number, 'Z', checksum.
 * Same expression Tax_compliance_model::is_valid_gstin() already uses to decide whether TCS is
 * collectable, so a seller can no longer save a value the settlement engine will reject.
 *
 * Both are optional here: whether they are REQUIRED is the form's business (a seller trading on
 * a GST Enrollment ID has no GSTIN at all), and this only asserts that a value which IS present
 * is well formed.
 *
 * @param  array $values keys: pan, gst, gst_enrollment_number
 * @return array human-readable messages; empty means everything present is well formed
 */
function seller_tax_identifier_errors($values)
{
    $errors = [];

    // The wording of each message matters: seller-profile.js maps a server rejection back onto
    // the field it belongs to by looking for the field's label in the message text
    // (SERVER_FIELD_HINTS), so "PAN Number", "GST Number" and "GST Enrollment ID" have to appear
    // verbatim. Without them the error arrives as an anonymous banner on whichever step the
    // seller happens to be on, which is the exact problem that mapping exists to solve.
    if (array_key_exists('pan', $values)) {
        $pan = strtoupper(preg_replace('/\s+/', '', (string) $values['pan']));
        if ($pan !== '' && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) {
            $errors[] = 'Enter a valid PAN Number - 10 characters, formatted like ABCDE1234F.';
        }
    }

    if (array_key_exists('gst', $values)) {
        $gstin = strtoupper(preg_replace('/\s+/', '', (string) $values['gst']));
        if ($gstin !== '' && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstin)) {
            $errors[] = 'Enter a valid GST Number - 15 characters, formatted like 22ABCDE0000A1Z5.';
        }
    }

    // The GSTIN embeds the holder's PAN at characters 3-12. A mismatch means one of the two
    // belongs to somebody else, which is exactly the case where a deduction gets deposited
    // against the wrong identity - and neither field looks wrong on its own.
    if (empty($errors) && !empty($values['pan']) && !empty($values['gst'])) {
        $pan = strtoupper(preg_replace('/\s+/', '', (string) $values['pan']));
        $gstin = strtoupper(preg_replace('/\s+/', '', (string) $values['gst']));
        if ($pan !== '' && strlen($gstin) === 15 && substr($gstin, 2, 10) !== $pan) {
            $errors[] = 'This GST Number does not contain the PAN Number above - characters 3 to 12 of a GSTIN are the holder\'s PAN. Please check both.';
        }
    }

    if (array_key_exists('gst_enrollment_number', $values)) {
        $enrol = strtoupper(preg_replace('/\s+/', '', (string) $values['gst_enrollment_number']));
        // A GST Enrollment ID (for an unregistered supplier) has no single published format, so
        // this only rejects the obviously-not-an-identifier cases rather than inventing a shape.
        if ($enrol !== '' && !preg_match('/^[0-9A-Z\-\/]{8,32}$/', $enrol)) {
            $errors[] = 'Enter a valid GST Enrollment ID - 8 to 32 letters, digits, hyphens or slashes.';
        }
    }

    return $errors;
}

/**
 * One-line wrapper matching seller_contact_validation_message(), so the profile and admin save
 * paths read the same way for both groups of identifiers.
 *
 * @return string '' when everything present is well formed
 */
function seller_tax_identifier_validation_message($values)
{
    $errors = seller_tax_identifier_errors($values);
    return empty($errors) ? '' : implode(' ', $errors);
}

/**
 * Request-scoped memo store for the `settings` table.
 *
 * Returned BY REFERENCE so callers can write into it. Keys are the `variable`
 * column; a value of FALSE means "looked up, no such row" (distinct from a row
 * whose value happens to be an empty string - the two produce different results
 * once json_decode() gets involved, so the distinction has to survive caching).
 *
 * @return array
 */
function &settings_cache_store()
{
    static $store = array();
    return $store;
}

/**
 * Drops the memoised settings for this request.
 *
 * MUST be called after any write to the `settings` table, otherwise code that
 * saves a setting and then re-reads it inside the same request would see the
 * pre-write value. Every write site in the application calls this.
 *
 * @param string|null $type Clear just this variable, or NULL for all of them.
 */
function clear_settings_cache($type = null)
{
    $store = &settings_cache_store();
    if ($type === null) {
        $store = array();
    } else {
        unset($store[$type]);
    }

    /*
     * The memo above lives for one request. get_settings_raw() now also keeps a
     * CROSS-request copy, so a write has to drop that too or the next visitor
     * would keep reading the pre-write value until the TTL lapsed.
     *
     * Deleting the whole group rather than the single key is deliberate: it costs
     * one glob of a handful of files, and it means a caller that clears one
     * variable after writing several (which happens in the admin settings forms)
     * cannot leave a stale sibling behind.
     */
    if (function_exists('app_cache_delete_group')) {
        app_cache_delete_group('settings.');
    }
}

/**
 * Pass-through wrapper that invalidates the settings memo after a write.
 *
 * Wraps the *result* of a settings INSERT/UPDATE so it can be dropped in around
 * an existing expression - including one in `return` position - without changing
 * what that expression evaluates to.
 *
 *   return settings_write_done($this->db->...->update('settings'));
 *
 * @param  mixed $result Whatever the write expression returned.
 * @return mixed The same value, untouched.
 */
function settings_write_done($result = null)
{
    clear_settings_cache();
    return $result;
}

/**
 * Raw, un-transformed `settings`.`value` for a variable, memoised per request.
 *
 * @return string|false The stored string, or FALSE when the row does not exist.
 */
function get_settings_raw($type)
{
    $store = &settings_cache_store();
    if (!array_key_exists($type, $store)) {
        /*
         * PERFORMANCE: nine `settings` reads were still hitting MySQL on every page
         * even with the per-request memo, because the memo starts empty each time.
         * The values change only when an administrator saves a settings form, and
         * every one of those writes already funnels through clear_settings_cache()
         * / settings_write_done() - so a cross-request copy is safe to add on top.
         *
         * FALSE (no such row) is cached as well as a real value: a lookup for a
         * variable that does not exist is just as repeatable, and not caching it
         * would leave the misses querying every time. It is stored wrapped in an
         * array because FALSE is indistinguishable from app_cache_get()'s miss
         * sentinel otherwise.
         *
         * The 300s TTL is a backstop, not the primary invalidation: the four
         * settings writes that live in MIGRATIONS do not all call the clear hook,
         * and a migration run should not be able to pin a stale value forever.
         */
        $cached = app_cache_get('settings.' . $type);
        if (is_array($cached) && array_key_exists('v', $cached)) {
            $store[$type] = $cached['v'];
        } else {
            $t = &get_instance();
            // Narrowed from `SELECT *` to the one column that is ever read. The policy
            // rows in this table are large (seller_terms_conditions is 13 KB), so the
            // discarded columns were real bytes off the wire on every single call.
            $res = $t->db->select('value')->where('variable', $type)->get('settings')->result_array();
            $store[$type] = isset($res[0]['value']) ? $res[0]['value'] : false;
            app_cache_set('settings.' . $type, array('v' => $store[$type]), 300);
        }
    }
    return $store[$type];
}

/**
 * Reads one row out of the `settings` table.
 *
 * PERFORMANCE: this used to issue a fresh `SELECT *` on every call. There are
 * ~700 call sites in the application and a single storefront listing page was
 * measured making 121 of them, all for the same handful of variables. The value
 * is now fetched once per variable per request and reused.
 *
 * The return contract is unchanged, including the edge cases:
 *   - no such row            -> NULL (implicit, as before)
 *   - $is_json = true        -> json_decode($value, true)
 *   - $is_json = false       -> output_escaping($value)
 */
function get_settings($type = 'system_settings', $is_json = false)
{
    $value = get_settings_raw($type);
    if ($value !== false) {
        if ($is_json) {
            return json_decode($value, true);
        } else {
            return output_escaping($value);
        }
    }
}

function get_logo()
{
    $t = &get_instance();
    $res = $t->db->select(' * ')->where('variable', 'logo')->get('settings')->result_array();
    if (!empty($res)) {
        $logo['is_null'] = FALSE;
        $logo['value'] = base_url() . $res[0]['value'];
    } else {
        $logo['is_null'] = TRUE;
        $logo['value'] = base_url() . NO_IMAGE;
    }
    return $logo;
}

function add_ver($url)
{
    $relative = ltrim(str_replace(rtrim(base_url(), '/'), '', $url), '/');
    $full_path = FCPATH . $relative;
    if (is_file($full_path)) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . 'v=' . filemtime($full_path);
    }
    return $url;
}

function fetch_details($table, $where = NULL, $fields = '*', $limit = '', $offset = '', $sort = '', $order = '', $where_in_key = '', $where_in_value = '')
{
    $t = &get_instance();
    $t->db->select($fields);
    if (!empty($where)) {
        $t->db->where($where);
    }

    if (!empty($where_in_key) && !empty($where_in_value)) {
        $t->db->where_in($where_in_key, $where_in_value);
    }

    if (!empty($limit)) {
        $t->db->limit($limit);
    }

    if (!empty($offset)) {
        $t->db->offset($offset);
    }

    if (!empty($order) && !empty($sort)) {
        $t->db->order_by($sort, $order);
    }

    $res = $t->db->get($table)->result_array();

    // print_r($res);
    /* Some characters need to be output escaped (strip slashes) in some tables */
    if($table == 'pickup_locations' || $table == 'products' || $table == 'seller_data' || $table == 'pickup_locations'){
        /* $res[0]['pickup_location'] = output_escaping($res[0]['pickup_location']);
        $res[0]['address'] = output_escaping($res[0]['address']); */

        // $res[0] = output_escaping($res[0]);
        $res = output_escaping_new($res);
    }
    if($table == 'users'){
        // $res[0]['address'] = output_escaping($res[0]['address']);
        // $res[0]['address'] = output_escaping_new($res[0]['address']);
    }

    return $res;
}

/**
 * Returns the current logged-in customer's state (name string) from their default
 * address (falls back to their most recent address). Empty string if unknown/guest.
 * Used for the GST-enrollment state restriction (P3.2).
 */
function get_customer_state()
{
    $t = &get_instance();
    if (!isset($t->ion_auth) || !$t->ion_auth->logged_in()) {
        return '';
    }
    $user_id = $t->session->userdata('user_id');
    if (empty($user_id)) {
        return '';
    }
    $row = $t->db->select('state')
        ->from('addresses')
        ->where('user_id', $user_id)
        ->order_by('is_default', 'DESC')
        ->order_by('id', 'DESC')
        ->limit(1)
        ->get()->row_array();
    return (!empty($row) && !empty($row['state'])) ? $row['state'] : '';
}
function get_state_from_pincode($pincode)
{
    static $cache = [];
    $pincode = preg_replace('/\D/', '', (string) $pincode);
    if (strlen($pincode) !== 6) {
        return '';
    }
    if (isset($cache[$pincode])) {
        return $cache[$pincode];
    }

    $state = '';

    // 1) zippopotam.us (cURL first, file_get_contents fallback)
    $url  = 'https://api.zippopotam.us/in/' . $pincode;
    $body = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 4,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        if ($resp !== false && (int) curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
            $body = $resp;
        }
        curl_close($ch);
    }
    if ($body === false && ini_get('allow_url_fopen')) {
        $ctx  = stream_context_create(['http' => ['timeout' => 4], 'https' => ['timeout' => 4]]);
        $body = @file_get_contents($url, false, $ctx);
    }
    if (!empty($body)) {
        $data = json_decode($body, true);
        if (!empty($data['places'][0]['state'])) {
            $state = $data['places'][0]['state'];
        }
    }

    // 2) Local DB fallback
    if ($state === '') {
        $t   = &get_instance();
        $row = $t->db->select('s.name AS state')
            ->from('zipcodes z')
            ->join('cities c', 'z.city_id = c.city_id', 'LEFT')
            ->join('districts d', 'c.district_id = d.id', 'LEFT')
            ->join('states s', 'd.state_id = s.id', 'LEFT')
            ->where('z.zipcode', $pincode)
            ->limit(1)
            ->get()->row_array();
        if (!empty($row['state'])) {
            $state = $row['state'];
        }
    }

    $cache[$pincode] = $state;
    return $state;
}

function normalize_state_name($state)
{
    $s = strtolower(trim((string) $state));
    $s = preg_replace('/[^a-z]/', '', $s);
    $aliases = [
        'newdelhi'     => 'delhi',
        'nctofdelhi'   => 'delhi',
        'delhincr'     => 'delhi',
        'up'           => 'uttarpradesh',
        'mp'           => 'madhyapradesh',
        'hp'           => 'himachalpradesh',
        'ap'           => 'andhrapradesh',
        'tn'           => 'tamilnadu',
        'wb'           => 'westbengal',
        'jk'           => 'jammuandkashmir',
        'jammukashmir' => 'jammuandkashmir',
        'orissa'       => 'odisha',
        'uttaranchal'  => 'uttarakhand',
        'pondicherry'  => 'puducherry',
    ];
    return isset($aliases[$s]) ? $aliases[$s] : $s;
}

/**
 * True when two free-text state names refer to the same state (after normalisation).
 * A match requires both to be non-empty.
 */
function states_match($a, $b)
{
    $na = normalize_state_name($a);
    $nb = normalize_state_name($b);
    return ($na !== '' && $nb !== '' && $na === $nb);
}

function fetch_product($user_id = NULL, $filter = NULL, $id = NULL, $category_id = NULL, $limit = NULL, $offset = NULL, $sort = NULL, $order = NULL, $return_count = NULL, $is_deliverable = NULL, $seller_id = NULL)
{

    $settings = get_settings('system_settings', true);
    $low_stock_limit = isset($settings['low_stock_limit']) ? $settings['low_stock_limit'] : 5;
    $t = &get_instance();

    if ($sort == 'pv.price' && !empty($sort) && $sort != NULL) {

        /*
         * Price sorting used to silently do nothing.
         *
         * The old expression added tax with `p.tax`, but `p.tax` is a foreign key into
         * `taxes` (see the LEFT JOIN on `taxes tax` below), not a percentage - and it is
         * NULL for every product on this catalogue. `price + ((price * NULL)/100)` is NULL,
         * so the whole ORDER BY key was NULL on every row and MySQL kept the rows in their
         * natural order: "Price - Low To High" returned exactly the default listing.
         *
         * The tax component now comes from `tax.percentage` (COALESCEd to 0 so a product
         * with no tax row still sorts on its own price), matching how tax_percentage is
         * resolved further down when the price fields are built for the response.
         *
         * The key is also aggregated, because this query GROUP BYs p.id while LEFT JOINing
         * product_variants - a variable product has several prices and an un-aggregated
         * ORDER BY would pick an arbitrary one. Ascending sorts on the cheapest variant and
         * descending on the dearest, which is what the grid shows as the product's price.
         */
        $effective_price = "IF(pv.special_price > 0,
                 pv.special_price * (1 + IF(p.is_prices_inclusive_tax = 1, 0, COALESCE(tax.percentage, 0) / 100)),
                 pv.price * (1 + IF(p.is_prices_inclusive_tax = 1, 0, COALESCE(tax.percentage, 0) / 100))
            )";

        $aggregate = (strtoupper(trim((string)$order)) == 'DESC') ? 'MAX' : 'MIN';
        $t->db->order_by($aggregate . '(' . $effective_price . ') ' . $order, false);
    }


    if (isset($filter['show_only_active_products']) && $filter['show_only_active_products'] == 0) {
        $where = [];
    } else {
        // listing_visibility keeps a seller inside their plan's listing limit in the shop
        // itself, not just when adding products - see Seller_subscription_model's
        // "Storefront listing visibility" section. This is the main storefront read, so
        // it covers listings, product detail, search results, related products, sections
        // and a seller's own store page in one place.
        $where = ['p.status' => '1', 'pv.status' => 1, 'sd.status' => 1, 'p.listing_visibility' => 1];
    }

    $discount_filter_data = (isset($filter['discount']) && !empty($filter['discount'])) ? ' pv.*,( if(pv.special_price > 0,( (pv.price-pv.special_price)/pv.price)*100,0)) as cal_discount_percentage, ' : '';

    $t->db->select($discount_filter_data . ' IF(ss.id IS NOT NULL, 1, 0) as has_subscription, (select count(id)  from products where products.category_id=c.id ) as total,count(p.id) as sales, p.stock_type ,
     p.is_prices_inclusive_tax, p.type ,GROUP_CONCAT(DISTINCT(pa.attribute_value_ids)) as attr_value_ids,sd.rating as seller_rating,sd.slug as seller_slug,sd.no_of_ratings as seller_no_of_ratings,sd.logo as seller_profile, sd.store_name as store_name,sd.store_description, p.seller_id, u.username as seller_name,
     p.id,p.stock,p.name,p.category_id,p.short_description,p.slug,p.description,p.extra_description,p.total_allowed_quantity,p.status,p.deliverable_type,p.is_attachment_required,p.deliverable_zipcodes,p.minimum_order_quantity,p.sku,
     p.quantity_step_size,p.cod_allowed,p.row_order,p.rating,p.no_of_ratings,p.image,p.is_returnable,p.is_cancelable,p.cancelable_till,p.indicator,p.other_images, 
     p.video_type, p.video, p.tags, p.warranty_period, p.guarantee_period, p.made_in,p.hsn_code,p.download_allowed,p.download_type,p.download_link,p.pickup_location,p.brand,p.availability, p.slug as product_slug, b.slug as brand_slug,c.name as category_name,tax.percentage as tax_percentage ,tax.id as tax_id ')
        ->join(" categories c", "p.category_id=c.id ", 'LEFT')
        ->join(" brands b", "p.brand=b.name", 'LEFT')
        ->join(" seller_data sd", "p.seller_id=sd.user_id ", 'LEFT')
        ->join(" users u", "p.seller_id=u.id", 'LEFT')
        ->join('`product_variants` pv', 'p.id = pv.product_id', 'LEFT')
        ->join('`taxes` tax', 'tax.id = p.tax', 'LEFT')
        ->join('`product_attributes` pa', ' pa.product_id = p.id ', 'LEFT')
        ->join('`seller_subscriptions` ss', 'ss.seller_id = p.seller_id AND ss.is_active = 1', 'LEFT');


    if (isset($filter['show_only_stock_product']) && $filter['show_only_stock_product'] == 1) {
        $t->db->where('(p.stock != "" or pv.stock != "")');
    }

    if (isset($filter) && !empty($filter['product_type']) && strtolower($filter['product_type']) == 'most_selling_products') {
        $t->db->join('`order_items` oi', 'oi.product_variant_id = pv.id', 'LEFT');
        $sort = 'count(p.id)';
        $order = 'DESC';
    }

    if (isset($filter) && !empty($filter['search'])) {
        $tags = explode(" ", $filter['search']);
        $t->db->group_Start();
        foreach ($tags as $i => $tag) {
            if ($i == 0) {
                $t->db->like('p.tags', trim($tag));
            } else {
                $t->db->or_like('p.tags', trim($tag));
            }
        }
        $t->db->or_like('p.name', trim($filter['search']));
        $t->db->group_end();
    }
    if (isset($filter) && !empty($filter['flag']) && $filter['flag'] != "null" && $filter['flag'] != "") {
        $flag = $filter['flag'];
        if ($flag == 'low') {
            $t->db->group_Start();
            $where1 = "p.stock_type is  NOT NULL";
            $t->db->where($where1);
            $t->db->where('p.stock <=', $low_stock_limit);
            $t->db->where('p.availability =', '1');
            $t->db->or_where('pv.stock <=', $low_stock_limit);
            $t->db->where('pv.availability =', '1');
            $t->db->group_End();
        } else {
            $t->db->group_Start();
            $t->db->or_where('p.availability ', '0');
            $t->db->or_where('pv.availability ', '0');
            $t->db->where('p.stock ', '0');
            $t->db->or_where('pv.stock ', '0');
            $t->db->group_End();
        }
    }
    if (isset($filter['min_price']) && $filter['min_price'] > 0) {
        $min_price = $filter['min_price'];
        $where_min = "if( pv.special_price > 0 , pv.special_price , pv.price ) >=$min_price";
        $t->db->group_Start();
        $t->db->where($where_min);
        $t->db->group_End();
    }
    if (isset($filter['max_price']) && $filter['max_price'] > 0 && isset($filter['min_price']) && $filter['min_price'] > 0) {
        $max_price = $filter['max_price'];
        $where_max = "if( pv.special_price > 0 , pv.special_price , pv.price ) <=$max_price";
        $t->db->group_Start();
        $t->db->where($where_max);
        $t->db->group_End();
    }

    if (isset($filter) && !empty($filter['tags'])) {
        $tags = explode(",", $filter['tags']);
        $t->db->group_Start();
        foreach ($tags as $i => $tag) {
            if ($i == 0) {
                $t->db->like('p.tags', trim($tag));
            } else {
                $t->db->or_like('p.tags', trim($tag));
            }
        }
        $t->db->group_end();
    }

    if (isset($filter) && !empty($filter['brand'])) {
        $t->db->where('p.brand', trim($filter['brand']));
    }

    /* MODIFIED - We have added filtering for multiple brands at once by using where_in instead */
    if (isset($filter) && !empty($filter['brands'])) {
        $t->db->where_in('p.brand', explode("|", $filter['brands']));
    }

    if (isset($filter) && !empty($filter['slug'])) {
        $where['p.slug'] = $filter['slug'];
    }
    if (isset($seller_id) && !empty($seller_id) && $seller_id != "") {
        $where['p.seller_id'] = $seller_id;
    }


    if (isset($filter) && !empty($filter['attribute_value_ids'])) {
        /* https://stackoverflow.com/questions/5015403/mysql-find-in-set-with-multiple-search-string */
        $str = str_replace(',', '|', $filter['attribute_value_ids']); //str_replace(find,replace,string,count)
        $t->db->where('CONCAT(",", pa.attribute_value_ids , ",") REGEXP ",(' . $str . ')," !=', 0, false);
    }

    if (isset($category_id) && !empty($category_id)) {
        /* A category must list everything filed beneath it, at any depth. This used to match the
           category itself plus its DIRECT children only (c.parent_id), so a product in a
           grandchild category (Footwear > Handbags > Tote bags) never showed on the parent's
           page - and a scalar $category_id matched that single category exactly, hiding every
           subcategory product. category_descendant_ids() expands the whole subtree. */
        $descendant_ids = category_descendant_ids($category_id);
        if (!empty($descendant_ids)) {
            $t->db->where_in('p.category_id', $descendant_ids);
        }
    }

    if (isset($filter) && !empty($filter['product_type']) && strtolower($filter['product_type']) == 'products_on_sale') {
        $t->db->where('pv.special_price >', '0');
    }

    if (isset($filter) && !empty($filter['product_type']) && strtolower($filter['product_type']) == 'top_rated_products') {
        $sort = null;
        $order = null;
        $t->db->order_by("p.rating", "desc");
        $t->db->order_by("p.no_of_ratings", "desc");
        $where = ['p.no_of_ratings > ' => 0];
    }



    if (isset($filter) && !empty($filter['product_type']) && strtolower($filter['product_type']) == 'top_rated_product_including_all_products') {
        $sort = null;
        $order = null;
        $t->db->order_by("p.rating", "desc");
        $t->db->order_by("p.no_of_ratings", "desc");
    }

    if (isset($filter) && !empty($filter['product_type']) && $filter['product_type'] == 'new_added_products') {
        $sort = 'p.id';
        $order = 'desc';
    }

    if (isset($filter) && !empty($filter['product_variant_ids'])) {
        if (is_array($filter['product_variant_ids'])) {
            $t->db->where_in('pv.id', $filter['product_variant_ids']);
        }
    }

    if (isset($id) && !empty($id) && $id != null) {
        if (is_array($id) && !empty($id)) {
            $t->db->where_in('p.id', $id);
            $t->db->where($where);
        } else {
            if (isset($filter) && !empty($filter['is_similar_products']) && $filter['is_similar_products'] == '1') {
                $where[' p.id != '] = $id;
            } else {
                $where['p.id'] = $id;
            }
            $t->db->where($where);
        }
    } else {
        $t->db->where($where);
    }
    // GST enrollment restriction (P3.2): products from sellers who registered via a
    // GST Enrollment Number (sd.is_gst_registered = 0) are visible ONLY to customers
    // located in the seller's own state. Applied only when the customer's state is known
    // (logged-in customer with an address); guests are gated at checkout instead.
    if (isset($filter['customer_state']) && !empty($filter['customer_state'])) {
        $cs = $t->db->escape($filter['customer_state']);
        $t->db->where("(sd.is_gst_registered = 1 OR LOWER(TRIM(sd.state)) = LOWER(TRIM($cs)))", null, false);
    }
    /*
     * A product is excluded here when its category's status is neither '1' nor '0' - which in
     * practice means NULL, and NULL happens in two very different situations:
     *
     *   - `categories.status` is nullable with no default, so a category row created without an
     *     explicit status silently swallows every product filed under it. Seven categories on
     *     this database are in that state, including real ones ("HOME & LIVING", "FOOD &
     *     SNACKS", "Bags"). Migration 050 backfills them and adds a default, and this clause now
     *     treats an existing-but-unset category as visible so the trap cannot reopen.
     *
     *   - the join found no category row at all, because `products.category_id` points at a
     *     category that no longer exists. 177 products here do exactly that (their category ids
     *     are all above the highest surviving category id). Those stay excluded from storefront
     *     reads - a product with no category cannot be browsed or filtered coherently - but
     *     `ignore_category_status` lets administrative reads such as Manage Stock list them, so
     *     they are at least visible to whoever has to repair them.
     */
    if (!isset($filter['flag']) && empty($filter['flag']) && empty($filter['ignore_category_status'])) {
        $t->db->group_Start();
        $t->db->or_where('c.status', '1');
        $t->db->or_where('c.status', '0');
        $t->db->or_where('(c.id IS NOT NULL AND c.status IS NULL)', null, false);
        $t->db->group_End();
    }
    if (isset($filter['discount']) && !empty($filter['discount']) && $filter['discount'] != "") {
        $discount_pr = $filter['discount'];
        $t->db->group_by('p.id')->having("cal_discount_percentage  <= " . $discount_pr, null, false)->having("cal_discount_percentage  > 0 ", null, false);
    } else {
        $t->db->group_by('p.id');
    }


    if ($limit != null || $offset != null) {
        $t->db->limit($limit, $offset);
    }
    if (isset($filter['discount']) && !empty($filter['discount']) && $filter['discount'] != "") {
        $t->db->order_by('cal_discount_percentage', 'DESC');
    } else {
        $t->db->order_by('has_subscription', 'DESC');
        if ($sort != null && $sort != 'pv.price') {
            $t->db->order_by($sort, $order);
        }
        $t->db->order_by('p.row_order', 'ASC');
    }


    if (!empty($return_count)) {
        return $t->db->count_all_results('products p');
    } else {
        $product = $t->db->get('products p')->result_array();
    }

    $count = isset($filter) && !empty($filter['flag']) ? 'count(DISTINCT(p.id))' : 'count(DISTINCT(p.id))';
    $discount_filter = (isset($filter['discount']) && !empty($filter['discount'])) ? ' , GROUP_CONCAT( IF( ( IF( pv.special_price > 0, ((pv.price - pv.special_price) / pv.price) * 100, 0 ) ) > ' . $filter['discount'] . ', ( IF( pv.special_price > 0, ((pv.price - pv.special_price) / pv.price) * 100, 0 ) ), 0 ) ) AS cal_discount_percentage ' : '';
    /*
     * `seller_data` is joined LEFT here to match the data query above.
     *
     * It used to be an INNER join (no third argument) while the data query LEFT-joins the same
     * table, so the two disagreed about any product whose seller has no `seller_data` row: the
     * rows came back but the total counted them out. Three products on this database are in that
     * state, which is why the stock report advertised 267 records and returned 270. Every list
     * built on fetch_product() paginates on this number, so the last page silently hid rows and
     * the record count was wrong wherever such a product exists.
     */
    $product_count = $t->db->select('count(DISTINCT(p.id)) as total , GROUP_CONCAT(pa.attribute_value_ids) as attr_value_ids' . $discount_filter)
        ->join(" categories c", "p.category_id=c.id ", 'LEFT')
        ->join(" seller_data sd", "p.seller_id=sd.user_id ", 'LEFT')
        ->join('`product_variants` pv', 'p.id = pv.product_id', 'LEFT')
        ->join('`product_attributes` pa', ' pa.product_id = p.id ', 'LEFT');

    if (isset($filter) && !empty($filter['search'])) {
        $tags = explode(" ", $filter['search']);
        $t->db->group_Start();
        foreach ($tags as $i => $tag) {
            if ($i == 0) {
                $t->db->like('p.tags', trim($tag));
            } else {
                $t->db->or_like('p.tags', trim($tag));
            }
        }
        $product_count->or_like('p.name', $filter['search']);
        $t->db->group_End();
    }
    if (isset($filter) && !empty($filter['flag'])) {
        $flag = $filter['flag'];
        if ($flag == 'low') {
            $t->db->group_Start();
            $where1 = "p.stock_type is  NOT NULL";
            $t->db->where($where1);
            $t->db->where('p.stock <=', $low_stock_limit);
            $t->db->where('p.availability =', '1');
            $t->db->or_where('pv.stock <=', $low_stock_limit);
            $t->db->where('pv.availability =', '1');
            $t->db->group_End();
        } else {
            $t->db->group_Start();
            $t->db->or_where('p.availability ', '0');
            $t->db->or_where('pv.availability ', '0');
            $t->db->where('p.stock ', '0');
            $t->db->or_where('pv.stock ', '0');
            $t->db->group_End();
        }
    }

    if (isset($filter) && !empty($filter['tags'])) {
        $tags = explode(",", $filter['tags']);
        $t->db->group_Start();
        foreach ($tags as $i => $tag) {
            if ($i == 0) {
                $t->db->like('p.tags', trim($tag));
            } else {
                $t->db->or_like('p.tags', trim($tag));
            }
        }
        $t->db->group_End();
    }

    if (isset($filter) && !empty($filter['brand'])) {
        $t->db->where('p.brand', trim($filter['brand']));
    }

    if (isset($filter) && !empty($filter['attribute_value_ids'])) {
        $str = str_replace(',', '|', $filter['attribute_value_ids']); // Ids should be in string and comma separated 
        $product_count->where('CONCAT(",", pa.attribute_value_ids, ",") REGEXP ",(' . $str . ')," !=', 0, false);
    }
    if (isset($filter) && !empty($filter['product_type']) && strtolower($filter['product_type']) == 'most_selling_products') {
        $product_count->join('`order_items` oi', 'oi.product_variant_id = pv.id', 'LEFT');
    }
    if (isset($category_id) && !empty($category_id)) {
        /* Must match the main query above, or the pager count disagrees with the rows shown. */
        $descendant_ids = category_descendant_ids($category_id);
        if (!empty($descendant_ids)) {
            $product_count->where_in('p.category_id', $descendant_ids);
        }
    }

    if (isset($filter) && !empty($filter['product_type']) && strtolower($filter['product_type']) == 'products_on_sale') {
        $product_count->where('pv.special_price >=', '0');
    }
    if (isset($id) && !empty($id) && $id != null) {
        if (is_array($id) && !empty($id)) {
            $product_count->where_in('p.id', $id);
        }
    }
    if (isset($seller_id) && !empty($seller_id) && $seller_id != "") {
        $where['p.seller_id'] = $seller_id;
    }
    if (isset($seller_id) && !empty($seller_id) && $seller_id != "") {
        if (isset($filter['show_only_stock_product']) && $filter['show_only_stock_product'] == 1) {
            $t->db->where('(p.stock != "" or pv.stock != "")');
        }
    }
    if (isset($filter['show_only_stock_product']) && $filter['show_only_stock_product'] == 1) {
        $t->db->where('(p.stock != "" or pv.stock != "")');
    }
    $product_count->where($where);
    // Must mirror the data query above exactly, or the row count and the rows disagree.
    if (!isset($filter['flag']) && empty($filter['flag']) && empty($filter['ignore_category_status'])) {
        $product_count->group_Start();
        $product_count->or_where('c.status', '1');
        $product_count->or_where('c.status', '0');
        $product_count->or_where('(c.id IS NOT NULL AND c.status IS NULL)', null, false);
        $product_count->group_End();
    }

    $count_res = $product_count->get('products p')->result_array();

    // return;
    $attribute_values_ids = array();
    $temp = [];
    // Context-aware slider bounds: min/max of the SAME filtered set being listed
    // (excluding the price filter itself), padded to a clean step. Replaces the
    // old global get_price() which ignored the category/brand/search context and
    // could leave shown products outside the slider range. See
    // get_filtered_price_range() for the full rationale.
    $price_range = get_filtered_price_range($filter, $category_id, $seller_id);
    $min_price = $price_range['min'];
    $max_price = $price_range['max'];

    /*
     * `total` used to be assigned ONLY inside the `if (!empty($product))` block below, so an
     * empty result set returned no `total` key at all. Every caller reads it unconditionally -
     * e.g. Product_model::get_stock_details() does `$total = $products['total']` - which meant
     * an empty list produced an "Undefined array key total" warning printed straight into the
     * JSON body, plus `"total": null`. bootstrap-table then has no row count to page with.
     * Confirmed live on Manage Stock (admin and seller).
     *
     * The count query has already run at this point, so the real figure is available whether or
     * not any rows came back.
     */
    $response['total'] = isset($count_res[0]['total']) ? (int) $count_res[0]['total'] : 0;

    if (!empty($product)) {

        $t->load->model('rating_model');

        /*
         * PERFORMANCE: the loop below used to issue ~8 queries PER PRODUCT (variants
         * twice over, attributes, min/max price, stock per variant, the seller's
         * product count, review images, cart quantity). Measured on this database a
         * single /products render cost 976 queries to show 119 products.
         *
         * Everything those queries fetch is now pulled up front in a fixed handful of
         * WHERE ... IN (...) statements. The loop body is otherwise unchanged: the
         * same helpers are called with the same arguments and perform the same
         * transformations - they just read their rows from the prefetch instead of
         * going back to the database one product at a time.
         *
         * The scope is closed in a finally block so an exception raised anywhere in
         * the loop cannot leave a stale cache visible to the rest of the request.
         */
        product_batch_open($product, $user_id);
        try {
        for ($i = 0; $i < count($product); $i++) {

            $rating = $t->rating_model->fetch_rating($product[$i]['id'], '', 8, 0, 'pr.id', 'desc', '', 1);
            $product[$i]['review_images'] = (!empty($rating)) ? [$rating] : array();

            $product[$i]['tax_percentage'] = (isset($product[$i]['tax_percentage']) && intval($product[$i]['tax_percentage']) > 0) ? $product[$i]['tax_percentage'] : '0';
            $product[$i]['tax_id'] = ((isset($product[$i]['tax_id']) && intval($product[$i]['tax_id']) > 0) && $product[$i]['tax_id'] != "") ? $product[$i]['tax_id'] : '0';
            $product[$i]['attributes'] = get_attribute_values_by_pid($product[$i]['id']);
            // print_r($product[$i]['attributes']);
            // die;
            /*
             * This called get_variants_values_by_pid() TWICE with identical arguments
             * and assigned the two identical results to two different variables - one
             * wasted query per product, 119 of them on a single listing page. The one
             * result now feeds both.
             */
            $product[$i]['variants'] = get_variants_values_by_pid($product[$i]['id']);
            $variants = $product[$i]['variants'];
            $total_stock = 0;
            foreach ($variants as $variant) {
                $stock = (isset($variant['stock']) && !empty($variant['stock'])) ? $variant['stock'] : 0;
                $total_stock  += $stock;
                $product[$i]['total_stock'] = isset($total_stock) && !empty($total_stock) ? $total_stock : '';
            }
            $product[$i]['min_max_price'] = get_min_max_price_of_product($product[$i]['id']);
            $product[$i]['stock_type'] = isset($product[$i]['stock_type']) && ($product[$i]['stock_type'] != '') ? $product[$i]['stock_type'] : '';
            $product[$i]['stock'] = isset($product[$i]['stock']) && !empty($product[$i]['stock']) ? $product[$i]['stock'] : '';
            $product[$i]['relative_path'] = isset($product[$i]['image']) && !empty($product[$i]['image']) ? $product[$i]['image'] : '';
            $product[$i]['other_images_relative_path'] = isset($product[$i]['other_images']) && !empty($product[$i]['other_images']) ? json_decode($product[$i]['other_images']) : [];
            $product[$i]['video_relative_path'] = (isset($product[$i]['video']) && (!empty($product[$i]['video']))) ? $product[$i]['video'] : "";
            $product[$i]['video_type'] = isset($product[$i]['video_type']) && !empty($product[$i]['video_type']) ? $product[$i]['video_type'] : '';
            $product[$i]['attr_value_ids'] = isset($product[$i]['attr_value_ids']) && !empty($product[$i]['attr_value_ids']) ? $product[$i]['attr_value_ids'] : '';
            $product[$i]['made_in'] = isset($product[$i]['made_in']) && !empty($product[$i]['made_in']) ? $product[$i]['made_in'] : '';
            $product[$i]['hsn_code'] = isset($product[$i]['hsn_code']) && !empty($product[$i]['hsn_code']) ? $product[$i]['hsn_code'] : '';
            $product[$i]['brand'] = isset($product[$i]['brand']) && !empty($product[$i]['brand']) ? $product[$i]['brand'] : '';


            // echo "<pre>";
            // print_r($product[$i]);
            // die;

            $product[$i]['warranty_period'] = isset($product[$i]['warranty_period']) && !empty($product[$i]['warranty_period']) ? $product[$i]['warranty_period'] : '';
            $product[$i]['guarantee_period'] = isset($product[$i]['guarantee_period']) && !empty($product[$i]['guarantee_period']) ? $product[$i]['guarantee_period'] : '';
            $product[$i]['total_allowed_quantity'] = isset($product[$i]['total_allowed_quantity']) && !empty($product[$i]['total_allowed_quantity']) ? $product[$i]['total_allowed_quantity'] : '';
            $product[$i]['download_allowed'] = isset($product[$i]['download_allowed']) && !empty($product[$i]['download_allowed']) ? $product[$i]['download_allowed'] : '';
            $product[$i]['download_type'] = isset($product[$i]['download_type']) && !empty($product[$i]['download_type']) ? $product[$i]['download_type'] : '';
            $product[$i]['download_link'] = isset($product[$i]['download_link']) && !empty($product[$i]['download_link']) ? $product[$i]['download_link'] : '';
            $product[$i]['status'] = isset($product[$i]['status']) && !empty($product[$i]['status']) ? $product[$i]['status'] : '';
            /*
             * PERFORMANCE: one COUNT per product, re-counting the SAME seller once for
             * every product of theirs on the page. The prefetch does it as a single
             * GROUP BY seller_id. COUNT() returns a string from mysqli and that string
             * was assigned straight through, so the batch preserves the string type.
             */
            if (Product_batch::is_open() && array_key_exists((string) $product[$i]['seller_id'], Product_batch::$seller_product_count)) {
                $total_product = array(array('total' => Product_batch::$seller_product_count[(string) $product[$i]['seller_id']]));
            } else {
                $total_product = $t->db->query("select count(id) as total  from products where products.seller_id=" . $product[$i]['seller_id'] . " AND products.status='1' AND products.listing_visibility=1")->result_array();
            }

            /* outputing escaped data */
            $product[$i]['name'] = output_escaping($product[$i]['name']);
            $product[$i]['total_product'] = ($total_product[0]['total']);
            $product[$i]['store_name'] = output_escaping($product[$i]['store_name']);
            $product[$i]['seller_rating'] = (isset($product[$i]['seller_rating']) && !empty($product[$i]['seller_rating'])) ? output_escaping(number_format($product[$i]['seller_rating'], 1)) : 0;
            $product[$i]['store_description'] = (isset($product[$i]['store_description']) && !empty($product[$i]['store_description'])) ? output_escaping($product[$i]['store_description']) : "";
            $product[$i]['seller_profile'] = output_escaping(base_url() . $product[$i]['seller_profile']);
            $product[$i]['seller_name'] = output_escaping($product[$i]['seller_name']);
            $product[$i]['short_description'] = output_escaping($product[$i]['short_description']);
            $product[$i]['description'] = (isset($product[$i]['description']) && !empty($product[$i]['description'])) ? output_escaping($product[$i]['description']) : "";
            $product[$i]['extra_description'] = (isset($product[$i]['extra_description']) && !empty($product[$i]['extra_description']) && $product[$i]['extra_description'] != 'NULL') ? output_escaping($product[$i]['extra_description']) : "";
            $product[$i]['pickup_location'] = (isset($product[$i]['pickup_location']) && !empty($product[$i]['pickup_location']) && $product[$i]['pickup_location']) != 'NULL' ? $product[$i]['pickup_location'] : '';

            $product[$i]['seller_slug'] = isset($product[$i]['seller_slug']) && !empty($product[$i]['seller_slug']) ? output_escaping($product[$i]['seller_slug']) : "";
            $product[$i]['deliverable_type'] = $product[$i]['deliverable_type'];
            $product[$i]['deliverable_zipcodes_ids'] = output_escaping($product[$i]['deliverable_zipcodes']);
            if (isset($filter['discount']) && !empty($filter['discount']) && $filter['discount'] != "") {
                $product[$i]['cal_discount_percentage'] = output_escaping(number_format($product[$i]['cal_discount_percentage'], 2));
            }
            $product[$i]['cancelable_till'] = isset($product[$i]['cancelable_till']) && !empty($product[$i]['cancelable_till']) ? $product[$i]['cancelable_till'] : '';
            $product[$i]['is_attachment_required'] = isset($product[$i]['is_attachment_required']) && !empty($product[$i]['is_attachment_required']) ? $product[$i]['is_attachment_required'] : '0';
            $product[$i]['indicator'] = isset($product[$i]['indicator']) && !empty($product[$i]['indicator']) ? $product[$i]['indicator'] : '0';
            $product[$i]['deliverable_zipcodes_ids'] = isset($product[$i]['deliverable_zipcodes_ids']) && !empty($product[$i]['deliverable_zipcodes_ids']) ? $product[$i]['deliverable_zipcodes_ids'] : '';
            $product[$i]['rating'] = output_escaping(number_format($product[$i]['rating'], 2));
            $product[$i]['availability'] = isset($product[$i]['availability']) && ($product[$i]['availability'] != "") ? $product[$i]['availability'] : '';
            $product[$i]['sku'] = isset($product[$i]['sku']) && ($product[$i]['sku'] != "") ? $product[$i]['sku'] : '';

            /* getting zipcodes from ids */
            if ($product[$i]['deliverable_type'] != NONE && $product[$i]['deliverable_type'] != ALL) {
                $zipcodes = array();
                $zipcode_ids = explode(",", $product[$i]['deliverable_zipcodes_ids']);
                // $t->db->select('zipcode');
                // $t->db->where_in('id', $zipcode_ids);
                // $zipcodes = $t->db->get('zipcodes')->result_array();
                $zipcodes = array_column($zipcodes, "zipcode");
                $product[$i]['deliverable_zipcodes'] = implode(",", $zipcode_ids);
            } else {
                $product[$i]['deliverable_zipcodes'] = '';
            }
            $product[$i]['category_name'] = (isset($product[$i]['category_name']) && !empty($product[$i]['category_name'])) ? output_escaping($product[$i]['category_name']) : '';
            /* check product delivrable or not */
            if ($is_deliverable != NULL) {
                $zipcode = fetch_details('zipcodes', ['zipcode' => $is_deliverable], 'id');
                if (!empty($zipcode)) {
                    $product[$i]['is_deliverable'] = is_product_delivarable($type = 'zipcode', $zipcode[0]['id'], $product[$i]['id']);
                } else {
                    $product[$i]['is_deliverable'] = false;
                }
            } else {
                $product[$i]['is_deliverable'] = false;
            }

            if ($product[$i]['deliverable_type'] == 1) {
                $product[$i]['is_deliverable'] = true;
            }


            // Tags may be stored comma- OR semicolon-separated (seed/import data is
            // inconsistent). Split on both, trim each tag, and drop empties so the
            // parsed value is always a clean array of individual tags — otherwise a
            // semicolon-joined string lands whole in tags[0] and prints as one blob
            // (e.g. "bag;sling bag;canvas bag") on the product cards.
            $product[$i]['tags'] = (!empty($product[$i]['tags']))
                ? array_values(array_filter(array_map('trim', preg_split('/[;,]/', $product[$i]['tags']))))
                : [];

            $product[$i]['video'] = (isset($product[$i]['video_type']) && (!empty($product[$i]['video_type']) || $product[$i]['video_type'] != NULL)) ? (($product[$i]['video_type'] == 'youtube' || $product[$i]['video_type'] == 'vimeo') ? $product[$i]['video'] : base_url($product[$i]['video'])) : "";
            $product[$i]['minimum_order_quantity'] = isset($product[$i]['minimum_order_quantity']) && (!empty($product[$i]['minimum_order_quantity'])) ? $product[$i]['minimum_order_quantity'] : 1;
            $product[$i]['quantity_step_size'] = isset($product[$i]['quantity_step_size']) && (!empty($product[$i]['quantity_step_size'])) ? $product[$i]['quantity_step_size'] : 1;
            /*
             * These two accumulators used to be declared INSIDE the `if (!empty(...variants))`
             * block below, but they are consumed unconditionally further down (array_count_values
             * on $is_purchased_count, array_count_values on $count_stock). A product with no
             * variant rows therefore reached
             *   array_count_values(): Argument #1 ($array) must be of type array, null given
             * which is a fatal TypeError on PHP 8. The storefront never hit it because its WHERE
             * carries `pv.status = 1`, so variantless products are filtered out - but every
             * administrative read that passes show_only_active_products = 0 (Manage Stock, the
             * product exports) does reach them, and 13 active products on this database have no
             * variant rows.
             *
             * Declaring them here preserves the intended semantics exactly: for a variantless
             * product both stay empty, array_sum([]) is 0, and `is_purchased` resolves to false -
             * the same value the loop would have produced had it run and pushed nothing.
             */
            $count_stock = array();
            $is_purchased_count = array();
            if (!empty($product[$i]['variants'])) {
                for ($k = 0; $k < count($product[$i]['variants']); $k++) {

                    $variant_other_images = $variant_other_images_sm = $variant_other_images_md = json_decode((string)$product[$i]['variants'][$k]['images'], 1);

                    if (!empty($variant_other_images[0]) && isset($variant_other_images[0])) {

                        $product[$i]['variants'][$k]['variant_relative_path'] = isset($product[$i]['variants'][$k]['images']) && !empty($product[$i]['variants'][$k]['images']) ? json_decode($product[$i]['variants'][$k]['images']) : [];
                        $counter = 0;
                        foreach ($variant_other_images_md as $row) {
                            $variant_other_images_md[$counter] = get_image_url($variant_other_images_md[$counter], 'thumb', 'md');
                            $counter++;
                        }
                        $product[$i]['variants'][$k]['images_md'] = isset($variant_other_images_md) && !empty($variant_other_images_md) ? $variant_other_images_md : "";

                        $counter = 0;
                        foreach ($variant_other_images_sm as $row) {
                            $variant_other_images_sm[$counter] = get_image_url($variant_other_images_sm[$counter], 'thumb', 'sm');
                            $counter++;
                        }
                        $product[$i]['variants'][$k]['images_sm'] = $variant_other_images_sm;

                        $counter = 0;
                        foreach ($variant_other_images as $row) {
                            $variant_other_images[$counter] = get_image_url($variant_other_images[$counter]);
                            $counter++;
                        }
                        $product[$i]['variants'][$k]['images'] = isset($variant_other_images) && !empty($variant_other_images) ? $variant_other_images : "";
                    } else {
                        $product[$i]['variants'][$k]['images'] = array();
                        $product[$i]['variants'][$k]['images_md'] = array();
                        $product[$i]['variants'][$k]['images_sm'] = array();
                        $product[$i]['variants'][$k]['variant_relative_path'] = array();
                    }
                    $product[$i]['variants'][$k]['swatche_type'] = (!empty($product[$i]['variants'][$k]['swatche_type'])) ? $product[$i]['variants'][$k]['swatche_type'] : "0";
                    $product[$i]['variants'][$k]['swatche_value'] = (!empty($product[$i]['variants'][$k]['swatche_value'])) ? $product[$i]['variants'][$k]['swatche_value'] : "0";
                    if (($product[$i]['stock_type'] == 0  || $product[$i]['stock_type'] == null)) {
                        if ($product[$i]['availability'] != null) {
                            $product[$i]['variants'][$k]['availability'] = $product[$i]['availability'];
                        }
                    } else {
                        $product[$i]['variants'][$k]['availability'] = ($product[$i]['variants'][$k]['availability'] != null) ? $product[$i]['variants'][$k]['availability'] : 1;
                        array_push($count_stock, $product[$i]['variants'][$k]['availability']);
                    }
                    if (($product[$i]['stock_type'] == 0)) {
                        $product[$i]['variants'][$k]['stock'] = isset($product[$i]['variants'][$k]['stock']) && !empty($product[$i]['variants'][$k]['stock']) ? get_stock($product[$i]['id'], 'product') : '';
                    } else {
                        $product[$i]['variants'][$k]['stock'] = isset($product[$i]['variants'][$k]['stock']) && !empty($product[$i]['variants'][$k]['stock']) ? get_stock($product[$i]['variants'][$k]['id'], 'variant') : '';
                    }
                    $percentage = (isset($product[$i]['tax_percentage']) && intval($product[$i]['tax_percentage']) > 0 && $product[$i]['tax_percentage'] != null) ? $product[$i]['tax_percentage'] : '0';
                    if ((isset($product[$i]['is_prices_inclusive_tax']) && $product[$i]['is_prices_inclusive_tax'] == 0) || (!isset($product[$i]['is_prices_inclusive_tax'])) && $percentage > 0) {
                        $price_tax_amount = $product[$i]['variants'][$k]['price'] * ($percentage / 100);
                        $product[$i]['variants'][$k]['price'] =  strval($product[$i]['variants'][$k]['price'] + $price_tax_amount);
                        $special_price_tax_amount = $product[$i]['variants'][$k]['special_price'] * ($percentage / 100);
                        $product[$i]['variants'][$k]['special_price'] =  strval($product[$i]['variants'][$k]['special_price'] + $special_price_tax_amount);
                    } else {
                        $product[$i]['variants'][$k]['price'] =  strval($product[$i]['variants'][$k]['price']);
                        $product[$i]['variants'][$k]['special_price'] =  strval($product[$i]['variants'][$k]['special_price']);
                    }
                    if (isset($user_id) && $user_id != NULL) {
                        // PERFORMANCE: one cart lookup per variant for a logged-in
                        // shopper; prefetched in a single query keyed by variant+user.
                        $user_cart_data = product_batch_get('cart', $product[$i]['variants'][$k]['id'] . '|' . $user_id);
                        if ($user_cart_data === null) {
                            $user_cart_data = $t->db->select('qty as cart_count')->where(['product_variant_id' => $product[$i]['variants'][$k]['id'], 'user_id' => $user_id, 'is_saved_for_later' => 0])->get('cart')->result_array();
                        }
                        if (!empty($user_cart_data)) {
                            $product[$i]['variants'][$k]['cart_count'] = $user_cart_data[0]['cart_count'];
                        } else {
                            $product[$i]['variants'][$k]['cart_count'] = "0";
                        }
                        /*
                         * PERFORMANCE: "has this shopper already bought this variant?".
                         * This ran once per VARIANT and was the biggest single source of
                         * queries left on a logged-in storefront page - 42 of the 147 the
                         * homepage issued. Prefetched in one grouped query; see bucket 10
                         * in batch_helper.php. Falls through to the original lookup when
                         * no scope is open, so nothing outside fetch_product() changes.
                         */
                        $is_purchased = product_batch_get('purchased', $product[$i]['variants'][$k]['id'] . '|' . $user_id);
                        if ($is_purchased === null) {
                            $is_purchased = $t->db->where(['oi.product_variant_id' => $product[$i]['variants'][$k]['id'], 'oi.user_id' => $user_id])->order_by('oi.id', 'DESC')->limit(1)->get('order_items oi')->result_array();
                        }

                        if (!empty($is_purchased) && strtolower($is_purchased[0]['active_status']) == 'delivered') {
                            array_push($is_purchased_count, 1);
                            $product[$i]['variants'][$k]['is_purchased'] = 1;
                        } else {
                            array_push($is_purchased_count, 0);
                            $product[$i]['variants'][$k]['is_purchased'] = 0;
                        }

                        // PERFORMANCE: keyed only on (user_id, product_id) yet executed
                        // once per VARIANT, so a five-variant product ran it five times
                        // for one answer. Prefetched once per product.
                        $user_rating = product_batch_get('user_rating', $product[$i]['id'] . '|' . $user_id);
                        if ($user_rating === null) {
                            $user_rating = $t->db->select('rating,comment')->where(['user_id' => $user_id, 'product_id' => $product[$i]['id']])->get('product_rating')->result_array();
                        }
                        if (!empty($user_rating)) {
                            $product[$i]['user']['user_rating'] =   (isset($product[$i]['user']['user_rating']) && (!empty($product[$i]['user']['user_rating']))) ? $user_rating[0]['rating'] : '';
                            $product[$i]['user']['user_comment'] =   (isset($product[$i]['user']['user_comment']) && (!empty($product[$i]['user']['user_comment']))) ? $user_rating[0]['user_comment'] : '';
                        }
                    } else {
                        $product[$i]['variants'][$k]['cart_count'] = "0";
                    }
                }
            }

            $is_purchased_count = array_count_values($is_purchased_count);
            $is_purchased_count = array_keys($is_purchased_count);
            $product[$i]['is_purchased'] = (isset($is_purchased) && array_sum($is_purchased_count) == 1) ? true : false;

            if (($product[$i]['stock_type'] != null && !empty($product[$i]['stock_type']))) {


                //Case 2 & 3 : Product level(variable product) ||  Variant level(variable product)
                if ($product[$i]['stock_type'] == 1 || $product[$i]['stock_type'] == 2) {
                    $counts = array_count_values($count_stock);
                    $counts = array_keys($counts);
                    if (isset($counts)) {
                        $product[$i]['availability'] = array_sum($counts);
                    }
                }
            }

            if (isset($user_id) && $user_id != null) {
                // PERFORMANCE: one query per product for a logged-in shopper.
                // Prefetched as a single GROUP BY. num_rows() returned a count, so the
                // batch stores counts and unfavourited products resolve to 0.
                $fav_key = $product[$i]['id'] . '|' . $user_id;
                if (Product_batch::is_open() && array_key_exists($fav_key, Product_batch::$favorites)) {
                    $fav = Product_batch::$favorites[$fav_key];
                } else {
                    $fav = $t->db->where(['product_id' => $product[$i]['id'], 'user_id' => $user_id])->get('favorites')->num_rows();
                }
                $product[$i]['is_favorite'] = $fav;
            } else {
                $product[$i]['is_favorite'] = '0';
            }

            $product[$i]['image_md'] = get_image_url($product[$i]['image'], 'thumb', 'md');
            $product[$i]['image_sm'] = get_image_url($product[$i]['image'], 'thumb', 'sm');
            $product[$i]['image'] = get_image_url($product[$i]['image']);
            $other_images = $other_images_sm =  $other_images_md = json_decode($product[$i]['other_images'], 1);

            if (!empty($other_images)) {

                $k = 0;
                foreach ($other_images_md as $row) {
                    $other_images_md[$k] = get_image_url($row, 'thumb', 'md');
                    $k++;
                }
                $other_images_md = (array) $other_images_md;
                $other_images_md = array_values($other_images_md);
                $product[$i]['other_images_md'] = $other_images_md;

                $k = 0;
                foreach ($other_images_sm as $row) {
                    $other_images_sm[$k] = get_image_url($row, 'thumb', 'sm');
                    $k++;
                }
                $other_images_sm = (array) $other_images_sm;
                $other_images_sm = array_values($other_images_sm);
                $product[$i]['other_images_sm'] = $other_images_sm;

                $k = 0;
                foreach ($other_images as $row) {
                    $other_images[$k] = get_image_url($row);
                    $k++;
                }
                $other_images = (array) $other_images;
                $other_images = array_values($other_images);
                $product[$i]['other_images'] = $other_images;
            } else {
                $product[$i]['other_images'] = array();
                $product[$i]['other_images_sm'] = array();
                $product[$i]['other_images_md'] = array();
            }
            $tags_to_strip = array("table", "<th>", "<td>");
            $replace_with = array("", "h3", "p");
            $n = 0;
            foreach ($tags_to_strip as $tag) {
                // $product[$i]['description'] = output_escaping(str_replace('\r\n', '&#13;&#10;', (string)$product[$i]['description']));
                $product[$i]['description'] = !empty($product[$i]['description']) ? output_escaping(str_replace('\r\n', '&#13;&#10;', (string)$product[$i]['description'])) : "";
                $product[$i]['extra_description'] = !empty($product[$i]['extra_description']) && $product[$i]['extra_description'] != null ? output_escaping(str_replace('\r\n', '&#13;&#10;', (string)$product[$i]['extra_description'])) : "";
                $n++;
            }
            $variant_attributes = [];
            /*
             * A product with no variant rows has an empty `variants` array, so [0] was an
             * "Undefined array key 0" warning immediately followed by "Trying to access array
             * offset on value of type null". 13 ACTIVE products on this database have no
             * variants, and in development those warnings print into the response body and
             * corrupt the JSON the product lists are parsing.
             */
            $first_variant_attr_names = isset($product[$i]['variants'][0]['attr_name'])
                ? $product[$i]['variants'][0]['attr_name']
                : '';
            $attributes_array = ($first_variant_attr_names !== '') ? explode(',', $first_variant_attr_names) : [];

            foreach ($attributes_array as $attribute) {
                $attribute = trim($attribute);
                $key = array_search($attribute, array_column($product[$i]['attributes'], 'name'), false);
                /*
                 * These four reads were indexed $product[0] while $key was searched for in
                 * $product[$i] - so for every product after the first in a list, the attribute
                 * ids, values and colour/image swatches were copied off the FIRST product in the
                 * result set. It looked correct on product-detail pages (one product, $i is
                 * always 0) and silently wrong on listings, category pages and search results.
                 */
                if (($key === 0 || !empty($key)) && isset($product[$i]['attributes'][$key])) {
                    $variant_attributes[$key]['ids'] = $product[$i]['attributes'][$key]['ids'];
                    $variant_attributes[$key]['values'] = $product[$i]['attributes'][$key]['value'];
                    $variant_attributes[$key]['swatche_type'] = $product[$i]['attributes'][$key]['swatche_type'];
                    $variant_attributes[$key]['swatche_value'] = $product[$i]['attributes'][$key]['swatche_value'];
                    $variant_attributes[$key]['attr_name'] = $attribute;
                }
            }
            $product[$i]['variant_attributes'] = $variant_attributes;
        }
        } finally {
            // Always closes, so a throw inside the loop cannot leave prefetched rows
            // visible to unrelated code later in the request.
            product_batch_close();
        }

        if (isset($count_res[0]['cal_discount_percentage'])) {
            $dicounted_total = array_values(array_filter(explode(',', $count_res[0]['cal_discount_percentage'])));
        } else {
            $dicounted_total = 0;
        }
        // Overridden here only for the discount filter, which counts differently. Otherwise the
        // value set before this block (which also covers the empty-result case) already stands.
        $response['total'] = (isset($filter) && !empty($filter['discount']))
            ? count($dicounted_total)
            : (isset($count_res[0]['total']) ? (int) $count_res[0]['total'] : 0);

        array_push($attribute_values_ids, $count_res[0]['attr_value_ids']);
        $attribute_values_ids = implode(",", $attribute_values_ids);
        $attr_value_ids = array_filter(array_unique(explode(',', $attribute_values_ids)));
    }
    $response['min_price'] = $min_price;
    $response['max_price'] = $max_price;
    $response['product'] = $product;
    if (isset($filter) && $filter != null) {
        if (!empty($attr_value_ids)) {
            $response['filters'] = get_attribute_values_by_id($attr_value_ids);
        }
    } else {
        $response['filters'] = [];
    }
    // print_r($response['filters']);
    // print_r($response);
    // die;
    return $response;
}

function update_details($set, $where, $table, $escape = true)
{
    $t = &get_instance();
    $t->db->trans_start();
    if ($escape) {
        $set = escape_array($set);
    }
    $t->db->set($set)->where($where)->update($table);
    $t->db->trans_complete();
    $response = FALSE;
    if ($t->db->trans_status() === TRUE) {
        $response = TRUE;
    }
    return $response;
}

function delete_image($id, $path, $field, $img_name, $table_name, $isjson = TRUE)
{
    $t = &get_instance();
    $t->db->trans_start();
    if ($isjson == TRUE) {
        $image_set = fetch_details($table_name, ['id' => $id], $field);
        $diff_new_image_set = json_decode($image_set[0][$field]);
        $new_image_set = escape_array(array_diff((array)$diff_new_image_set, array($img_name)));
        $new_image_set = json_encode($new_image_set);
        $t->db->set([$field => $new_image_set])->where('id', $id)->update($table_name);
        $t->db->trans_complete();
        $response = FALSE;
        if ($t->db->trans_status() === TRUE) {
            $response = TRUE;
        }
    } else {
        $t->db->set([$field => ' '])->where(['id' => $id])->update($table_name);
        $t->db->trans_complete();
        $response = FALSE;
        if ($t->db->trans_status() === TRUE) {
            $response = TRUE;
        }
    }
    return $response;
}

function delete_details($where, $table)
{
    $t = &get_instance();
    if ($t->db->where($where)->delete($table)) {
        return true;
    } else {
        return false;
    }
}

//JSON Validator function
function is_json($data = NULL)
{
    if (!empty($data)) {
        @json_decode($data);
        return (json_last_error() === JSON_ERROR_NONE);
    }
    return false;
}

//validate_promo_code
/**
 * How much a promo code takes off the amount the customer pays RIGHT NOW.
 *
 * Zero for a cashback code, by definition: a cashback code does not reduce the bill, it pays
 * the customer back afterwards (settle_cashback_discount() credits their wallet once the order
 * is delivered and the return window has passed).
 *
 * This exists because 'final_discount' is populated for cashback codes too - it is the value
 * that will eventually be credited - and the checkout screens were subtracting it from the
 * amount charged. A cashback code therefore discounted the payment AND paid the cashback: the
 * customer was charged the reduced amount at the gateway while the order recorded the full
 * total_payable, and the cashback landed in their wallet days later. Callers that are sizing a
 * payment, a wallet deduction or a gateway charge must use this; callers reporting the value
 * of the code to the customer want final_discount.
 *
 * @param  array $promo_row
 * @param  float $discount
 * @return string
 */
function promo_checkout_discount($promo_row, $discount)
{
    $is_cashback = isset($promo_row['is_cashback']) && $promo_row['is_cashback'] == 1;
    return strval($is_cashback ? 0 : floatval($discount));
}

/**
 * Applies a promo code row's discount to a total and builds the success payload for it.
 *
 * This math existed inline in two byte-identical copies inside validate_promo_code(), and the
 * refund recalculation path kept a third, subtly different copy of its own. Extracted so the
 * campaign ceiling and the clamp below cannot end up applying to only some of the callers -
 * which is exactly how the refund path came to report a discount the checkout path would have
 * capped.
 *
 * @param  array $promo_row  a row from `promo_codes`
 * @param  float $final_total the amount the discount applies to
 * @return array the row, with final_total / final_discount / checkout_discount filled in
 */
function apply_promo_code_discount($promo_row, $final_total)
{
    $final_total = floatval($final_total);
    $is_cashback = isset($promo_row['is_cashback']) && $promo_row['is_cashback'] == 1;

    $discount = ($promo_row['discount_type'] == 'percentage')
        ? floatval($final_total * $promo_row['discount'] / 100)
        : floatval($promo_row['discount']);

    /* cap at the campaign's own ceiling */
    $max_discount = floatval($promo_row['max_discount_amount']);
    if ($discount > $max_discount) {
        $discount = $max_discount;
    }

    // ...and cap at the order itself. Nothing stops an admin creating a flat "amount" code
    // worth more than the cart it is used on, and the reported discount was left uncapped:
    // the customer's displayed total was floored at 0, but place_order() recomputes the
    // discount from the same row with no floor at all, writing orders.total = 1000 - 5000 =
    // -4000 and orders.promo_discount = 5000. process_refund() then sizes refunds and the
    // per-seller order_charges split off that figure. A discount can never exceed the thing
    // being discounted.
    if ($discount > $final_total) {
        $discount = $final_total;
    }

    /* a cashback code is paid to the wallet after delivery, so it does not reduce the total */
    $total = $is_cashback ? $final_total : $final_total - $discount;

    $promo_row['final_total'] = strval(floatval(max(0, $total)));
    $promo_row['image'] = (isset($promo_row['image']) && !empty($promo_row['image'])) ? $promo_row['image'] : '';
    $promo_row['final_discount'] = strval(floatval($discount));
    $promo_row['checkout_discount'] = promo_checkout_discount($promo_row, $discount);

    return $promo_row;
}

/**
 * @param bool $skip_usage_checks  recalculation mode - see the block guarded by it below.
 */
function validate_promo_code($promo_code, $user_id, $final_total, $skip_usage_checks = false)
{
    // Every caller reads $res['error'] straight off the result. With no code supplied this
    // function used to fall off the end and return NULL, so the caller got
    // "Trying to access array offset on value of type null" and then treated the missing error
    // flag as success. Return the same shape as every other exit.
    if (!isset($promo_code) || $promo_code === '' || $promo_code === null) {
        return [
            'error'   => true,
            'message' => 'No promo code was supplied.',
            'data'    => ['final_total' => strval(floatval($final_total))],
        ];
    }

    {
        $t = &get_instance();

        // user_id and promo_code were spliced directly into this raw SELECT subquery string
        // with no escaping at all - promo_code in particular is attacker-controlled (typed into
        // the "apply promo code" box at checkout), so a crafted value could break out of the
        // quoted string and inject arbitrary SQL. $t->db->escape() quotes/escapes it safely for
        // this raw-string context; user_id is cast to int since it's only ever a numeric id.
        $promo_code_input = $promo_code;

        // Both counters exclude orders that did not stand. They used to count every row in
        // `orders` carrying the code, so a cancelled order permanently burned a slot: nothing
        // anywhere releases promo quota when an order is cancelled or refunded, and a customer
        // whose order failed could not re-use their own single-use code.
        //
        // Returned orders are released for the same reason cancelled ones are: the sale did not
        // stand, the customer was refunded, and the campaign got none of what it was paying
        // for. Excluding only cancellations meant a customer whose single-use code was on an
        // order they returned in full could never use that code again, while a customer who
        // cancelled the identical order could.
        //
        // promo_used_counter counts DISTINCT USERS, not orders. It is compared against
        // no_of_users, and the message shown to the customer says "applicable only for first N
        // users" - but count(o.id) is a count of ORDERS, so on a repeat-usage code a handful of
        // customers placing several orders each exhausted a "first 100 users" campaign long
        // before 100 people had seen it.
        // There is no orders.active_status column - this app tracks status per order_item - so
        // "still stands" means the order has at least one line that is neither cancelled nor
        // returned.
        $cancelled_clause = " AND EXISTS (SELECT 1 FROM order_items oi2 WHERE oi2.order_id = o2.id AND oi2.active_status NOT IN ('cancelled', 'returned'))";
        $used_by_subquery = '(SELECT COUNT(DISTINCT o2.user_id) FROM orders o2 WHERE o2.promo_code = pc.promo_code' . $cancelled_clause . ')';
        $user_usage_subquery = '(SELECT COUNT(o2.id) FROM orders o2 WHERE o2.user_id = ' . (int) $user_id
            . ' AND o2.promo_code = ' . $t->db->escape($promo_code_input) . $cancelled_clause . ')';

        // In recalculation mode the campaign's status and date range are deliberately not
        // applied: the order in hand already carries this code, and a campaign that has since
        // been switched off or run past its end date must not retroactively change what that
        // order was discounted by.
        $promo_where = ['pc.promo_code' => $promo_code_input];
        if (!$skip_usage_checks) {
            $promo_where['pc.status'] = '1';
            $promo_where[' start_date <= '] = date('Y-m-d');
            $promo_where['  end_date >= '] = date('Y-m-d');
        }

        $promo_code = $t->db->select('pc.*, ' . $used_by_subquery . ' as promo_used_counter, ' . $user_usage_subquery . ' as user_promo_usage_counter', false)
            ->where($promo_where)
            ->get('promo_codes pc')->result_array();
        if (!empty($promo_code[0]['id'])) {

            // Recalculation mode, used when an order that ALREADY carries this code is being
            // partially cancelled or returned and its discount has to be resized to the
            // remaining cart. Whether the campaign is still live, how many people have used
            // it, and whether this customer has quota left are all settled questions by then -
            // the customer redeemed it on this very order. Re-asking them is what made
            // recalculate_promo_discount() return 0 for every single-use code, which
            // short-paid the customer by the entire promo discount on every refund.
            //
            // The minimum-order test is the one gate that still applies: a cart that has
            // shrunk below the campaign's minimum genuinely no longer qualifies, and forfeits
            // the discount.
            if ($skip_usage_checks) {
                if (floatval($final_total) < floatval($promo_code[0]['minimum_order_amount'])) {
                    return [
                        'error'   => true,
                        'message' => 'This promo code is applicable only for amount greater than or equal to ' . $promo_code[0]['minimum_order_amount'],
                        'data'    => ['final_total' => strval(floatval($final_total))],
                    ];
                }
                $promo_code[0] = apply_promo_code_discount($promo_code[0], $final_total);
                return [
                    'error'   => false,
                    'message' => 'The promo code is valid',
                    'data'    => $promo_code,
                ];
            }

            // A code that belongs to somebody. Referral coupons are issued to one named
            // customer and sit in their inbox, so "whoever types it first wins" is not an
            // acceptable rule for money owed to a specific person - and no_of_users cannot
            // express ownership, only a headcount.
            //
            // The guard applies ONLY to codes that have binding rows, so every campaign
            // that predates promo_code_users behaves exactly as it always has.
            if ($t->db->table_exists('promo_code_users')) {
                $bindings = $t->db->where('promo_code_id', $promo_code[0]['id'])
                    ->count_all_results('promo_code_users');

                if ($bindings > 0) {
                    $is_owner = $t->db->where('promo_code_id', $promo_code[0]['id'])
                        ->where('user_id', (int) $user_id)
                        ->count_all_results('promo_code_users');

                    if (!$is_owner) {
                        return [
                            'error'   => true,
                            'message' => 'This promo code was issued to another account.',
                            'data'    => ['final_total' => strval(floatval($final_total))],
                        ];
                    }
                }
            }

            // A user who has already used the code is inside the cap regardless of how many
            // distinct users have used it - otherwise a repeat-usage code stops working for
            // its existing users the moment the Nth user joins.
            $distinct_users = intval($promo_code[0]['promo_used_counter']);
            $already_used_by_this_user = intval($promo_code[0]['user_promo_usage_counter']) > 0;

            if ($already_used_by_this_user || $distinct_users < intval($promo_code[0]['no_of_users'])) {

                // floatval, not intval: a minimum of 499.50 was truncated to 499, letting a
                // 499.00 cart through.
                if (floatval($final_total) >= floatval($promo_code[0]['minimum_order_amount'])) {

                    // `<`, not `<=`. no_of_repeat_usage is the number of times a user may use
                    // the code; with `<=`, a user who had already used it exactly that many
                    // times still passed and got one more - every repeat-usage code granted
                    // N+1 redemptions per customer.
                    if ($promo_code[0]['repeat_usage'] == 1 && (intval($promo_code[0]['user_promo_usage_counter']) < intval($promo_code[0]['no_of_repeat_usage']))) {
                        if (intval($promo_code[0]['user_promo_usage_counter']) < intval($promo_code[0]['no_of_repeat_usage'])) {

                            $response['error'] = false;
                            $response['message'] = 'The promo code is valid';

                            $promo_code[0] = apply_promo_code_discount($promo_code[0], $final_total);
                            $response['data'] = $promo_code;
                            return $response;
                        } else {

                            $response['error'] = true;
                            $response['message'] = 'This promo code cannot be redeemed as it exceeds the usage limit';
                            $response['data']['final_total'] = strval(floatval($final_total));
                            return $response;
                        }
                    } else if ($promo_code[0]['repeat_usage'] == 0 && ($promo_code[0]['user_promo_usage_counter'] <= 0)) {
                        if (intval($promo_code[0]['user_promo_usage_counter']) <= intval($promo_code[0]['no_of_repeat_usage'])) {

                            $response['error'] = false;
                            $response['message'] = 'The promo code is valid';

                            // The flat-amount case here used to compute
                            // "$final_total - $promo_code[0]['discount']" as the DISCOUNT ITSELF
                            // rather than the flat discount amount, which collapsed to
                            // "$total = $discount" - a customer redeeming a non-repeatable
                            // flat-amount code paid only the discount value (a Rs. 500 order with
                            // a Rs. 10 discount charged just Rs. 10, not Rs. 490). Now shared with
                            // the repeat-usage branch so the two cannot drift again.
                            $promo_code[0] = apply_promo_code_discount($promo_code[0], $final_total);
                            $response['data'] = $promo_code;
                            return $response;
                        } else {

                            $response['error'] = true;
                            $response['message'] = 'This promo code cannot be redeemed as it exceeds the usage limit';
                            $response['data']['final_total'] = strval(floatval($final_total));
                            return $response;
                        }
                    } else {
                        $response['error'] = true;
                        // A repeat-usage code whose per-customer quota is spent landed here and
                        // was reported as "already redeemed, cannot be reused" - which
                        // contradicts the code being reusable and tells the customer nothing
                        // about the quota they just exhausted. (The dedicated usage-limit
                        // message inside the branch above is unreachable: that branch's own
                        // condition already requires quota to remain.)
                        $response['message'] = ($promo_code[0]['repeat_usage'] == 1)
                            ? 'This promo code cannot be redeemed as it exceeds the usage limit'
                            : 'The promo has already been redeemed. cannot be reused';
                        $response['data']['final_total'] = strval(floatval($final_total));
                        return $response;
                    }
                } else {

                    $response['error'] = true;
                    $response['message'] = 'This promo code is applicable only for amount greater than or equal to ' . $promo_code[0]['minimum_order_amount'];
                    $response['data']['final_total'] = strval(floatval($final_total));
                    return $response;
                }
            } else {

                $response['error'] = true;
                $response['message'] = "This promo code is applicable only for first " . $promo_code[0]['no_of_users'] . " users";
                $response['data']['final_total'] = strval(floatval($final_total));
                return $response;
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'The promo code is not available or expired';
            $response['data']['final_total'] = strval(floatval($final_total));
            return $response;
        }
    }

    // Unreachable today - every branch above returns - but a function whose result is read as
    // an array must never be able to hand back NULL.
    return [
        'error'   => true,
        'message' => 'The promo code could not be validated.',
        'data'    => ['final_total' => strval(floatval($final_total))],
    ];
}

//update_wallet_balance
function update_wallet_balance($operation, $user_id, $amount, $message = "Balance Debited", $order_item_id = "", $is_refund = 0, $transaction_type = 'wallet')
{

    $t = &get_instance();
    $user_balance = $t->db->select('balance')->where(['id' => $user_id])->get('users')->result_array();
    if (!empty($user_balance)) {
        if ($operation == 'debit' && $amount > $user_balance[0]['balance']) {
            $response['error'] = true;
            $response['message'] = "Debited amount can't exceeds the user balance !";
            $response['data'] = array();
            return $response;
        }

        if ($amount == 0) {
            $response['error'] = true;
            $response['message'] = "Amount can't be Zero !";
            $response['data'] = array();
            return $response;
        }

        // Was `if ($user_balance[0]['balance'] >= 0)`, which refused EVERY operation - credits
        // and refunds included - whenever the stored balance happened to be negative. A seller
        // whose balance had gone negative (the old unlocked withdrawal path could overdraw, and
        // raw update_balance() calls bypass every check) was therefore permanently stranded:
        // no commission could ever be credited to them again, their delivered orders retried
        // and failed on every settlement run, and the only visible symptom was an unexplained
        // "wallet balance less than -X can be used only" error. A credit or refund must always
        // be allowed - paying money IN is exactly how a negative balance gets corrected. Debits
        // are unaffected: the `$amount > balance` check above already rejects those.
        if ($operation != 'debit' || $user_balance[0]['balance'] >= 0) {
            $t = &get_instance();
            $data = [
                'transaction_type' => $transaction_type,
                'user_id' => $user_id,
                'type' => $operation,
                'amount' => $amount,
                'message' => $message,
                'order_item_id' => $order_item_id,
                'is_refund' => $is_refund,
            ];
            // There used to be a "skip the wallet credit when the order was paid by Razorpay,
            // because Razorpay pays out directly" branch here. It never once fired: it looked
            // for a transaction of type 'razorpay' by order_item_id, and gateway payments are
            // always written with order_item_id NULL, so the condition was constantly false and
            // every card refund quietly became a wallet credit. Routing a refund back to the
            // gateway is now an explicit decision made in refund_to_payment_source(), which
            // calls this function only for the part that genuinely belongs in the wallet - so
            // there is nothing left for a guard here to second-guess.
            $t->db->trans_start();
            if ($operation == 'debit') {
                $data['message'] = (isset($message)) ? $message : 'Balance Debited';
                $data['type'] = 'debit';
                $t->db->set('balance', '`balance` - ' . $amount, false)->where('id', $user_id)->update('users');
                // Referral credit is spend-only, so the wallet carries a second figure:
                // how much of the balance came from the referral programme and therefore
                // cannot be withdrawn. Spending consumes that restricted part FIRST -
                // the reading most favourable to the user, and the only rule that needs
                // no per-transaction tagging, which matters because wallet debits happen
                // in a dozen places in this codebase. GREATEST() floors it at zero so a
                // debit larger than the restricted part cannot drive it negative.
                // This sits inside the same transaction as the balance write for the
                // same reason that write is here: the two must not be able to disagree.
                $t->db->set('referral_credit', 'GREATEST(0, `referral_credit` - ' . $amount . ')', false)
                    ->where('id', $user_id)->update('users');
            } else if ($operation == 'credit') {
                $data['message'] = (isset($message)) ? $message : 'Balance Credited';
                $data['type'] = 'credit';
                $t->db->set('balance', '`balance` + ' . $amount, false)->where('id', $user_id)->update('users');
            } else {
                $data['message'] = (isset($message)) ? $message : 'Balance refuned';
                $data['type'] = 'refund';
                $t->db->set('balance', '`balance` + ' . $amount, false)->where('id', $user_id)->update('users');
            }
            $data = escape_array($data);
            $t->db->insert('transactions', $data);
            // The balance UPDATE and the transactions INSERT above used to run as two separate,
            // unwrapped writes - if the second failed after the first succeeded (or vice versa),
            // users.balance would no longer reconcile with the transaction log, with nothing to
            // detect or roll it back.
            $t->db->trans_complete();

            $response['error'] = ($t->db->trans_status() === false);
            $response['message'] = $response['error'] ? 'Something went wrong. Please try again.' : "Balance Update Successfully";
            $response['data'] = array();
        } else {
            $response['error'] = true;
            $response['message'] = ($user_balance[0]['balance'] != 0) ? "User's Wallet balance less than " . $user_balance[0]['balance'] . " can be used only" : "Doesn't have sufficient wallet balance to proceed further.";
            $response['data'] = array();
        }
    } else {
        $response['error'] = true;
        $response['message'] = "User does not exist";
        $response['data'] = array();
    }
    return $response;
}

/**
 * True when push notifications cannot possibly be delivered, so callers can skip the network
 * round trip instead of blocking a user-facing request on a call that is guaranteed to fail.
 *
 * A fresh install ships `fcm_server_key` as the literal placeholder string
 * "your_fcm_server_key", which is non-empty - so every `if (empty($fcm_key))` guard in this
 * codebase passed and every notification attempt went out with a bogus credential, waited for
 * the round trip, and threw the result away.
 */
function fcm_is_configured()
{
    $key = trim((string) get_settings('fcm_server_key'));
    if ($key === '' || $key === 'NULL') {
        return false;
    }
    // Placeholders shipped by the base product / left in by an incomplete setup.
    $placeholders = ['your_fcm_server_key', 'fcm_server_key', 'your-fcm-server-key', 'xxxx'];
    return !in_array(strtolower($key), $placeholders, true);
}

function send_notification($fcmMsg, $registrationIDs_chunks)
{
    /* Same guard as add_user_notification(): a push body is the one copy of a notification
     * nobody can edit after the fact, so any placeholder the caller could not fill is cleared
     * here rather than shipped to a phone. */
    foreach (['title', 'body'] as $field) {
        if (isset($fcmMsg[$field])) {
            $fcmMsg[$field] = fill_notification_placeholders($fcmMsg[$field]);
        }
    }

    $fcmFields = [
        'priority'     => 'high',
        'notification' => $fcmMsg,
        'data'         => $fcmMsg,
    ];

    if (!fcm_is_configured()) {
        // Logged rather than silently ignored: "notifications don't arrive" was previously
        // indistinguishable from "notifications were never attempted".
        log_message('error', 'send_notification: FCM server key is not configured (Admin > Notification Settings) - push skipped.');
        return $fcmFields;
    }

    // A single scalar token, or a flat array of tokens, used to be passed straight through as if
    // it were already a list of chunks. `foreach` over a scalar is a PHP 8 error and a flat token
    // list ended up sending one request per token with `to` set to that token, so normalise the
    // shape here instead of trusting ~60 call sites to agree on it.
    if (!is_array($registrationIDs_chunks)) {
        $registrationIDs_chunks = [[$registrationIDs_chunks]];
    } elseif (!empty($registrationIDs_chunks) && !is_array(reset($registrationIDs_chunks))) {
        $registrationIDs_chunks = array_chunk($registrationIDs_chunks, 1000);
    }

    $sent = 0;
    foreach ($registrationIDs_chunks as $registrationIDs) {
        // Callers routinely build these lists with `$fcm_ids[0][] = $row['fcm_id']` without
        // checking the column is populated, so the arrays arriving here are full of null / ''
        // / the literal string 'NULL'. FCM rejects the whole request when the token list
        // contains one, which meant one user with no device silently suppressed the
        // notification for everyone else in the same batch.
        $registrationIDs = array_values(array_unique(array_filter(
            is_array($registrationIDs) ? $registrationIDs : [$registrationIDs],
            function ($token) {
                $token = is_string($token) ? trim($token) : $token;
                return !empty($token) && $token !== 'NULL' && $token !== 'null';
            }
        )));

        if (empty($registrationIDs)) {
            continue;
        }

        $fcmFields = array(
            'priority' => 'high',
            'notification' => $fcmMsg,
            'data' => $fcmMsg,
        );
        if (count($registrationIDs) > 1) {
            $fcmFields['registration_ids'] = $registrationIDs;
        } else {
            $fcmFields['to'] = reset($registrationIDs);
        }

        $headers = array(
            'Authorization: key=' . get_settings('fcm_server_key'),
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // No timeouts at all previously: an unreachable or slow FCM endpoint stalled the
        // customer's checkout / ticket reply / status change for as long as curl's default
        // (which is effectively forever for the connect phase).
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmFields));
        $result = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        // The result was assigned to a variable and then discarded, so a rejected credential,
        // an expired token or the legacy endpoint being retired all looked identical to success.
        if ($result === false || $status < 200 || $status > 299) {
            log_message('error', 'send_notification: FCM request failed (http ' . $status . ') ' . substr((string) ($curl_error !== '' ? $curl_error : $result), 0, 300));
        } else {
            $sent++;
        }
    }

    $fcmFields['sent_batches'] = $sent;
    return $fcmFields;
}

/**
 * Records an in-app notification for one specific user, so it shows on
 * My Account > Notifications even when push delivery is unavailable.
 *
 * Push was the ONLY delivery channel for every event-driven notification in this codebase
 * (ticket replies, status changes, order events). With the FCM key unset - the default - those
 * events produced no trace anywhere the customer could see. This writes the same `notifications`
 * row shape the admin "Send Notification" screen produces, scoped to a single user.
 */
function add_user_notification($user_id, $title, $message, $type = 'default', $link = '', $type_id = '')
{
    $t = &get_instance();
    $user_id = (int) $user_id;
    if ($user_id < 1) {
        return false;
    }

    /* Last line of defence against a raw "< order_id >" reaching a reader: a template can
     * always carry a token the calling path has no value for, and once the row is written
     * there is nothing downstream that can repair it. Callers that DO know the values still
     * fill them first - this only clears whatever is left. */
    $title = fill_notification_placeholders($title);
    $message = fill_notification_placeholders($message);

    $row = [
        'title'    => mb_substr((string) $title, 0, 128),
        'message'  => mb_substr((string) $message, 0, 512),
        // notifications.type was varchar(12) - too narrow for the type strings this product
        // itself writes ('notification_url', 'ticket_message', 'ticket_status'), all of which
        // were truncated mid-word on insert so nothing downstream could match on them.
        // Migration 047 widens the column to 64; keep the guard in step with it.
        'type'     => mb_substr((string) $type, 0, 64),
        // Was hardcoded to '', so the row recorded WHAT happened but not to which ticket or
        // order - which made every notification a dead end: the recipient's list could not
        // build a link back to the thing the notification was about.
        'type_id'  => mb_substr((string) $type_id, 0, 128),
        'send_to'  => 'specific_user',
        // Stored in the same shape the admin screen uses (json array of id strings) so
        // Notification_model's per-user filter matches both.
        'users_id' => json_encode([(string) $user_id]),
        'link'     => (string) $link,
    ];

    return (bool) $t->db->insert('notifications', $row);
}

/**
 * Resolves an admin-authored custom notification template by type, falling back to the supplied
 * defaults when no row exists.
 *
 * Every call site duplicated this block, and each copy indexed $custom_notification[0] without
 * checking the lookup returned anything - with an empty `custom_notifications` table (the
 * default) that is an "Undefined array key 0" warning printed straight into the JSON response
 * body, corrupting it for the caller.
 */
/**
 * Unread notification count for a user, for the storefront and seller-panel bells.
 *
 * Lives in a helper rather than being called straight from the view: inside a CI view `$this`
 * is the CI_Loader, and `$this->load->model(...)` followed by `$this->notification_model->...`
 * in the same view resolves through CI_Loader::__get() and comes back null ("Call to a member
 * function count_user_unread() on null", which 500s the whole page). get_instance() from a
 * helper has no such ambiguity.
 */
function user_unread_notification_count($user_id, $panel = 'customer')
{
    $user_id = (int) $user_id;
    if ($user_id < 1) {
        return 0;
    }
    $t = &get_instance();
    $t->load->model('notification_model');
    // $panel keeps the badge in step with the list it belongs to - a seller is also a `members`
    // user, so an unscoped count adds their buyer broadcasts to the seller navbar bell.
    return (int) $t->notification_model->count_user_unread($user_id, $panel);
}

/**
 * Fills the `< token >` placeholders the custom_notifications templates are written with.
 *
 * These templates ship with placeholders - "Your order #< order_id > has been placed" - and
 * every send path substituted them by hand, with its own list of str_replace() pairs. Some
 * lists were incomplete: the place_order paths replaced `< order_id >` in the TITLE and only
 * `< application_name >` in the MESSAGE, so customers and admins were shown the literal text
 * "Your order #< order_id > has been placed". This is the one place that knows how to do it.
 *
 * `application_name` fills itself from system settings when the caller does not pass it.
 *
 * Anything still unresolved after substitution is REMOVED rather than left in the text: a
 * template can legitimately carry a token a given path has no value for, and a half-empty
 * sentence reads far better than a raw placeholder. A '#' immediately in front of a dropped
 * token goes with it, so "order #< order_id >" degrades to "order", not "order #".
 */
function fill_notification_placeholders($text, $tokens = [])
{
    $text = (string) $text;
    if ($text === '' || strpos($text, '<') === false) {
        return $text;
    }

    if (!array_key_exists('application_name', $tokens)) {
        $settings = get_settings('system_settings', true);
        $tokens['application_name'] = !empty($settings['app_name']) ? $settings['app_name'] : '';
    }

    // The seeded templates spell it "cutomer_name". Accept both spellings for either value so
    // a corrected template does not silently stop resolving.
    if (isset($tokens['customer_name']) && !isset($tokens['cutomer_name'])) {
        $tokens['cutomer_name'] = $tokens['customer_name'];
    } elseif (isset($tokens['cutomer_name']) && !isset($tokens['customer_name'])) {
        $tokens['customer_name'] = $tokens['cutomer_name'];
    }
    // Same story for the two id tokens: templates use whichever one their author had in mind,
    // and a path that knows one of them almost always means the same number by the other.
    if (isset($tokens['order_id']) && !isset($tokens['order_item_id'])) {
        $tokens['order_item_id'] = $tokens['order_id'];
    } elseif (isset($tokens['order_item_id']) && !isset($tokens['order_id'])) {
        $tokens['order_id'] = $tokens['order_item_id'];
    }

    // One pass over every "< name >" in the text: spacing inside the angle brackets is
    // inconsistent across the seeds ("< status  >" appears in the admin API), so it is matched
    // loosely rather than compared literally.
    $text = preg_replace_callback('/(#\s*)?<\s*([a-z0-9_]+)\s*>/i', function ($m) use ($tokens) {
        $key = strtolower($m[2]);
        if (array_key_exists($key, $tokens) && $tokens[$key] !== null && $tokens[$key] !== '') {
            return (isset($m[1]) ? $m[1] : '') . $tokens[$key];
        }
        return '';
    }, $text);

    // Substitution can leave a double space or a space before punctuation behind it.
    $text = preg_replace('/[ 	]{2,}/', ' ', $text);
    $text = preg_replace('/\s+([,.!?])/', '$1', $text);

    return trim($text);
}

/**
 * Renders one stored template string (title or message) for delivery.
 *
 * The json_encode/html_entity_decode/output_escaping dance is what every call site already
 * did by hand - it undoes the escaping the admin template editor stores - so it is kept
 * exactly, with the placeholder fill folded in.
 */
function render_notification_text($raw, $tokens = [])
{
    $decoded = html_entity_decode(json_encode((string) $raw, JSON_UNESCAPED_UNICODE));

    return output_escaping(trim(fill_notification_placeholders(trim($decoded, '"'), $tokens)));
}

/**
 * Read-side repair for stored notification rows.
 *
 * Every send path fills its placeholders now, but rows written before that was true are still
 * sitting in both notification tables reading "Your order #< order_id > has been placed", and
 * a template can always carry a token the sending path had no value for. Nothing downstream of
 * the insert can fix the stored text, so every list that displays notifications passes its rows
 * through here first: the row's own `type_id` supplies the order/ticket id where it has one, and
 * anything still unresolved is dropped along with the "#" in front of it rather than shown raw.
 *
 * Cheap by design - fill_notification_placeholders() returns immediately when the text contains
 * no "<" at all, which is the case for every correctly-written row.
 *
 * @param array $rows  Rows with `title` / `message` keys (and optionally `type_id`).
 * @return array       The same rows, with those two fields rendered.
 */
function clean_notification_rows($rows)
{
    if (empty($rows) || !is_array($rows)) {
        return $rows;
    }

    foreach ($rows as $i => $row) {
        if (!is_array($row)) {
            continue;
        }

        $tokens = [];
        // type_id is the id of the thing the notification was about - the order for order
        // events, the ticket for ticket events. Only usable when it is actually a number.
        if (isset($row['type_id']) && ctype_digit((string) $row['type_id']) && (int) $row['type_id'] > 0) {
            $tokens['order_id'] = (int) $row['type_id'];
        }

        foreach (['title', 'message'] as $field) {
            if (isset($row[$field])) {
                $rows[$i][$field] = fill_notification_placeholders($row[$field], $tokens);
            }
        }
    }

    return $rows;
}

/**
 * @param array $tokens Placeholder values for the template, e.g. ['order_id' => 12].
 */
function get_custom_notification_template($type, $default_title, $default_message, $tokens = [])
{
    $custom = fetch_details('custom_notifications', ['type' => $type], '*', 1);

    $title   = $default_title;
    $message = $default_message;

    if (!empty($custom) && !empty($custom[0]['message'])) {
        $title = !empty($custom[0]['title']) ? render_notification_text($custom[0]['title'], $tokens) : $default_title;
        $message = render_notification_text($custom[0]['message'], $tokens);
    }

    return ['title' => $title, 'message' => $message];
}

/**
 * Single notification trigger for the support-ticket system, used by both the admin panel and
 * the app API so the two cannot drift apart.
 *
 * Before this existed, an admin replying to a ticket from the admin panel notified nobody at all
 * (only a status CHANGE did), so a customer had no way of knowing a reply had arrived; and a
 * customer replying via the app notified admins by push only, which is dead whenever FCM is
 * unconfigured.
 *
 * @param string $event 'message' or 'status'
 * @param string $actor 'admin' or 'user' - who performed the action
 */
function notify_ticket_event($ticket_id, $event, $actor)
{
    $t = &get_instance();
    $ticket_id = (int) $ticket_id;
    if ($ticket_id < 1) {
        return false;
    }

    $ticket = fetch_details('tickets', ['id' => $ticket_id], '*', 1);
    if (empty($ticket)) {
        return false;
    }
    $ticket = $ticket[0];

    $settings   = get_settings('system_settings', true);
    $app_name   = !empty($settings['app_name']) ? $settings['app_name'] : 'Support';
    $status_map = [
        PENDING  => 'Pending',
        OPENED   => 'Opened',
        RESOLVED => 'Resolved',
        CLOSED   => 'Closed',
        REOPEN   => 'Reopened',
    ];
    $status_label = isset($status_map[(string) $ticket['status']]) ? $status_map[(string) $ticket['status']] : 'Updated';
    $subject      = (string) $ticket['subject'];

    // Sellers raise tickets from their own panel (seller/Support) and customers from
    // my-account, but both are rows in the same table with the same owner column - only
    // tickets.raised_by separates them. It decides two things below: which panel URL the
    // notification links to (a seller cannot use the customer my-account page, and vice
    // versa) and whether the admin-facing wording says "customer" or "seller".
    $is_seller_ticket = (isset($ticket['raised_by']) && $ticket['raised_by'] === 'seller');
    $raiser_word      = $is_seller_ticket ? 'seller' : 'customer';
    $ticket_url       = $is_seller_ticket
        ? base_url('seller/support?ticket_id=' . $ticket_id)
        : base_url('my-account/support?ticket_id=' . $ticket_id);

    if ($actor === 'admin') {
        /* ---------------- notify the customer ---------------- */
        $owner_id = (int) $ticket['user_id'];
        if ($owner_id < 1) {
            return false;
        }

        if ($event === 'status') {
            $tpl = get_custom_notification_template(
                'ticket_status',
                'Your ticket status has been updated',
                'Ticket #' . $ticket_id . ' (' . $subject . ') is now ' . $status_label . '.'
            );
        } else {
            $tpl = get_custom_notification_template(
                'ticket_message',
                'New reply on your support ticket',
                'Our team replied to ticket #' . $ticket_id . ' (' . $subject . ').'
            );
        }

        add_user_notification($owner_id, $tpl['title'], $tpl['message'], $event === 'status' ? 'ticket_status' : 'ticket_message', $ticket_url, $ticket_id);

        $owner = fetch_details('users', ['id' => $owner_id], 'fcm_id,email,username', 1);
        if (!empty($owner)) {
            if (!empty($owner[0]['fcm_id'])) {
                send_notification([
                    'title'        => $tpl['title'],
                    'body'         => $tpl['message'],
                    'type'         => $event === 'status' ? 'ticket_status' : 'ticket_message',
                    'type_id'      => (string) $ticket_id,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ], [[$owner[0]['fcm_id']]]);
            }

            // Email is the only channel that reaches a customer who is not currently in the app
            // and has push disabled; the ticket system had no email step whatsoever.
            $to = !empty($ticket['email']) ? $ticket['email'] : (!empty($owner[0]['email']) ? $owner[0]['email'] : '');
            if (!empty($to) && filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $body = '<p>Hi ' . html_escape((string) $owner[0]['username']) . ',</p>'
                    . '<p>' . html_escape($tpl['message']) . '</p>'
                    . '<p><strong>Ticket #' . $ticket_id . '</strong> &mdash; ' . html_escape($subject) . '<br>'
                    . 'Status: ' . html_escape($status_label) . '</p>'
                    . '<p>You can view the full conversation here: '
                    . '<a href="' . $ticket_url . '">View ticket</a></p>'
                    . '<p>&mdash; ' . html_escape($app_name) . '</p>';
                send_mail($to, $tpl['title'] . ' - ' . $app_name, $body);
            }
        }

        return true;
    }

    /* ---------------- notify the admins ---------------- */
    if ($event === 'created') {
        $title = 'New ' . $raiser_word . ' ticket #' . $ticket_id;
        $body  = 'A ' . $raiser_word . ' raised ticket #' . $ticket_id . ' (' . $subject . ').';
    } elseif ($event === 'status') {
        $title = 'Ticket #' . $ticket_id . ' is now ' . $status_label;
        $body  = 'The ' . $raiser_word . ' set ticket #' . $ticket_id . ' (' . $subject . ') to ' . $status_label . '.';
    } else {
        $title = 'New ticket message #' . $ticket_id;
        $body  = 'A ' . $raiser_word . ' replied to ticket #' . $ticket_id . ' (' . $subject . ').';
    }

    // The admin bell reads `system_notification`; ticket activity never wrote there, so a reply
    // arriving overnight was invisible until somebody happened to open the tickets page.
    $t->db->insert('system_notification', [
        'title'   => mb_substr($title, 0, 256),
        'message' => mb_substr($body, 0, 512),
        'type'    => 'ticket_message',
        'type_id' => $ticket_id,
        'read_by' => 0,
    ]);

    $staff = $t->db->select('u.fcm_id')
        ->join('users u', 'u.id = up.user_id', 'inner')
        ->where('u.fcm_id IS NOT NULL')
        ->where('u.fcm_id !=', '')
        ->get('user_permissions up')
        ->result_array();

    if (!empty($staff)) {
        send_notification([
            'title'        => $title,
            'body'         => $body,
            'type'         => 'ticket_message',
            'type_id'      => (string) $ticket_id,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ], [array_column($staff, 'fcm_id')]);
    }

    return true;
}

function get_attribute_values_by_pid($id)
{
    $t = &get_instance();
    $swatche_type = $swatche_values1 =  array();
    /*
     * PERFORMANCE: served from the batch prefetch when one is open (see
     * application/helpers/batch_helper.php), otherwise queried exactly as before.
     * The rows are identical either way - only the WHERE differs - and the
     * transformation below is untouched and runs on them all the same.
     */
    $attribute_values = product_batch_get('attributes', $id);
    if ($attribute_values === null) {
        $attribute_values = $t->db->select(" group_concat(`av`.`id` ORDER BY `av`.`id` ASC) as ids,group_concat(' ', `av`.`value`  ORDER BY `av`.`id` ASC ) as value ,`a`.`name` as attr_name, a.name, GROUP_CONCAT(av.swatche_type ORDER BY av.id ASC ) as swatche_type , GROUP_CONCAT(av.swatche_value  ORDER BY av.id ASC) as swatche_value")
            ->join('attribute_values av ', 'FIND_IN_SET(av.id, pa.attribute_value_ids ) > 0', 'inner')
            ->join('attributes a', 'a.id = av.attribute_id', 'inner')
            ->where('pa.product_id', $id)->group_by('`a`.`name`')->get('product_attributes pa')->result_array();
    }
    if (!empty($attribute_values)) {
            // print_r($attribute_values);
            // die;
        for ($i = 0; $i < count($attribute_values); $i++) {
            $swatche_type = array();
            $swatche_values1 = array();
            $swatche_type =  explode(",", $attribute_values[$i]['swatche_type']);
            $swatche_values =  explode(",", $attribute_values[$i]['swatche_value']);
            for ($j = 0; $j < count($swatche_type); $j++) {
                if ($swatche_type[$j] == "2") {
                    $swatche_values1[$j]  = get_image_url($swatche_values[$j], 'thumb', 'sm');
                } else if ($swatche_type[$j] == "0") {
                    $swatche_values1[$j] = '0';
                } else if ($swatche_type[$j] == "1") {
                    $swatche_values1[$j] = $swatche_values[$j];
                }
                $row = implode(',', $swatche_values1);
                $attribute_values[$i]['swatche_value'] = $row;
            }
            $attribute_values[$i] = output_escaping($attribute_values[$i]);
        }
    }
    return $attribute_values;
}

function get_attribute_values_by_id($id)
{
    $t = &get_instance();
    $attribute_values = $t->db->select(" GROUP_CONCAT(av.value  ORDER BY av.id ASC) as attribute_values ,GROUP_CONCAT(av.id ORDER BY av.id ASC ) as attribute_values_id ,a.name , GROUP_CONCAT(av.swatche_type ORDER BY av.id ASC ) as swatche_type , GROUP_CONCAT(av.swatche_value ORDER BY av.id ASC ) as swatche_value")
        ->join(' attributes a ', 'av.attribute_id = a.id ', 'inner')
        ->where_in('av.id', $id)->group_by('`a`.`name`')->get('attribute_values av')->result_array();
    if (!empty($attribute_values)) {
        for ($i = 0; $i < count($attribute_values); $i++) {
            if ($attribute_values[$i]['swatche_type'] != "") {
                $swatche_type = array();
                $swatche_values1 = array();
                $swatche_type =  explode(",", $attribute_values[$i]['swatche_type']);
                $swatche_values =  explode(",", $attribute_values[$i]['swatche_value']);

                for ($j = 0; $j < count($swatche_type); $j++) {
                    if ($swatche_type[$j] == "2") {
                        $swatche_values1[$j]  = get_image_url($swatche_values[$j], 'thumb', 'sm');
                    } else if ($swatche_type[$j] == "0") {
                        $swatche_values1[$j] = '0';
                    } else if ($swatche_type[$j] == "1") {
                        $swatche_values1[$j] = $swatche_values[$j];
                    }
                    $row = implode(',', $swatche_values1);
                    $attribute_values[$i]['swatche_value'] = $row;
                }
            }
            $attribute_values[$i] = output_escaping($attribute_values[$i]);
        }
    }
    // print_r($attribute_values);
    // die;
    return $attribute_values;
}

function get_variants_values_by_pid($id, $status = [1])
{

    $t = &get_instance();
    /*
     * PERFORMANCE: served from the batch prefetch when one is open. The bucket is
     * keyed by product id AND status, so a caller asking for a non-default status
     * set is never served the default-status rows - it simply misses and queries,
     * exactly as before.
     */
    $varaint_values = product_batch_get('variants', $id . '|' . implode(',', (array) $status));
    if ($varaint_values === null) {
        $varaint_values = $t->db->select("pv.*,pv.`product_id`,group_concat(`av`.`id`  ORDER BY av.id ASC) as variant_ids,group_concat( ' ' ,`a`.`name` ORDER BY av.id ASC) as attr_name, group_concat(`av`.`value` ORDER BY av.id ASC) as variant_values , pv.price as price , GROUP_CONCAT(av.swatche_type ORDER BY av.id ASC ) as swatche_type , GROUP_CONCAT(av.swatche_value ORDER BY av.id ASC ) as swatche_value")
            ->join('attribute_values av ', 'FIND_IN_SET(av.id, pv.attribute_value_ids ) > 0', 'left')
            ->join('attributes a', 'a.id = av.attribute_id', 'left')
            ->where(['pv.product_id' => $id])->where_in('pv.status', $status)->group_by('`pv`.`id`')->order_by('pv.id')->get('product_variants pv')->result_array();
    }
    if (!empty($varaint_values)) {
        for ($i = 0; $i < count($varaint_values); $i++) {
            if ($varaint_values[$i]['swatche_type'] != "") {
                $swatche_type = array();
                $swatche_values1 = array();
                $swatche_type =  explode(",", $varaint_values[$i]['swatche_type']);
                $swatche_values =  explode(",", $varaint_values[$i]['swatche_value']);

                for ($j = 0; $j < count($swatche_type); $j++) {
                    if ($swatche_type[$j] == "2") {
                        $swatche_values1[$j]  = get_image_url($swatche_values[$j], 'thumb', 'sm');
                    } else if ($swatche_type[$j] == "0") {
                        $swatche_values1[$j] = '0';
                    } else if ($swatche_type[$j] == "1") {
                        $swatche_values1[$j] = $swatche_values[$j];
                    }
                    $row = implode(',', $swatche_values1);
                    $varaint_values[$i]['swatche_value'] = $row;
                }
            }
            $varaint_values[$i] = output_escaping($varaint_values[$i]);
            $varaint_values[$i]['availability'] = isset($varaint_values[$i]['availability']) && ($varaint_values[$i]['availability'] != "") ? $varaint_values[$i]['availability'] : '';
        }
    }
    return $varaint_values;
}

function get_variants_values_by_id($id)
{
    $t = &get_instance();
    // PERFORMANCE: served from a variant batch prefetch when one is open (see
    // variant_batch_open()); otherwise queried exactly as before.
    $varaint_values = product_batch_get('variants_by_id', $id);
    if ($varaint_values === null) {
        $varaint_values = $t->db->select("pv.*,pv.`product_id`,group_concat(`av`.`id` separator ', ') as varaint_ids,group_concat(`a`.`name` separator ', ') as attr_name, group_concat(`av`.`value` separator ', ') as variant_values")
            ->join('attribute_values av ', 'FIND_IN_SET(av.id, pv.attribute_value_ids ) > 0', 'inner')
            ->join('attributes a', 'a.id = av.attribute_id', 'inner')
            ->where('pv.id', $id)->group_by('`pv`.`id`')->order_by('pv.id')->get('product_variants pv')->result_array();
    }
    if (!empty($varaint_values)) {
        for ($i = 0; $i < count($varaint_values); $i++) {
            $varaint_values[$i] = output_escaping($varaint_values[$i]);
            $varaint_values[$i]['availability'] = isset($varaint_values[$i]['availability']) && ($varaint_values[$i]['availability'] != "") ? $varaint_values[$i]['availability'] : '';
            $varaint_values[$i]['images'] = isset($varaint_values[$i]['images']) && (!empty($varaint_values[$i]['images'])) ? $varaint_values[$i]['images'] : '';

            // Adding for cretzo adaptability
            if(!empty($varaint_values[$i]['images'])){
                $variant_images_array = json_decode($varaint_values[$i]['images'], true);
                // Store the first element from the parsed variant image array
                if(isset($variant_images_array) && !empty($variant_images_array)){
                    $varaint_values[$i]['variant_image'] = get_image_url($variant_images_array[0]); // Use null coalescing to handle cases where the array is empty
                }
            }
        }
    }
    return $varaint_values;
}

//Used in form validation(API)
function userrating_check()
{
    $t = &get_instance();
    $user_id = $t->input->post('user_id', true);
    $product_id = $t->input->post('product_id', true);
    $res = $t->db->select('*')->where(['user_id' => $user_id, 'product_id' => $product_id])->get('product_rating');
    if ($res->num_rows() > 0) {
        return false;
    } else {
        return true;
    }
}

//update_stock()
/**
 * Resolve a stock_type value to the documented 0 / 1 / 2, or null for "not tracked".
 *
 * stock_type is free text in practice. Alongside NULL, '0', '1' and '2', ten live products
 * hold the literal string 'simple_product'. Under PHP 8 that string does not loosely equal 0
 * (it did on PHP 7), so code comparing `stock_type == 0` silently skipped those products
 * entirely - they sold without their stock ever moving. Everything that branches on
 * stock_type goes through this so they all agree.
 *
 * @return int|null 0 = simple, 1 = product level, 2 = variant level, null = not tracked
 */
function normalise_stock_type($stock_type)
{
    if ($stock_type === null || $stock_type === '') {
        return null;
    }
    if (is_numeric($stock_type)) {
        $value = (int) $stock_type;
        return in_array($value, [0, 1, 2], true) ? $value : null;
    }
    if ($stock_type === 'simple_product') {
        return 0;
    }

    // Unrecognised marker: treat as untracked rather than guessing a branch.
    return null;
}

/**
 * Clean a stock value arriving from a CSV import.
 *
 * The importer wrote the raw cell straight into the column, so a blank, a negative or a
 * non-numeric value went in untouched - bypassing every guard update_stock() applies and
 * leaving stock in states the rest of the system does not expect (a negative stock reads as
 * "available" to the availability flip).
 *
 * @return int|null null when the cell is blank, i.e. leave stock untracked.
 */
function sanitise_import_stock($value)
{
    if ($value === null || trim((string) $value) === '') {
        return null;
    }
    if (!is_numeric($value)) {
        return null;
    }

    return max(0, (int) $value);
}

/**
 * Clean a stock level arriving from the product add/edit form.
 *
 * The form fields are validated as 'numeric' at best (and 'total_stock_variant_type' not even
 * that), so a negative or non-numeric level went straight into the column - bypassing every
 * guard update_stock() applies. Negative stock is particularly bad because the availability
 * flip and the listing filters both read "not zero" as "in stock".
 *
 * @return int  never negative
 */
function sanitise_stock_input($value)
{
    if ($value === null || trim((string) $value) === '' || !is_numeric($value)) {
        return 0;
    }
    return max(0, (int) $value);
}

/**
 * Reconcile the "stock status" dropdown against the stock level saved beside it.
 *
 * These two are independent form fields, so a product could be saved as In Stock (availability
 * 1) with a stock level of 0. Nothing reconciled them, and the two halves of the system then
 * disagreed permanently: the storefront and admin listings filter on `availability`, so the
 * product was advertised as purchasable, while validate_stock() correctly refused it at
 * checkout. update_stock() has always derived availability from the resulting stock
 * (`IF(stock > 0, '1', '0')`), so a manual save was the one way to get the two out of step.
 *
 * Applies the weaker half of that same rule: zero stock can never be "available". A seller can
 * still mark a well-stocked item unavailable deliberately - that is a legitimate choice, and
 * only the impossible combination is corrected.
 *
 * @param  mixed $stock                 the stock level being saved
 * @param  mixed $requested_availability the flag the form asked for
 * @return string '0' or '1'
 */
function reconcile_stock_availability($stock, $requested_availability)
{
    $wants_available = ((string) $requested_availability === '1');

    if (sanitise_stock_input($stock) <= 0) {
        return '0';
    }

    return $wants_available ? '1' : '0';
}

/**
 * The stock a variant actually has right now, read from whichever column update_stock() will
 * change for that product's stock_type.
 *
 * All three manual-adjust endpoints (admin, seller panel, seller API) used to cap a subtract
 * against a `current_stock` value POSTed by the client - a number supplied by the same caller
 * making the change, so it proved nothing and could simply be inflated to subtract past the
 * real stock.
 *
 * @return int|null null when the product does not track stock, i.e. no ceiling applies.
 */
function get_variant_current_stock($variant_id)
{
    if (empty($variant_id)) {
        return null;
    }

    $t = &get_instance();
    $row = $t->db->select('p.stock_type, p.stock AS p_stock, pv.stock AS pv_stock')
        ->join('products p', 'p.id = pv.product_id')
        ->where('pv.id', $variant_id)
        ->get('product_variants pv')
        ->row_array();

    if (empty($row) || $row['stock_type'] === null || $row['stock_type'] === '') {
        return null;
    }

    $stock_type = normalise_stock_type($row['stock_type']);
    if ($stock_type === null) {
        return null;
    }

    $stock = ($stock_type === 0) ? $row['p_stock'] : $row['pv_stock'];

    return ($stock === null) ? null : (int) $stock;
}

/**
 * Return every line of an order to stock.
 *
 * The webhook and payment-cancel paths used to do this by hand, and each got it wrong in a
 * different way: some passed $order['order_data'][0]['product_variant_ids'], a key
 * fetch_orders() does not produce (so both arguments were NULL); others restored only
 * order_items[0], silently losing every line after the first on a multi-item order. Reading
 * the items straight from the table gets it right in one place.
 *
 * Goes line by line through restore_order_item_stock() so each line is restored at most once,
 * even if a per-item path (a customer cancelling one line, or a Shiprocket webhook for one
 * shipment of a multi-parcel order) already put that line back.
 *
 * @param int    $order_id
 * @param string $note  recorded against each stock movement
 * @return int number of lines actually restored by this call
 */
function restore_order_stock($order_id, $note = 'Order cancelled')
{
    if (empty($order_id)) {
        return 0;
    }

    $restored = 0;
    foreach (fetch_details('order_items', ['order_id' => $order_id], 'id') as $item) {
        if (restore_order_item_stock($item['id'], $note)) {
            $restored++;
        }
    }

    return $restored;
}

/**
 * Describe the reason for the next stock movement, so update_stock() can record it.
 *
 * update_stock() is reached from ~20 call sites through several layers, and threading a
 * reason argument through all of them would mean touching every one. This sets it for the
 * next call instead; update_stock() consumes and clears it, so an unlabelled call can never
 * inherit a previous one's reason.
 *
 * @param string   $reason   order_deduct | order_restore | manual_add | manual_subtract |
 *                           import | expiry_restore
 * @param int|null $order_id
 * @param int|null $user_id
 */
function set_stock_movement_context($reason, $order_id = null, $user_id = null, $note = null)
{
    $t = &get_instance();
    $t->_stock_movement_context = [
        'reason'   => $reason,
        'order_id' => $order_id,
        'user_id'  => $user_id,
        'note'     => $note,
    ];
}

function update_stock($product_variant_ids, $qtns, $type = '')
{

    /*
		--First Check => Is stock management active (Stock type != NULL) 
		Case 1 : Simple Product 		
		Case 2 : Variable Product (Product Level,Variant Level) 			

		Stock Type :
			0 => Simple Product(simple product)
			  	-Stock will be stored in (product)master table	
			1 => Product level(variable product)
				-Stock will be stored in product_variant table	
			2 => Variant level(variable product)		
				-Stock will be stored in product_variant table	
		*/
    $t = &get_instance();

    // Callers are wildly inconsistent about argument shape: roughly ten cancellation and
    // return paths pass SCALARS (update_stock($row['product_variant_id'], $row['quantity'],
    // 'plus')) while the order paths pass arrays. With a scalar quantity the old code did
    // $qtns[$i], which STRING-INDEXES it - so restoring a quantity of "12" put back "1", and
    // every cancelled or returned line of 10 or more silently lost most of its stock.
    // Normalising here fixes all of those call sites at once rather than editing each.
    $ids = is_array($product_variant_ids) ? array_values($product_variant_ids) : [$product_variant_ids];
    $quantities = is_array($qtns) ? array_values($qtns) : [$qtns];

    // Pair each id with its quantity before filtering, so a blank entry cannot shift the
    // positional alignment between the two lists.
    $wanted = [];
    foreach ($ids as $index => $variant_id) {
        if ($variant_id === null || $variant_id === '') {
            continue;
        }
        $quantity = isset($quantities[$index]) ? (int) $quantities[$index] : 0;
        if ($quantity <= 0) {
            continue;
        }
        // Same variant twice in one call: accumulate rather than overwrite.
        $variant_id = (int) $variant_id;
        $wanted[$variant_id] = isset($wanted[$variant_id]) ? $wanted[$variant_id] + $quantity : $quantity;
    }

    // Nothing to do. The old code built `ORDER BY FIELD(pv.id,)` from an empty id list, which
    // is a SQL syntax error - the query returned FALSE and the next line fataled with
    // "Call to a member function result_array() on bool". The Razorpay payment-success webhook
    // hits exactly this (it passes keys that fetch_orders does not produce), so every
    // successful Razorpay payment crashed the webhook after recording the payment, and
    // Razorpay then retried it.
    // Consume the reason set for this call (see set_stock_movement_context). Cleared straight
    // away so an unlabelled later call cannot inherit it.
    $context = isset($t->_stock_movement_context) ? $t->_stock_movement_context : null;
    $t->_stock_movement_context = null;

    if (empty($wanted)) {
        return;
    }

    $res = $t->db->select('p.id as p_id, p.stock_type, p.stock as p_stock, pv.id as pv_id, pv.stock as pv_stock')
        ->where_in('pv.id', array_keys($wanted))
        ->join('products p', 'pv.product_id = p.id')
        ->get('product_variants pv')
        ->result_array();

    foreach ($res as $row) {
        $quantity = isset($wanted[$row['pv_id']]) ? (int) $wanted[$row['pv_id']] : 0;
        if ($quantity <= 0) {
            continue;
        }

        $mirror_to_siblings = null; // set only by the product-level branch below

        // stock_type is free text in practice: NULL / '' mean stock management is off, and 10
        // live products store the literal string 'simple_product' instead of 0. Under PHP 8
        // 'simple_product' == 0 is FALSE (it was TRUE on PHP 7), so those products matched no
        // branch at all and their stock was never moved in either direction - sold without
        // ever decrementing. Normalised to the documented 0/1/2 before dispatching.
        $stock_type = normalise_stock_type($row['stock_type']);
        if ($stock_type === null) {
            continue; // stock management off, or an unrecognised marker
        }

        if ($stock_type === 0) {
            $table = 'products';
            $where = ['id' => $row['p_id']];
            $current = $row['p_stock'];
            // Which row the audit log should re-read afterwards. Same as $where here.
            $audit_where = $where;
        } elseif ($stock_type === 1) {
            /*
             * Product level: every variant of the product is meant to hold ONE shared number.
             *
             * This used to update `WHERE product_id = X`, applying `stock + delta` to each
             * variant row separately. That looks equivalent, but it only is while the rows
             * already agree - and once they disagree, a relative update preserves the gap
             * forever. Measured on this database: product 25 "Handmade Hair clips" is the one
             * product-level product and its three variants hold 12, 76 and 76. Nothing can ever
             * bring those together, so a customer buying the 76 variant is checked against 76
             * while the product really has 12 - overselling by design, indefinitely.
             *
             * So the movement is applied to the ordered variant only (relative, therefore still
             * safe against two concurrent checkouts), and the siblings are then mirrored onto the
             * result further down. The first movement collapses the divergence permanently.
             */
            $table = 'product_variants';
            $where = ['id' => $row['pv_id']];
            $mirror_to_siblings = $row['p_id'];
            $current = $row['pv_stock'];
            /*
             * ...but the audit log must re-read the SAME row it recorded `stock_before` from.
             * It used to re-read with $where, i.e. `WHERE product_id = X`, and take row_array()
             * off a multi-row result - so `stock_after` came from whichever variant MySQL
             * returned first, which is generally NOT the variant that was ordered and can even
             * be a deleted one. Observed live on a product-level product: a single +1 restore
             * logged stock_before = 77, delta = +1, stock_after = 13. Three numbers that cannot
             * all describe the same movement, in the table meant to be the stock audit trail.
             */
            $audit_where = ['id' => $row['pv_id']];
        } elseif ($stock_type === 2) {
            $table = 'product_variants';
            $where = ['id' => $row['pv_id']];
            $current = $row['pv_stock'];
            $audit_where = $where;
        } else {
            continue;
        }

        // A NULL stock means this row does not track stock; leave it alone (matches the old
        // behaviour, which skipped NULL in both directions).
        if ($current === null) {
            continue;
        }

        // Done as ONE relative SQL statement instead of read-into-PHP-then-write. Three things
        // this fixes:
        //   * Overselling. The old code read the stock, computed in PHP, then wrote an absolute
        //     value. Two concurrent checkouts of the last unit both read the same number and
        //     both wrote stock-1, so the item sold twice. A relative update is applied by the
        //     database under a row lock.
        //   * Lost updates within a single call. For product-level stock two variants of the
        //     same product both read the pre-update value, so the second write undid the first
        //     (10 - 2 - 3 came out as 7). Relative updates accumulate correctly.
        //   * Negative stock. The only guard was "stock > 0", so 1 minus 5 wrote -4 - and
        //     because the availability flip tested `== 0` rather than `<= 0`, the product stayed
        //     purchasable forever at negative stock. GREATEST(0, ...) puts a floor under it.
        //
        // availability is assigned BEFORE stock in the SET list on purpose: MySQL evaluates
        // assignments left to right and later ones see already-updated values, so computing it
        // first means it reads the pre-update stock, exactly like the expression beside it.
        $delta = ($type == 'plus') ? $quantity : -$quantity;
        $new_stock_expr = 'GREATEST(0, COALESCE(`stock`, 0) + (' . (int) $delta . '))';

        $t->db->set('availability', 'IF(' . $new_stock_expr . ' > 0, \'1\', \'0\')', false)
            ->set('stock', $new_stock_expr, false)
            ->where($where)
            ->update($table);

        // Audit row. The resulting stock is re-read rather than computed, so the log records
        // what the column actually holds even when a concurrent movement landed in between.
        // Read via $audit_where, which always identifies exactly the row $current came from.
        $after = $t->db->select('stock')->where($audit_where)->get($table)->row_array();

        // Product-level stock only: bring every other variant of the product onto the number the
        // ordered variant now holds, so "one stock figure per product" is actually true. Copied
        // from the re-read value rather than recomputed, so the siblings cannot disagree with the
        // row this movement was validated against.
        if (!empty($mirror_to_siblings) && isset($after['stock'])) {
            $t->db->set('stock', (int) $after['stock'])
                ->set('availability', ((int) $after['stock'] > 0) ? '1' : '0')
                ->where('product_id', $mirror_to_siblings)
                ->where('id !=', $row['pv_id'])
                ->update('product_variants');
        }

        $t->db->insert('stock_logs', [
            'product_id'         => $row['p_id'],
            'product_variant_id' => $row['pv_id'],
            'stock_table'        => $table,
            'reason'             => isset($context['reason']) ? $context['reason'] : (($type == 'plus') ? 'restore' : 'deduct'),
            'quantity'           => $quantity,
            'delta'              => $delta,
            'stock_before'       => (int) $current,
            'stock_after'        => isset($after['stock']) ? (int) $after['stock'] : null,
            'order_id'           => isset($context['order_id']) ? $context['order_id'] : null,
            'user_id'            => isset($context['user_id']) ? $context['user_id'] : null,
            'note'               => isset($context['note']) ? $context['note'] : null,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
    }
}

function validate_stock($product_variant_ids, $qtns)
{
    /*
		--First Check => Is stock management active (Stock type != NULL) 
		Case 1 : Simple Product 		
		Case 2 : Variable Product (Product Level,Variant Level) 			

		Stock Type :
			0 => Simple Product(simple product)
			  	-Stock will be stored in (product)master table	
			1 => Product level(variable product)
				-Stock will be stored in product_variant table	
			2 => Variant level(variable product)		
				-Stock will be stored in product_variant table	
		*/
    $t = &get_instance();
    $response = array();
    $is_exceed_allowed_quantity_limit = false;
    $error = false;
    $count = isset($product_variant_ids) ? count($product_variant_ids) : '';
    for ($i = 0; $i < $count; $i++) {
        /*
         * The quantity must be a positive whole number, and that has to be asserted BEFORE any
         * stock arithmetic happens.
         *
         * Every stock check below is phrased as `stock - quantity < 0`. A negative quantity turns
         * that into `stock + n`, which is larger, so it passed every check - and then the line
         * subtotal came out negative, update_stock('minus') INCREASED sellable inventory, and the
         * order total went below zero. orders.id=26 in this database is exactly that: a total of
         * -1099 with its order_charges row at -1099 too. A negative line in a multi-item cart can
         * also offset the positive ones, bringing any basket down to whatever the buyer chooses.
         *
         * Zero is refused as well: it is never a purchase, and it would create an order line for
         * nothing while still reserving a settlement row for it. Non-integers ("1.5", "1e3",
         * "abc") are refused for the same reason - the value ends up in arithmetic and in the
         * quantity column, and intval() silently turns anything unparseable into 0.
         */
        $qty_raw = isset($qtns[$i]) ? trim((string) $qtns[$i]) : '';
        if ($qty_raw === '' || !ctype_digit($qty_raw) || (int) $qty_raw < 1) {
            $response['error'] = true;
            $response['message'] = 'Please enter a valid quantity.';
            $response['data'] = array();
            return $response;
        }

        $res = $t->db->select('p.*,pv.*,pv.id as pv_id,p.stock as p_stock,p.availability as p_availability,pv.stock as pv_stock,pv.availability as pv_availability,p.name as product_name')->where('pv.id = ', $product_variant_ids[$i])->join('products p', 'pv.product_id = p.id')->get('product_variants pv')->result_array();

        // An invalid / removed variant is treated as not purchasable.
        if (empty($res)) {
            $error = true;
            break;
        }

        // Out-of-stock / unavailable FLAG check. This must run regardless of stock_type:
        // a seller can mark a product/variant unavailable (availability = 0) WITHOUT using
        // quantity-based stock management, and update_stock() also flips availability to 0
        // when tracked stock reaches 0. Previously this flag was only honoured inside the
        // stock_type block, so an unavailable item with no stock management (or with an
        // empty stock value) slipped through into the cart and the order.
        // For variant-level / product-level variable stock the variant flag governs;
        // otherwise (simple product or no stock management) the product flag governs.
        // A null / empty flag means "available".
        // stock_type is free text in practice: NULL / '' mean stock management is off, and this
        // database holds 10 products storing the literal string 'simple_product' instead of 0.
        // Normalised here for the same reason update_stock() and get_low_stock_items() normalise
        // it - the raw comparisons below ('simple_product' == 0 is FALSE on PHP 8) matched no
        // branch at all, so those products passed this check with their stock never examined
        // while update_stock() went on to deduct from it. The result was a product that could be
        // ordered past its stock: no pre-purchase rejection, and the quantity simply floored at
        // zero on the way out.
        $normalised_stock_type = normalise_stock_type($res[0]['stock_type']);

        $effective_availability = ($normalised_stock_type === 1 || $normalised_stock_type === 2)
            ? $res[0]['pv_availability']
            : $res[0]['p_availability'];
        if ($effective_availability === 0 || $effective_availability === '0') {
            $error = true;
            break;
        }

        if ($res[0]['total_allowed_quantity'] != null && $res[0]['total_allowed_quantity'] >= 0) {
            $total_allowed_quantity = intval($res[0]['total_allowed_quantity']) - intval($qtns[$i]);
            if ($total_allowed_quantity < 0) {
                $error = true;
                $is_exceed_allowed_quantity_limit = true;
                break;
            }
        }

        if ($normalised_stock_type !== null) {
            //Case 1 : Simple Product(simple product)
            if ($normalised_stock_type === 0) {
                if ($res[0]['p_stock'] != null && $res[0]['p_stock'] != '') {
                    $stock = intval($res[0]['p_stock']) - intval($qtns[$i]);
                    if ($stock < 0 || $res[0]['p_availability'] == 0) {
                        $error = true;
                        break;
                    }
                }
            }
            //Case 2 & 3 : Product level(variable product) ||  Variant level(variable product)
            if ($normalised_stock_type === 1 || $normalised_stock_type === 2) {
                if ($res[0]['pv_stock'] != null && $res[0]['pv_stock'] != '') {
                    $stock = intval($res[0]['pv_stock']) - intval($qtns[$i]);
                    if ($stock < 0 || $res[0]['pv_availability'] == 0) {
                        $error = true;
                        break;
                    }
                }
            }
        }
    }

    if ($error) {
        $response['error'] = true;
        // $res is EMPTY when the loop broke because the variant no longer exists (deleted or a
        // bogus id), so reading [0]['product_name'] raised two warnings - and because this runs
        // during an AJAX request, that warning HTML was emitted ahead of the JSON body and broke
        // the caller's response parsing. Named generically when there is no product to name.
        $product_label = isset($res[0]['product_name']) ? $res[0]['product_name'] : 'This';
        if ($is_exceed_allowed_quantity_limit) {
            $response['message'] = $product_label . " product's quantity exceeds the allowed limit.Please deduct some quanity in order to purchase the item";
        } else {
            $response['message'] =  $product_label . " product is out of stock.";
        }
    } else {
        $response['error'] = false;
        $response['message'] = "Stock available for purchasing.";
    }
    return $response;
}

//stock_status()
function stock_status($product_variant_id)
{
    /*
		--First Check => Is stock management active (Stock type != NULL) 
		Case 1 : Simple Product 		
		Case 2 : Variable Product (Product Level,Variant Level) 			

		Stock Type :
			0 => Simple Product(simple product)
			  	-Stock will be stored in (product)master table	
			1 => Product level(variable product)
				-Stock will be stored in product_variant table	
			2 => Variant level(variable product)		
				-Stock will be stored in product_variant table	
		*/
    $t = &get_instance();
    $res = $t->db->select('p.*,pv.*,pv.id as pv_id,p.stock as p_stock,pv.stock as pv_stock')->where_in('pv.id', $product_variant_id)->join('products p', 'pv.product_id = p.id')->get('product_variants pv')->result_array();
    $out_of_stock = false;
    for ($i = 0; $i < count($res); $i++) {
        if (($res[$i]['stock_type'] != null && !empty($res[$i]['stock_type']))) {
            //Case 1 : Simple Product(simple product)
            if ($res[$i]['stock_type'] == 0) {

                if ($res[$i]['p_stock'] == null || $res[$i]['p_stock'] == 0) {
                    $out_of_stock = true;
                    break;
                }
            }
            //Case 2 & 3 : Product level(variable product) ||  Variant level(variable product)
            if ($res[$i]['stock_type'] == 1 || $res[$i]['stock_type'] == 2) {
                if ($res[$i]['pv_stock'] == null || $res[$i]['pv_stock'] == 0) {
                    $out_of_stock = true;
                    break;
                }
            }
        }
    }
    return $out_of_stock;
}

//verify_user()
function verify_user($data)
{
    $t = &get_instance();
    $res = $t->db->where('mobile', $data['mobile'])->get('users')->result_array();
    return $res;
}

//edit_unique($value, $params)
function edit_unique($value, $params)
{
    $CI = &get_instance();

    $CI->form_validation->set_message('edit_unique', "Sorry, that %s is already being used.");

    list($table, $field, $current_id) = explode(".", $params);

    $query = $CI->db->select()->from($table)->where($field, $value)->limit(1)->get();
    if ($query->row() && $query->row()->id != $current_id) {
        return FALSE;
    } else {
        return TRUE;
    }
}

function validate_order_status($order_ids, $status, $table = 'order_items', $user_id = null, $fromuser = false)
{
    $t = &get_instance();
    $error = 0;
    $cancelable_till = '';
    $returnable_till = '';
    $is_already_returned = 0;
    $is_already_cancelled = 0;
    $is_returnable = 0;
    $is_cancelable = 0;
    $returnable_count = 0;
    $cancelable_count = 0;
    $return_request = 0;
    $check_status = ['received', 'processed', 'shipped', 'delivered', 'cancelled', 'returned'];
    $group = array('admin', 'delivery_boy');
    if (in_array(strtolower(trim($status)), $check_status)) {
        if ($table == 'order_items') {
            $t->db->select('active_status');
            $t->db->where_in('id', explode(',', $order_ids));
            $active_status = $t->db->get('order_items')->result_array();
            $active_status = array_column($active_status, 'active_status');
            if (in_array("cancelled", $active_status) || in_array("returned", $active_status)) {
                $response['error'] = true;
                $response['message'] = "You can't update status once item cancelled / returned";
                $response['data'] = array();
                return $response;
            }
        }

        // oi.delivered_at is selected so the return WINDOW can be enforced below. It was only
        // ever applied in fetch_orders() when rendering the order page, which hides the Return
        // button but does nothing to the endpoint behind it - so a request posted directly (or
        // replayed) could return an item delivered any length of time ago, including one whose
        // commission had already been settled to the seller weeks earlier.
        $t->db->select('p.*,oi.active_status,pv.*,oi.id as order_item_id,oi.user_id as user_id,oi.product_variant_id as product_variant_id,oi.order_id as order_id, oi.status as order_item_status, oi.delivered_at as delivered_at')
            ->join('product_variants pv', 'pv.id=oi.product_variant_id', 'left')
            ->join('products p', 'pv.product_id=p.id', 'left');
        if ($table == 'orders') {
            $t->db->where('oi.order_id', $order_ids);
        } else {
            $t->db->where_in('oi.id', explode(',', $order_ids));
        }
        $product_data = $t->db->get('order_items oi')->result_array();

        // $priority_status = [
        //     'received' => 0,
        //     'processed' => 1,
        //     'shipped' => 2,
        //     'delivered' => 3,
        //     'cancelled' => 4,
        //     'returned' => 5,
        // ];
        $priority_status = [
            'received' => 0,
            'processed' => 1,
            'shipped' => 2,
            'delivered' => 3,
            // A refused return leaves the item exactly where it was: delivered, and staying
            // sold. Absent from this ladder it scored 0 ("not yet delivered"), so once an admin
            // declined a return nobody - admin included - could mark that item returned again
            // if the decision was reversed.
            'return_request_decline' => 3,
            'return_request_pending' => 4,
            'return_request_approved' => 5,
            'cancelled' => 6,
            'returned' => 7,
        ];

        $is_posted_status_set = $canceling_delivered_item = $returning_non_delivered_item = false;
        $is_posted_status_set_count = 0;
        for ($i = 0; $i < count($product_data); $i++) {
            /* check if there are any products returnable or cancellable products available in the list or not */
            if ($product_data[$i]['is_returnable'] == 1) {
                $returnable_count += 1;
            }
            if ($product_data[$i]['is_cancelable'] == 1) {
                $cancelable_count += 1;
            }

            /* check if the posted status is present in any of the variants */
            $product_data[$i]['order_item_status'] = json_decode($product_data[$i]['order_item_status'], true);
            $order_item_status = array_column($product_data[$i]['order_item_status'], '0');

            /* check if posted status is already present in how many of the order items */
            if (in_array($status, $order_item_status)) {
                $is_posted_status_set_count++;
            }
            /* if all are marked as same as posted status set the flag */
            if ($is_posted_status_set_count == count($product_data)) {
                $is_posted_status_set = true;
            }

            /* check if user is cancelling the order after it is delivered */
            if (($status == "cancelled") && (in_array("delivered", $order_item_status) || in_array("returned", $order_item_status))) {
                $canceling_delivered_item = true;
            }

            /* check if user is returning non delivered item */
            if (($status == "returned") && !in_array("delivered", $order_item_status)) {
                $returning_non_delivered_item = true;
            }
        }

        if ($is_posted_status_set == true) {
            /* status posted is already present in any of the order item */
            $response['error'] = true;
            $response['message'] = "Order is already marked as $status. You cannot set it again!";
            $response['data'] = array();
            return $response;
        }

        if ($canceling_delivered_item == true) {
            /* when user is trying cancel delivered order / item */
            $response['error'] = true;
            $response['message'] = "You cannot cancel delivered or returned order / item. You can only return that!";
            $response['data'] = array();
            return $response;
        }
        if ($returning_non_delivered_item == true) {
            /* when user is trying return non delivered order / item */
            $response['error'] = true;
            $response['message'] = "You cannot return a non-delivered order / item. First it has to be marked as delivered and then you can return it!";
            $response['data'] = array();
            return $response;
        }

        $is_returnable = ($returnable_count >= 1) ? 1 : 0;
        $is_cancelable = ($cancelable_count >= 1) ? 1 : 0;

        for ($i = 0; $i < count($product_data); $i++) {
            if ($product_data[$i]['active_status'] == 'returned') {
                $error = 1;
                $is_already_returned = 1;
                break;
            }

            if ($product_data[$i]['active_status'] == 'cancelled') {
                $error = 1;
                $is_already_cancelled = 1;
                break;
            }

            if ($status == 'returned' && $product_data[$i]['is_returnable'] == 0) {
                $error = 1;
                break;
            }

            // active_status can legitimately hold values that are not on the ladder ('awaiting'
            // on an unpaid order, 'return_request_pending'), and indexing $priority_status with
            // one raised a warning and then compared null - which reads as 0, i.e. "not yet
            // delivered". Absent from the ladder is treated as not-delivered explicitly.
            $item_priority = isset($priority_status[$product_data[$i]['active_status']])
                ? $priority_status[$product_data[$i]['active_status']]
                : 0;

            if ($status == 'returned' && $product_data[$i]['is_returnable'] == 1 && $item_priority < 3) {
                $error = 1;
                $returnable_till = 'delivery';
                break;
            }

            // Enforce the return window, not just the returnable flag - but only for the
            // customer ($fromuser). Staff must still be able to mark an item returned after the
            // window closes: the customer raises the request inside the window and the parcel
            // frequently gets back after it, so gating the staff-side transition on the same
            // deadline would strand every return that took longer than the window to travel.
            if ($fromuser && $status == 'returned' && $product_data[$i]['is_returnable'] == 1) {
                $window = order_item_return_window($product_data[$i]);
                if ($window['expired']) {
                    $error = 1;
                    // A separate boolean, not just the date: return_till is null when no
                    // delivery date could be established at all, and testing the date alone
                    // would let that case fall through every message branch below and end up
                    // creating a return request regardless.
                    $return_window_closed = true;
                    $return_window_expired = $window['return_till'];
                    break;
                }
            }

            if ($status == 'cancelled' && $product_data[$i]['is_cancelable'] == 1) {
                $max = isset($priority_status[$product_data[$i]['cancelable_till']]) ? $priority_status[$product_data[$i]['cancelable_till']] : 0;
                $min = $item_priority;

                if ($min > $max) {
                    $error = 1;
                    $cancelable_till = $product_data[$i]['cancelable_till'];
                    break;
                }
            }

            if ($status == 'cancelled' && $product_data[$i]['is_cancelable'] == 0) {
                $error = 1;
                break;
            }
        }

        if ($status == 'returned' && $error == 1 && !empty($return_window_closed)) {
            if (!empty($return_window_expired)) {
                $response['message'] = (count($product_data) > 1)
                    ? "One of the order item is past its return window (returnable till " . $return_window_expired . ")."
                    : "This item can no longer be returned. The return window closed on " . $return_window_expired . ".";
            } else {
                // No delivery date on record, so there is no window to be inside of.
                $response['message'] = (count($product_data) > 1)
                    ? "One of the order item has no recorded delivery date, so it cannot be returned."
                    : "This item cannot be returned because it has no recorded delivery date.";
            }
            $response['error'] = true;
            $response['data'] = array();
            return $response;
        }

        if ($status == 'returned'  && $error == 1 && !empty($returnable_till)) {
            $response['error'] = true;
            $response['message'] = (count($product_data) > 1) ? "One of the order item is not delivered yet !" : "The order item is not delivered yet !";
            $response['data'] = array();
            return $response;
        }
        if ($status == 'returned'  && $error == 1 && !$t->ion_auth->logged_in() && !$t->ion_auth->in_group($group, $user_id)) {
            $response['error'] = true;
            $response['message'] = (count($product_data) > 1) ? "One of the order item can't be returned !" : "The order item can't be returned !";
            $response['data'] = $product_data;
            return $response;
        }

        if ($status == 'cancelled' && $error == 1 && !empty($cancelable_till) && !$t->ion_auth->logged_in() && !$t->ion_auth->in_group($group, $user_id)) {
            $response['error'] = true;
            $response['message'] = (count($product_data) > 1) ? " One of the order item can be cancelled till " . $cancelable_till . " only " : "The order item can be cancelled till " . $cancelable_till . " only";
            $response['data'] = array();
            return $response;
        }

        if ($status == 'cancelled' && $error == 1 && !$t->ion_auth->logged_in() && !$t->ion_auth->in_group($group, $user_id)) {
            $response['error'] = true;
            $response['message'] = (count($product_data) > 1) ? "One of the order item can't be cancelled !" : "The order item can't be cancelled !";
            $response['data'] = array();
            return $response;
        }

        for ($i = 0; $i < count($product_data); $i++) {

            if ($status == 'returned' && $product_data[$i]['is_returnable'] == 1 && $error == 0) {
                $error = 1;
                $return_request_flag = 1;

                $return_status = [
                    'is_already_returned' =>  $is_already_returned,
                    'is_already_cancelled' =>  $is_already_cancelled,
                    'return_request_submitted' =>  $return_request,
                    'is_returnable' =>  $is_returnable,
                    'is_cancelable' =>  $is_cancelable,
                ];

                if ($fromuser == true || $fromuser == 1) {


                    if ($table == 'order_items') {

                        if (is_exist(['user_id' => $product_data[$i]['user_id'], 'order_item_id' => $product_data[$i]['order_item_id'], 'order_id' => $product_data[$i]['order_id']], 'return_requests')) {

                            $response['error'] = true;
                            $response['message'] =  "Return request already submitted !";
                            $response['data'] = array();
                            $response['return_status'] =  $return_status;
                            return $response;
                        }
                        $request_data_item_data = $product_data[$i];
                        set_user_return_request($request_data_item_data, $table);
                    } else {

                        // $product_data[$j], not $product_data[$i]. Indexed by $i this loop
                        // re-tested the SAME item once per item on the order and never looked at
                        // any of the others, so a whole-order return only noticed an existing
                        // request if it happened to be against the first item. With a request
                        // already pending on any later item, set_user_return_request() below
                        // inserted a SECOND row for it - two pending requests for one item, both
                        // approvable (the "already finalized" guard is per request row), and
                        // create_shiprocket_return_shipment() booked a courier pickup for each.
                        for ($j = 0; $j < count($product_data); $j++) {
                            if (is_exist(['user_id' => $product_data[$j]['user_id'], 'order_item_id' => $product_data[$j]['order_item_id'], 'order_id' => $product_data[$j]['order_id']], 'return_requests')) {

                                $response['error'] = true;
                                $response['message'] =  "Return request already submitted !";
                                $response['data'] = array();
                                $response['return_status'] =  $return_status;
                                return $response;
                            }
                        }
                        $request_data_overall_item_data = $product_data;
                        set_user_return_request($request_data_overall_item_data, $table);
                    }
                }

                $response['error'] = false;
                $response['message'] =  "Return request submitted successfully !";
                $response['return_request_flag'] =  1;
                $response['data'] = array();
                return $response;
            }
        }

        $response['error'] = false;
        $response['message'] = " ";
        $response['data'] = array();

        return $response;
    } else {
        $response['error'] = true;
        $response['message'] = "Invalid Status Passed";
        $response['data'] = array();
        return $response;
    }
}

/**
 * Works out whether an order item is still inside its return window.
 *
 * The delivery date is taken from order_items.delivered_at (the authoritative column added by
 * migration 036), falling back to searching the status history BY NAME. It is deliberately not
 * read positionally: the history is not guaranteed to be
 * received -> processed -> shipped -> delivered, so index 3 is frequently not the delivery
 * entry - or does not exist at all when an item was marked delivered directly.
 *
 * A missing delivery date means "never delivered", which is not returnable. That is the same
 * conclusion fetch_orders() reaches when it renders the Return button, so the endpoint and the
 * UI now agree instead of the UI hiding a control the endpoint would still honour.
 *
 * @param  array $item  row containing delivered_at and (optionally) a decoded status history
 * @return array ['expired' => bool, 'return_till' => string|null, 'delivered_at' => string|null]
 */
/**
 * Books a Shiprocket reverse pickup for an approved return.
 *
 * The customer's delivery address becomes the PICKUP end and the seller's registered pickup
 * location becomes the DROP end - the mirror of the forward shipment. The resulting shipment
 * is recorded in order_tracking with is_return = 1 so the webhook can tell the return leg
 * apart from the original delivery; both legs reference the same order item, and without the
 * flag a "DELIVERED" callback for the return would have marked the item delivered again.
 *
 * Deliberately non-fatal: if Shiprocket is disabled, unconfigured, or simply refuses the
 * booking, the return approval itself still stands and the customer is still refunded. The
 * parcel can then be arranged manually. Tying the refund to a successful courier booking would
 * mean an outage at Shiprocket blocks refunds.
 *
 * @param  int $order_item_id
 * @return array ['error' => bool, 'message' => string, 'data' => array]
 */
function create_shiprocket_return_shipment($order_item_id)
{
    $t = &get_instance();

    $shipping_settings = get_settings('shipping_method', true);
    if (empty($shipping_settings['shiprocket_shipping_method']) || $shipping_settings['shiprocket_shipping_method'] != 1) {
        return ['error' => true, 'message' => 'Shiprocket shipping is not enabled.', 'data' => []];
    }

    // Never book the same return twice - approving, un-approving and re-approving, or simply
    // double-clicking, would otherwise raise two reverse pickups for one parcel.
    $existing = fetch_details('order_tracking', ['order_item_id' => $order_item_id, 'is_return' => 1, 'is_canceled' => 0], 'id,shiprocket_order_id,shipment_id');
    if (!empty($existing)) {
        return ['error' => false, 'message' => 'A return pickup has already been booked for this item.', 'data' => $existing[0]];
    }

    $item = $t->db
        ->select('oi.id, oi.order_id, oi.quantity, oi.price, oi.sub_total, oi.tax_amount, oi.seller_id, oi.product_variant_id,
                  p.name as product_name, p.slug as product_slug, p.sku as product_sku, p.pickup_location,
                  pv.sku as variant_sku, pv.weight, pv.length, pv.breadth, pv.height')
        ->join('product_variants pv', 'pv.id = oi.product_variant_id', 'left')
        ->join('products p', 'p.id = pv.product_id', 'left')
        ->where('oi.id', $order_item_id)
        ->get('order_items oi')
        ->row_array();

    if (empty($item)) {
        return ['error' => true, 'message' => 'Order item not found.', 'data' => []];
    }

    $order = fetch_details('orders', ['id' => $item['order_id']], 'id,user_id,address_id,mobile,date_added,payment_method');
    if (empty($order)) {
        return ['error' => true, 'message' => 'Order not found.', 'data' => []];
    }

    $address = fetch_details('addresses', ['id' => $order[0]['address_id']], 'address,city_id,city,state,country,pincode,mobile,name');
    if (empty($address) || empty($address[0]['pincode'])) {
        return ['error' => true, 'message' => 'The delivery address on this order is incomplete, so a return pickup cannot be booked.', 'data' => []];
    }

    $pickup = fetch_details('pickup_locations', ['pickup_location' => $item['pickup_location'], 'seller_id' => $item['seller_id']], '*');
    if (empty($pickup)) {
        // Fall back to any location this seller has registered, rather than failing outright -
        // products created before pickup locations existed carry no pickup_location value.
        $pickup = fetch_details('pickup_locations', ['seller_id' => $item['seller_id']], '*');
    }
    if (empty($pickup)) {
        return ['error' => true, 'message' => 'The seller has no Shiprocket pickup location configured, so the return cannot be collected.', 'data' => []];
    }
    $pickup = $pickup[0];

    $customer = fetch_details('users', ['id' => $order[0]['user_id']], 'username,email,mobile');
    $customer_name = !empty($address[0]['name']) ? $address[0]['name'] : (!empty($customer[0]['username']) ? $customer[0]['username'] : 'Customer');
    $customer_phone = !empty($address[0]['mobile']) ? $address[0]['mobile'] : (!empty($order[0]['mobile']) ? $order[0]['mobile'] : (isset($customer[0]['mobile']) ? $customer[0]['mobile'] : ''));

    $city = !empty($address[0]['city']) ? $address[0]['city'] : '';
    if (empty($city) && !empty($address[0]['city_id'])) {
        // The `cities` table keys on city_id and names the column city_name - it has no `id` and
        // no `name`, so this lookup never returned anything and the Shiprocket booking went out
        // with an empty city. Shiprocket requires that field and rejects the shipment.
        $city_row = fetch_details('cities', ['city_id' => $address[0]['city_id']], 'city_name');
        $city = !empty($city_row) ? $city_row[0]['city_name'] : '';
    }

    $sku = !empty($item['variant_sku']) ? $item['variant_sku'] : (!empty($item['product_sku']) ? $item['product_sku'] : $item['product_slug']);

    // Shiprocket rejects a zero weight outright, and dimensions of 0 on any axis. Products
    // predating the shipping fields carry 0 in all four, so fall back to a nominal parcel.
    $weight  = shiprocket_parcel_weight($item['weight']);
    $length  = ((float) $item['length'] > 0) ? (float) $item['length'] : 10;
    $breadth = ((float) $item['breadth'] > 0) ? (float) $item['breadth'] : 10;
    $height  = ((float) $item['height'] > 0) ? (float) $item['height'] : 10;

    $payload = [
        // Unique per return, and traceable back to the item it belongs to.
        'order_id'   => 'RET-' . $item['order_id'] . '-' . $item['id'],
        'order_date' => date('Y-m-d H:i', strtotime($order[0]['date_added'])),
        'channel_id' => '',

        /* PICKUP = the customer (this is the reverse leg) */
        'pickup_customer_name' => $customer_name,
        'pickup_last_name'     => '',
        'pickup_address'       => $address[0]['address'],
        'pickup_address_2'     => '',
        'pickup_city'          => $city,
        'pickup_state'         => $address[0]['state'],
        'pickup_country'       => $address[0]['country'],
        'pickup_pincode'       => $address[0]['pincode'],
        'pickup_email'         => isset($customer[0]['email']) ? $customer[0]['email'] : '',
        'pickup_phone'         => $customer_phone,

        /* SHIPPING = back to the seller's registered pickup location */
        'shipping_customer_name' => $pickup['name'],
        'shipping_last_name'     => '',
        'shipping_address'       => $pickup['address'],
        'shipping_address_2'     => isset($pickup['address_2']) ? $pickup['address_2'] : '',
        'shipping_city'          => $pickup['city'],
        'shipping_country'       => $pickup['country'],
        'shipping_pincode'       => $pickup['pin_code'],
        'shipping_state'         => $pickup['state'],
        'shipping_email'         => $pickup['email'],
        'shipping_phone'         => $pickup['phone'],

        'order_items' => [[
            'name'          => $item['product_name'],
            'sku'           => $sku,
            'units'         => (int) $item['quantity'],
            'selling_price' => (float) $item['price'],
            'discount'      => 0,
            'hsn'           => '',
        ]],

        // A return is never collected on delivery.
        'payment_method' => 'PREPAID',
        'sub_total'      => (float) $item['sub_total'],
        'length'         => $length,
        'breadth'        => $breadth,
        'height'         => $height,
        'weight'         => $weight,
    ];

    $t->load->library('shiprocket');
    $response = $t->shiprocket->create_return_order($payload);

    if (!is_array($response) || empty($response['order_id'])) {
        $reason = 'Shiprocket did not accept the return pickup.';
        if (is_array($response)) {
            if (!empty($response['message'])) {
                $reason = is_array($response['message']) ? implode(' ', array_map('strval', $response['message'])) : $response['message'];
            } elseif (!empty($response['errors'])) {
                $reason = implode(' ', array_map(function ($e) {
                    return is_array($e) ? implode(' ', $e) : (string) $e;
                }, $response['errors']));
            }
        }
        return ['error' => true, 'message' => $reason, 'data' => is_array($response) ? $response : []];
    }

    $t->db->insert('order_tracking', [
        'order_id'              => $item['order_id'],
        'order_item_id'         => $item['id'],
        'shiprocket_order_id'   => $response['order_id'],
        'shipment_id'           => isset($response['shipment_id']) ? $response['shipment_id'] : 0,
        'courier_company_id'    => 0,
        'is_return'             => 1,
        'pickup_status'         => 0,
        'pickup_scheduled_date' => '',
        'pickup_token_number'   => '',
        'status'                => 0,
        'others'                => 'RETURN REQUESTED',
        'pickup_generated_date' => '',
        'data'                  => '',
        'date'                  => '',
        'manifest_url'          => '',
        'label_url'             => '',
        'invoice_url'           => '',
        'is_canceled'           => 0,
        'tracking_id'           => '',
        'url'                   => '',
    ]);

    return ['error' => false, 'message' => 'Return pickup booked with Shiprocket.', 'data' => $response];
}

function order_item_return_window($item)
{
    $delivery_date = null;

    if (!empty($item['delivered_at'])) {
        $delivery_date = $item['delivered_at'];
    } else {
        $history = isset($item['order_item_status']) ? $item['order_item_status'] : (isset($item['status']) ? $item['status'] : null);
        if (is_string($history)) {
            $history = json_decode($history, true);
        }
        if (is_array($history)) {
            foreach ($history as $entry) {
                if (is_array($entry) && isset($entry[0], $entry[1]) && $entry[0] === 'delivered') {
                    $delivery_date = $entry[1];
                    break;
                }
            }
        }
    }

    if (empty($delivery_date)) {
        return ['expired' => true, 'return_till' => null, 'delivered_at' => null];
    }

    $settings = get_settings('system_settings', true);
    $return_days = isset($settings['max_product_return_days']) ? (int) $settings['max_product_return_days'] : 0;

    $delivered_ts = strtotime($delivery_date);
    if ($delivered_ts === false) {
        return ['expired' => true, 'return_till' => null, 'delivered_at' => null];
    }

    $return_till = date('Y-m-d', strtotime('+' . $return_days . ' days', $delivered_ts));

    // `>` not `>=`, so the final day of the window is still returnable - matching the boundary
    // fetch_orders() uses when it decides whether to show the Return button.
    return [
        'expired'      => (date('Y-m-d') > $return_till),
        'return_till'  => $return_till,
        'delivered_at' => $delivery_date,
    ];
}

function is_exist($where, $table, $update_id = null)
{
    $t = &get_instance();
    $where_tmp = [];
    foreach ($where as $key => $val) {
        $where_tmp[$key] = $val;
    }

    if (($update_id == null)  ? $t->db->where($where_tmp)->get($table)->num_rows() > 0 : $t->db->where($where_tmp)->where_not_in('id', $update_id)->get($table)->num_rows() > 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * Records the customer's return request(s).
 *
 * One request per order item, enforced here rather than only in the callers: there is no unique
 * constraint on (order_id, order_item_id) and a duplicate row is not harmless - each one is
 * separately approvable and each approval books its own Shiprocket return pickup.
 */
function set_user_return_request($data, $table = 'orders')
{

    $data = escape_array($data);

    $t = &get_instance();

    $insert_once = function ($row) use ($t) {
        if (is_exist(['order_id' => $row['order_id'], 'order_item_id' => $row['order_item_id']], 'return_requests')) {
            return;
        }
        $t->db->insert('return_requests', [
            'user_id' => $row['user_id'],
            'product_id' => $row['product_id'],
            'product_variant_id' => $row['product_variant_id'],
            'order_id' => $row['order_id'],
            'order_item_id' => $row['order_item_id'],
        ]);
    };

    if ($table == 'orders') {
        for ($i = 0; $i < count($data); $i++) {
            $insert_once($data[$i]);
        }
    } else {
        $insert_once($data);
    }
}

function get_categories_option_html($categories, $selected_vals = null)
{
    $html = "";
    for ($i = 0; $i < count($categories); $i++) {
        $pre_selected = (!empty($selected_vals) && in_array($categories[$i]['id'], $selected_vals)) ? "selected" : "";
        $html .= '<option value="' . $categories[$i]['id'] . '" class="l' . $categories[$i]['level'] . '" ' . $pre_selected . '  >' . output_escaping($categories[$i]['name']) . '</option>';
        if (!empty($categories[$i]['children'])) {
            $html .= get_subcategory_option_html($categories[$i]['children'], $selected_vals);
        }
    }

    return $html;
}

function get_subcategory_option_html($subcategories, $selected_vals)
{
    $html = "";
    for ($i = 0; $i < count($subcategories); $i++) {
        $pre_selected = (!empty($selected_vals) && in_array($subcategories[$i]['id'], $selected_vals)) ? "selected" : "";
        // get_categories_option_html() (the top-level caller) escapes the name via
        // output_escaping(); this nested version never did, for every subcategory at every
        // depth, in every "Select Category/Parent" dropdown across the whole admin and seller
        // panels that render more than one level deep.
        $html .= '<option value="' . $subcategories[$i]['id'] . '" class="l' . $subcategories[$i]['level'] . '" ' . $pre_selected . '  >' . output_escaping($subcategories[$i]['name']) . '</option>';
        if (!empty($subcategories[$i]['children'])) {
            $html .=  get_subcategory_option_html($subcategories[$i]['children'], $selected_vals);
        }
    }
    return $html;
}

function get_cart_total($user_id, $product_variant_id = false, $is_saved_for_later = '0', $address_id = '', $is_cod = false)
{
    $t = &get_instance();
    $t->db->select('(select sum(c.qty)  from cart c join product_variants pv on c.product_variant_id=pv.id join products p on p.id=pv.product_id join seller_data sd on sd.user_id=p.seller_id  where c.user_id="' . $user_id . '" and qty >= 0  and  is_saved_for_later = "' . $is_saved_for_later . '" and p.status=1 AND pv.status=1 AND sd.status=1) as total_items,(select count(c.id) from cart c join product_variants pv on c.product_variant_id=pv.id join products p on p.id=pv.product_id join seller_data sd on sd.user_id=p.seller_id where c.user_id="' . $user_id . '" and qty>=0 and  is_saved_for_later = "' . $is_saved_for_later . '" and p.status=1 AND pv.status=1 AND sd.status=1) as cart_count,`c`.qty,c.is_saved_for_later,p.is_prices_inclusive_tax,p.cod_allowed,p.type,p.download_allowed,p.minimum_order_quantity,p.slug,p.quantity_step_size,p.total_allowed_quantity, p.name, p.image, p.stock as product_stock,p.is_attachment_required, p.availability as product_availability, p.short_description,p.pickup_location,p.is_prices_inclusive_tax,p.seller_id, pv.weight,`c`.user_id,pv.*,tax.percentage as tax_percentage,tax.title as tax_title,sd.store_name as store_name');

    if ($product_variant_id == true) {
        $t->db->where(['c.product_variant_id' => $product_variant_id, 'c.user_id' => $user_id, 'c.qty !=' => '0']);
    } else {
        $t->db->where(['c.user_id' => $user_id, 'c.qty >=' => '0']);
    }

    if ($is_saved_for_later == 0) {
        $t->db->where('is_saved_for_later', 0);
    } else {
        $t->db->where('is_saved_for_later', 1);
    }

    $t->db->join('product_variants pv', 'pv.id=c.product_variant_id');
    $t->db->join('products p ', 'pv.product_id=p.id');
    $t->db->join('seller_data sd ', 'sd.user_id=p.seller_id');
    $t->db->join('`taxes` tax', 'tax.id = p.tax', 'LEFT');
    $t->db->join('categories ctg', 'p.category_id = ctg.id', 'left');
    $t->db->where(['p.status' => '1', 'pv.status' => 1, 'sd.status' => 1]);
    $t->db->group_by('c.id')->order_by('c.id', "DESC");
    $data = $t->db->get('cart c')->result_array();
    //     echo "<pre>";
    // print_r($data);
    // die;

    $total_mrp = array();
    $discount_on_mrp = array();
    $total = array();
    $variant_id = array();
    $quantity = array();
    $percentage = array();
    $amount = array();
    $cod_allowed = 1;
    // Names of every cart item whose product forbids Cash on Delivery. The single
    // $cod_allowed flag only says "something in this cart blocks COD"; the checkout page and
    // Cart::place_order() both need to tell the customer WHICH items, all of them at once,
    // otherwise they are sent back to the cart to remove one product per attempt.
    $cod_blocked_products = array();
    $download_allowed = array();
    $is_attachment_required = '0';
    for ($i = 0; $i < count($data); $i++) {

        /* echo "<pre>";
        print_r($data[$i]);
        die; */

        $tax_title = (isset($data[$i]['tax_title']) && !empty($data[$i]['tax_title'])) ? $data[$i]['tax_title'] : '';
        $is_attachment_required = (isset($data[$i]['is_attachment_required']) && !empty($data[$i]['is_attachment_required'])) ? $data[$i]['is_attachment_required'] : '0';
        $prctg = (isset($data[$i]['tax_percentage']) && intval($data[$i]['tax_percentage']) > 0 && $data[$i]['tax_percentage'] != null) ? $data[$i]['tax_percentage'] : '0';
        $data[$i]['item_tax_percentage'] = $prctg;
        $data[$i]['tax_title'] = $tax_title;
        if ((isset($data[$i]['is_prices_inclusive_tax']) && $data[$i]['is_prices_inclusive_tax'] == 0) || (!isset($data[$i]['is_prices_inclusive_tax'])) && $prctg > 0) {
            $price_tax_amount = $data[$i]['price'] * ($prctg / 100);
            $special_price_tax_amount = $data[$i]['special_price'] * ($prctg / 100);
        } else {
            // $price_tax_amount  = $data[$i]['price'] - ($data[$i]['price'] * (100 / (100 + $prctg)));
            // $special_price_tax_amount  = $data[$i]['special_price'] - ($data[$i]['special_price'] * (100 / (100 + $prctg)));
            $price_tax_amount = 0;
            $special_price_tax_amount = 0;
        }
        $data[$i]['image_sm'] = get_image_url($data[$i]['image'], 'thumb', 'sm');
        $data[$i]['image_md'] = get_image_url($data[$i]['image'], 'thumb', 'md');
        $data[$i]['image'] = get_image_url($data[$i]['image']);
        if ($data[$i]['cod_allowed'] == 0) {
            $cod_allowed = 0;
            $blocked_name = isset($data[$i]['name']) ? trim((string) $data[$i]['name']) : '';
            if ($blocked_name !== '' && !in_array($blocked_name, $cod_blocked_products, true)) {
                $cod_blocked_products[] = $blocked_name;
            }
        }
        $variant_id[$i] = $data[$i]['id'];
        $quantity[$i] = intval($data[$i]['qty']);

        $total_mrp[$i] = floatval($data[$i]['price'] + $price_tax_amount) * $data[$i]['qty'];

        if (floatval($data[$i]['special_price']) > 0) {
            $total[$i] = floatval($data[$i]['special_price'] + $special_price_tax_amount) * $data[$i]['qty'];
            $discount_on_mrp[$i] = $total_mrp[$i] - $total[$i];
        } else {
            $total[$i] = floatval($data[$i]['price'] + $price_tax_amount) * $data[$i]['qty'];
        }
        $data[$i]['special_price'] = $data[$i]['special_price'] + $special_price_tax_amount;
        $data[$i]['price'] = $data[$i]['price'] + $price_tax_amount;

        $price = isset($data[$i]['special_price']) && !empty($data[$i]['special_price']) && $data[$i]['special_price'] > 0 ? $data[$i]['special_price'] : $data[$i]['price'];

        if (isset($data[$i]['is_prices_inclusive_tax']) && $data[$i]['is_prices_inclusive_tax'] == 1) {
            $tax_amount  = $price - ($price * (100 / (100 + $prctg)));
        } else {
            $tax_amount = $price * ($prctg / 100);
        }
        $data[$i]['tax_amount'] = number_format($tax_amount, 2);

        $percentage[$i] = (isset($data[$i]['tax_percentage']) && floatval($data[$i]['tax_percentage']) > 0) ? $data[$i]['tax_percentage'] : 0;

        if ($percentage[$i] != NUll && $percentage[$i] > 0) {
            $amount[$i] = (!empty($special_price_tax_amount)) ? number_format($special_price_tax_amount, 2) : number_format($price_tax_amount, 2);
        } else {
            $amount[$i] = 0;
            $percentage[$i] = 0;
        }

        $data[$i]['product_variants'] = get_variants_values_by_id($data[$i]['id']);
        array_push($download_allowed, $data[$i]['download_allowed']);
    }
    
    $total_mrp = array_sum($total_mrp);
    $discount_on_mrp = array_sum($discount_on_mrp);
    $total = array_sum($total);

    $system_settings = get_settings('system_settings', true);
    $delivery_charge = $system_settings['delivery_charge'];
    // $data[0] only exists when the cart actually holds a line - the summary keys added below
    // ($data['sub_total'] and friends) are present either way, so callers cannot tell an empty
    // cart apart by checking !empty($data). Quoting a delivery charge for nothing to deliver is
    // meaningless anyway, and reading $data[0]['product_id'] off an empty cart raised
    // "Undefined array key 0" here - reproduced live by calling cart/pre-payment-setup right
    // after an order cleared the cart, which printed the warning into the JSON the checkout
    // page then tried to parse.
    $cart_has_items = isset($data[0]) && is_array($data[0]) && isset($data[0]['product_id']);

    if ($cart_has_items && !empty($address_id) && !empty($address = fetch_details('addresses', ['id' => $address_id], ['area_id', 'area', 'pincode']))) {
        $zipcode_id = fetch_details('zipcodes', ['zipcode' => $address[0]['pincode']], 'id')[0] ?? array();

        $tmpRow['is_deliverable'] = (!empty($zipcode_id['id']) && $zipcode_id['id'] > 0) ?
            is_product_delivarable('zipcode', $zipcode_id['id'], $data[0]['product_id'])
            : false;

        $shipping_settings = get_settings('shipping_method', true);
        $local_shipping_enabled = isset($shipping_settings['local_shipping_method']) && $shipping_settings['local_shipping_method'] == 1;
        $shiprocket_enabled = isset($shipping_settings['shiprocket_shipping_method']) && $shipping_settings['shiprocket_shipping_method'] == 1;
            
        $tmpRow['delivery_by'] = ($tmpRow['is_deliverable']) && $local_shipping_enabled ? "local" : "standard_shipping";

        if (isset($tmpRow['delivery_by']) && $tmpRow['delivery_by'] == 'standard_shipping') {

            $parcels = make_shipping_parcels($data);
            $parcels_details = check_parcels_deliveriblity($parcels, $address[0]['pincode']);

            if($is_cod){
                $delivery_charge = $parcels_details['delivery_charge_with_cod'];
            }
            else{
                $delivery_charge = $parcels_details['delivery_charge_without_cod'];
            }
            
        } else {
            $delivery_charge = get_delivery_charge($address_id, $total);
        }

        /* If both are disabled, this should be handled as an error */
        if(!$shiprocket_enabled && !$local_shipping_enabled){
            $data['delivery_error'] = true;
        }
    }

    $delivery_charge = isset($data[0]['type']) && $data[0]['type'] == 'digital_product' ? 0 :  $delivery_charge;
    // Seller-paid shipping. Everything above still runs - the serviceability check decides
    // whether the cart can be delivered at all, and the courier ETA is still quoted to the
    // customer - but the CHARGE is dropped here, after the branches that computed it, so the
    // single figure the whole checkout derives its totals from is 0 and no caller can
    // reintroduce it. Cart::place_order() takes the delivery charge from this array, so this
    // is also what stops one being stored on the order.
    if (seller_paid_shipping_enabled()) {
        $data['quoted_delivery_charge'] = str_replace(",", "", (string) $delivery_charge);
        $delivery_charge = 0;
    }
    $delivery_charge = str_replace(",", "", $delivery_charge);
    $overall_amt = 0;
    $tax_amount = array_sum($amount);
    $overall_amt = $total + $delivery_charge;
    $data[0]['is_cod_allowed'] = $cod_allowed;
    $data['cod_blocked_products'] = $cod_blocked_products;
    $data['total_mrp'] = strval($total_mrp);
    $data['discount_on_mrp'] = strval($discount_on_mrp);
    $data['sub_total'] = strval($total);
    $data['quantity'] = strval(array_sum($quantity));
    $data['tax_percentage'] = strval(array_sum($percentage));
    $data['tax_amount'] = strval(array_sum($amount));
    $data['total_arr'] = $total;
    $data['variant_id'] = $variant_id;
    $data['delivery_charge'] = $delivery_charge;
    $data['is_free_delivery'] = seller_paid_shipping_enabled() ? 1 : 0;
    $data['overall_amount'] = strval($overall_amt);
    $data['amount_inclusive_tax'] = strval($overall_amt + $tax_amount);
    $data['is_attachment_required'] = $is_attachment_required;
    $data['download_allowed'] = $download_allowed;
    return $data;
}
function get_frontend_categories_html()
{
    $t = &get_instance();
    $t->load->model('category_model');

    $limit =  8;
    $offset =  0;
    $sort = 'row_order';
    $order =  'ASC';
    $has_child_or_item = 'false';


    $categories = $t->category_model->get_categories('', $limit, $offset, $sort, $order, trim($has_child_or_item));
    $nav = '<div class="cd-morph-dropdown"><a href="#0" class="nav-trigger">Open Nav<span aria-hidden="true"></span></a><nav class="main-nav"><ul>';
    $html = "<div class='morph-dropdown-wrapper'><div class='dropdown-list'><ul>";

    for ($i = 0; $i < count($categories); $i++) {
        $nav .= '<li class="has-dropdown" data-content="' . str_replace(' ', '', str_replace('&', '-', trim(strtolower(strip_tags(str_replace('\'', '', $categories[$i]['name'])))))) . '">';
        $nav .= '<a href="' . base_url('products/category/' . $categories[$i]['slug']) . '">' . Ucfirst($categories[$i]['name']) . '</a></li>';
        $html .= "<li id='" . str_replace(' ', '', str_replace('&', '-', trim(strtolower(strip_tags($categories[$i]['name']))))) . "' class='dropdown'> <a href='#0' class='label'>" . $categories[$i]['name'] . "</a><div class='content'><ul>";

        if (!empty($categories[$i]['children'])) {
            $html .= get_frontend_subcategories_html($categories[$i]['children']);
        }
        $html .= "</ul></div>";
    }
    $nav .= '<li><a href="' . base_url('home/categories') . '">See All</a></li>';
    $html .= "</ul><div class='bg-layer' aria-hidden='true'></div></div></div></div>";
    $nav .= '</ul></nav>';
    return $nav . $html;
}

function get_frontend_subcategories_html($subcategories)
{
    $html = "";

    for ($i = 0; $i < count($subcategories); $i++) {
        $html .= "<li><a href='#0'>" . $subcategories[$i]['name'] . "</a>";
        if (!empty($subcategories[$i]['children'])) {
            $html .= '<ul>' . get_frontend_subcategories_html($subcategories[$i]['children']) . '</ul>';
        }
        $html .= "</li>";
    }

    return $html;
}

function resize_image($image_data, $source_path, $id = false)
{
    if ($image_data['is_image']) {

        $t = &get_instance();

        $image_type = ['thumb', 'cropped'];
        $image_size = ['md' => array('width' => 800, 'height' => 800), 'sm' => array('width' => 450, 'height' => 450)];
        $target_path = $source_path; // Target path will be under source path
        $image_name = $image_data['file_name']; // original image's name    
        $w = $image_data['image_width']; // original image's width    
        $h = $image_data['image_height']; // original images's height 

        $t->load->library('image_lib');

        if ($id != false && is_numeric($id)) {
            // Resize the original images            
            $config['maintain_ratio'] = true;
            $config['create_thumb'] = FALSE;
            $config['source_image'] =  $source_path . $image_name;
            $config['new_image'] = $target_path . $image_name;
            $config['quality'] = '80%';
            $config['width'] = $w - 1;
            $config['height'] = $h - 1;
            $t->image_lib->initialize($config);
            if ($t->image_lib->resize()) {

                $size = filesize($config['new_image']);
                update_details(['size' => $size], ['id' => $id], 'media');
            } else {
                return $t->image_lib->display_errors();
            }
            $t->image_lib->clear();
        }

        for ($i = 0; $i < count($image_type); $i++) {

            if (file_exists($source_path . $image_name)) {

                //check if the image file exist 
                foreach ($image_size as $image_size_key => $image_size_value) {
                    if (!file_exists($target_path . $image_type[$i] . '-' . $image_size_key)) {
                        mkdir($target_path . $image_type[$i] . '-' . $image_size_key, 0777);
                    }

                    $n_w = $image_size_value['width']; // destination image's width //800
                    $n_h = $image_size_value['height']; // destination image's height //800
                    $config['image_library'] = 'gd2';
                    $config['create_thumb'] = FALSE;
                    $config['source_image'] =  $source_path . $image_name;
                    $config['new_image'] = $target_path . $image_type[$i] . '-' . $image_size_key . '/' . $image_name;
                    if (($w >= $n_w || $h >= $n_h) && $image_type[$i] == 'cropped') {
                        $y = date('Y');
                        $thumb_type = ($image_size_key == 'sm') ? 'thumb-sm/' : 'thumb-md/';
                        $thumb_path = $source_path . $thumb_type . $image_name;

                        $data = getimagesize($thumb_path);
                        $width = $data[0];
                        $height = $data[1];
                        $config['source_image'] = (file_exists($thumb_path)) ?  $thumb_path : $image_name;

                        /*  x-axis : (left)   
                        width : (right)   
                        y-axis : (top)    
                        height : (bottom) */
                        $config['maintain_ratio'] = false;

                        if ($width > $height) {
                            $config['width'] = $height;
                            $config['height'] = round($height);
                            $config['x_axis'] = (($width / 4) - ($n_w / 4));
                        } else {
                            $config['width'] = $width;
                            $config['height'] = $width;
                            $config['y_axis'] = (($height / 4) - ($n_h / 4));
                        }

                        $t->image_lib->initialize($config);
                        $t->image_lib->crop();
                        $t->image_lib->clear();
                    }

                    if (($w >= $n_w || $h >= $n_h) && $image_type[$i] == 'thumb') {
                        $config['maintain_ratio'] = true;
                        $config['create_thumb'] = FALSE;
                        $config['width'] = $n_w;
                        $config['height'] = $n_h;
                        $t->image_lib->initialize($config);
                        if (!$t->image_lib->resize()) {
                            return $t->image_lib->display_errors();
                        }
                        $t->image_lib->clear();
                    }
                }
            }
        }
    }
}

/**
 * The current request's memo of user_permissions rows, keyed by user id.
 *
 * Returned by reference so callers can write through it.
 */
function &user_permissions_cache_store()
{
    static $store = array();
    return $store;
}

/**
 * Drops the memoised permissions for this request.
 *
 * MUST be called after any write to `user_permissions`, otherwise the admin who has
 * just saved a system user's permissions would keep seeing the pre-write set for the
 * rest of that request. Every write site in System_users_model calls this.
 *
 * @param int|string|null $id Clear just this user, or NULL for all of them.
 */
function clear_user_permissions_cache($id = null)
{
    $store = &user_permissions_cache_store();
    if ($id === null) {
        $store = array();
    } else {
        unset($store[(string) $id]);
    }
}

/**
 * PERFORMANCE: memoised per request.
 *
 * has_permissions() calls this every time it is asked about a module, and the admin
 * sidebar asks about a module for every entry it draws. Measured with MySQL's general
 * log, a single /admin/home render issued
 *
 *     77 x  SELECT * FROM `user_permissions` WHERE `user_id` = '107'
 *
 * - 77 of the page's 111 queries, all of them the same row fetched again and again.
 *
 * The row can only change through System_users_model, which clears this memo on write,
 * so a save followed by a re-read in the same request still sees the new value. The
 * memo is per request only: nothing is shared across requests, so there is no way for
 * one admin's permissions to be served to another.
 */
function get_user_permissions($id)
{
    $store = &user_permissions_cache_store();
    $key = (string) $id;

    if (!array_key_exists($key, $store)) {
        $store[$key] = fetch_details('user_permissions', ['user_id' => $id]);
    }

    return $store[$key];
}

function has_permissions($role, $module)
{
    $role = trim($role);
    $module = trim($module);

    if (!is_modification_allowed($module) && in_array($role, ['create', 'update', 'delete'])) {
        return false; //Modification not allowed
    }
    $t = &get_instance();
    $id = $t->session->userdata('user_id');
    $t->load->config('eshop');
    $general_system_permissions  = $t->config->item('system_modules');
    $userData = get_user_permissions($id);

    // No user_permissions row at all: guests, customers, sellers, delivery boys, and
    // any admin whose permissions row was deleted. This used to fall off the end of the
    // function and return NULL implicitly - falsy, so the usual `!has_permissions(...)`
    // callers happened to deny, but the function had no boolean contract: any caller
    // written as `has_permissions(...) === false` would have silently granted access.
    // Deny explicitly.
    if (empty($userData)) {
        return false;
    }

    // role 0 is the super admin: holds every permission implicitly and has no
    // permissions JSON stored at all (System_users_model forces it to NULL for role 0).
    if (intval($userData[0]['role']) <= 0) {
        return true;
    }

    $permissions = !empty($userData[0]['permissions']) ? json_decode($userData[0]['permissions'], 1) : [];
    if (!is_array($permissions)) {
        return false;
    }

    // The module must be a real, registered module AND be present in this user's
    // granted set.
    if (!array_key_exists($module, $general_system_permissions) || !array_key_exists($module, $permissions)) {
        return false; //User has no permission
    }

    // The requested action must be a registered action for this module. Previously an
    // action that was NOT registered in system_modules for this module skipped the
    // grant check entirely and fell through to `return true` - so any sub-admin holding
    // any single permission on a module was granted every action name that happened not
    // to be registered for it (this is the bug the eshop.php comments describe hitting
    // via 'new_offer_images'). Verified before tightening: no has_permissions() call
    // site in this codebase currently depends on that fallthrough.
    if (!in_array($role, $general_system_permissions[$module], true)) {
        return false;
    }

    if (!is_array($permissions[$module]) || !array_key_exists($role, $permissions[$module])) {
        return false; //User has no permission
    }

    return true; //User has permission
}

function print_msg($error, $message, $module = false, $is_csrf_enabled = true)
{
    $t = &get_instance();
    if ($error) {

        $response['error'] = true;
        $response['message'] = (is_modification_allowed($module)) ? $message : DEMO_VERSION_MSG;
        if ($is_csrf_enabled) {
            $response['csrfName'] = $t->security->get_csrf_token_name();
            $response['csrfHash'] = $t->security->get_csrf_hash();
        }
        print_r(json_encode($response));
        return true;
    }
}

function get_system_update_info()
{
    $t = &get_instance();
    $db_version_data = $t->db->from('updates')->order_by("id", "desc")->get()->result_array();
    if (!empty($db_version_data) && isset($db_version_data[0]['version'])) {
        $db_current_version = $db_version_data[0]['version'];
    }
    if ($t->db->table_exists('updates') && !empty($db_current_version)) {
        $data['db_current_version'] = $db_current_version;
    } else {
        $data['db_current_version'] = $db_current_version = 1.0;
    }

    if (file_exists(UPDATE_PATH . "update/updater.txt") || file_exists(UPDATE_PATH . "updater.txt")) {
        $sub_directory = (file_exists(UPDATE_PATH . "update/folders.json")) ? "update/" : "";
        $lines_array = file(UPDATE_PATH . $sub_directory . "updater.txt");

        $search_string = "version";

        foreach ($lines_array as $line) {
            if (strpos($line, $search_string) !== false) {
                list(, $new_str) = explode(":", $line);
                // If you don't want the space before the word bong, uncomment the following line.
                $new_str = trim($new_str);
            }
        }
        $data['file_current_version'] = $file_current_version = $new_str;
    } else {
        $data['file_current_version'] = $file_current_version = false;
    }

    if ($file_current_version != false && $file_current_version > $db_current_version) {

        $data['is_updatable'] =  true;
    } else {
        $data['is_updatable'] =  false;
    }

    return $data;
}

/**
 * Strip anything credential-shaped out of an SMTP debug dump so the reason a send failed
 * can safely be written to the log.
 *
 * CI's print_debugger() replays the whole SMTP conversation, which includes the
 * base64-encoded username and password sent during AUTH LOGIN. Those lines - and any
 * literal copy of the configured password - are removed here.
 */
function redact_smtp_debug($debug, $config = [])
{
    $text = strip_tags((string) $debug);

    // The two bare base64 lines that follow "AUTH LOGIN" are the username and password.
    $text = preg_replace('/^\s*[A-Za-z0-9+\/]{16,}={0,2}\s*$/m', '[redacted]', $text);
    $text = preg_replace('/(AUTH\s+LOGIN)[^\r\n]*/i', '$1 [redacted]', $text);

    if (!empty($config['smtp_pass'])) {
        $text = str_replace(
            [$config['smtp_pass'], base64_encode($config['smtp_pass'])],
            '[redacted]',
            $text
        );
    }

    // Keep it to the useful part - the server's refusal - not the whole transcript.
    $text = preg_replace('/\s+/', ' ', $text);
    return trim(mb_substr($text, 0, 500));
}

function send_mail($to, $subject, $message)
{
    $t = &get_instance();
    $settings = get_settings('system_settings', true);
    $t->load->library('email');
    $config = $t->config->item('email_config');

    // email_config is assembled at runtime by the MyConfig::get_email_settings hook from
    // the settings table. If that row is missing or malformed the array is empty, and
    // reading $config['smtp_user'] below raised an undefined-index warning that printed
    // into the response body and corrupted the JSON callers were trying to parse.
    if (empty($config) || !is_array($config) || empty($config['smtp_user'])) {
        log_message('error', 'send_mail: email settings are not configured (admin > Email Settings).');
        return [
            'error'   => true,
            'config'  => [],
            'message' => 'Email is not configured. Set it up under Admin > Email Settings.',
        ];
    }

    $t->email->initialize($config);
    $t->email->set_newline("\r\n");

    $t->email->from($config['smtp_user'], $settings['app_name']);
    $t->email->to($to);
    $t->email->subject($subject);
    $t->email->message($message);
    if ($t->email->send()) {
        $response['error'] = false;
        $response['config'] = $config;
        $response['message'] = 'Email Sent';
    } else {
        $response['error'] = true;
        $response['config'] = $config;
        $response['message'] = $t->email->print_debugger();
        // Without this the log said only "email channel failed" with no reason, so a
        // failure like Gmail's "534-5.7.9 Application-specific password required" was
        // invisible to whoever had to fix it.
        $response['reason'] = redact_smtp_debug($response['message'], $config);
        log_message('error', 'send_mail: delivery to ' . $to . ' failed - ' . $response['reason']);
    }

    return $response;
}
function send_digital_product_mail($to, $subject, $message, $attachment)
{
    $t = &get_instance();
    $settings = get_settings('system_settings', true);
    $t->load->library('email');
    $config = $t->config->item('email_config');
    $config['mailtype'] = 'html';
    $t->email->initialize($config);
    $t->email->set_newline("\r\n");

    $t->email->from($config['smtp_user'], $settings['app_name']);
    $t->email->to($to);
    $t->email->subject($subject);
    $t->email->message($message);
    $t->email->attach($attachment);
    if ($t->email->send()) {
        $response['error'] = false;
        $response['config'] = $config;
        $response['message'] = 'Email Sent';
    } else {
        $response['error'] = true;
        $response['config'] = $config;
        $response['message'] = $t->email->print_debugger();
    }

    return $response;
}

function fetch_orders($order_id = NULL, $user_id = NULL, $status = NULL, $delivery_boy_id = NULL, $limit = NULL, $offset = NULL, $sort = NULL, $order = NULL, $download_invoice = false, $start_date = null, $end_date = null, $search = null, $city_id = null, $area_id = null, $seller_id = null, $order_type = '', $from_seller = false, $search_2 = null)
{

    $t = &get_instance();
    $where = [];

    $count_res = $t->db->select(' COUNT(distinct o.id) as `total`')
        ->join(' `users` u', 'u.id= o.user_id', 'left')
        ->join(' `order_items` oi', 'o.id= oi.order_id', 'left')
        ->join('product_variants pv', 'pv.id=oi.product_variant_id', 'left')
        ->join('products p', 'pv.product_id=p.id', 'left')
        ->join('order_tracking ot ', ' ot.order_item_id = oi.id', 'left')
        ->join('addresses a', 'a.id=o.address_id', 'left');
    if (isset($order_id) && $order_id != null) {
        $where['o.id'] = $order_id;
    }

    if (isset($delivery_boy_id) && $delivery_boy_id != NULL) {
        $where['oi.delivery_boy_id'] = $delivery_boy_id;
    }

    if (isset($user_id) && $user_id != null) {
        $where['o.user_id'] = $user_id;
    }
    if (isset($city_id) && $city_id != null) {
        $where['a.city_id'] = $city_id;
    }
    if (isset($area_id) && $area_id != null) {
        $where['a.area_id'] = $area_id;
    }
    if (isset($seller_id) && $seller_id != null) {
        $where['oi.seller_id'] = $seller_id;
    }
    if (isset($order_type) && $order_type != '' && $order_type == 'digital') {
        $where['p.type'] = 'digital_product';
    }
    if (isset($order_type) && $order_type != '' && $order_type == 'simple') {
        $where['p.type !='] = 'digital_product';
    }


    if (isset($status) &&  is_array($status) &&  count($status) > 0) {
        $status = array_map('trim', $status);
        $count_res->where_in('oi.active_status', $status);
    }

    if (isset($start_date) && $start_date != null && isset($end_date) && $end_date != null) {
        $count_res->where(" DATE(o.date_added) >= DATE('" . $start_date . "') ");
        $count_res->where(" DATE(o.date_added) <= DATE('" . $end_date . "') ");
    }

    if (isset($search) and $search != null) {

        $filters = [
            'u.username' => $search,
            'u.email' => $search,
            'o.id' => $search,
            'o.mobile' => $search,
            'o.address' => $search,
            'o.payment_method' => $search,
            'o.delivery_time' => $search,
            'o.date_added' => $search,
            'p.name' => $search,
            'oi.active_status' => $search,
        ];
    }
    if (isset($filters) && !empty($filters)) {
        $count_res->group_Start();
        $count_res->or_like($filters);
        $count_res->group_End();
    }


    $count_res->where($where);

    if (isset($seller_id) && $seller_id != null) {
        $count_res->where("oi.active_status != 'awaiting'");
    }

    if ($sort == 'date_added') {
        $sort = 'o.date_added';
    }
    $count_res->order_by($sort, $order);

    $order_count = $count_res->get('`orders` o')->result_array();

    $total = "0";
    foreach ($order_count as $row) {
        $total = $row['total'];
    }

    $search_res = $t->db->select(' o.*, u.username,u.country_code, u.email as email, p.name,p.type,p.download_allowed,p.pickup_location,a.name as order_recipient_person,pv.special_price,pv.price,oc.delivery_charge as seller_delivery_charge,oc.promo_discount as seller_promo_dicount')
        ->join(' `users` u', 'u.id= o.user_id', 'left')
        ->join(' `order_items` oi', 'o.id= oi.order_id', 'left')
        ->join('product_variants pv', 'pv.id=oi.product_variant_id', 'left')
        ->join('addresses a', 'a.id=o.address_id', 'left')
        ->join('order_charges oc', 'o.id=oc.order_id', 'left')
        ->join('products p', 'pv.product_id=p.id', 'left');

    // if (isset($seller_id) && $seller_id != null) {

    //     $search_res->where("oc.seller_id", $seller_id);
    // }
    if (isset($order_id) && $order_id != null) {
        $search_res->where("o.id", $order_id);
    }
    if (isset($user_id) && $user_id != null) {
        $search_res->where("o.user_id", $user_id);
    }
    if (isset($delivery_boy_id) && $delivery_boy_id != NULL) {
        $search_res->where('oi.delivery_boy_id', $delivery_boy_id);
    }
    if (isset($seller_id) && $seller_id != null) {
        $search_res->group_Start();
        // $where['oi.seller_id'] = $seller_id;
        $search_res->where("oi.seller_id", $seller_id);
        $search_res->or_where("oc.seller_id", $seller_id);
        $search_res->group_End();
    }

    // if (isset($seller_id) && $seller_id != null) {
    //     $where['oi.seller_id'] = $seller_id;
    // }
    if (isset($start_date) && $start_date != null && isset($end_date) && $end_date != null) {
        $search_res->where(" DATE(o.date_added) >= DATE('" . $start_date . "') ");
        $search_res->where(" DATE(o.date_added) <= DATE('" . $end_date . "') ");
    }
    if (isset($order_type) && $order_type != '' && $order_type == 'digital') {
        $search_res->where("p.type = 'digital_product'");
    }
    if (isset($order_type) && $order_type != '' && $order_type == 'simple') {
        $search_res->where("p.type != 'digital_product'");
    }
    if (isset($status) &&  is_array($status) &&  count($status) > 0) {
        $status = array_map('trim', $status);
        $count_res->where_in('oi.active_status', $status);
    }

    if (isset($filters) && !empty($filters)) {
        $search_res->group_Start();
        $search_res->or_like($filters);
        $search_res->group_End();
    }

    if (empty($sort)) {
        // $sort = `o.date_added`;
    }
    $search_res->group_by('o.id');
    $search_res->order_by($sort, $order);
    if ($limit != null || $offset != null) {
        $search_res->limit($limit, $offset);
    }

    $order_details = $search_res->get('`orders` o')->result_array();


    for ($i = 0; $i < count($order_details); $i++) {


        $pr_condition = ($user_id != NULL && !empty(trim($user_id)) && is_numeric($user_id)) ? " and pr.user_id = $user_id " : "";
        $t->db->select('oi.*,p.id as product_id,p.is_cancelable,p.is_prices_inclusive_tax,p.cancelable_till,p.type,p.slug,p.download_allowed,p.download_link,sd.store_name,u.longitude as seller_longitude,u.mobile as seller_mobile,u.address as seller_address,u.latitude as seller_latitude,(select username from users where id=oi.delivery_boy_id) as delivery_boy_name ,sd.store_description,sd.rating as seller_rating,sd.logo as seller_profile,ot.courier_agency,ot.tracking_id,ot.awb_code,ot.url,u.username as seller_name,p.is_returnable,
        pv.special_price,pv.price as main_price,p.image,p.name,p.short_description,p.pickup_location,pv.weight,p.rating as product_rating,p.type,pr.rating as user_rating, pr.images as user_rating_images, pr.comment as user_rating_comment,oi.status as status,
        (Select count(id) from order_items where order_id = oi.order_id ) as order_counter ,
        (Select count(active_status) from order_items where active_status ="cancelled" and order_id = oi.order_id ) as order_cancel_counter , (Select count(active_status) from order_items where active_status ="returned" and order_id = oi.order_id ) as order_return_counter ')
            ->join('product_variants pv', 'pv.id=oi.product_variant_id', 'left')
            ->join('products p', 'pv.product_id=p.id', 'left')
            ->join('product_rating pr', 'pv.product_id=pr.product_id ' . $pr_condition, 'left')
            ->join('seller_data sd', 'sd.user_id=oi.seller_id', 'left')
            ->join('order_tracking ot ', ' ot.order_item_id = oi.id', 'left')
            ->join('users u', 'u.id=oi.seller_id', 'left');

        $t->db->or_where_in('oi.order_id', $order_details[$i]['id']);
        if (isset($seller_id) && $seller_id != null) {
            $t->db->where('oi.seller_id=' . $seller_id);
            $t->db->where("oi.active_status != 'awaiting'");
        }
        if (isset($order_type) && $order_type != '' && $order_type == 'digital') {
            $t->db->where("p.type = 'digital_product'");
        }
        if (isset($order_type) && $order_type != '' && $order_type == 'simple') {
            $t->db->where("p.type != 'digital_product'");
        }
        if (isset($delivery_boy_id) && $delivery_boy_id != null) {
            $t->db->where('oi.delivery_boy_id=' . $delivery_boy_id);
        }
        // if(isset($from_seller) && $from_seller == true){
        //     $t->db->where('oi.active_status !=' , 'cancelled');
        //     $t->db->where('oi.active_status !=' , 'returned');
        // }
        if (isset($status) &&  is_array($status) &&  count($status) > 0) {
            $status = array_map('trim', $status);
            $count_res->where_in('oi.active_status', $status);
        }

        
        /* Added for Cretzo, filters products whose name match the search query */
        if (isset($search_2) and $search_2 != null) {
            $t->db->like('p.name', $search_2); // Filter only order items that match the product name
        }
        
        $t->db->group_by('oi.id');
        $order_item_data = $t->db->get('order_items oi')->result_array();
        
        $order_item_data = output_escaping_new($order_item_data);
        
        
        $return_request = fetch_details('return_requests', ['user_id' => $user_id]);
        if ($order_details[$i]['payment_method'] == "bank_transfer") {
            $bank_transfer = fetch_details('order_bank_transfer', ['order_id' => $order_details[$i]['id']], 'attachments,id,status');
            if (!empty($bank_transfer)) {
                $bank_transfer = array_map(function ($attachment) {
                    $temp['id'] = $attachment['id'];
                    $temp['attachment'] = base_url($attachment['attachments']);
                    $temp['banktransfer_status'] = $attachment['status'];
                    return $temp;
                }, $bank_transfer);
            }
        }
        $order_details[$i]['latitude'] = (isset($order_details[$i]['latitude']) && !empty($order_details[$i]['latitude'])) ? $order_details[$i]['latitude'] : "";
        $order_details[$i]['longitude'] = (isset($order_details[$i]['longitude']) && !empty($order_details[$i]['longitude'])) ? $order_details[$i]['longitude'] : "";
        $order_details[$i]['order_recipient_person'] = (isset($order_details[$i]['order_recipient_person']) && !empty($order_details[$i]['order_recipient_person'])) ? $order_details[$i]['order_recipient_person'] : "";
        $order_details[$i]['attachments'] = (isset($bank_transfer) && !empty($bank_transfer)) ? $bank_transfer : [];
        $order_details[$i]['notes'] = (isset($order_details[$i]['notes']) && !empty($order_details[$i]['notes'])) ? $order_details[$i]['notes'] : "";
        $order_details[$i]['payment_method'] = ($order_details[$i]['payment_method'] == 'bank_transfer') ? ucwords(str_replace('_', " ", $order_details[$i]['payment_method'])) : $order_details[$i]['payment_method'];
        $order_details[$i]['courier_agency'] = "";
        $order_details[$i]['tracking_id'] = "";
        $order_details[$i]['url'] = "";
        
        $address_city = fetch_details('addresses', ['id' => $order_details[$i]['address_id']], 'city_id');
        $city_id = !empty($address_city) ? $address_city[0]['city_id'] : null;
        $order_details[$i]['is_shiprocket_order'] = (isset($city_id) && $city_id == 0) ? 1 : 0;
        
        if (isset($seller_id) && !empty($seller_id)) {
            if (isset($order_details[$i]['seller_delivery_charge'])) {
                $order_details[$i]['delivery_charge'] = $order_details[$i]['seller_delivery_charge'];
            } else {
                $order_details[$i]['delivery_charge'] = $order_details[$i]['delivery_charge'];
            }
        } else {
            $order_details[$i]['delivery_charge'] = $order_details[$i]['delivery_charge'];
        }
        if (isset($order_details[$i]['seller_promo_dicount'])) {
            $order_details[$i]['promo_discount'] = $order_details[$i]['seller_promo_dicount'];
        } else {
            $order_details[$i]['promo_discount'] = $order_details[$i]['promo_discount'];
        }
        
        $returnable_count = 0;
        $cancelable_count = 0;
        $already_returned_count = 0;
        $already_cancelled_count = 0;
        $return_request_submitted_count = 0;
        $total_tax_percent = $total_tax_amount = $item_subtotal = 0;
        $download_allowed = array();
        
        $order_item_data_count = empty($order_item_data) ? 0 : count($order_item_data);
        for ($k = 0; $k < $order_item_data_count; $k++) {

            array_push($download_allowed, $order_item_data[$k]['download_allowed']);
            // $download_allowed = array_values(array_unique(array_column($order_item_data[$k], "download_allowed")));
            
            
            if (isset($order_item_data[$k]['quantity']) && $order_item_data[$k]['quantity'] != 0) {
                $price = $order_item_data[$k]['special_price'] != '' && $order_item_data[$k]['special_price'] != null && is_numeric($order_item_data[$k]['special_price']) && $order_item_data[$k]['special_price'] > 0 && $order_item_data[$k]['special_price'] < $order_item_data[$k]['main_price'] ? $order_item_data[$k]['special_price'] : $order_item_data[$k]['main_price'];
                $quantity = is_numeric($order_item_data[$k]['quantity']) ? (float)$order_item_data[$k]['quantity'] : 0;
                $price = is_numeric($price) ? (float)$price : 0;
                $amount = $quantity * $price;
            }
            if (!empty($order_item_data)) {
                
                $user_rating_images = json_decode($order_item_data[$k]['user_rating_images'], true);
                $order_item_data[$k]['user_rating_images'] = array();
                if (!empty($user_rating_images)) {
                    for ($f = 0; $f < count($user_rating_images); $f++) {
                        $order_item_data[$k]['user_rating_images'][] = base_url($user_rating_images[$f]);
                    }
                }
                // $price_tax_amount = $price * ($order_item_data[$k]['tax_percent'] / 100);
                // Coerced to a float first. order_items.tax_percent is nullable, and
                // output_escaping_new() above runs stripslashes() over every scalar column -
                // which turns a NULL into an EMPTY STRING. "" / 100 is a fatal TypeError on
                // PHP 8 ("Unsupported operand types: string / int"), so a single order item
                // with no tax percentage recorded took down fetch_orders() outright, and with
                // it the order pages, the invoices, the app API and process_refund().
                $tax_percent = is_numeric($order_item_data[$k]['tax_percent']) ? (float) $order_item_data[$k]['tax_percent'] : 0.0;
                if (isset($order_item_data[$k]['is_prices_inclusive_tax']) && $order_item_data[$k]['is_prices_inclusive_tax'] == 1) {
                    $price_tax_amount  = $price - ($price * (100 / (100 + $tax_percent)));
                } else {
                    $price_tax_amount = $price * ($tax_percent / 100);
                }
                $order_item_data[$k]['tax_amount'] = isset($price_tax_amount) && !empty($price_tax_amount) ?  (float)number_format($price_tax_amount, 2) : 0.00;
                $order_item_data[$k]['net_amount'] = $order_item_data[$k]['price'] - $order_item_data[$k]['tax_amount'];
                $item_subtotal += $order_item_data[$k]['sub_total'];
                $order_item_data[$k]['seller_name'] = (!empty($order_item_data[$k]['seller_name'])) ? $order_item_data[$k]['seller_name'] : '';
                $order_item_data[$k]['store_description'] = (!empty($order_item_data[$k]['store_description'])) ? $order_item_data[$k]['store_description'] : '';
                $order_item_data[$k]['seller_rating'] = (!empty($order_item_data[$k]['seller_rating'])) ? number_format($order_item_data[$k]['seller_rating'], 1) : "0";
                $order_item_data[$k]['seller_profile'] = (!empty($order_item_data[$k]['seller_profile'])) ? base_url() . $order_item_data[$k]['seller_profile'] : '';
                $order_item_data[$k]['seller_latitude'] = (isset($order_item_data[$k]['seller_latitude']) && !empty($order_item_data[$k]['seller_latitude'])) ? $order_item_data[$k]['seller_latitude'] : '';
                $order_item_data[$k]['seller_longitude'] = (isset($order_item_data[$k]['seller_longitude']) && !empty($order_item_data[$k]['seller_longitude'])) ? $order_item_data[$k]['seller_longitude'] : '';
                $order_item_data[$k]['seller_address'] = (isset($order_item_data[$k]['seller_address']) && !empty($order_item_data[$k]['seller_address'])) ? $order_item_data[$k]['seller_address'] : '';
                $order_item_data[$k]['seller_mobile'] = (isset($order_item_data[$k]['seller_mobile']) && !empty($order_item_data[$k]['seller_mobile'])) ? $order_item_data[$k]['seller_mobile'] : '';
                
                if (isset($seller_id) && $seller_id != null) {
                    $order_item_data[$k]['otp'] = (get_seller_permission($order_item_data[$k]['seller_id'], "view_order_otp")) ? $order_item_data[$k]['otp'] : "0";
                }
                $order_item_data[$k]['pickup_location'] = isset($order_item_data[$k]['pickup_location']) && !empty($order_item_data[$k]['pickup_location']) && $order_item_data[$k]['pickup_location'] != 'NULL' ? $order_item_data[$k]['pickup_location'] : '';
                $varaint_data = get_variants_values_by_id($order_item_data[$k]['product_variant_id']);
                $order_item_data[$k]['varaint_ids'] = (!empty($varaint_data)) ? $varaint_data[0]['varaint_ids'] : '';
                $order_item_data[$k]['variant_values'] = (!empty($varaint_data)) ? $varaint_data[0]['variant_values'] : '';
                $order_item_data[$k]['attr_name'] = (!empty($varaint_data)) ? $varaint_data[0]['attr_name'] : '';
                
                // Added for cretzo
                $order_item_data[$k]['variant_image'] = (!empty($varaint_data) && !empty($varaint_data[0]['variant_image'])) ? $varaint_data[0]['variant_image'] : '';
                
                $order_item_data[$k]['product_rating'] = (!empty($order_item_data[$k]['product_rating'])) ? number_format($order_item_data[$k]['product_rating'], 1) : "0";
                $order_item_data[$k]['name'] = (!empty($order_item_data[$k]['name'])) ? $order_item_data[$k]['name'] : $order_item_data[$k]['product_name'];
                $order_item_data[$k]['variant_values'] = (!empty($order_item_data[$k]['variant_values'])) ? $order_item_data[$k]['variant_values'] : $order_item_data[$k]['variant_name'];
                $order_item_data[$k]['user_rating'] = (!empty($order_item_data[$k]['user_rating'])) ? $order_item_data[$k]['user_rating'] : '0';
                $order_item_data[$k]['user_rating_comment'] = (!empty($order_item_data[$k]['user_rating_comment'])) ? $order_item_data[$k]['user_rating_comment'] : '';
                $order_item_data[$k]['status'] = json_decode($order_item_data[$k]['status']);
                if (!in_array($order_item_data[$k]['active_status'], ['returned', 'cancelled'])) {
                    // $tax_percent is the numeric-coerced copy made above; the raw column is a
                    // NULL-turned-empty-string here (see the note there) and "int + string" is
                    // just as fatal on PHP 8 as the division was.
                    $total_tax_percent = $total_tax_percent + $tax_percent;
                    // $total_tax_amount  = $total_tax_amount + $order_item_data[$k]['tax_amount'];
                    $item_tax_amount = is_numeric($order_item_data[$k]['tax_amount']) ? (float) $order_item_data[$k]['tax_amount'] : 0.0;
                    $item_quantity = is_numeric($order_item_data[$k]['quantity']) ? (float) $order_item_data[$k]['quantity'] : 0.0;
                    $total_tax_amount  =  $item_tax_amount * $item_quantity;
                }
                $order_item_data[$k]['image_sm'] = (empty($order_item_data[$k]['image']) || file_exists(FCPATH . $order_item_data[$k]['image']) == FALSE) ? base_url(NO_IMAGE) : get_image_url($order_item_data[$k]['image'], 'thumb', 'sm');
                $order_item_data[$k]['image_md'] = (empty($order_item_data[$k]['image']) || file_exists(FCPATH . $order_item_data[$k]['image']) == FALSE) ? base_url(NO_IMAGE) : get_image_url($order_item_data[$k]['image'], 'thumb', 'md');
                $order_item_data[$k]['image'] = (empty($order_item_data[$k]['image']) || file_exists(FCPATH . $order_item_data[$k]['image']) == FALSE) ? base_url(NO_IMAGE) : get_image_url($order_item_data[$k]['image']);
                $order_item_data[$k]['is_already_returned'] =  ($order_item_data[$k]['active_status'] == 'returned') ? '1' : '0';
                $order_item_data[$k]['is_already_cancelled'] = ($order_item_data[$k]['active_status'] == 'cancelled') ? '1' : '0';
                $return_request_key = array_search($order_item_data[$k]['id'], array_column($return_request, 'order_item_id'));
                if ($return_request_key !== false) {
                    $order_item_data[$k]['return_request_submitted'] = $return_request[$return_request_key]['status'];
                    if ($order_item_data[$k]['return_request_submitted'] == '1') {
                        $return_request_submitted_count += $order_item_data[$k]['return_request_submitted'];
                    }
                } else {
                    $order_item_data[$k]['return_request_submitted'] = '';
                    $return_request_submitted_count = null;
                }
                $order_item_data[$k]['courier_agency'] = (isset($order_item_data[$k]['courier_agency']) && !empty($order_item_data[$k]['courier_agency'])) ?  $order_item_data[$k]['courier_agency'] : "";
                $order_item_data[$k]['tracking_id'] = (isset($order_item_data[$k]['tracking_id']) && !empty($order_item_data[$k]['tracking_id'])) ? $order_item_data[$k]['tracking_id'] : "";
                $order_item_data[$k]['url'] = (isset($order_item_data[$k]['url']) && !empty($order_item_data[$k]['url'])) ? $order_item_data[$k]['url'] : "";
                $order_item_data[$k]['shiprocket_order_tracking_url'] = (isset($order_item_data[$k]['awb_code']) && !empty($order_item_data[$k]['awb_code']) && $order_item_data[$k]['awb_code'] != '' && $order_item_data[$k]['awb_code'] != null) ? "https://shiprocket.co/tracking/" . $order_item_data[$k]['awb_code'] : "";
                $order_item_data[$k]['deliver_by'] = (isset($order_item_data[$k]['delivery_boy_name']) && !empty($order_item_data[$k]['delivery_boy_name'])) ? $order_item_data[$k]['delivery_boy_name'] : "";
                $order_item_data[$k]['delivery_boy_id'] = (isset($order_item_data[$k]['delivery_boy_id']) && !empty($order_item_data[$k]['delivery_boy_id'])) ? $order_item_data[$k]['delivery_boy_id'] : "";
                $order_item_data[$k]['discounted_price'] = (isset($order_item_data[$k]['discounted_price']) && !empty($order_item_data[$k]['discounted_price'])) ? $order_item_data[$k]['discounted_price'] : "";
                $order_item_data[$k]['delivery_boy_name'] = (isset($order_item_data[$k]['delivery_boy_name']) && !empty($order_item_data[$k]['delivery_boy_name'])) ? $order_item_data[$k]['delivery_boy_name'] : "";
                if (($order_details[$i]['type'] == 'digital_product' && in_array(0, $download_allowed)) ||  ($order_details[$i]['type'] != 'digital_product' && in_array(0, $download_allowed))) {
                    $order_details[$i]['download_allowed'] = '0';
                    $order_item_data[$k]['download_link'] = '';
                } else {
                    $order_details[$i]['download_allowed'] = '1';
                    $order_item_data[$k]['download_link'] = $order_item_data[$k]['download_link'];
                }
                $order_item_data[$k]['email'] = (isset($order_details[$i]['email']) && !empty($order_details[$i]['email']) ? $order_details[$i]['email'] : '');
                
                $returnable_count += (int) $order_item_data[$k]['is_returnable'];
                $cancelable_count += (int) $order_item_data[$k]['is_cancelable'];
                $already_returned_count += (int) $order_item_data[$k]['is_already_returned'];
                $already_cancelled_count += (int) $order_item_data[$k]['is_already_cancelled'];
                // The delivery date was read from status[3][1] - a hardcoded index assuming the
                // history is always received -> processed -> shipped -> delivered, so that
                // 'delivered' is the fourth entry. It very often isn't: an admin or seller who
                // marks an item delivered without stepping through every intermediate status
                // produces a one-entry history, index 3 doesn't exist, the delivery date comes
                // out NULL and the item is silently NEVER returnable. Every delivered item in
                // this database is in exactly that state, so returns were effectively impossible.
                //
                // delivered_at is the authoritative timestamp (migration 036). Falling back to
                // searching the history by NAME rather than by position covers any row the
                // backfill could not resolve.
                $delivery_date = null;
                if (!empty($order_item_data[$k]['delivered_at'])) {
                    $delivery_date = $order_item_data[$k]['delivered_at'];
                } elseif (!empty($order_item_data[$k]['status']) && is_array($order_item_data[$k]['status'])) {
                    foreach ($order_item_data[$k]['status'] as $history_entry) {
                        if (is_array($history_entry) && isset($history_entry[0], $history_entry[1]) && $history_entry[0] === 'delivered') {
                            $delivery_date = $history_entry[1];
                            break;
                        }
                    }
                }
                $settings = get_settings('system_settings', true);
                $today = date('Y-m-d');
                $return_till = !empty($delivery_date) ? date('Y-m-d', strtotime($delivery_date . ' + ' . $settings['max_product_return_days'] . ' days')) : null;
                // `<=` so the final day of the window is still returnable. With `<`, a 1-day
                // window expired the moment it was granted.
                $order_item_data[$k]['is_returnable'] = (!empty($return_till) && $today <= $return_till) ? '1' : '0';
            }
        }

        
        $order_details[$i]['delivery_time'] = (isset($order_details[$i]['delivery_time']) && !empty($order_details[$i]['delivery_time'])) ? $order_details[$i]['delivery_time'] : "";
        $order_details[$i]['delivery_date'] = (isset($order_details[$i]['delivery_date']) && !empty($order_details[$i]['delivery_date'])) ? $order_details[$i]['delivery_date'] : "";
        // Same `<=` boundary as the per-item flag above, so the order and its items agree on
        // the final day of the window instead of the order saying "not returnable" while the
        // item it contains says it is.
        $order_details[$i]['is_returnable'] = ($returnable_count >= 1 && isset($delivery_date) && !empty($delivery_date) && $today <= $return_till) ? '1' : '0';
        $order_details[$i]['is_cancelable'] = ($cancelable_count >= 1) ? '1' : '0';
        $order_details[$i]['is_already_returned'] = ($already_returned_count == $order_item_data_count) ? '1' : '0';
        $order_details[$i]['is_already_cancelled'] = ($already_cancelled_count == $order_item_data_count) ? '1' : '0';
        if ($return_request_submitted_count == null) {
            $order_details[$i]['return_request_submitted'] = '';
        } else {
            $order_details[$i]['return_request_submitted'] = ($return_request_submitted_count == $order_item_data_count) ? '1' : '0';
        }
        
        if ((isset($delivery_boy_id) && $delivery_boy_id != null) || (isset($seller_id) && $seller_id != null)) {
            
            $order_details[$i]['total'] = strval($item_subtotal);
            
            $order_details[$i]['final_total'] = strval($item_subtotal - $total_tax_amount +  $order_details[$i]['delivery_charge']);
            $order_details[$i]['total_payable'] = strval($item_subtotal +  $order_details[$i]['delivery_charge'] - $order_details[$i]['promo_discount'] -  $order_details[$i]['wallet_balance']);
        } else {
            $order_details[$i]['total'] = strval($order_details[$i]['total']);
        }
        $order_details[$i]['address'] = (isset($order_details[$i]['address']) && !empty($order_details[$i]['address'])) ? output_escaping($order_details[$i]['address']) : "";
        $order_details[$i]['username'] = output_escaping($order_details[$i]['username']);
        $order_details[$i]['country_code'] = (isset($order_details[$i]['country_code']) && !empty($order_details[$i]['country_code'])) ? $order_details[$i]['country_code'] : '';
        $order_details[$i]['total_tax_percent'] = strval($total_tax_percent);
        $order_details[$i]['total_tax_amount'] = strval($total_tax_amount);
        if (isset($seller_id) && $seller_id != null) {
            if ($download_invoice == true || $download_invoice == 1) {
                $order_details[$i]['invoice_html'] =  get_seller_invoice_html($order_details[$i]['id'], $seller_id);
            }
        } else {
            if ($download_invoice == true || $download_invoice == 1) {
                $order_details[$i]['invoice_html'] =  get_invoice_html($order_details[$i]['id']);
            }
        }
        if (!empty($order_item_data)) {
            $order_details[$i]['order_items'] = $order_item_data;
        } else {
            $order_details[$i]['order_items'] =  [];
        }
    }
    
    $order_data['total'] = $total;
    $order_data['order_data'] = array_values($order_details);
    return $order_data;
}

function fetch_order_items($order_item_id = NULL, $user_id = NULL, $status = NULL, $delivery_boy_id = NULL, $limit = NULL, $offset = NULL, $sort = NULL, $order = NULL, $start_date = null, $end_date = null, $search = null, $seller_id = null, $order_id = null)
{
    
    $t = &get_instance();
    $where = [];
    
    $count_res = $t->db->select(' COUNT(o.id) as `total` ')
        ->join(' `users` u', 'u.id= oi.delivery_boy_id', 'left')
        ->join('users us ', ' us.id = oi.seller_id', 'left')
        ->join(' `orders` o', 'o.id= oi.order_id')
        ->join('users un ', ' un.id = o.user_id', 'left')
        ->join('product_variants pv', 'pv.id=oi.product_variant_id', 'left')
        ->join('products p', 'pv.product_id=p.id', 'left')
        ->join('seller_data sd', 'sd.user_id=p.seller_id');
    if (isset($order_item_id) && $order_item_id != null) {
        $where['oi.id'] = $order_item_id;
    }
    if (isset($order_id) && $order_id != null) {
        $where['oi.order_id'] = $order_id;
    }

    if (isset($delivery_boy_id) && $delivery_boy_id != null) {
        $where['oi.delivery_boy_id'] = $delivery_boy_id;
    }

    $where['oi.seller_id'] = $seller_id;

    if (isset($start_date) && $start_date != null && isset($end_date) && $end_date != null) {
        $count_res->where(" DATE(oi.date_added) >= DATE('" . $start_date . "') ");
        $count_res->where(" DATE(oi.date_added) <= DATE('" . $end_date . "') ");
    }

    if (isset($search) and $search != null) {

        $filters = [
            'u.username' => $search,
            'u.email' => $search,
            'oi.id' => $search,
            'p.name' => $search
        ];
    }
    if (isset($filters) && !empty($filters)) {
        $count_res->group_Start();
        $count_res->or_like($filters);
        $count_res->group_End();
    }

    $count_res->where($where);
    if ($sort == 'date_added') {
        $sort = 'oi.date_added';
    }
    $count_res->order_by($sort, $order);

    $order_count = $count_res->get('order_items oi')->result_array();

    $total = "0";
    foreach ($order_count as $row) {
        $total = $row['total'];
    }

    $search_res = $t->db->select('oi.*,p.id as product_id,p.is_cancelable,sd.store_name,p.is_returnable,p.image,p.name,p.type,oi.status as status,(Select count(id) from order_items where order_id = oi.order_id ) as order_counter ,(Select count(active_status) from order_items where active_status ="cancelled" and order_id = oi.order_id ) as order_cancel_counter , (Select count(active_status) from order_items where active_status ="returned" and order_id = oi.order_id ) as order_return_counter ')
        ->join(' `users` u', 'u.id= oi.delivery_boy_id', 'left')
        ->join('users us ', ' us.id = oi.seller_id', 'left')
        ->join(' `orders` o', 'o.id= oi.order_id')
        ->join('users un ', ' un.id = o.user_id', 'left')
        ->join('product_variants pv', 'pv.id=oi.product_variant_id', 'left')
        ->join('products p', 'pv.product_id=p.id', 'left')
        ->join('seller_data sd', 'sd.user_id=p.seller_id');
    $search_res->where($where);
    if (isset($start_date) && $start_date != null && isset($end_date) && $end_date != null) {
        $search_res->where(" DATE(oi.date_added) >= DATE('" . $start_date . "') ");
        $search_res->where(" DATE(oi.date_added) <= DATE('" . $end_date . "') ");
    }
    if (isset($filters) && !empty($filters)) {
        $search_res->group_Start();
        $search_res->or_like($filters);
        $search_res->group_End();
    }
    if (empty($sort)) {
        $sort = `oi.date_added`;
    }
    $search_res->group_by('oi.id');
    $search_res->order_by($sort, $order);
    if ($limit != null || $offset != null) {
        $search_res->limit($limit, $offset);
    }

    $order_item_data = $search_res->get('order_items oi')->result_array();
    for ($k = 0; $k < count($order_item_data); $k++) {

        $multipleWhere = ['seller_id' => $order_item_data[$k]['seller_id'], 'order_id' => $order_item_data[$k]['order_id']];
        $order_charge_data = $t->db->where($multipleWhere)->get('order_charges')->result_array();
        $return_request = fetch_details('return_requests', ['user_id' => $user_id]);
        $order_item_data[$k]['status'] = json_decode($order_item_data[$k]['status']);
        $order_item_data[$k]['delivery_boy_id'] = (isset($order_item_data[$k]['delivery_boy_id']) && !empty($order_item_data[$k]['delivery_boy_id'])) ? $order_item_data[$k]['delivery_boy_id'] : '';
        $order_item_data[$k]['discounted_price'] = (isset($order_item_data[$k]['discounted_price']) && !empty($order_item_data[$k]['discounted_price'])) ? $order_item_data[$k]['discounted_price'] : '';
        $order_item_data[$k]['deliver_by'] = (isset($order_item_data[$k]['deliver_by']) && !empty($order_item_data[$k]['deliver_by'])) ? $order_item_data[$k]['deliver_by'] : '';
        if ($order_item_data[$k]['otp'] != 0) {
            $order_item_data[$k]['otp'] =  $order_item_data[$k]['otp'];
        } else if ($order_charge_data[0]['otp'] != 0) {
            $order_item_data[$k]['otp'] =  $order_charge_data[0]['otp'];
        } else {
            $order_item_data[$k]['otp'] = '';
        }

        for ($j = 0; $j < count($order_item_data[$k]['status']); $j++) {
            $order_item_data[$k]['status'][$j][1] = date('d-m-Y h:i:sa', strtotime($order_item_data[$k]['status'][$j][1]));
        }

        $returnable_count = 0;
        $cancelable_count = 0;
        $already_returned_count = 0;
        $already_cancelled_count = 0;
        $return_request_submitted_count = 0;
        $total_tax_percent = $total_tax_amount = 0;

        $varaint_data = get_variants_values_by_id($order_item_data[$k]['product_variant_id']);
        // varient ids
        $order_item_data[$k]['varaint_ids'] = (!empty($varaint_data)) ? $varaint_data[0]['varaint_ids'] : '';
        $order_item_data[$k]['variant_values'] = (!empty($varaint_data)) ? $varaint_data[0]['variant_values'] : '';
        $order_item_data[$k]['attr_name'] = (!empty($varaint_data)) ? $varaint_data[0]['attr_name'] : '';

        $order_item_data[$k]['name'] = (!empty($order_item_data[$k]['name'])) ? $order_item_data[$k]['name'] : $order_item_data[$k]['product_name'];
        $order_item_data[$k]['variant_values'] = (!empty($order_item_data[$k]['variant_values'])) ? $order_item_data[$k]['variant_values'] : $order_item_data[$k]['variant_name'];

        if (!in_array($order_item_data[$k]['active_status'], ['returned', 'cancelled'])) {
            $total_tax_percent = $total_tax_percent +  $order_item_data[$k]['tax_percent'];
            $total_tax_amount  = $total_tax_amount + $order_item_data[$k]['tax_amount'];
        }

        for ($j = 0; $j < count($order_item_data[$k]['status']); $j++) {
            $order_item_data[$k]['status'][$j][1] = date('d-m-Y h:i:sa', strtotime($order_item_data[$k]['status'][$j][1]));
        }

        $order_item_data[$k]['image_sm'] = (empty($order_item_data[$k]['image']) || file_exists(FCPATH . $order_item_data[$k]['image']) == FALSE) ? base_url(NO_IMAGE) : get_image_url($order_item_data[$k]['image'], 'thumb', 'sm');
        $order_item_data[$k]['image_md'] = (empty($order_item_data[$k]['image']) || file_exists(FCPATH . $order_item_data[$k]['image']) == FALSE) ? base_url(NO_IMAGE) : get_image_url($order_item_data[$k]['image'], 'thumb', 'md');
        $order_item_data[$k]['image'] = (empty($order_item_data[$k]['image']) || file_exists(FCPATH . $order_item_data[$k]['image']) == FALSE) ? base_url(NO_IMAGE) : get_image_url($order_item_data[$k]['image']);
        $order_item_data[$k]['is_already_returned'] =  ($order_item_data[$k]['active_status'] == 'returned') ? '1' : '0';
        $order_item_data[$k]['is_already_cancelled'] = ($order_item_data[$k]['active_status'] == 'cancelled') ? '1' : '0';
        $return_request_key = array_search($order_item_data[$k]['id'], array_column($return_request, 'order_item_id'));
        if ($return_request_key !== false) {
            $order_item_data[$k]['return_request_submitted'] =  $return_request[$return_request_key]['status'];
            if ($order_item_data[$k]['return_request_submitted'] == '1') {
                $return_request_submitted_count += $order_item_data[$k]['return_request_submitted'];
            }
        } else {
            $order_item_data[$k]['return_request_submitted'] = '';
            $return_request_submitted_count = null;
        }

        $returnable_count += (int) $order_item_data[$k]['is_returnable'];
        $cancelable_count += (int) $order_item_data[$k]['is_cancelable'];
        $already_returned_count += (int) $order_item_data[$k]['is_already_returned'];
        $already_cancelled_count += (int) $order_item_data[$k]['is_already_cancelled'];

        $order_details[$k]['is_returnable'] = ($returnable_count >= 1) ? '1' : '0';
        $order_details[$k]['is_cancelable'] = ($cancelable_count >= 1) ? '1' : '0';
        $order_details[$k]['is_already_returned'] = ($already_returned_count == count($order_item_data)) ? '1' : '0';
        $order_details[$k]['is_already_cancelled'] = ($already_cancelled_count == count($order_item_data)) ? '1' : '0';
        if ($return_request_submitted_count == null) {
            $order_details[$k]['return_request_submitted'] = null;
        } else {
            $order_details[$k]['return_request_submitted'] = ($return_request_submitted_count == count($order_item_data)) ? '1' : '0';
        }
        $order_details[$k]['username'] = output_escaping($order_details[$k]['username']);
        $order_details[$k]['total_tax_percent'] = strval($total_tax_percent);
        $order_details[$k]['total_tax_amount'] = strval($total_tax_amount);
    }

    $order_data['total'] = $total;
    $order_data['order_data'] = (!empty($order_item_data)) ? array_values($order_item_data) : [];
    return $order_data;
}

function find_media_type($extenstion)
{
    $t = &get_instance();
    $t->config->load('eshop');
    $type = $t->config->item('type');
    foreach ($type as $main_type => $extenstions) {
        foreach ($extenstions['types'] as $k => $v) {
            if ($v === strtolower($extenstion)) {
                return array($main_type, $extenstions['icon']);
            }
        }
    }
    return false;
}

function formatBytes($size, $precision = 2)
{
    $base = log($size, 1024);
    $suffixes = array('', 'KB', 'MB', 'GB', 'TB');

    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

function delete_images($subdirectory, $image_name)
{
    $image_types = ['thumb-md/', 'thumb-sm/', 'cropped-md/', 'cropped-sm/'];
    $main_dir = FCPATH . $subdirectory;

    foreach ($image_types as $types) {
        $path = $main_dir . $types . $image_name;
        if (file_exists($path)) {
            unlink($path);
        }
    }

    if (file_exists($main_dir . $image_name)) {
        unlink($main_dir . $image_name);
    }
}

function get_image_url($path, $image_type = '', $image_size = '', $file_type = 'image')
{
    $path = explode('/', (string)$path);
    $subdirectory = '';
    for ($i = 0; $i < count($path) - 1; $i++) {
        $subdirectory .= $path[$i] . '/';
    }
    $image_name = end($path);

    $file_main_dir = FCPATH . $subdirectory;
    $image_main_dir = base_url() . $subdirectory;
    if ($file_type == 'image') {
        $types = ['thumb', 'cropped'];
        $sizes = ['md', 'sm'];
        if (in_array(trim(strtolower($image_type)), $types) &&  in_array(trim(strtolower($image_size)), $sizes)) {
            $filepath = $file_main_dir . $image_type . '-' . $image_size . '/' . $image_name;
            $imagepath = $image_main_dir . $image_type . '-' . $image_size . '/' . $image_name;
            if (file_exists($filepath)) {
                return  $imagepath;
            } else if (file_exists($file_main_dir . $image_name)) {
                return  $image_main_dir . $image_name;
            } else {
                return  base_url() . NO_IMAGE;
            }
        } else {
            if (file_exists($file_main_dir . $image_name)) {
                return  $image_main_dir . $image_name;
            } else {
                return  base_url() . NO_IMAGE;
            }
        }
    } else {
        $file = new SplFileInfo($file_main_dir . $image_name);
        $ext  = $file->getExtension();

        $media_data =  find_media_type($ext);
        $image_placeholder = $media_data[1];
        $filepath = FCPATH .  $image_placeholder;
        $extensionpath = base_url() .  $image_placeholder;
        if (file_exists($filepath)) {
            return  $extensionpath;
        } else {
            return  base_url() . NO_IMAGE;
        }
    }
}

function fetch_users($id)
{
    $t = &get_instance();
    // Every one of the 16 callers of this function was returning a hard SQL error 1054
    // ("Unknown column 'c.name' in 'field list'"), which meant an outright 500 on the admin
    // ticket-reply endpoint, the app's add_ticket / edit_ticket / send_message endpoints and
    // the seller auth flow among others. Two separate mistakes:
    //   - the `cities` table's columns are city_id / city_name; there is no `cities.name`.
    //   - users.city / users.area store the numeric ID of the row, not its name, so joining
    //     on the name column matched nothing even once the column name was right.
    $user_details = $t->db->select('u.id,username,email,mobile,balance,dob, referral_code, friends_code, c.city_name as cities,a.name as area,street,pincode')
        ->join('areas a', 'u.area = a.id', 'left')
        ->join('cities c', 'u.city = c.city_id', 'left')
        ->where('u.id', $id)->get('users u')
        ->result_array();
    return $user_details;
}

function escape_array($array)
{
    $t = &get_instance();
    $posts = array();
    if (!empty($array)) {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $posts[$key] = $t->db->escape_str($value ?? '');
            }
        } else {
            return $t->db->escape_str($array);
        }
    }
    return $posts;
}

function allowed_media_types()
{
    $t = &get_instance();
    $t->config->load('eshop');
    $type = $t->config->item('type');
    $general = [];
    foreach ($type as $main_type => $extenstions) {
        $general = array_merge_recursive($general, $extenstions['types']);
    }
    return $general;
}

function get_current_version()
{
    $t = &get_instance();
    $version = $t->db->select('max(version) as version')->get('updates')->result_array();
    return $version[0]['version'];
}

function resize_review_images($image_data, $source_path, $id = false)
{
    if ($image_data['is_image']) {

        $t = &get_instance();

        $target_path = $source_path; // Target path will be under source path        
        $image_name = $image_data['file_name']; // original image's name    
        $w = $image_data['image_width']; // original image's width    
        $h = $image_data['image_height']; // original images's height 

        $t->load->library('image_lib');

        if (file_exists($source_path . $image_name)) {  //check if the image file exist 

            if (!file_exists($target_path)) {
                mkdir($target_path, 0777);
            }

            $n_w = 800;
            $n_h = 800;
            $config['image_library'] = 'gd2';
            $config['create_thumb'] = FALSE;
            $config['maintain_ratio'] = TRUE;
            $config['quality'] = '90%';
            $config['source_image'] =  $source_path . $image_name;
            $config['new_image'] = $target_path . $image_name;
            $config['width'] = $n_w;
            $config['height'] = $n_h;
            $t->image_lib->clear();
            $t->image_lib->initialize($config);
            if (!$t->image_lib->resize()) {
                return $t->image_lib->display_errors();
            }
        }
    }
}

function get_invoice_html($order_id)
{
    $t = &get_instance();
    $invoice_generated_html = '';
    $t->data['main_page'] = VIEW . 'api-order-invoice';
    $settings = get_settings('system_settings', true);
    $t->data['title'] = 'Invoice Management |' . $settings['app_name'];
    $t->data['meta_description'] = 'Ekart | Invoice Management';
    if (isset($order_id) && !empty($order_id)) {
        $res = $t->Order_model->get_order_details(['o.id' => $order_id], true);
        if (!empty($res)) {
            $items = [];
            $promo_code = [];
            if (!empty($res[0]['promo_code'])) {
                $promo_code = fetch_details('promo_codes', ['promo_code' => trim($res[0]['promo_code'])]);
            }
            foreach ($res as $row) {
                $row = output_escaping($row);
                $temp['product_id'] = $row['product_id'];
                $temp['seller_id'] = $row['seller_id'];
                $temp['product_variant_id'] = $row['product_variant_id'];
                $temp['pname'] = $row['pname'];
                $temp['quantity'] = $row['quantity'];
                $temp['discounted_price'] = $row['discounted_price'];
                $temp['tax_percent'] = $row['tax_percent'];
                $temp['tax_amount'] = $row['tax_amount'];
                $temp['price'] = $row['price'];
                $temp['delivery_boy'] = $row['delivery_boy'];
                $temp['active_status'] = $row['oi_active_status'];
                $temp['is_prices_inclusive_tax'] = $row['is_prices_inclusive_tax'];
                array_push($items, $temp);
            }
            $t->data['order_detls'] = $res;
            $t->data['items'] = $items;
            $t->data['promo_code'] = $promo_code;
            $t->data['settings'] = get_settings('system_settings', true);
            $invoice_generated_html = $t->load->view('admin/invoice-template', $t->data, TRUE);
        } else {
            $invoice_generated_html = '';
        }
    } else {
        $invoice_generated_html = '';
    }
    return $invoice_generated_html;
}

function get_seller_invoice_html($order_id, $seller_id)
{
    $t = &get_instance();
    $invoice_generated_html = '';
    $t->data['main_page'] = VIEW . 'api-order-invoice';
    $settings = get_settings('system_settings', true);
    $t->data['title'] = 'Invoice Management |' . $settings['app_name'];
    $t->data['meta_description'] = 'Ekart | Invoice Management';
    if (isset($order_id) && !empty($order_id) && isset($seller_id) && !empty($seller_id)) {
        $s_user_data = fetch_details('users', ['id' => $seller_id], 'email,mobile,address,country_code');
        $seller_data = fetch_details('seller_data', ['user_id' => $seller_id], 'store_name,pan_number,tax_name,tax_number');
        $res = $t->order_model->get_order_details(['o.id' => $order_id, 'oi.seller_id' => $seller_id], true);
        if (!empty($res)) {
            $items = [];
            $promo_code = [];
            if (!empty($res[0]['promo_code'])) {
                $promo_code = fetch_details('promo_codes', ['promo_code' => trim($res[0]['promo_code'])]);
            }
            foreach ($res as $row) {
                $row = output_escaping($row);
                $temp['product_id'] = $row['product_id'];
                $temp['product_variant_id'] = $row['product_variant_id'];
                $temp['pname'] = $row['pname'];
                $temp['quantity'] = $row['quantity'];
                $temp['discounted_price'] = $row['discounted_price'];
                $temp['tax_percent'] = $row['tax_percent'];
                $temp['tax_amount'] = $row['tax_amount'];
                $temp['price'] = $row['price'];
                $temp['delivery_boy'] = $row['delivery_boy'];
                $temp['active_status'] = $row['oi_active_status'];
                array_push($items, $temp);
            }
            $t->data['order_detls'] = $res;
            $t->data['items'] = $items;
            $t->data['s_user_data'] = $s_user_data;
            $t->data['seller_data'] = $seller_data;
            $t->data['promo_code'] = $promo_code;
            $t->data['settings'] = get_settings('system_settings', true);
            $invoice_generated_html = $t->load->view('seller/invoice-template', $t->data, TRUE);
        } else {
            $invoice_generated_html = '';
        }
    } else {
        $invoice_generated_html = '';
    }
    return $invoice_generated_html;
}

function is_modification_allowed($module)
{
    // Demo-mode write restriction is controlled solely by IS_ALLOWED_MODIFICATION,
    // not by which account is logged in (see MyConfig::allow_modification() for the
    // matching fix - this was the same hardcoded-mobile-number bypass duplicated here).
    $allow_modification = (IS_ALLOWED_MODIFICATION == 0) ? 0 : 1;
    $excluded_modules = ['orders'];
    if (isset($allow_modification) && $allow_modification == 0) {
        if (!in_array(strtolower($module), $excluded_modules)) {
            return false;
        }
    }
    return true;
}
function output_escaping($array)
{
    $exclude_fields = ["images", "other_images"];
    $t = &get_instance();

    if (!empty($array)) {
        if (is_array($array)) {
            $data = array();
            foreach ($array as $key => $value) {
                if (!in_array($key, $exclude_fields)) {
                    $data[$key] = stripcslashes((string)$value);
                } else {
                    $data[$key] = $value;
                }
            }
            return $data;
        } else if (is_object($array)) {
            $data = new stdClass();
            foreach ($array as $key => $value) {
                if (!in_array($key, $exclude_fields)) {
                    $data->$key = stripcslashes($value);
                } else {
                    $data[$key] = $value;
                }
            }
            return $data;
        } else {
            return stripcslashes($array);
        }
    }
}
function get_min_max_price_of_product($product_id = '')
{
    $t = &get_instance();
    /*
     * PERFORMANCE: served from the batch prefetch when one is open. Note the miss
     * path is preserved verbatim, including the no-argument form (which prices the
     * whole catalogue at once and is never prefetched).
     */
    $response = !empty($product_id) ? product_batch_get('minmax', $product_id) : null;
    if ($response === null) {
        $t->db->join('`product_variants` pv', 'p.id = pv.product_id')->join('`taxes` tax', 'tax.id = p.tax', 'LEFT');
        if (!empty($product_id)) {
            $t->db->where('p.id', $product_id);
        }
        $response = $t->db->select('is_prices_inclusive_tax,price,special_price,tax.percentage as tax_percentage')->get('products p')->result_array();
    }

    /*
     * The joins above are INNER joins onto product_variants, so a product with no variant rows
     * yields an empty $response - and min(array_column([], 'price')) is a fatal
     *   ValueError: min(): Argument #1 ($value) must contain at least one element
     * on PHP 8 (it was merely a warning returning false on PHP 7, which is why this survived).
     * 13 ACTIVE products on this database have no variant rows, so any page that priced one of
     * them returned a 500 with no explanation.
     */
    if (empty($response)) {
        return [
            'min_price'              => 0,
            'max_price'              => 0,
            'special_price'          => 0,
            'max_special_price'      => 0,
            'discount_in_percentage' => 0,
        ];
    }

    $percentage = (isset($response[0]['tax_percentage']) && intval($response[0]['tax_percentage']) > 0 && $response[0]['tax_percentage'] != null) ? $response[0]['tax_percentage'] : '0';
    if ((isset($response[0]['is_prices_inclusive_tax']) && $response[0]['is_prices_inclusive_tax'] == 0) || (!isset($response[0]['is_prices_inclusive_tax'])) && $percentage > 0) {
        $price_tax_amount = $response[0]['price'] * ($percentage / 100);
        $special_price_tax_amount = $response[0]['special_price'] * ($percentage / 100);
    } else {
        $price_tax_amount = 0;
        $special_price_tax_amount = 0;
    }
    $data['min_price'] = min(array_column($response, 'price')) + $price_tax_amount;
    $data['max_price'] = max(array_column($response, 'price')) + $price_tax_amount;
    $data['special_price'] = min(array_column($response, 'special_price')) + $special_price_tax_amount;
    $data['max_special_price'] = max(array_column($response, 'special_price')) + $special_price_tax_amount;
    $data['discount_in_percentage'] = find_discount_in_percentage($data['special_price'] + $special_price_tax_amount, $data['min_price'] + $price_tax_amount);
    return $data;
}
function get_price_range_of_product($product_id = '')
{
    $system_settings = get_settings('system_settings', true);
    $currency = (isset($system_settings['currency']) && !empty($system_settings['currency'])) ? $system_settings['currency'] : '';
    $t = &get_instance();
    $t->db->join('`product_variants` pv', 'p.id = pv.product_id')->join('`taxes` tax', 'tax.id = p.tax', 'LEFT');
    if (!empty($product_id)) {
        $t->db->where('p.id', $product_id);
    }
    $response = $t->db->select('is_prices_inclusive_tax,price,special_price,tax.percentage as tax_percentage')->get('products p')->result_array();

    // Same empty-variant-set fatal as get_min_max_price_of_product(): with no rows, the else
    // branch's for-loop never runs and $data['range'] is never assigned, so the caller reads an
    // undefined index; and where the loop DOES run on an empty column it throws on min().
    if (empty($response)) {
        $data['range'] = $currency . "<small style='font-size: 20px;'>" . number_format(0, 2) . "</small>";
        return $data;
    }

    if (count($response) == 1) {
        $percentage = (isset($response[0]['tax_percentage']) && intval($response[0]['tax_percentage']) > 0 && $response[0]['tax_percentage'] != null) ? $response[0]['tax_percentage'] : '0';
        if ((isset($response[0]['is_prices_inclusive_tax']) && $response[0]['is_prices_inclusive_tax'] == 0) || (!isset($response[0]['is_prices_inclusive_tax'])) && $percentage > 0) {
            $price_tax_amount = $response[0]['price'] * ($percentage / 100);
            $special_price_tax_amount = $response[0]['special_price'] * ($percentage / 100);
        } else {
            $price_tax_amount = 0;
            $special_price_tax_amount = 0;
        }
        $price_tax_amount = $price_tax_amount;
        $special_price_tax_amount = $special_price_tax_amount;
        $price = $response[0]['special_price'] == 0 ? $response[0]['price'] + $price_tax_amount : $response[0]['special_price'] + $special_price_tax_amount;
        $data['range'] =  $currency . "<small style='font-size: 20px;'>" . number_format($price, 2) . "</small>";
    } else {
        for ($i = 0; $i < count($response); $i++) {
            $is_all_specical_price_zero = 1;
            if ($response[$i]['special_price'] != 0) {
                $is_all_specical_price_zero = 0;
            }

            if ($is_all_specical_price_zero == 1) {
                $min = min(array_column($response, 'price'));
                $max = max(array_column($response, 'price'));
                $percentage = (isset($response[$i]['tax_percentage']) && intval($response[$i]['tax_percentage']) > 0 && $response[$i]['tax_percentage'] != null) ? $response[$i]['tax_percentage'] : '0';
                if ((isset($response[$i]['is_prices_inclusive_tax']) && $response[$i]['is_prices_inclusive_tax'] == 0) || (!isset($response[$i]['is_prices_inclusive_tax'])) && $percentage > 0) {
                    $min_price_tax_amount = $min * ($percentage / 100);
                    $min = $min + $min_price_tax_amount;

                    $max_price_tax_amount = $max * ($percentage / 100);
                    $max = $max + $max_price_tax_amount;
                }

                $data['range'] = $currency . "<small style='font-size: 20px;'>" . number_format($min, 2) . "</small>" . ' - ' . $currency . "<small style='font-size: 20px;'>" . number_format($max, 2) . "</small>";
            } else {

                $min_special_price = array_column($response, 'special_price');
                for ($j = 0; $j < count($min_special_price); $j++) {
                    if ($min_special_price[$j] == 0) {
                        unset($min_special_price[$j]);
                    }
                }
                // Every special_price could have been unset by the loop above (this branch is
                // reached when ANY variant has a special price, but the zero-stripping runs over
                // ALL of them) - min([]) is fatal on PHP 8. Fall back to the list price.
                $min_special_price = !empty($min_special_price)
                    ? min($min_special_price)
                    : min(array_column($response, 'price'));
                $max = max(array_column($response, 'price'));
                $percentage = (isset($response[$i]['tax_percentage']) && intval($response[$i]['tax_percentage']) > 0 && $response[$i]['tax_percentage'] != null) ? $response[$i]['tax_percentage'] : '0';
                if ((isset($response[$i]['is_prices_inclusive_tax']) && $response[$i]['is_prices_inclusive_tax'] == 0) || (!isset($response[$i]['is_prices_inclusive_tax'])) && $percentage > 0) {
                    $min_price_tax_amount = $min_special_price * ($percentage / 100);
                    $min_special_price = $min_special_price + $min_price_tax_amount;
                    $max_price_tax_amount = $max * ($percentage / 100);
                    $max = $max + $max_price_tax_amount;
                }
                $data['range'] = $currency . "<small style='font-size: 20px;'>" . number_format($min_special_price, 2) . "</small>" . ' - ' . $currency . "<small style='font-size: 20px;'>" . number_format($max, 2) . "</small>";
            }
        }
    }

    return $data;
}
function find_discount_in_percentage($special_price, $price)
{
    $diff_amount = $price - $special_price;
    if ($diff_amount > 0) {
        return intval(($diff_amount * 100) / $price);
    }
}
function get_attribute_ids_by_value($values, $names)
{
    $t = &get_instance();
    $names = str_replace('-', ' ', $names);
    $attribute_ids = $t->db->select("av.id")
        ->join('attributes a ', 'av.attribute_id = a.id ')
        ->where_in('av.value', $values)
        ->where_in('a.name', $names)
        ->get('attribute_values av')->result_array();
    return array_column($attribute_ids, 'id');
}

function insert_details($data, $table)
{
    $t = &get_instance();
    return $t->db->insert($table, $data);
}

function get_category_id_by_slug($slug)
{
    $t = &get_instance();
    $slug = urldecode($slug);
    // Try exact match first
    $res = $t->db->select('id')
        ->where('slug', $slug)
        ->get('categories')->row_array();

    // Fallback: try rawurldecode (in case of different encoding)
    if (empty($res)) {
        $alt = rawurldecode($slug);
        if ($alt !== $slug) {
            $res = $t->db->select('id')->where('slug', $alt)->get('categories')->row_array();
        }
    }

    // Fallback: case-insensitive match
    if (empty($res)) {
        $res = $t->db->select('id')->where("LOWER(slug) = " . $t->db->escape(strtolower($slug)))->get('categories')->row_array();
    }

    if (!empty($res) && isset($res['id'])) {
        return $res['id'];
    }

    // Log missing slug for production debugging
    log_message('error', "get_category_id_by_slug: slug not found [$slug] on " . current_url());
    return null;
}

function get_variant_attributes($product_id)
{
    $product = fetch_product(NULL, NULL, $product_id);
    if (!empty($product['product'][0]['variants']) && isset($product['product'][0]['variants'])) {
        $attributes_array = explode(',', $product['product'][0]['variants'][0]['attr_name']);
        $variant_attributes = [];
        foreach ($attributes_array as $attribute) {
            $attribute = trim($attribute);

            $key = array_search($attribute, array_column($product['product'][0]['attributes'], 'name'), false);
            if ($key === 0 || !empty(strval($key))) {
                $variant_attributes[$key]['ids'] = $product['product'][0]['attributes'][$key]['ids'];
                $variant_attributes[$key]['values'] = $product['product'][0]['attributes'][$key]['value'];
                $variant_attributes[$key]['attr_name'] = $attribute;
            }
        }
        return $variant_attributes;
    }
}

function get_product_variant_details($product_variant_id)
{
    $CI = &get_instance();
    $res = $CI->db->join('products p', 'p.id=pv.product_id')
        ->where('pv.id', $product_variant_id)
        ->select('p.name,p.id,p.image,p.short_description,pv.*')->get('product_variants pv')->result_array();

    if (!empty($res)) {
        $res = array_map(function ($d) {
            $d['image_sm'] = get_image_url($d['image'], 'sm');
            $d['image_md'] = get_image_url($d['image'], 'md');
            $d['image'] = get_image_url($d['image']);
            return $d;
        }, $res);
    } else {
        return null;
    }
    return $res[0];
}

function get_cities($id = NULL, $limit = NULL, $offset = NULL)
{
    $CI = &get_instance();
    if (!empty($limit) || !empty($offset)) {
        $CI->db->limit($limit, $offset);
    }
    return $CI->db->get('cities')->result_array();
}

function get_favorites($user_id, $limit = NULL, $offset = NULL, $return_count = NULL)
{
    $CI = &get_instance();
    $CI->db->join('products p', 'p.id=f.product_id')
        ->where('f.user_id', $user_id);

    if (!empty($return_count)) {
        return $CI->db->count_all_results('favorites f');
    }

    if (!empty($limit) || !empty($offset)) {
        $CI->db->limit($limit, $offset);
    }
    $res = $CI->db->select('p.*')
        ->order_by('f.id', "DESC")
        ->get('favorites f')->result_array();

    $res = array_map(function ($d) {
        $d['image_md'] = get_image_url($d['image'], 'thumb', 'md');
        $d['image_sm'] = get_image_url($d['image'], 'thumb', 'sm');
        $d['image'] = get_image_url($d['image']);
        $d['variants'] = get_variants_values_by_pid($d['id']);
        $d['min_max_price'] = get_min_max_price_of_product($d['id']);
        return $d;
    }, $res);
    return $res;
}
function current_theme($id = '', $name = '', $slug = '', $is_default = 1, $status = '')
{
    //If don't pass any params then this function will return the current theme.
    $CI = &get_instance();
    if (!empty($id)) {
        $CI->db->where('id', $id);
    }
    if (!empty($name)) {
        $CI->db->where('name', $name);
    }
    if (!empty($slug)) {
        $CI->db->where('slug', $slug);
    }
    if (!empty($is_default)) {
        $CI->db->where('is_default', $is_default);
    }
    if (!empty($status)) {
        $CI->db->where('status', $status);
    }
    $res = $CI->db->get('themes')->result_array();
    $res = array_map(function ($d) {
        $d['image'] = base_url('assets/front_end/theme-images/' . $d['image']);
        return $d;
    }, $res);
    return $res;
}
function get_languages($id = '', $language_name = '', $code = '', $is_rtl = '')
{
    /*
     * PERFORMANCE: called three times while rendering a single page - once by the
     * header for the language switcher, once by imp-inputs.php and once by
     * template.php to resolve the RTL flag - and again on any page that includes
     * the chat partial. The `languages` table is written only by the admin
     * Language form, so it is effectively static at runtime.
     *
     * Keyed on the arguments because the callers pass different filters and each
     * combination is its own result set. Busted by Language_model on add/edit; the
     * hour-long TTL is only a backstop.
     */
    $cache_key = 'languages.' . md5(serialize(array($id, $language_name, $code, $is_rtl)));
    $cached = app_cache_get($cache_key);
    if ($cached !== null) {
        return $cached;
    }

    $CI = &get_instance();
    if (!empty($id)) {
        $CI->db->where('id', $id);
    }
    if (!empty($language_name)) {
        $CI->db->where('language', $language_name);
    }
    if (!empty($code)) {
        $CI->db->where('code', $code);
    }
    if (!empty($is_rtl)) {
        $CI->db->where('is_rtl', $is_rtl);
    }
    $res = $CI->db->get('languages')->result_array();
    app_cache_set($cache_key, $res, 3600);
    return $res;
}

/**
 * Is the language the visitor is currently browsing in a right-to-left one?
 *
 * The front-end template works this out inline and hands the result to include-css as
 * $is_rtl, but include-css / include-script are also pulled in by pages that render
 * outside that template (pages/floating_chat.php), where $is_rtl does not exist. Those
 * files call this instead of reading an undefined variable.
 */
function is_rtl_language()
{
    static $is_rtl = null;
    if ($is_rtl !== null) {
        return $is_rtl;
    }

    $CI = &get_instance();
    $CI->load->helper('cookie');
    $lang = $CI->input->cookie('language', TRUE);
    if (empty($lang)) {
        /* fall back to the site's default language */
        $lang = $CI->config->item('language');
    }

    $language = get_languages(0, $lang, 0, 1);
    $is_rtl = !empty($language);
    return $is_rtl;
}

function verify_payment_transaction($txn_id, $payment_method, $additional_data = [])
{
    if (empty(trim($txn_id))) {
        $response['error'] = true;
        $response['message'] = "Transaction ID is required";
        return $response;
    }

    $CI = &get_instance();
    $CI->config->load('eshop');
    $supported_methods = $CI->config->item('supported_payment_methods');

    if (empty(trim($payment_method)) || !in_array($payment_method, $supported_methods)) {
        $response['error'] = true;
        $response['message'] = "Invalid payment method supplied";
        return $response;
    }
    switch ($payment_method) {
        case 'razorpay':
            $CI->load->library("razorpay");
            $payment = $CI->razorpay->fetch_payments($txn_id);
            if (!empty($payment) && isset($payment['status'])) {
                if ($payment['status'] == 'authorized') {

                    /* if the payment is authorized try to capture it using the API */
                    $capture_response = $CI->razorpay->capture_payment($payment['amount'], $txn_id, $payment['currency']);
                    if ($capture_response['status'] == 'captured') {
                        $response['error'] = false;
                        $response['message'] = "Payment captured successfully";
                        $response['amount'] = $capture_response['amount'] / 100;
                        $response['data'] = $capture_response;
                        return $response;
                    } else if ($capture_response['status'] == 'refunded') {
                        $response['error'] = true;
                        $response['message'] = "Payment is refunded.";
                        $response['amount'] = $capture_response['amount'] / 100;
                        $response['data'] = $capture_response;
                        return $response;
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Payment could not be captured.";
                        $response['amount'] = (isset($capture_response['amount'])) ? $capture_response['amount'] / 100 : 0;
                        $response['data'] = $capture_response;
                        return $response;
                    }
                } else if ($payment['status'] == 'captured') {
                    $response['error'] = false;
                    $response['message'] = "Payment captured successfully";
                    $response['amount'] = $payment['amount'] / 100;
                    $response['data'] = $payment;
                    return $response;
                } else if ($payment['status'] == 'created') {
                    $response['error'] = true;
                    $response['message'] = "Payment is just created and yet not authorized / captured!";
                    $response['amount'] = $payment['amount'] / 100;
                    $response['data'] = $payment;
                    return $response;
                } else {
                    $response['error'] = true;
                    $response['message'] = "Payment is " . ucwords($payment['status']) . "! ";
                    $response['amount'] = (isset($payment['amount'])) ? $payment['amount'] / 100 : 0;
                    $response['data'] = $payment;
                    return $response;
                }
            } else {
                $response['error'] = true;
                $response['message'] = "Payment not found by the transaction ID!";
                $response['amount'] = 0;
                $response['data'] = [];
                return $response;
            }
            break;
        case 'paystack':
            $CI->load->library("paystack");
            $payment = $CI->paystack->verify_transation($txn_id);
            if (!empty($payment)) {
                $payment = json_decode($payment, true);
                if (isset($payment['data']['status']) && $payment['data']['status'] == 'success') {
                    $response['error'] = false;
                    $response['message'] = "Payment is successful";
                    $response['amount'] = (isset($payment['data']['amount'])) ? $payment['data']['amount'] / 100 : 0;
                    $response['data'] = $payment;
                    return $response;
                } elseif (isset($payment['data']['status']) && $payment['data']['status'] != 'success') {
                    $response['error'] = true;
                    $response['message'] = "Payment is " . ucwords($payment['data']['status']) . "! ";
                    $response['amount'] = (isset($payment['data']['amount'])) ? $payment['data']['amount'] / 100 : 0;
                    $response['data'] = $payment;
                    return $response;
                } else {
                    $response['error'] = true;
                    $response['message'] = "Payment is unsuccessful! ";
                    $response['amount'] = (isset($payment['data']['amount'])) ? $payment['data']['amount'] / 100 : 0;
                    $response['data'] = $payment;
                    return $response;
                }
            } else {
                $response['error'] = true;
                $response['message'] = "Payment not found by the transaction ID!";
                $response['amount'] = 0;
                $response['data'] = [];
                return $response;
            }
            break;

        case 'instamojo':
            $CI->load->library("instamojo");
            $payment = $CI->instamojo->payment_requests_detail($txn_id);
            if (!empty($payment)) {
                $payment = json_decode($payment['body'], true);

                if (isset($payment['status']) && ($payment['status'] == 'Completed' || $payment['status'] == 'completed')) {
                    $response['error'] = false;
                    $response['message'] = "Payment is successful";
                    $response['amount'] = (isset($payment['amount'])) ? $payment['amount'] : 0;
                    $response['data'] = $payment;
                    return $response;
                } elseif (isset($payment['status']) && $payment['status'] != 'success') {
                    $response['error'] = true;
                    $response['message'] = "Payment is " . ucwords($payment['status']) . "! ";
                    $response['amount'] = (isset($payment['amount'])) ? $payment['amount'] : 0;
                    $response['data'] = $payment;
                    return $response;
                } else {
                    $response['error'] = true;
                    $response['message'] = "Payment is unsuccessful! ";
                    $response['amount'] = (isset($payment['amount'])) ? $payment['amount'] : 0;
                    $response['data'] = $payment;
                    return $response;
                }
            } else {
                $response['error'] = true;
                $response['message'] = "Payment not found by the transaction ID!";
                $response['amount'] = 0;
                $response['data'] = [];
                return $response;
            }
            break;
        case 'flutterwave':
            $CI->load->library("flutterwave");
            $transaction = $CI->flutterwave->verify_transaction($txn_id);
            if (!empty($transaction)) {
                $transaction = json_decode($transaction, true);
                if ($transaction['status'] == 'error') {
                    $response['error'] = true;
                    $response['message'] = $transaction['message'];
                    $response['amount'] = (isset($transaction['data']['amount'])) ? $transaction['data']['amount'] : 0;
                    $response['data'] = $transaction;
                    return $response;
                }

                if ($transaction['status'] == 'success' && $transaction['data']['status'] == 'successful') {
                    $response['error'] = false;
                    $response['message'] = "Payment has been completed successfully";
                    $response['amount'] = $transaction['data']['amount'];
                    $response['data'] = $transaction;
                    return $response;
                } else if ($transaction['status'] == 'success' && $transaction['data']['status'] != 'successful') {
                    $response['error'] = true;
                    $response['message'] = "Payment is " . $transaction['data']['status'];
                    $response['amount'] = $transaction['data']['amount'];
                    $response['data'] = $transaction;
                    return $response;
                }
            } else {
                $response['error'] = true;
                $response['message'] = "Payment not found by the transaction ID!";
                $response['amount'] = 0;
                $response['data'] = [];
                return $response;
            }
            break;

        case 'stripe':
            # code...
            return "stripe is supplied";
            break;

        case 'phonepe':
            $CI->load->library("phonepe");
            $transaction = $CI->phonepe->check_status($txn_id);
            $status = $transaction['code'];
            if (!empty($transaction)) {
                if ($status == 'PAYMENT_SUCCESS') {
                    $response['error'] = false;
                    $response['message'] = "Payment has been completed successfully";
                    $response['amount'] = $transaction['data']['amount'];
                    $response['data'] = $transaction;
                    return $response;
                } elseif ($status == "BAD_REQUEST"  || $status == "AUTHORIZATION_FAILED" || $status == "PAYMENT_ERROR" || $status == "TRANSACTION_NOT_FOUND" || $status == "PAYMENT_DECLINED" || $status == "TIMED_OUT") {
                    $response['error'] = true;
                    $response['message'] = $transaction['message'];
                    $response['amount'] = (isset($transaction['data']['amount'])) ? $transaction['data']['amount'] : 0;
                    $response['data'] = $transaction;
                    return $response;
                } else {
                    $response['error'] = true;
                    $response['message'] = "Internal error occurred please try again later!";
                    $response['amount'] = (isset($transaction['data']['amount'])) ? $transaction['data']['amount'] : 0;;
                    $response['data'] = $transaction;
                    return $response;
                }
            } else {
                $response['error'] = true;
                $response['message'] = "Payment not found by the transaction ID!";
                $response['amount'] = 0;
                $response['data'] = [];
                return $response;
            }
            break;


        case 'paytm':
            $CI->load->library('paytm');
            $payment = $CI->paytm->transaction_status($txn_id); /* We are using order_id created during the generation of txn token */
            if (!empty($payment)) {
                $payment = json_decode($payment, true);
                if (
                    isset($payment['body']['resultInfo']['resultCode'])
                    && ($payment['body']['resultInfo']['resultCode'] == '01' && $payment['body']['resultInfo']['resultStatus'] == 'TXN_SUCCESS')
                ) {
                    $response['error'] = false;
                    $response['message'] = "Payment is successful";
                    $response['amount'] = (isset($payment['body']['txnAmount'])) ? $payment['body']['txnAmount'] : 0;
                    $response['data'] = $payment;
                    return $response;
                } elseif (
                    isset($payment['body']['resultInfo']['resultCode'])
                    && ($payment['body']['resultInfo']['resultStatus'] == 'TXN_FAILURE')
                ) {
                    $response['error'] = true;
                    $response['message'] = $payment['body']['resultInfo']['resultMsg'];
                    $response['amount'] = (isset($payment['body']['txnAmount'])) ? $payment['body']['txnAmount'] : 0;
                    $response['data'] = $payment;
                    return $response;
                } else if (
                    isset($payment['body']['resultInfo']['resultCode'])
                    && ($payment['body']['resultInfo']['resultStatus'] == 'PENDING')
                ) {
                    $response['error'] = true;
                    $response['message'] = $payment['body']['resultInfo']['resultMsg'];
                    $response['amount'] = (isset($payment['body']['txnAmount'])) ? $payment['body']['txnAmount'] : 0;
                    $response['data'] = $payment;
                    return $response;
                } else {
                    $response['error'] = true;
                    $response['message'] = "Payment is unsuccessful!";
                    $response['amount'] = (isset($payment['body']['txnAmount'])) ? $payment['body']['txnAmount'] : 0;
                    $response['data'] = $payment;
                    return $response;
                }
            } else {
                $response['error'] = true;
                $response['message'] = "Payment not found by the Order ID!";
                $response['amount'] = 0;
                $response['data'] = [];
                return $response;
            }
            break;

        case 'paypal':
            # code...
            return "paypal is supplied";
            break;

        default:
            # code...
            $response['error'] = true;
            $response['message'] = "Could not validate the transaction with the supplied payment method";
            return $response;
            break;
    }
}

function process_referral_bonus($user_id, $order_id, $status)
{
    /* 
        $user_id = 99;              << user ID of the person whose order is being marked not the friend's ID who is going to get the bonus  
        $status = "delivered";      << current status of the order 
        $order_id = 644;            << Order which is being marked as delivered

    */

    /* -----------------------------------------------------------------------
     * This is now the referral programme's hook into order status changes, and
     * the ONLY thing it does is delegate to Referral_engine.
     *
     * The nine call sites - admin, seller, delivery-boy and app paths, web and
     * API - are deliberately untouched. They already fire at exactly the two
     * moments the programme cares about, and they already pass the status, so
     * routing both earning and reversal through this one function means neither
     * had to be wired into nine controllers a second time.
     *
     * Everything the old body did is gone: it credited through the deprecated
     * customer_model->update_balance() (users.balance written outside a
     * transaction - the documented cause of balances drifting from the ledger),
     * keyed idempotency on the ORDER while gating on the referee's LIFETIME
     * order count (so a new buyer's first few orders each paid the referrer
     * again), and paid the instant an order was marked delivered, with no hold,
     * so a returned order kept its bonus. The dead body below is kept for one
     * release as a reference for anyone comparing behaviour, and goes with the
     * legacy `is_refer_earn_on` settings group in phase 3.
     * --------------------------------------------------------------------- */
    $CI = &get_instance();
    $CI->load->library('referral_engine');

    if ($status === 'delivered') {
        /* Two different milestones fire on one delivery, and they belong to two
         * different people:
         *   - the BUYER's first delivered order, which pays whoever referred the
         *     buyer (customer programmes);
         *   - the referred SELLER's first delivered SALE, which pays whoever
         *     referred the seller. The call sites only know the buyer, so the
         *     sellers are read off the order's items inside the engine. */
        $buyer_side = $CI->referral_engine->order_delivered($user_id, $order_id);
        $CI->referral_engine->seller_sale_delivered($order_id);

        return $buyer_side;
    }

    if ($status === 'returned' || $status === 'cancelled') {
        return $CI->referral_engine->reverse_for_order(
            $order_id,
            ($status === 'returned') ? 'Qualifying order returned' : 'Qualifying order cancelled'
        );
    }

    return ['ok' => false, 'reason' => 'status_not_relevant'];

    /* -----------------------------------------------------------------------
     * DEAD CODE BELOW - the legacy implementation, kept for one release.
     *
     * This has never paid anybody, because web signup never issued a
     * referral_code, so no customer's friends_code could ever match one. That
     * changed with migration 078, which backfilled a code onto every account:
     * without this guard, the next delivered order would start paying bonuses
     * through the body below, which has three known defects -
     *
     *   1. it credits with customer_model->update_balance(), the path marked
     *      DEPRECATED - do not call in this repo, because it moves users.balance
     *      outside a transaction and is the documented cause of stored balances
     *      drifting from the transactions ledger;
     *   2. its idempotency key is the ORDER ("refer-and-earn-<order_id>"), while
     *      its eligibility test counts the referee's LIFETIME orders, so a new
     *      buyer's first `refer_earn_bonus_times` orders each pay the referrer
     *      again - nothing is scoped to the referral relationship;
     *   3. it pays the moment an order is marked delivered, with no hold window,
     *      so a returned order keeps its bonus.
     *
     * The nine call sites are left exactly as they are: they fire at every point
     * an order reaches "delivered" across web, app, seller and delivery paths,
     * which is precisely where the phase 2 reward engine needs to be called
     * from. This function becomes that call; until then it does nothing.
     *
     * --------------------------------------------------------------------- */
    $CI = &get_instance();
    $settings = get_settings('system_settings', true);
    if (isset($settings['is_refer_earn_on']) && $settings['is_refer_earn_on'] == 1 && $status == "delivered") {
        $user = fetch_users($user_id);

        /* check if user has set friends code or not */
        if (isset($user[0]['friends_code']) && !empty($user[0]['friends_code'])) {

            /* find number of previous orders of the user */
            $total_orders = fetch_details('orders', ['user_id' => $user_id], 'COUNT(id) as total');
            $total_orders = $total_orders[0]['total'];

            if ($total_orders < $settings['refer_earn_bonus_times']) {

                /* find a friends account details */
                $friend_user = fetch_details('users', ['referral_code' => $user[0]['friends_code']], 'id,username,email,mobile,balance');
                if (!empty($friend_user)) {
                    $order = fetch_orders($order_id);
                    $final_total = $order['order_data'][0]['final_total'];
                    if ($final_total >= $settings['min_refer_earn_order_amount']) {
                        $referral_bonus = 0;
                        if ($settings['refer_earn_method'] == 'percentage') {
                            $referral_bonus = $final_total * ($settings['refer_earn_bonus'] / 100);
                            if ($referral_bonus > $settings['max_refer_earn_amount']) {
                                $referral_bonus = $settings['max_refer_earn_amount'];
                            }
                        } else {
                            $referral_bonus = $settings['refer_earn_bonus'];
                        }

                        $referral_id = "refer-and-earn-" . $order_id;
                        $previous_referral = fetch_details('transactions', ['order_id' => $referral_id], 'id,amount');
                        if (empty($previous_referral)) {
                            $CI->load->model("transaction_model");
                            $transaction_data = [
                                'transaction_type' => "wallet",
                                'user_id' => $friend_user[0]['id'],
                                'order_id' => $referral_id,
                                'type' => "credit",
                                'txn_id' => "",
                                'amount' => $referral_bonus,
                                'status' => "success",
                                'message' => "Refer and Earn bonus on " . $user[0]['username'] . "'s order",
                            ];
                            $CI->transaction_model->add_transaction($transaction_data);
                            $CI->load->model('customer_model');
                            if ($CI->customer_model->update_balance($referral_bonus, $friend_user[0]['id'], 'add')) {
                                $response['error'] = false;
                                $response['message'] = "User's wallet credited successfully";
                                return $response;
                            }
                        } else {
                            $response['error'] = true;
                            $response['message'] = "Bonus is already given for the following order!";
                            return $response;
                        }
                    } else {
                        $response['error'] = true;
                        $response['message'] = "This order amount is not eligible refer and earn bonus!";
                        return $response;
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = "Friend user not found for the used referral code!";
                    return $response;
                }
            } else {
                $response['error'] = true;
                $response['message'] = "Number of orders have exceeded the eligible first few orders!";
                return $response;
            }
        } else {
            $response['error'] = true;
            $response['message'] = "No friends code found!";
            return $response;
        }
    } else {
        if ($status == "delivered") {
            $response['error'] = true;
            $response['message'] = "Referred and earn system is turned off";
            return $response;
        } else {
            $response['error'] = true;
            $response['message'] = "Status must be set to delivered to get the bonus";
            return $response;
        }
    }
}

function process_refund($id, $status, $type = 'order_items')
{
    $possible_status = array("cancelled", "returned");
    if (!in_array($status, $possible_status)) {
        $response['error'] = true;
        $response['message'] = 'Refund cannot be processed. Invalid status';
        $response['data'] = array();
        return $response;
    }

    if ($type == 'order_items') {

        /* fetch order_id */
        $order_item_details = fetch_details('order_items', ['id' => $id], 'order_id,id,seller_id,sub_total,quantity,status,refunded_at,accounted_at');

        if (empty($order_item_details)) {
            return ['error' => true, 'message' => 'Order item not found.', 'data' => array()];
        }

        // TWO independent once-only guards, because paying the customer and adjusting the books
        // are not always done by the same actor or at the same moment.
        //
        // Seven callers reach this function - the customer's cancel/return, the app API, the
        // admin and seller order screens, the delivery boy screen, the return-request approval,
        // and the Shiprocket webhook - and several legitimately fire in sequence for one
        // return. The guards live on the item rather than in the callers because the callers
        // cannot be made to agree on ordering; the Shiprocket webhook in particular arrives
        // whenever the courier says so.
        //
        // refunded_at guards the PAYMENT: the wallet credit or the gateway refund.
        // accounted_at guards the ACCOUNTING: the seller commission clawback, the per-seller
        // order_charges rewrite and the order totals. All of it is non-idempotent.
        //
        // They were a single stamp, and that quietly lost money. An admin refunding a card from
        // the order screen pays the customer and stamps refunded_at without touching the books;
        // when the return was then approved, this function returned immediately, so the seller
        // kept the commission on a refunded sale, the order totals still counted the returned
        // line, and the seller's parcel was never resized.
        $already_paid = !empty($order_item_details[0]['refunded_at']);
        $already_accounted = !empty($order_item_details[0]['accounted_at']);

        if ($already_paid && $already_accounted) {
            return [
                'error' => false,
                'message' => 'Refund already processed for this order item.',
                'data' => array(),
                'already_refunded' => true,
            ];
        }

        /* fetch order and its complete details with order_items */
        $order_id = $order_item_details[0]['order_id'];
        $seller_id = $order_item_details[0]['seller_id'];

        $order_item_data = fetch_details('order_charges', ['order_id' => $order_id, 'seller_id' => $seller_id], 'sub_total');
        $order_total = 0.00;
        if (isset($order_item_data) && !empty($order_item_data)) {
            $order_total = floatval($order_item_data[0]['sub_total']);
        }

        $order_item_total = $order_item_details[0]['sub_total'];

        $order_details =  fetch_orders($order_id);
        $order_details = $order_details['order_data'];

        $order_items_details = $order_details[0]['order_items'];

        $key = array_search($id, array_column($order_items_details, 'id'));
        $current_price = $order_items_details[$key]['sub_total'];
        $order_item_id = $order_items_details[$key]['id'];
        $currency = (isset($system_settings['currency']) && !empty($system_settings['currency'])) ? $system_settings['currency'] : '';
        $payment_method = $order_details[0]['payment_method'];


        //check for order active status 
        $active_status = json_decode($order_item_details[0]['status'], true);

        // An order that went straight from "awaiting" (payment never completed) to
        // "cancelled" has nothing to refund. Indexes are checked before use because the
        // history can legitimately hold a single entry, and reading [1][0] off that
        // raised warnings and then compared null - which happened to be harmless here,
        // but only by luck.
        if (trim(strtolower($payment_method)) != 'wallet') {
            if (is_array($active_status)
                && isset($active_status[0][0], $active_status[1][0])
                && $active_status[1][0] == 'cancelled'
                && $active_status[0][0] == 'awaiting') {
                $response['error'] = true;
                $response['message'] = 'Refund cannot be processed.';
                $response['data'] = array();
                return $response;
            }
        }

        $total = $order_details[0]['total'];
        $is_delivery_charge_returnable = isset($order_details[0]['is_delivery_charge_returnable']) && $order_details[0]['is_delivery_charge_returnable'] == 1 ? '1' : '0';
        $delivery_charge = (isset($order_details[0]['delivery_charge']) && !empty($order_details[0]['delivery_charge'])) ? $order_details[0]['delivery_charge'] : 0;

        $promo_code = $order_details[0]['promo_code'];
        $promo_discount = $order_details[0]['promo_discount'];
        $final_total = $order_details[0]['final_total'];
        $wallet_balance = $order_details[0]['wallet_balance'];
        $total_payable = $order_details[0]['total_payable'];
        $user_id = $order_details[0]['user_id'];

        // Is this the final live line on the order? It decides whether a returnable delivery
        // charge is refunded too.
        //
        // This used to compare order_counter against the number of items whose active_status was
        // ALREADY 'cancelled'/'returned', which makes the answer depend on whether the caller
        // wrote the status before or after calling this function. The return-approval path
        // writes it AFTER (the item sits at return_request_pending here and becomes
        // return_request_approved once this returns), so last_item was never 1 on a return and
        // the returnable delivery charge was silently never refunded. Now that the refund fires
        // exactly once, at approval, there is no later pass to make up for it.
        //
        // Asked instead as "once this refund is done, is every line on this order finished?",
        // counting a line as finished if it is cancelled/returned, has already been refunded, or
        // is the line being refunded right now. That is true regardless of caller ordering.
        $all_lines = fetch_details('order_items', ['order_id' => $order_id], 'id,active_status,refunded_at');
        $order_items_count = count($all_lines);
        $finished_lines = 0;
        foreach ($all_lines as $line) {
            if (
                (string) $line['id'] === (string) $id
                || !empty($line['refunded_at'])
                || in_array($line['active_status'], ['cancelled', 'returned'], true)
            ) {
                $finished_lines++;
            }
        }
        $last_item = ($order_items_count > 0 && $finished_lines >= $order_items_count) ? 1 : 0;

        $user_res = fetch_details('users', ['id' => $user_id],  'fcm_id,mobile,email,username');
        $fcm_ids = array();
        if (!empty($user_res[0]['fcm_id'])) {
            $fcm_ids[0][] = $user_res[0]['fcm_id'];
        }
        $new_total = $total - $current_price;

        /* recalculate delivery charge */
        $new_delivery_charge = ($new_total > 0) ? recalulate_delivery_charge($order_details[0]['address_id'], $new_total, $delivery_charge) : 0;
        /* recalculate promo discount */
        $new_promo_discount = recalculate_promo_discount($promo_code, $promo_discount, $user_id, $new_total, $payment_method, $new_delivery_charge, $wallet_balance);

        $new_final_total = $new_total + $new_delivery_charge - $new_promo_discount;
        $bank_receipt = fetch_details('order_bank_transfer', ['order_id' => $order_item_details[0]['order_id']]);
        $bank_receipt_status = (isset($bank_receipt[0]['status'])) ? $bank_receipt[0]['status'] : "";

        /* find returnable_amount, new_wallet_balance
        condition : 1
        */
        // Defaults: neither condition 1 nor condition 2 below matches a Bank Transfer
        // order whose receipt sits in any state other than 0/1/2 (e.g. a rejected
        // receipt), which left both of these undefined and then used them for
        // notifications, wallet credits and order totals.
        $returnable_amount = 0;
        $new_wallet_balance = $wallet_balance;
        if (trim(strtolower($payment_method)) == 'cod' || $payment_method == 'Bank Transfer') {
            /* when payment method is COD or Bank Transfer and payment is not yet done */
            if (trim(strtolower($payment_method)) == 'cod' || ($payment_method == 'Bank Transfer' && (empty($bank_receipt_status) || $bank_receipt_status == "0" || $bank_receipt_status == "1"))) {
                $returnable_amount = ($wallet_balance <= $current_price) ? $wallet_balance : (($wallet_balance > 0) ? $current_price : 0);
                $returnable_amount = ($promo_discount != $new_promo_discount && $last_item == 0) ? $returnable_amount - $promo_discount + $new_promo_discount : $returnable_amount; /* if the new promo discount changed then adjust that here */
                $returnable_amount = ($returnable_amount < 0) ? 0 : $returnable_amount;

                /* if returnable_amount is 0 then don't change he wallet_balance */
                $new_wallet_balance = ($returnable_amount > 0) ? (($wallet_balance <= $current_price) ? 0 : (($wallet_balance - $current_price > 0) ? $wallet_balance - $current_price : 0)) : $wallet_balance;
            }
            /* if it is bank transfer and payment is already done by bank transfer 
            same as condition : 2
            */
        }

        /* if it is any other payment method or bank transfer with accepted receipts then payment is already done 
        condition : 2
        */
        if ((trim(strtolower($payment_method)) != 'cod' && $payment_method != 'Bank Transfer') || ($payment_method == 'Bank Transfer' && $bank_receipt_status == 2)) {
            $returnable_amount = $current_price;
            $returnable_amount = ($promo_discount != $new_promo_discount) ? $returnable_amount - $promo_discount + $new_promo_discount : $returnable_amount;
            $returnable_amount = ($last_item == 1 && $is_delivery_charge_returnable == 1) ? $returnable_amount + $delivery_charge : $returnable_amount;  /* if its the last item getting cancelled then check if we have to return delivery charge or not */
            $returnable_amount = ($returnable_amount < 0) ? 0 : $returnable_amount;
            $new_wallet_balance = ($last_item == 1) ? 0 : (($wallet_balance - $returnable_amount < 0) ? 0 : $wallet_balance - $returnable_amount);
        }

        /* find new_total_payable */
        if (trim(strtolower($payment_method)) != 'cod' && $payment_method != 'Bank Transfer') {
            /* online payment or any other payment method is used. and payment is already done */
            $new_total_payable = 0;
        } else {
            if ($bank_receipt_status == 2) {
                $new_total_payable = 0;
            } else {
                $new_total_payable = $new_final_total - $new_wallet_balance;
            }
        }

        if ($new_total == 0) {
            $new_total = $new_wallet_balance = $new_delivery_charge = $new_final_total = $new_total_payable = 0;
        }

        //custom message
        // Guarded: there is no guarantee a "wallet_transaction" custom_notifications row
        // exists (it doesn't on a default install), and reading [0]['message'] off an
        // empty result raised two warnings on every single refund. The template is
        // already treated as optional five lines below - treat it as optional here too.
        $custom_notification = fetch_details('custom_notifications', ['type' => "wallet_transaction"], '');
        $hashtag_currency = '< currency >';
        $hashtag_returnable_amount = '< returnable_amount >';
        $message = '';
        if (!empty($custom_notification) && isset($custom_notification[0]['message'])) {
            $string = json_encode($custom_notification[0]['message'], JSON_UNESCAPED_UNICODE);
            $hashtag = html_entity_decode($string);
            $data = str_replace(array($hashtag_currency, $hashtag_returnable_amount), array($currency, $returnable_amount), $hashtag);
            $message = output_escaping(trim($data, '"'));
        }

        $refund_result = ['mode' => 'none', 'gateway_amount' => 0.0, 'wallet_amount' => 0.0, 'error' => false, 'message' => ''];
        if (!$already_paid && $returnable_amount > 0) {

            $fcmMsg = array(
                'title' => (!empty($custom_notification)) ? $custom_notification[0]['title'] : "Amount Credited To Wallet",
                'body' => (!empty($custom_notification)) ? $message : $currency . $returnable_amount,
                'type' => "wallet",
            );
            send_notification($fcmMsg, $fcm_ids);
            (notify_event(
                "wallet_transaction",
                ["customer" => [$user_res[0]['email']]],
                ["customer" => [$user_res[0]['mobile']]],
                ["users.id" => $user_id]
            ));

            // Back the way it came in - to the card/UPI account for a gateway payment, to the
            // wallet for wallet-funded or COD money. This used to credit the wallet in every
            // case: the two branches here differed only in the LABEL they put on the ledger row
            // ('refund' vs 'credit'), so a customer who paid by card was handed store credit
            // and told it was a refund. See refund_to_payment_source().
            $refund_result = refund_to_payment_source(
                $order_id,
                $id,
                $user_id,
                $returnable_amount,
                'Refund for Order Item ID  : ' . $id
            );
        }

        // ---------------------------------------------------------------------------------
        // ACCOUNTING. Guarded by accounted_at, independently of whether the customer has
        // already been paid - see the note on the guards at the top of this branch.
        // ---------------------------------------------------------------------------------
        if (!$already_accounted) {

        // Claw back the seller's commission for this item.
        //
        // The customer has just been refunded for a sale that is no longer happening, but
        // nothing anywhere reversed the seller side: if the item had already been settled the
        // seller kept the full net payable and the platform kept its commission, so the refund
        // came entirely out of platform funds with no record that it had. This runs for every
        // cancellation and every approved return because every one of them reaches this
        // function. It no-ops when the item was never settled (the common case, where the
        // return window has not yet elapsed), and it is idempotent, so a repeated cancellation
        // or a webhook retry cannot debit the seller twice.
        $CI = &get_instance();
        $CI->load->model('Seller_model');
        $CI->Seller_model->reverse_settlement_for_order_item(
            $order_item_id,
            ($status == 'returned') ? 'Order item returned' : 'Order item cancelled'
        );

        // recalculate delivery charge and promocode for each seller

        $order_delivery_charge = fetch_details('order_charges', ['order_id' => $order_id, 'seller_id' => $seller_id], 'delivery_charge');
        $order_charges_data = fetch_details('order_charges', ['order_id' => $order_id, 'seller_id !=' => $seller_id], '*');

        if (isset($order_delivery_charge) && !empty($order_delivery_charge)) {
            $parcel_total = floatval($order_total) - floatval($order_item_total);
            // Initialised so that the $parcel_total == 0 case (this seller's parcel is now
            // empty - every item of theirs on the order has been cancelled/returned) doesn't
            // fall through to an undefined variable. Zero is also the correct share: an
            // empty parcel carries none of the promo discount and none of the delivery charge.
            $seller_promocode_discount_percentage = 0;
            if ($parcel_total != 0) {
                if ($new_total != 0) {
                    $seller_promocode_discount_percentage = ($parcel_total * 100) / $new_total;
                } else {
                    $seller_promocode_discount_percentage = ($parcel_total * 100);
                }
            }
            $seller_promocode_discount =  ($new_promo_discount * $seller_promocode_discount_percentage) / 100;
            $seller_delivery_charge = ($new_delivery_charge * $seller_promocode_discount_percentage) / 100;

            $parcel_final_total = $parcel_total + $seller_delivery_charge - $seller_promocode_discount;
            $set =  [
                'promo_discount' => round($seller_promocode_discount, 2),
                'delivery_charge' => round($seller_delivery_charge, 2),
                'sub_total' => round($parcel_total, 2),
                'total' => round($parcel_final_total, 2)
            ];
            update_details($set, ['order_id' => $order_id, 'seller_id' => $seller_id], 'order_charges');
        }
        if (isset($order_charges_data) && !empty($order_charges_data)) {
            foreach ($order_charges_data as $data) {

                $total = $data['sub_total'] + $data['promo_discount'] - $data['delivery_charge'];
                if ($new_total != 0) {
                    $promocode_discount_percentage = ($data['sub_total'] * 100) / $new_total;
                } else {
                    $promocode_discount_percentage = ($data['sub_total'] * 100);
                }
                $promocode_discount =  ($new_promo_discount * $promocode_discount_percentage) / 100;
                $delivery_charge = ($new_delivery_charge * $promocode_discount_percentage) / 100;
                $final_total = $data['sub_total'] + $delivery_charge - $promocode_discount;
                $value =  [
                    'promo_discount' => round($promocode_discount, 2),
                    'delivery_charge' => round($delivery_charge, 2),
                    'sub_total' => $data['sub_total'],
                    'total' => round($final_total, 2)
                ];
                update_details($value, ['order_id' => $order_id, 'seller_id' => $data['seller_id']], 'order_charges');
            }
        }
        // end

        $set =  [
            'total' => $new_total,
            'final_total' => $new_final_total,
            'total_payable' => $new_total_payable,
            'promo_discount' => (!empty($new_promo_discount) && $new_promo_discount > 0) ? $new_promo_discount : 0,
            'delivery_charge' => $new_delivery_charge,
            'wallet_balance' => $new_wallet_balance
        ];
        update_details($set, ['id' => $order_id], 'orders');

            update_details(['accounted_at' => date('Y-m-d H:i:s')], ['id' => $id], 'order_items');
        }

        // Close the item out for payment. Written unconditionally - including when
        // $returnable_amount was 0 (an unpaid COD cancellation, say). "Nothing was owed" is
        // just as final a settlement as "we paid 900", and leaving it NULL would let the next
        // status change hand out a refund.
        //
        // Keyed on $id, the item this call was asked to refund, rather than on the
        // $order_item_id derived from array_search() above - that returns false when the item
        // is missing from the rebuilt order payload, and false as an id would stamp nothing.
        if (!$already_paid) {
            update_details([
                'refunded_at'   => date('Y-m-d H:i:s'),
                'refund_amount' => round((float) $returnable_amount, 2),
                // The channel the money actually went back through - gateway, wallet, both, or
                // none - rather than a hardcoded 'wallet'. admin/Orders::refund_payment() reads
                // this to decide whether a manual card refund would be a second payout.
                'refund_mode'   => isset($refund_result['mode']) ? $refund_result['mode'] : 'none',
            ], ['id' => $id], 'order_items');
        }

        $response['error'] = false;
        $response['message'] = 'Status Updated Successfully';
        $response['data'] = array();
        return $response;
    } elseif ($type == 'orders') {

        /* if complete order is getting cancelled */
        // Same clawback as the per-item branch, applied to every item on the order. A
        // whole-order cancellation refunds the customer for all of them, so any that had
        // already been settled must be reversed too - otherwise cancelling an entire order
        // was a way for the seller's commission to survive intact. No-ops per item when
        // nothing was settled, and is idempotent.
        // Same one-payout-per-item rule as the per-item branch above, applied to the order as a
        // whole. This branch sizes the refund from the ORDER totals, so it is only correct if
        // nothing on the order has been refunded individually yet - otherwise it would pay for
        // items that have already been settled. Any item already stamped means a per-item path
        // got here first, and the per-item paths between them cover the whole order.
        $order_items_to_refund = fetch_details('order_items', ['order_id' => $id], 'id,refunded_at,accounted_at');
        if (empty($order_items_to_refund)) {
            return ['error' => true, 'message' => 'Order not found.', 'data' => array()];
        }

        // Same two-guard split as the per-item branch above: refunded_at gates the payment,
        // accounted_at gates the commission clawback.
        //
        // The payment here is sized from the ORDER totals, so it is only correct while nothing
        // on the order has been refunded individually - otherwise it would pay again for lines
        // already settled. One stamped line therefore disables the payment for the whole order
        // (the per-item paths between them cover it). The clawback is per item and is applied
        // to whichever lines have not had it yet, so a line refunded by hand from the order
        // screen still has its commission reversed when the order is cancelled.
        $unpaid_items = [];
        $unaccounted_items = [];
        foreach ($order_items_to_refund as $item) {
            if (empty($item['refunded_at'])) {
                $unpaid_items[] = $item;
            }
            if (empty($item['accounted_at'])) {
                $unaccounted_items[] = $item;
            }
        }

        if (empty($unpaid_items) && empty($unaccounted_items)) {
            return [
                'error' => false,
                'message' => 'Refund already processed for this order.',
                'data' => array(),
                'already_refunded' => true,
            ];
        }

        $may_pay = (count($unpaid_items) === count($order_items_to_refund));

        $CI = &get_instance();
        $CI->load->model('Seller_model');
        foreach ($unaccounted_items as $item) {
            $CI->Seller_model->reverse_settlement_for_order_item(
                $item['id'],
                ($status == 'returned') ? 'Order returned' : 'Order cancelled'
            );
            update_details(['accounted_at' => date('Y-m-d H:i:s')], ['id' => $item['id']], 'order_items');
        }

        if (!$may_pay) {
            return [
                'error'   => false,
                'message' => 'Refund already processed for part of this order; the remaining accounting has been applied.',
                'data'    => array(),
                'already_refunded' => true,
            ];
        }

        $order_details =  fetch_orders($id);
        $order_item_details = fetch_details('order_items', ['order_id' => $order_details['order_data'][0]['id']], 'sum(tax_amount) as total_tax,status');
        $order_details = $order_details['order_data'];
        $payment_method = $order_details[0]['payment_method'];

        // Same "went straight from awaiting to cancelled, so nothing was ever paid" test as the
        // per-item branch, and guarded the same way. Indexing [1][0]/[0][0] blind warned twice
        // on every whole-order refund (the history legitimately holds a single entry) and then
        // compared null - and $order_item_details here is a bare aggregate over ALL the order's
        // items, so `status` is whichever row MySQL happened to pick. Read the history from the
        // order's own items instead, and only skip the refund when EVERY line went
        // awaiting -> cancelled; one paid line means there is something to refund.
        $never_paid = false;
        if (trim(strtolower($payment_method)) != 'wallet') {
            $status_rows = fetch_details('order_items', ['order_id' => $id], 'status');
            $never_paid = !empty($status_rows);
            foreach ($status_rows as $row) {
                $history = json_decode($row['status'], true);
                if (
                    !is_array($history)
                    || !isset($history[0][0], $history[1][0])
                    || $history[0][0] != 'awaiting'
                    || $history[1][0] != 'cancelled'
                ) {
                    $never_paid = false;
                    break;
                }
            }
        }
        if ($never_paid) {
            $response['error'] = true;
            $response['message'] = 'Refund cannot be processed.';
            $response['data'] = array();
            return $response;
        }

        $refund_result = ['mode' => 'none', 'gateway_amount' => 0.0, 'wallet_amount' => 0.0, 'error' => false, 'message' => ''];
        $wallet_refund = true;
        $bank_receipt = fetch_details('order_bank_transfer', ['order_id' => $id]);

        $is_transfer_accepted = 0;

        if ($payment_method == 'Bank Transfer') {
            if (!empty($bank_receipt)) {
                foreach ($bank_receipt as $receipt) {
                    if ($receipt['status'] == 2) {
                        $is_transfer_accepted = 1;
                        break;
                    }
                }
            }
        }
        if ($order_details[0]['wallet_balance'] == 0 && $status == 'cancelled' && $payment_method == 'Bank Transfer' && (!$is_transfer_accepted || empty($bank_receipt))) {
            $wallet_refund = false;
        } else {
            $wallet_refund = true;
        }

        $promo_discount = $order_details[0]['promo_discount'];
        $final_total = $order_details[0]['final_total'];
        $is_delivery_charge_returnable = isset($order_details[0]['is_delivery_charge_returnable']) && $order_details[0]['is_delivery_charge_returnable'] == 1 ? '1' : '0';
        $payment_method = trim(strtolower($payment_method));
        $total_tax_amount = $order_item_details[0]['total_tax'];
        $wallet_balance = $order_details[0]['wallet_balance'];
        $currency = (isset($system_settings['currency']) && !empty($system_settings['currency'])) ? $system_settings['currency'] : '';
        $user_id = $order_details[0]['user_id'];
        $fcmMsg = array(
            'title' => "Amount Credited To Wallet",
        );
        $user_res = fetch_details('users', ['id' => $user_id],  'fcm_id,mobile,email');
        $fcm_ids = array();
        if (!empty($user_res[0]['fcm_id'])) {
            $fcm_ids[0][] = $user_res[0]['fcm_id'];
        }
        if ($wallet_refund == true) {
            if ($payment_method != 'cod') {
                /* update user's wallet */
                if ($is_delivery_charge_returnable == 1) {
                    $returnable_amount =  $order_details[0]['total'] +  $order_details[0]['delivery_charge'];
                } else {
                    $returnable_amount =  $order_details[0]['total'];
                }

                if ($payment_method == 'bank transfer' && !$is_transfer_accepted) {
                    $returnable_amount =  $returnable_amount - $order_details[0]['total_payable'];
                }
                //send custom notifications
                // Optional template - see the per-item branch. Reading [0]['message'] off an
                // empty result warned twice on every whole-order refund, and $message was then
                // the empty string while the fcm/notify calls below still treated the template
                // as present.
                $custom_notification = fetch_details('custom_notifications', ['type' => "wallet_transaction"], '');
                $hashtag_currency = '< currency >';
                $hashtag_returnable_amount = '< returnable_amount >';
                $message = '';
                if (!empty($custom_notification) && isset($custom_notification[0]['message'])) {
                    $string = json_encode($custom_notification[0]['message'], JSON_UNESCAPED_UNICODE);
                    $hashtag = html_entity_decode($string);
                    $data = str_replace(array($hashtag_currency, $hashtag_returnable_amount), array($currency, $returnable_amount), $hashtag);
                    $message = output_escaping(trim($data, '"'));
                }
                $fcmMsg = array(
                    'title' => (!empty($custom_notification)) ? $custom_notification[0]['title'] : "Amount Credited To Wallet",
                    'body' => (!empty($custom_notification)) ? $message : $currency . $returnable_amount,
                    'type' => "wallet",
                );
                send_notification($fcmMsg, $fcm_ids);
                (notify_event(
                    "wallet_transaction",
                    ["customer" => [$user_res[0]['email']]],
                    ["customer" => [$user_res[0]['mobile']]],
                    ["users.id" => $user_id]
                ));

                // Back the way it came in - see refund_to_payment_source().
                $refund_result = refund_to_payment_source($id, null, $user_id, $returnable_amount, 'Refund for Order ID  : ' . $id);
            } else {
                if ($wallet_balance != 0) {
                    /* update user's wallet */
                    $returnable_amount = $wallet_balance;
                    //send custom notifications
                    $custom_notification = fetch_details('custom_notifications', ['type' => "wallet_transaction"], '');
                    $hashtag_currency = '< currency >';
                    $hashtag_returnable_amount = '< returnable_amount >';
                    $message = '';
                    if (!empty($custom_notification) && isset($custom_notification[0]['message'])) {
                        $string = json_encode($custom_notification[0]['message'], JSON_UNESCAPED_UNICODE);
                        $hashtag = html_entity_decode($string);
                        $data = str_replace(array($hashtag_currency, $hashtag_returnable_amount), array($currency, $returnable_amount), $hashtag);
                        $message = output_escaping(trim($data, '"'));
                    }
                    $fcmMsg = array(
                        'title' => (!empty($custom_notification)) ? $custom_notification[0]['title'] : "Amount Credited To Wallet",
                        'body' => (!empty($custom_notification)) ? $message : $currency . $returnable_amount,
                        'type' => "wallet",
                    );
                    send_notification($fcmMsg, $fcm_ids);
                    (notify_event(
                        "wallet_transaction",
                        ["customer" => [$user_res[0]['email']]],
                        ["customer" => [$user_res[0]['mobile']]],
                        ["users.id" => $user_id]
                    ));

                    // A COD order was never charged anywhere - the only money the customer
                    // actually parted with is the wallet balance they put towards it, so this
                    // leg is a wallet credit by definition. Routed through the same helper so
                    // the mode recorded on the items below is decided in one place.
                    $refund_result = refund_to_payment_source($id, null, $user_id, $returnable_amount, 'Refund for Order ID  : ' . $id);
                }
            }
        }

        // Close out every item on the order, so a subsequent per-item cancel/return (or a
        // Shiprocket webhook for one of these items) cannot pay a second time. The amount is
        // recorded against the order as a whole rather than split across items, because that
        // is how it was actually calculated.
        $refunded_total = isset($returnable_amount) ? round((float) $returnable_amount, 2) : 0;
        foreach ($unpaid_items as $item) {
            update_details([
                'refunded_at'   => date('Y-m-d H:i:s'),
                'refund_amount' => 0,
                // The channel the money went back through, not a hardcoded 'wallet'.
                'refund_mode'   => isset($refund_result['mode']) ? $refund_result['mode'] : 'none',
            ], ['id' => $item['id']], 'order_items');
        }

        // This branch used to fall off the end returning NULL, so callers doing
        // `$res['error']` on the result got an "Trying to access array offset on null" notice
        // instead of a status.
        return [
            'error'   => false,
            'message' => 'Status Updated Successfully',
            'data'    => array(),
        ];
    }

    return ['error' => true, 'message' => 'Refund cannot be processed. Invalid type', 'data' => array()];
}

/**
 * The gateways this installation can push a refund back to, mapped to the library that does it.
 *
 * A payment transaction records its gateway in `transactions`.`type` ('razorpay', 'cod',
 * 'wallet', ...). Only the entries listed here can be refunded automatically; anything else
 * (COD, or a gateway whose library has no refund call) falls back to the customer's wallet.
 */
function refundable_payment_gateways()
{
    return [
        'razorpay'    => 'razorpay',
        'flutterwave' => 'flutterwave',
    ];
}

/**
 * Pushes a refund back through the gateway that took the payment.
 *
 * @return array{error: bool, refund_id: string, message: string}
 */
function gateway_refund($gateway, $txn_id, $amount)
{
    $gateway = strtolower(trim((string) $gateway));
    $libraries = refundable_payment_gateways();

    if (!isset($libraries[$gateway])) {
        return ['error' => true, 'refund_id' => '', 'message' => 'The ' . $gateway . ' gateway cannot be refunded automatically.'];
    }
    if (empty($txn_id) || $amount <= 0) {
        return ['error' => true, 'refund_id' => '', 'message' => 'No gateway payment reference to refund against.'];
    }

    $t = &get_instance();
    $library = $libraries[$gateway];
    $t->load->library($library);

    try {
        $response = $t->$library->refund_payment($txn_id, $amount);
    } catch (Throwable $e) {
        log_message('error', 'Gateway refund threw for ' . $gateway . ' txn ' . $txn_id . ': ' . $e->getMessage());
        return ['error' => true, 'refund_id' => '', 'message' => 'The gateway refund call failed: ' . $e->getMessage()];
    }

    // Success is asserted POSITIVELY, from the refund id the gateway returns - the libraries
    // hand back the decoded refund object on success and the raw curl result on failure, and a
    // total network failure comes back with http_code 0, which reads as "no error" to any test
    // phrased as empty($response['http_code']).
    if (!empty($response['id'])) {
        // Logged to file as well as to the ledger. process_refund() is called from inside a DB
        // transaction on some paths (the return-request approval wraps it), and money leaving
        // the gateway is not something a rollback can undo - so if the surrounding transaction
        // ever fails, this line is the record that the refund actually went out.
        log_message('error', 'Gateway refund issued: ' . $gateway . ' payment ' . $txn_id . ' amount ' . $amount . ' refund ' . $response['id']);
        return ['error' => false, 'refund_id' => (string) $response['id'], 'message' => 'Refunded to the original payment method.'];
    }

    $body = isset($response['body']) ? json_decode($response['body'], true) : null;
    $reason = isset($body['error']['description'])
        ? $body['error']['description']
        : 'The gateway did not confirm the refund.';
    log_message('error', 'Gateway refund failed for ' . $gateway . ' txn ' . $txn_id . ' amount ' . $amount . ': ' . $reason);

    return ['error' => true, 'refund_id' => '', 'message' => $reason];
}

/**
 * Refunds money the way the customer paid it.
 *
 * Money that arrived at a payment gateway goes back to that gateway - to the card, the UPI
 * handle or the netbanking account the customer actually used. Money that came out of the
 * customer's wallet (or was never charged at all, as on COD) goes back to the wallet. An order
 * part-paid from the wallet and part-charged to a card is split in exactly that way, gateway
 * first: the gateway leg is capped at what was really captured there, less anything already
 * refunded against the same payment, and whatever is left over lands in the wallet.
 *
 * Everything used to go to the wallet unconditionally, so a customer who paid by card got store
 * credit instead of their money back and had to spend it here to realise its value. An admin
 * could push a card refund by hand from the order screen, but that was a separate, manual and
 * easily-forgotten step.
 *
 * If the gateway call fails - the gateway is down, the payment is too old to refund, the
 * balance is short - the customer is still made whole through their wallet rather than left
 * waiting on an operator. The failure reason is returned so the caller can surface it, and it
 * is written into the ledger row's message so the reason survives in the transaction history.
 *
 * @param  int    $order_id
 * @param  int    $order_item_id  the line being refunded, or 0/null for a whole-order refund
 * @param  int    $user_id        the customer
 * @param  float  $amount         total to refund
 * @param  string $reason         free text recorded on the ledger rows
 * @return array{
 *     mode: string, gateway_amount: float, wallet_amount: float,
 *     gateway: string, refund_id: string, error: bool, message: string
 * }  mode is one of none | wallet | gateway | gateway+wallet
 */
function refund_to_payment_source($order_id, $order_item_id, $user_id, $amount, $reason = 'Refund')
{
    $t = &get_instance();
    $amount = round((float) $amount, 2);

    $result = [
        'mode'           => 'none',
        'gateway_amount' => 0.0,
        'wallet_amount'  => 0.0,
        'gateway'        => '',
        'refund_id'      => '',
        'error'          => false,
        'message'        => '',
    ];

    if ($amount <= 0) {
        return $result;
    }

    // The gateway payment is recorded against the ORDER, never against an order item - payments
    // are written with order_item_id NULL. Keyed on the item (as one earlier version of this
    // lookup was) it finds the wallet refund rows instead, whose txn_id is empty.
    $payment = $t->db
        ->where('order_id', $order_id)
        ->where('transaction_type', 'transaction')
        ->where('status', 'success')
        ->where('txn_id IS NOT NULL', null, false)
        ->where('txn_id !=', '')
        ->order_by('id', 'DESC')
        ->get('transactions')
        ->row_array();

    $gateway = !empty($payment['type']) ? strtolower(trim($payment['type'])) : '';
    $gateways = refundable_payment_gateways();
    $can_refund_to_gateway = !empty($payment) && isset($gateways[$gateway]);

    $gateway_part = 0.0;
    if ($can_refund_to_gateway) {
        // Never refund more to the card than was charged to it. Refunds already pushed against
        // this same payment are subtracted, so a second line on the same order cannot re-refund
        // the first line's share, and an admin who already refunded part of it by hand from the
        // order screen is accounted for.
        $already = $t->db
            ->select('COALESCE(SUM(amount), 0) as refunded', false)
            ->where('order_id', $order_id)
            ->where('transaction_type', 'transaction')
            ->where('type', 'refund')
            ->where('status', 'success')
            ->get('transactions')
            ->row_array();

        $capacity = round((float) $payment['amount'] - (float) $already['refunded'], 2);
        $gateway_part = min($amount, max(0.0, $capacity));
    }

    $wallet_part = round($amount - $gateway_part, 2);

    if ($gateway_part > 0) {
        $refund = gateway_refund($gateway, $payment['txn_id'], $gateway_part);

        if (empty($refund['error'])) {
            $result['gateway_amount'] = $gateway_part;
            $result['gateway']        = $gateway;
            $result['refund_id']      = $refund['refund_id'];

            $t->load->model('Transaction_model');
            $t->Transaction_model->add_transaction([
                'transaction_type' => 'transaction',
                'user_id'          => $user_id,
                'order_id'         => $order_id,
                'order_item_id'    => !empty($order_item_id) ? $order_item_id : null,
                'type'             => 'refund',
                'txn_id'           => $refund['refund_id'],
                'amount'           => $gateway_part,
                'status'           => 'success',
                'message'          => $reason . ' - refunded to the original payment method (' . $gateway . ')',
            ]);
        } else {
            // Make the customer whole through the wallet rather than leave the refund owed
            // while somebody notices. The wallet credit is recorded with the gateway's own
            // failure reason so it is obvious later why it went this way.
            $wallet_part = round($wallet_part + $gateway_part, 2);
            $result['error']   = true;
            $result['message'] = $refund['message'];
            $reason .= ' (gateway refund unavailable: ' . $refund['message'] . ')';
        }
    }

    if ($wallet_part > 0) {
        update_wallet_balance('refund', $user_id, $wallet_part, $reason, !empty($order_item_id) ? $order_item_id : '');
        $result['wallet_amount'] = $wallet_part;
    }

    if ($result['gateway_amount'] > 0 && $result['wallet_amount'] > 0) {
        $result['mode'] = 'gateway+wallet';
    } elseif ($result['gateway_amount'] > 0) {
        $result['mode'] = 'gateway';
    } elseif ($result['wallet_amount'] > 0) {
        $result['mode'] = 'wallet';
    }

    return $result;
}

/**
 * Restores the stock of a single order item, at most once.
 *
 * Every cancellation and return path used to call update_stock(..., 'plus') inline, and
 * several of them run in sequence over the same item (approve a return, then mark it returned
 * when the parcel arrives; or a status change followed by the Shiprocket webhook for the same
 * shipment). Each call put the quantity back again, so inventory drifted upwards by one
 * quantity per extra path. The "already restored" fact is recorded on the item for the same
 * reason process_refund() records "already refunded" there - the callers cannot be ordered.
 *
 * Also labels the movement, which the inline calls never did, so stock_logs shows WHY the
 * quantity moved instead of an unattributed adjustment.
 *
 * @param  int    $order_item_id
 * @param  string $note    free-text note recorded on the stock movement, e.g. 'Order item returned'
 * @param  string $reason  stock_logs reason - 'order_restore' normally, 'expiry_restore' when an
 *                         unpaid order is being released
 * @return bool   true when stock was actually put back by this call
 */
function restore_order_item_stock($order_item_id, $note = 'Order item cancelled', $reason = 'order_restore')
{
    $item = fetch_details('order_items', ['id' => $order_item_id], 'id,order_id,user_id,product_variant_id,quantity,stock_restored_at');
    if (empty($item) || !empty($item[0]['stock_restored_at'])) {
        return false;
    }

    // Stamp BEFORE restoring: if two requests race here, the second one's read of
    // stock_restored_at is what stops it, and losing a restore is recoverable while a double
    // restore silently inflates sellable inventory.
    update_details(['stock_restored_at' => date('Y-m-d H:i:s')], ['id' => $order_item_id], 'order_items');

    set_stock_movement_context($reason, $item[0]['order_id'], $item[0]['user_id'], $note);
    update_stock([$item[0]['product_variant_id']], [$item[0]['quantity']], 'plus');

    return true;
}


function recalulate_delivery_charge($address_id, $total, $old_delivery_charge)
{
    $t = &get_instance();
    $system_settings = get_settings('system_settings', true);
    $min_amount = $system_settings['min_amount'];
    $d_charge = $old_delivery_charge;

    if ((isset($system_settings['area_wise_delivery_charge']) && !empty($system_settings['area_wise_delivery_charge']))) {
        if (isset($address_id) && !empty($address_id)) {
            $address = fetch_details('addresses', ['id' => $address_id],  'area_id,pincode,city_id');
            if ((isset($address[0]['area_id']) && !empty($address[0]['area_id'])) || (isset($address[0]['pincode']) && !empty($address[0]['pincode']))) {
                $area = fetch_details('areas', ['id' => $address[0]['area_id']], 'minimum_free_delivery_order_amount');
                if ($t->db->field_exists('delivery_charges', 'zipcodes') && $t->db->field_exists('minimum_free_delivery_order_amount', 'zipcodes')) {
                    $zipcode = fetch_details('zipcodes', ['zipcode' => $address[0]['pincode'], 'city_id' => $address[0]['city_id']], 'delivery_charges,minimum_free_delivery_order_amount');
                }
                if (isset($area[0]['minimum_free_delivery_order_amount']) || isset($zipcode[0]['minimum_free_delivery_order_amount'])) {
                    $min_amount = isset($area[0]['minimum_free_delivery_order_amount']) && !empty($area[0]['minimum_free_delivery_order_amount']) ? $area[0]['minimum_free_delivery_order_amount'] : $zipcode[0]['minimum_free_delivery_order_amount'];
                }
            }
        }
    }
    if ($total < $min_amount) {
        if ($old_delivery_charge == 0) {
            if (isset($address_id) && !empty($address_id)) {
                $d_charge = get_delivery_charge($address_id);
            } else {
                $d_charge = $system_settings['delivery_charge'];
            }
        }
    }

    return $d_charge;
}

/**
 * Resizes an order's promo discount to what is left of the cart after a cancellation/return.
 *
 * $payment_method, $delivery_charge and $wallet_balance are retained for call compatibility.
 * They fed a $total_payable local that was computed in three branches and then never returned
 * or read by anybody - process_refund(), the sole caller, derives total_payable itself.
 */
function recalculate_promo_discount($promo_code, $promo_discount, $user_id, $total, $payment_method, $delivery_charge, $wallet_balance)
{
    /* recalculate promocode discount if the status of the order_items is cancelled or returned */
    if (!isset($promo_code) || empty($promo_code)) {
        return $promo_discount;
    }

    // The 4th argument - "this is a recalculation, not a fresh redemption" - was ALREADY being
    // passed here, but validate_promo_code() declared only three parameters, so PHP discarded
    // it silently and the call ran the full eligibility check. That check asks "may this
    // customer redeem this code?" about a customer who has already redeemed it on the very
    // order being refunded, so it failed for every single-use code (and for any code whose
    // campaign had since ended or been switched off). This function then returned 0, and
    // process_refund() sized the refund as "item total - the discount we have just decided to
    // stop honouring": on a 2-item Rs. 2000 order with a Rs. 200 discount, returning one
    // Rs. 1000 item refunded Rs. 800 instead of Rs. 1000, and left orders.promo_discount at 0
    // while orders.promo_code still named the code.
    $res = validate_promo_code($promo_code, $user_id, $total, true);
    if (!empty($res['error'])) {
        /* the remaining cart no longer meets the campaign minimum - the discount is forfeited */
        return 0;
    }

    // A cashback code never reduced the order total to begin with: place_order() records
    // promo_discount = 0 for one and settle_cashback_discount() credits the customer's wallet
    // after delivery instead. Recalculating it as an order-level discount invented a discount
    // the order never had, which OVER-refunded the customer by the cashback value and cut
    // final_total at the same time.
    if (isset($res['data'][0]['is_cashback']) && $res['data'][0]['is_cashback'] == 1) {
        return 0;
    }

    // Already capped at max_discount_amount and clamped to the remaining total by
    // apply_promo_code_discount(). The old code applied the ceiling only inside some of its
    // payment-method branches, so a percentage code could return a discount above its own cap.
    return floatval($res['data'][0]['final_discount']);
}

function process_refund_old($id, $status, $type = 'order_items')
{
    /**
     * @param
     * type : orders / order_items
     */
    $possible_status = array("cancelled", "returned");
    if (!in_array($status, $possible_status)) {
        $response['error'] = true;
        $response['message'] = 'Refund cannot be processed. Invalid status';
        $response['data'] = array();
        return $response;
    }
    if ($type == 'order_items') {
        $order_item_details = fetch_details('order_items', ['id' => $id], 'order_id');
        $order_details =  fetch_orders($order_item_details[0]['order_id']);
        if (!empty($order_details) && !empty($order_item_details)) {
            $order_details = $order_details['order_data'];
            $wallet_refund = true;
            $wallet_balance = 0;
            $wallet_balance = $order_details[0]['wallet_balance'];
            $payment_method = $order_details[0]['payment_method'];
            $bank_receipt = fetch_details('order_bank_transfer', ['order_id' => $order_item_details[0]['order_id']]);
            if ($status == 'cancelled' && $payment_method == 'Bank Transfer' && ($bank_receipt[0]['status'] == "0" || $bank_receipt[0]['status'] == "1" || empty($bank_receipt))) {
                if ($wallet_balance == "" || empty($wallet_balance)) {
                    $wallet_refund = false;
                } else {
                    $wallet_refund = true;
                }
            } else {
                $wallet_refund = true;
            }

            $order_items_details = $order_details[0]['order_items'];
            $is_delivery_charge_returnable = isset($order_details[0]['is_delivery_charge_returnable']) && $order_details[0]['is_delivery_charge_returnable'] == 1 ? '1' : '0';
            $total_tax_amount = $order_details[0]['total_tax_amount'];
            $final_total = $order_details[0]['final_total'];
            $total = $order_details[0]['total'] + $total_tax_amount;
            $total_payable = $order_details[0]['total_payable'];
            $key = array_search($id, array_column($order_items_details, 'id'));
            $order_id = $order_details[0]['id'];
            $promo_discount = $order_details[0]['promo_discount'];
            $user_id = $order_details[0]['user_id'];
            $system_settings = get_settings('system_settings', true);
            $currency = (isset($system_settings['currency']) && !empty($system_settings['currency'])) ? $system_settings['currency'] : '';
            $delivery_charge = (isset($order_details[0]['delivery_charge']) && !empty($order_details[0]['delivery_charge'])) ? $order_details[0]['delivery_charge'] : 0;
            $current_price = $order_items_details[$key]['sub_total'];
            $tax_amount = $order_items_details[$key]['tax_amount'];
            $order_counter = $order_items_details[$key]['order_counter'];
            $order_cancel_counter = $order_items_details[$key]['order_cancel_counter'];
            $order_return_counter = $order_items_details[$key]['order_return_counter'];
            $returnable_amount = 0;
            $user_res = fetch_details('users', ['id' => $user_id], 'fcm_id,email,mobile');
            $fcm_ids = array();
            if (!empty($user_res[0]['fcm_id'])) {
                $fcm_ids[0][] = $user_res[0]['fcm_id'];
            }

            if ($wallet_refund == true) {
                $new_final_total = floatval($final_total - $current_price);
                if ($new_final_total >= $promo_discount) {
                    if (trim(strtolower($payment_method)) != 'cod' && $payment_method != 'Bank Transfer') {
                        if ((($order_counter == $order_cancel_counter && $status == 'cancelled') ||  ($order_counter == $order_return_counter && $status == 'returned')) && $is_delivery_charge_returnable == 1) {
                            $returnable_amount = $current_price - $promo_discount + $delivery_charge;
                        } else {
                            $returnable_amount = $current_price - $promo_discount;
                        }
                        //send custom notifications
                        $custom_notification = fetch_details('custom_notifications', ['type' => "wallet_transaction"], '');
                        $hashtag_currency = '< currency >';
                        $hashtag_returnable_amount = '< returnable_amount >';
                        $string = json_encode($custom_notification[0]['message'], JSON_UNESCAPED_UNICODE);
                        $hashtag = html_entity_decode($string);
                        $data = str_replace(array($hashtag_currency, $hashtag_returnable_amount), array($currency, $returnable_amount), $hashtag);
                        $message = output_escaping(trim($data, '"'));
                        $fcmMsg = array(
                            'title' => (!empty($custom_notification)) ? $custom_notification[0]['title'] : "Amount Credited To Wallet",
                            'body' => (!empty($custom_notification)) ? $message : $currency . $returnable_amount,
                            'type' => "wallet",
                        );
                        send_notification($fcmMsg, $fcm_ids);
                        (notify_event(
                            "wallet_transaction",
                            ["customer" => [$user_res[0]['email']]],
                            ["customer" => [$user_res[0]['mobile']]],
                            ["users.id" => $user_id]
                        ));
                        update_wallet_balance('credit', $user_id, $returnable_amount, 'Refund Amount Credited for Order Item ID  : ' . $id);
                        if ($wallet_balance != 0) {
                            $wallet_balance = $wallet_balance >= $returnable_amount ? $wallet_balance - $returnable_amount : 0;
                        }
                        $total = $total - $returnable_amount < 0 ? 0 : $total - $returnable_amount;
                        $final_total = $final_total - $returnable_amount < 0 ? 0 : $final_total - $returnable_amount;
                        $total_payable = $total_payable - $returnable_amount < 0 ? 0 : $total_payable - $returnable_amount;
                    } else {
                        if ($current_price <=  $wallet_balance) {
                            if ((($order_counter == $order_cancel_counter && $status == 'cancelled') ||  ($order_counter == $order_return_counter && $status == 'returned')) && $is_delivery_charge_returnable == 1) {
                                $returnable_amount = $current_price - $promo_discount + $delivery_charge;
                            } else {
                                $returnable_amount = $current_price - $promo_discount;
                            }
                            //send custom notifications
                            $custom_notification = fetch_details('custom_notifications', ['type' => "wallet_transaction"], '');
                            $hashtag_currency = '< currency >';
                            $hashtag_returnable_amount = '< returnable_amount >';
                            $string = json_encode($custom_notification[0]['message'], JSON_UNESCAPED_UNICODE);
                            $hashtag = html_entity_decode($string);
                            $data = str_replace(array($hashtag_currency, $hashtag_returnable_amount), array($currency, $returnable_amount), $hashtag);
                            $message = output_escaping(trim($data, '"'));
                            $fcmMsg = array(
                                'title' => (!empty($custom_notification)) ? $custom_notification[0]['title'] : "Amount Credited To Wallet",
                                'body' => (!empty($custom_notification)) ? $message : $currency . $returnable_amount,
                                'type' => "wallet",
                            );
                            send_notification($fcmMsg, $fcm_ids);
                            (notify_event(
                                "wallet_transaction",
                                ["customer" => [$user_res[0]['email']]],
                                ["customer" => [$user_res[0]['mobile']]],
                                ["users.id" => $user_id]
                            ));
                            update_wallet_balance('credit', $user_id, $returnable_amount, 'Wallet Amount Credited for Order Item ID  : ' . $id);

                            if ($wallet_balance != 0) {
                                $wallet_balance = $wallet_balance >= $returnable_amount ? $wallet_balance - $returnable_amount : 0;
                            }
                            $total = $total - $returnable_amount < 0 ? 0 : $total - $returnable_amount;
                            $final_total = $final_total - $returnable_amount < 0 ? 0 : $final_total - $returnable_amount;
                            $total_payable = $total_payable - $returnable_amount < 0 ? 0 : $total_payable - $returnable_amount;
                        } else {
                            if ($wallet_balance > 0) {
                                if ($wallet_balance <= $current_price) {
                                    $returnable_amount = $wallet_balance;
                                    //send custom notifications
                                    $custom_notification = fetch_details('custom_notifications', ['type' => "wallet_transaction"], '');
                                    $hashtag_currency = '< currency >';
                                    $hashtag_returnable_amount = '< returnable_amount >';
                                    $string = json_encode($custom_notification[0]['message'], JSON_UNESCAPED_UNICODE);
                                    $hashtag = html_entity_decode($string);
                                    $data = str_replace(array($hashtag_currency, $hashtag_returnable_amount), array($currency, $returnable_amount), $hashtag);
                                    $message = output_escaping(trim($data, '"'));
                                    $fcmMsg = array(
                                        'title' => (!empty($custom_notification)) ? $custom_notification[0]['title'] : "Amount Credited To Wallet",
                                        'body' => (!empty($custom_notification)) ? $message : $currency . $returnable_amount,
                                        'type' => "wallet",
                                    );
                                    send_notification($fcmMsg, $fcm_ids);
                                    (notify_event(
                                        "wallet_transaction",
                                        ["customer" => [$user_res[0]['email']]],
                                        ["customer" => [$user_res[0]['mobile']]],
                                        ["users.id" => $user_id]
                                    ));
                                    update_wallet_balance('credit', $user_id, $returnable_amount, 'Wallet Amount Credited for Order Item ID  : ' . $id);
                                    $wallet_balance = 0;
                                    $total = $total - $current_price < 0 ? 0 : $total - $current_price;
                                    $final_total = $final_total - $current_price < 0 ? 0 : $final_total - $current_price;
                                    $total_payable = $total_payable - $current_price < 0 ? 0 : $total_payable - $current_price;
                                } else {
                                    $returnable_amount = $current_price;
                                    //send custom notifications
                                    $custom_notification = fetch_details('custom_notifications', ['type' => "wallet_transaction"], '');
                                    $hashtag_currency = '< currency >';
                                    $hashtag_returnable_amount = '< returnable_amount >';
                                    $string = json_encode($custom_notification[0]['message'], JSON_UNESCAPED_UNICODE);
                                    $hashtag = html_entity_decode($string);
                                    $data = str_replace(array($hashtag_currency, $hashtag_returnable_amount), array($currency, $returnable_amount), $hashtag);
                                    $message = output_escaping(trim($data, '"'));
                                    $fcmMsg = array(
                                        'title' => (!empty($custom_notification)) ? $custom_notification[0]['title'] : "Amount Credited To Wallet",
                                        'body' => (!empty($custom_notification)) ? $message : $currency . $returnable_amount,
                                        'type' => "wallet",
                                    );
                                    send_notification($fcmMsg, $fcm_ids);
                                    (notify_event(
                                        "wallet_transaction",
                                        ["customer" => [$user_res[0]['email']]],
                                        ["customer" => [$user_res[0]['mobile']]],
                                        ["users.id" => $user_id]
                                    ));
                                    update_wallet_balance('credit', $user_id, $returnable_amount, 'Wallet Amount Credited for Order Item ID  : ' . $id);
                                    $wallet_balance = $wallet_balance - $returnable_amount >= 0 ? $wallet_balance - $returnable_amount : 0;
                                    $total = $total - $returnable_amount < 0 ? 0 : $total - $returnable_amount;
                                    $final_total = $final_total - $returnable_amount < 0 ? 0 : $final_total - $returnable_amount;
                                    $total_payable = $total_payable - $returnable_amount < 0 ? 0 : $total_payable - $returnable_amount;
                                }
                            } else {
                                $total = $total - $current_price < 0 ? 0 : $total - $current_price;
                                $final_total = $final_total - $current_price < 0 ? 0 : $final_total - $current_price;
                                $total_payable = $total_payable - $current_price < 0 ? 0 : $total_payable - $current_price;
                            }
                        }
                    }
                } else {

                    if (trim(strtolower($payment_method)) != 'cod') {
                        if ((($order_counter == $order_cancel_counter && $status == 'cancelled') ||  ($order_counter == $order_return_counter && $status == 'returned')) && $is_delivery_charge_returnable == 1) {
                            $returnable_amount = $current_price - $promo_discount + $delivery_charge;
                        } else {
                            $returnable_amount = $current_price - $promo_discount;
                        }
                        //send custom notifications
                        $custom_notification = fetch_details('custom_notifications', ['type' => "wallet_transaction"], '');
                        $hashtag_currency = '< currency >';
                        $hashtag_returnable_amount = '< returnable_amount >';
                        $string = json_encode($custom_notification[0]['message'], JSON_UNESCAPED_UNICODE);
                        $hashtag = html_entity_decode($string);
                        $data = str_replace(array($hashtag_currency, $hashtag_returnable_amount), array($currency, $returnable_amount), $hashtag);
                        $message = output_escaping(trim($data, '"'));
                        $fcmMsg = array(
                            'title' => (!empty($custom_notification)) ? $custom_notification[0]['title'] : "Amount Credited To Wallet",
                            'body' => (!empty($custom_notification)) ? $message : $currency . $returnable_amount,
                            'type' => "wallet",
                        );
                        send_notification($fcmMsg, $fcm_ids);
                        (notify_event(
                            "wallet_transaction",
                            ["customer" => [$user_res[0]['email']]],
                            ["customer" => [$user_res[0]['mobile']]],
                            ["users.id" => $user_id]
                        ));
                        update_wallet_balance('credit', $user_id, $returnable_amount, 'Refund Amount Credited for Order Item ID  : ' . $id);
                        if ($wallet_balance != 0) {
                            $wallet_balance = $wallet_balance >= $returnable_amount ? $wallet_balance - $returnable_amount : 0;
                        }
                        $total = $total - $returnable_amount < 0 ? 0 : $total - $returnable_amount;
                        $final_total = $final_total - $returnable_amount < 0 ? 0 : $final_total - $returnable_amount;
                        $total_payable = $total_payable - $returnable_amount < 0 ? 0 : $total_payable - $returnable_amount;
                    } else {

                        if ($current_price <=  $wallet_balance) {
                            if ($wallet_balance > 0) {
                                if ($wallet_balance <= $current_price) {
                                    $returnable_amount = $wallet_balance;
                                    //send custom notifications
                                    $custom_notification = fetch_details('custom_notifications', ['type' => "wallet_transaction"], '');
                                    $hashtag_currency = '< currency >';
                                    $hashtag_returnable_amount = '< returnable_amount >';
                                    $string = json_encode($custom_notification[0]['message'], JSON_UNESCAPED_UNICODE);
                                    $hashtag = html_entity_decode($string);
                                    $data = str_replace(array($hashtag_currency, $hashtag_returnable_amount), array($currency, $returnable_amount), $hashtag);
                                    $message = output_escaping(trim($data, '"'));
                                    $fcmMsg = array(
                                        'title' => (!empty($custom_notification)) ? $custom_notification[0]['title'] : "Amount Credited To Wallet",
                                        'body' => (!empty($custom_notification)) ? $message : $currency . $returnable_amount,
                                        'type' => "wallet",
                                    );
                                    send_notification($fcmMsg, $fcm_ids);
                                    (notify_event(
                                        "wallet_transaction",
                                        ["customer" => [$user_res[0]['email']]],
                                        ["customer" => [$user_res[0]['mobile']]],
                                        ["users.id" => $user_id]
                                    ));
                                    update_wallet_balance('credit', $user_id, $returnable_amount, 'Wallet Amount Credited for Order Item ID  : ' . $id);

                                    $wallet_balance = 0;
                                    $total = $total - $returnable_amount < 0 ? 0 : $total - $returnable_amount;
                                    $final_total = $final_total - $returnable_amount < 0 ? 0 : $final_total - $returnable_amount;
                                    $total_payable = $total_payable - $returnable_amount < 0 ? 0 : $total_payable - $returnable_amount;
                                } else {
                                    $returnable_amount = $current_price;
                                    //send custom notifications
                                    $custom_notification = fetch_details('custom_notifications', ['type' => "wallet_transaction"], '');
                                    $hashtag_currency = '< currency >';
                                    $hashtag_returnable_amount = '< returnable_amount >';
                                    $string = json_encode($custom_notification[0]['message'], JSON_UNESCAPED_UNICODE);
                                    $hashtag = html_entity_decode($string);
                                    $data = str_replace(array($hashtag_currency, $hashtag_returnable_amount), array($currency, $returnable_amount), $hashtag);
                                    $message = output_escaping(trim($data, '"'));
                                    $fcmMsg = array(
                                        'title' => (!empty($custom_notification)) ? $custom_notification[0]['title'] : "Amount Credited To Wallet",
                                        'body' => (!empty($custom_notification)) ? $message : $currency . $returnable_amount,
                                        'type' => "wallet",
                                    );
                                    send_notification($fcmMsg, $fcm_ids);
                                    (notify_event(
                                        "wallet_transaction",
                                        ["customer" => [$user_res[0]['email']]],
                                        ["customer" => [$user_res[0]['mobile']]],
                                        ["users.id" => $user_id]
                                    ));
                                    update_wallet_balance('credit', $user_id, $returnable_amount, 'Wallet Amount Credited for Order Item ID  : ' . $id);

                                    $wallet_balance = $wallet_balance - $returnable_amount >= 0 ? $wallet_balance - $returnable_amount : 0;
                                    $total = $total - $returnable_amount < 0 ? 0 : $total - $returnable_amount;
                                    $final_total = $final_total - $returnable_amount < 0 ? 0 : $final_total - $returnable_amount;
                                    $total_payable = $total_payable - $returnable_amount < 0 ? 0 : $total_payable - $returnable_amount;
                                }
                            } else {
                                $total = $total - $current_price < 0 ? 0 : $total - $current_price;
                                $final_total = $final_total - $current_price < 0 ? 0 : $final_total - $current_price;
                                $total_payable = $total_payable - $current_price < 0 ? 0 : $total_payable - $current_price;
                            }
                        } else {
                            $total = $total - $current_price < 0 ? 0 : $total - $current_price;
                            $final_total = $final_total - $current_price < 0 ? 0 : $final_total - $current_price;
                            $total_payable = $total_payable - $current_price < 0 ? 0 : $total_payable - $current_price;
                        }
                    }
                }
            }
            $system_settings = get_settings('system_settings', true);
            $min_amount = $system_settings['min_amount'];
            if ((isset($system_settings['area_wise_delivery_charge']) && !empty($system_settings['area_wise_delivery_charge']))) {
                if (isset($order_details[0]['address_id']) && !empty($order_details[0]['address_id'])) {
                    $address = fetch_details('addresses', ['id' => $order_details[0]['address_id']], 'area_id');
                    if (isset($address[0]['area_id']) && !empty($address[0]['area_id'])) {
                        $area = fetch_details('areas', ['id' => $address[0]['area_id']], 'minimum_free_delivery_order_amount');
                        if (isset($area[0]['minimum_free_delivery_order_amount'])) {
                            $min_amount = $area[0]['minimum_free_delivery_order_amount'];
                        }
                    }
                }
            }
            if ($total < $min_amount) {
                if ($delivery_charge == 0) {
                    if (isset($order_details[0]['address_id']) && !empty($order_details[0]['address_id'])) {
                        $d_charge = get_delivery_charge($order_details[0]['address_id']);
                    } else {
                        $d_charge = $system_settings['delivery_charge'];
                    }
                    $delivery_charge = $d_charge;
                    $final_total += $d_charge;
                    $total_payable += $d_charge;
                }
            }

            if ($total == 0) {
                $total = $wallet_balance = $delivery_charge = $final_total = $total_payable = 0;
            }

            $set =  [
                'total' => $total,
                'final_total' => $final_total,
                'total_payable' => $total_payable,
                'delivery_charge' => $delivery_charge,
                'wallet_balance' => $wallet_balance
            ];

            update_details($set, ['id' => $order_id], 'orders');

            $response['error'] = false;
            $response['message'] = 'Status Updated Successfully';
            $response['data'] = array();
            return $response;
        }
    }
}

/**
 * Resolves a banner row (slider / offer) to the storefront URL it should link to.
 *
 * Returns the URL string, or FALSE when the banner points at something a visitor cannot
 * actually reach - a category or product that has since been deleted, deactivated, pulled from
 * listing, or whose seller is no longer approved. get_sliders()/get_offers() previously looked
 * the target up by id ONLY, with no status conditions at all, and on a miss just left the link
 * empty: the banner still rendered on the homepage hero and clicking it silently reloaded the
 * current page. Callers now drop those rows instead of shipping a dead banner.
 *
 * The product conditions deliberately mirror fetch_product()'s visibility filter
 * (p.status / p.listing_visibility / seller_data.status) so a banner can never advertise a
 * product that the shop itself refuses to show.
 *
 * @param  string $type    slider/offer type: categories | products | slider_url | offer_url
 * @param  mixed  $type_id target row id (ignored for the *_url types)
 * @param  string $raw_link admin-entered URL, used by the *_url types
 * @return string|false
 */
function resolve_banner_target($type, $type_id, $raw_link = '')
{
    $ci = &get_instance();
    $type = (string) $type;

    if ($type === 'categories') {
        $row = $ci->db->select('slug')->where('id', $type_id)->where('status', 1)
            ->get('categories')->row_array();
        return (!empty($row['slug'])) ? base_url('products/category/' . $row['slug']) : false;
    }

    if ($type === 'products') {
        $row = $ci->db->select('p.slug')
            ->join('seller_data sd', 'sd.user_id = p.seller_id', 'inner')
            ->where('p.id', $type_id)
            ->where('p.status', 1)
            ->where('p.listing_visibility', 1)
            ->where('sd.status', 1)
            ->get('products p')->row_array();
        return (!empty($row['slug'])) ? base_url('products/details/' . $row['slug']) : false;
    }

    if ($type === 'slider_url' || $type === 'offer_url') {
        // html_escape() because this one is admin-entered free text and gets rendered straight
        // into an href at the storefront; the branches above build their URLs server-side.
        return (trim((string) $raw_link) !== '') ? html_escape($raw_link) : false;
    }

    // 'default' and anything unrecognised: a plain image banner with nowhere to go. Keep it,
    // with no link - that is a legitimate configuration, unlike a broken target above.
    return '';
}

function get_sliders($id = '', $type = '', $type_id = '')
{
    $ci = &get_instance();
    if (!empty($id)) {
        $ci->db->where('id', $id);
    }
    if (!empty($type)) {
        $ci->db->where('type', $type);
    }
    if (!empty($type_id)) {
        $ci->db->where('type_id', $type_id);
    }
    $res = $ci->db->get('sliders')->result_array();

    $sliders = [];
    foreach ($res as $d) {
        // See resolve_banner_target(): FALSE means this slider advertises a category/product
        // that is deleted, deactivated, unlisted or belongs to an unapproved seller, or a URL
        // slider with no URL. Such a slider used to render on the hero with an empty href and
        // do nothing on click - drop it rather than show a dead banner.
        $link = resolve_banner_target($d['type'], $d['type_id'], $d['link']);
        if ($link === false) {
            continue;
        }
        $d['link'] = $link;
        $sliders[] = $d;
    }
    return $sliders;
}

function get_offers($id = '', $type = '', $type_id = '')
{
    $ci = &get_instance();
    if (!empty($id)) {
        $ci->db->where('id', $id);
    }
    if (!empty($type)) {
        $ci->db->where('type', $type);
    }
    if (!empty($type_id)) {
        $ci->db->where('type_id', $type_id);
    }
    $res = $ci->db->get('offers')->result_array();

    $offers = [];
    foreach ($res as $d) {
        // Same treatment as get_sliders() above - this function is a copy of it.
        $link = resolve_banner_target($d['type'], $d['type_id'], $d['link']);
        if ($link === false) {
            continue;
        }
        $d['link'] = $link;
        $offers[] = $d;
    }
    return $offers;
}
function get_cart_count($user_id)
{
    $ci = &get_instance();
    if (!empty($user_id)) {
        $ci->db->where('user_id', $user_id);
    }
    $ci->db->where('qty !=', 0);
    $ci->db->where('is_saved_for_later =', 0);
    $ci->db->distinct();
    $ci->db->select('count(id) as total');
    $res = $ci->db->get('cart')->result_array();
    return $res;
}
function is_variant_available_in_cart($product_variant_id, $user_id)
{
    $ci = &get_instance();
    $ci->db->where('product_variant_id', $product_variant_id);
    $ci->db->where('user_id', $user_id);
    $ci->db->where('qty !=', 0);
    $ci->db->where('is_saved_for_later =', 0);
    $ci->db->select('id');
    $res = $ci->db->get('cart')->result_array();
    if (!empty($res[0]['id'])) {
        return true;
    } else {
        return false;
    }
}
function get_user_balance($user_id)
{
    $ci = &get_instance();
    $ci->db->where('id', $user_id);
    $ci->db->select('balance');
    $res = $ci->db->get('users')->result_array();
    if (!empty($res[0]['balance'])) {
        return $res[0]['balance'];
    } else {
        return "0";
    }
}

function get_stock($id, $type)
{
    $t = &get_instance();
    /*
     * PERFORMANCE: served from the batch prefetch when one is open. The buckets
     * hold the stock value directly rather than a result set, so a hit is checked
     * with array_key_exists on the id - an id that exists with a NULL stock is
     * still a hit, and returns NULL, which is what the original returned for it.
     */
    if (Product_batch::is_open()) {
        $bucket = ($type == 'variant') ? Product_batch::$stock_variant : Product_batch::$stock_product;
        if (array_key_exists((string) $id, $bucket)) {
            return $bucket[(string) $id];
        }
    }
    $t->db->where('id', $id);
    if ($type == 'variant') {
        $response = $t->db->select('stock')->get('product_variants')->result_array();
    } else {
        $response = $t->db->select('stock')->get('products')->result_array();
    }
    $stock = isset($response[0]['stock']) ? $response[0]['stock'] : null;
    return $stock;
}
/**
 * Is the marketplace on the seller-paid shipping model?
 *
 * Under it the customer is never charged freight - every order ships free - and the ACTUAL
 * courier charge Shiprocket bills is recovered from the seller's settlement instead. Sellers
 * price their own shipping into `price` / `special_price`.
 *
 * Defaults to ON when the key is absent, which is the case only between deploying this code
 * and running migration 070. That is the intended behaviour, not a fallback: an install that
 * has not been configured must still ship free rather than quietly charging customers freight
 * the settlement engine is simultaneously recovering from the seller - which would collect it
 * twice.
 */
function seller_paid_shipping_enabled()
{
    $settings = get_settings('system_settings', true);
    if (!isset($settings['seller_paid_shipping']) || $settings['seller_paid_shipping'] === '') {
        return true;
    }
    return ($settings['seller_paid_shipping'] == '1');
}

/**
 * Pull the freight Shiprocket actually billed out of one of its responses.
 *
 * The figure appears under a different key and at a different depth depending on which
 * endpoint answered - `freight_charges` inside `response.data` on an AWB assignment, `charges`
 * or `freight_charges` on a shipment record - so this walks the whole body for the first
 * recognised key rather than hard-coding one path and silently recording 0 when Shiprocket
 * changes shape.
 *
 * Deliberately does NOT accept `rate`. That is the serviceability QUOTE - an estimate made
 * before the parcel was weighed - and recording an estimate in a column the settlement engine
 * debits sellers from would charge them a number no courier ever billed.
 *
 * @return float|null  null when the response carries no freight figure at all.
 */
function shiprocket_freight_from_response($response)
{
    if (!is_array($response)) {
        return null;
    }

    $keys = ['freight_charges', 'freight_charge', 'shipping_charges', 'charges'];
    $found = null;

    $walk = function ($node) use (&$walk, $keys, &$found) {
        if ($found !== null || !is_array($node)) {
            return;
        }
        foreach ($keys as $key) {
            if (isset($node[$key]) && is_numeric($node[$key])) {
                $found = (float) $node[$key];
                return;
            }
        }
        foreach ($node as $value) {
            if (is_array($value)) {
                $walk($value);
                if ($found !== null) {
                    return;
                }
            }
        }
    };
    $walk($response);

    // A negative freight is meaningless and would CREDIT the seller at settlement.
    if ($found !== null && $found < 0) {
        log_message('error', 'shiprocket_freight_from_response: refusing negative freight ' . $found);
        return null;
    }

    return $found;
}

/**
 * Record what the courier actually charged for a shipment, and spread it over the order items
 * that shipment carries so the settlement engine can recover it per item.
 *
 * Under the seller-paid shipping model this is the only place the real freight enters the
 * system. Three writes, all recomputed from scratch on every call so a re-capture (a retry, a
 * reconciliation sweep re-reading the same shipment, an admin correcting the figure) overwrites
 * rather than accumulates:
 *
 *   - `order_tracking.freight_charge`   the parcel's billed freight, with its provenance
 *   - `order_items.shipping_deduction`  that freight apportioned across the parcel's items
 *   - `order_charges.freight_charge`    the seller's total for the order, re-summed from the above
 *
 * Apportionment is pro-rata by the item's own `sub_total` - a 2000 item in a parcel with a 500
 * one carries four fifths of the freight - falling back to an equal split when every line is
 * zero-valued (a fully discounted or free item). Any rounding remainder lands on the last item
 * so the apportioned figures always sum back to exactly what the courier billed.
 *
 * REVERSE shipments are recorded but never apportioned. A return leg's freight is a separate
 * commercial question (who pays for a return depends on why it was returned) and deducting it
 * here would silently charge the seller for a return the platform had accepted responsibility
 * for. The figure is stored so that decision can be made from real numbers later.
 *
 * @param  int    $shipment_id  Shiprocket shipment id, as held on order_tracking
 * @param  float  $freight      amount the courier billed
 * @param  string $source       awb_assignment | reconciliation | manual
 * @return array  ['error' => bool, 'message' => string, 'data' => [...]]
 */
function record_shiprocket_freight($shipment_id, $freight, $source = 'awb_assignment')
{
    $t = &get_instance();
    $shipment_id = (int) $shipment_id;

    if ($shipment_id <= 0 || !is_numeric($freight) || (float) $freight < 0) {
        return ['error' => true, 'message' => 'A shipment id and a non-negative freight amount are required.', 'data' => []];
    }
    $freight = round((float) $freight, 2);

    // Guarded: this runs from the AWB path on every install, including ones that have the code
    // but have not run migration 070 yet. Without the check that path would fatal on an
    // unknown column and take the AWB generation down with it.
    if (!$t->db->field_exists('freight_charge', 'order_tracking')) {
        log_message('error', 'record_shiprocket_freight: order_tracking.freight_charge is missing - run the pending migrations.');
        return ['error' => true, 'message' => 'Freight capture is not available until the pending database migrations are applied.', 'data' => []];
    }

    $tracking = fetch_details('order_tracking', ['shipment_id' => $shipment_id], 'id,order_id,order_item_id,is_return');
    if (empty($tracking)) {
        log_message('error', 'record_shiprocket_freight: no order_tracking row for shipment ' . $shipment_id);
        return ['error' => true, 'message' => 'No shipment is recorded against that Shiprocket shipment id.', 'data' => []];
    }
    $tracking = $tracking[0];

    $t->db->set([
        'freight_charge'        => $freight,
        'freight_charge_source' => $source,
        'freight_captured_at'   => date('Y-m-d H:i:s'),
    ])->where('shipment_id', $shipment_id)->update('order_tracking');

    if (!empty($tracking['is_return'])) {
        return [
            'error'   => false,
            'message' => 'Return-leg freight recorded. It is not deducted from the seller.',
            'data'    => ['freight' => $freight, 'apportioned' => []],
        ];
    }

    if (!$t->db->field_exists('shipping_deduction', 'order_items')) {
        log_message('error', 'record_shiprocket_freight: order_items.shipping_deduction is missing - run the pending migrations.');
        return ['error' => true, 'message' => 'Freight apportionment is not available until the pending database migrations are applied.', 'data' => []];
    }

    // order_tracking.order_item_id holds a COMMA LIST of the items in the parcel, which is how
    // create_shiprocket_forward_shipment() writes it. array_filter drops the empties a stray
    // trailing comma would otherwise turn into `WHERE id IN ('')`.
    $item_ids = array_values(array_filter(array_map('intval', explode(',', (string) $tracking['order_item_id']))));
    if (empty($item_ids)) {
        log_message('error', 'record_shiprocket_freight: shipment ' . $shipment_id . ' names no order items, so its freight of '
            . $freight . ' could not be apportioned.');
        return ['error' => true, 'message' => 'That shipment names no order items, so its freight cannot be apportioned.', 'data' => ['freight' => $freight]];
    }

    $items = $t->db->select('id, seller_id, sub_total')
        ->where_in('id', $item_ids)
        ->order_by('id', 'ASC')
        ->get('order_items')->result_array();
    if (empty($items)) {
        log_message('error', 'record_shiprocket_freight: none of the order items named by shipment ' . $shipment_id . ' still exist.');
        return ['error' => true, 'message' => 'The order items on that shipment no longer exist.', 'data' => ['freight' => $freight]];
    }

    $basis = 0.0;
    foreach ($items as $item) {
        $basis += max(0, (float) $item['sub_total']);
    }

    $apportioned = [];
    $running = 0.0;
    $last = count($items) - 1;
    foreach ($items as $i => $item) {
        if ($i === $last) {
            // Remainder, so the parts always add back up to the billed freight exactly.
            $share = round($freight - $running, 2);
        } elseif ($basis > 0) {
            $share = round($freight * (max(0, (float) $item['sub_total']) / $basis), 2);
        } else {
            $share = round($freight / count($items), 2);
        }
        $share = ($share < 0) ? 0 : $share;
        $running += $share;

        $t->db->set('shipping_deduction', $share)->where('id', $item['id'])->update('order_items');
        $apportioned[(int) $item['id']] = $share;
    }

    /*
     * Re-sum the seller's parcel total from the items rather than adding this shipment's
     * freight to whatever was there. A seller shipping from two pickup locations has two
     * shipments but only ONE order_charges row, so an additive update would double-count the
     * second capture of either one.
     */
    if ($t->db->field_exists('freight_charge', 'order_charges')) {
        $sellers = array_unique(array_map(function ($item) {
            return (int) $item['seller_id'];
        }, $items));
        foreach ($sellers as $seller_id) {
            $total = $t->db->select('COALESCE(SUM(shipping_deduction), 0) as freight', false)
                ->where('order_id', (int) $tracking['order_id'])
                ->where('seller_id', $seller_id)
                ->get('order_items')->row_array();
            $t->db->set('freight_charge', round((float) $total['freight'], 2))
                ->where('order_id', (int) $tracking['order_id'])
                ->where('seller_id', $seller_id)
                ->update('order_charges');
        }
    }

    return [
        'error'   => false,
        'message' => 'Freight recorded and apportioned across the parcel.',
        'data'    => ['freight' => $freight, 'apportioned' => $apportioned],
    ];
}

function get_delivery_charge($address_id, $total = 0)
{
    $t = &get_instance();

    // Seller-paid shipping: the customer is never quoted freight. Returned from the top so
    // every caller - the web checkout, the mobile cart, the POS screen - is free-shipping
    // without each one having to know about the model. The real freight is captured from
    // Shiprocket at AWB time and recovered from the seller at settlement instead.
    if (seller_paid_shipping_enabled()) {
        return number_format(0, 2);
    }

    $total = str_replace(',', '', $total);
    $system_settings = get_settings('system_settings', true);
    $address = fetch_details('addresses', ['id' => $address_id], 'area_id,pincode,city_id');
    $min_amount = $system_settings['min_amount'];
    $delivery_charge = $system_settings['delivery_charge'];
    if ((isset($system_settings['area_wise_delivery_charge']) && !empty($system_settings['area_wise_delivery_charge']))) {
        if ((isset($address[0]['area_id']) && !empty($address[0]['area_id'])) || (isset($address[0]['pincode']) && !empty($address[0]['pincode']))) {
            $area = fetch_details('areas', ['id' => $address[0]['area_id']], 'delivery_charges,minimum_free_delivery_order_amount');
            if ($t->db->field_exists('delivery_charges', 'zipcodes') && $t->db->field_exists('minimum_free_delivery_order_amount', 'zipcodes')) {
                $zipcode = fetch_details('zipcodes', ['zipcode' => $address[0]['pincode'], 'city_id' => $address[0]['city_id']], 'delivery_charges,minimum_free_delivery_order_amount');
            }
            if (isset($area[0]['minimum_free_delivery_order_amount']) || isset($zipcode[0]['minimum_free_delivery_order_amount'])) {
                $min_amount = isset($area[0]['minimum_free_delivery_order_amount']) && !empty($area[0]['minimum_free_delivery_order_amount']) ? $area[0]['minimum_free_delivery_order_amount'] : $zipcode[0]['minimum_free_delivery_order_amount'];
                $delivery_charge = isset($area[0]['delivery_charges']) && !empty($area[0]['delivery_charges']) ? $area[0]['delivery_charges'] : $zipcode[0]['delivery_charges'];
            }
        }
    }
    if ($total < $min_amount || $total = 0) {
        $d_charge = $delivery_charge;
    } else {
        $d_charge = 0;
    }

    return number_format($d_charge, 2);
}
function validate_otp($otp, $order_item_id = NULL, $order_id = NULL, $seller_id = NULL)
{
    $res = fetch_details('order_items', ['id' => $order_item_id], 'otp');
    $order_res = fetch_details('order_charges', ['order_id' => $order_id, 'seller_id' => $seller_id], 'otp');

    if (($res[0]['otp'] != 0 && $res[0]['otp'] == $otp) || ($order_res[0]['otp'] != 0 && $order_res[0]['otp'] == $otp)) {
        return true;
    } else {
        return false;
    }
}

function is_product_delivarable($type, $type_id, $product_id)
{
    $ci = &get_instance();

    if ($type == 'zipcode') {
        $zipcode_id = $type_id;
    } else if ($type == 'area') {
        $res = fetch_details('areas', ['id' => $type_id], 'zipcode_id');
        $zipcode_id = $res[0]['zipcode_id'];
    } else {
        return false;
    }

    $zipcode_id = (int) $zipcode_id;
    $product_id = (int) $product_id;

    if ($zipcode_id > 0 && $product_id > 0) {
        /*
         * Both ids were interpolated straight into the SQL - FIND_IN_SET('$zipcode_id', ...) and
         * where("id = $product_id") - with no binding or casting. $product_id reaches this from
         * request data on three call paths (Products::..., and two app API endpoints). Cast to
         * int above and bound below.
         *
         * deliverable_type vocabulary, from config/constants.php:
         *   0 NONE     - not deliverable anywhere (correctly matches nothing here)
         *   1 ALL      - deliverable everywhere
         *   2 INCLUDED - deliverable only to the listed zipcodes
         *   3 EXCLUDED - deliverable everywhere except the listed zipcodes
         * Note: 3 products on this database store deliverable_type = 4, which is not a defined
         * value, so they match no branch and are treated as undeliverable. That is a data problem
         * to correct on those products, not something to guess at here.
         */
        $ci->db->select('id');
        $ci->db->group_Start();
        $ci->db->where("((deliverable_type = '2' AND FIND_IN_SET(" . $zipcode_id . ", deliverable_zipcodes)) OR deliverable_type = '1') OR (deliverable_type = '3' AND NOT FIND_IN_SET(" . $zipcode_id . ", deliverable_zipcodes))", null, false);
        $ci->db->group_End();
        $ci->db->where('id', $product_id);
        $product = $ci->db->get('products')->num_rows();

        return ($product > 0);
    }

    return false;
}

function check_cart_products_delivarable($user_id, $area_id = 0, $zipcode = "", $zipcode_id = "")
{
    $t = &get_instance();
    $products = $tmpRow = array();
    $cart = get_cart_total($user_id);

    $settings = get_settings('shipping_method', true);
    // get_cart_total() always returns its summary keys (sub_total, total_arr, ...), so
    // !empty($cart) is true even for an EMPTY cart - but the numeric row $cart[0] only
    // exists when there is at least one item. Viewing the cart with nothing in it
    // therefore read cart_count off a missing row. Check the row itself.
    if (!empty($cart) && isset($cart[0]['cart_count'])) {
        $product_weight = 0;
        for ($i = 0; $i < $cart[0]['cart_count']; $i++) {
            /*
             * $tmpRow is reused for every cart item and only two of its keys were reset at the
             * top of the loop. `message`, `estimate_date` and `is_valid_wight` were not - so an
             * item that produced no message of its own inherited the PREVIOUS item's, and the
             * cart could tell a customer "Product is deliverable by <date>" about an item that
             * had never been checked, or carry a stale "You cannot ship weight more then 15 KG"
             * onto an item well under the limit.
             */
            $tmpRow = [];
            /* check in local shipping first */
            $tmpRow['is_deliverable'] = false;
            $tmpRow['delivery_by'] = "";

            if (isset($settings['local_shipping_method']) && $settings['local_shipping_method'] == 1) {
                $tmpRow['is_deliverable'] = (!empty($zipcode_id) && $zipcode_id > 0) ?
                    is_product_delivarable('zipcode', $zipcode_id, $cart[$i]['product_id'])
                    : false;
                $tmpRow['delivery_by'] = (isset($tmpRow['is_deliverable']) && $tmpRow['is_deliverable']) ? "local" : "";
            }

            /* check in standard shipping then */
            if (isset($settings['shiprocket_shipping_method']) && $settings['shiprocket_shipping_method'] == 1) {
                /*
                 * The gate here used to be `trim($cart[$i]['pickup_location']) != ""`, i.e. the
                 * product had to name a pickup address itself. 278 of 290 live products leave that
                 * column blank, so this whole block was skipped for them and they fell out with
                 * is_deliverable = false and no message - the customer was told their address was
                 * not serviceable when the real problem was that nobody had typed a nickname into
                 * a product field. resolve_seller_pickup_location() falls back to the seller's own
                 * registered pickup address, which is what actually determines where a parcel is
                 * collected from.
                 */
                $pickup = resolve_seller_pickup_location($cart[$i]['pickup_location'], $cart[$i]['seller_id']);

                if (!$tmpRow['is_deliverable'] && empty($pickup)) {
                    // Genuinely nothing to ship from: this seller has registered no pickup address
                    // at all. Say so instead of leaving a silent false - it is the seller's or the
                    // admin's problem to fix, and it used to be invisible to both.
                    $tmpRow['message'] = 'This seller has not set up a pickup address yet, so delivery cannot be quoted.';
                }

                if (!$tmpRow['is_deliverable'] && !empty($pickup)) {

                    $t->load->library(['Shiprocket']);
                    $pickup_pincode = [['pin_code' => $pickup['pin_code']]];

                    // $product_weight += $cart[$i]['weight'] * $cart[$i]['qty']; // This sums up the total weight of the products in the cart and checks the total for the limit.
                    // Modified so that each individual item shall be within the weight limit.
                    // (previously the total of the items needed to be within the weight limit)
                    //
                    // Passed straight through before, so a product with weight 0 - 266 of the 299
                    // variants on this store - sent weight="" and Shiprocket answered
                    // "Weight Required" with no couriers. The customer saw that as their address
                    // being unserviceable. Nominal fallback, same as the return path already used.
                    $product_weight = shiprocket_parcel_weight($cart[$i]['weight']);

                    if (isset($zipcode) && !empty($zipcode)) {

                        if ($product_weight > 15) {
                            $tmpRow['is_deliverable'] = false;
                            $tmpRow['is_valid_wight'] = 0;
                            $tmpRow['message'] = "You cannot ship weight more then 15 KG";
                        } else {
                            $availibility_data = [
                                'pickup_postcode' => (isset($pickup_pincode[0]['pin_code']) && !empty($pickup_pincode[0]['pin_code'])) ? $pickup_pincode[0]['pin_code'] : "",
                                'delivery_postcode' => $zipcode,
                                'cod' => 0,
                                'weight' => $product_weight,
                            ];

                            $check_deliveribility = $t->shiprocket->check_serviceability($availibility_data);

                            if (isset($check_deliveribility['status_code']) && $check_deliveribility['status_code'] == 422) {
                                $tmpRow['is_deliverable'] = false;
                                $tmpRow['message'] = "Invalid zipcode supplied!";
                            } else {
                                if (isset($check_deliveribility['status']) && $check_deliveribility['status'] == 200 && !empty($check_deliveribility['data']['available_courier_companies'])) {
                                    $tmpRow['is_deliverable'] = true;
                                    $tmpRow['delivery_by'] = "standard_shipping";
                                    $estimate_date = $check_deliveribility['data']['available_courier_companies'][0]['etd'];
                                    $tmpRow['estimate_date'] = $estimate_date;
                                    $_SESSION['valid_zipcode'] = $zipcode;
                                    $tmpRow['message'] = 'Product is deliverable by ' . $estimate_date;
                                } else {
                                    $tmpRow['is_deliverable'] = false;
                                    // Read unguarded before: a transport failure returns null and
                                    // a rejection does not always carry `message`, so this warned
                                    // "Trying to access array offset on value of type null" and
                                    // showed the customer a blank reason.
                                    //
                                    // Beyond that, "cannot be delivered to this pincode" was told to
                                    // the customer for BOTH real causes: a route no courier serves,
                                    // and Shiprocket never having been reached at all. Those need
                                    // different answers - the first is about their address, the
                                    // second is a configuration fault on our side and no change of
                                    // address will fix it. curl() returns a non-array precisely when
                                    // the call did not complete, and carries the reason on
                                    // last_error(), so separate the two and log the real cause.
                                    if (!is_array($check_deliveribility)) {
                                        $reason = method_exists($t->shiprocket, 'last_error')
                                            ? (string) $t->shiprocket->last_error() : '';
                                        log_message('error', 'Deliverability check could not reach Shiprocket for product '
                                            . $cart[$i]['product_id'] . ' (pickup ' . $availibility_data['pickup_postcode']
                                            . ' -> ' . $zipcode . '): ' . ($reason !== '' ? $reason : 'unknown error'));
                                        $tmpRow['message'] = 'Delivery cannot be checked right now. Please try again shortly.';
                                    } elseif (!empty($check_deliveribility['message'])) {
                                        $tmpRow['message'] = $check_deliveribility['message'];
                                    } else {
                                        $tmpRow['message'] = 'This item cannot be delivered to the selected pincode right now.';
                                    }
                                }
                            }
                        }
                    } else {
                        $tmpRow['is_deliverable'] = false;
                        $tmpRow['message'] = 'Please select zipcode to check the deliveribility of item.';
                    }
                }
            }
            $tmpRow['product_id'] = $cart[$i]['product_id'];
            $tmpRow['variant_id'] = $cart[$i]['id'];
            $tmpRow['name'] = $cart[$i]['name'];
            $products[] = $tmpRow;

            // RESET VARIABLES:
            $tmpRow = [];
        }

        if (!empty($products)) {
            return $products;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function orders_count($status = "", $seller_id = "", $order_type = "")
{

    $t = &get_instance();
    // $t->db->select('count(DISTINCT `order_id`) total');
    // if (!empty($status)) {
    //     $t->db->where('active_status', $status);
    // }
    // if (!empty($seller_id)) {
    //     $t->db->where('seller_id', $seller_id);
    //     // $t->db->where("active_status != 'awaiting' ");
    // }
    // $result = $t->db->from("order_items")->get()->result_array();
    // return $result[0]['total'];
    $where = [];
    $count_res = $t->db->select(' COUNT(distinct oi.order_id) as `total`')
        ->join(' `orders` o', 'o.id= oi.order_id', 'left')
        ->join('product_variants pv', 'pv.id=oi.product_variant_id', 'left')
        ->join('products p', 'pv.product_id=p.id', 'left');

    if (isset($order_type) && $order_type != '' && $order_type == 'digital') {
        $where['p.type'] = 'digital_product';
        $where['oi.active_status'] = $status;
    }
    if (isset($order_type) && $order_type != '' && $order_type == 'simple') {
        $where['p.type!='] = 'digital_product';
        $where['oi.active_status'] = $status;
    }
    if ($order_type == '' && !empty($status)) {
        $where['oi.active_status'] = $status;
    }

    if (!empty($seller_id)) {
        $where['oi.seller_id'] = $seller_id;
        $where['oi.active_status'] != 'awaiting';
    }

    $count_res->where($where);
    $result =  $count_res->get('`order_items` oi')->result_array();
    return $result[0]['total'];
}

function delivery_boy_orders_count($status = "", $delivery_boy_id = "")
{
    $t = &get_instance();
    $t->db->select('count(DISTINCT `order_id`) total');
    if (!empty($status)) {
        $t->db->where('active_status', $status);
    }
    if (!empty($delivery_boy_id)) {
        $t->db->where('delivery_boy_id', $delivery_boy_id);
        // $t->db->where("active_status != 'awaiting' ");
    }
    $result = $t->db->from("order_items")->get()->result_array();
    return $result[0]['total'];
}




function curl($url, $method = 'GET', $data = [], $authorization = "")
{
    $ch = curl_init();
    $curl_options = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded',
        )
    );

    if (!empty($authorization)) {
        $curl_options['CURLOPT_HTTPHEADER'][] = $authorization;
    }

    if (strtolower($method) == 'post') {
        $curl_options[CURLOPT_POST] = 1;
        $curl_options[CURLOPT_POSTFIELDS] = http_build_query($data);
    } else {
        $curl_options[CURLOPT_CUSTOMREQUEST] = 'GET';
    }
    curl_setopt_array($ch, $curl_options);

    $result = array(
        'body' => json_decode(curl_exec($ch), true),
        'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
    );
    return $result;
}

function get_seller_permission($seller_id, $permit = NULL)
{
    $t = &get_instance();
    $seller_id = (isset($seller_id) && !empty($seller_id)) ? $seller_id : $t->session->userdata('user_id');
    $permits = fetch_details('seller_data', ['user_id' => $seller_id], 'permissions');
    if (!empty($permit)) {
        $s_permits = !empty($permits[0]['permissions']) ? json_decode($permits[0]['permissions'], true) : null;
        return isset($s_permits[$permit]) ? $s_permits[$permit] : null;
    } else {
        return !empty($permits[0]['permissions']) ? json_decode($permits[0]['permissions']) : null;
    }
}

/**
 * Context-aware price range for the product-listing price slider.
 *
 * Returns the MIN and MAX "effective price" across the SAME set of products the
 * listing query (fetch_product) returns for the given filters — but EXCLUDING
 * the price filter itself, so the range never collapses to the current
 * selection.
 *
 * Why this exists: get_price() below computes a single GLOBAL min/max across the
 * whole catalog and ignores the category / brand / search / seller context. That
 * made the slider bounds feel "fixed" (same on every page) and, because its join
 * conditions are stricter than the listing, a product that the grid actually
 * shows could sit OUTSIDE the bound (e.g. a 8599 product unreachable on a 7999
 * slider). Here we mirror the listing's filters and use the IDENTICAL price
 * expression the price WHERE filter uses (see fetch_product) so every product
 * shown is guaranteed to fall within [min, max].
 *
 * The max is padded UP and the min padded DOWN to a clean step so a product can
 * never sit exactly on the cap.
 *
 * @param  array $filter       same $filter passed to fetch_product (price keys ignored)
 * @param  mixed $category_id  int|array|null category context
 * @param  mixed $seller_id    seller context
 * @return array ['min' => float, 'max' => float]
 */
function get_filtered_price_range($filter = NULL, $category_id = NULL, $seller_id = NULL)
{
    $t = &get_instance();

    // Effective (selling) price — MUST match the expression used by the price
    // WHERE filter in fetch_product() so the slider and the filter speak the
    // same units. A customer filtering by price is filtering by what they'd
    // actually pay, so both the MIN and MAX bounds are keyed off this — not
    // the struck-through MRP shown alongside it on the card.
    $price_expr = 'IF( pv.special_price > 0 , pv.special_price , pv.price )';
    $max_expr = $price_expr;

    $t->db->select("MIN($price_expr) as min_price, MAX($max_expr) as max_price", false)
        ->join(" categories c", "p.category_id=c.id ", 'LEFT')
        ->join(" brands b", "p.brand=b.name", 'LEFT')
        ->join(" seller_data sd", "p.seller_id=sd.user_id ", 'LEFT')
        ->join('`product_variants` pv', 'p.id = pv.product_id', 'LEFT')
        ->join('`product_attributes` pa', ' pa.product_id = p.id ', 'LEFT');

    // Base active-status conditions — mirror fetch_product(), listing cap included.
    $where = ['p.status' => '1', 'pv.status' => 1, 'sd.status' => 1, 'p.listing_visibility' => 1];

    /* --- mirror the listing filters, EXCLUDING min_price / max_price --- */
    if (isset($filter) && !empty($filter['search'])) {
        $tags = explode(" ", $filter['search']);
        $t->db->group_Start();
        foreach ($tags as $i => $tag) {
            if ($i == 0) {
                $t->db->like('p.tags', trim($tag));
            } else {
                $t->db->or_like('p.tags', trim($tag));
            }
        }
        $t->db->or_like('p.name', trim($filter['search']));
        $t->db->group_end();
    }
    if (isset($filter) && !empty($filter['brand'])) {
        $t->db->where('p.brand', trim($filter['brand']));
    }
    if (isset($filter) && !empty($filter['brands'])) {
        $t->db->where_in('p.brand', explode("|", $filter['brands']));
    }
    if (isset($filter) && !empty($filter['attribute_value_ids'])) {
        $str = str_replace(',', '|', $filter['attribute_value_ids']);
        $t->db->where('CONCAT(",", pa.attribute_value_ids , ",") REGEXP ",(' . $str . ')," !=', 0, false);
    }
    if (isset($filter) && !empty($filter['product_type']) && strtolower($filter['product_type']) == 'products_on_sale') {
        $t->db->where('pv.special_price >', '0');
    }
    if (isset($seller_id) && !empty($seller_id) && $seller_id != "") {
        $where['p.seller_id'] = $seller_id;
    }
    if (isset($category_id) && !empty($category_id)) {
        /* Same subtree as fetch_product(), so the price slider spans the products actually listed. */
        $descendant_ids = category_descendant_ids($category_id);
        if (!empty($descendant_ids)) {
            $t->db->where_in('p.category_id', $descendant_ids);
        }
    }

    // GST enrollment / customer-state restriction — mirror fetch_product() so the
    // range matches exactly what a given customer can see.
    if (isset($filter['customer_state']) && !empty($filter['customer_state'])) {
        $cs = $t->db->escape($filter['customer_state']);
        $t->db->where("(sd.is_gst_registered = 1 OR LOWER(TRIM(sd.state)) = LOWER(TRIM($cs)))", null, false);
    }

    $t->db->where($where);

    // Only active/visible categories (mirror fetch_product's no-flag branch, including its
    // treatment of an existing category whose status was never set).
    $t->db->group_Start();
    $t->db->or_where('c.status', '1');
    $t->db->or_where('c.status', '0');
    $t->db->or_where('(c.id IS NOT NULL AND c.status IS NULL)', null, false);
    $t->db->group_End();

    $row = $t->db->from("products p")->get()->row_array();

    $min = (isset($row['min_price']) && $row['min_price'] !== null) ? (float) $row['min_price'] : 0;
    $max = (isset($row['max_price']) && $row['max_price'] !== null) ? (float) $row['max_price'] : 0;

    // Show the REAL page min/max — just snap to whole rupees so the bounds stay
    // integers. No cosmetic rounding to hundreds: if the cheapest product is 50
    // the slider min is 50, and if the dearest is 4999 the max is 4999 (not 5000).
    // The cheapest/dearest products are always reachable because the price filter
    // itself is inclusive (>= min, <= max).
    if ($max > 0) {
        $min = max(0, floor($min));
        $max = ceil($max);
    }

    return ['min' => $min, 'max' => $max];
}

function get_price($type = "max")
{
    $t = &get_instance();
    $t->db->select('IF( pv.special_price > 0, `pv`.`special_price`, pv.price ) as pr_price')
        ->join(" categories c", "p.category_id=c.id ", 'LEFT')
        ->join(" seller_data sd", "p.seller_id=sd.user_id ")
        ->join('`product_variants` pv', 'p.id = pv.product_id', 'LEFT')
        ->join('`product_attributes` pa', ' pa.product_id = p.id ', 'LEFT');
    $t->db->where(" `p`.`status` = '1' AND `pv`.`status` = 1 AND `sd`.`status` = 1 AND   (`c`.`status` = '1' OR `c`.`status` = '0')");
    $result = $t->db->from("products p ")->get()->result_array();
    if (isset($result) && !empty($result)) {
        $pr_price = array_column($result, 'pr_price');
        $data = ($type == "min") ? min($pr_price) : max($pr_price);
    } else {
        $data = 0;
    }
    return $data;
}

/**
 * Ids of every category that has at least one matching product ANYWHERE in its subtree,
 * including the ancestors of the category the product actually sits in.
 *
 * "Beauty" holds no products directly - all of them are filed under Skin care / Makeup /
 * Bath and Body - so a plain join on products.category_id = categories.id reported it as
 * empty and hid it from category pickers and shop-by-category strips. Walking up from each
 * product's own category marks the whole ancestor chain instead.
 *
 * @param mixed $seller_id  restrict to one seller's products, or NULL for the whole catalogue
 * @param bool  $only_live  count only live, listable products (default) or every product
 * @return array category ids
 */
function category_ids_with_products($seller_id = NULL, $only_live = TRUE)
{
    $t = &get_instance();

    /* Raw query(), NOT the query builder: these helpers are called in the middle of another
       query being assembled (fetch_product(), get_categories()), and a builder get() there
       flushes that half-built select/join state into this query and wrecks both. */
    static $cache = [];
    $cache_key = (int) $seller_id . '|' . (int) $only_live;
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $sql = 'SELECT DISTINCT category_id FROM products WHERE 1 = 1';
    if ($only_live) {
        $sql .= ' AND status = 1 AND listing_visibility = 1';
    }
    if (!empty($seller_id)) {
        $sql .= ' AND seller_id = ' . (int) $seller_id;
    }
    $rows = $t->db->query($sql)->result_array();
    $leaf_ids = array_filter(array_map('intval', array_column($rows, 'category_id')));
    if (empty($leaf_ids)) {
        return $cache[$cache_key] = [];
    }

    $parent_of = [];
    foreach ($t->db->query('SELECT id, parent_id FROM categories')->result_array() as $c) {
        $parent_of[(int) $c['id']] = (int) $c['parent_id'];
    }

    $marked = [];
    foreach ($leaf_ids as $leaf) {
        $current = $leaf;
        $depth = 0;
        /* isset($marked[$current]) also short-circuits chains already walked. The depth cap
           stops a mis-parented cycle (A -> B -> A) from spinning forever. */
        while ($current > 0 && !isset($marked[$current]) && $depth++ < 20) {
            $marked[$current] = TRUE;
            $current = isset($parent_of[$current]) ? $parent_of[$current] : 0;
        }
    }
    return $cache[$cache_key] = array_map('intval', array_keys($marked));
}

function category_descendant_ids($category_id)
{
    if (empty($category_id)) {
        return [];
    }
    $t = &get_instance();
    $ids = is_array($category_id) ? $category_id : [$category_id];
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (empty($ids)) {
        return [];
    }
    static $cache = [];
    $cache_key = implode(',', $ids);
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $all = $ids;
    $frontier = $ids;
    /* Walk down one level at a time. array_diff against what has already been collected keeps a
       mis-parented cycle (A -> B -> A) from looping forever; the depth cap is a backstop.
       Raw query() on purpose: this runs while a caller is still assembling its own query on the
       shared CI query builder, and a builder get() here would swallow that pending state. */
    $depth = 0;
    while (!empty($frontier) && $depth++ < 20) {
        $rows = $t->db->query('SELECT id FROM categories WHERE parent_id IN (' . implode(',', array_map('intval', $frontier)) . ')')->result_array();
        $children = array_map('intval', array_column($rows, 'id'));
        $frontier = array_values(array_diff($children, $all));
        $all = array_merge($all, $frontier);
    }
    return $cache[$cache_key] = array_values(array_unique($all));
}

function check_for_parent_id($category_id)
{
    $t = &get_instance();
    $t->db->select('id,parent_id,name');
    $t->db->where('id', $category_id);
    $result = $t->db->from("categories")->get()->result_array();
    if (!empty($result)) {
        return $result;
    } else {
        return false;
    }
}

/**
 * See the model copies of this function for the full rationale. In short: $amount was
 * concatenated into SQL with escaping disabled and never validated, 'add' with a negative
 * amount debited the wallet, and update() reporting TRUE for zero matched rows made a credit to
 * a non-existent user look successful. Writes no `transactions` row - use
 * update_wallet_balance() when you need the ledger entry too (it does both atomically).
 */
function update_balance($amount, $delivery_boy_id, $action)
{
    $t = &get_instance();

    $delivery_boy_id = (int) $delivery_boy_id;
    if ($delivery_boy_id < 1 || !is_numeric($amount)) {
        return false;
    }

    $amount = (float) $amount;
    if ($amount <= 0) {
        log_message('error', 'update_balance: refused a non-positive amount (' . $amount . ') for user ' . $delivery_boy_id);
        return false;
    }

    if ($action === 'add') {
        $t->db->set('balance', '`balance` + ' . $amount, FALSE);
    } elseif ($action === 'deduct') {
        $t->db->set('balance', '`balance` - ' . $amount, FALSE);
    } else {
        return false;
    }

    $t->db->where('id', $delivery_boy_id)->update('users');
    return $t->db->affected_rows() > 0;
}

/** Same unescaped-concatenation and no-validation problem as update_balance() above. */
function update_cash_received($amount, $delivery_boy_id, $action)
{
    $t = &get_instance();

    $delivery_boy_id = (int) $delivery_boy_id;
    if ($delivery_boy_id < 1 || !is_numeric($amount)) {
        return false;
    }

    $amount = (float) $amount;
    if ($amount <= 0) {
        return false;
    }

    if ($action === 'add') {
        $t->db->set('cash_received', '`cash_received` + ' . $amount, FALSE);
    } elseif ($action === 'deduct') {
        $t->db->set('cash_received', '`cash_received` - ' . $amount, FALSE);
    } else {
        return false;
    }

    $t->db->where('id', $delivery_boy_id)->update('users');
    return $t->db->affected_rows() > 0;
}

function word_limit($string, $length = WORD_LIMIT, $dots = "...")
{
    $split = explode(" ", $string);
    $newLength = 0;
    $words = [];
    foreach ($split as $word) {
        $newLength += strlen($word);
        array_push($words, $word);
        if ($newLength >= $length) {
            break;
        }
    }
    $newstr = implode(" ", $words);
    if (strlen($newstr) < strlen($string)) {
        $newstr .= $dots;
    }
    return $newstr;
    // return (strlen($string) > $length) ? substr($string, 0, $length - strlen($dots)) . $dots : $string;
}
function short_description_word_limit($string, $length = SHORT_DESCRIPTION_WORD_LIMIT, $dots = "...")
{
    $split = explode(" ", $string);
    $newLength = 0;
    $words = [];
    foreach ($split as $word) {
        $newLength += strlen($word);
        array_push($words, $word);
        if ($newLength >= $length) {
            break;
        }
    }
    $newstr = implode(" ", $words);
    if (strlen($newstr) < $string) {
        $newstr .= $dots;
    }
    return $newstr;
    // return (strlen($string) > $length) ? substr($string, 0, $length - strlen($dots)) . $dots : $string;
}
function description_word_limit($string, $length = DESCRIPTION_WORD_LIMIT, $dots = "...")
{
    $split = explode(" ", $string);
    $newLength = 0;
    $words = [];
    foreach ($split as $word) {
        $newLength += strlen($word);
        array_push($words, $word);
        if ($newLength >= $length) {
            break;
        }
    }
    $newstr = implode(" ", $words);
    if (strlen($newstr) < $string) {
        $newstr .= $dots;
    }
    return $newstr;
    // return (strlen($string) > $length) ? substr($string, 0, $length - strlen($dots)) . $dots : $string;
}
function calculate_tax_inclusive($original_cost, $tax)
{
    $tax_amount = ($original_cost * (100 / (100 + $tax)));
    $Net_price = $original_cost - $tax_amount;
    return $Net_price;
}
function labels($label, $alt = '')
{
    $label = trim($label);
    $t = &get_instance();

    /*
     * Looked up with $log_errors = FALSE, and once instead of three times.
     *
     * This helper's contract is "use the translation if there is one, otherwise use the English
     * default in $alt", so a key with no translation is the normal case here - not an error. Going
     * through the lang() helper leaves CI's default $log_errors = TRUE, which writes
     * "Could not find the language line" to the error log on every single miss. The stock report
     * alone has nine untranslated column headers, so each load of it wrote nine error lines:
     * across one test run of this project, 324 of them. That is enough to bury a real warning in
     * the same file, and it did - a genuine "Undefined array key" sat among them.
     *
     * Behaviour is unchanged: a missing, empty, or un-translated key still falls back to $alt.
     */
    $value = $t->lang->line('Text.' . $label, false);

    if ($value === false || $value === '' || $value === 'Text.' . $label) {
        return trim($alt);
    }

    return trim($value);
}


function is_single_seller($product_variant_id, $user_id)
{
    $t = &get_instance();
    if (isset($product_variant_id) && !empty($product_variant_id) && $product_variant_id != "" && isset($user_id) && !empty($user_id) && $user_id != "") {
        $pv_id = (strpos($product_variant_id, ",")) ? explode(",", $product_variant_id) : $product_variant_id;

        // get exist data from cart if any 
        $exist_data = $t->db->select('`c`.product_variant_id,p.seller_id')
            ->join('product_variants pv ', 'pv.id=c.product_variant_id')
            ->join('products p ', 'pv.product_id=p.id')
            ->where(['user_id' => $user_id, 'is_saved_for_later' => 0])->group_by('p.seller_id')->get('cart c')->result_array();
        if (!empty($exist_data)) {
            $seller_id = array_values(array_unique(array_column($exist_data, "seller_id")));
        } else {
            // clear to add cart
            return true;
        }
        // get seller ids of varients
        $new_data = $t->db->select('p.seller_id')
            ->join('products p ', 'pv.product_id=p.id')
            ->where_in('pv.id', $pv_id)->get('product_variants pv')->result_array();
        $new_seller_id = $new_data[0]["seller_id"];
        if (!empty($seller_id) && !empty($new_seller_id)) {
            if (in_array($new_seller_id, $seller_id)) {
                // clear to add to cart
                return true;
            } else {
                // another seller id verient, give single seller error
                return false;
            }
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function is_single_product_type($product_variant_id, $user_id)
{
    $t = &get_instance();
    if (isset($product_variant_id) && !empty($product_variant_id) && $product_variant_id != "" && isset($user_id) && !empty($user_id) && $user_id != "") {
        $pv_id = (strpos($product_variant_id, ",")) ? explode(",", $product_variant_id) : $product_variant_id;

        // get exist data from cart if any 
        $exist_data = $t->db->select('`c`.product_variant_id,p.type')
            ->join('product_variants pv ', 'pv.id=c.product_variant_id')
            ->join('products p ', 'pv.product_id=p.id')
            ->where(['user_id' => $user_id, 'is_saved_for_later' => 0, 'p.status' => '1', 'pv.status' => '1'])->group_by('p.type')->get('cart c')->result_array();
        if (!empty($exist_data)) {
            $product_type = array_values(array_unique(array_column($exist_data, "type")));
        } else {
            // clear to add cart
            return true;
        }
        // get product types of varients
        $new_data = $t->db->select('p.type')
            ->join('products p ', 'pv.product_id=p.id')
            ->where_in('pv.id', $pv_id)->get('product_variants pv')->result_array();
        $new_product_type = $new_data[0]["type"];
        if (!empty($product_type) && !empty($new_product_type)) {
            if (in_array($new_product_type, $product_type)) {
                // clear to add to cart
                return true;
            } else {
                if (!in_array("digital_product", $product_type) && ($new_product_type == "variable_product" || $new_product_type == "simple_product")) {
                    return true;
                } else {
                    // another product type, give single product type
                    return false;
                }
            }
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function label($label = "", $alt = "")
{
    $t = &get_instance();
    return !empty($t->lang->line($label)) ?  $t->lang->line($label) : $alt;
}

function shiprocket_recomended_data($shiprocket_data)
{
    $result = array();
    $available_courier_companies = $shiprocket_data['data']['available_courier_companies'] ?? array();
    if (empty($available_courier_companies)) {
        return $result;
    }
    if (isset($shiprocket_data['data']['recommended_courier_company_id'])) {
        foreach ($available_courier_companies as  $rd) {
            if ($shiprocket_data['data']['recommended_courier_company_id'] == $rd['courier_company_id']) {
                $result = $rd;
                break;
            }
        }
    } else {
        foreach ($available_courier_companies as  $rd) {
            if ($rd['courier_company_id']) {
                $result = $rd;
                break;
            }
        }
    }
    return $result;
}
function get_shipment_id($item_id, $order_id)
{

    $t = &get_instance();
    $t->db->select('*');
    $t->db->from('order_tracking');
    $t->db->where('order_id', $order_id);
    $t->db->where('find_in_set("' . $item_id . '", order_item_id) <> 0');
    $query = $t->db->get()->result_array();
    if (!empty($query)) {
        return $query;
    } else {
        return false;
    }
}
function make_shipping_parcels($data)
{
    /**
     * 
     */
    $parcels = array();
    foreach ($data as $product) {

        // Grouped on the product's own pickup_location before, so any product with that column
        // blank - 278 of the 290 live ones - was silently dropped from the parcel set. Its weight
        // never reached the rate request, so a cart containing one was quoted a delivery charge
        // for only part of what was actually being shipped. Resolve to the seller's registered
        // pickup address instead (see resolve_seller_pickup_location).
        $seller_id = isset($product['seller_id']) ? $product['seller_id'] : 0;
        $pickup = resolve_seller_pickup_location(
            isset($product['pickup_location']) ? $product['pickup_location'] : '',
            $seller_id
        );

        if (empty($pickup)) {
            continue; // seller has no pickup address at all; nothing to quote against
        }

        $location = $pickup['pickup_location'];
        // Nominal per-unit weight when the product carries none, so a parcel of zero-weight
        // products is still quotable - Shiprocket rejects a zero total with "Weight Required".
        $weight = shiprocket_parcel_weight(isset($product['weight']) ? $product['weight'] : 0)
            * (isset($product['qty']) ? (float) $product['qty'] : 1);

        // The original expression assigned the whole parcel ARRAY back into ['weight'] on the
        // second item of a group (`? $parcels[$seller][$loc] :`), so a seller shipping two lines
        // from one address ended up with an array where a number belonged and the rate request
        // went out with a nonsense weight. Plain accumulation.
        if (!isset($parcels[$seller_id][$location]['weight'])) {
            $parcels[$seller_id][$location]['weight'] = 0;
        }
        $parcels[$seller_id][$location]['weight'] += $weight;
        $parcels[$seller_id][$location]['pin_code'] = $pickup['pin_code'];
    }
    return $parcels;
}

function check_parcels_deliveriblity($parcels, $user_pincode)
{
    $t = &get_instance();
    $t->load->library(['shiprocket']);
    $min_days = $max_days = $delivery_charge_with_cod  = $delivery_charge_without_cod = 0;
    // Accumulates one entry per seller per pickup location - see the note where it is filled.
    $data = [];

    foreach ($parcels as $seller_id => $parcel) {
        foreach ($parcel as $pickup_location => $parcel_weight) {


            // make_shipping_parcels() now carries the resolved pincode on the parcel, so there is
            // no second lookup to get wrong. The old one searched `pickup_locations` by nickname
            // ALONE - not scoped to the seller - so with two sellers using the same label it could
            // quote from the wrong warehouse's pincode entirely, and it read [0]['pin_code']
            // unguarded, which fataled outright when the nickname matched no row.
            $pickup_pin = isset($parcel_weight['pin_code']) ? $parcel_weight['pin_code'] : '';
            if ($pickup_pin === '') {
                $resolved = resolve_seller_pickup_location($pickup_location, $seller_id);
                $pickup_pin = isset($resolved['pin_code']) ? $resolved['pin_code'] : '';
            }
            if ($pickup_pin === '') {
                continue;
            }

            if (isset($parcel[$pickup_location]['weight']) && $parcel[$pickup_location]['weight'] > 15) {
                $data = "More than 15kg weight is not allow";
            } else {
                $availibility_data = [
                    'pickup_postcode' => $pickup_pin,
                    'delivery_postcode' => $user_pincode,
                    'cod' => 0,
                    'weight' => $parcel_weight['weight'],
                ];


                $check_deliveribility = $t->shiprocket->check_serviceability($availibility_data);
                $shiprocket_data = shiprocket_recomended_data($check_deliveribility);


                $availibility_data_with_cod = [
                    'pickup_postcode' => $pickup_pin,
                    'delivery_postcode' => $user_pincode,
                    'cod' => 1,
                    'weight' =>  $parcel_weight['weight'],
                ];

                $check_deliveribility_with_cod = $t->shiprocket->check_serviceability($availibility_data_with_cod);
                $shiprocket_data_with_cod = shiprocket_recomended_data($check_deliveribility_with_cod);

                /*
                 * `$data = []` used to sit here, INSIDE the per-parcel loop, so every parcel
                 * wiped the ones before it and a multi-seller cart reported serviceability,
                 * courier and ETA for its LAST parcel only. The accumulated charge totals below
                 * were right, but the per-parcel breakdown the mobile app renders was not.
                 * Initialised once, before the loop, instead.
                 */
                $estimated_delivery_days = $shiprocket_data['estimated_delivery_days'] ?? 0;

                $data[$seller_id][$pickup_location]['parcel_weight'] = $parcel_weight['weight'];
                $data[$seller_id][$pickup_location]['pickup_availability'] = $shiprocket_data['pickup_availability'] ?? false;
                $data[$seller_id][$pickup_location]['courier_name'] = $shiprocket_data['courier_name'] ?? '';
                // The courier's quoted rate. Under seller-paid shipping it is NOT what the
                // customer pays - it is kept as `quoted_*` for diagnostics and for the
                // shipping-cost picture a seller is shown, while the customer-facing keys
                // below are zeroed at the bottom of this function.
                $data[$seller_id][$pickup_location]['quoted_delivery_charge_with_cod'] = $shiprocket_data_with_cod['rate'] ?? 0;
                $data[$seller_id][$pickup_location]['quoted_delivery_charge_without_cod'] = $shiprocket_data['rate'] ?? 0;
                $data[$seller_id][$pickup_location]['delivery_charge_with_cod'] = $shiprocket_data_with_cod['rate'] ?? 0;
                $data[$seller_id][$pickup_location]['delivery_charge_without_cod'] = $shiprocket_data['rate'] ?? 0;
                $data[$seller_id][$pickup_location]['estimate_date'] = $shiprocket_data['etd'] ?? '';
                $data[$seller_id][$pickup_location]['estimate_days'] = $estimated_delivery_days;



                $min_days = (empty($min_days) || $estimated_delivery_days < $min_days) ? $estimated_delivery_days : $min_days;
                $max_days = (empty($max_days) || $estimated_delivery_days > $max_days) ? $estimated_delivery_days : $max_days;

                $delivery_charge_with_cod += $data[$seller_id][$pickup_location]['delivery_charge_with_cod'];
                $delivery_charge_without_cod += $data[$seller_id][$pickup_location]['delivery_charge_without_cod'];
            }
        }
    }

    $delivery_day = ($min_days == $max_days) ? $min_days : $min_days . '-' . $max_days;

    /*
     * Seller-paid shipping: the quote above is still made - it is the same serviceability call
     * that produces the delivery ETA, and the rate is worth keeping visible - but the customer
     * is charged nothing. The `quoted_*` keys carry the real courier estimate for anyone who
     * needs it; the plain keys are what checkout, the cart and the mobile app read, and they
     * are 0. The freight actually recovered from the seller is not this estimate at all: it is
     * captured from Shiprocket at AWB assignment (see record_shiprocket_freight()).
     */
    $customer_charge_with_cod = round($delivery_charge_with_cod);
    $customer_charge_without_cod = round($delivery_charge_without_cod);
    if (seller_paid_shipping_enabled()) {
        $customer_charge_with_cod = 0;
        $customer_charge_without_cod = 0;
        // $data is a STRING ("More than 15kg weight is not allow") on the overweight branch
        // above, not the per-parcel array - iterating it would be a fatal on a heavy cart.
        $parcel_rows = is_array($data) ? $data : [];
        foreach ($parcel_rows as $seller_id => $pickups) {
            if (!is_array($pickups)) {
                continue;
            }
            foreach ($pickups as $pickup_location => $parcel) {
                $data[$seller_id][$pickup_location]['delivery_charge_with_cod'] = 0;
                $data[$seller_id][$pickup_location]['delivery_charge_without_cod'] = 0;
            }
        }
    }

    $shipping_parcels = [
        'error' => false,
        'estimated_delivery_days' => $delivery_day,
        'estimate_date' => $shiprocket_data['etd'] ?? '',
        'delivery_charge' => 0,
        'delivery_charge_with_cod' => $customer_charge_with_cod,
        'delivery_charge_without_cod' => $customer_charge_without_cod,
        'quoted_delivery_charge_with_cod' => round($delivery_charge_with_cod),
        'quoted_delivery_charge_without_cod' => round($delivery_charge_without_cod),
        'is_free_delivery' => seller_paid_shipping_enabled() ? 1 : 0,
        'data' => $data
    ];
    return $shipping_parcels;
}
/**
 * The Shiprocket ORDER record, for display and for the inline status sync on the order edit pages.
 *
 * Two things this now avoids, both of which were paid on every render of admin/seller
 * "edit order" - a page that is opened constantly:
 *
 *  1. Duplicate calls in one render. The call sites sit inside a per-seller / per-pickup-location
 *     loop, so an order split across three parcels asked Shiprocket the same question three
 *     times, each a blocking request with a 15-second timeout. A slow Shiprocket meant a hung
 *     admin page, and the whole page's worth of calls happened before a single byte was sent.
 *
 *  2. Asking about shipments that can no longer change. Once a parcel is delivered, cancelled or
 *     returned there is no further scan coming, yet every page load re-asked - which is what
 *     filled the log with `orders/show/990777 (http 404)` on repeat. The last status Shiprocket
 *     reported is already on the tracking row, so it is served from there.
 *
 * The webhook (real time) and sync_shipment_statuses() (cron) are the mechanisms that actually
 * keep status current; this call is only the page's own read, so serving a terminal one locally
 * loses nothing.
 *
 * @param  bool $force_remote  Skip both the cache and the terminal short-circuit and go to
 *                              Shiprocket. Required by anything reading a field the local
 *                              reconstruction does not carry - freight reconciliation in
 *                              particular, which runs precisely on shipments that HAVE finished
 *                              and needs the billed charges, not the status.
 * @return array|null decoded Shiprocket response, or a locally-reconstructed one for a
 *                    finished shipment. Shape always carries ['data'], as callers assume.
 */
function get_shiprocket_order($shiprocket_order_id, $force_remote = false)
{
    static $cache = [];

    $key = (string) $shiprocket_order_id;
    if ($key === '' || $key === '0') {
        return null;
    }
    if (!$force_remote && array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $t = &get_instance();

    /*
     * `others` holds whatever Shiprocket last said about this shipment - the webhook and the cron
     * both write it. If that is already a terminal state, answer from it.
     */
    $tracking = $force_remote ? [] : fetch_details('order_tracking', ['shiprocket_order_id' => $shiprocket_order_id], 'others,is_canceled');
    if (!empty($tracking)) {
        $last_status = isset($tracking[0]['others']) ? (string) $tracking[0]['others'] : '';
        $internal = shiprocket_status_to_order_status($last_status);
        if (in_array($internal, ['delivered', 'cancelled', 'returned'], true)) {
            $cache[$key] = ['data' => ['status' => $last_status, 'status_code' => 0]];
            return $cache[$key];
        }
    }

    $t->load->library(['shiprocket']);
    $res = $t->shiprocket->get_specific_order($shiprocket_order_id);
    $cache[$key] = $res;
    return $res;
}

function generate_awb($shipment_id)
{
    $t = &get_instance();
    $order_tracking = fetch_details('order_tracking', ['shipment_id' => $shipment_id], 'courier_company_id');
    // This was read and then never used: the courier the platform selected and quoted
    // from was dropped on the floor and Shiprocket assigned whatever its own default
    // was. Pass it through so the AWB is raised against the intended courier.
    $courier_company_id = (!empty($order_tracking) && !empty($order_tracking[0]['courier_company_id']))
        ? $order_tracking[0]['courier_company_id']
        : null;

    $t->load->library(['Shiprocket']);
    $res = $t->shiprocket->generate_awb($shipment_id, $courier_company_id);

    // Only persist an AWB we actually got back. The old else-branch fired a SECOND
    // identical request on failure and then read $res['response']['data']['awb_code']
    // unconditionally - on a genuine failure that key doesn't exist, so it wrote NULL
    // over awb_code. Since the Shiprocket webhook finds orders BY awb_code, that also
    // permanently severed the tracking link for the shipment.
    $awb_code = isset($res['response']['data']['awb_code']) ? $res['response']['data']['awb_code'] : null;

    if (!empty($awb_code)) {
        $t->db->set(['awb_code' => $awb_code])->where('shipment_id', $shipment_id)->update('order_tracking');

        /*
         * Capture the freight the courier is actually billing, now, while Shiprocket is telling
         * us. This is the moment the cost becomes real: assigning the AWB commits the parcel to
         * a courier at a rate, and it is the only response that carries `freight_charges`
         * without a second API call.
         *
         * Under seller-paid shipping that figure is what the seller is charged at settlement,
         * so it has to be recorded here or it is lost - a later `shipments/{id}` read is a
         * reconciliation fallback, not a substitute. Non-fatal by design: a missing figure
         * means the seller is not charged freight for the parcel, which is the safe direction
         * to fail in, and the reconciliation cron picks it up on its next pass.
         */
        $freight = shiprocket_freight_from_response($res);
        if ($freight !== null) {
            record_shiprocket_freight($shipment_id, $freight, 'awb_assignment');
        } else {
            log_message('error', 'Shiprocket AWB assignment for shipment ' . $shipment_id
                . ' carried no freight figure; it will be reconciled later.');
        }
    } else {
        log_message('error', 'Shiprocket AWB assignment returned no awb_code for shipment ' . $shipment_id . ' --> ' . var_export($res, true));
    }

    return $res;
}

function send_pickup_request($shipment_id)
{
    $t = &get_instance();
    $t->load->library(['Shiprocket']);
    $res = $t->shiprocket->request_for_pickup($shipment_id);

    if (!shiprocket_result_ok('pickup', $res)) {
        // Previously this simply fell through and returned the response, and the callers report
        // success on any non-empty value - so a refused pickup ("already requested", "past the
        // courier's cut-off", an unserviceable address) told the seller "Request send
        // successfully" and no courier was ever coming.
        log_message('error', 'Shiprocket pickup request refused for shipment ' . $shipment_id . ': '
            . shiprocket_result_message($res, 'pickup could not be scheduled'));
        return $res;
    }

    /*
     * Every one of these was read unguarded off $res['response']. Shiprocket does not always
     * return the same shape - a queued pickup can come back without a token or a scheduled date -
     * so each missing key was an "Undefined array key" warning that then wrote NULL over a column.
     * Two are worse than that:
     *   - `data` is sometimes an ARRAY, and assigning an array to a DB column is an
     *     "Array to string conversion" that stores the literal text "Array".
     *   - order_tracking.status is int NOT NULL, so a non-numeric status wrote 0 - the same value
     *     as "nothing has happened yet".
     */
    $response = isset($res['response']) && is_array($res['response']) ? $res['response'] : [];

    $order_tracking_data = [
        'pickup_status'         => 1,
        'pickup_scheduled_date' => isset($response['pickup_scheduled_date']) ? (string) $response['pickup_scheduled_date'] : '',
        'pickup_token_number'   => isset($response['pickup_token_number']) ? (string) $response['pickup_token_number'] : '',
        'pickup_generated_date' => json_encode([isset($response['pickup_generated_date']) ? $response['pickup_generated_date'] : null]),
        'data'                  => isset($response['data'])
            ? (is_scalar($response['data']) ? (string) $response['data'] : json_encode($response['data']))
            : '',
    ];
    if (isset($response['status']) && is_numeric($response['status'])) {
        $order_tracking_data['status'] = (int) $response['status'];
    }

    $t->db->set($order_tracking_data)->where('shipment_id', $shipment_id)->update('order_tracking');

    return $res;
}

/**
 * Did a Shiprocket operation actually succeed?
 *
 * Every one of these operations was judged by its caller with `if (!empty($res))`. A Shiprocket
 * REJECTION is also a non-empty array, so a refused pickup, an ungenerated label, a missing
 * invoice and a declined cancellation all reported success to the seller or admin who pressed the
 * button. Each operation has its own success marker, and this is the single place that knows them.
 *
 * @param string $operation pickup|label|invoice|cancel
 * @param mixed  $res       decoded Shiprocket response (null on a transport failure)
 */
function shiprocket_result_ok($operation, $res)
{
    if (!is_array($res)) {
        return false; // transport failure, or a non-JSON response
    }

    switch ($operation) {
        case 'pickup':
            return isset($res['pickup_status']) && $res['pickup_status'] == 1;
        case 'label':
            return isset($res['label_created']) && $res['label_created'] == 1 && !empty($res['label_url']);
        case 'invoice':
            return isset($res['is_invoice_created']) && $res['is_invoice_created'] == 1 && !empty($res['invoice_url']);
        case 'manifest':
            // manifests/print answers with the URL itself; there is no "created" flag to check.
            return !empty($res['manifest_url']);
        case 'cancel':
            // Shiprocket does not always put a `status` in the cancel BODY. Observed live: a
            // successful cancellation came back as HTTP 200 with just
            // {"message":"Order cancelled successfully."} - no status field at all. Requiring one
            // meant a cancellation that Shiprocket had actually performed was reported to the
            // admin as a failure, AND order_tracking.is_canceled was left at 0, so this side went
            // on believing the shipment was live and the status-sync cron kept polling a dead
            // shipment forever.
            if (isset($res['status']) && (int) $res['status'] === 200) {
                return true;
            }
            if (isset($res['status_code']) && (int) $res['status_code'] === 200) {
                return true;
            }
            // Nothing in the body to judge by: fall back to the transport result. curl() records
            // the HTTP status and only leaves last_error() set when the call did not succeed.
            $t = &get_instance();
            if (isset($t->shiprocket) && method_exists($t->shiprocket, 'last_status')) {
                $http = (int) $t->shiprocket->last_status();
                $failed = method_exists($t->shiprocket, 'last_error') ? $t->shiprocket->last_error() : null;
                if ($http >= 200 && $http <= 299 && empty($failed)) {
                    return true;
                }
            }
            return false;
        default:
            return false;
    }
}

/**
 * The most useful failure text Shiprocket gave us, for showing to whoever pressed the button.
 */
function shiprocket_result_message($res, $fallback = 'Shiprocket rejected the request.')
{
    if (is_array($res)) {
        foreach (['message', 'error', 'errors'] as $key) {
            if (!empty($res[$key])) {
                return is_string($res[$key]) ? $res[$key] : json_encode($res[$key]);
            }
        }
        // A pickup that is refused because one is already booked reports it under `response`.
        if (isset($res['response']) && is_string($res['response']) && $res['response'] !== '') {
            return $res['response'];
        }
    }

    $t = &get_instance();
    if (isset($t->shiprocket) && method_exists($t->shiprocket, 'last_error')) {
        $last = $t->shiprocket->last_error();
        if (!empty($last)) {
            return $last;
        }
    }

    return $fallback;
}

function generate_label($shipment_id)
{
    $t = &get_instance();
    $t->load->library(['Shiprocket']);
    $res = $t->shiprocket->generate_label($shipment_id);

    // Was `label_created == 1` alone, then read $res['label_url'] unguarded - so a response
    // claiming the label was created but carrying no URL wrote NULL over any previous label.
    if (shiprocket_result_ok('label', $res)) {
        $t->db->set(['label_url' => $res['label_url']])->where('shipment_id', $shipment_id)->update('order_tracking');
    } else {
        log_message('error', 'Shiprocket label not generated for shipment ' . $shipment_id . ': '
            . shiprocket_result_message($res, 'label could not be generated'));
    }
    return $res;
}

function generate_invoice($shiprocket_order_id)
{
    $t = &get_instance();
    $t->load->library(['Shiprocket']);
    $res = $t->shiprocket->generate_invoice($shiprocket_order_id);

    // Same as generate_label(): the URL was read unguarded once the flag looked right.
    if (shiprocket_result_ok('invoice', $res)) {
        $t->db->set(['invoice_url' => $res['invoice_url']])->where('shiprocket_order_id', $shiprocket_order_id)->update('order_tracking');
    } else {
        log_message('error', 'Shiprocket invoice not generated for order ' . $shiprocket_order_id . ': '
            . shiprocket_result_message($res, 'invoice could not be generated'));
    }
    return $res;
}
function cancel_shiprocket_order($shiprocket_order_id)
{
    $t = &get_instance();
    $t->load->library(['Shiprocket']);
    $res = $t->shiprocket->cancel_order($shiprocket_order_id);

    // Only mark the shipment cancelled once Shiprocket has actually accepted the
    // cancellation. This used to be set unconditionally, so a rejected or failed
    // cancel call still flipped is_canceled to 1 locally - the shipment stayed live
    // at Shiprocket while every screen here showed it as cancelled, and the cancel
    // button was then hidden so nobody could retry.
    if (shiprocket_result_ok('cancel', $res)) {
        $t->db->set(['is_canceled' => 1])->where('shiprocket_order_id', $shiprocket_order_id)->update('order_tracking');
    } else {
        log_message('error', 'Shiprocket cancellation refused for order ' . $shiprocket_order_id . ': '
            . shiprocket_result_message($res, 'the shipment could not be cancelled'));
    }
    return $res;
}

/**
 * Maps a Shiprocket shipment status onto this app's internal order-item status.
 *
 * Returns NULL for any Shiprocket status that has no internal equivalent (NEW,
 * INVOICED, PICKUP ERROR, ...) so callers can skip it rather than guess.
 */
function shiprocket_status_to_order_status($shiprocket_status)
{
    $shiprocket_status = strtoupper(trim((string) $shiprocket_status));
    if ($shiprocket_status === '') {
        return null;
    }

    $map = [
        /* Pre-handover: the parcel exists here but no courier has it yet. */
        'INVOICED'           => 'processed',
        'READY TO SHIP'      => 'processed',
        'SHIPMENT BOOKED'    => 'processed',
        'MANIFEST GENERATED' => 'processed',
        'OUT FOR PICKUP'     => 'processed',

        /* With the courier and moving. */
        'PICKUP SCHEDULED'   => 'shipped',
        'PICKUP GENERATED'   => 'shipped',
        'PICKUP QUEUED'      => 'shipped',
        'PICKED UP'          => 'shipped',
        'HANDOVER TO COURIER' => 'shipped',
        'SHIPPED'            => 'shipped',
        'IN TRANSIT'         => 'shipped',
        'IN FLIGHT'          => 'shipped',
        'CUSTOM CLEARED'     => 'shipped',
        'MISROUTED'          => 'shipped',
        'DELAYED'            => 'shipped',
        'REACHED WAREHOUSE'  => 'shipped',
        'REACHED DESTINATION HUB' => 'shipped',
        'OUT FOR DELIVERY'   => 'shipped',

        'DELIVERED'          => 'delivered',

        'CANCELED'           => 'cancelled',
        'CANCELLED'          => 'cancelled',

        'RTO INITIATED'      => 'returned',
        'RTO IN TRANSIT'     => 'returned',
        'RTO OFD'            => 'returned',
        'RTO NDR'            => 'returned',
        'RTO DELIVERED'      => 'returned',
        'RTO ACKNOWLEDGED'   => 'returned',
        'RETURN DELIVERED'   => 'returned',
    ];

    /*
     * Statuses left DELIBERATELY unmapped, so they are recorded on the tracking row and shown,
     * but change no order status by themselves:
     *
     *   UNDELIVERED, PARTIAL_DELIVERED, PICKUP ERROR, PICKUP EXCEPTION, LOST, DAMAGED, DESTROYED
     *
     * Each is an exception a person has to settle. The only internal statuses that would fit are
     * `cancelled` and `returned`, and both of those refund the customer and restore stock through
     * Order_model::update_order() - so guessing here would issue refunds off a courier scan. An
     * UNDELIVERED scan in particular is routine (customer not home) and is normally followed by a
     * successful re-attempt; auto-cancelling on it would refund orders that then get delivered.
     */

    return isset($map[$shiprocket_status]) ? $map[$shiprocket_status] : null;
}

/**
 * Applies a Shiprocket shipment status to the order items covered by an
 * order_tracking row.
 *
 * This is the single place that turns "Shiprocket says X" into a status change
 * here, shared by the webhook and by any manual/polled refresh.
 *
 * Why it exists: the webhook used to write orders.active_status / orders.status
 * directly. Neither column exists - this app tracks status per order_item - so
 * every delivery and cancellation callback died with "Unknown column
 * 'active_status'", Shiprocket got a 500, and nothing ever synced. Statuses only
 * advanced when a seller happened to open the order page (which does its own
 * inline sync). Writing to order_items here is what actually makes the sync work,
 * and it goes through Order_model::update_order() so the status-history JSON,
 * the forward-only status ladder, refunds and stock all behave exactly as they do
 * when a seller or admin changes the status by hand.
 *
 * @return array ['error' => bool, 'message' => string, 'updated' => int]
 */
function sync_shiprocket_shipment_status($tracking, $shiprocket_status, $raw_payload = null)
{
    $t = &get_instance();
    $t->load->model('Order_model');

    if (empty($tracking) || empty($tracking['order_id'])) {
        return ['error' => true, 'message' => 'Shipment not found', 'updated' => 0];
    }

    $is_return_leg = !empty($tracking['is_return']);

    // A reverse pickup reports the SAME vocabulary as a forward shipment - PICKED UP, IN
    // TRANSIT, DELIVERED - but it means the opposite thing. Mapping a return leg through the
    // forward table marked the item "shipped" while it was travelling back to the seller, and
    // then "delivered" when it arrived there, which is how an approved return ended up looking
    // like a fresh successful delivery. On the return leg only the terminal state matters: the
    // parcel is back with the seller.
    if ($is_return_leg) {
        $internal_status = in_array(strtoupper(trim((string) $shiprocket_status)), ['DELIVERED', 'RTO DELIVERED', 'RETURN DELIVERED'], true)
            ? 'returned'
            : null;
    } else {
        $internal_status = shiprocket_status_to_order_status($shiprocket_status);
    }

    // Always record what Shiprocket last told us, even for statuses with no
    // internal equivalent - otherwise intermediate tracking states (IN TRANSIT,
    // OUT FOR DELIVERY, ...) were simply discarded and the tracking row kept
    // whatever it was created with.
    $tracking_update = ['others' => substr((string) $shiprocket_status, 0, 255)];
    if ($internal_status === 'cancelled') {
        $tracking_update['is_canceled'] = 1;
    }
    $t->db->set($tracking_update)->where('id', $tracking['id'])->update('order_tracking');

    if ($internal_status === null) {
        return ['error' => false, 'message' => 'Status recorded, no internal status change required', 'updated' => 0];
    }

    // order_item_id on this table is a comma-separated list (it is widened to
    // MEDIUMTEXT by migration 011 for exactly that reason). Fall back to every
    // item on the order if the list is empty, so a tracking row written before
    // that column was populated still syncs.
    $order_item_ids = array_filter(array_map('intval', explode(',', (string) $tracking['order_item_id'])));
    if (empty($order_item_ids)) {
        $order_item_ids = array_column(
            fetch_details('order_items', ['order_id' => $tracking['order_id']], 'id'),
            'id'
        );
    }
    if (empty($order_item_ids)) {
        return ['error' => true, 'message' => 'No order items found for this shipment', 'updated' => 0];
    }

    $updated = 0;
    foreach ($order_item_ids as $order_item_id) {
        $current = fetch_details('order_items', ['id' => $order_item_id], 'active_status,product_variant_id,quantity');
        if (empty($current)) {
            continue;
        }
        if ($current[0]['active_status'] === $internal_status) {
            continue; // already there - keeps repeated webhook deliveries idempotent
        }
        // Never resurrect an item a customer/seller already cancelled or returned here.
        if (in_array($current[0]['active_status'], ['cancelled', 'returned'], true)) {
            continue;
        }
        /*
         * Forward-only. Shiprocket retries webhooks and does not guarantee ordering, so a
         * delayed IN TRANSIT callback can land AFTER the DELIVERED one. The previous guard only
         * blocked cancelled/returned, so that late callback un-delivered the order: verified
         * live, a delivered item went back to "shipped".
         *
         * That is not cosmetic - `delivered` is what stamps delivered_at and makes the item
         * eligible for seller commission settlement, and the customer gets an "order shipped"
         * notification after they have already received the parcel.
         *
         * The ranking matches $priority_status in admin/Orders.php, which the manual
         * status-update screens already enforce. cancelled and returned outrank delivered on
         * purpose: an RTO or a post-delivery cancellation must still be able to land.
         */
        $status_rank = [
            'received'  => 0,
            'processed' => 1,
            'shipped'   => 2,
            'delivered' => 3,
            'cancelled' => 4,
            'returned'  => 5,
        ];
        $current_rank = isset($status_rank[$current[0]['active_status']]) ? $status_rank[$current[0]['active_status']] : -1;
        $new_rank = isset($status_rank[$internal_status]) ? $status_rank[$internal_status] : -1;
        if ($current_rank >= 0 && $new_rank >= 0 && $new_rank < $current_rank) {
            log_message('debug', 'sync_shiprocket_shipment_status: ignoring out-of-order status "'
                . $shiprocket_status . '" for order item ' . $order_item_id
                . ' (already ' . $current[0]['active_status'] . ').');
            continue;
        }

        $t->Order_model->update_order(['status' => $internal_status], ['id' => $order_item_id], true, 'order_items');
        $t->Order_model->update_order(['active_status' => $internal_status], ['id' => $order_item_id], false, 'order_items');

        if ($internal_status === 'cancelled' || $internal_status === 'returned') {
            // Both of these are no-ops when the item has already been settled - which is the
            // normal case for a return, where approving the request refunded the customer and
            // restored the stock before the courier ever collected the parcel. They are still
            // called so that a shipment that fails without anyone touching the order here (an
            // RTO on an undelivered parcel, say) is still refunded and restocked.
            process_refund($order_item_id, $internal_status, 'order_items');
            restore_order_item_stock(
                $order_item_id,
                ($internal_status === 'returned') ? 'Shiprocket return/RTO delivered' : 'Shiprocket shipment cancelled'
            );
        }
        $updated++;
    }

    if ($updated > 0) {
        notify_customer_order_status($tracking['order_id'], $internal_status, $order_item_ids);
    }

    return [
        'error'   => false,
        'message' => 'Order updated successfully',
        'updated' => $updated,
    ];
}

/**
 * Sends the standard "order status changed" push + notify_event for an order, to
 * the customer who placed it and to the seller(s) whose items moved. Mirrors the
 * notification block the seller and admin status-update screens already use.
 *
 * @param int|string  $order_id
 * @param string      $status         internal status (received/processed/...)
 * @param array|null  $order_item_ids limits the seller recipients to the sellers
 *                                    behind these items; all sellers on the order
 *                                    when omitted.
 */
function notify_customer_order_status($order_id, $status, $order_item_ids = null)
{
    $t = &get_instance();

    $types = [
        'received'  => 'customer_order_received',
        'processed' => 'customer_order_processed',
        'shipped'   => 'customer_order_shipped',
        'delivered' => 'customer_order_delivered',
        'cancelled' => 'customer_order_cancelled',
        'returned'  => 'customer_order_returned',
    ];
    if (!isset($types[$status])) {
        return;
    }
    $type = $types[$status];

    $order = fetch_details('orders', ['id' => $order_id], 'user_id');
    if (empty($order)) {
        return;
    }
    $user_res = fetch_details('users', ['id' => $order[0]['user_id']], 'username,fcm_id,mobile,email');
    if (empty($user_res)) {
        return;
    }

    $settings = get_settings('system_settings', true);
    $app_name = isset($settings['app_name']) && !empty($settings['app_name']) ? $settings['app_name'] : '';
    $custom_notification = fetch_details('custom_notifications', ['type' => $type], '');

    $message = 'Hello Dear ' . $user_res[0]['username'] . ', order status updated to ' . $status
        . ' for order ID #' . $order_id . ' please take note of it! Thank you. Regards ' . $app_name;
    if (!empty($custom_notification)) {
        $string = json_encode($custom_notification[0]['message'], JSON_UNESCAPED_UNICODE);
        $hashtag = html_entity_decode($string);
        $parsed = str_replace(
            ['< cutomer_name >', '< order_item_id >', '< application_name >'],
            [$user_res[0]['username'], $order_id, $app_name],
            $hashtag
        );
        $message = output_escaping(trim($parsed, '"'));
    }

    $title = (!empty($custom_notification)) ? $custom_notification[0]['title'] : 'Order status updated';

    /*
     * Record it in the customer's own notification list.
     *
     * Before this, an order status change reached the customer through push and email ONLY - and
     * both of those can be unavailable at the same time:
     *   - push needs an FCM server key, which ships as the placeholder "your_fcm_server_key";
     *   - email needs working SMTP credentials, and Gmail currently rejects the configured
     *     password ("534-5.7.9 Application-specific password required").
     * With both down, a customer whose order was shipped, delivered or cancelled was told
     * NOTHING, anywhere - there was no in-app record at all. The support-ticket events already
     * write one (see notify_ticket_event); order events are at least as important and now do too,
     * so My Account > Notifications is a reliable history regardless of the other two channels.
     */
    add_user_notification(
        $order[0]['user_id'],
        $title,
        $message,
        'order_' . $status,
        base_url('my-account/orders'),
        $order_id
    );

    if (!empty($user_res[0]['fcm_id'])) {
        send_notification([
            'title'    => $title,
            'body'     => $message,
            'type'     => 'order',
            'order_id' => $order_id,
        ], [[$user_res[0]['fcm_id']]]);
    }

    // The seller whose items just moved needs to know too - the admin order screen
    // already notified sellers alongside customers, so keep that behaviour when the
    // status change arrives from the Shiprocket webhook instead of from a person.
    $seller_where = ['order_id' => $order_id];
    $seller_rows = (!empty($order_item_ids))
        ? fetch_details('order_items', $seller_where, 'seller_id', '', '', '', '', 'id', $order_item_ids)
        : fetch_details('order_items', $seller_where, 'seller_id');
    $seller_ids = array_unique(array_filter(array_column($seller_rows, 'seller_id')));

    $seller_emails = $seller_mobiles = [];
    foreach ($seller_ids as $seller_id) {
        $seller = fetch_details('users', ['id' => $seller_id], 'fcm_id,email,mobile,username');
        if (empty($seller)) {
            continue;
        }
        $seller_body = 'Order status updated to ' . $status . ' for order ID #' . $order_id . '.';

        // The seller half of this was push + email ONLY, and on this deployment both of those
        // channels are down at the same time (placeholder FCM key, Gmail rejecting the SMTP
        // password) - so a seller whose order was shipped, delivered or cancelled was told
        // nothing anywhere. The customer already gets an in-app record above; the seller panel
        // now has a notification list to read one from, so write it.
        add_user_notification($seller_id, $title, $seller_body, 'order_' . $status, base_url('seller/orders'), $order_id);

        if (!empty($seller[0]['fcm_id'])) {
            send_notification([
                'title'    => $title,
                'body'     => $seller_body,
                'type'     => 'order',
                'order_id' => $order_id,
            ], [[$seller[0]['fcm_id']]]);
        }
        if (!empty($seller[0]['email'])) {
            $seller_emails[] = $seller[0]['email'];
        }
        if (!empty($seller[0]['mobile'])) {
            $seller_mobiles[] = $seller[0]['mobile'];
        }
    }

    notify_event(
        $type,
        ["customer" => [$user_res[0]['email']], "seller" => $seller_emails],
        ["customer" => [$user_res[0]['mobile']], "seller" => $seller_mobiles],
        ["orders.id" => $order_id]
    );
}
// function parse_sms(string $string = "", string $mobile = "", string $sms = "", string $country_code = "")
// {
//     // $parsedString = str_replace('{country_code}', $country_code, $string);
//     $parsedString = str_replace("{only_mobile_number}", "asdasdas", $string);
//     $parsedString = str_replace("{message}", $sms, $string);

//     return $parsedString;
// }

function parse_sms(string $string = "", string $mobile = "", string $sms = "", string $country_code = "")
{
    $parsedString = str_replace("{only_mobile_number}", $mobile, $string);
    $parsedString = str_replace("{message}", $sms, $parsedString); // Use $parsedString as the third argument

    return $parsedString;
}


function expoxable_settings()
{
    $settings = get_settings('system_settings', true);
    $settings_data = [];
    $settings_data['system.app_name'] = isset($settings['app_name']) ? $settings['app_name'] : '';
    $settings_data['system.support_number'] = isset($settings['support_number']) ? $settings['support_number'] : '';
    $settings_data['system.support_email'] = isset($settings['support_email']) ? $settings['support_email'] : '';
    // company_name is blank in the live settings row, so every template that signs off with
    // "{system.company_name}" delivered a message ending in "Best regards," and nothing else.
    // Each key is also read unguarded, which warns on any install where the key was never saved.
    $settings_data['system.company_name'] = !empty($settings['company_name'])
        ? $settings['company_name']
        : (!empty($settings['app_name']) ? $settings['app_name'] : '');
    $settings_data['system.currency'] = isset($settings['currency']) ? $settings['currency'] : '';
    return $settings_data;
}

/**
 * Assembles the {order.*} / {user.*} / {addresses.*} / {transactions.*} / {return_requests.*}
 * placeholder data set that every notification template is rendered against.
 *
 * The SELECT below used to name `users.created_on`, a column that does not exist on this schema
 * (it is `users.created_at`). One wrong column name made the WHOLE query fail with SQL error
 * 1054, and this function is the first thing notify_event() calls - so every transactional email
 * and SMS on the platform (order placed, received, processed, shipped, delivered, cancelled,
 * returned, return approved/declined, wallet credit, seller settlement, bank transfer status)
 * died right here, before a template was ever rendered. It is still aliased as
 * `user.created_on` because that is the placeholder name $config['order_keys'] advertises.
 */
function get_order_data($where = [], $first = false)
{
    $t = &get_instance();

    $settings_data = expoxable_settings();
    $t->db->from('orders')->select("orders.id AS 'order.id', 
                orders.user_id AS 'order.user_id', 
                orders.address_id AS 'order.address_id', 
                orders.mobile AS 'order.mobile', 
                orders.total AS 'order.total', 
                orders.delivery_charge AS 'order.delivery_charge', 
                orders.is_delivery_charge_returnable AS 'order.is_delivery_charge_returnable', 
                orders.wallet_balance AS 'order.wallet_balance', 
                orders.promo_code AS 'order.promo_code', 
                orders.promo_discount AS 'order.promo_discount', 
                orders.discount AS 'order.discount', 
                orders.total_payable AS 'order.total_payable', 
                orders.payment_method AS 'order.payment_method', 
                orders.latitude AS 'order.latitude', 
                orders.longitude AS 'order.longitude', 
                orders.address AS 'order.address', 
                orders.delivery_time AS 'order.delivery_time', 
                orders.delivery_date AS 'order.delivery_date', 
                orders.date_added AS 'order.date_added', 
                orders.otp AS 'order.otp', 
                orders.notes AS 'order.notes', 
                orders.attachments AS 'order.attachments', 
                orders.is_pos_order AS 'order.is_pos_order', 
                users.id AS 'user.id', 
                users.ip_address AS 'user.ip_address', 
                users.username AS 'user.username', 
                users.email AS 'user.email', 
                users.mobile AS 'user.mobile', 
                users.image AS 'user.image', 
                users.balance AS 'user.balance', 
                users.active AS 'user.active', 
                users.company AS 'user.company', 
                users.address AS 'user.address', 
                users.bonus_type AS 'user.bonus_type', 
                users.bonus AS 'user.bonus', 
                users.cash_received AS 'user.cash_received', 
                users.dob AS 'user.dob', 
                users.city AS 'user.city', 
                users.area AS 'user.area', 
                users.street AS 'user.street', 
                users.pincode AS 'user.pincode', 
                users.serviceable_zipcodes AS 'user.serviceable_zipcodes', 
                users.fcm_id AS 'user.fcm_id', 
                users.latitude AS 'user.latitude', 
                users.longitude AS 'user.longitude', 
                users.type AS 'user.type', 
                users.driving_license AS 'user.driving_license', 
                users.status AS 'user.status', 
                users.web_fcm AS 'user.web_fcm', 
                users.created_at AS 'user.created_on', 
                addresses.id AS 'addresses.id', 
                addresses.user_id AS 'addresses.user_id', 
                addresses.name AS 'addresses.name', 
                addresses.type AS 'addresses.type', 
                addresses.mobile AS 'addresses.mobile', 
                addresses.alternate_mobile AS 'addresses.alternate_mobile', 
                addresses.address AS 'addresses.address', 
                addresses.landmark AS 'addresses.landmark', 
                addresses.area_id AS 'addresses.area_id', 
                addresses.city_id AS 'addresses.city_id', 
                addresses.city AS 'addresses.city', 
                addresses.area AS 'addresses.area', 
                addresses.pincode AS 'addresses.pincode', 
                addresses.country_code AS 'addresses.country_code', 
                addresses.state AS 'addresses.state', 
                addresses.country AS 'addresses.country', 
                addresses.latitude AS 'addresses.latitude', 
                addresses.longitude AS 'addresses.longitude', 
                addresses.is_default AS 'addresses.is_default',
                transactions.id AS 'transactions.id',
                transactions.transaction_type AS 'transactions.transaction_type', 
                transactions.user_id AS 'transactions.user_id', 
                transactions.order_id AS 'transactions.order_id', 
                transactions.order_item_id AS 'transactions.order_item_id', 
                transactions.type AS 'transactions.type', 
                transactions.txn_id AS 'transactions.txn_id', 
                transactions.payu_txn_id AS 'transactions.payu_txn_id', 
                transactions.amount AS 'transactions.amount', 
                transactions.status AS 'transactions.status', 
                transactions.currency_code AS 'transactions.currency_code', 
                transactions.payer_email AS 'transactions.payer_email', 
                transactions.message AS 'transactions.message', 
                transactions.transaction_date AS 'transactions.transaction_date', 
                transactions.date_created AS 'transactions.date_created',
                transactions.is_refund AS 'transactions.is_refund',
                return_requests.id 'return_requests.id',
                return_requests.user_id AS 'return_requests.user_id',
                return_requests.product_id AS 'return_requests.product_id',
                return_requests.product_variant_id AS 'return_requests.product_variant_id',
                return_requests.order_id AS 'return_requests.order_id',
                return_requests.order_item_id AS 'return_requests.order_item_id',
                return_requests.status AS 'return_requests.status',
                return_requests.remarks AS 'return_requests.remarks',
                return_requests.date_created AS 'return_requests.date_created'
                ")
        ->join("users", "orders.user_id = users.id", "LEFT")
        ->join("addresses", "orders.address_id = addresses.id", "LEFT")
        ->join("transactions", "orders.id = transactions.order_id", "LEFT")
        ->join("return_requests", "orders.id = return_requests.order_id", "LEFT");




    foreach ($where as $key => $val) {
        $t->db->where($key,  $val);
    }

    $data = $t->db->get()->result_array();
    if (count($data) == 0) {
        return [
            "error" => true,
            "message" => "Data not found.",
            "data" => $data
        ];
    }
    $data = array_merge($data[0], $settings_data);
    return [
        "error" => false,
        "message" => "order data received successfully,",
        "data" => $data
    ];
}
function get_notification_variables()
{
    $t = &get_instance();
    $tags = [];
    $keys = $t->config->item('order_keys');
    foreach (expoxable_settings() as $key => $val) {
        $keys[] = $key;
    }

    foreach ($keys as $val) {
        $tags[] = "{" . $val . "}";
    }

    return $tags;
}

function set_user_otp($mobile, $otp)
{
    $t = &get_instance();
    $otp = random_int(100000, 999999);
    $time = strtotime(date('Y-m-d H:i:s'));

    $otps = fetch_details('otps', ['mobile' => $mobile]);
    if (empty($otps)) {
        return [
            "error" => true,
            "message" => "Something went wrong."
        ];
    }

    $data['otp'] = $otp;
    $data['created_at'] = $time;

    // Persist BEFORE sending: the stored OTP is what verify_password_reset_otp()
    // compares against, so writing it first keeps the row consistent with
    // whatever the gateway ends up delivering.
    $t->db->where('id', $otps[0]['id']);
    $t->db->update('otps', $data);

    // send_sms() returns ['body'=>..,'http_code'=>..] and, when the gateway is
    // unconfigured or curl fails, an 'error' key. This return value used to be
    // discarded entirely, so every caller reported "OTP sent successfully" even
    // when nothing was ever handed to a gateway - the exact reason password
    // reset "worked" in the response but no SMS ever arrived.
    $sms = send_sms($mobile, "please don't share with anyone $otp");
    if (!empty($sms['error']) || empty($sms['http_code']) || $sms['http_code'] < 200 || $sms['http_code'] >= 300) {
        log_message('error', 'set_user_otp: SMS send failed for ' . $mobile . ' - ' . json_encode($sms));
        return [
            "error" => true,
            "message" => !empty($sms['error']) ? $sms['error'] : "Could not send the OTP SMS. Please try again later."
        ];
    }

    return [
        "error" => false,
        "message" => "OTP send successfully.",
        "data" => $data
    ];
}


function checkOTPExpiration($otpTime)
{

    $time = date('Y-m-d H:i:s');
    $currentTime = strtotime($time);
    $timeDifference = $currentTime - $otpTime;

    // Was "<= 30" (30 seconds) - too short for anyone to receive and type an
    // SMS OTP, effectively making every OTP appear expired immediately. 10
    // minutes matches the window already used elsewhere in the app (e.g.
    // seller/Auth.php's tempdata OTP).
    if ($timeDifference <= 600) {
        return [
            "error" => false,
            "message" => "Success: OTP is valid."
        ];
    } else {
        return [
            "error" => true,
            "message" => "Error: Session has expired."
        ];
    }
}

/**
 * Verify a Firebase ID token server-side and return its trusted claims.
 *
 * WHY THIS EXISTS: the social-login endpoint used to trust the uid/email/name POSTed by
 * the browser ("already verified on client via OAuth"). That is not a verification at all -
 * anyone could POST an existing customer's email straight to the endpoint and be logged in
 * as them, with no password and no Facebook involvement. Confirmed exploitable before this
 * was added.
 *
 * A Firebase ID token is an RS256-signed JWT. It is only trustworthy once the signature is
 * checked against Google's rotating public certificates AND the audience/issuer/expiry are
 * checked against our own Firebase project. Everything the caller sends is then ignored in
 * favour of the claims inside the verified token.
 *
 * @return array ['error'=>bool, 'message'=>string, 'uid'=>string, 'email'=>string,
 *                'name'=>string, 'provider'=>string]
 */
function verify_firebase_id_token($id_token)
{
    $t = &get_instance();
    $fail = function ($msg) {
        return ['error' => true, 'message' => $msg, 'uid' => '', 'email' => '', 'name' => '', 'provider' => ''];
    };

    $id_token = trim((string) $id_token);
    if ($id_token === '') {
        return $fail('Missing sign-in token.');
    }

    $firebase_settings = get_settings('firebase_settings', true);
    $project_id = is_array($firebase_settings) && !empty($firebase_settings['projectId'])
        ? trim($firebase_settings['projectId'])
        : '';
    if ($project_id === '') {
        return $fail('Firebase is not configured on the server.');
    }

    // Google's public signing certs, keyed by the token header's "kid". Cached briefly so
    // a normal login does not make an outbound HTTPS call every time; Google rotates these
    // roughly daily and always publishes the new key before using it.
    $cache_file = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'cretzo_firebase_certs.json';
    $certs = null;
    if (is_file($cache_file) && (time() - filemtime($cache_file)) < 3600) {
        $certs = json_decode((string) file_get_contents($cache_file), true);
    }
    if (empty($certs) || !is_array($certs)) {
        $url = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';
        $raw = false;
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_TIMEOUT => 10,
            ]);
            $raw = curl_exec($ch);
            curl_close($ch);
        }
        if ($raw === false || $raw === '') {
            $raw = @file_get_contents($url);
        }
        $certs = ($raw !== false) ? json_decode((string) $raw, true) : null;
        if (empty($certs) || !is_array($certs)) {
            return $fail('Could not verify sign-in right now. Please try again.');
        }
        @file_put_contents($cache_file, json_encode($certs));
    }

    $t->load->library(['jwt', 'Key']);

    // Key construction must be inside the try as well: Key::__construct() throws on
    // anything it can't use as key material, and a malformed/empty entry in the cert
    // response would otherwise escape as an uncaught exception - crashing the endpoint
    // with an HTML error page instead of returning clean JSON. Unparseable certs are
    // skipped rather than fatal.
    try {
        $keys = [];
        foreach ($certs as $kid => $pem) {
            if (!is_string($pem) || trim($pem) === '') {
                continue;
            }
            $keys[$kid] = new Key($pem, 'RS256');
        }
        if (empty($keys)) {
            return $fail('Could not verify sign-in right now. Please try again.');
        }

        Jwt::$leeway = 60; // tolerate small clock skew
        $payload = $t->jwt->decode($id_token, $keys);
    } catch (Exception $e) {
        return $fail('Sign-in could not be verified. Please try again.');
    } catch (Throwable $e) {
        // php-jwt/openssl can also raise Error (not Exception) on bad key material.
        return $fail('Sign-in could not be verified. Please try again.');
    }

    // Audience must be OUR project, and the issuer must be Google's token service for it -
    // otherwise a valid token minted for some other Firebase project would be accepted.
    $aud = isset($payload->aud) ? $payload->aud : '';
    $iss = isset($payload->iss) ? $payload->iss : '';
    if ($aud !== $project_id || $iss !== 'https://securetoken.google.com/' . $project_id) {
        return $fail('Sign-in token was not issued for this site.');
    }
    if (empty($payload->sub)) {
        return $fail('Sign-in token is missing its subject.');
    }
    if (isset($payload->exp) && (int) $payload->exp < (time() - 60)) {
        return $fail('Sign-in token has expired. Please try again.');
    }

    $provider = '';
    if (isset($payload->firebase->sign_in_provider)) {
        $provider = (string) $payload->firebase->sign_in_provider; // e.g. facebook.com
    }

    return [
        'error'    => false,
        'message'  => 'verified',
        'uid'      => (string) $payload->sub,
        'email'    => isset($payload->email) ? strtolower(trim((string) $payload->email)) : '',
        'name'     => isset($payload->name) ? (string) $payload->name : '',
        // Phone-auth tokens carry the number Firebase actually delivered the SMS to and
        // saw confirmed. It is the only trustworthy statement that the caller controls
        // that handset, so password reset binds to this rather than to a posted field.
        'phone'    => isset($payload->phone_number) ? (string) $payload->phone_number : '',
        'provider' => $provider,
    ];
}

/**
 * Reduce a phone number to comparable digits, dropping the +91/0 prefixes that differ
 * between what Firebase returns (+919876543210) and what is stored in users.mobile
 * (9876543210). Compares the last 10 digits, which is what the app stores.
 */
function phone_digits_match($a, $b)
{
    $norm = function ($v) {
        $digits = preg_replace('/\D+/', '', (string) $v);
        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    };

    $a = $norm($a);
    $b = $norm($b);

    return $a !== '' && $a === $b;
}

/**
 * `users.mobile` is NOT NULL + UNIQUE with no default, but social/email-only
 * signups (Facebook/Google login, and any other passwordless path) have no
 * real phone number to store. Those callers used to pass a literal '' for
 * every such account - fine for the first one, but the UNIQUE index then
 * rejected every second account with a raw duplicate-key DB error (not a
 * friendly validation message), effectively blocking any social signup after
 * the very first. This generates a placeholder that's checked for uniqueness
 * before being used.
 */
function generate_unique_placeholder_mobile()
{
    for ($i = 0; $i < 20; $i++) {
        $candidate = '9' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        if (!is_exist(['mobile' => $candidate], 'users')) {
            return $candidate;
        }
    }
    // Practically unreachable given the loop above, but never return a blank/
    // colliding value.
    return '9' . substr((string) (time() . random_int(0, 999)), -9);
}

/**
 * Which account (and which portal it belongs to) owns a mobile number.
 *
 * WHY THIS EXISTS: every "forgot password" screen used to answer with a flat
 * "You have not registered using this number." whenever its own role lookup
 * missed - it could not tell "no such account anywhere" apart from "that account
 * exists, but on a different portal". That produced the exact contradiction
 * reported on 9675916976: the number belongs to a SELLER account (users.id 55,
 * users_groups.group_id 4), so the customer reset modal said "not registered"
 * while customer signup - which checks users.mobile with no role filter - said
 * "Mobile is already registered. Please try to login!". Both were "right" per
 * their own logic and the user was left with no way forward.
 *
 * Returns the row plus the portal role so callers can send people somewhere useful.
 *
 * @return array ['exists'=>bool, 'user'=>array|null, 'role'=>'customer'|'seller'|'admin'|'delivery_boy']
 */
function classify_mobile_owner($mobile)
{
    $t = &get_instance();

    $mobile = trim((string) $mobile);
    if ($mobile === '') {
        return ['exists' => false, 'user' => null, 'role' => ''];
    }

    $rows = $t->db->select('u.*, g.name AS group_name')
        ->from('users u')
        // LEFT join deliberately: a chunk of legacy accounts have no users_groups
        // row at all, and an INNER join would report them as non-existent.
        ->join('users_groups ug', 'ug.user_id = u.id', 'left')
        ->join('groups g', 'g.id = ug.group_id', 'left')
        ->where('u.mobile', $mobile)
        ->get()
        ->result_array();

    if (empty($rows)) {
        return ['exists' => false, 'user' => null, 'role' => ''];
    }

    $groups = [];
    foreach ($rows as $row) {
        if (!empty($row['group_name'])) {
            $groups[] = strtolower($row['group_name']);
        }
    }

    // Most-privileged wins when an account somehow sits in several groups, so we
    // never point an admin at the customer portal.
    $role = 'customer';
    if (in_array('admin', $groups, true)) {
        $role = 'admin';
    } elseif (in_array('seller', $groups, true)) {
        $role = 'seller';
    } elseif (in_array('delivery_boy', $groups, true)) {
        $role = 'delivery_boy';
    }

    unset($rows[0]['group_name']);
    return ['exists' => true, 'user' => $rows[0], 'role' => $role];
}

/**
 * Whether an account holds a given role.
 *
 * One mobile number = one account, but that account can hold SEVERAL roles - a buyer who
 * later signs up to sell keeps their buyer role and gains the seller one. Anything that
 * asks "is this a seller?" therefore has to test group MEMBERSHIP; asking
 * classify_mobile_owner() for a single primary role answers "seller" for such a person and
 * would wrongly lock them out of buyer-side flows.
 *
 * @param int    $user_id
 * @param string $role 'admin' | 'customer' | 'seller' | 'delivery_boy'
 */
function user_has_role($user_id, $role)
{
    $t = &get_instance();

    if (empty($user_id) || empty($role)) {
        return false;
    }

    // 'customer' is stored as the 'members' group.
    $group_name = ($role === 'customer') ? 'members' : $role;

    $count = $t->db
        ->from('users_groups ug')
        ->join('groups g', 'g.id = ug.group_id')
        ->where('ug.user_id', (int) $user_id)
        ->where('g.name', $group_name)
        ->count_all_results();

    return $count > 0;
}

/**
 * Completes a password reset that was verified by Firebase phone auth.
 *
 * Shared by all three portals (customer, seller, admin) so the security checks cannot
 * drift apart between them. This site has NO SMS gateway configured
 * (settings.sms_gateway_settings is '{}') - `authentication_method` is "firebase", so the
 * OTP SMS is sent and confirmed by Firebase in the browser. Everything the browser then
 * claims is re-verified here.
 *
 * The checks, in order, and why each one matters:
 *   1. The ID token's RS256 signature, audience, issuer and expiry are validated against
 *      our own Firebase project.
 *   2. The sign-in provider must literally be 'phone'. A Google/Facebook/email token is
 *      also a perfectly valid token for this project, and without this check one could be
 *      replayed here to reset an account whose phone the holder does not control.
 *   3. The phone number is taken from the VERIFIED token, never from the request body,
 *      and must match the account being reset.
 *   4. The account must actually belong to the portal doing the reset, so a customer
 *      cannot be reset through the admin endpoint or vice versa.
 *
 * @param string $id_token      Firebase ID token from the client.
 * @param string $mobile        Number the caller says it is resetting.
 * @param string $new_password  Replacement password.
 * @param string $expected_role 'customer' | 'seller' | 'admin' | 'delivery_boy'
 * @return array ['error' => bool, 'message' => string]
 */
function firebase_phone_reset($id_token, $mobile, $new_password, $expected_role)
{
    $t = &get_instance();

    $verified = verify_firebase_id_token($id_token);
    if (!empty($verified['error'])) {
        return ['error' => true, 'message' => $verified['message']];
    }

    if ($verified['provider'] !== 'phone' || empty($verified['phone'])) {
        return ['error' => true, 'message' => 'Please verify your mobile number to reset your password.'];
    }

    if (!phone_digits_match($verified['phone'], $mobile)) {
        return ['error' => true, 'message' => 'The verified mobile number does not match the account you are resetting.'];
    }

    $owner = classify_mobile_owner($mobile);
    if (empty($owner['exists'])) {
        return ['error' => true, 'message' => 'You have not registered using this number.'];
    }

    // Membership, not "primary role": a buyer who also sells holds both roles on one
    // account, and must be able to reset that single password from either portal.
    if (!user_has_role($owner['user']['id'], $expected_role)) {
        $portal = reset_portal_for_role($owner['role']);
        $where  = !empty($portal['url'])
            ? 'Please reset your password here: ' . base_url($portal['url'])
            : 'Please reset your password from the customer login on the main site.';
        return ['error' => true, 'message' => 'This number is registered as ' . $portal['label'] . '. ' . $where];
    }

    $user = $owner['user'];
    $identity_column = $t->config->item('identity', 'ion_auth');
    $identity = ($identity_column == 'email') ? $user['email'] : $user['mobile'];

    if (!$t->ion_auth->reset_password($identity, $new_password)) {
        return ['error' => true, 'message' => strip_tags((string) $t->ion_auth->errors())];
    }

    // Burn any pending server-side OTP for this number so an older code cannot be
    // replayed against the account afterwards.
    if (is_exist(['mobile' => $mobile], 'otps')) {
        update_details(['otp' => null, 'varified' => 0], ['mobile' => $mobile], 'otps');
    }

    return ['error' => false, 'message' => 'Reset Password Successfully'];
}

/**
 * Human label + reset URL for a role returned by classify_mobile_owner(), used to
 * build "that number belongs to X, reset it over there" messages.
 */
function reset_portal_for_role($role)
{
    $map = [
        'admin'        => ['label' => 'an admin account',       'url' => 'admin/login/forgot_password',        'login_url' => 'admin'],
        'seller'       => ['label' => 'a seller account',       'url' => 'seller/login/forgot_password',       'login_url' => 'seller/home'],
        'delivery_boy' => ['label' => 'a delivery boy account', 'url' => 'delivery_boy/login/forgot_password', 'login_url' => 'delivery_boy'],
        'customer'     => ['label' => 'a customer account',     'url' => '',                                   'login_url' => ''],
    ];
    return isset($map[$role]) ? $map[$role] : $map['customer'];
}

/**
 * Partially hides an email for display in a "we sent your OTP to ..." message, so
 * the user can confirm which inbox to open without the address being disclosed to
 * whoever typed the mobile number.
 */
function mask_email_for_display($email)
{
    $email = trim((string) $email);
    $at = strrpos($email, '@');
    if ($at === false || $at < 1) {
        return $email;
    }
    $name   = substr($email, 0, $at);
    $domain = substr($email, $at);
    $keep   = ($name !== '') ? substr($name, 0, 1) : '';
    return $keep . str_repeat('*', max(3, strlen($name) - 1)) . $domain;
}

/**
 * True only when admin has actually saved an SMS gateway. Mirrors the guard inside
 * send_sms() so callers can decide to use another channel BEFORE burning an attempt
 * on a gateway that cannot possibly deliver.
 */
function password_reset_sms_available()
{
    $gateway = get_settings('sms_gateway_settings', true);
    return !empty($gateway) && is_array($gateway) && !empty($gateway['base_url']);
}

/**
 * Stores a fresh password-reset OTP against $mobile and returns it, WITHOUT
 * sending anything. Split out from set_user_otp() (which stores *and* SMSes, and
 * is still used by the registration flow) so the reset flow can choose a delivery
 * channel after the code exists.
 */
function store_password_reset_otp($mobile)
{
    $t = &get_instance();

    if (!is_exist(['mobile' => $mobile], 'otps')) {
        insert_details(['mobile' => $mobile], 'otps');
    }
    $otps = fetch_details('otps', ['mobile' => $mobile]);
    if (empty($otps)) {
        return ['error' => true, 'message' => 'Could not start the password reset. Please try again.'];
    }

    $otp = random_int(100000, 999999);
    $t->db->where('id', $otps[0]['id']);
    $t->db->update('otps', [
        'otp'        => $otp,
        'varified'   => 0,
        'created_at' => strtotime(date('Y-m-d H:i:s')),
    ]);

    return ['error' => false, 'otp' => $otp];
}

/**
 * Generates a password-reset OTP for $mobile and delivers it over whichever channel
 * is actually configured, reporting which one was used.
 *
 * WHY THIS IS NOT JUST send_sms(): this used to be a one-liner into set_user_otp()
 * -> send_sms(), i.e. the SMS gateway was the ONLY possible channel. On production
 * `settings.sms_gateway_settings` is literally '{}' (no gateway ever configured),
 * so every password reset on every portal died with "SMS gateway is not configured."
 * - which is what the seller and admin screens were showing. Registration does not
 * hit this at all: `authentication_settings` is {"authentication_method":"firebase"}
 * so signup OTPs are minted client-side by Firebase phone auth, which is why signup
 * worked while reset did not.
 *
 * Order of preference:
 *   1. SMS gateway, if admin has configured one (unchanged behaviour when set up).
 *   2. The account's registered email over the SMTP credentials already saved in
 *      admin > Email Settings - configured and working on production today.
 * If neither can deliver we say so explicitly rather than claiming success.
 *
 * @param string     $mobile
 * @param array|null $user Row for the account being reset, used for its email.
 */
function send_password_reset_otp($mobile, $user = null)
{
    $t = &get_instance();

    // send_sms() lives in sms_helper, which isn't autoloaded app-wide and isn't
    // loaded by every controller that needs password reset. Idempotent.
    $t->load->helper('sms_helper');

    $stored = store_password_reset_otp($mobile);
    if (!empty($stored['error'])) {
        return $stored;
    }
    $otp = $stored['otp'];

    $settings  = get_settings('system_settings', true);
    $app_name  = (!empty($settings['app_name'])) ? $settings['app_name'] : 'Cretzo';
    $sms_text  = 'Your ' . $app_name . ' password reset OTP is ' . $otp . '. It is valid for 10 minutes. Please do not share it with anyone.';

    $failures = [];

    // ---- Channel 1: SMS gateway ------------------------------------------------
    if (password_reset_sms_available()) {
        $sms = send_sms($mobile, $sms_text);
        $code = isset($sms['http_code']) ? (int) $sms['http_code'] : 0;
        if (empty($sms['error']) && $code >= 200 && $code < 300) {
            return [
                'error'   => false,
                'channel' => 'sms',
                'message' => 'OTP sent to your mobile number ending in ' . substr($mobile, -4) . '.',
            ];
        }
        log_message('error', 'send_password_reset_otp: SMS channel failed for ' . $mobile . ' - ' . json_encode($sms));
        $failures[] = 'sms';
    }

    // ---- Channel 2: the account's registered email -----------------------------
    // Look the account up ourselves when the caller didn't hand us one, so this stays
    // usable from the app APIs that only have a mobile number.
    if (empty($user) || !is_array($user)) {
        $owner = classify_mobile_owner($mobile);
        $user  = $owner['user'];
    }

    $email = (!empty($user['email'])) ? trim($user['email']) : '';
    // Social/email-less signups get a synthetic placeholder in `mobile`, and some rows
    // carry a truncated copy of the email in `mobile`; only mail a real address.
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $subject = $app_name . ' password reset OTP';
        $body    = 'Your ' . $app_name . ' password reset OTP is <b>' . $otp . '</b>.<br><br>'
            . 'It is valid for 10 minutes. If you did not request a password reset you can ignore this email.';

        $mail = send_mail($email, $subject, $body);
        if (empty($mail['error'])) {
            return [
                'error'   => false,
                'channel' => 'email',
                'message' => 'OTP sent to your registered email ' . mask_email_for_display($email) . '.',
            ];
        }
        // NB: never log $mail['config'] - it holds the raw SMTP settings including the
        // mailbox password. $mail['reason'] is the redacted server response, which is the
        // part that actually says WHY (bad credentials, blocked port, quota, ...).
        log_message('error', 'send_password_reset_otp: email channel failed for ' . $mobile
            . ' - ' . (!empty($mail['reason']) ? $mail['reason'] : 'no reason reported'));
        $failures[] = 'email';
    }

    if (empty($failures)) {
        return [
            'error'   => true,
            'message' => 'No OTP delivery method is available for this account. Please contact support.',
        ];
    }

    return [
        'error'   => true,
        'message' => 'We could not deliver your OTP right now. Please try again in a few minutes, or contact support.',
    ];
}

/**
 * Verifies $otp against the pending otps row for $mobile (checking expiry too),
 * then invalidates it so it can't be replayed.
 */
function verify_password_reset_otp($mobile, $otp)
{
    $otps = fetch_details('otps', ['mobile' => $mobile]);
    if (empty($otps)) {
        return ['error' => true, 'message' => 'Please request a new OTP.'];
    }
    if (checkOTPExpiration($otps[0]['created_at'])['error']) {
        return ['error' => true, 'message' => 'OTP has expired. Please request a new OTP.'];
    }
    if ((string) $otps[0]['otp'] !== (string) $otp) {
        return ['error' => true, 'message' => 'Invalid OTP.'];
    }
    update_details(['otp' => ''], ['mobile' => $mobile], 'otps');
    return ['error' => false, 'message' => 'OTP verified.'];
}

function get_statistics($product_varient_id)
{

    $t = &get_instance();
    $dateString = date('Y-m-d H:i:s');

    $query = $t->db->query('
    SELECT
        (SELECT COUNT(id) FROM order_items 
         WHERE product_variant_id = ? 
         AND DATE(date_added) >= DATE(NOW()) - INTERVAL 31 DAY) AS total_ordered,
        (SELECT COUNT(f.id) FROM favorites f 
         LEFT JOIN product_variants pv ON f.product_id = pv.product_id 
         WHERE pv.id = ?) AS total_favorites,
        (SELECT COUNT(id) FROM cart 
         WHERE product_variant_id = ?) AS total_in_cart
', [$product_varient_id, $product_varient_id, $product_varient_id]);

    $result = $query->row_array();

    // Round to the nearest multiple of 100
    $totalOrdered = round($result['total_ordered'], -1);
    $totalFavorites = round($result['total_favorites'], -1);
    $totalInCart = round($result['total_in_cart'], -1);

    // Add a "+" sign if needed
    $totalOrdered = ($totalOrdered > 10) ? number_format($totalOrdered) . '+' : $totalOrdered;
    $totalFavorites = ($totalFavorites > 10) ? number_format($totalFavorites) . '+' : $totalFavorites;
    $totalInCart = ($totalInCart > 10) ? number_format($totalInCart) . '+' : $totalInCart;
    $total = [
        "total_ordered" => $totalOrdered,
        "total_favorites" => $totalFavorites,
        "total_in_cart" => $totalInCart,
        'product_variant_id' => $product_varient_id
    ];

    // print_r($reult);
    // print_r($totsal);
    return $total;
}

function moneyFormatIndia($num) {
    // Coerce to a finite number; fall back to 0 for empty/invalid input.
    $n = is_numeric($num) ? (float)$num : 0;

    $isNegative = $n < 0;
    $n = abs($n);

    // Round to paise (2 dp) so floating-point noise can't leak into the output.
    $n = round($n, 2);

    // Show decimals only when there's a fractional part; whole amounts stay clean
    // (e.g. 1200 -> "1,200", 44.9 -> "44.90"). This mirrors the JS moneyFormatIndia()
    // and never mangles a decimal value into "4,4.9".
    $fixed = (fmod($n, 1) != 0.0) ? number_format($n, 2, '.', '') : number_format($n, 0, '.', '');

    $parts   = explode('.', $fixed);
    $intPart = $parts[0];
    $decPart = isset($parts[1]) ? '.' . $parts[1] : '';

    // Indian digit grouping applies to the INTEGER part only:
    // rightmost 3 digits, then groups of 2 (e.g. 1234567 -> "12,34,567").
    if (strlen($intPart) > 3) {
        $lastThree = substr($intPart, -3);
        $rest      = substr($intPart, 0, strlen($intPart) - 3);
        $rest      = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        $grouped   = $rest . ',' . $lastThree;
    } else {
        $grouped = $intPart;
    }

    return ($isNegative ? '-' : '') . $grouped . $decPart;
}

function orderStatusTimeToHumanReadableString($dateTimeStr, $get_array = false){
    // Try the first format: 'd-m-Y h:i:sa'
    $dateTime = DateTime::createFromFormat('d-m-Y h:i:sa', $dateTimeStr);
    
    // If the first format fails, try the second format: 'Y-m-d H:i:s' (The template has a messy bug which stores the date in different format during order update)
    if (!$dateTime) {
        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $dateTimeStr);
    }

     // If both formats fail, return the original string
     if (!$dateTime) {
        return $dateTimeStr;
    }

    // Convert to human readable format
    $humanReadableDate = $dateTime->format('l, jS F Y, h:i A');

    if($get_array){
        // break comma separated string to individual strings in an array and return it.
        return explode(', ', $humanReadableDate);
    }

    return $humanReadableDate;
}
/* Modified output escaping (stripping slashes), handles complex db array data directly, multi-dimensional */
function output_escaping_new($array)
{
    $exclude_fields = ["images", "other_images"];
    $t = &get_instance();

    if (!empty($array)) {
        if (is_array($array)) {
            $data = array();
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    // Recursively call output_escaping on nested arrays
                    $data[$key] = output_escaping_new($value);
                } elseif (is_object($value)) {
                    // Recursively call output_escaping on nested objects
                    $data[$key] = output_escaping_new($value);
                } else {
                    if (!in_array($key, $exclude_fields)) {
                        $data[$key] = stripslashes($value);  // Correct function is stripslashes(), not stripcslashes()
                    } else {
                        $data[$key] = $value;
                    }
                }
            }
            return $data;
        } elseif (is_object($array)) {
            // Handle objects
            $data = new stdClass();
            foreach ($array as $key => $value) {
                if (!in_array($key, $exclude_fields)) {
                    $data->$key = stripslashes($value);  // Correct function is stripslashes(), not stripcslashes()
                } else {
                    $data->$key = $value;
                }
            }
            return $data;
        } else {
            return stripslashes($array);  // Correct function is stripslashes(), not stripcslashes()
        }
    }
}

/**
 * Checks the store's configuration for combinations that leave it unable to trade, and
 * returns one entry per problem found.
 *
 * This exists because of a failure this codebase has no other way to report. The store is
 * configured for Shiprocket-only delivery (shipping_method.shiprocket_shipping_method = 1,
 * local_shipping_method = 0) while the Shiprocket email and password are both blank, so every
 * serviceability lookup fails and check_cart_products_delivarable() marks every cart line
 * undeliverable. The customer gets "Some of the item(s) are not delivarable on selected
 * address. Try changing address or modify your cart items." on an address that is perfectly
 * fine, and no order can be completed at all. Verified on this database: turning local
 * shipping on let the exact same cart check out immediately, and the last storefront order in
 * the table predates the current settings - everything since is an in-panel POS order.
 *
 * Nothing anywhere told the admin. The Shipping Settings form does reject a save that leaves
 * the credentials blank, so the state cannot be created through the UI any more, but a store
 * already in it stays there silently. These checks make it visible on the dashboard.
 *
 * Each returned entry has:
 *   severity - 'critical' (the storefront cannot take orders) or 'warning'
 *   title    - short label
 *   message  - what is wrong and what it causes
 *   url      - admin page that fixes it (may be empty)
 *   link     - label for that page
 *
 * @return array
 */
function validate_store_configuration()
{
    $t = &get_instance();
    $problems = [];

    $system   = get_settings('system_settings', true);
    $shipping = get_settings('shipping_method', true);
    $payment  = get_settings('payment_method', true);

    $system   = is_array($system) ? $system : [];
    $shipping = is_array($shipping) ? $shipping : [];
    $payment  = is_array($payment) ? $payment : [];

    $shiprocket_on = isset($shipping['shiprocket_shipping_method']) && $shipping['shiprocket_shipping_method'] == 1;
    $local_on      = isset($shipping['local_shipping_method']) && $shipping['local_shipping_method'] == 1;

    /* ---------------------------------------------------------------- shipping */

    if (!$shiprocket_on && !$local_on) {
        $problems[] = [
            'severity' => 'critical',
            'title'    => 'No delivery method is enabled',
            'message'  => 'Neither Shiprocket nor local (zipcode) shipping is switched on, so no product can be '
                . 'delivered anywhere and checkout will reject every order.',
            'url'      => base_url('admin/shipping-settings'),
            'link'     => 'Shipping Methods',
        ];
    }

    if ($shiprocket_on && (empty(trim((string) ($shipping['email'] ?? ''))) || empty(trim((string) ($shipping['password'] ?? ''))))) {
        $problems[] = [
            'severity' => 'critical',
            'title'    => 'Shiprocket is enabled but has no credentials',
            'message'  => 'Shiprocket is the ' . ($local_on ? 'primary' : 'only') . ' delivery method but its email '
                . 'and/or password are blank, so every serviceability check fails. Customers are told their address '
                . 'is not deliverable'
                . ($local_on ? '' : ' and no order can be placed at all')
                . '. Enter the credentials of a Shiprocket API user - created under Settings > API in the '
                . 'Shiprocket panel, with an email not already registered there; the normal account login '
                . 'will not authenticate. Or switch on local shipping.',
            'url'      => base_url('admin/shipping-settings'),
            'link'     => 'Shipping Methods',
        ];
    }

    if ($shiprocket_on) {
        // Shiprocket books a pickup from a registered address. A product whose seller has none
        // can never be quoted or collected, whatever the customer's address is.
        //
        // Counted on products.pickup_location being blank before, which reported 278 of 290 here
        // and was the wrong question: that column is an optional per-product override, and
        // resolve_seller_pickup_location() falls back to the seller's own registered address. What
        // actually blocks a shipment is the SELLER having registered nothing at all.
        // Restricted to products that could ACTUALLY be sold if they had a pickup address, i.e.
        // whose seller is approved and active. Counting every product regardless made this cry
        // wolf: on this store all 14 it was reporting belong to sellers who are not trading at
        // all (two have no seller profile, one is deactivated), so their products are off the
        // shop for a quite different reason and no pickup address would change that. A warning
        // that cannot be acted on teaches people to ignore the panel.
        $missing_pickup = (int) $t->db->query(
            "SELECT COUNT(*) AS total
               FROM products p
              WHERE p.status = 1
                AND EXISTS (
                    SELECT 1 FROM seller_data sd
                     WHERE sd.user_id = p.seller_id AND sd.status = 1
                )
                AND NOT EXISTS (
                    SELECT 1 FROM pickup_locations pl
                     WHERE pl.seller_id = p.seller_id AND pl.pin_code <> ''
                )"
        )->row()->total;

        if ($missing_pickup > 0) {
            $active_products = (int) $t->db->where('status', 1)->count_all_results('products');
            $sellers_without = (int) $t->db->query(
                // Same active-seller restriction as the product count above, so the two numbers
                // in the message describe the same set rather than disagreeing.
                "SELECT COUNT(DISTINCT p.seller_id) AS total
                   FROM products p
                  WHERE p.status = 1
                    AND EXISTS (
                        SELECT 1 FROM seller_data sd
                         WHERE sd.user_id = p.seller_id AND sd.status = 1
                    )
                    AND NOT EXISTS (
                        SELECT 1 FROM pickup_locations pl
                         WHERE pl.seller_id = p.seller_id AND pl.pin_code <> ''
                    )"
            )->row()->total;

            $problems[] = [
                'severity' => $local_on ? 'warning' : 'critical',
                'title'    => 'Sellers with no pickup address',
                'message'  => $sellers_without . ' seller(s), covering ' . $missing_pickup . ' of '
                    . $active_products . ' live products, have not registered a pickup address. Shiprocket '
                    . 'cannot quote or collect those, so they read as undeliverable to every customer. '
                    . 'Add a pickup address for each seller - or import the ones already on the Shiprocket '
                    . 'account rather than retyping them.',
                'url'      => base_url('admin/Pickup_location/manage-pickup-locations'),
                'link'     => 'Pickup Locations',
            ];
        }
    }

    if ($local_on) {
        $zipcodes = (int) $t->db->count_all_results('zipcodes');
        if ($zipcodes === 0) {
            $problems[] = [
                'severity' => 'critical',
                'title'    => 'Local shipping has no zipcodes',
                'message'  => 'Local shipping is enabled but no delivery zipcodes are configured, so every address '
                    . 'is treated as out of range.',
                'url'      => base_url('admin/area/manage-zipcodes'),
                'link'     => 'Zipcodes',
            ];
        }
    }

    /* ---------------------------------------------------------------- catalogue */

    // The storefront listing (fetch_product()) requires ALL of: products.status = 1,
    // products.listing_visibility = 1, at least one active variant, an ACTIVE seller_data row
    // for the seller, and a category row that still exists. Each of those is a reasonable rule
    // on its own, and each is applied silently - a product that fails one simply is not in the
    // result. Overlap them and a catalogue can disappear completely with nothing logged and no
    // error anywhere. That is the current state of this store: 290 live products, and
    // products/ajax_get_products returns {"total":0}. Whoever runs the shop has no way to
    // discover that from the admin panel, where all 290 look perfectly healthy.
    $active_products = (int) $t->db->where('status', 1)->count_all_results('products');

    if ($active_products > 0) {
        $visible = (int) $t->db->query(
            "SELECT COUNT(DISTINCT p.id) AS total
               FROM products p
               JOIN product_variants pv ON pv.product_id = p.id AND pv.status = 1
               JOIN seller_data sd ON sd.user_id = p.seller_id AND sd.status = 1
               LEFT JOIN categories c ON c.id = p.category_id
              WHERE p.status = 1
                AND p.listing_visibility = 1
                AND (c.status = '1' OR c.status = '0' OR (c.id IS NOT NULL AND c.status IS NULL))"
        )->row()->total;

        if ($visible === 0 || $visible < ($active_products * 0.25)) {
            // Report the reasons, so this points at the fix rather than just the symptom.
            $counts = $t->db->query(
                "SELECT
                    (SELECT COUNT(*) FROM products WHERE status = 1 AND listing_visibility <> 1) AS plan_hidden,
                    (SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id
                      WHERE p.status = 1 AND c.id IS NULL) AS no_category,
                    (SELECT COUNT(*) FROM products p LEFT JOIN seller_data sd
                        ON sd.user_id = p.seller_id AND sd.status = 1
                      WHERE p.status = 1 AND sd.id IS NULL) AS no_active_seller,
                    (SELECT COUNT(*) FROM products p LEFT JOIN product_variants pv
                        ON pv.product_id = p.id AND pv.status = 1
                      WHERE p.status = 1 AND pv.id IS NULL) AS no_active_variant"
            )->row_array();

            $reasons = [];
            if (!empty($counts['plan_hidden'])) {
                $reasons[] = $counts['plan_hidden'] . ' are hidden by their seller\'s plan listing limit';
            }
            if (!empty($counts['no_category'])) {
                $reasons[] = $counts['no_category'] . ' point at a category that no longer exists';
            }
            if (!empty($counts['no_active_seller'])) {
                $reasons[] = $counts['no_active_seller'] . ' belong to a seller with no approved seller profile';
            }
            if (!empty($counts['no_active_variant'])) {
                $reasons[] = $counts['no_active_variant'] . ' have no active variant';
            }

            $problems[] = [
                'severity' => ($visible === 0) ? 'critical' : 'warning',
                'title'    => ($visible === 0)
                    ? 'No products are visible on the storefront'
                    : 'Most products are hidden from the storefront',
                'message'  => $visible . ' of ' . $active_products . ' live products actually appear in the shop. '
                    . (empty($reasons) ? '' : 'Of the rest: ' . implode('; ', $reasons) . '. ')
                    . 'A product must be active, within its seller\'s plan listing limit, have an active variant, '
                    . 'belong to an approved seller and sit in a category that still exists.',
                'url'      => base_url('admin/product'),
                'link'     => 'Products',
            ];
        }
    }

    /* ---------------------------------------------------------------- payment */

    $gateway_flags = [
        'razorpay_payment_method', 'paypal_payment_method', 'paystack_payment_method',
        'stripe_payment_method', 'flutterwave_payment_method', 'paytm_payment_method',
        'midtrans_payment_method', 'direct_bank_transfer', 'cod_payment_method',
        'my_fatoorah_payment_method', 'instamojo_payment_method', 'phonepe_payment_method',
    ];
    $enabled_gateways = 0;
    foreach ($gateway_flags as $flag) {
        if (isset($payment[$flag]) && $payment[$flag] == 1) {
            $enabled_gateways++;
        }
    }
    if ($enabled_gateways === 0) {
        $problems[] = [
            'severity' => 'critical',
            'title'    => 'No payment method is enabled',
            'message'  => 'Every payment gateway is switched off, so a customer has no way to pay for an order.',
            'url'      => base_url('admin/payment-settings'),
            'link'     => 'Payment Methods',
        ];
    }

    /* ---------------------------------------------------------------- store basics */

    if (empty(trim((string) ($system['currency'] ?? '')))) {
        $problems[] = [
            'severity' => 'critical',
            'title'    => 'No currency is set',
            'message'  => 'Every price on the storefront is rendered without a currency symbol.',
            'url'      => base_url('admin/setting'),
            'link'     => 'Store Settings',
        ];
    }

    foreach (['support_email' => 'support email', 'support_number' => 'support phone number'] as $key => $label) {
        if (empty(trim((string) ($system[$key] ?? '')))) {
            $problems[] = [
                'severity' => 'warning',
                'title'    => 'No ' . $label,
                'message'  => 'The ' . $label . ' is blank. It is shown to customers across the site and used on '
                    . 'order notifications.',
                'url'      => base_url('admin/setting'),
                'link'     => 'Store Settings',
            ];
        }
    }

    if (isset($system['is_web_under_maintenance']) && $system['is_web_under_maintenance'] == 1) {
        $problems[] = [
            'severity' => 'warning',
            'title'    => 'The website is in maintenance mode',
            'message'  => 'Every visitor is being redirected to the maintenance page. Switch this off to reopen '
                . 'the storefront.',
            'url'      => base_url('admin/setting'),
            'link'     => 'Store Settings',
        ];
    }

    return $problems;
}

/**
 * Does this store actually run its own delivery staff?
 *
 * The owner has confirmed delivery is handled by Shiprocket, not by the built-in delivery-boy
 * feature, and there is not a single account in the `delivery_boy` group - the Delivery Boys menu
 * is switched off in the admin sidebar too. Any rule that REQUIRES a delivery boy is therefore
 * unsatisfiable here, and simply blocks the flow it was guarding. Two were doing exactly that:
 *
 *   - admin/Orders and seller/Orders refused to mark an order `shipped` without one. Both already
 *     made an exception for Shiprocket, but only once an order_tracking row with a live
 *     shiprocket_order_id existed - i.e. after the shipment had actually been booked. With no
 *     Shiprocket credentials configured nothing is ever booked, so the exception never applied and
 *     `shipped` was unreachable. The workaround was to jump processed -> delivered, which skips
 *     the "your order has shipped" notification to the customer entirely.
 *   - admin/Return_request refused to approve a return without one whenever Shiprocket was off,
 *     so a customer could raise a return that could never be actioned.
 *
 * "Runs its own delivery staff" means both halves are true: local shipping is switched on (so
 * somebody in-house is doing the collecting) AND at least one delivery-boy account exists to be
 * picked. Either half missing and asking for a delivery boy is asking for something that cannot
 * be given.
 *
 * Kept in one place so the three call sites cannot drift apart.
 *
 * @return bool
 */
function store_uses_delivery_boys()
{
    $t = &get_instance();

    $shipping = get_settings('shipping_method', true);
    $local_shipping_on = is_array($shipping)
        && isset($shipping['local_shipping_method'])
        && $shipping['local_shipping_method'] == 1;

    if (!$local_shipping_on) {
        return false;
    }

    return (int) $t->db
        ->from('users_groups ug')
        ->join('groups g', 'g.id = ug.group_id')
        ->where('g.name', 'delivery_boy')
        ->count_all_results() > 0;
}

/**
 * Works out which pickup address a product actually ships from.
 *
 * Shiprocket cannot quote, book or collect anything without a pickup address, and the platform
 * only ever looked at `products.pickup_location` - a free-text nickname typed per product. On this
 * store 278 of 290 live products have that column empty, so every Shiprocket path skipped them:
 *
 *   - check_cart_products_delivarable() gated the whole serviceability check on
 *     `trim($cart[$i]['pickup_location']) != ""`, so those products fell through with
 *     is_deliverable = false and NO message at all. The customer saw "Some of the item(s) are not
 *     delivarable on selected address" for an address that was perfectly serviceable, with nothing
 *     naming the real reason.
 *   - make_shipping_parcels() dropped them from the parcel set, so their weight never entered the
 *     rate request and the quoted delivery charge was wrong whenever a cart mixed them in.
 *
 * The nickname is a per-seller label, not a product-level fact: every seller registers their pickup
 * addresses once with Shiprocket, and in practice a seller ships everything from the same one. So
 * an empty column means "this seller's usual pickup address", not "this product cannot ship". This
 * resolves it that way - the product's own value wins when it is set and valid, otherwise the
 * seller's registered pickup address is used.
 *
 * Verified against this database: seller 7 has exactly one registered pickup location
 * ("Developer's Den", 201301) and 276 live products, only 12 of which named it. With this
 * fallback all 276 resolve.
 *
 * @param  string $product_pickup_location value of products.pickup_location (may be blank)
 * @param  int    $seller_id
 * @return array  ['pickup_location' => string, 'pin_code' => string] or [] when the seller has
 *                registered no usable pickup address at all
 */
/**
 * Is this pickup location one Shiprocket will actually accept for a booking?
 *
 * Shiprocket books a pickup by NICKNAME and rejects any nickname the account does not hold
 * ("Wrong Pickup location entered"). The rejection lands late: serviceability only needs a
 * pincode, so the shop happily quotes a delivery charge and takes the order, and the failure
 * only appears when a seller tries to ship it.
 *
 * `shiprocket_verified_at` is stamped by the pickup-location sync (migration 055 and
 * admin/seller Pickup_location) when Shiprocket confirms the address, so a NULL there means
 * the row was typed in locally and Shiprocket has never heard of it.
 *
 * Returns ['ok' => bool, 'reason' => string]. Only meaningful when Shiprocket shipping is the
 * active method - a store on local delivery has no Shiprocket to confirm anything, so this
 * says ok there.
 *
 * @param string $pickup_name  the nickname to book with
 * @param int    $seller_id    owner of the address; nicknames are not unique across sellers
 */
function shiprocket_pickup_is_bookable($pickup_name, $seller_id)
{
    $t = &get_instance();

    $shipping = get_settings('shipping_method', true);
    if (empty($shipping['shiprocket_shipping_method']) || $shipping['shiprocket_shipping_method'] != 1) {
        return ['ok' => true, 'reason' => ''];
    }

    $pickup_name = trim((string) $pickup_name);
    $seller_id = (int) $seller_id;

    if ($pickup_name === '' || $seller_id <= 0) {
        return ['ok' => false, 'reason' => 'No pickup location was selected for this shipment.'];
    }

    $row = $t->db->select('id, pickup_location, shiprocket_verified_at, phone_verified')
        ->where('seller_id', $seller_id)
        ->where('pickup_location', $pickup_name)
        ->get('pickup_locations')->row_array();

    if (empty($row)) {
        return [
            'ok'     => false,
            'reason' => 'Pickup location "' . $pickup_name . '" is not registered against this seller.',
        ];
    }

    // Older installs may not have the column yet; do not block on something we cannot check.
    if (!$t->db->field_exists('shiprocket_verified_at', 'pickup_locations')) {
        return ['ok' => true, 'reason' => ''];
    }

    if (empty($row['shiprocket_verified_at'])) {
        return [
            'ok'     => false,
            'reason' => 'Pickup location "' . $pickup_name . '" has not been confirmed by Shiprocket, so a '
                . 'shipment booked from it would be rejected. Add it under Pickup Location (it is then '
                . 'registered with Shiprocket), or pick one that has been confirmed.',
        ];
    }

    return ['ok' => true, 'reason' => ''];
}

function resolve_seller_pickup_location($product_pickup_location, $seller_id)
{
    static $cache = [];

    $seller_id = (int) $seller_id;
    $named = trim((string) $product_pickup_location);
    $key = $seller_id . '|' . $named;

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $t = &get_instance();
    $resolved = [];

    // 1. The product names one. Scope the lookup to the seller: `pickup_location` is a nickname,
    //    not a unique key, and two sellers can both call one "Warehouse" - an unscoped lookup
    //    returns whichever row comes first and can quote from a different seller's pincode.
    if ($named !== '' && $seller_id > 0) {
        $row = $t->db->select('pickup_location, pin_code, shiprocket_verified_at')
            ->where('seller_id', $seller_id)
            ->where('pickup_location', $named)
            ->where('pin_code !=', '')
            ->get('pickup_locations')->row_array();

        // Honouring the product's named location only helps if Shiprocket can actually book
        // from it. On this store the 12 products that name one all name the single address
        // Shiprocket has never confirmed, so taking it at face value dead-ended those items:
        // the booking was refused ("Wrong Pickup location entered") and there was nothing else
        // to fall back to. When Shiprocket is the shipping method and the named row is not
        // confirmed, fall through to the preference-ordered pick below - which is the same
        // address a blank product resolves to, and one that can actually ship.
        $named_is_usable = !empty($row);
        if ($named_is_usable && $t->db->field_exists('shiprocket_verified_at', 'pickup_locations')) {
            $shipping = get_settings('shipping_method', true);
            $shiprocket_on = !empty($shipping['shiprocket_shipping_method']) && $shipping['shiprocket_shipping_method'] == 1;
            if ($shiprocket_on && empty($row['shiprocket_verified_at'])) {
                $named_is_usable = false;
            }
        }

        if ($named_is_usable) {
            $resolved = ['pickup_location' => $row['pickup_location'], 'pin_code' => $row['pin_code']];
        }
    }

    // 2. Otherwise (blank, or naming a location that no longer exists) use the seller's own
    //    registered pickup address.
    //
    //    Ordering matters more than it looks. Shiprocket books a pickup by nickname and rejects
    //    any it does not hold on the account - "Wrong Pickup location entered" - and it will not
    //    schedule a pickup from an address whose phone is unverified. This store had one stale
    //    hand-entered row that Shiprocket has never heard of, and because the order was simply
    //    oldest-first that row won and every booking failed. It failed late, too: serviceability
    //    only needs a pincode, so the shop quoted a charge and took the order, and the rejection
    //    surfaced only when someone tried to ship it.
    //
    //    So: addresses Shiprocket has confirmed first, then phone-verified ones, then active,
    //    then oldest for a stable choice. Rows that have never been confirmed are still usable as
    //    a last resort - a store on local shipping has no Shiprocket to confirm anything.
    if (empty($resolved) && $seller_id > 0) {
        $has_verification = $t->db->field_exists('shiprocket_verified_at', 'pickup_locations');

        $t->db->select('pickup_location, pin_code')
            ->where('seller_id', $seller_id)
            ->where('pin_code !=', '');
        if ($has_verification) {
            $t->db->order_by('shiprocket_verified_at IS NULL', 'ASC', false)
                ->order_by('phone_verified', 'DESC');
        }
        $row = $t->db->order_by('status', 'DESC')
            ->order_by('id', 'ASC')
            ->limit(1)
            ->get('pickup_locations')->row_array();

        if (!empty($row)) {
            $resolved = ['pickup_location' => $row['pickup_location'], 'pin_code' => $row['pin_code']];
        }
    }

    $cache[$key] = $resolved;
    return $resolved;
}

/**
 * Generates a manifest for a shipment and stores its PDF URL (Shiprocket flow steps 7 and 8).
 *
 * Mirrors generate_label() / generate_invoice() above. Those two were wired up; the manifest was
 * not, so `order_tracking.manifest_url` stayed at the '' written when the shipment was created,
 * even though the seller app reads and offers that column next to the label and invoice.
 *
 * Two calls, because Shiprocket splits them: `manifests/generate` builds it, `manifests/print`
 * returns the URL. Generate is idempotent enough to re-run - an already-manifested shipment simply
 * reports so - and print is what actually yields something to store.
 */
function generate_manifest($shipment_id)
{
    $t = &get_instance();
    $t->load->library(['Shiprocket']);

    /*
     * The two steps do NOT take the same identifier, which is easy to miss because the local
     * variable is one number: `manifests/generate` takes the SHIPMENT id, `manifests/print` takes
     * the SHIPROCKET ORDER id. This passed the shipment id to both, so the print step asked
     * Shiprocket to manifest an order id that belongs to a different record entirely - it could
     * only ever have returned nothing, or someone else's manifest.
     */
    $tracking = fetch_details('order_tracking', ['shipment_id' => $shipment_id], 'shiprocket_order_id');
    $shiprocket_order_id = !empty($tracking) ? $tracking[0]['shiprocket_order_id'] : '';

    if (empty($shiprocket_order_id)) {
        log_message('error', 'generate_manifest: no shiprocket_order_id recorded against shipment ' . $shipment_id
            . ', so its manifest cannot be printed.');
        return null;
    }

    $generated = $t->shiprocket->generate_manifests($shipment_id);
    $res = $t->shiprocket->print_manifest($shiprocket_order_id);

    if (shiprocket_result_ok('manifest', $res)) {
        $t->db->set(['manifest_url' => $res['manifest_url']])->where('shipment_id', $shipment_id)->update('order_tracking');
    } else {
        log_message('error', 'Shiprocket manifest not generated for shipment ' . $shipment_id . ': '
            . shiprocket_result_message($res, 'manifest could not be generated')
            . ' (generate step: ' . shiprocket_result_message($generated, 'no detail') . ')');
    }

    return $res;
}

/**
 * A parcel weight Shiprocket will accept, in kilograms.
 *
 * Shiprocket rejects a serviceability or rate request whose weight is empty or zero - it answers
 * "Weight Required" and no courier is returned, which the storefront then shows the customer as
 * "not deliverable on selected address". 266 of the 299 product variants on this store carry
 * weight 0 (the shipping fields were added to the product form after most of the catalogue was
 * entered), so that rejection applied to almost everything in the shop.
 *
 * build_shiprocket_return_payload() already had exactly this fallback - a nominal 0.5 kg - because
 * the return path hit the same wall first. The forward paths never got it, so the two disagreed:
 * a return could be booked for an item whose delivery could not even be quoted. Same constant,
 * defined once, used by both.
 *
 * A nominal weight is the right default rather than refusing to quote: it is what the seller would
 * be charged for anyway at the courier's minimum slab, and an under-declared weight is reconciled
 * by the courier at pickup. Setting real weights on the products is still the correct fix, and the
 * dashboard reports how many are missing.
 */
function shiprocket_parcel_weight($weight)
{
    $weight = (float) $weight;
    return ($weight > 0) ? $weight : SHIPROCKET_NOMINAL_WEIGHT_KG;
}

/**
 * A parcel dimension Shiprocket will accept, in centimetres.
 *
 * Companion to shiprocket_parcel_weight() above, and for the same reason: Shiprocket rejects a
 * shipment whose length, breadth or height is 0 on any axis, and the product shipping fields
 * were added to this catalogue after most of it was entered, so the variants carry 0 in all
 * four. build_shiprocket_return_payload() already open-coded this fallback; the forward paths
 * need the identical one, so it lives in one place.
 */
/**
 * One line of address text, fit to print on a courier label.
 *
 * Two things wrong with sending these columns straight through, both visible in what this
 * store would have shipped:
 *
 *  - The backslashes are compounded. `escape_array()` runs on the way IN at several call sites
 *    and the escapes stack up on every edit, so address 14 holds the literal text
 *    "Noida Sector 57\\\\r\\\\nA-56". One stripcslashes() - which is all output_escaping() does -
 *    leaves "\\r\\n" sitting in the middle of the street name. Unescaping until it stops
 *    changing gets back the real line break.
 *  - A label field is one line, not a textarea. The real line break is then folded into a
 *    comma so the courier prints "Noida Sector 57, A-56" rather than losing it.
 *
 * Bounded at four passes so a pathological value cannot spin, and left alone once stable.
 */
function shiprocket_address_text($value)
{
    $value = (string) $value;

    for ($i = 0; $i < 4; $i++) {
        $unescaped = stripcslashes($value);
        if ($unescaped === $value) {
            break;
        }
        $value = $unescaped;
    }

    $value = preg_replace('/\s*[\r\n]+\s*/', ', ', $value);
    $value = preg_replace('/\s+/', ' ', $value);
    $value = preg_replace('/(,\s*){2,}/', ', ', $value);

    return trim($value, " ,\t");
}

function shiprocket_parcel_dimension($value)
{
    $value = (float) $value;
    return ($value > 0) ? $value : SHIPROCKET_NOMINAL_DIMENSION_CM;
}

/**
 * Order item ids on an order that already belong to a live FORWARD Shiprocket shipment.
 *
 * `order_tracking`.`order_item_id` is a comma-separated list, not an id, so this cannot be a
 * WHERE clause - the rows are read and the lists exploded. Returns an id => true map.
 *
 * is_return = 0 because a reverse pickup is booked against the same order item and must not
 * make the forward leg look done; is_canceled = 0 so a cancelled shipment can be rebooked.
 */
function shiprocket_booked_order_items($order_id)
{
    $t = &get_instance();

    $rows = $t->db->select('order_item_id')
        ->where('order_id', (int) $order_id)
        ->where('is_return', 0)
        ->where('is_canceled', 0)
        ->get('order_tracking')->result_array();

    $booked = [];
    foreach ($rows as $row) {
        foreach (explode(',', (string) $row['order_item_id']) as $id) {
            $id = (int) trim($id);
            if ($id > 0) {
                $booked[$id] = true;
            }
        }
    }
    return $booked;
}

/**
 * Books the FORWARD Shiprocket shipment(s) for an order - seller -> customer.
 *
 * Nothing did this. Shiprocket::create_order() had exactly two callers, both of them a
 * seller/admin clicking "Create Shiprocket Order" on the order edit screen and typing the
 * parcel weight and dimensions in by hand. So a customer could place an order, be told it was
 * received, and the shipment simply never existed at Shiprocket until somebody remembered to
 * go and raise it. On this database that is every order ever placed: `order_tracking` holds 0
 * rows against 43 order items, 18 of which are still sitting at "received".
 *
 * This is that missing step, run automatically when the order is placed (and again if an
 * awaiting order is later confirmed). Design notes:
 *
 *  - One shipment PER SELLER PER PICKUP LOCATION, which is how the parcel already splits in
 *    `order_charges` and how the manual screen splits it. A two-seller order becomes two
 *    Shiprocket orders, each collected from its own seller.
 *  - Idempotent. Items already carrying a live forward shipment are skipped, so re-running
 *    this - a retry, an awaiting order being confirmed, the manual button afterwards - cannot
 *    double-book a parcel.
 *  - NON-FATAL, always. Shiprocket being down, unconfigured, or refusing one parcel must never
 *    fail a checkout the customer has already paid for: the failure is logged and the order
 *    stands, and the seller's manual "Create Shiprocket Order" button remains the retry path.
 *  - Digital products are skipped - there is nothing to ship - as are cancelled and returned
 *    items, and anything still 'awaiting' (payment unconfirmed).
 *
 * Booking here creates the order in the Shiprocket panel only. Generating the AWB and
 * requesting the pickup stay deliberately manual: those commit the seller to a courier and
 * cost money.
 *
 * @param  int      $order_id
 * @param  int|null $only_seller_id  restrict to one seller's parcel
 * @return array    ['error' => bool, 'message' => string, 'data' => [per-parcel results]]
 */
function create_shiprocket_forward_shipment($order_id, $only_seller_id = null)
{
    $t = &get_instance();
    $order_id = (int) $order_id;

    $shipping_settings = get_settings('shipping_method', true);
    if (empty($shipping_settings['shiprocket_shipping_method']) || $shipping_settings['shiprocket_shipping_method'] != 1) {
        return ['error' => true, 'message' => 'Shiprocket shipping is not enabled.', 'data' => []];
    }

    $order = fetch_details('orders', ['id' => $order_id], 'id,user_id,address_id,mobile,date_added,payment_method,delivery_charge');
    if (empty($order)) {
        return ['error' => true, 'message' => 'Order not found.', 'data' => []];
    }
    $order = $order[0];

    if (empty($order['address_id'])) {
        // Digital-only orders carry no address at all; there is nothing to ship.
        return ['error' => true, 'message' => 'This order has no delivery address, so there is nothing to ship.', 'data' => []];
    }

    $address = fetch_details('addresses', ['id' => $order['address_id']], 'address,city,city_id,state,country,pincode,mobile,name');
    if (empty($address) || empty($address[0]['pincode'])) {
        log_message('error', 'create_shiprocket_forward_shipment: order ' . $order_id . ' has no usable delivery address.');
        return ['error' => true, 'message' => 'The delivery address on this order is incomplete, so a shipment cannot be booked.', 'data' => []];
    }
    $address = $address[0];

    // The address's own city text wins over the `cities` lookup - see the long note in
    // seller/Orders::create_shiprocket_order(); city_id is a legacy FK into 18 demo cities and
    // answers "Mumbai" for Delhi addresses.
    $city = !empty($address['city']) ? $address['city'] : '';
    if (empty($city) && !empty($address['city_id'])) {
        $city_row = fetch_details('cities', ['city_id' => $address['city_id']], 'city_name');
        $city = !empty($city_row) ? $city_row[0]['city_name'] : '';
    }

    $customer = fetch_details('users', ['id' => $order['user_id']], 'username,email,mobile');
    $customer_name  = !empty($address['name']) ? $address['name'] : (!empty($customer[0]['username']) ? $customer[0]['username'] : 'Customer');
    $customer_phone = !empty($address['mobile']) ? $address['mobile'] : (!empty($order['mobile']) ? $order['mobile'] : (isset($customer[0]['mobile']) ? $customer[0]['mobile'] : ''));
    $customer_email = isset($customer[0]['email']) ? $customer[0]['email'] : '';

    $is_cod = (strtoupper((string) $order['payment_method']) === 'COD');

    $booked = shiprocket_booked_order_items($order_id);

    $t->db->select('oi.id, oi.seller_id, oi.quantity, oi.price, oi.sub_total, oi.tax_amount, oi.active_status,
                    p.name as product_name, p.slug as product_slug, p.sku as product_sku, p.type as product_type,
                    p.pickup_location, pv.sku as variant_sku, pv.weight, pv.length, pv.breadth, pv.height')
        ->join('product_variants pv', 'pv.id = oi.product_variant_id', 'left')
        ->join('products p', 'p.id = pv.product_id', 'left')
        ->where('oi.order_id', $order_id)
        ->where_not_in('oi.active_status', ['awaiting', 'cancelled', 'returned']);
    if (!empty($only_seller_id)) {
        $t->db->where('oi.seller_id', (int) $only_seller_id);
    }
    $items = $t->db->get('order_items oi')->result_array();

    if (empty($items)) {
        return ['error' => true, 'message' => 'No shippable items on this order.', 'data' => []];
    }

    /* Group into parcels: one per seller per pickup location, as order_charges already splits. */
    $parcels = [];
    foreach ($items as $item) {
        if (isset($item['product_type']) && $item['product_type'] == 'digital_product') {
            continue;
        }
        if (isset($booked[(int) $item['id']])) {
            continue;
        }

        $pickup = resolve_seller_pickup_location(
            isset($item['pickup_location']) ? $item['pickup_location'] : '',
            $item['seller_id']
        );
        $pickup_name = isset($pickup['pickup_location']) ? $pickup['pickup_location'] : '';
        if ($pickup_name === '') {
            log_message('error', 'create_shiprocket_forward_shipment: seller ' . $item['seller_id']
                . ' has no usable pickup location, so order item ' . $item['id'] . ' on order '
                . $order_id . ' could not be booked.');
            continue;
        }

        $key = $item['seller_id'] . '|' . $pickup_name;
        if (!isset($parcels[$key])) {
            $parcels[$key] = [
                'seller_id'       => (int) $item['seller_id'],
                'pickup_location' => $pickup_name,
                'pickup_pincode'  => isset($pickup['pin_code']) ? $pickup['pin_code'] : '',
                'item_ids'        => [],
                'items'           => [],
                'sub_total'       => 0,
                'weight'          => 0,
                'length'          => 0,
                'breadth'         => 0,
                'height'          => 0,
            ];
        }

        $qty = max(1, (int) $item['quantity']);
        $sku = !empty($item['variant_sku'])
            ? $item['variant_sku']
            : (!empty($item['product_sku']) ? $item['product_sku'] : $item['product_slug']);

        $parcels[$key]['item_ids'][] = (int) $item['id'];
        $parcels[$key]['sub_total'] += (float) $item['sub_total'];
        // Weight is cumulative across the parcel; the box only has to be as large as its
        // largest item on each axis.
        $parcels[$key]['weight']  += shiprocket_parcel_weight($item['weight']) * $qty;
        $parcels[$key]['length']   = max($parcels[$key]['length'], shiprocket_parcel_dimension($item['length']));
        $parcels[$key]['breadth']  = max($parcels[$key]['breadth'], shiprocket_parcel_dimension($item['breadth']));
        $parcels[$key]['height']   = max($parcels[$key]['height'], shiprocket_parcel_dimension($item['height']));
        $parcels[$key]['items'][]  = [
            'name'          => shiprocket_address_text($item['product_name']),
            'sku'           => !empty($sku) ? $sku : ('ITEM-' . $item['id']),
            'units'         => $qty,
            'selling_price' => (float) $item['price'],
            'discount'      => 0,
            'tax'           => (float) $item['tax_amount'],
        ];
    }

    if (empty($parcels)) {
        return ['error' => false, 'message' => 'Every shippable item on this order is already booked with Shiprocket.', 'data' => []];
    }

    $t->load->library(['Shiprocket']);
    $results = [];
    $booked_any = false;

    foreach ($parcels as $parcel) {
        $result = [
            'seller_id'       => $parcel['seller_id'],
            'pickup_location' => $parcel['pickup_location'],
            'order_item_ids'  => implode(',', $parcel['item_ids']),
            'error'           => true,
            'message'         => '',
        ];

        // Refuse a pickup address Shiprocket has not confirmed BEFORE the request goes out -
        // it would come back 422 "Wrong Pickup location entered".
        $bookable = shiprocket_pickup_is_bookable($parcel['pickup_location'], $parcel['seller_id']);
        if (!$bookable['ok']) {
            $result['message'] = $bookable['reason'];
            log_message('error', 'create_shiprocket_forward_shipment: order ' . $order_id . ' seller '
                . $parcel['seller_id'] . ' - ' . $bookable['reason']);
            $results[] = $result;
            continue;
        }

        // Per-seller delivery charge, as split across the parcels at checkout. Only a COD
        // parcel collects it from the customer, which is what Shiprocket's sub_total means.
        $charge_row = fetch_details('order_charges', ['order_id' => $order_id, 'seller_id' => $parcel['seller_id']], 'delivery_charge');
        $delivery_charge = !empty($charge_row) ? (float) $charge_row[0]['delivery_charge'] : (float) $order['delivery_charge'];
        if (!$is_cod) {
            $delivery_charge = 0;
        }

        // Best-effort: a recommended courier for this pickup -> delivery leg. Unserviceable or
        // unreachable simply means no preference is recorded and Shiprocket picks at AWB time.
        $courier_company_id = 0;
        if (!empty($parcel['pickup_pincode'])) {
            $serviceability = $t->shiprocket->check_serviceability([
                'pickup_postcode'   => $parcel['pickup_pincode'],
                'delivery_postcode' => $address['pincode'],
                'cod'               => $is_cod ? '1' : '0',
                'weight'            => $parcel['weight'],
            ]);
            $recommended = shiprocket_recomended_data($serviceability);
            $courier_company_id = isset($recommended['courier_company_id']) ? (int) $recommended['courier_company_id'] : 0;
        }

        // Deterministic reference, so a retry of the same parcel reuses it rather than raising
        // a second Shiprocket order for the same goods. Kept short - Shiprocket caps this
        // field - and readable back to the order and the seller.
        $reference = $order_id . '-' . $parcel['seller_id'] . '-' . substr(sha1(implode(',', $parcel['item_ids'])), 0, 6);

        $payload = [
            'order_id'              => $reference,
            'order_date'            => date('Y-m-d H:i', strtotime($order['date_added'])),
            'pickup_location'       => $parcel['pickup_location'],
            'billing_customer_name' => shiprocket_address_text($customer_name),
            'billing_last_name'     => '',
            'billing_address'       => shiprocket_address_text($address['address']),
            'billing_city'          => shiprocket_address_text($city),
            'billing_pincode'       => $address['pincode'],
            'billing_state'         => shiprocket_address_text($address['state']),
            'billing_country'       => shiprocket_address_text($address['country']),
            'billing_email'         => $customer_email,
            'billing_phone'         => $customer_phone,
            'shipping_is_billing'   => true,
            'order_items'           => $parcel['items'],
            'payment_method'        => $is_cod ? 'COD' : 'Prepaid',
            'sub_total'             => round($parcel['sub_total'] + $delivery_charge, 2),
            'length'                => $parcel['length'],
            'breadth'               => $parcel['breadth'],
            'height'                => $parcel['height'],
            'weight'                => round($parcel['weight'], 3),
        ];

        $response = $t->shiprocket->create_order($payload);

        if (!is_array($response) || empty($response['order_id'])) {
            $reason = 'Shiprocket did not accept the shipment.';
            if (method_exists($t->shiprocket, 'last_error') && !empty($t->shiprocket->last_error())) {
                $reason = (string) $t->shiprocket->last_error();
            } elseif (is_array($response) && !empty($response['message'])) {
                $reason = is_array($response['message']) ? implode(' ', array_map('strval', $response['message'])) : $response['message'];
            }
            $result['message'] = $reason;
            log_message('error', 'create_shiprocket_forward_shipment: order ' . $order_id . ' seller '
                . $parcel['seller_id'] . ' rejected - ' . $reason);
            $results[] = $result;
            continue;
        }

        $t->db->insert('order_tracking', [
            'order_id'              => $order_id,
            'order_item_id'         => implode(',', $parcel['item_ids']),
            'shiprocket_order_id'   => $response['order_id'],
            'shipment_id'           => isset($response['shipment_id']) ? $response['shipment_id'] : 0,
            'courier_company_id'    => $courier_company_id,
            'is_return'             => 0,
            'pickup_status'         => 0,
            'pickup_scheduled_date' => '',
            'pickup_token_number'   => '',
            'status'                => 0,
            'others'                => '',
            'pickup_generated_date' => '',
            'data'                  => '',
            'date'                  => '',
            'manifest_url'          => '',
            'label_url'             => '',
            'invoice_url'           => '',
            'is_canceled'           => 0,
            'tracking_id'           => '',
            'url'                   => '',
        ]);

        $booked_any = true;
        $result['error']   = false;
        $result['message'] = 'Shipment booked with Shiprocket.';
        $result['shiprocket_order_id'] = $response['order_id'];
        $result['shipment_id'] = isset($response['shipment_id']) ? $response['shipment_id'] : 0;
        $results[] = $result;
    }

    $failed = array_values(array_filter($results, function ($r) {
        return $r['error'] === true;
    }));

    if ($booked_any) {
        $message = empty($failed)
            ? 'Shipment booked with Shiprocket.'
            : 'Some parcels were booked with Shiprocket; ' . $failed[0]['message'];
    } else {
        $message = !empty($failed) ? $failed[0]['message'] : 'No shipment could be booked with Shiprocket.';
    }

    return ['error' => !$booked_any, 'message' => $message, 'data' => $results];
}

/**
 * Parcel weight and dimensions to pre-fill the manual "Create Shiprocket Order" form with.
 *
 * custom.js has always POSTed to `{seller|admin}/shiprocket/parcel-defaults` to fill those four
 * required fields the moment a pickup location is picked - but no such controller existed, so
 * the request 404'd, the jQuery `.done()` handler never ran (an HTML error page does not parse
 * as JSON) and the seller was left with four empty required fields and no explanation. Nothing
 * logged it either, because the AJAX call had no error handler.
 *
 * Same arithmetic as create_shiprocket_forward_shipment(): weight is cumulative across the
 * parcel, the box only has to be as large as its largest item on each axis, and the nominal
 * fallbacks cover the variants whose shipping fields were never filled in.
 *
 * Read from the database, not from the order_items JSON the browser posts, which is
 * client-controlled - the seller can still overtype the values in the form, which is the point
 * of the form.
 */
function shiprocket_parcel_defaults($order_id, $seller_id, $pickup_location)
{
    $t = &get_instance();

    $items = $t->db->select('oi.id, oi.seller_id, oi.quantity, p.pickup_location, p.type as product_type,
                             pv.weight, pv.length, pv.breadth, pv.height')
        ->join('product_variants pv', 'pv.id = oi.product_variant_id', 'left')
        ->join('products p', 'p.id = pv.product_id', 'left')
        ->where('oi.order_id', (int) $order_id)
        ->where('oi.seller_id', (int) $seller_id)
        ->where_not_in('oi.active_status', ['cancelled', 'returned'])
        ->get('order_items oi')->result_array();

    $weight = 0;
    $length = 0;
    $breadth = 0;
    $height = 0;
    $matched = 0;

    foreach ($items as $item) {
        if (isset($item['product_type']) && $item['product_type'] == 'digital_product') {
            continue;
        }
        $resolved = resolve_seller_pickup_location(
            isset($item['pickup_location']) ? $item['pickup_location'] : '',
            $item['seller_id']
        );
        $resolved_name = isset($resolved['pickup_location']) ? $resolved['pickup_location'] : '';
        if ($resolved_name !== trim((string) $pickup_location)) {
            continue;
        }

        $matched++;
        $weight += shiprocket_parcel_weight($item['weight']) * max(1, (int) $item['quantity']);
        $length  = max($length, shiprocket_parcel_dimension($item['length']));
        $breadth = max($breadth, shiprocket_parcel_dimension($item['breadth']));
        $height  = max($height, shiprocket_parcel_dimension($item['height']));
    }

    if ($matched === 0) {
        return ['error' => true, 'message' => 'No items of this seller ship from that pickup location.', 'data' => []];
    }

    return [
        'error'   => false,
        'message' => 'Parcel defaults calculated.',
        'data'    => [
            'parcel_weight'  => round($weight, 3),
            'parcel_length'  => $length,
            'parcel_breadth' => $breadth,
            'parcel_height'  => $height,
        ],
    ];
}

/**
 * Customer-facing name for an order status.
 *
 * The storefront printed ucwords() of the raw column - so the moment an order was placed the
 * customer saw "Received", which reads as "you have received this", and the store was fielding
 * "why does it say received when nothing has arrived?". These are internal fulfilment states:
 * 'received' means the STORE received the order. Nothing about the underlying ladder changes
 * here - only the words shown to the customer.
 */
function order_status_label($status)
{
    $labels = [
        'awaiting'                => 'Payment Pending',
        'received'                => 'Order Placed',
        'processed'               => 'Packed',
        'shipped'                 => 'Shipped',
        'delivered'               => 'Delivered',
        'cancelled'               => 'Cancelled',
        'returned'                => 'Returned',
        'return_request_pending'  => 'Return Requested',
        'return_request_approved' => 'Return Approved',
        'return_request_decline'  => 'Return Declined',
    ];

    $status = trim(strtolower((string) $status));
    return isset($labels[$status]) ? $labels[$status] : ucwords(str_replace('_', ' ', $status));
}

/**
 * Badge tone for an order status, for the `czap-badge--*` classes in
 * account-suite.css.
 *
 * Lives beside order_status_label() so the three storefront order screens
 * (dashboard, orders list, order details) cannot drift into colouring the same
 * status three different ways. Returns 'ok' | 'bad' | 'info' | 'warn'.
 */
/**
 * Prepares the admin-authored About Us copy for the About page.
 *
 * The stored blob is 50 flat <p> tags with no headings at all - the section
 * titles are written as paragraphs containing nothing but a <strong>, which is
 * what a WYSIWYG produces when someone bolds a line and presses return. Printed
 * raw that is an unscannable wall of text.
 *
 * So those paragraphs are promoted to real <h2>s. NOT all of them: of the ten
 * such paragraphs in the current copy, four are emphasised SENTENCES rather than
 * titles ("Our mission is simple:", "But our dream is global."). Promoting those
 * would invent nonsense headings, so a line only counts as a title when it is
 * short and does not read as a sentence - no trailing colon or full stop. The
 * rest keep their emphasis and stay in the prose where they belong.
 *
 * Returns ['html' => ..., 'sections' => [['id' => .., 'text' => ..], ..]].
 */
function about_page_prepare($html)
{
    $html = (string) $html;
    if (trim($html) === '') {
        return ['html' => '', 'sections' => []];
    }

    $sections = [];
    $used = [];

    $promoted = preg_replace_callback(
        // A paragraph whose entire content is one <strong>/<b>.
        '#<p>\s*<(strong|b)>(.*?)</\1>\s*</p>#is',
        function ($m) use (&$sections, &$used) {
            $raw = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($m[2]), ENT_QUOTES, 'UTF-8')));

            $is_title = ($raw !== ''
                // Long enough to be prose is not a heading.
                && mb_strlen($raw) <= 45
                // A heading does not end in punctuation that continues a thought.
                && !preg_match('/[:.,;!?]$/u', $raw)
                // Nor does it contain sentence-internal punctuation.
                && strpos($raw, '.') === false);

            if (!$is_title) {
                return $m[0];
            }

            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $raw));
            $slug = 'about-' . trim($slug, '-');
            if (isset($used[$slug])) {
                $used[$slug]++;
                $slug .= '-' . $used[$slug];
            } else {
                $used[$slug] = 1;
            }

            $sections[] = ['id' => $slug, 'text' => $raw];

            return '<h2 id="' . $slug . '">' . html_escape($raw) . '</h2>';
        },
        $html
    );

    return ['html' => $promoted, 'sections' => $sections];
}

/**
 * Prepares an admin-authored policy document (Terms, Privacy, Return, Shipping)
 * for the shared legal-page layout.
 *
 * These four documents are stored as HTML blobs in `settings` and were printed
 * raw into a bare container, which left three problems the reader could see:
 *
 *  - TWO <h1>s on the page. The Return and Shipping documents open with their
 *    own <h1>, and the view printed a second <h1> of its own above it. (Terms
 *    and Privacy do not, so the duplication appeared on half the pages - which
 *    is exactly the sort of inconsistency you get from four copy-pasted views.)
 *  - No way to navigate 25 numbered clauses other than scrolling, and no way to
 *    link anyone to one of them.
 *  - "Last Updated: ..." buried as the first paragraph of the prose instead of
 *    presented as document metadata.
 *
 * Returns ['html' => prose with id="" anchors on every clause,
 *          'toc'  => [['id' => .., 'text' => ..], ..],
 *          'updated' => 'July 19, 2026' | ''].
 *
 * Done server-side with DOM rather than in JS so the anchors are real (a
 * /terms-and-conditions#refunds link works on first paint, and for a crawler),
 * and rather than with regex because these blobs are hand-edited in a WYSIWYG
 * and their exact tag soup is not something to pattern-match against.
 */
function legal_page_prepare($html)
{
    $empty = ['html' => '', 'toc' => [], 'updated' => ''];

    $html = (string) $html;
    if (trim($html) === '') {
        return $empty;
    }

    if (!class_exists('DOMDocument')) {
        // No dom extension: degrade to the raw document rather than a blank page.
        return ['html' => $html, 'toc' => [], 'updated' => ''];
    }

    $doc = new DOMDocument();
    // The blob is a FRAGMENT. Wrapping it keeps libxml from inventing <html>
    // structure, and the XML declaration is what makes it treat the input as
    // UTF-8 (without it, "&" and non-ASCII in the prose come out mojibake).
    $previous = libxml_use_internal_errors(true);
    $loaded = $doc->loadHTML(
        '<?xml encoding="UTF-8"?><div id="czlegal-root">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (!$loaded) {
        return ['html' => $html, 'toc' => [], 'updated' => ''];
    }

    $root = $doc->getElementById('czlegal-root');
    if ($root === null) {
        return ['html' => $html, 'toc' => [], 'updated' => ''];
    }

    $xpath = new DOMXPath($doc);
    $updated = '';

    /* ---- 1. drop the document's own <h1>; the layout renders the title ---- */
    foreach (iterator_to_array($xpath->query('.//h1', $root)) as $h1) {
        $h1->parentNode->removeChild($h1);
    }

    /* ---- 2. lift "Last Updated: <date>" out of the prose ----
     * Matched on <p> AND <h2>/<h3>: three of the four documents put the line in
     * a paragraph but the Privacy one puts it in an <h2>, where it would
     * otherwise become the first entry in the table of contents ("Last Updated:
     * 19/07/2026" as a clause). Whichever element carries it is removed, so the
     * date is shown once, as metadata. */
    foreach (iterator_to_array($xpath->query('.//p | .//h2 | .//h3', $root)) as $node) {
        $text = trim(preg_replace('/\s+/u', ' ', $node->textContent));
        if (preg_match('/^last\s+updated\s*:?\s*(.+)$/iu', $text, $m)) {
            $updated = trim($m[1], " \t\n\r\0\x0B.:");
            $node->parentNode->removeChild($node);
            // Only the first one - a document that mentions the phrase again
            // further down is talking about something else.
            break;
        }
    }

    /* ---- 2b. drop now-orphaned leading/trailing rules ----
     * The documents put an <hr> after their title block. With the title and the
     * "Last Updated" line lifted out, that rule is left stranded at the very top
     * of the prose, drawing a line under nothing. Same at the end. Walks into
     * the wrapper element the documents use (<section class="terms-conditions">)
     * because that is where the rules actually live. */
    $container = $root;
    // Descend past a single wrapper that holds the whole document.
    while (true) {
        $elements = [];
        foreach ($container->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $elements[] = $child;
            } elseif ($child->nodeType === XML_TEXT_NODE && trim($child->textContent) !== '') {
                $elements[] = $child;
            }
        }
        if (count($elements) === 1 && $elements[0]->nodeType === XML_ELEMENT_NODE
            && in_array(strtolower($elements[0]->nodeName), ['div', 'section', 'article'], true)) {
            $container = $elements[0];
            continue;
        }
        break;
    }

    $edge = function ($reverse) use ($container) {
        $children = iterator_to_array($container->childNodes);
        if ($reverse) {
            $children = array_reverse($children);
        }
        foreach ($children as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                if (trim($child->textContent) === '') {
                    continue; // whitespace between tags
                }
                return null;
            }
            if ($child->nodeType === XML_COMMENT_NODE) {
                continue;
            }
            return $child;
        }
        return null;
    };

    foreach ([false, true] as $fromEnd) {
        $node = $edge($fromEnd);
        while ($node !== null && strtolower($node->nodeName) === 'hr') {
            $node->parentNode->removeChild($node);
            $node = $edge($fromEnd);
        }
    }

    /* ---- 3. anchor every clause and build the contents list ---- */
    $toc = [];
    $used = [];
    foreach ($xpath->query('.//h2', $root) as $index => $h2) {
        $text = trim(preg_replace('/\s+/u', ' ', $h2->textContent));
        if ($text === '') {
            continue;
        }

        // Slug from the heading, minus the leading clause number ("1. About
        // Cretzo" -> "about-cretzo") so an anchor survives a clause being
        // renumbered when a new one is inserted above it.
        $slug = preg_replace('/^\d+[\.\)]?\s*/u', '', $text);
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'clause';
        }
        $slug = 'clause-' . $slug;

        // Two clauses can legitimately share a title across a long document.
        if (isset($used[$slug])) {
            $used[$slug]++;
            $slug .= '-' . $used[$slug];
        } else {
            $used[$slug] = 1;
        }

        $h2->setAttribute('id', $slug);
        $toc[] = ['id' => $slug, 'text' => $text];
    }

    /* ---- 4. serialise the children, not the wrapper ---- */
    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }

    return ['html' => $out, 'toc' => $toc, 'updated' => $updated];
}

function order_status_tone($status)
{
    switch (trim(strtolower((string) $status))) {
        case 'delivered':
        case 'return_request_approved':
            return 'ok';

        case 'cancelled':
        case 'returned':
        case 'return_request_decline':
            return 'bad';

        case 'shipped':
        case 'out_for_delivery':
            return 'info';

        /* awaiting / received / processed / return_request_pending: in flight,
         * nothing has gone wrong and nothing is finished. */
        default:
            return 'warn';
    }
}

/**
 * When an order item reached a given status, or '' if it never did.
 *
 * `order_items`.`status` is the status HISTORY: a list of [status, timestamp] PAIRS. Both
 * storefront order screens were searching it with array_search('delivered', $history), which
 * compares the needle against each PAIR and so can never match - it always returned false.
 *
 * On the order list that false was then used as an index, and PHP reads false as 0, so
 * $history[false][1] handed back the timestamp of the FIRST entry - the moment the order was
 * placed. The return window was therefore measured from the order date instead of the
 * delivery date, and "Return Order" was offered on items that had not been delivered at all.
 * On the detail page the same call was guarded with !== false, so the opposite happened: the
 * return-window line could never render for anyone.
 *
 * Searches from the END so a redelivery after a failed return reports the latest date, which
 * is the one the return window should run from.
 */
function order_status_history_date($history, $status)
{
    if (!is_array($history)) {
        $history = json_decode((string) $history, true);
    }
    if (!is_array($history)) {
        return '';
    }

    $status = trim(strtolower((string) $status));
    foreach (array_reverse($history) as $entry) {
        if (is_array($entry) && isset($entry[0]) && trim(strtolower((string) $entry[0])) === $status) {
            return isset($entry[1]) ? $entry[1] : '';
        }
    }
    return '';
}

/**
 * Icon URL for an order status on the storefront order screens.
 *
 * Both screens built this as base_url(".../new_cretzo/{$active_status}.png") straight from the
 * column. Seven icons exist - received, processed, shipped, delivered, cancelled, returned -
 * but `active_status` also legitimately holds 'awaiting' and the three return_request_* states,
 * and each of those rendered a broken image. There is already a return_request_approved item on
 * this database, so it is not hypothetical.
 *
 * Statuses without an icon of their own borrow the nearest one that exists, and anything
 * unrecognised falls back to received rather than to a 404.
 */
function order_status_icon_url($status)
{
    $status = trim(strtolower((string) $status));

    $aliases = [
        'awaiting'                => 'received',
        'return_request_pending'  => 'returned',
        'return_request_approved' => 'returned',
        'return_request_decline'  => 'delivered',
    ];
    $icon = isset($aliases[$status]) ? $aliases[$status] : $status;

    $dir = 'assets/front_end/' . THEME . '/img/new_cretzo/';
    if ($icon === '' || !file_exists(FCPATH . $dir . $icon . '.png')) {
        $icon = 'received';
    }

    return base_url($dir . $icon . '.png');
}

/**
 * Public URL for a customer's profile photo, or '' when there isn't a usable one.
 *
 * `users`.`image` stores only the BARE FILE NAME inside USER_IMG_PATH - that is how the
 * mobile app's update_user_profile has always written it, and how My Account > Profile
 * writes it now. The storefront header was using the column value directly as an <img
 * src>, so a stored "a1b2c3.png" resolved against the current page URL, 404'd, and the
 * avatar silently fell back to the default icon for every user who had a photo.
 *
 * Returns '' rather than a placeholder so each caller keeps its own theme-specific default
 * icon (the path differs per theme). A row naming a file that is no longer on disk is
 * treated as having no photo.
 */
/**
 * Responsive-image data for a full-width hero/banner image.
 *
 * The homepage slider used to render get_image_url($image, 'thumb', 'md') as a single src.
 * thumb-md is capped at 800px on its long edge by resize_image(), while the slider is
 * full-bleed - so on a 1920px screen an 800px file was being upscaled ~2.4x and the banner
 * looked visibly blurry. Handing the browser a srcset lets it pick per viewport and DPR:
 * phones still get the small derivative, desktops get the full-size file.
 *
 * Returns:
 *   src    - fallback URL for browsers without srcset (the largest available file)
 *   srcset - "url 450w, url 800w, url 1535w" built only from files that exist
 *   width  - intrinsic pixel width of `src` (0 if it could not be read)
 *   height - intrinsic pixel height of `src` (0 if it could not be read)
 *
 * Every candidate is measured rather than assumed, so a portrait or an unusually sized
 * banner gets honest width descriptors, and width/height on the tag lets the browser
 * reserve the right space instead of reflowing the page once the image lands.
 */
function hero_image_srcset($image)
{
    $full = get_image_url($image);

    // Keyed by URL: get_image_url() falls back to the original when a derivative is
    // missing, and the same file must not appear twice with two different descriptors.
    $candidates = [
        get_image_url($image, 'thumb', 'sm') => true,
        get_image_url($image, 'thumb', 'md') => true,
        $full                                => true,
    ];

    $srcset = [];
    $width = $height = 0;

    foreach (array_keys($candidates) as $url) {
        // base_url()-relative -> path on disk, so the size can be read locally.
        $path = FCPATH . ltrim(str_replace(base_url(), '', $url), '/');
        $size = @getimagesize($path);

        if (!$size || empty($size[0])) {
            continue;
        }

        $srcset[] = $url . ' ' . (int) $size[0] . 'w';

        if ($url === $full) {
            $width = (int) $size[0];
            $height = (int) $size[1];
        }
    }

    return [
        'src'    => $full,
        'srcset' => implode(', ', $srcset),
        'width'  => $width,
        'height' => $height,
    ];
}

function get_user_avatar_url($image)
{
    $image = trim((string) $image);

    if ($image === '') {
        return '';
    }

    // Rows written by older/social-login paths can already hold an absolute URL.
    if (preg_match('#^https?://#i', $image)) {
        return $image;
    }

    if (!file_exists(FCPATH . USER_IMG_PATH . $image)) {
        return '';
    }

    return base_url(USER_IMG_PATH . $image);
}


/**
 * Work out which character actually separates the columns of an uploaded CSV.
 *
 * The bulk importers hard-coded "," as the delimiter. Excel on a non-US locale (and every
 * "export as CSV" that really writes TSV) hands the seller a tab- or semicolon-separated
 * file, which fgetcsv then reads as ONE giant column - so $row[2], $row[27] and friends do
 * not exist, the row fails validation for a reason that has nothing to do with the data, and
 * the PHP "Undefined array key" warnings printed ahead of the JSON reply broke the response
 * outright. Sniffing the header line costs nothing and makes those files importable.
 *
 * @param string $csv_path path to the uploaded temp file
 * @return string one of , ; \t |  - defaults to "," when nothing looks better
 */
function detect_csv_delimiter($csv_path)
{
    $candidates = array(',', ';', "\t", '|');
    $handle = @fopen($csv_path, 'r');
    if ($handle === false) {
        return ',';
    }
    $header = fgets($handle, 100000);
    fclose($handle);
    if ($header === false || $header === '') {
        return ',';
    }

    $best = ',';
    $best_count = 0;
    foreach ($candidates as $delimiter) {
        $count = count(str_getcsv($header, $delimiter));
        if ($count > $best_count) {
            $best_count = $count;
            $best = $delimiter;
        }
    }

    return $best;
}

/**
 * Pad a CSV row out to the expected column count.
 *
 * Every bulk importer addresses cells by fixed index ($row[27] is deliverable_type, and so
 * on). A short row - a trailing column the seller's editor dropped, or a delimiter we guessed
 * wrong - used to raise an "Undefined array key" warning per read. Those warnings are echoed
 * into the response body, so the AJAX caller could not parse the JSON that followed and every
 * such upload reported the useless "Something went wrong while uploading" instead of the real
 * per-row reason. Padding with '' keeps the reads quiet and the validation messages truthful.
 *
 * @param array $row
 * @param int   $width minimum number of columns the caller indexes into
 * @return array
 */
function pad_csv_row($row, $width)
{
    if (!is_array($row)) {
        return array_fill(0, $width, '');
    }
    for ($i = 0; $i < $width; $i++) {
        if (!isset($row[$i])) {
            $row[$i] = '';
        }
    }
    return $row;
}

/**
 * Check that a bulk-import header row has a workable number of columns.
 *
 * Every importer reads a fixed block of product columns and then repeats a fixed-width variant
 * block for as many variants as the file carries, so the only valid widths are
 * $fixed + (n * $block) for n >= 1. Anything else means the columns do not line up with what
 * the code indexes: one spare column (a hand-added seller_id in a seller file, say) shifts the
 * whole variant block along, so price lands in attribute_value_ids and the import silently
 * writes corrupt products. Refusing the file is the only safe answer.
 *
 * @param int $count  columns found in the header row
 * @param int $fixed  columns before the first variant block
 * @param int $block  width of one variant block
 * @return bool
 */
function is_valid_bulk_header_width($count, $fixed, $block)
{
    $count = (int) $count;
    if ($count < $fixed + $block) {
        return false;
    }
    return (($count - $fixed) % $block) === 0;
}

/**
 * The message shown when is_valid_bulk_header_width() rejects a file.
 *
 * Spells out both numbers, because "the upload could not be completed" told the user nothing
 * about what to change.
 *
 * @return string
 */
function bulk_header_width_message($count, $fixed, $block)
{
    $minimum = $fixed + $block;
    return 'Your file has ' . (int) $count . ' column(s), which does not match the expected layout: '
        . $fixed . ' product columns followed by one block of ' . $block . ' columns per variant '
        . '(so ' . $minimum . ' columns for one variant, ' . ($minimum + $block) . ' for two, and so on). '
        . 'Please start from the sample CSV and do not add or remove columns.';
}

/**
 * Read a yes/no cell from a bulk upload sheet.
 *
 * These columns used to accept only 0 and 1, which is unreadable on a spreadsheet. The
 * generated template writes words, and a seller typing their own answer is very likely to write
 * one of these instead of a digit, so all of them are accepted.
 *
 * @param string $value cell contents
 * @return int|null 1, 0, or null when the cell is blank/unrecognised so the caller can fall
 *                  back to the setting chosen on the upload form
 */
function bulk_parse_yes_no($value)
{
    $value = strtolower(trim((string) $value));
    if ($value === '') {
        return null;
    }
    if (in_array($value, ['1', 'yes', 'y', 'true', 'allowed', 'returnable', 'included', 'inclusive'], true)) {
        return 1;
    }
    if (in_array($value, ['0', 'no', 'n', 'false', 'not allowed', 'not returnable', 'excluded', 'exclusive'], true)) {
        return 0;
    }
    return null;
}

/**
 * Read a cell that must be one of a fixed set of choices, by word or by code.
 *
 * @param string $value cell contents
 * @param array  $map   lower-cased accepted spelling => value to store
 * @return mixed|null the mapped value, or null when the cell is blank/unrecognised
 */
function bulk_parse_choice($value, $map)
{
    $value = strtolower(trim((string) $value));
    if ($value === '') {
        return null;
    }
    return array_key_exists($value, $map) ? $map[$value] : null;
}


/*
 * ---------------------------------------------------------------------------
 * Bulk upload: settings shared by the seller and admin importers
 * ---------------------------------------------------------------------------
 * These live here rather than in either controller because both importers must agree on the
 * spellings they write into a template and accept back out of one. When the same table was
 * duplicated per controller the two drifted, and a file generated on one page failed on the
 * other.
 */
/**
 * Emit a per-row bulk upload failure and stop.
 *
 * Every one of these used to be six repeated lines. Wording matters more than the count
 * here: the message is the only thing a seller has to work out what to change, so each
 * caller says which column and what to put in it.
 *
 * @return bool always false, so callers can `return bulk_upload_row_error(...)`
 */
function bulk_upload_row_error($message)
{
    $ci = &get_instance();
    $response = [];
    $response['error'] = true;
    $response['message'] = $message;
    $response['csrfName'] = $ci->security->get_csrf_token_name();
    $response['csrfHash'] = $ci->security->get_csrf_hash();
    print_r(json_encode($response));
    return false;
}

/**
 * Read the settings that apply to every row of a bulk upload from the form.
 *
 * These are the columns sellers could not read: cod_allowed, is_prices_inclusive_tax,
 * is_returnable, is_cancelable + cancelable_till, indicator and deliverable_type were all
 * numeric codes in the sheet, and all of them are the same for every product in a real
 * upload. Asking once, with words, removes six code columns from the file. Plain-number
 * settings (minimum order quantity and friends) stay in the sheet - they need no decoding.
 *
 * @return array the values to write on every row, plus 'error' ('' when the form is valid)
 */
function collect_bulk_upload_defaults()
{
    $ci = &get_instance();
    $defaults = [
        'indicator'               => 0,
        'cod_allowed'             => 1,
        'is_prices_inclusive_tax' => 0,
        'is_returnable'           => 0,
        'is_cancelable'           => 0,
        'cancelable_till'         => '',
        'deliverable_type'        => ALL,
        'deliverable_zipcodes'    => '',
        'error'                   => '',
    ];

    $indicator = $ci->input->post('default_indicator');
    if (in_array($indicator, ['0', '1', '2'], true)) {
        $defaults['indicator'] = (int) $indicator;
    }

    // Only overridden when the field is actually present. Treating an absent field as "0"
    // silently flipped cod_allowed to Not allowed for any caller that did not post it,
    // contradicting the default declared above.
    foreach (['default_cod_allowed' => 'cod_allowed',
              'default_prices_inclusive_tax' => 'is_prices_inclusive_tax',
              'default_is_returnable' => 'is_returnable'] as $field => $key) {
        $posted = $ci->input->post($field);
        if ($posted !== null && $posted !== '') {
            $defaults[$key] = ($posted == '1') ? 1 : 0;
        }
    }

    // One control instead of two: "cancellable until X" carries both the flag and the stage,
    // so the pair can no longer contradict each other the way two loose columns could.
    $cancelable_till = $ci->input->post('default_cancelable_till');
    if (in_array($cancelable_till, ['received', 'processed', 'shipped'], true)) {
        $defaults['is_cancelable'] = 1;
        $defaults['cancelable_till'] = $cancelable_till;
    }

    $deliverable_type = $ci->input->post('default_deliverable_type');
    if (!in_array($deliverable_type, [NONE, ALL, INCLUDED, EXCLUDED], true)) {
        $deliverable_type = ALL;
    }
    $defaults['deliverable_type'] = $deliverable_type;

    if ($deliverable_type == INCLUDED || $deliverable_type == EXCLUDED) {
        // Only digits and separators survive: the field is a free-text box, and a stray
        // label pasted in with the pincodes would be stored as a deliverable zipcode.
        $zipcodes = preg_split('/[^0-9]+/', (string) $ci->input->post('default_deliverable_zipcodes'), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($zipcodes)) {
            $defaults['error'] = 'Please list the pincodes for the delivery area you chose.';
            return $defaults;
        }
        $defaults['deliverable_zipcodes'] = implode(',', array_unique($zipcodes));
    }

    return $defaults;
}

/**
 * The word spellings the generated template writes, and everything else it will accept.
 *
 * Kept in one place so the template writer and the importer can never drift apart: the
 * template picks the first spelling, the importer accepts any key.
 */
function bulk_setting_vocabulary()
{
    return [
        'cancellable_until' => [
            'labels' => ['' => 'No', 'received' => 'Until received', 'processed' => 'Until processed', 'shipped' => 'Until shipped'],
            'map'    => [
                'no' => '', 'none' => '', 'not cancellable' => '', 'not cancelable' => '',
                'until received' => 'received', 'received' => 'received',
                'until processed' => 'processed', 'processed' => 'processed',
                'until shipped' => 'shipped', 'shipped' => 'shipped',
            ],
        ],
        'food_type' => [
            'labels' => ['0' => 'Not a food product', '1' => 'Vegetarian', '2' => 'Non-vegetarian'],
            'map'    => [
                'not a food product' => 0, 'none' => 0, 'no' => 0, '0' => 0,
                'vegetarian' => 1, 'veg' => 1, '1' => 1,
                'non-vegetarian' => 2, 'non vegetarian' => 2, 'non-veg' => 2, 'nonveg' => 2, '2' => 2,
            ],
        ],
        'delivery_area' => [
            'labels' => [NONE => 'Not deliverable yet', ALL => 'Everywhere', INCLUDED => 'Only these pincodes', EXCLUDED => 'Except these pincodes'],
            'map'    => [
                'everywhere' => ALL, 'all' => ALL, '1' => ALL,
                'only these pincodes' => INCLUDED, 'only these' => INCLUDED, 'included' => INCLUDED, '2' => INCLUDED,
                'except these pincodes' => EXCLUDED, 'everywhere except these pincodes' => EXCLUDED, 'except these' => EXCLUDED, 'excluded' => EXCLUDED, '3' => EXCLUDED,
                'not deliverable yet' => NONE, 'not deliverable' => NONE, 'none' => NONE, '0' => NONE,
            ],
        ],
    ];
}

/**
 * Work out one row's settings: the cell if it says something, otherwise the form.
 *
 * Both sources exist on purpose. The template writes every one of these columns, so a
 * downloaded-and-filled file is self-describing and can be re-uploaded months later without
 * remembering which switches were set. A seller who deletes the columns' contents, or who
 * built the file by hand, still gets the form's answers rather than a validation error.
 *
 * @return array ready to write onto the products row
 */
function resolve_bulk_row_settings($row, $C, $defaults)
{
    $vocab = bulk_setting_vocabulary();
    $settings = $defaults;

    $cod = bulk_parse_yes_no($row[$C['cod_allowed']]);
    if ($cod !== null) {
        $settings['cod_allowed'] = $cod;
    }
    $inclusive = bulk_parse_yes_no($row[$C['prices_include_tax']]);
    if ($inclusive !== null) {
        $settings['is_prices_inclusive_tax'] = $inclusive;
    }
    $returnable = bulk_parse_yes_no($row[$C['returnable']]);
    if ($returnable !== null) {
        $settings['is_returnable'] = $returnable;
    }

    // Only the admin sheet carries this column. The seller sheet dropped it - cancellation
    // is an admin policy per product - so its column map does not name it and there is
    // nothing to read here.
    if (isset($C['cancellable_until'])) {
        $until = bulk_parse_choice($row[$C['cancellable_until']], $vocab['cancellable_until']['map']);
        if ($until !== null) {
            $settings['is_cancelable'] = ($until === '') ? 0 : 1;
            $settings['cancelable_till'] = $until;
        }
    }

    $food = bulk_parse_choice($row[$C['food_type']], $vocab['food_type']['map']);
    if ($food !== null) {
        $settings['indicator'] = $food;
    }

    $area = bulk_parse_choice($row[$C['delivery_area']], $vocab['delivery_area']['map']);
    if ($area !== null) {
        $settings['deliverable_type'] = $area;
        // Pincodes belong to whichever area the row asked for, so they are re-read here
        // rather than inherited from the form's (possibly different) choice.
        $settings['deliverable_zipcodes'] = '';
        if ($area == INCLUDED || $area == EXCLUDED) {
            $zipcodes = preg_split('/[^0-9]+/', (string) $row[$C['pincodes']], -1, PREG_SPLIT_NO_EMPTY);
            $settings['deliverable_zipcodes'] = !empty($zipcodes)
                ? implode(',', array_unique($zipcodes))
                : $defaults['deliverable_zipcodes'];
        }
    }

    return $settings;
}


/**
 * Where a seller stands in the admin-approval journey.
 *
 * Three gates share this: the dashboard popup that nags until the profile is submitted, the
 * one-time approval congratulation, and the subscription checkout guard. They were each
 * reading seller_data directly with slightly different rules, which is how a seller could be
 * told "pending approval" on one screen and still be allowed to pay on another.
 *
 * Returns:
 *   stage        - 'incomplete' (profile not submitted for review), 'pending' (submitted,
 *                  awaiting the admin) or 'approved'
 *   is_approved  - convenience boolean for stage === 'approved'
 *   requested_at - when the seller submitted, if ever
 *   show_approval_popup - true only on the first load after approval, until acknowledged
 */
function seller_approval_state($user_id)
{
    $CI = &get_instance();

    $state = [
        'stage'               => 'incomplete',
        'is_approved'         => false,
        'requested_at'        => null,
        'show_approval_popup' => false,
    ];

    if (empty($user_id) || !$CI->db->table_exists('seller_data')) {
        return $state;
    }

    // Every column here was added by a later migration, so a database that has not caught up
    // must degrade to "incomplete" rather than throw an unknown-column error on the dashboard.
    $select = ['status'];
    $has_requested_at = $CI->db->field_exists('verification_request_at', 'seller_data');
    $has_popup_flag   = $CI->db->field_exists('approval_popup_seen_at', 'seller_data');
    if ($has_requested_at) {
        $select[] = 'verification_request_at';
    }
    if ($has_popup_flag) {
        $select[] = 'approval_popup_seen_at';
    }

    $row = $CI->db->select(implode(',', $select))
        ->where('user_id', $user_id)
        ->get('seller_data')
        ->row_array();

    if (empty($row)) {
        return $state;
    }

    $state['requested_at'] = $has_requested_at && !empty($row['verification_request_at'])
        ? $row['verification_request_at']
        : null;

    if ((string) $row['status'] === '1') {
        $state['stage'] = 'approved';
        $state['is_approved'] = true;
        // No flag column yet means we cannot tell "already seen" from "never shown"; staying
        // silent is better than congratulating the same seller on every page load.
        $state['show_approval_popup'] = $has_popup_flag && empty($row['approval_popup_seen_at']);
        return $state;
    }

    $state['stage'] = !empty($state['requested_at']) ? 'pending' : 'incomplete';

    return $state;
}

/**
 * The canonical seller-profile sections.
 *
 * One definition feeds three consumers that must never disagree: the dashboard completion
 * meter, the "what is still missing?" list on the profile page, and the gate that decides
 * whether a saved profile is complete enough to be sent to the admin for verification. When
 * these lived apart, a seller could be shown 100% complete and still be refused review.
 */
function seller_profile_sections()
{
    return [
        'personal' => [
            'weight' => 30,
            'label'  => 'Personal Details',
            'fields' => ['first_name', 'last_name', 'phone', 'email', 'district', 'state', 'pin'],
        ],
        'store' => [
            'weight' => 25,
            'label'  => 'Store Details',
            'fields' => ['shop_name', 'shop_phone', 'pickup_address1', 'entity_type', 'pan', 'gst', 'primary_category_id'],
        ],
        'account' => [
            'weight' => 20,
            'label'  => 'Bank Account Details',
            'fields' => ['account_number', 'account_holder_name', 'ifsc', 'branch', 'bank_name'],
        ],
    ];
}

/**
 * Is one profile field filled in?
 *
 * GST is the exception: a seller who ticked "We are not GST registered" files a GST
 * enrollment id instead, so demanding a GSTIN from them left their profile permanently
 * incomplete - stuck at 75% on the dashboard, and (now that submission is gated on
 * completeness) unable to ever reach the admin.
 */
function seller_profile_field_filled($row, $field)
{
    $value = isset($row[$field]) ? trim((string) $row[$field]) : '';

    // A zero id is the "nothing chosen" state of the category dropdown, not a category.
    if ($field === 'primary_category_id') {
        return (int) $value > 0;
    }

    if ($field === 'gst' && $value === '') {
        $is_registered = !isset($row['is_gst_registered']) || (string) $row['is_gst_registered'] === '1';
        if (!$is_registered) {
            return isset($row['gst_enrollment_number']) && trim((string) $row['gst_enrollment_number']) !== '';
        }
    }

    return $value !== '';
}

/**
 * The seller_data row the completeness rules read, with the two fields that can legitimately
 * live on the users table instead (email / mobile) folded in - exactly the way the profile
 * form itself resolves them, so a filled-in form is never reported as missing.
 */
function seller_profile_row($user_id)
{
    $CI = &get_instance();

    if (empty($user_id) || !$CI->db->table_exists('seller_data')) {
        return [];
    }

    $row = $CI->db
        ->select('sd.*, COALESCE(NULLIF(sd.email, ""), u.email) as email, COALESCE(NULLIF(sd.phone, ""), u.mobile) as phone')
        ->from('seller_data sd')
        ->join('users u', 'u.id = sd.user_id', 'left')
        ->where('sd.user_id', $user_id)
        ->get()
        ->row_array();

    return is_array($row) ? $row : [];
}

/**
 * Which profile sections the seller still has to fill in.
 *
 * Pass $row to reuse a seller_data row you already have (the admin seller form does); leave
 * it null and the row is fetched. An empty list means the profile is submission-ready.
 */
function seller_profile_incomplete_sections($user_id, $row = null)
{
    // No row at all simply means nothing is filled in - every section comes back as
    // missing, which is the honest answer.
    if ($row === null) {
        $row = seller_profile_row($user_id);
    }
    if (!is_array($row)) {
        $row = [];
    }

    $missing = [];
    foreach (seller_profile_sections() as $key => $section) {
        foreach ($section['fields'] as $field) {
            if (!seller_profile_field_filled($row, $field)) {
                $missing[] = [
                    'key'   => $key,
                    'label' => $section['label'],
                    'link'  => base_url('seller/home/profile?section=' . $key),
                ];
                break;
            }
        }
    }

    return $missing;
}

/**
 * Sends a seller's profile to the admin for verification.
 *
 * There is deliberately no seller-facing "Request Admin Verification" button any more: the
 * admin cannot review a half-filled profile, so the request is filed automatically when the
 * seller saves a profile that has every section complete. Saving an incomplete profile is
 * still allowed - it just does not raise a review the admin would have to bounce.
 *
 * Returns:
 *   filed             - true only when this call stamped a new request (i.e. notify the seller)
 *   already_requested - the profile was already awaiting review
 *   approved          - already approved, nothing to file
 *   missing_sections  - why nothing was filed, when it wasn't
 */
function seller_file_verification_request($user_id)
{
    $CI = &get_instance();

    $result = [
        'filed'             => false,
        'already_requested' => false,
        'approved'          => false,
        'missing_sections'  => [],
    ];

    if (empty($user_id) || !$CI->db->table_exists('seller_data')) {
        return $result;
    }

    $row = seller_profile_row($user_id);
    if (empty($row)) {
        return $result;
    }

    if (isset($row['status']) && (string) $row['status'] === '1') {
        $result['approved'] = true;
        return $result;
    }

    // Added by a later migration: on a database that has not caught up there is nowhere to
    // record the request, so stay silent rather than fail the seller's profile save.
    if (!$CI->db->field_exists('verification_request_at', 'seller_data')) {
        return $result;
    }

    if (!empty($row['verification_request_at'])) {
        $result['already_requested'] = true;
        return $result;
    }

    $result['missing_sections'] = seller_profile_incomplete_sections($user_id, $row);
    if (!empty($result['missing_sections'])) {
        return $result;
    }

    $stamped = (bool) $CI->db->where('user_id', $user_id)
        ->update('seller_data', ['verification_request_at' => date('Y-m-d H:i:s')]);

    if (!$stamped) {
        return $result;
    }

    $result['filed'] = true;

    $seller_user = $CI->db->select('username')->where('id', $user_id)->get('users')->row_array();
    $seller_name = !empty($seller_user['username']) ? $seller_user['username'] : ('Seller #' . $user_id);
    $CI->db->insert('system_notification', [
        'title'    => 'Seller verification request received',
        'message'  => $seller_name . ' has completed their profile and is awaiting admin verification. Review and approve/reject from seller management.',
        'type'     => 'seller_verification_request',
        'type_id'  => $user_id,
        'read_by'  => 0,
    ]);

    return $result;
}

/**
 * The support WhatsApp number, in the form wa.me expects: digits only, country code included.
 *
 * Every "WhatsApp support" button on the site used to read `system_settings.whatsapp_number`
 * straight out of the settings row and hide itself when that field was blank - which is exactly
 * the state the store was in. The settings form's `whatsapp_status` toggle was off, and
 * Setting_model::update_settings() blanks `whatsapp_number` whenever it is saved with the toggle
 * off, so the number was wiped and every support button silently vanished. The seller, admin and
 * buyer chat pages were left reading "WhatsApp support is currently unavailable".
 *
 * A support channel is not an optional decoration, so this resolves a number instead of giving
 * up: the dedicated WhatsApp field first, then the support numbers that the footer and
 * contact-us page already display, then the number the owner confirmed (see migration 052).
 * A bare 10-digit Indian number is given the 91 country code, because wa.me will not accept a
 * number without one.
 *
 * @return string digits only, or '' if nothing usable is configured anywhere.
 */
function support_whatsapp_number()
{
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }

    $system = get_settings('system_settings', true);
    $web    = get_settings('web_settings', true);
    $system = is_array($system) ? $system : [];
    $web    = is_array($web) ? $web : [];

    $candidates = [
        isset($system['whatsapp_number']) ? $system['whatsapp_number'] : '',
        isset($system['support_number']) ? $system['support_number'] : '',
        isset($web['support_number']) ? $web['support_number'] : '',
        SUPPORT_WHATSAPP_DEFAULT,
    ];

    $resolved = '';
    foreach ($candidates as $candidate) {
        $digits = preg_replace('/\D+/', '', (string) $candidate);
        if ($digits === '') {
            continue;
        }
        if (strlen($digits) == 10) {
            $digits = '91' . $digits;
        }
        // Anything shorter than a country code + subscriber number, or longer than E.164
        // allows, is a typo rather than a phone number - fall through to the next candidate.
        if (strlen($digits) >= 11 && strlen($digits) <= 15) {
            $resolved = $digits;
            break;
        }
    }

    return $resolved;
}

/**
 * A ready-to-use https://wa.me/... link for the support number, with the chat pre-filled.
 *
 * @param  string $message text the chat opens with; defaults to a greeting naming the store.
 * @return string the URL, or '' when no support number can be resolved at all (in which case
 *                callers should show their fallback copy rather than a dead button).
 */
function whatsapp_support_link($message = '')
{
    $number = support_whatsapp_number();
    if (empty($number)) {
        return '';
    }

    if ($message === '' || $message === null) {
        $settings = get_settings('system_settings', true);
        $app_name = (is_array($settings) && !empty($settings['app_name'])) ? $settings['app_name'] : 'Cretzo';
        $message  = 'Hello ' . $app_name . ' Support,';
    }

    return 'https://wa.me/' . $number . '?text=' . rawurlencode($message);
}


/**
 * ONE pagination component for the whole buyer-facing storefront.
 *
 * Before this there were twelve near-identical CodeIgniter pagination configs
 * copied between Products, Sellers, Home, My_account and Blogs, and they had
 * drifted: the product listing drew arrow icons from Unicons while the
 * category, search, tag and seller listings drew "First"/"Last" text plus
 * Font-Awesome arrows, and the brands page had no prev/next configured at all.
 * Three of the twelve built links that no view ever printed. Same site, five
 * different pagers.
 *
 * Everything on the storefront now goes through here, so the markup is fixed:
 *
 *   <ul class="pagination cz-pagination">
 *     <li class="page-item cz-page-prev disabled"><span class="page-link">…</span></li>
 *     <li class="page-item"><a class="page-link" href="…">1</a></li>
 *     <li class="page-item active"><span class="page-link" aria-current="page">2</span></li>
 *     …
 *     <li class="page-item cz-page-next"><a class="page-link" href="…">…</a></li>
 *   </ul>
 *
 * Prev and next are ALWAYS rendered, disabled on the first/last page, so the
 * control keeps its shape instead of showing a lone right arrow on page 1.
 * CodeIgniter's library simply omits them at the edges, so the two
 * placeholders are added here afterwards - which is also why the prev/next
 * items carry their own classes, they are what makes that check reliable.
 *
 * The URL building is still CodeIgniter's: each caller's base_url, uri_segment
 * and query-string reuse already work, and re-deriving them by hand for eleven
 * routes is exactly where a pager breaks.
 *
 * assets/front_end/cretzo/js/cretzo/product-listing.js emits this same markup
 * for the AJAX listing (see renderPagination there) - the two must stay in
 * step, since a filter or sort click swaps the server-rendered pager for the
 * JavaScript one on the same page.
 *
 * @param string $base_url    Route the page numbers hang off, e.g. base_url('products')
 * @param int    $total_rows  Total matching records
 * @param int    $per_page    Page size
 * @param array  $extra       Any config key to add or override (uri_segment,
 *                            reuse_query_string, num_links, ...)
 * @return string The <ul> markup, or '' when there is only one page
 */
function storefront_pagination($base_url, $total_rows, $per_page, $extra = [])
{
    $CI = &get_instance();
    $CI->load->library('pagination');

    $per_page = max(1, (int) $per_page);
    $total_rows = max(0, (int) $total_rows);

    // Nothing to page through. Returning '' keeps the empty <nav> out of the
    // layout rather than leaving a stray single "1" button under the grid.
    if ($total_rows <= $per_page) {
        return '';
    }

    $arrow_prev = '<span class="cz-page-arrow" aria-hidden="true"><i class="uil uil-angle-left-b"></i></span>';
    $arrow_next = '<span class="cz-page-arrow" aria-hidden="true"><i class="uil uil-angle-right-b"></i></span>';

    $config = [
        'base_url' => $base_url,
        'total_rows' => $total_rows,
        'per_page' => $per_page,
        /*
         * Ask the library for EVERY page number, then trim the list down in
         * storefront_pagination_window() below.
         *
         * num_links used to be 2, which is CI's "n either side of the current
         * page" - so the pager showed a bare sliding window (". . . 3 4 [5] 6 7")
         * with no way to see how many pages there are or to reach the first or
         * last one: from page 1 you could only ever see pages 1-3, then 2-4, and
         * so on. The first and last page now always show, with an ellipsis
         * standing in for each hidden run.
         *
         * Rendering all the numbers first and then trimming keeps CI in charge of
         * building the hrefs, which differ per caller (uri_segment 3/4/5, and
         * reuse_query_string), so none of that URL logic is duplicated here.
         */
        'num_links' => max(1, (int) ceil($total_rows / $per_page)),
        'use_page_numbers' => true,
        'reuse_query_string' => true,
        'page_query_string' => false,

        'full_tag_open' => '<ul class="pagination cz-pagination">',
        'full_tag_close' => '</ul>',

        'attributes' => ['class' => 'page-link'],

        'num_tag_open' => '<li class="page-item">',
        'num_tag_close' => '</li>',

        // A <span>, not an <a href="#">: the current page is not a link, and a
        // dead "#" href moved the page to the top when it was clicked.
        'cur_tag_open' => '<li class="page-item active"><span class="page-link" aria-current="page">',
        'cur_tag_close' => '</span></li>',

        'prev_tag_open' => '<li class="page-item cz-page-prev">',
        'prev_link' => $arrow_prev,
        'prev_tag_close' => '</li>',

        'next_tag_open' => '<li class="page-item cz-page-next">',
        'next_link' => $arrow_next,
        'next_tag_close' => '</li>',

        // First/Last are off everywhere. They used to appear on some listings
        // and not others, and as words among icons they were the widest thing
        // in the row on a phone.
        'first_link' => false,
        'last_link' => false,
    ];

    foreach ($extra as $key => $value) {
        $config[$key] = $value;
    }

    $CI->pagination->initialize($config);
    $html = $CI->pagination->create_links();

    if ($html === '') {
        return '';
    }

    $html = storefront_pagination_window($html);

    // Symmetry: put back whichever arrow the library dropped because we are on
    // the first or the last page.
    if (strpos($html, 'cz-page-prev') === false) {
        $html = str_replace(
            '<ul class="pagination cz-pagination">',
            '<ul class="pagination cz-pagination"><li class="page-item cz-page-prev disabled"><span class="page-link">' . $arrow_prev . '</span></li>',
            $html
        );
    }
    if (strpos($html, 'cz-page-next') === false) {
        $html = str_replace(
            '</ul>',
            '<li class="page-item cz-page-next disabled"><span class="page-link">' . $arrow_next . '</span></li></ul>',
            $html
        );
    }

    return $html;
}

/**
 * Trims a full list of page numbers down to first + a window around the current
 * page + last, with an ellipsis standing in for each hidden run.
 *
 * storefront_pagination() hands the library a num_links big enough to emit every
 * page, then calls this. Working on the finished markup - rather than building the
 * numbers here - means the hrefs are still the library's, so segment-based and
 * query-string pagers keep working unchanged.
 *
 *   page 1 of 9    ->  <  [1] 2 ... 9  >
 *   page 5 of 9    ->  <  1 ... 4 [5] 6 ... 9  >
 *   page 9 of 9    ->  <  1 ... 8 [9]  >
 *
 * A gap of exactly one page is filled with that page's own number instead of an
 * ellipsis: "1 ... 3" would hide a single page behind a wider control than just
 * showing "1 2 3".
 */
function storefront_pagination_window($html, $window = 1)
{
    // Only the numbered items: the prev/next items carry extra classes
    // (cz-page-prev / cz-page-next) and so do not match.
    $pattern = '#<li class="page-item(?: active)?">.*?</li>#s';
    if (!preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
        return $html;
    }

    $items = $matches[0];
    $total_pages = count($items);
    if ($total_pages < 2) {
        return $html;
    }

    // 1-based position of the current page among the numbered items.
    $current = 1;
    foreach ($items as $i => $item) {
        if (strpos($item[0], 'page-item active') !== false) {
            $current = $i + 1;
            break;
        }
    }

    $keep = storefront_pagination_keep_pages($current, $total_pages, $window);

    // Rebuild the run of numbered items in place, so whatever the library put
    // before (prev) and after (next) them is preserved untouched.
    $first_offset = $items[0][1];
    $last_offset = $items[$total_pages - 1][1] + strlen($items[$total_pages - 1][0]);

    $ellipsis = '<li class="page-item cz-page-gap" aria-hidden="true"><span class="page-link">&hellip;</span></li>';
    $rebuilt = '';
    $previous = 0;
    foreach ($keep as $page) {
        if ($previous !== 0 && $page > $previous + 1) {
            $rebuilt .= $ellipsis;
        }
        $rebuilt .= $items[$page - 1][0];
        $previous = $page;
    }

    return substr($html, 0, $first_offset) . $rebuilt . substr($html, $last_offset);
}

/**
 * The page numbers a pager shows: always the first and the last, plus $window
 * pages either side of the current one. Shared with the AJAX twin in
 * assets/front_end/cretzo/js/cretzo/product-listing.js - keep the two in step.
 *
 * Returns an ascending, de-duplicated list of 1-based page numbers.
 */
function storefront_pagination_keep_pages($current, $total_pages, $window = 1)
{
    $current = min(max(1, (int) $current), (int) $total_pages);

    $pages = [1, $total_pages];
    for ($p = $current - $window; $p <= $current + $window; $p++) {
        if ($p >= 1 && $p <= $total_pages) {
            $pages[] = $p;
        }
    }

    /*
     * Near an end the window is clipped ("1 [2] 3 ... 9" is one number shorter
     * than "1 ... 4 [5] 6 ... 9"), which made the control visibly change width
     * as you paged. Spend the clipped slots on the other side so the number of
     * pages shown stays the same wherever you are.
     */
    $target = min($total_pages, 2 + (2 * $window + 1));
    for ($p = 1; count(array_unique($pages)) < $target && $p <= $total_pages; $p++) {
        if ($current <= $window + 2) {
            $pages[] = min($total_pages, $current + $window + $p);
        } else {
            $pages[] = max(1, $current - $window - $p);
        }
    }

    $pages = array_values(array_unique($pages));
    sort($pages);

    return $pages;
}

/**
 * Screen-reader label for a pager, so every <nav> around
 * storefront_pagination() output announces what it pages through.
 */
function storefront_pagination_label($what = 'results')
{
    return 'Pagination for ' . $what;
}

/**
 * Human-readable text for a subscription plan's free-text `validity` column.
 *
 * The column is admin-entered and holds anything from a bare day count ("365") to
 * prose ("1 month") to nothing at all. A blank value means the plan never expires -
 * assign_subscription() writes a NULL end_date for it - so it must read as "Ongoing"
 * everywhere rather than as an empty gap or a raw "365".
 */
function plan_validity_text($validity)
{
    $raw = trim((string) $validity);
    if ($raw === '') {
        return 'Ongoing';
    }

    if (!ctype_digit($raw)) {
        return $raw;
    }

    $days = (int) $raw;
    if ($days <= 0) {
        return 'Ongoing';
    }
    if ($days % 365 === 0) {
        $years = $days / 365;
        return $years . ' Year' . ($years > 1 ? 's' : '');
    }
    if ($days % 30 === 0) {
        $months = $days / 30;
        return $months . ' Month' . ($months > 1 ? 's' : '');
    }

    return $days . ' Day' . ($days > 1 ? 's' : '');
}

/**
 * Human-readable text for a plan's free-text `listings_limit` column, matching the
 * parsing Seller_subscription_model uses to enforce the cap: blank or "Unlimited"
 * or text with no digits means no cap, otherwise the first number is the cap.
 */
function plan_listings_text($listings_limit)
{
    $raw = trim((string) $listings_limit);
    if ($raw === '' || stripos($raw, 'unlimited') !== false || !preg_match('/\d+/', $raw, $m)) {
        return 'Unlimited listings';
    }

    $limit = (int) $m[0];

    return $limit . ' listing' . ($limit === 1 ? '' : 's');
}

/**
 * =============================================================================
 *  HOMEPAGE INSTAGRAM STRIP
 * =============================================================================
 *
 * Live posts for the homepage `czig` band, newest first, each entry:
 *
 *     ['image' => <https url>, 'url' => <permalink>, 'caption' => <short text>]
 *
 * WHERE THE POSTS COME FROM
 * -------------------------
 * The public Curator.io feed that already aggregates @cretzo_ - the same feed
 * the old embed widget used. Curator polls Instagram; we read the JSON. So a new
 * Instagram post appears on the homepage on its own, delayed by Curator's poll
 * interval plus this cache's TTL.
 *
 * Images are served straight from Curator's CDN rather than copied into
 * assets/. An earlier version of this section did copy them, with a CLI script
 * to re-pull - that made the strip a snapshot that only changed when someone
 * remembered to run the script, which defeats the point.
 *
 * WHY NOT INSTAGRAM'S OWN API
 * ---------------------------
 * The Graph API is the one route with no third party in it, and it needs a Meta
 * app plus a long-lived token on the business account, renewed every 60 days.
 * When that token exists, replace instagram_fetch_posts() only - it is the sole
 * function that knows where posts come from, and the shape above is what the
 * view consumes.
 *
 * WHY NOT THE EMBED WIDGET
 * ------------------------
 * The free plan injects a black "Powered by Curator.io" tile into the grid and
 * its script verifies that credit is still in the DOM, so the tile cannot be
 * removed from our side. Reading the feed as data leaves the tile sizes, the
 * post count and the render timing to us.
 *
 * FAILURE BEHAVIOUR
 * -----------------
 * A slow or broken feed must never cost a shopper the homepage, so the fetch has
 * a short timeout and every failure path returns the last good copy, which is
 * kept for a month under its own key. If there is no last good copy either, the
 * caller gets [] and the view omits the section entirely.
 */

/** Public Curator feed id for @cretzo_ - the widget's old feed, read as JSON. */
define('INSTAGRAM_FEED_ID', 'b22ac81e-28c4-4e42-8277-146ac29a87b1');

/** How long a fetched feed is served before refetching. */
define('INSTAGRAM_FEED_TTL', 1800);

/** How long the last successful fetch is retained as the failure fallback. */
define('INSTAGRAM_FEED_FALLBACK_TTL', 2592000);

/**
 * One HTTP read of the feed, mapped to the shape above. Returns null on any
 * failure - never a partial or empty list, so the caller can tell "the fetch
 * failed" from "the account has no photo posts".
 */
function instagram_fetch_posts($limit = 12)
{
    if (!function_exists('curl_init')) {
        return null;
    }

    $url = 'https://api.curator.io/v1.1/feeds/' . INSTAGRAM_FEED_ID . '/posts?limit=' . (int) $limit;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        /* This runs inside a page render. Better a stale strip than a homepage
           that waits on someone else's API. */
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT        => 4,
        CURLOPT_USERAGENT      => 'cretzo-storefront/1.0',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $code !== 200) {
        log_message('error', 'instagram_fetch_posts: HTTP ' . $code);
        return null;
    }

    $data = json_decode($body, true);
    if (!is_array($data) || empty($data['posts']) || !is_array($data['posts'])) {
        log_message('error', 'instagram_fetch_posts: unexpected feed payload');
        return null;
    }

    $posts = [];
    foreach ($data['posts'] as $post) {
        /* Text-only posts have nothing to show in a photo grid. */
        if (empty($post['has_image'])) {
            continue;
        }

        /* image_large is 850px - enough for the 2x2 feature tile; `image` is the
           480px thumbnail and is the fallback when the large one is missing. */
        $image = '';
        foreach (['image_large', 'image'] as $key) {
            if (!empty($post[$key])) {
                $image = $post[$key];
                break;
            }
        }
        if ($image === '') {
            continue;
        }

        /* Captions run to hashtag walls; only a short alt text is needed. */
        $caption = isset($post['text']) ? trim(preg_replace('/\s+/u', ' ', $post['text'])) : '';
        if ($caption !== '' && function_exists('mb_substr') && mb_strlen($caption, 'UTF-8') > 120) {
            $caption = rtrim(mb_substr($caption, 0, 117, 'UTF-8')) . '...';
        }

        $posts[] = [
            'image'   => $image,
            'url'     => !empty($post['url']) ? $post['url'] : '',
            'caption' => $caption,
        ];
    }

    return $posts !== [] ? $posts : null;
}

/**
 * Cached feed for the view. Never throws, never blocks for long, and returns []
 * only when there is nothing at all to show.
 */
function instagram_feed($limit = 8)
{
    $posts = app_cache_remember('instagram_feed', INSTAGRAM_FEED_TTL, function () {
        $fetched = instagram_fetch_posts();

        /* Kept separately and for far longer than the feed itself: this is what
           the section falls back to while the API is unreachable. */
        if ($fetched !== null) {
            app_cache_set('instagram_feed_last_good', $fetched, INSTAGRAM_FEED_FALLBACK_TTL);
        }

        return $fetched;
    });

    if (empty($posts)) {
        $posts = app_cache_get('instagram_feed_last_good');
    }

    if (empty($posts) || !is_array($posts)) {
        return [];
    }

    return array_slice($posts, 0, (int) $limit);
}
