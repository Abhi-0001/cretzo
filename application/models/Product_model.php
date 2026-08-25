<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Product_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language', 'function_helper']);
    }

    /**
     * Search products by name and return id and name in array
     *
     * @param string $search_term The search term to look for in product names
     * @param int $limit Maximum number of results to return (default: 10)
     * @return array Array of products with id and name
     */
    public function search_products_by_name($search_term, $limit = 10)
    {
        $this->db->select("
                        products.id AS id,
                        CONCAT(products.name, ' (', categories.name, ')') AS name
                    ", false);
                
                    $this->db->from('products');
                    $this->db->join(
                        'categories',
                        'categories.id = products.category_id',
                        'left'
                    );
                
                    if (!empty($search_term)) {
                        $this->db->group_start();
                            $this->db->like('products.name', $search_term);
                            $this->db->or_like('categories.name', $search_term);
                        $this->db->group_end();
                    }
                
                    $this->db->where('products.status', 1);
                    $this->db->where('products.listing_visibility', 1);
                    $this->db->where('categories.status', 1);
                
                    $this->db->order_by('products.name', 'ASC');
                    $this->db->limit($limit);
                
                    $query = $this->db->get();
                    return $query->result_array();
    }

    /**
     * The single product_variants row that holds a simple / digital product's price.
     *
     * Any status is accepted - a soft-removed row (status 7) still has to be reused on
     * update, otherwise the edit would update nothing and the price would be lost.
     */
    private function get_simple_product_variant($product_id)
    {
        return $this->db->select('id')
            ->where('product_id', $product_id)
            ->order_by('status = 1', 'DESC', false)
            ->order_by('id', 'ASC')
            ->limit(1)
            ->get('product_variants')
            ->row_array();
    }

    public function add_product($data)
    {
        // Keep the pre-escape values. escape_array() is applied below for backwards
        // compatibility with the rest of this method (everything it writes is read back through
        // output_escaping(), which stripcslashes() the escaping off again), but a few fields are
        // IDENTIFIERS matched exactly against another table rather than text that gets displayed,
        // and for those the escaping must not be applied. See $pickup_location below.
        $raw_data = $data;
        $data = escape_array($data);


        if ($data['product_type'] == 'simple_product' || $data['product_type'] == 'variable_product') {
            $pro_type = ($data['product_type'] == 'simple_product') ? 'simple_product' : 'variable_product';
        } else {
            $pro_type = ($data['product_type'] == 'digital_product') ? 'digital_product' : '';
        }
        $short_description = $data['short_description'];
        $category_id = $data['category_id'];
        $seller_id = $data['seller_id'];

        // get seller product release permission
        $permits = fetch_details('seller_data', ['user_id' => $seller_id], 'permissions');
        $s_permits = json_decode($permits[0]['permissions'], true);
        if (isset($data['edit_product_id']) && !empty($data['edit_product_id'])) {
            $edit_status = fetch_details('products', ['id' => $data['edit_product_id']], 'status');
            $require_products_approval = isset($data['status']) && ($data['status'] != '') ? $data['status'] : $edit_status[0]['status'];
        } else {
            $is_permit = (isset($s_permits['require_products_approval']) && $s_permits['require_products_approval'] == 0) ? 1 : 2;
            $require_products_approval = $is_permit;
        }
        $made_in = (isset($data['made_in'])) ? $data['made_in'] : null;
        $brand = (isset($data['brand'])) ? $data['brand'] : null;
        $indicator = (isset($data['indicator'])) ? $data['indicator'] : null;
        $description = $data['pro_input_description'];
        $extra_description = $data['extra_input_description'];
        $tags = (!empty($data['tags'])) ? $data['tags'] : "";
        // create_unique_slug() must be told which row we're editing, otherwise it finds THIS
        // product's own existing slug, treats it as a collision and mints "name-1", "name-2",
        // ... on every save - silently changing the product's public URL
        // (products/details/<slug>) each time it is edited, breaking every shared link,
        // bookmark and indexed page for it. Live data already showed products sitting on "-3".
        $slug_edit_id = (isset($data['edit_product_id']) && !empty($data['edit_product_id'])) ? $data['edit_product_id'] : null;
        $slug   = ($slug_edit_id !== null)
            ? create_unique_slug($data['pro_input_name'], 'products', 'slug', 'id', $slug_edit_id)
            : create_unique_slug($data['pro_input_name'], 'products');
        $main_image_name = $data['pro_input_image'];
        $other_images = (isset($data['other_images']) && !empty($data['other_images'])) ? $data['other_images'] : [];
        if (isset($data['product_type']) && $data['product_type'] == 'digital_product') {
            $total_allowed_quantity = 1;
        } else {
            $total_allowed_quantity = (isset($data['total_allowed_quantity']) && !empty($data['total_allowed_quantity'])) ? $data['total_allowed_quantity'] : null;
        }
        $minimum_order_quantity = (isset($data['minimum_order_quantity']) && !empty($data['minimum_order_quantity'])) ? $data['minimum_order_quantity'] : 1;
        $quantity_step_size = (isset($data['quantity_step_size']) && !empty($data['quantity_step_size'])) ? $data['quantity_step_size'] : 1;
        $warranty_period = (isset($data['warranty_period']) && !empty($data['warranty_period'])) ? $data['warranty_period'] : "";
        $guarantee_period = (isset($data['guarantee_period']) && !empty($data['guarantee_period'])) ? $data['guarantee_period'] : "";
        $tax = (isset($data['pro_input_tax']) && $data['pro_input_tax'] != 0 && !empty($data['pro_input_tax'])) ? $data['pro_input_tax'] : 0;
        $video_type = (isset($data['video_type']) && !empty($data['video_type'])) ? $data['video_type'] : "";
        $video = (!empty($video_type)) ? (($video_type == 'youtube' || $video_type == 'vimeo') ? $data['video'] : $data['pro_input_video']) : "";
        $is_attachment_required = (isset($data['is_attachment_required'])) ? $data['is_attachment_required'] : '0';
        $hsn_code = (isset($data['hsn_code']) && !empty($data['hsn_code'])) ? $data['hsn_code'] : "";
        $download_type = (isset($data['download_link_type']) && !empty($data['download_link_type'])) ? $data['download_link_type'] : "";
        $download_link = (!empty($download_type)) ? (($download_type == 'add_link') ? $data['download_link'] : $data['pro_input_zip']) : "";
        /*
         * products.pickup_location is NOT NULL — a literal null here fatals the insert
         * (1048 "Column 'pickup_location' cannot be null"). Mirrors the same coercion
         * the mobile API already applies before calling this method.
         *
         * Read from $raw_data, NOT the escape_array()'d copy. This column holds the pickup
         * location's NICKNAME and is matched exactly against pickup_locations.pickup_location -
         * by create_shiprocket_order() to find the pickup pincode, and by
         * check_cart_products_delivarable() to check serviceability. escape_array() turned
         * "Developer's Den" into "Developer\'s Den" on the product while the pickup_locations
         * row holds the plain form, so the two never matched: verified on this database, all 12
         * products that name a pickup location resolve to nothing, which means no shipment can be
         * booked for them at all. Everything else in this method stays escaped because it is read
         * back through output_escaping(); an exact-match key cannot be.
         */
        $pickup_location = (isset($raw_data['pickup_location']) && $raw_data['pickup_location'] !== 'NULL' && $raw_data['pickup_location'] !== null) ? $raw_data['pickup_location'] : '';

        $pro_data = [
            'name' => $data['pro_input_name'],
            'short_description' => $short_description,
            'slug' => $slug,
            'type' => $pro_type,
            'tax' => $tax,
            'category_id' => $category_id,
            'seller_id' => $seller_id,
            'made_in' => $made_in,
            'brand' => $brand,
            'indicator' => $indicator,
            'image' => $main_image_name,
            'total_allowed_quantity' => $total_allowed_quantity,
            'minimum_order_quantity' => $minimum_order_quantity,
            'quantity_step_size' => $quantity_step_size,
            'warranty_period' => $warranty_period,
            'guarantee_period' => $guarantee_period,
            'other_images' => $other_images,
            'video_type' => $video_type,
            'video' => $video,
            'tags' => $tags,
            'status' => $require_products_approval,
            'description' => $description,
            'extra_description' => $extra_description,
            'deliverable_type' => isset($data['deliverable_type']) && !empty($data['deliverable_type']) ? $data['deliverable_type'] : 0,
            'deliverable_zipcodes' => ($data['deliverable_type'] == ALL || $data['deliverable_type'] == NONE) ? NULL : $data['zipcodes'],
            'hsn_code' => $hsn_code,
            'pickup_location' => $pickup_location,
            'is_attachment_required' => $is_attachment_required,
        ];

        if ($data['product_type'] == 'simple_product') {
            if (isset($data['simple_product_stock_status']) && empty($data['simple_product_stock_status'])) {
                $pro_data['stock_type'] = NULL;
            }

            if (isset($data['simple_product_stock_status'])  && in_array($data['simple_product_stock_status'], array('0', '1'))) {
                $pro_data['stock_type'] = '0';
            }

            if (isset($data['simple_product_stock_status'])  && in_array($data['simple_product_stock_status'], array('0', '1'))) {
                if (!empty($data['product_sku'])) {
                    $pro_data['sku'] = $data['product_sku'];
                }
                // Sanitised and reconciled: the form fields allow a negative/non-numeric level,
                // and the stock-status dropdown is independent of the level beside it, so
                // "In Stock" with a stock of 0 was storable and the listings believed it.
                $pro_data['stock'] = sanitise_stock_input($data['product_total_stock']);
                $pro_data['availability'] = reconcile_stock_availability($data['product_total_stock'], $data['simple_product_stock_status']);
            }
        }

        // Was written as a chain of variant_stock_status checks OR'd together, every
        // branch of which is true whenever the key exists - and when it doesn't, the
        // second test read the missing key (a warning on every save that omits it) and
        // came out true anyway. So the whole thing only ever meant "variable product",
        // which is all it says now. The stock level below still overrides this default.
        if ($data['product_type'] == 'variable_product') {
            $pro_data['stock_type'] = NULL;
        }
        if (isset($data['variant_stock_level_type']) && !empty($data['variant_stock_level_type']) && $data['product_type'] != 'digital_product') {
            $pro_data['stock_type'] = ($data['variant_stock_level_type'] == 'product_level') ? 1 : 2;
        }

        if (isset($data['is_attachment_required'])  && $data['is_attachment_required']) {
            $pro_data['is_attachment_required'] = '1';
        }

        if ($data['product_type'] != 'digital_product' && isset($data['is_returnable'])  && $data['is_returnable'] != "" && ($data['is_returnable'] == "on" || $data['is_returnable'] == '1')) {
            $pro_data['is_returnable'] = '1';
        } else {
            $pro_data['is_returnable'] = '0';
        }

        if ($data['product_type'] != 'digital_product' && isset($data['is_cancelable'])  && $data['is_cancelable'] != "" && ($data['is_cancelable'] == "on" || $data['is_cancelable'] == '1')) {
            $pro_data['is_cancelable'] = '1';
            $pro_data['cancelable_till'] = $data['cancelable_till'];
        } else {
            $pro_data['is_cancelable'] = '0';
            $pro_data['cancelable_till'] = '';
        }

        if (isset($data['download_allowed'])  && $data['download_allowed'] != "" && ($data['download_allowed'] == "on" || $data['download_allowed'] == '1')) {
            $pro_data['download_allowed'] = '1';
            $pro_data['download_type'] = $download_type;
            $pro_data['download_link'] = $download_link;
        } else {
            $pro_data['download_allowed'] = '0';
            $pro_data['download_type'] = '';
            $pro_data['download_link'] = '';
        }

        if ($data['product_type'] != 'digital_product' && isset($data['cod_allowed'])  && $data['cod_allowed'] != "" && ($data['cod_allowed'] == "on" || $data['cod_allowed'] == '1')) {
            $pro_data['cod_allowed'] = '1';
        } else {
            $pro_data['cod_allowed'] = '0';
        }

        if (isset($data['is_prices_inclusive_tax']) && $data['is_prices_inclusive_tax'] != "" && ($data['is_prices_inclusive_tax'] == "on" || $data['is_prices_inclusive_tax'] == '1')) {
            $pro_data['is_prices_inclusive_tax'] = '1';
        } else {
            $pro_data['is_prices_inclusive_tax'] = '0';
        }

        $variant_images = (!empty($data['variant_images']) && isset($data['variant_images'])) ? $data['variant_images'] : [];

        if (isset($data['edit_product_id'])) {
            if (empty($main_image_name)) {
                unset($pro_data['image']);
            }

            $pro_data['other_images'] = json_encode($other_images, 1);
            $this->db->set($pro_data)->where(['id' => $data['edit_product_id'], 'seller_id' => $seller_id])->update('products');
        } else {
            $pro_data['other_images'] = json_encode($other_images, 1);
            $this->db->insert('products', $pro_data);
        }
        $pro_variance_data['weight'] = 0.0;
        $p_id = (isset($data['edit_product_id'])) ? $data['edit_product_id'] : $this->db->insert_id();
        $pro_variance_data['product_id'] = $p_id;
        $pro_attr_data = [
            'product_id' => $p_id,
            // Both product forms send this field, but a caller that omits it (the API,
            // bulk upload) hit an undefined-key warning here rather than just saving no
            // attributes, which is the sensible reading of "not supplied".
            'attribute_value_ids' => isset($data['attribute_values']) ? strval($data['attribute_values']) : '',
        ];
        // print_r($pro_attr_data);


        if (isset($data['edit_product_id'])) {
            $this->db->where('product_id', $data['edit_product_id'])->update('product_attributes', $pro_attr_data);
        } else {
            $this->db->insert('product_attributes', $pro_attr_data);
        }

        if ($pro_type == 'simple_product') {
            $pro_variance_data = [
                'product_id' => $p_id,
                'price' => $data['simple_price'],
                'special_price' => (isset($data['simple_special_price']) && !empty($data['simple_special_price'])) ? $data['simple_special_price'] : '0',
                'weight' => (isset($data['weight'])) ? floatval($data['weight']) : 0,
                'height' => (isset($data['height'])) ? $data['height'] : 0,
                'breadth' => (isset($data['breadth'])) ? $data['breadth'] : 0,
                'length' => (isset($data['length'])) ? $data['length'] : 0,
            ];

            if (isset($data['edit_product_id'])) {
                $existing_variant = $this->get_simple_product_variant($data['edit_product_id']);
                if ((isset($_POST['reset_settings']) && trim($_POST['reset_settings']) == '1') || empty($existing_variant)) {
                    $this->db->insert('product_variants', $pro_variance_data);
                } else {
                    // Target the row by id and revive it: products whose variant row had been
                    // soft-removed (status 7) used to swallow the new price silently.
                    $pro_variance_data['status'] = 1;
                    $this->db->where('id', $existing_variant['id'])->update('product_variants', $pro_variance_data);
                }
            } else {
                $this->db->insert('product_variants', $pro_variance_data);
            }
        } elseif ($pro_type == 'digital_product') {
            $pro_variance_data = [
                'product_id' => $p_id,
                'price' => $data['simple_price'],
                'special_price' => (isset($data['simple_special_price']) && !empty($data['simple_special_price'])) ? $data['simple_special_price'] : '0',
            ];

            if (isset($data['edit_product_id'])) {
                $existing_variant = $this->get_simple_product_variant($data['edit_product_id']);
                if ((isset($_POST['reset_settings']) && trim($_POST['reset_settings']) == '1') || empty($existing_variant)) {
                    $this->db->insert('product_variants', $pro_variance_data);
                } else {
                    $pro_variance_data['status'] = 1;
                    $this->db->where('id', $existing_variant['id'])->update('product_variants', $pro_variance_data);
                }
            } else {
                $this->db->insert('product_variants', $pro_variance_data);
            }
        } else {
            $flag = " ";
            if (isset($data['variant_stock_status']) && $data['variant_stock_status'] == '0') {
                if ($data['variant_stock_level_type'] == "product_level") {
                    $flag = "product_level";
                    $pro_variance_data['sku'] = $data['sku_variant_type'];
                    // 'total_stock_variant_type' is not even validated as numeric by either
                    // controller, so this is the only thing standing between the form and the
                    // column. Same reconciliation as the simple-product branch above.
                    $pro_variance_data['stock'] = sanitise_stock_input($data['total_stock_variant_type']);
                    $pro_variance_data['availability']  = reconcile_stock_availability($data['total_stock_variant_type'], $data['variant_status']);
                    $variant_price = $data['variant_price'];
                    $variant_special_price = (isset($data['variant_special_price']) && !empty($data['variant_special_price'])) ? $data['variant_special_price'] : '0';
                    $variant_weight = (isset($data['weight'])) ? $data['weight'] : 0.0;
                    $variant_height = (isset($data['height'])) ? $data['height'] : 0.0;
                    $variant_breadth = (isset($data['breadth'])) ? $data['breadth'] : 0.0;
                    $variant_length = (isset($data['length'])) ? $data['length'] : 0.0;
                } else {
                    $flag = "variant_level";
                    $variant_price = $data['variant_price'];
                    $variant_special_price =  (isset($data['variant_special_price']) && !empty($data['variant_special_price'])) ? $data['variant_special_price'] : '0';
                    $variant_sku = $data['variant_sku'];
                    $variant_total_stock = $data['variant_total_stock'];
                    $variant_stock_status = $data['variant_level_stock_status'];
                    $variant_weight = (isset($data['weight'])) ? $data['weight'] : 0.0;
                    $variant_height = (isset($data['height'])) ? $data['height'] : 0.0;
                    $variant_breadth = (isset($data['breadth'])) ? $data['breadth'] : 0.0;
                    $variant_length = (isset($data['length'])) ? $data['length'] : 0.0;
                }
            } else {

                $variant_price = $data['variant_price'];
                $variant_special_price = (isset($data['variant_special_price']) && !empty($data['variant_special_price'])) ? $data['variant_special_price'] : '0';
                $variant_weight = (isset($data['weight'])) ? $data['weight'] : 0.0;
                $variant_height = (isset($data['height'])) ? $data['height'] : 0.0;
                $variant_breadth = (isset($data['breadth'])) ? $data['breadth'] : 0.0;
                $variant_length = (isset($data['length'])) ? $data['length'] : 0.0;
            }

            if (!empty($data['variants_ids'])) {
                $variants_ids = $data['variants_ids'];
                if (isset($data['edit_variant_id']) && !empty($data['edit_variant_id'])) {
                    $this->db->set('status', 7)->where('product_id', $data['edit_product_id'])->where('status !=', 0)->where_not_in('id', $data['edit_variant_id'])->update('product_variants');
                }

                if (!isset($data['edit_variant_id']) && isset($data['edit_product_id'])) {
                    $this->db->set('status', 7)->where('product_id', $data['edit_product_id'])->where('status !=', 0)->update('product_variants');
                }

                for ($i = 0; $i < count($variants_ids); $i++) {
                    $value = str_replace(' ', ',', trim($variants_ids[$i]));
                    if ($flag == "variant_level") {
                        $pro_variance_data['price'] = $variant_price[$i];
                        $pro_variance_data['special_price'] =  (isset($variant_special_price[$i]) && !empty($variant_special_price[$i])) ? $variant_special_price[$i] : '0';
                        $pro_variance_data['weight'] = $variant_weight[$i];
                        $pro_variance_data['height'] = $variant_height[$i];
                        $pro_variance_data['breadth'] = $variant_breadth[$i];
                        $pro_variance_data['length'] = $variant_length[$i];
                        $pro_variance_data['sku'] = $variant_sku[$i];
                        // Per-variant stock: same sanitise + reconcile as the other two branches.
                        $pro_variance_data['stock'] = sanitise_stock_input($variant_total_stock[$i]);
                        $pro_variance_data['availability'] = reconcile_stock_availability($variant_total_stock[$i], $variant_stock_status[$i]);
                    } else {
                        $pro_variance_data['price'] = $variant_price[$i];
                        $pro_variance_data['special_price'] = (isset($variant_special_price[$i]) && !empty($variant_special_price[$i])) ? $variant_special_price[$i] : '0';
                        $pro_variance_data['weight'] = $variant_weight[$i];
                        $pro_variance_data['height'] = $variant_height[$i];
                        $pro_variance_data['breadth'] = $variant_breadth[$i];
                        $pro_variance_data['length'] = $variant_length[$i];
                    }

                    if (isset($variant_images[$i]) && !empty($variant_images[$i])) {
                        $pro_variance_data['images'] = json_encode($variant_images[$i]);
                    } else {
                        $pro_variance_data['images'] = '[]';
                    }

                    $pro_variance_data['attribute_value_ids'] = $value;

                    if (isset($data['edit_variant_id'][$i]) && !empty($data['edit_variant_id'][$i])) {
                        $this->db->where('id', $data['edit_variant_id'][$i])->update('product_variants', $pro_variance_data);
                    } else {
                        $this->db->insert('product_variants', $pro_variance_data);
                    }
                }
            }
        }

        // Return the product id. This function saved the product and then returned nothing,
        // so callers had no handle on what they had just created - which is why the seller
        // panel could not tell the seller whether the product had gone live or landed in
        // the approval queue.
        return $p_id;
    }


    /**
     * Delete a product and everything that hangs off it.
     *
     * Both delete paths (seller panel and admin) previously removed only product_variants,
     * products and product_attributes. Everything else keyed on the product or its variants
     * was left behind:
     *   - `cart` rows pointing at a variant that no longer exists. This is the damaging one:
     *     the cart still counts the line, but the joins that price it find nothing, so
     *     checkout misbehaves for a shopper who did nothing wrong.
     *   - `favorites`, `product_faqs`, `product_rating` - dead rows that keep appearing in
     *     wishlists and rating aggregates for a product that is gone.
     * The live database already contains orphans of exactly these kinds from past deletes.
     *
     * `order_items` and `return_requests` are deliberately NOT touched - they are financial
     * history, and order_items denormalises product_name/variant_name/price so past orders
     * still render correctly after the product is gone.
     *
     * @return bool True when the product row itself was removed.
     */
    public function delete_product_cascade($product_id, $seller_id = null)
    {
        $product_id = (int) $product_id;
        if ($product_id <= 0) {
            return false;
        }

        // Scope to the owner when one is given, so a seller can only ever delete their own.
        $owner_where = ['id' => $product_id];
        if ($seller_id !== null) {
            $owner_where['seller_id'] = (int) $seller_id;
        }
        $owner_row = fetch_details('products', $owner_where, 'id,seller_id,listing_visibility');
        if (empty($owner_row)) {
            return false;
        }
        $owner_id = $owner_row[0]['seller_id'];
        $freed_a_slot = ((int) $owner_row[0]['listing_visibility'] === 1);

        $variant_ids = array_column(
            $this->db->select('id')->where('product_id', $product_id)->get('product_variants')->result_array(),
            'id'
        );

        $this->db->trans_start();

        if (!empty($variant_ids)) {
            $this->db->where_in('product_variant_id', $variant_ids)->delete('cart');
        }

        $this->db->where('product_id', $product_id)->delete('product_variants');
        $this->db->where('product_id', $product_id)->delete('product_attributes');
        $this->db->where('product_id', $product_id)->delete('favorites');
        $this->db->where('product_id', $product_id)->delete('product_faqs');
        $this->db->where('product_id', $product_id)->delete('product_rating');
        $this->db->where($owner_where)->delete('products');

        $this->db->trans_complete();

        $deleted = $this->db->trans_status();

        // Deleting a visible product frees one of the plan's slots — hand it to whichever
        // listing the cap was holding back, instead of leaving the seller below their limit.
        if ($deleted && $freed_a_slot && !empty($owner_id)) {
            $this->load->model('Seller_subscription_model');
            $this->Seller_subscription_model->enforce_listing_visibility($owner_id);
        }

        return $deleted;
    }

    public function get_product_details($flag = NULL, $seller_id = NULL, $p_status = NULL)
    {
        $settings = get_settings('system_settings', true);
        $low_stock_limit = isset($settings['low_stock_limit']) ? $settings['low_stock_limit'] : 5;
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';
        $multipleWhere = '';
        if (isset($_GET['offset']))

            $offset = $_GET['offset'];

        if (isset($_GET['limit']))

            $limit = $_GET['limit'];

        if (isset($_GET['sort']))
            if ($_GET['sort'] == 'id') {
                $sort = "product_variants.id";
            } else {
                $sort = $_GET['sort'];
            }

        if (isset($_GET['order']))
            $order = $_GET['order'];

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = trim($_GET['search']);
            $multipleWhere = ['p.`id`' => $search, 'p.`name`' => $search, 'p.`description`' => $search, 'p.`short_description`' => $search, 'c.name' => $search];
        }

        if (isset($_GET['category_id']) || isset($_GET['search'])) {
            if (isset($_GET['search']) and $_GET['search'] != '') {
                $multipleWhere['p.`category_id`'] = $search;
            }
            if (isset($_GET['category_id']) and $_GET['category_id'] != '') {
                $category_id = $_GET['category_id'];
            }
        }

        $count_res = $this->db->select(' COUNT( distinct(p.id)) as `total` ')->join(" categories c", "p.category_id=c.id ")->join('product_variants', 'product_variants.product_id = p.id');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->group_Start();
            $count_res->or_like($multipleWhere);
            $count_res->group_End();
        }

        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        if ($flag == 'low') {
            $where = "p.stock_type is  NOT NULL";
            $count_res->where($where);
            $count_res->group_Start();
            $count_res->where('p.stock <=', $low_stock_limit);
            $count_res->where('p.availability  =', '1');
            $count_res->or_where('product_variants.stock <=', $low_stock_limit);
            $count_res->where('product_variants.availability  =', '1');
            $count_res->group_End();
        }

        if (isset($seller_id) && $seller_id != "") {
            $count_res->where("p.seller_id", $seller_id);
        }

        if (isset($p_status) && $p_status != "") {
            $count_res->where("p.status", $p_status);
        }

        if ($flag == 'sold') {
            $where = "p.stock_type is  NOT NULL";
            $count_res->where($where);
            $count_res->group_Start();
            $count_res->where('p.stock ', '0');
            $count_res->where('p.availability ', '0');
            $count_res->or_where('product_variants.stock ', '0');
            $count_res->where('product_variants.availability ', '0');
            $count_res->group_End();
        }

        if (isset($category_id) && !empty($category_id)) {
            // Whole subtree: the old pair matched the category and its direct children only, so
            // products in a grandchild category were missing from the parent's listing.
            $count_res->where_in('p.category_id', category_descendant_ids($category_id));
        }

        $product_count = $count_res->get('products p')->result_array();

        foreach ($product_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select('product_variants.id AS id,c.name as category_name,COALESCE(NULLIF(sd.shop_name, ""), sd.store_name) as store_name, p.id as pid,p.rating,p.no_of_ratings,p.name, p.type, p.image, p.status,p.brand,product_variants.price , product_variants.special_price, product_variants.stock')
            ->join("categories c", "p.category_id=c.id")
            ->join("seller_data sd", "sd.user_id=p.seller_id ")
            ->join('product_variants', 'product_variants.product_id = p.id');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->group_Start();
            $search_res->or_like($multipleWhere);
            $search_res->group_End();
        }

        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }
        if ($flag != null && $flag == 'low') {
            $search_res->group_Start();
            $where = "p.stock_type is  NOT NULL";
            $search_res->where($where);
            $search_res->where('p.stock <=', $low_stock_limit);
            $search_res->where('p.availability  =', '1');
            $search_res->or_where('product_variants.stock <=', $low_stock_limit);
            $search_res->where('product_variants.availability  =', '1');
            $search_res->group_End();
        }

        if ($flag != null && $flag == 'sold') {
            $search_res->group_Start();
            $where = "p.stock_type is  NOT NULL";
            $search_res->where($where);
            $search_res->where('p.stock ', '0');
            $search_res->where('p.availability ', '0');
            $search_res->or_where('product_variants.stock ', '0');
            $search_res->where('product_variants.availability ', '0');
            $search_res->group_End();
        }

        if (isset($category_id) && !empty($category_id)) {
            // Must match the count query above so the pager total agrees with the rows returned.
            $search_res->where_in('p.category_id', category_descendant_ids($category_id));
        }

        if (isset($seller_id) && $seller_id != "") {
            $search_res->where("p.seller_id", $seller_id);
        }

        if (isset($p_status) && $p_status != "") {
            $search_res->where("p.status", $p_status);
        }

        $pro_search_res = $search_res->group_by('pid')->order_by($sort, "DESC")->limit($limit, $offset)->get('products p')->result_array();
        $currency = get_settings('currency');
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        // Whichever panel is rendering this table. Used below to pick the correct View/Edit
        // destinations - see the note at those two links for why this matters.
        $is_seller_context = $this->ion_auth->is_seller();

        foreach ($pro_search_res as $row) {
            $row = output_escaping($row);

            // This model is shared by both admin/product/get_product_data and
            // seller/product/get_product_data, but the View and Edit links were hardcoded to
            // seller/product/view-product and seller/product/create-product regardless of which
            // panel was asking. On the admin product list this sent an administrator's click to
            // a controller method guarded by is_seller(), which is false for an admin account -
            // the request silently redirected to the seller login page instead of showing
            // anything. Confirmed live: every row's View/Edit link pointed at seller/product/...
            // even when requested through admin/product/get_product_data. Both admin/product
            // controller methods (view_product / create_product) already exist, are already
            // gated on is_admin(), and were already reachable directly - this just makes the
            // list's own buttons use them.
            $view_url = $is_seller_context
                ? base_url('seller/product/view-product?edit_id=' . $row['pid'])
                : base_url('admin/product/view_product?edit_id=' . $row['pid']);
            $edit_url = $is_seller_context
                ? base_url('seller/product/create-product?edit_id=' . $row['pid'])
                : base_url('admin/product/create_product?edit_id=' . $row['pid']);

            $operate = "<div><a href='" . $view_url . "'  class='btn action-btn btn-primary btn-xs mr-1 mb-1' title='View'><i class='fa fa-eye'></i></a>";
            $operate .= " <a href='" . $edit_url . "' data-id=" . $row['pid'] . " class='btn action-btn btn-success btn-xs mr-1 mb-1' title='Edit' ><i class='fa fa-pen'></i></a>";
            if ($row['status'] == '2') {
                $tempRow['status'] = '<a class="badge badge-danger text-white">Not-Approved</a>';
                if ($is_seller_context) {
                    $operate .= '<a class="btn btn-secondary action-btn mr-1 mb-1 ml-1 btn-xs" data-table="products" href="javascript:void(0)" title="Not-Approved" ><i class="fa fa-ban"></i></a></div>';
                } else {
                    $operate .= '<a class="btn btn-secondary mr-1 mb-1 action-btn ml-1 btn-xs update_active_status" data-table="products" href="javascript:void(0)" title="Approve" data-id="' . $row['pid'] . '" data-status="' . $row['status'] . '" ><i class="fa fa-ban"></i></a></div>';
                }
            }
            if ($row['status'] == '1') {
                $tempRow['status'] = '<a class="badge badge-success text-white" >Active</a>';
                // The closing </div> for the opening tag above only ever appeared in the
                // status==0 branch below, so for every ACTIVE product (290 of 290 in this
                // database) the first <div> was left unclosed and the second <div> that follows
                // (rating/FAQ/delete buttons) nested incorrectly inside it.
                $operate .= '<a class="btn btn-warning action-btn btn-xs update_active_status mr-1 mb-1 ml-1" data-table="products" title="Deactivate" href="javascript:void(0)" data-id="' . $row['pid'] . '" data-status="' . $row['status'] . '" ><i class="fa fa-toggle-on"></i></a></div>';
            } else  if ($row['status'] == '0') {
                $tempRow['status'] = '<a class="badge badge-danger text-white" >Inactive</a>';
                $operate .= '<a class="btn btn-secondary action-btn mr-1 mb-1 ml-1 btn-xs update_active_status" data-table="products" href="javascript:void(0)" title="Active" data-id="' . $row['pid'] . '" data-status="' . $row['status'] . '" ><i class="fa fa-toggle-off"></i></a></div>';
            }
            $operate .= '<div><a href="javascript:void(0)" data-id=' . $row['pid'] . ' class="btn action-btn btn-danger mr-1 mb-1  btn-xs delete-product"><i class="fa fa-trash"></i></a>';
            $operate .= " <a href='javascript:void(0)' data-id=" . $row['pid'] . " data-toggle='modal' data-target='#product-rating-modal' class='btn action-btn btn-success btn-xs mr-1 mb-1' title='View Ratings' ><i class='fa fa-star'></i></a>";
            $operate .= "<a href='javascript:void(0)' data-id=" . $row['pid'] . " data-toggle='modal' data-target='#product-faqs-modal' class='btn action-btn btn-info btn-xs mr-1 mb-1 ml-1' title='View FAQs' ><i class='fas fa-question-circle'></i></a></div>";

            $attr_values  =  get_variants_values_by_pid($row['pid']);
            $tempRow['id'] = $row['pid'];
            $tempRow['varaint_id'] = $row['id'];
            $tempRow['name'] = $row['name'] . '<br><small>' . ucwords(str_replace('_', ' ', $row['type'])) . '</small><br><small> By </small><b>' . $row['store_name'] . '</b>';
            $tempRow['product_name'] = $row['name'] . '   ' . '<small>(' . ucwords(str_replace('_', ' ', $row['type'])) . '</small><small> By </small><b>' . $row['store_name'] . ')</b>';
            $tempRow['type'] = $row['type'];
            $tempRow['brand'] = $row['brand'];
            $tempRow['category_name'] = $row['category_name'];
            $tempRow['price'] =  ($row['special_price'] == null || $row['special_price'] == '0') ? $currency . $row['price'] : $currency . $row['special_price'];
            $tempRow['stock'] = $row['stock'];
            $variations = '';
            foreach ($attr_values as $variants) {
                if (isset($attr_values[0]['attr_name'])) {
                    if (!empty($variations)) {
                        $variations .= '---------------------<br>';
                    }
                    $attr_name = explode(',', $variants['attr_name']);
                    $varaint_values = explode(',', $variants['variant_values']);
                    for ($i = 0; $i < count($attr_name); $i++) {
                        $variations .= '<b>' . $attr_name[$i] . '</b> : ' . $varaint_values[$i] . '&nbsp;&nbsp;<b> Varient id : </b>' . $variants['id'] . '<br>';
                    }
                }
            }

            $tempRow['variations'] = (!empty($variations)) ? $variations : '-';
            $row['image'] = get_image_url($row['image'], 'thumb', 'sm');
            $tempRow['image'] = '<div class="mx-auto product-image image-box-100"><a href=' . $row['image'] . ' data-toggle="lightbox" data-gallery="gallery">
        <img src=' . $row['image'] . ' class="rounded"></a></div>';
            $tempRow['rating'] = '<input type="text" class="kv-fa rating-loading" value="' . $row['rating'] . '" data-size="xs" title="" readonly> <span> (' . $row['rating'] . '/' . $row['no_of_ratings'] . ') </span>';
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }

    public function get_digital_product_details($flag = NULL, $seller_id = NULL, $p_status = NULL)
    {
        $settings = get_settings('system_settings', true);
        $low_stock_limit = isset($settings['low_stock_limit']) ? $settings['low_stock_limit'] : 5;
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';
        $multipleWhere = '';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        if (isset($_GET['sort']))
            if ($_GET['sort'] == 'id') {
                $sort = "product_variants.id";
            } else {
                $sort = $_GET['sort'];
            }

        if (isset($_GET['order']))
            $order = $_GET['order'];

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = trim($_GET['search']);
            $multipleWhere = ['p.`id`' => $search, 'p.`name`' => $search, 'p.`description`' => $search, 'p.`short_description`' => $search, 'c.name' => $search];
        }

        if (isset($_GET['category_id']) || isset($_GET['search'])) {
            if (isset($_GET['search']) and $_GET['search'] != '') {
                $multipleWhere['p.`category_id`'] = $search;
            }

            if (isset($_GET['category_id']) and $_GET['category_id'] != '') {
                $category_id = $_GET['category_id'];
            }
        }

        $count_res = $this->db->select(' COUNT( distinct(p.id)) as `total` ')->join(" categories c", "p.category_id=c.id ")->join('product_variants', 'product_variants.product_id = p.id');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->group_Start();
            $count_res->or_like($multipleWhere);
            $count_res->group_End();
        }

        // $category_id was read from the query string and then never applied - the digital
        // products list ignored the category filter completely. Applied here (and on the row
        // query below) over the category's whole subtree.
        if (isset($category_id) && $category_id != '') {
            $count_res->where_in('p.category_id', category_descendant_ids($category_id));
        }

        $where = ['p.`type` =' => 'digital_product'];
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }
        // Was hardcoded to 1 instead of honouring $p_status, so filtering the digital
        // products list by any other status counted the status-1 rows regardless.
        if (isset($p_status) && $p_status != "") {
            $count_res->where("p.status", $p_status);
        }
        $product_count = $count_res->get('products p')->result_array();
        foreach ($product_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select('product_variants.id AS id,c.name as category_name,COALESCE(NULLIF(sd.shop_name, ""), sd.store_name) as store_name, p.id as pid,p.rating,p.no_of_ratings,p.name, p.type, p.image, p.status,p.brand,product_variants.price , product_variants.special_price, product_variants.stock')
            ->join("categories c", "p.category_id=c.id")
            ->join("seller_data sd", "sd.user_id=p.seller_id ")
            ->join('product_variants', 'product_variants.product_id = p.id');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->group_Start();
            $search_res->or_like($multipleWhere);
            $search_res->group_End();
        }

        if (isset($category_id) && $category_id != '') {
            $search_res->where_in('p.category_id', category_descendant_ids($category_id));
        }

        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        // The status filter was applied to the COUNT query but never to the row query, so
        // the digital products table reported one total and then listed a different set -
        // e.g. "1 record" in the pager while every status was still rendered below it.
        if (isset($p_status) && $p_status != "") {
            $search_res->where("p.status", $p_status);
        }

        $pro_search_res = $search_res->group_by('pid')->order_by($sort, "DESC")->limit($limit, $offset)->get('products p')->result_array();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        foreach ($pro_search_res as $row) {
            $row = output_escaping($row);
            $attr_values  =  get_variants_values_by_pid($row['pid']);
            $tempRow['id'] = $row['pid'];
            $tempRow['varaint_id'] = $row['id'];
            $tempRow['name'] = $row['name'] . '<br><small>' . ucwords(str_replace('_', ' ', $row['type'])) . '</small><br><small> By </small><b>' . $row['store_name'] . '</b>';
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }

    function get_countries($search_term = "")
    {
        // Fetch users
        $this->db->select('*');
        $this->db->where("name like '%" . $search_term . "%'");
        $fetched_records = $this->db->get('countries');
        $countries = $fetched_records->result_array();

        // Initialize Array with fetched data
        $data = array();
        foreach ($countries as $country) {
            $data[] = array("id" => $country['name'], "text" => $country['name']);
        }
        return $data;
    }

    function get_brands($search_term = "")
    {
        // Fetch users
        $this->db->select('*');
        $this->db->where("name like '%" . $search_term . "%'");
        $fetched_records = $this->db->get('brands');
        $brands = $fetched_records->result_array();
        // Initialize Array with fetched data
        $data = array();
        foreach ($brands as $brand) {
            $data[] = array("id" => $brand['name'], "text" => $brand['name']);
        }
        return $data;
    }

    function get_faqs_data($search_term = "")
    {
        // Fetch users

        $this->db->select('*');
        $this->db->where("question like '%" . $search_term . "%'");
        $fetched_records = $this->db->get('product_faqs');
        $faqs = $fetched_records->result_array();
        // Initialize Array with fetched data
        $data = array();
        foreach ($faqs as $faq) {
            $data[] = array("id" => $faq['id'], "text" => $faq['question']);
        }
        return $data;
    }

    function get_country_list($search = "", $offset = 0, $limit = 25)
    {
        $multipleWhere = '';
        $where = array();
        if (!empty($search)) {
            $multipleWhere = [
                '`name`' => $search,
            ];
        }

        $search_res = $this->db->select('id,name');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->group_start();
            $search_res->or_like($multipleWhere);
            $search_res->group_end();
        }

        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $countries = $search_res->limit($limit, $offset)->get('countries')->result_array();
        $bulkData = array();
        $bulkData['error'] = (empty($countries)) ? true : false;
        $bulkData['message'] = (empty($countries)) ? "Countries Not Found" : "Countries Retrived Successfully";
        if (!empty($countries)) {
            for ($i = 0; $i < count($countries); $i++) {
                $countries[$i] = output_escaping($countries[$i]);
            }
        }

        $bulkData['data'] = (empty($countries)) ? [] : $countries;
        return $bulkData;
    }

    function get_brand_list($search = "", $offset = 0, $limit = 25)

    {
        $multipleWhere = '';
        $where = array();
        if (!empty($search)) {
            $multipleWhere = [
                '`name`' => $search,
            ];
        }
        $search_res = $this->db->select('id,name,image,slug');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->group_start();
            $search_res->or_like($multipleWhere);
            $search_res->group_end();
        }

        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $brands = $search_res->limit($limit, $offset)->get('brands')->result_array();
        $bulkData = array();
        $bulkData['error'] = (empty($brands)) ? true : false;
        $bulkData['message'] = (empty($brands)) ? "Brands Not Found" : "Brands Retrived Successfully";

        if (!empty($brands)) {
            for ($i = 0; $i < count($brands); $i++) {
                $brands[$i] = output_escaping($brands[$i]);
                $brands[$i]['image'] = base_url() . $brands[$i]['image'];
            }
        }

        $bulkData['data'] = (empty($brands)) ? [] : $brands;
        return $bulkData;
    }

    /* add_product_faqs */

    function add_product_faqs($data)
    {
        $answered_by = fetch_details('users', 'id=' . $_SESSION['user_id'], 'username');
        $data = escape_array($data);
        if (isset($data['edit_product_faq'])) {
            $edit_data = [
                'answer' => $data['answer'],
                'answered_by' => $_SESSION['user_id'],
            ];

            $this->db->set($edit_data)->where('id', $data['edit_product_faq'])->update('product_faqs');
        } else {
            $faq_data = [
                'product_id' => $data['product_id'],
                'user_id' => $data['user_id'],
                'question' => $data['question'],
                'answer' => $data['answer'],
                'answered_by' => (isset($data['answer']) && ($data['answer']) != "") ? $data['answer_by'] : 0,
            ];

            $this->db->insert('product_faqs', $faq_data);
            return $this->db->insert_id();
        }
    }

    /* get_product_faqs */

    function get_product_faqs($id = '', $product_id = '', $user_id = '', $search = '', $offset = '0', $limit = '10', $sort = 'id', $order = 'DESC', $is_seller = false, $seller_id = '')
    {

        $multipleWhere = '';
        $where = array();
        if (!empty($search)) {
            $multipleWhere = [
                '`pf.id`' => $search, '`pf.product_id`' => $search, '`pf.user_id`' => $search, '`pf.question`' => $search, '`pf.answer`' => $search
            ];
        }

        if (!empty($id)) {
            $where['pf.id'] = $id;
        }

        if (!empty($product_id)) {
            $where['pf.product_id'] = $product_id;
        }

        if (!empty($user_id)) {
            $where['pf.user_id'] = $user_id;
        }

        if (!empty($seller_id)) {
            $where['pf.seller_id'] = $seller_id;
        }

        //  count of total product faqs

        $count_res = $this->db->select(' COUNT(pf.id) as `total`')
            ->join('users u', 'u.id=pf.user_id', 'left')
            ->join('products p', 'p.id=pf.product_id', 'left');

        // return;
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->group_start();
            $count_res->or_like($multipleWhere);
            $count_res->group_end();
        }

        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        $cat_count = $count_res->get('product_faqs pf')->result_array();

        foreach ($cat_count as $row) {
            $total = $row['total'];
        }

        // get product faqs data

        $search_res = $this->db->select('pf.*,u.username')
            ->join('users u', 'u.id=pf.user_id', 'left')
            ->join('products p', 'p.id=pf.product_id', 'left');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->group_start();
            $search_res->or_like($multipleWhere);
            $search_res->group_end();
        }

        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $faq_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('product_faqs pf')->result_array();

        $rows = $tempRow = $bulkData = array();


        if (!empty($faq_search_res)) {

            foreach ($faq_search_res as $row) {
                // print_R($row);
                $row = output_escaping($row);
                $tempRow['id'] = $row['id'];
                $tempRow['product_id'] = $row['product_id'];
                $tempRow['user_id'] = $row['user_id'];
                $tempRow['username'] = $row['username'];
                $tempRow['question'] = $row['question'];
                $tempRow['votes'] = $row['votes'];
                $tempRow['answered_by'] = (isset($row['answered_by']) && $row['answered_by'] != '') ? $row['answered_by'] : '';
                $ans_by_name = fetch_details('users', 'id=' . $row['answered_by'], 'username');
                $tempRow['answered_by_name'] = (isset($row['answered_by']) && $row['answered_by'] != '' && !empty($ans_by_name[0]['username'])) ? $ans_by_name[0]['username'] : '';
                $tempRow['date_added'] = $row['date_added'];

                if (isset($is_seller) && (($is_seller == FALSE) && ((isset($row['answer']) && $row['answer'] == '')))) {

                    unset($tempRow);
                } else {

                    $tempRow['answer'] = (isset($row['answer']) && $row['answer'] != '') ? $row['answer'] : "";
                }

                if (isset($tempRow) && !empty($tempRow)) {
                    $rows[] = $tempRow;
                }
            }
            $bulkData['error'] = (empty($faq_search_res)) ? true : false;
            $bulkData['message'] = (empty($faq_search_res)) ? 'FAQs does not exist' : 'FAQs retrieved successfully';
            $bulkData['total'] = (empty($faq_search_res)) ? 0 : count($rows);
            $bulkData['data'] = $rows;
        } else {
            $bulkData['error'] = true;
            $bulkData['message'] = 'FAQs does not exist';
            $bulkData['total'] = 0;
            $bulkData['data'] = [];
        }
        return $bulkData;
    }

    public function delete_faq($faq_id)
    {
        $faq_id = escape_array($faq_id);
        $this->db->delete('product_faqs', ['id' => $faq_id]);
    }

    public function get_faqs($seller_id = null)
    {
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        $multipleWhere = '';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];
        if (isset($_GET['sort']))
            if ($_GET['sort'] == 'id') {
                $sort = "id";
            } else {
                $sort = $_GET['sort'];
            }

        if (isset($order) and $order != '') {
            $search = $order;
        }

        if (isset($_GET['product_id']) && $_GET['product_id'] != null) {
            $where['product_id'] = $_GET['product_id'];
        }

        $count_res = $this->db->select(' COUNT(pf.id) as total  ')->join('users u', 'u.id=pf.user_id');
        if (isset($_GET['search']) && trim($_GET['search'])) {
            $search = trim($_GET['search']);
            $multipleWhere = ['pf.id' => $search, 'pf.product_id' => $search, 'pf.user_id' => $search];
        }

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $this->db->group_start();
            $count_res->or_like($multipleWhere);
            $this->db->group_end();
        }

        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }
        // Scope to the requesting seller's own products only. Joined against the products
        // table (rather than trusting product_faqs.seller_id, which isn't reliably set) so
        // a seller can never see another seller's FAQs.
        if (!empty($seller_id)) {
            $count_res->join('products p', 'p.id = pf.product_id')->where('p.seller_id', $seller_id);
        }

        $rating_count = $count_res->get('product_faqs pf')->result_array();
        foreach ($rating_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select('pf.*,u.username as user_name')->join('users u', 'u.id=pf.user_id');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $this->db->group_start();

            $search_res->or_like($multipleWhere);

            $this->db->group_end();
        }



        if (isset($where) && !empty($where)) {

            $search_res->where($where);
        }
        if (!empty($seller_id)) {
            $search_res->join('products p', 'p.id = pf.product_id')->where('p.seller_id', $seller_id);
        }



        $rating_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('product_faqs pf')->result_array();



        $bulkData = array();

        $bulkData['total'] = $total;

        $rows = array();

        $tempRow = array();



        $i = 0;

        foreach ($rating_search_res as $row) {

            $row = output_escaping($row);

            $date = new DateTime($row['date_added']);

            if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

                $operate = ' <a href="javascript:void(0)" class="edit_btn btn btn-success btn-xs mr-1 mb-1" title="View" data-id="' . $row['id'] . '" data-url="admin/product/"><i class="fa fa-edit"></i></a>';

                $operate .= '<a class="btn btn-danger btn-xs mr-1 mb-1 delete-product-faq" href="javascript:void(0)" title="Delete" data-id="' . $row['id'] . '" ><i class="fa fa-trash"></i></a>';
            } else {

                $operate = ' <a href="javascript:void(0)" class="edit_btn btn btn-success btn-xs mr-1 mb-1" title="View" data-id="' . $row['id'] . '" data-url="seller/product/"><i class="fa fa-edit"></i></a>';

                $operate .= '<a class="btn btn-danger btn-xs mr-1 mb-1 delete-seller-product-faq" href="javascript:void(0)" title="Delete" data-id="' . $row['id'] . '" ><i class="fa fa-trash"></i></a>';
            }

            $tempRow['id'] = $row['id'];

            $tempRow['user_id'] = $row['user_id'];

            $tempRow['product_id'] = $row['product_id'];

            $tempRow['votes'] = $row['votes'];

            $tempRow['question'] = $row['question'];

            $tempRow['answer'] = $row['answer'];

            $tempRow['answered_by'] = $row['answered_by'];

            $tempRow['username'] = $row['user_name'];

            $tempRow['date_added'] = $date->format('d-M-Y');

            $tempRow['operate'] = $operate;

            $rows[] = $tempRow;

            $i++;
        }

        $bulkData['rows'] = $rows;

        print_r(json_encode($bulkData));
    }



    public function get_stock_details()

    {

        $filters['show_only_stock_product'] = true;

        /*
         * Manage Stock is an inventory screen, not a shop listing, so it must not inherit the
         * storefront visibility rules. fetch_product() otherwise applies
         *   p.status = 1 AND pv.status = 1 AND sd.status = 1 AND p.listing_visibility = 1
         *   AND (c.status = '1' OR c.status = '0')
         * which hides stock for three reasons that have nothing to do with inventory:
         *   - listing_visibility = 2 means the seller is over their PLAN's listing cap. Their
         *     stock still exists and still needs managing; on this database 176 of 290 products
         *     are in that state.
         *   - the category filter compares c.status to '1'/'0', so a product whose category row
         *     is missing (c.status IS NULL) is excluded. 177 products here reference category
         *     ids that no longer exist, and every product that passed the other filters fell
         *     into exactly that group.
         *   - a deactivated product or seller still has counted stock an admin may need to see.
         * Between them these left the admin and seller Manage Stock screens showing ZERO rows
         * while 270 products had stock recorded - verified live before this change.
         *
         * show_only_active_products = 0 is fetch_product()'s own documented switch for
         * "administrative read, not a storefront read" and sets the where-clause to empty.
         */
        $filters['show_only_active_products'] = 0;

        // Also list products whose category row has been deleted. 177 products here reference a
        // category id that no longer exists, which made them unreachable from every screen -
        // including the one an admin would use to notice and fix them.
        $filters['ignore_category_status'] = 1;

        $offset = 0;

        $limit = 10;

        $sort = 'id';

        $order = 'ASC';

        $filters['search'] =  (isset($_GET['search'])) ? $_GET['search'] : null;

        // $filter['search'] = (isset($_GET['search']) && !empty($_GET['search'])) ? $_GET['search'] : '';

        if (isset($_GET['seller_id'])) {

            $seller_id = $_GET['seller_id'];
        }

        if (isset($_GET['category_id'])) {

            $category_id = $_GET['category_id'];
        }

        if (isset($_GET['offset']))

            $offset = $_GET['offset'];

        if (isset($_GET['limit']))

            $limit = $_GET['limit'];

        // $order was passed straight through with no whitelist - $sort itself is hardcoded
        // above (never read from $_GET), so this alone isn't the same class of injection risk
        // as elsewhere, but relying on that rather than an explicit whitelist is fragile.
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'desc')

            $order = 'DESC';

        $products = fetch_product("", (isset($filters)) ? $filters : null, "", isset($category_id) ? $category_id : null, $limit, $offset, $sort, $order, "", "", isset($seller_id) ? $seller_id : null);

        $total = $products['total'];

        $bulkData = $rows = $tempRow = array();

        $bulkData['total'] = $total;





        foreach ($products['product'] as $product) {

            $category_id = $product['category_id'];

            $category_name = fetch_details('categories', ['id' => $category_id], 'name');

            $operate = $stock = "";

            $variants = get_variants_values_by_pid($product['id']);

            $stock = implode("<br/>", array_column($variants, 'stock'));



            /*
             * Two unguarded reads, both of which fire on real data:
             *   - $product['variants'][0]['id'] assumes the product HAS a variant row. 13 active
             *     products here have none, so this was "Undefined array key 0" followed by
             *     "Trying to access array offset on value of type null".
             *   - $category_name[0]['name'] assumes the category row exists. 177 products here
             *     reference a category id that has been deleted, so the lookup returns nothing.
             * In development both warnings print into the response body and corrupt the JSON the
             * stock table is parsing; in production they fill the log and render an empty cell.
             * A product with no variants still has a row worth showing - it just has no variant
             * id - so fall back to the product id rather than skipping it.
             */
            $first_variant_id = isset($product['variants'][0]['id']) ? $product['variants'][0]['id'] : null;
            $resolved_category = isset($category_name[0]['name']) ? $category_name[0]['name'] : '';

            $tempRow['id'] = $first_variant_id;

            // Neither product name, category name, nor variant attribute values were ever
            // escaped here - all three are seller-controlled input, rendered raw into the
            // bootstrap-table's HTML payload. A stored-XSS route the same as already fixed
            // on other list pages.
            $tempRow['name'] = html_escape($product['name']);

            $tempRow['seller_name'] = html_escape($product['seller_name']);

            $tempRow['category_name'] = html_escape($resolved_category);

            $tempRow['image'] = '<div class="mx-auto product-image image-box-100"><a href=' . $product['image'] . ' data-toggle="lightbox" data-gallery="gallery"><img src=' . $product['image'] . ' class="rounded"></a></div>';

            // $edit was only ever assigned inside this loop - a product with zero variant rows
            // (a data inconsistency, but not impossible) left it undefined, and the ternary
            // below then referenced it regardless, producing a PHP notice and an Edit button
            // with no real target for that row.
            $edit = '';
            $operate = "<table class='table-borderless table-sm w-100'>";

            // $edit is assigned inside the loop below. With no variants the loop never runs, so
            // the simple-product line at the end of this block used either an undefined variable
            // or - worse - the edit button built for the PREVIOUS product in the list, pointing
            // the admin at the wrong variant.
            $edit = '';

            for ($i = 0; $i < count($variants); $i++) {

                $edit = '<a href="javascript:void(0)" class="edit_btn btn action-btn btn-success btn-xs mr-1 mb-1" title="Edit" data-id="' . $variants[$i]['id'] . '" data-url="admin/manage_stock/"><i class="fa fa-pen"></i></a>';

                $operate .= "<tr> <th>" . html_escape(str_replace(",", ", ", $variants[$i]['variant_values']))  . '</th>';

                if ($product['stock_type'] != 1) {

                    $operate .= '<td><b>' . str_replace(",", ", ", $variants[$i]['stock']) . '</b></td>';

                    $operate .= '<td><b>' . $edit  . '</b></td></tr>';
                } else {

                    if ($i == 0) {

                        $operate .= '<td rowspan="' . count($variants) . '"><b>' .  $variants[$i]['stock'] . '</b></td>';

                        $operate .= '<td rowspan="' . count($variants) . '"><b>' . $edit  . '</b></td></tr>';
                    }
                }
            }

            $operate .= "</table>";

            /*
             * Which view this row gets is decided by stock_type, not by whether products.stock
             * happens to hold a number.
             *
             * products.stock is only maintained for a simple product (stock_type 0). For a
             * product-level or variant-level product the real figure lives on the variants - see
             * update_stock() and validate_stock(), which both read and write product_variants for
             * those types - and products.stock is a stale leftover nothing ever updates. Because
             * this line only tested !empty(), any such leftover made the report throw away the
             * per-variant table carefully built just above and print "Simple Product" with the
             * dead number instead.
             *
             * Measured live: product 25 "Handmade Hair clips" is stock_type 1 with its variants
             * holding 77, and this report displayed "Simple Product" and 21 - a figure no
             * customer, order or stock movement has anything to do with. On a stock report that
             * is the one thing that must not happen.
             */
            $row_stock_type = normalise_stock_type($product['stock_type']);
            $has_product_stock = isset($product['stock']) && $product['stock'] !== '' && $product['stock'] !== null;

            if ($row_stock_type === 0 && $has_product_stock) {
                $tempRow['operate'] = '<table class="table-borderless table-sm w-100"><tr><th><b>' . 'Simple Product' . '</b></th><td> <b>' . ($product['stock']) . '</b></td><td>' . ' ' . $edit . "</td></tr></table>";
            } else {
                $tempRow['operate'] = $operate;
            }

            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;



        print_r(json_encode($bulkData));
    }



    public function get_seller_stock_details()

    {

        $seller_id = $_SESSION['user_id'];

        $filters['show_only_stock_product'] = true;

        /*
         * Same reasoning as get_stock_details() above - this is the SELLER's copy of the same
         * screen and had the same defect. Manage Stock is an inventory screen, so it must not
         * inherit the storefront's visibility rules:
         *   - listing_visibility = 2 means the seller is over their plan's listing cap; the
         *     stock still exists and is still theirs to manage.
         *   - the category filter excludes a product whose category row was deleted.
         *   - a deactivated product still has counted stock.
         * Verified live: the admin screen listed 267 of this seller's stock products while the
         * seller's own screen listed ZERO.
         *
         * Seller scoping is unaffected - fetch_product() applies p.seller_id from the $seller_id
         * argument separately, after the visibility where-clause is built, so relaxing these
         * filters cannot show one seller another seller's stock. Confirmed by comparing the
         * variant ids this returns against products.seller_id.
         */
        $filters['show_only_active_products'] = 0;
        $filters['ignore_category_status'] = 1;

        $offset = 0;

        $limit = 10;

        $sort = 'id';

        $order = 'ASC';

        $filters['search'] =  (isset($_GET['search'])) ? $_GET['search'] : null;

        // $filter['search'] = (isset($_GET['search']) && !empty($_GET['search'])) ? $_GET['search'] : '';

        if (isset($_GET['offset']))

            $offset = $_GET['offset'];

        if (isset($_GET['limit']))

            $limit = $_GET['limit'];

        // Was assigned straight from the querystring. $sort is hardcoded to 'id' above so this
        // is not the same injection shape as elsewhere, but the admin copy of this method already
        // whitelists the direction and relying on that accident is fragile.
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'desc')

            $order = 'DESC';

        if (isset($_GET['category_id'])) {

            $category_id = $_GET['category_id'];
        }



        $products = fetch_product("", (isset($filters)) ? $filters : null, "", isset($category_id) ? $category_id : '', $limit, $offset, $sort, $order, "", "", $seller_id);

        $total = $products['total'];

        $bulkData = $rows = $tempRow = array();

        $bulkData['total'] = $total;





        foreach ($products['product'] as $product) {

            $category_id = $product['category_id'];

            $category_name = fetch_details('categories', ['id' => $category_id], 'name');

            $operate = $stock = "";

            $variants = get_variants_values_by_pid($product['id']);

            $stock = implode("<br/>", array_column($variants, 'stock'));



            /*
             * Two unguarded reads, both of which fire on real data:
             *   - $product['variants'][0]['id'] assumes the product HAS a variant row. 13 active
             *     products here have none, so this was "Undefined array key 0" followed by
             *     "Trying to access array offset on value of type null".
             *   - $category_name[0]['name'] assumes the category row exists. 177 products here
             *     reference a category id that has been deleted, so the lookup returns nothing.
             * In development both warnings print into the response body and corrupt the JSON the
             * stock table is parsing; in production they fill the log and render an empty cell.
             * A product with no variants still has a row worth showing - it just has no variant
             * id - so fall back to the product id rather than skipping it.
             */
            $first_variant_id = isset($product['variants'][0]['id']) ? $product['variants'][0]['id'] : null;
            $resolved_category = isset($category_name[0]['name']) ? $category_name[0]['name'] : '';

            $tempRow['id'] = $first_variant_id;

            // Escaped for the same reason the admin copy is: these are seller-authored strings
            // rendered straight into the table's HTML payload.
            $tempRow['name'] = html_escape($product['name']);

            $tempRow['seller_name'] = html_escape((string) $product['seller_name']);

            $tempRow['category_name'] = html_escape($resolved_category);

            $tempRow['image'] = '<div class="mx-auto product-image image-box-100"><a href=' . $product['image'] . ' data-toggle="lightbox" data-gallery="gallery"><img src=' . $product['image'] . ' class="rounded"></a></div>';

            $operate = "<table class='table-borderless table-sm w-100'>";

            // $edit is assigned inside the loop below. With no variants the loop never runs, so
            // the simple-product line at the end of this block used either an undefined variable
            // or - worse - the edit button built for the PREVIOUS product in the list, pointing
            // the admin at the wrong variant.
            $edit = '';

            for ($i = 0; $i < count($variants); $i++) {

                $edit = '<a href="javascript:void(0)" class="edit_btn btn btn-success btn-xs mr-1 mb-1" title="Edit" data-id="' . $variants[$i]['id'] . '" data-url="seller/manage_stock/"><i class="fa fa-pen"></i> Edit</a>';

                $operate .= "<tr> <th>" . str_replace(",", ", ", $variants[$i]['variant_values'])  . '</th>';

                if ($product['stock_type'] != 1) {

                    $operate .= '<td><b>' . str_replace(",", ", ", $variants[$i]['stock']) . '</b></td>';

                    $operate .= '<td><b>' . $edit  . '</b></td></tr>';
                } else {

                    if ($i == 0) {

                        $operate .= '<td rowspan="' . count($variants) . '"><b>' .  $variants[$i]['stock'] . '</b></td>';

                        $operate .= '<td rowspan="' . count($variants) . '"><b>' . $edit  . '</b></td></tr>';
                    }
                }
            }

            $operate .= "</table>";

            /*
             * Which view this row gets is decided by stock_type, not by whether products.stock
             * happens to hold a number.
             *
             * products.stock is only maintained for a simple product (stock_type 0). For a
             * product-level or variant-level product the real figure lives on the variants - see
             * update_stock() and validate_stock(), which both read and write product_variants for
             * those types - and products.stock is a stale leftover nothing ever updates. Because
             * this line only tested !empty(), any such leftover made the report throw away the
             * per-variant table carefully built just above and print "Simple Product" with the
             * dead number instead.
             *
             * Measured live: product 25 "Handmade Hair clips" is stock_type 1 with its variants
             * holding 77, and this report displayed "Simple Product" and 21 - a figure no
             * customer, order or stock movement has anything to do with. On a stock report that
             * is the one thing that must not happen.
             */
            $row_stock_type = normalise_stock_type($product['stock_type']);
            $has_product_stock = isset($product['stock']) && $product['stock'] !== '' && $product['stock'] !== null;

            if ($row_stock_type === 0 && $has_product_stock) {
                $tempRow['operate'] = '<table class="table-borderless table-sm w-100"><tr><th><b>' . 'Simple Product' . '</b></th><td> <b>' . ($product['stock']) . '</b></td><td>' . ' ' . $edit . "</td></tr></table>";
            } else {
                $tempRow['operate'] = $operate;
            }

            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;



        print_r(json_encode($bulkData));
    }

    /**
     * Products at or below the low-stock threshold, resolved through each product's stock_type
     * so the number compared is the column that actually holds its stock.
     *
     * The dashboard counters OR the product-level and variant-level stock columns together
     * without regard to stock_type and rely on NULL propagation to keep the wrong one out.
     * That mostly works, but it silently misses the 10 products whose stock_type is the string
     * 'simple_product'. This resolves the type explicitly instead.
     *
     * @return array rows of product_id, product_variant_id, product_name, variant_name,
     *               seller_id, stock
     */
    public function get_low_stock_items($limit = 5)
    {
        $limit = (int) $limit;

        $rows = $this->db
            ->select('p.id AS product_id, p.name AS product_name, p.seller_id, p.stock_type,
                      p.stock AS p_stock, pv.id AS product_variant_id, pv.stock AS pv_stock,
                      pv.attribute_value_ids', false)
            ->join('product_variants pv', 'pv.product_id = p.id')
            ->where('p.stock_type IS NOT NULL', null, false)
            ->where('p.stock_type !=', '')
            ->where('p.status', 1)
            ->where('pv.status !=', 7)
            ->get('products p')
            ->result_array();

        $out = [];
        $seen_products = [];

        foreach ($rows as $row) {
            $stock_type = normalise_stock_type($row['stock_type']);
            if ($stock_type === null) {
                continue;
            }

            $stock = ($stock_type === 0) ? $row['p_stock'] : $row['pv_stock'];
            if ($stock === null || (int) $stock > $limit) {
                continue;
            }

            // Simple and product-level stock is one number shared by the whole product, so
            // report it once rather than once per variant row.
            if ($stock_type !== 2) {
                if (isset($seen_products[$row['product_id']])) {
                    continue;
                }
                $seen_products[$row['product_id']] = true;
            }

            $out[] = [
                'product_id'         => $row['product_id'],
                'product_variant_id' => $row['product_variant_id'],
                'product_name'       => $row['product_name'],
                'variant_name'       => '',
                'seller_id'          => $row['seller_id'],
                'stock'              => (int) $stock,
            ];
        }

        return $out;
    }

    /**
     * Claim the right to warn about this variant, at this level.
     *
     * Returns TRUE only if nobody has warned about it yet, or it has since dropped LOWER than
     * the level of the open warning. The insert is attempted before the mail is sent and the
     * table has a UNIQUE key on product_variant_id, so two overlapping runs cannot both claim.
     */
    public function claim_low_stock_alert($product_id, $product_variant_id, $stock)
    {
        $stock = (int) $stock;

        $existing = $this->db
            ->where('product_variant_id', $product_variant_id)
            ->get('low_stock_alerts')
            ->row_array();

        if (!empty($existing)) {
            if ($stock < (int) $existing['alerted_at_stock']) {
                $this->db->where('id', $existing['id'])->update('low_stock_alerts', [
                    'alerted_at_stock' => $stock,
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);
                return true; // dropped further - worth saying so
            }
            return false;
        }

        $this->db->db_debug = false;
        $claimed = $this->db->insert('low_stock_alerts', [
            'product_id'         => $product_id,
            'product_variant_id' => $product_variant_id,
            'alerted_at_stock'   => $stock,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
        $this->db->db_debug = true;

        return (bool) $claimed;
    }

    /** Give the claim back when the notification could not actually be sent. */
    public function release_low_stock_alert($product_id, $product_variant_id)
    {
        return $this->db->where('product_variant_id', $product_variant_id)->delete('low_stock_alerts');
    }

    /** Recovery: clear a single variant's warning so it can alert again if it falls anew. */
    public function clear_low_stock_alert($product_id, $product_variant_id)
    {
        return $this->db->where('product_variant_id', $product_variant_id)->delete('low_stock_alerts');
    }

    /**
     * Drop warnings for anything that has climbed back above the threshold, so the next fall
     * is reported instead of being suppressed by a stale claim.
     */
    public function clear_recovered_low_stock_alerts($limit = 5)
    {
        $limit = (int) $limit;

        $open = $this->db->select('id, product_id, product_variant_id')->get('low_stock_alerts')->result_array();
        $cleared = 0;

        foreach ($open as $row) {
            $stock = get_variant_current_stock($row['product_variant_id']);
            if ($stock === null || $stock > $limit) {
                $this->db->where('id', $row['id'])->delete('low_stock_alerts');
                $cleared++;
            }
        }

        return $cleared;
    }
}
