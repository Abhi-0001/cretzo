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

        if (!has_permissions('read', 'media')) {
            $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
            redirect('admin/home', 'refresh');
        }
    }
    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = VIEW . 'media-gallary';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Media | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Media |' . $settings['app_name'];
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }
    public function upload()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
            exit();
        }
        if (print_msg(!has_permissions('create', 'media'), PERMISSION_ERROR_MSG, 'media')) {
            return false;
        }
        $year = date('Y');
        $target_path = FCPATH . MEDIA_PATH . $year . '/';
        $sub_directory = MEDIA_PATH . $year . '/';

        if (!file_exists($target_path)) {
            mkdir($target_path, 0777, true);
        }

        $temp_array = $media_ids = $other_images_new_name = array();
        // Every existing caller of this endpoint only ever reads error/message/csrfName/csrfHash
        // and re-fetches the media library table separately to find the file it just uploaded
        // (the two-step "upload, then go select it from the list" flow). Adding the saved
        // path for each file here is purely additive - it lets a caller attach an uploaded
        // image immediately, without touching how any existing caller behaves.
        $uploaded_files = array();
        $files = $_FILES;

        $other_image_info_error = "";
        $allowed_media_types = implode('|', allowed_media_types());
        $config['upload_path'] = $target_path;
        $config['allowed_types'] = $allowed_media_types;
        // No size cap was set at all here (CI's Upload library defaults max_size to 0 =
        // unlimited), and this is the one endpoint that accepts arbitrary file types
        // (image/video/doc/spreadsheet/archive) from any admin - bounded to 50MB, generous
        // enough for the video/archive types this library allows, without being unlimited.
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
                    if (strtolower($temp_array['image_type']) != 'gif')
                        resize_image($temp_array,  $target_path, $media_id);
                    $other_images_new_name[$i] = $temp_array['file_name'];
                    $uploaded_files[] = ['name' => $temp_array['file_name'], 'sub_directory' => $sub_directory];
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
            $this->response['file_name'] = '';
            $this->response['message'] = (empty($_FILES)) ? "Files not Uploaded Successfully..!" :  $other_image_info_error;
            print_r(json_encode($this->response));
        } else {
            $this->response['error'] = false;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['file_name'] = $_FILES['documents']['name'][0];
            $this->response['message'] = "Files Uploaded Successfully..!";
            $this->response['error'] = $other_image_info_error;
            $this->response['uploaded_files'] = $uploaded_files;
            print_r(json_encode($this->response));
        }
    }

    function delete($mediaid = false)
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
            exit();
        }
        if (print_msg(!has_permissions('delete', 'media'), PERMISSION_ERROR_MSG, 'media')) {
            return false;
        }
        $urlid = $this->uri->segment(4);
        $id = (isset($urlid)  && !empty($urlid)) ? $urlid : $mediaid;
        /* check if id is not empty or invalid */
        // Was "!is_numeric($id) && $id == ''", which only ever trips when $id is exactly an
        // empty string - non-numeric garbage like "abc" fell straight through (caught downstream
        // anyway by the "Media does not exist!" check below, so not a real hole, just dead logic).
        if (empty($id) || !is_numeric($id)) {
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

        // The single most important check on this page: nothing links a media row to where
        // it's actually used - every consuming table just stores the same path as plain text.
        // Without this, deleting a file still attached to a live product/category/brand/etc
        // silently broke that image site-wide, with no warning here that it was even in use.
        if ($this->media_model->is_media_in_use($media[0]['sub_directory'] . $media[0]['name'])) {
            $this->response['error'] = true;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = "This file is still in use elsewhere on the site and can't be deleted.";
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
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return $this->media_model->fetch_media();
        } else {
            redirect('admin/login', 'refresh');
        }
    }
}