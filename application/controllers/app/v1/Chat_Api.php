<?php

defined('BASEPATH') or exit('No direct script access allowed');


class Chat_Api extends CI_Controller
{

    /*
---------------------------------------------------------------------------
Defined Methods:-
---------------------------------------------------------------------------

    1. get_groups    
    2. get_chat_history
    3. Load_chat
    4. delete_msg
    
---------------------------------------------------------------------------
---------------------------------------------------------------------------

*/

    public function __construct()
    {
        parent::__construct();
        header("Content-Type: application/json");
        header("Expires: 0");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        $this->load->library(['upload', 'jwt', 'ion_auth', 'form_validation', 'Key']);
        $this->load->model(['Customer_model', 'chat_model', 'notification_model', 'Setting_model', 'media_model']);
        $this->load->helper(['language', 'string']);
        $this->form_validation->set_error_delimiters($this->config->item('error_start_delimiter', 'ion_auth'), $this->config->item('error_end_delimiter', 'ion_auth'));
        $this->lang->load('auth');
        $response = $temp = $bulkdata = array();
        $this->identity_column = $this->config->item('identity', 'ion_auth');
        // initialize db tables data
        $this->tables = $this->config->item('tables', 'ion_auth');
    }
    public function index()
    {
        $this->load->helper('file');
        $this->output->set_content_type(get_mime_by_extension(base_url('api-doc.txt')));
        $this->output->set_output(file_get_contents(base_url('api-doc.txt')));
    }
    public function generate_token()
    {
        $payload = [
            'iat' => time(), /* issued at time */
            'iss' => 'eshop',
            'exp' => time() + (30 * 60), /* expires after 1 minute */
        ];
        $token = $this->jwt->encode($payload, JWT_SECRET_KEY);
        print_r(json_encode($token));
    }

    public function verify_token()
    {
        try {
            $token = $this->jwt->getBearerToken();
        } catch (Exception $e) {
            $response['error'] = true;
            $response['message'] = $e->getMessage();
            print_r(json_encode($response));
            return false;
        }

        if (!empty($token)) {

            $api_keys = fetch_details('client_api_keys', ['status' => 1]);
            if (empty($api_keys)) {
                $response['error'] = true;
                $response['message'] = 'No Client(s) Data Found !';
                print_r(json_encode($response));
                return false;
            }
            JWT::$leeway = 6000000000;
            $flag = true; //For payload indication that it return some data or throws an expection.
            $error = true; //It will indicate that the payload had verified the signature and hash is valid or not.
            foreach ($api_keys as $row) {
                $message = '';
                try {
                    // $payload = $this->jwt->decode($token, $row['secret'], ['HS256']);
                    $payload = $this->jwt->decode($token, new Key($row['secret'], 'HS256'));
                    if (isset($payload->iss) && $payload->iss == 'eshop') {
                        $error = false;
                        $flag = false;
                    } else {
                        $error = true;
                        $flag = false;
                        $message = 'Invalid Hash';
                        break;
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                }
            }

            if ($flag) {
                $response['error'] = true;
                $response['message'] = $message;
                print_r(json_encode($response));
                return false;
            } else {
                if ($error == true) {
                    $response['error'] = true;
                    $response['message'] = $message;
                    print_r(json_encode($response));
                    return false;
                } else {
                    return true;
                }
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Unauthorized access not allowed";
            print_r(json_encode($response));
            return false;
        }
    }

    // public function get_groups()
    // {
    //     /*
    //         user_id:15 

    //     */
    //     if (!$this->verify_token()) {
    //         return false;
    //     }

    //     $this->form_validation->set_rules('user_id', 'User Id', 'trim|numeric|required|xss_clean');


    //     if (!$this->form_validation->run()) {
    //         $this->response['error'] = true;
    //         $this->response['message'] = strip_tags(validation_errors());
    //         $this->response['data'] = array();
    //         print_r(json_encode($this->response));
    //         return;
    //     } else {

    //         $groups = $this->chat_model->get_groups($_POST['user_id']);
    //         $i = 0;
    //         foreach ($groups as $grp) {
    //             $groups[$i] = $grp;
    //             $grp['group_members'] = [];
    //             $users = $this->chat_model->get_group_members($grp['group_id']);
    //             $j = 0;
    //             foreach ($users as $row) {
    //                 $users[$j]['image'] = isset($row['image'])  && !empty($row['image']) && $row['image'] != null ? $row['image'] : '';
    //                 $j++;
    //             }
    //             $groups[$i]['group_members'] = $users;
    //             $i++;
    //         }
    //         if (!empty($groups)) {
    //             $this->response['error'] = false;
    //             $this->response['message'] = "Groups retrieved successfully !";
    //             $this->response['data'] = $groups;
    //         } else {
    //             $this->response['error'] = true;
    //             $this->response['message'] = "groups Not Found !";
    //             $this->response['data'] = array();
    //         }
    //     }
    //     print_r(json_encode($this->response));
    // }

    public function get_chat_history()
    {
        /*
            user_id:15   
            limit : 10
            offset : 0
        */
        if (!$this->verify_token()) {
            return false;
        }

        $this->form_validation->set_rules('user_id', 'User Id', 'trim|numeric|required|xss_clean');


        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = strip_tags(validation_errors());
            $this->response['data'] = array();
            print_r(json_encode($this->response));
            return;
        } else {

            $limit = (isset($_POST['limit'])) ? $this->input->post('limit', true) : 10;
            $offset = (isset($_POST['offset'])) ? $this->input->post('offset', true) : 0;
            $user = array();
            $i = 0;
            $type = 'person';
            // print_r($to_id);
            // die;
            $members = $this->chat_model->get_chat_history($_POST['user_id'], $limit, $offset);

            foreach ($members as $row) {
                // print_r($row);
                $to_id = (isset($_POST['user_id'])) ? $this->input->post('user_id', true) : '';
                $from_id = $row['from_id'];
                // $to_id = $row['to_id'];
                // print_r($from_id);
                if (isset($from_id) && !empty($from_id)) {
                    $unread_meg = $this->chat_model->get_unread_msg_count($type, $from_id, $to_id);
                }
                $user[$i] = $row;
                $user[$i]['unread_msg'] = $unread_meg;

                $date = strtotime('now');
                if ($to_id == $row['opponent_user_id']) {
                    $user[$i]['is_online'] = 1;
                } else {
                    if ($row['last_online'] > $date) {
                        $user[$i]['is_online'] = 1;
                    } else {
                        $user[$i]['is_online'] = 0;
                    }
                }
                $i++;
            }




            if (isset($members)) {
                $this->response['error'] = false;
                $this->response['message'] = "chat retrieved successfully !";
                $this->response['data'] = $user;
            } else {
                $this->response['error'] = true;
                $this->response['message'] = "chat Not Found !";
                $this->response['data'] = array();
            }
        }
        print_r(json_encode($this->response));
    }

    public function load_chat()
    {
        /*
            
            from_id : 1 // if type is person then pass user id or pass group id
            to_id : 2 //current user_id
            type : person {person / group}
            offset : 0  
            limit : 10
        */
        if (!$this->verify_token()) {
            return false;
        }

        $this->form_validation->set_rules('from_id', 'From Id', 'trim|numeric|required|xss_clean');
        $this->form_validation->set_rules('to_id', 'To Id', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('type', 'Type', 'trim|xss_clean');


        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = strip_tags(validation_errors());
            $this->response['data'] = array();
            print_r(json_encode($this->response));
            return;
        } else {

            $limit = (isset($_POST['limit'])) ? $this->input->post('limit', true) : 50;
            $offset = (isset($_POST['offset'])) ? $this->input->post('offset', true) : 0;
            $from_id = (isset($_POST['from_id'])) ? $this->input->post('from_id', true) : '';
            $to_id = (isset($_POST['to_id'])) ? $this->input->post('to_id', true) : '';
            $type = (isset($_POST['type'])) ? $this->input->post('type', true) : '';

            $messages = $this->chat_model->load_chat($from_id, $to_id, $type,  $offset, $limit, 'id', "DESC");

            if ($messages['total_msg'] == 0) {

                $message['error'] = false;
                $message['message'] = "message Not Found !";
                $message['data']['total_msg'] = "0";
                $message['data']['msg'] = [];

                print_r(json_encode($message));
                return false;
            }

            $i = 0;
            $message['total_msg'] = $messages['total_msg'];

            //   print_R($messages['msg']);
            foreach ($messages['msg'] as $row) {

                $media_files = [];
                $bulkData = [];
                $message['msg'][$i] = $row;
                $media_files = $this->chat_model->get_media($row['id']);
                if (isset($media_files) && !empty($media_files)) {
                    $file_extention = explode('.', $media_files[0]['original_file_name']);
                    $media_files[0]['file_extension'] = end($file_extention);
                    $media_files[0]['file_url'] = base_url('uploads/chat_media/' . $media_files[0]['original_file_name']);
                }
                // foreach ($media_files as $value) {
                //     $file_extention = explode('.', $value['original_file_name']);
                //     $tempRow['id'] = $value['id'];
                //     $tempRow['message_id'] = $value['message_id'];
                //     $tempRow['user_id'] = $value['user_id'];
                //     $tempRow['original_file_name'] = $value['original_file_name'];
                //     $tempRow['file_name'] = $value['file_name'];
                //     $tempRow['file_url'] = base_url('uploads/chat_media/'.$value['original_file_name']);
                //     $tempRow['file_extension'] = end($file_extention);
                //     $tempRow['file_size'] = $value['file_size'];
                //     $tempRow['date_created'] = $value['date_created'];
                //      $rows[] = $tempRow;
                //  }
                //  $bulkData = $rows;


                $message['msg'][$i]['media_files'] = !empty($media_files) ? $media_files : [];
                $message['msg'][$i]['text'] = $row['message'];
                if ($row['from_id'] == $to_id) {
                    $message['msg'][$i]['position'] = 'right';
                } else {
                    $message['msg'][$i]['position'] = 'left';
                }
                $i++;
            }

            if (!empty($message)) {
                $this->response['error'] = false;
                $this->response['message'] = "message retrieved successfully !";
                $this->response['data'] = $message;
            } else {
                $this->response['error'] = false;
                $this->response['message'] = "message Not Found !";
                $this->response['data']['total_msg'] = "0";
                $this->response['data'] = array();
            }
        }
        print_r(json_encode($this->response));
    }
    public function delete_msg()
    {
        /*
           msg_id : 13
           from_id : 1 // current user_id
            
        */
        if (!$this->verify_token()) {
            return false;
        }

        $this->form_validation->set_rules('msg_id', 'Message Id', 'trim|numeric|required|xss_clean');
        $this->form_validation->set_rules('from_id', 'From Id', 'trim|numeric|required|xss_clean');


        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = strip_tags(validation_errors());
            $this->response['data'] = array();
            print_r(json_encode($this->response));
            return;
        } else {

            $from_id = (isset($_POST['from_id'])) ? $this->input->post('from_id') : '';
            $msg_id = (isset($_POST['msg_id'])) ? $this->input->post('msg_id') : '';

            if ($this->chat_model->delete_msg($from_id, $msg_id)) {
                $this->response['error'] = false;
                $this->response['message'] = "Message deleted successfully !";
            } else {
                $this->response['error'] = true;
                $this->response['message'] = "Message not deleted !";
                $this->response['data'] = array();
            }
        }
        print_r(json_encode($this->response));
    }

    public function switch_chat()
    {
        /*
            from_id : 1 // if type is person then pass user id or pass group id
            type : person {person / group}
            user_id : 1 {current user_id} //pass when type is group
            
        */
        if (!$this->verify_token()) {
            return false;
        }

        $this->form_validation->set_rules('type', 'Type', 'trim|required|xss_clean');
        $this->form_validation->set_rules('from_id', 'From Id', 'trim|numeric|required|xss_clean');
        if (isset($_POST['type']) && strtolower($_POST['type']) == 'group') {
            $this->form_validation->set_rules('user_id', 'User Id', 'trim|numeric|required|xss_clean');
        }


        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = strip_tags(validation_errors());
            $this->response['data'] = array();
            print_r(json_encode($this->response));
            return;
        } else {

            $from_id = (isset($_POST['from_id'])) ? $this->input->post('from_id') : '';
            $type = (isset($_POST['type'])) ? $this->input->post('type') : '';
            $users = $this->chat_model->switch_chat($from_id, $type);
            $user = array();
            $i = 0;
            foreach ($users as $row) {

                $user[$i] = $row;
                if ($type == 'person') {
                    $user[$i]['picture'] = $row['username'];

                    $date = strtotime('now');

                    if ($row['last_online'] > $date) {
                        $user[$i]['is_online'] = 1;
                    } else {
                        $user[$i]['is_online'] = 0;
                    }
                } else {
                    /*
                     * The group branch called $this->chat_model->check_group_admin(), which does
                     * NOT exist - it is commented out in Chat_model along with the rest of the
                     * group-chat code, and the `chat_groups` / `chat_group_members` tables are not
                     * in the schema. `type` is posted by the client, so any value other than
                     * 'person' reached here and died with an uncaught
                     * "Call to undefined method Chat_model::check_group_admin()".
                     * Chat_model::switch_chat() now falls back to the user lookup for any type, so
                     * there is no group membership to report.
                     */
                    $user[$i]['picture'] = '#';
                    $user[$i]['is_admin'] = false;
                }

                $i++;
            }

            if (!empty($user)) {
                $this->response['error'] = false;
                $this->response['message'] = "Data fetched successfully !";
                $this->response['data'] = $user;
            } else {
                $this->response['error'] = true;
                $this->response['message'] = "Data not available !";
                $this->response['data'] = array();
            }
        }
        print_r(json_encode($this->response));
    }

    public function mark_msg_read()
    {
        /*
            from_id : 1 // if type is person then pass user id or pass group id
            type : person {person / group}
            user_id : 1 {current user_id} //pass when type is group
            
        */
        if (!$this->verify_token()) {
            return false;
        }

        $this->form_validation->set_rules('type', 'Type', 'trim|required|xss_clean');
        $this->form_validation->set_rules('from_id', 'From Id', 'trim|numeric|required|xss_clean');
        // user_id identifies WHOSE copy of the conversation is being marked read, so it is
        // required for every type - not just 'group'. Without it $to_id below was '', and the
        // model built "... AND to_id=" - a SQL syntax error (1064) on a call the app was told
        // had succeeded. It now fails validation with a clear message instead.
        $this->form_validation->set_rules('user_id', 'User Id', 'trim|numeric|required|xss_clean');

        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = strip_tags(validation_errors());
            $this->response['data'] = array();
            print_r(json_encode($this->response));
            return;
        } else {

            $from_id = (isset($_POST['from_id'])) ? $this->input->post('from_id') : '';
            $to_id = (isset($_POST['user_id'])) ? $this->input->post('user_id') : '';
            $type = (isset($_POST['type'])) ? $this->input->post('type') : '';

            if ($this->chat_model->mark_msg_read($type, $from_id, $to_id)) {
                $this->response['error'] = false;
                $this->response['message'] = "Message marked as read !";
            } else {
                $this->response['error'] = true;
                $this->response['message'] = "Message not marked as read !";
                $this->response['data'] = array();
            }
        }
        print_r(json_encode($this->response));
    }

    public function send_msg()
    {

        /*
            type : person {person / group}
            from_id : 1 // current user id
            to_id : 1 // receiver user id
              : this is test msg
            documents[] : FILE {optional}
            
        */
        if (!$this->verify_token()) {
            return false;
        }

        $this->form_validation->set_rules('type', 'Type', 'trim|required|xss_clean');
        $this->form_validation->set_rules('from_id', 'From Id', 'trim|numeric|required|xss_clean');
        $this->form_validation->set_rules('to_id', 'To Id', 'trim|numeric|required|xss_clean');
        $this->form_validation->set_rules('message', 'Message', 'trim|xss_clean');
        $this->form_validation->set_rules('message', 'Message', 'trim|xss_clean');
        $this->form_validation->set_rules('documents', 'documents', 'trim|xss_clean');


        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = strip_tags(validation_errors());
            $this->response['data'] = array();
            print_r(json_encode($this->response));
            return;
        } else {

            $type = (isset($_POST['type'])) ? $this->input->post('type') : '';
            $from_id = (isset($_POST['from_id'])) ? $this->input->post('from_id') : '';
            $to_id = (isset($_POST['to_id'])) ? $this->input->post('to_id') : '';
            $message = (isset($_POST['message'])) ? $this->input->post('message') : '';

            $data = array(
                'type' => $type,
                'from_id' => $from_id,
                'to_id' => $to_id,
                'message' => $message
            );
            $msg_id = $this->chat_model->send_msg($data);

            if (!empty($_FILES['documents']['name'])) {

                $year = date('Y');
                // CHAT_MEDIA_PATH already ends in '/', so appending another produced
                // 'uploads/chat_media//' - stored verbatim in media.sub_directory and in every
                // URL built from it. Harmless to the filesystem, but it means the stored path no
                // longer matches CHAT_MEDIA_PATH, which Chat_model::delete_msg() now relies on.
                $target_path = FCPATH . CHAT_MEDIA_PATH;
                $sub_directory = CHAT_MEDIA_PATH;

                if (!file_exists($target_path)) {
                    mkdir($target_path, 0777, true);
                }

                $temp_array = $media_ids = $other_images_new_name = array();
                $files = $_FILES;
                $other_image_info_error = "";
                $allowed_media_types = implode('|', allowed_media_types());
                $config['upload_path'] = $target_path;
                $config['allowed_types'] = $allowed_media_types;
                // No size cap was set (CI defaults max_size to 0 = unlimited) on an endpoint that
                // accepts every type the media library allows, from any logged-in user.
                $config['max_size'] = 20480;
                $other_image_cnt = count((array)$_FILES['documents']['name']);
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

                            // Three bugs in the row this used to write, all of which made the
                            // attachment unreachable:
                            //   - file_name stored $_FILES['temp_image']['tmp_name'], i.e. PHP's
                            //     temporary path (C:\...\phpXXXX.tmp), which no longer exists by
                            //     the time anyone reads the row. The name the file was actually
                            //     saved under ($temp_array['file_name']) was thrown away, so
                            //     nothing could build a URL to it - and Chat_model::delete_msg(),
                            //     which unlinks by this column, could never find the real file.
                            //   - file_extension stored the browser-supplied MIME type, not an
                            //     extension.
                            //   - the insert sat OUTSIDE this else, so a FAILED upload still
                            //     created a chat_media row pointing at nothing.
                            $data = array(
                                'original_file_name' => $_FILES['temp_image']['name'],
                                'file_name' => $temp_array['file_name'],
                                'file_extension' => ltrim($temp_array['file_ext'], '.'),
                                'file_size' => $_FILES['temp_image']['size'],
                                'user_id' => $from_id,
                                'message_id' => $msg_id
                            );
                            $file_id = $this->chat_model->add_file($data);
                            $this->chat_model->add_media_ids_to_msg($msg_id, $file_id);
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
                        // No chat_media row here: this branch is reached only when the
                        // slot's filename is EMPTY, i.e. nothing was uploaded. It used to insert
                        // a row anyway, attaching a phantom file to the message.
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
            }


            $messages = $this->chat_model->get_msg_by_id($msg_id, $to_id, $from_id, $type);
            $message = array();
            $i = 0;
            // print_r($messages == 1);
            // die;    
            if ($messages == 1) {
                $response['error'] = true;
                $response['message'] = 'User Not Found';
            } else {
                foreach ($messages as $row) {
                    $message[$i] = $row;
                    $media_files = $this->chat_model->get_media($row['id']);
                    if (isset($media_files) && !empty($media_files)) {
                        $file_extention = explode('.', $media_files[0]['original_file_name']);
                        $media_files[0]['file_extension'] = end($file_extention);
                        $media_files[0]['file_url'] = base_url('uploads/chat_media/' . $media_files[0]['original_file_name']);
                    }
                    $message[$i]['media_files'] = !empty($media_files) ? $media_files : [];
                    $message[$i]['text'] = $row['message'];
                    $i++;
                }
                $new_msg = $message;

                if (!empty($msg_id)) {

                    $to_id = $to_id;
                    $from_id = $from_id;

                    // if ($to_id == $from_id && $this->input->post('chat_type') == 'person') {
                    //     return false;
                    // }

                    // single user msg
                    if ($type == 'person') {

                        // this is the user who going to recive FCM msg
                        // $user = $this->users_model->get_user_by_id($to_id);
                        $user = fetch_details('users', ['active' => 1, 'id' => $to_id]);


                        // this is the user who going to send FCM msg 
                        // $senders_info = $this->users_model->get_user_by_id($this->session->userdata('user_id'));
                        $senders_info = fetch_details('users', ['active' => 1, 'id' => $from_id]);

                        $data = $notification = array();
                        $notification['title'] = $senders_info[0]['username'];
                        // $notification['picture'] = mb_substr($senders_info[0]['first_name'], 0, 1) . '' . mb_substr($senders_info[0]['last_name'], 0, 1);

                        // $notification['profile'] = !empty($senders_info[0]['profile']) ? $senders_info[0]['profile'] : '';

                        $notification['senders_name'] = $senders_info[0]['username'];

                        $notification['type'] = 'message';
                        $notification['message_type'] = 'person';
                        $notification['from_id'] = $from_id;
                        $notification['to_id'] = $to_id;
                        $notification['msg_id'] = $msg_id;
                        $notification['new_msg'] = json_encode($new_msg);
                        $notification['body'] = $this->input->post('chat-input-textarea');
                        // $notification['icon'] = 'assets/icons/' . (!empty(get_half_logo()) ? get_half_logo() : 'logo-half.png');
                        $notification['base_url'] = base_url('chat');
                        $data['data']['data'] = $notification;
                        $data['data']['webpush']['fcm_options']['link'] = base_url('chat');

                        $data['to'] = isset($user[0]['fcm_id']) && !empty($user[0]['fcm_id']) ? $user[0]['fcm_id'] : '';

                        //send notification in app
                        $results = fetch_details('users', null, 'fcm_id', 10000, 0, '', '', "id", $to_id);
                        $result = $res = array();
                        for ($i = 0; $i <= count($results); $i++) {
                            if (isset($results[$i]['fcm_id']) && !empty($results[$i]['fcm_id']) && ($results[$i]['fcm_id'] != 'NULL')) {
                                $res = array_merge($result, $results);
                            }
                        }

                        $fcm_ids = array();
                        foreach ($res as $fcm_id) {
                            if (!empty($fcm_id)) {
                                $fcm_ids[][] = $fcm_id['fcm_id'];
                            }
                        }

                        $registrationIDs = $fcm_ids;
                        $fcm_admin_subject = 'New Message from ' . $senders_info[0]['username'];
                        $fcmMsg = array(
                            'title' => $fcm_admin_subject,
                            'body' => $this->input->post('message'),
                            'type' => "chat",
                            'message' => ($new_msg),
                            'content_available' => true
                        );
                        $registrationIDs_chunks = array_chunk($registrationIDs, 1000);
                        $fcmFields = send_notification($fcmMsg, $registrationIDs);

                        $ch = curl_init();
                        $fcm_key = get_settings('fcm_server_key');


                        $fcm_key = !empty($fcm_key) ? $fcm_key : '';

                        // $fcm_key = !empty($fcm_key->fcm_server_key) ? $fcm_key->fcm_server_key : '';

                        curl_setopt($ch, CURLOPT_POST, 1);
                        $headers = array();
                        $headers[] = "Authorization: key = " . $fcm_key;
                        $headers[] = "Content-Type: application/json";
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                        curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/fcm/send");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

                        $result['error'] = false;
                        $result['response'] = curl_exec($ch);
                        if (curl_errno($ch))
                            echo 'Error:' . curl_error($ch);

                        curl_close($ch);
                    } else {
                        /*
                         * The "group chat" delivery path used to live here and was a guaranteed
                         * fatal error for anybody who reached it:
                         *
                         *   PHP Error - Call to undefined method Chat_model::get_group_members()
                         *
                         * Group chat was removed from this product - `chat_groups` /
                         * `chat_group_members` are not in the schema, and both model methods this
                         * branch called (get_group_members(), set_group_msg_as_unread()) are
                         * commented out in Chat_model. `type` is supplied by the client, so any
                         * value other than 'person' landed here. This was latent only because the
                         * whole chat API answered 403 on every request (it was missing from
                         * csrf_exclude_uris); with that fixed, this branch became reachable.
                         *
                         * Chat_model::send_msg() normalises the type, so the message row itself is
                         * stored correctly as a person message - there is simply no group fan-out
                         * left to perform.
                         */
                        log_message('debug', 'chat send_msg: ignoring unsupported type "' . $type . '" - group chat is not part of this schema.');
                    }

                    $response['error'] = false;
                    $response['message'] = 'Successful';
                    $response['msg_id'] = $msg_id;
                    $response['new_msg'] = $new_msg;
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Not Successful';
                }
            }
        }
        print_r(json_encode($response));
    }

    public function search_user()
    {
        /*
         
          search : test
          limit : 10
          offset : 10
          order : DESC/ASC
          sort : id
        */
        if (!$this->verify_token()) {
            return false;
        }

        $this->form_validation->set_rules('search', 'Search keyword', 'trim|xss_clean');
        $this->form_validation->set_rules('sort', 'sort', 'trim|xss_clean');
        $this->form_validation->set_rules('limit', 'limit', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('offset', 'offset', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('order', 'order', 'trim|xss_clean');


        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = strip_tags(validation_errors());
            $this->response['data'] = array();
            print_r(json_encode($this->response));
            return;
        } else {
            $search = (isset($_POST['search']) && !empty(trim($_POST['search']))) ? $this->input->post('search', true) : "";
            $limit = (isset($_POST['limit']) && is_numeric($_POST['limit']) && !empty(trim($_POST['limit']))) ? $this->input->post('limit', true) : 25;
            $offset = (isset($_POST['offset']) && is_numeric($_POST['offset']) && !empty(trim($_POST['offset']))) ? $this->input->post('offset', true) : 0;
            $order = (isset($_POST['order']) && !empty(trim($_POST['order']))) ? $_POST['order'] : 'DESC';
            $sort = (isset($_POST['sort']) && !empty(trim($_POST['sort']))) ? $_POST['sort'] : 'u.id';
            if (isset($search) and $search != '') {
                $multipleWhere = ['u.`username`' => $search, 'u.`email`' => $search, 'sd.`store_name`' => $search];
            }
            $where = ['u.active' => 1];

            $search_res = $this->db->select('u.id,u.username,u.email,COALESCE(NULLIF(sd.shop_name, ""), sd.store_name) as store_name,u.image')->join('users_groups ug', ' ug.user_id = u.id ')->join('seller_data sd', ' sd.user_id = u.id ');

            // $res = [

            //     ]''


            if (isset($multipleWhere) && !empty($multipleWhere)) {
                $search_res->group_start();
                $search_res->or_like($multipleWhere);
                $search_res->group_end();
            }
            if (isset($where) && !empty($where)) {

                $search_res->group_start();
                $search_res->where('ug.group_id', 4);
                $search_res->or_where('ug.group_id', 1);
                $search_res->group_end();
            }
            $res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('users u')->result_array();

            if (!empty($res)) {

                foreach ($res as $row) {

                    $tempRow['id'] = $row['id'];
                    $tempRow['username'] = $row['username'];
                    $tempRow['username'] = $row['username'];
                    $tempRow['email'] = isset($row['email']) && !empty($row['email']) ? $row['email'] : "";
                    $tempRow['store_name'] = isset($row['store_name']) && !empty($row['store_name']) ? $row['store_name'] : "";
                    $tempRow['image'] = isset($row['image']) && !empty($row['image']) ? base_url(USER_IMG_PATH . '/' . $row['image']) : "";
                    $rows[] = $tempRow;
                }
                $bulkData['rows'] = $rows;
            }

            if (!empty($res)) {
                $this->response['error'] = false;
                $this->response['message'] = "Data fetched successfully !";
                $this->response['data'] = $bulkData['rows'];
            } else {
                $this->response['error'] = true;
                $this->response['message'] = "Data not available !";
                $this->response['data'] = array();
            }
        }
        print_r(json_encode($this->response));
    }

    public function get_supporters()
    {
        if (!$this->verify_token()) {
            return false;
        }

        // Optional: lets the app show a per-supporter unread badge. Chat_model::get_supporters()
        // computes it against this viewer.
        $viewer_id = (isset($_POST['user_id']) && is_numeric($_POST['user_id'])) ? (int) $this->input->post('user_id', true) : 0;

        $data = $this->chat_model->get_supporters($viewer_id);
        $items = [];
        $res = [];
        foreach ($data as $key => $value) {

            $items['user_permission_id'] = isset($value['user_permission_id']) ? $value['user_permission_id'] : "";
            // Was `!empty($value['user_role'])`, and Super Admin IS role 0 - a falsy value - so
            // every super-admin supporter was reported with an EMPTY role instead of "0".
            $items['user_role'] = isset($value['user_role']) ? (string) $value['user_role'] : "";
            $items['userto_id'] = isset($value['userto_id']) ? $value['userto_id'] : "";
            $items['username'] = isset($value['username']) ? $value['username'] : "";
            $items['image'] = isset($value['image']) ? $value['image'] : "";
            $items['last_online'] = isset($value['last_online']) ? $value['last_online'] : "";
            $items['unread_msg'] = isset($value['unread_msg']) ? (int) $value['unread_msg'] : 0;
            /*
             * These eight keys are kept for wire compatibility with the app, but they were never
             * meaningful: they came from a `LEFT JOIN messages ... GROUP BY u.id` in the old
             * get_supporters() query that selected `m.*`, so they held the columns of ONE
             * arbitrary message row (and collided with u.id). That join was invalid under
             * ONLY_FULL_GROUP_BY anyway and has been removed; the real unread figure is
             * `unread_msg` above.
             */
            $items['id'] = "";
            $items['from_id'] = "";
            $items['to_id'] = "";
            $items['is_read'] = "";
            $items['message'] = "";
            $items['type'] = "";
            $items['media'] = "";
            $items['date_created'] = "";

            array_push($res, $items);
        }
        if (!empty($res)) {
            $this->response['error'] = false;
            $this->response['message'] = "Data fetched successfully !";
            $this->response['data'] = $res;
        } else {
            $this->response['error'] = true;
            $this->response['message'] = "Data not available !";
            $this->response['data'] = array();
        }
        print_r(json_encode($this->response));
    }
}
