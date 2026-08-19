<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Media extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'upload']);
        $this->load->helper(['url', 'language', 'file']);
        $this->load->model(['media_model']);
    }
    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $this->data['main_page'] = VIEW . 'media-gallary';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Media | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Media |' . $settings['app_name'];
            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }
    public function upload()
    {
        // This previously used inverted logic (required ALL of "not logged in AND not a
        // seller AND status 2/7" to block) which never actually holds for a real visitor —
        // meaning literally anyone, logged in or not, could upload files here. Replaced
        // with the standard "must be an active/pending seller" guard used everywhere else.
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0))) {
            redirect('seller/login', 'refresh');
            exit();
        }
        if (print_msg(!is_modification_allowed('create'), DEMO_VERSION_MSG, 'media', false)) {
            return false;
        }


        // When a POST body exceeds post_max_size, PHP discards both $_POST and $_FILES and hands
        // the script an empty request. Without this the dropzone got the generic "Files not
        // Uploaded Successfully..!" (or, on the seller side, a CSRF failure page, since the token
        // lives in the discarded $_POST) with no hint that the real problem was the batch size.
        if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
            $this->response['error'] = true;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = 'That upload is larger than this server accepts (post limit: ' . ini_get('post_max_size') . ', per-file limit: ' . ini_get('upload_max_filesize') . '). Upload fewer or smaller files at a time.';
            print_r(json_encode($this->response));
            return false;
        }

        // $_FILES['documents'] was dereferenced unconditionally below - a request that reaches
        // this action without the field (a bare GET, a mis-wired caller) died on an undefined
        // index instead of answering with JSON the dropzone can read.
        if (!isset($_FILES['documents']['name']) || !is_array($_FILES['documents']['name'])) {
            $this->response['error'] = true;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = 'No files were received.';
            print_r(json_encode($this->response));
            return false;
        }

        $year = date('Y');
        $target_path = FCPATH . MEDIA_PATH . $year . '/';
        $sub_directory = MEDIA_PATH . $year . '/';

        if (!file_exists($target_path)) {
            mkdir($target_path, 0777, true);
        }

        $temp_array = $media_ids = $other_images_new_name = $uploaded_files = array();
        $files = $_FILES;
        $other_image_info_error = "";

        // The media picker tells us what it's expecting via the media_type field (e.g.
        // "archive,document" for the digital-product attachment, "image" elsewhere) — if
        // present, restrict uploads to those extensions instead of every type this app
        // knows about, so a seller can't slip an .exe/.php in where an image was expected.
        $requested_media_type = trim((string) $this->input->post('media_type'));
        $restricted_extensions = [];
        if ($requested_media_type !== '') {
            $this->config->load('eshop');
            $type_config = $this->config->item('type');
            foreach (explode(',', $requested_media_type) as $type_key) {
                $type_key = trim($type_key);
                if (isset($type_config[$type_key]['types'])) {
                    $restricted_extensions = array_merge($restricted_extensions, $type_config[$type_key]['types']);
                }
            }
        }
        $allowed_media_types = !empty($restricted_extensions) ? implode('|', $restricted_extensions) : implode('|', allowed_media_types());
        $config['upload_path'] = $target_path;
        $config['allowed_types'] = $allowed_media_types;
        // No size cap was set here at all (CI's Upload library defaults max_size to 0 =
        // unlimited), unlike the admin side of the same endpoint. PHP's upload_max_filesize
        // masks it today, but nothing in the app enforced a ceiling of its own. Matches the
        // admin cap so both halves of the media library behave the same.
        $config['max_size'] = 51200;
        $other_image_cnt = count($_FILES['documents']['name']);
        $other_img = $this->upload;
        $other_img->initialize($config);
        for ($i = 0; $i < $other_image_cnt; $i++) {
            if (!empty($_FILES['documents']['name'][$i])) {

                $_FILES['temp_image']['name'] = $files['documents']['name'][$i];
                $_FILES['temp_image']['type'] = $files['documents']['type'][$i];
                $_FILES['temp_image']['tmp_name'] = $files['documents']['tmp_name'][$i];
                $_FILES['temp_image']['error'] = $files['documents']['error'][$i];
                $_FILES['temp_image']['size'] = $files['documents']['size'][$i];
                if (!$other_img->do_upload('temp_image')) {
                    $other_image_info_error = $other_image_info_error . ' ' . $other_img->display_errors();
                } else {
                    $temp_array = $other_img->data();
                    $temp_array['sub_directory'] = $sub_directory;
                    $media_ids[] = $media_id = $this->media_model->set_media($temp_array); /* set media in database */
                    // Skip GIFs, as the admin side already does: image_lib re-encodes only the
                    // first frame, so running an animated GIF through the resizer silently
                    // flattened it to a still image.
                    if (strtolower($temp_array['image_type']) != 'gif') {
                        resize_image($temp_array,  $target_path, $media_id);
                    }
                    $other_images_new_name[$i] = $temp_array['file_name'];
                    $uploaded_files[] = [
                        'name' => $temp_array['file_name'],
                        'sub_directory' => $sub_directory,
                        'url' => base_url() . $sub_directory . $temp_array['file_name'],
                    ];
                }
            } else {

                $_FILES['temp_image']['name'] = $files['documents']['name'][$i];
                $_FILES['temp_image']['type'] = $files['documents']['type'][$i];
                $_FILES['temp_image']['tmp_name'] = $files['documents']['tmp_name'][$i];
                $_FILES['temp_image']['error'] = $files['documents']['error'][$i];
                $_FILES['temp_image']['size'] = $files['documents']['size'][$i];
                if (!$other_img->do_upload('temp_image')) {
                    $other_image_info_error = $other_img->display_errors();
                }
            }
        }

        // Deleting Uploaded Images if any overall error occured
        if ($other_image_info_error != NULL) {
            if (isset($other_images_new_name) && !empty($other_images_new_name)) {
                foreach ($other_images_new_name as $key => $val) {
                    unlink($target_path . $other_images_new_name[$key]);
                }
            }
        }

        if (empty($_FILES) || $other_image_info_error != NULL) {
            $this->response['error'] = true;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = (empty($_FILES)) ? "Files not Uploaded Successfully..!" :  $other_image_info_error;
            print_r(json_encode($this->response));
        } else {
            $this->response['error'] = false;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = "Files Uploaded Successfully..!";
            $this->response['error'] = (isset($other_image_info_error) && !empty($other_image_info_error)) ? $other_image_info_error : false;
            $this->response['files'] = $uploaded_files;
            print_r(json_encode($this->response));
        }
    }

    function delete($mediaid = false)
    {
        if (print_msg(!is_modification_allowed('create'), DEMO_VERSION_MSG, 'media', false)) {
            return false;
        }
        // Was a blacklist of two statuses (2 and 7), which let through every other value -
        // including whatever a suspended/rejected seller ends up as. Use the same positive
        // "active or pending" test as upload()/index()/fetch() in this controller.
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0))) {
            redirect('seller/login', 'refresh');
            exit();
        }
        $urlid = $this->uri->segment(4);
        $id = (isset($urlid)  && !empty($urlid)) ? $urlid : $mediaid;
        /* check if id is not empty or invalid */
        if (!is_numeric($id) && $id == '') {
            $this->response['error'] = true;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = "Something went wrong! Try again!";
            print_r(json_encode($this->response));
            return false;
        }
        $media = $this->media_model->get_media_by_id($id);
        /* check if media actually exists */
        if (empty($media)) {
            $this->response['error'] = true;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = "Media does not exist!";
            print_r(json_encode($this->response));
            return false;
        }
        // The UI only ever shows the delete button for a seller's own uploads, but that's
        // not enforced anywhere server-side — without this check any seller could delete
        // any other seller's media file just by knowing its id.
        if ((int) $media[0]['seller_id'] !== (int) $this->ion_auth->get_user_id()) {
            $this->response['error'] = true;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = "Media does not exist!";
            print_r(json_encode($this->response));
            return false;
        }
        // The admin side of this endpoint already refuses to delete a file that is still
        // referenced somewhere on the site; the seller side never got that check. Nothing links
        // a media row to where it is used - every consuming table stores the same relative path
        // as plain text - so deleting an in-use file silently broke a live product image, with
        // get_image_url() quietly substituting the placeholder and no warning anywhere.
        if ($this->media_model->is_media_in_use($media[0]['sub_directory'] . $media[0]['name'])) {
            $this->response['error'] = true;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = "This file is still in use on one of your products and can't be deleted.";
            print_r(json_encode($this->response));
            return false;
        }

        $where = array('id' => $id);

        if (delete_details($where, 'media')) {

            delete_images($media[0]['sub_directory'], $media[0]['name']);
            $this->response['error'] = false;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = "Media deleted successfully!";
            print_r(json_encode($this->response));
            return false;
        } else {
            $this->response['error'] = true;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = "Media could not be deleted!";
            print_r(json_encode($this->response));
            return false;
        }
    }

    function fetch()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            return $this->media_model->fetch_media(true, $this->ion_auth->get_user_id());
        } else {
            redirect('seller/login', 'refresh');
        }
    }
}
