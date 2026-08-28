<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Chat extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'upload']);
        $this->load->helper(['url', 'language', 'file']);
        $this->load->model(['Customer_model', 'chat_model', 'notification_model', 'Setting_model', 'media_model']);

        if (!has_permissions('read', 'chat')) {
            $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
            redirect('admin/home', 'refresh');
        }
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // Was also gating this page on has_permissions('read','notification_setting') - an
            // unrelated module, almost certainly copy-pasted from elsewhere. The constructor
            // already checks the correct module ('chat') for every method in this controller;
            // this second check meant an admin who could see chat but had no notification-
            // settings access at all was redirected out with a generic "not authorized" message.
            $this->data['main_page'] = VIEW . 'chat';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Chat | ' . $settings['app_name'];
            $this->data['meta_description'] = ' chat  | ' . $settings['app_name'];
            $this->data['whatsapp_status'] = !empty($settings['whatsapp_status']) ? $settings['whatsapp_status'] : 0;
            $this->data['whatsapp_number'] = !empty($settings['whatsapp_number']) ? $settings['whatsapp_number'] : '';
            $this->data['fcm_server_key'] = get_settings('fcm_server_key');
            // $users = fetch_details('users', ['active' => 1]);
            $users = $this->chat_model->get_chat_history($_SESSION['user_id'], 10, 0);

            $user = array();
            $i = 0;
            $type = 'person';
            $to_id = $this->session->userdata('user_id');

            foreach ($users as $row) {
                $from_id = $row['opponent_user_id'];

                // Was declared outside the loop (and only assigned when $from_id was non-empty),
                // so a conversation row with an empty opponent id silently reused whatever
                // unread count the PREVIOUS row happened to compute, instead of showing 0.
                $unread_meg = 0;
                if (isset($from_id) && !empty($from_id)) {
                    $unread_meg = $this->chat_model->get_unread_msg_count($type, $from_id, $to_id);
                }

                $user[$i] = $row;
                $user[$i]['unread_msg'] = $unread_meg;
                $user[$i]['picture']  = $row['opponent_username'];

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
            // if ($this->ion_auth->is_admin()) {
            //     $this->data['not_in_groups'] = $this->chat_model->get_groups_all($to_id);
            // } else {
            //     $this->data['not_in_groups'] = '';
            // }

            $this->data['supporters'] = $this->chat_model->get_supporters($to_id);
            $this->data['users'] = $user;
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function make_me_online()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {

            $user_id = $this->session->userdata('user_id');
            $date = strtotime('now');
            $date = $date + 60;
            $data = array(
                'last_online' => $date
            );

            if ($this->chat_model->make_me_online($user_id, $data)) {

                $response['error'] = false;
                $response['message'] = 'Successful';
                echo json_encode($response);
            } else {
                $response['error'] = true;
                $response['message'] = 'Not Successful';
                echo json_encode($response);
            }
        }
    }
    public function get_system_settings()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {
            $response = get_settings('firebase_settings');
            $configData = json_decode($response, true);
            $configData['fcm_server_key'] = get_settings('fcm_server_key');
            $configData['vap_id_Key'] = get_settings('vap_id_Key');

            $data = json_encode($configData);

            // print_r($data);
            // print_r($configData);
            // die;
            echo json_encode($data);
        }
    }

    public function get_online_members()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {
            $user_id = $this->session->userdata('user_id');

            $date = strtotime('now');
            $date = $date + 15;
            $data = array(
                'last_online' => $date
            );

            $this->chat_model->make_me_online($user_id, $data);

            $users = $this->chat_model->get_chat_history($user_id, 20, 0);

            /*
             * This used to read:
             *     $user_ids = explode(',', $users[0]['id']);
             * `$users[0]['id']` is a single integer - the id of the newest MESSAGE in the first
             * conversation, not a comma-separated id list - so exploding it produced a
             * one-element array holding a message id, which was then looked up in `users`.
             * Result: the online-members panel listed at most one user, and that user was
             * whoever happened to own the id matching a message id rather than one of the
             * viewer's actual contacts. It also raised "Undefined array key 0" outright whenever
             * the viewer had no conversations yet.
             *
             * The conversation rows already carry the correct contact id as `opponent_user_id`.
             */
            $user_ids = array();
            foreach ($users as $conversation) {
                if (!empty($conversation['opponent_user_id'])) {
                    $user_ids[] = (int) $conversation['opponent_user_id'];
                }
            }
            $user_ids = array_values(array_unique($user_ids));


            $member = array();
            $i = 0;

            $type = 'person';
            $to_id = $this->session->userdata('user_id');

            $members = !empty($user_ids) ? $this->chat_model->get_members($user_ids) : array();

            foreach ($members as $row) {

                // Was iterating $users (conversation rows) and reading $row['id'] - the newest
                // MESSAGE id - as if it were the contact's user id, so every unread badge was
                // counted against an unrelated user, and the panel's `image` / `last_online`
                // came from the conversation row rather than the contact's own user record.
                $from_id = (int) $row['id'];

                $unread_meg = ($from_id > 0) ? $this->chat_model->get_unread_msg_count($type, $from_id, $to_id) : 0;

                $member[$i] = $row;
                $member[$i]['unread_msg'] = $unread_meg;
                $member[$i]['picture']  = isset($row['image']) ? $row['image'] : '';
                $date = strtotime('now');

                if ($row['last_online'] > $date) {
                    $member[$i]['is_online'] = 1;
                } else {
                    $member[$i]['is_online'] = 0;
                }
                $i++;
            }

            // $data1['groups'] = $this->chat_model->get_groups($to_id);
            $data1['members'] = $member;

            if (!empty($member)) {
                $response['error'] = false;
                $response['data'] = $data1;
                echo json_encode($response);
            } else {
                $response['error'] = true;
                $response['message'] = 'Not Successful';
                echo json_encode($response);
            }
        }
    }

    // public function edit_group()
    // {
    //     if (!$this->ion_auth->logged_in()) {
    //         redirect('auth', 'refresh');
    //     }
    //     $user_id = $this->session->userdata('user_id');


    //     $this->form_validation->set_rules('update_id', str_replace(':', '', 'ID is empty.'), 'trim');
    //     $this->form_validation->set_rules('title', str_replace(':', '', 'Title is empty.'), 'trim|required');
    //     $this->form_validation->set_rules('description', str_replace(':', '', 'description is empty.'), 'trim|required');

    //     if ($this->form_validation->run() === TRUE) {

    //         $admin_id = $this->session->userdata('user_id');
    //         $group_id = $this->input->post('update_id');

    //         if (!empty($this->input->post('users'))) {
    //             $group_mem_ids = implode(",", $this->input->post('users')) . ',' . $admin_id;
    //             $group_mem_ids = explode(",", $group_mem_ids);
    //         } else {
    //             $group_mem_ids = array($this->session->userdata('user_id'));
    //         }

    //         $no_of_mem = count($group_mem_ids);

    //         if (!empty($this->input->post('admins'))) {
    //             $admins_ids = implode(",", $this->input->post('admins')) . ',' . $admin_id;
    //             $admins_ids = explode(",", $admins_ids);
    //         } else {
    //             $admins_ids = array($this->session->userdata('user_id'));
    //         }

    //         $data = array(
    //             'title' => strip_tags($this->input->post('title', true)),
    //             'description' => strip_tags($this->input->post('description', true)),
    //             'no_of_members' => $no_of_mem
    //         );

    //         if ($this->chat_model->edit_group($data, $group_id)) {

    //             foreach ($group_mem_ids as $user_id) {
    //                 $data1 = array(
    //                     'group_id' => $group_id,
    //                     'user_id' => $user_id,
    //                 );

    //                 $this->chat_model->add_group_members($data1);
    //             }

    //             $this->chat_model->remove_all_group_members($group_id, $group_mem_ids);

    //             $this->chat_model->make_group_admin($group_id, $admins_ids);

    //             $response['error'] = false;
    //             $response['message'] = 'Group Edited successfully';


    //         } else {
    //             $response['error'] = true;
    //             $response['message'] = 'Group could not Edited! Try again!';
    //         }

    //      return json_encode($response);
    //     } else {
    //         $response['error'] = true;
    //         $response['message'] = validation_errors();
    //         print_r(json_encode($response));
    //     }
    // }

    // public function create_group()
    // {

    //     if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {


    //         $user_id = $this->session->userdata('user_id');

    //         $this->form_validation->set_rules('title', 'Titel', 'trim|required|xss_clean');
    //         $this->form_validation->set_rules('description', 'Description', 'trim|required|xss_clean');
    //         if (!$this->form_validation->run()) {

    //             $this->response['error'] = true;
    //             $this->response['csrfName'] = $this->security->get_csrf_token_name();
    //             $this->response['csrfHash'] = $this->security->get_csrf_hash();
    //             $this->response['message'] = validation_errors();
    //             print_r(json_encode($this->response));
    //         } else {
    //             $admin_id = $this->session->userdata('user_id');

    //             if (!empty($this->input->post('users'))) {
    //                 $group_mem_ids = implode(",", $this->input->post('users')) . ',' . $admin_id;
    //                 $group_mem_ids = explode(",", $group_mem_ids);
    //             } else {
    //                 $group_mem_ids = array($this->session->userdata('user_id'));
    //             }


    //             $no_of_mem = count($group_mem_ids);

    //             $data = array(
    //                 'title' => strip_tags($this->input->post('title', true)),
    //                 'description' => strip_tags($this->input->post('description', true)),
    //                 'created_by' => $this->session->userdata('user_id'),
    //                 'no_of_members' => $no_of_mem
    //             );

    //             $group_id = $this->chat_model->create_group($data);

    //             if ($group_id != false) {

    //                 foreach ($group_mem_ids as $user_id) {
    //                     $data1 = array(
    //                         'group_id' => $group_id,
    //                         'user_id' => $user_id,
    //                     );
    //                     $this->chat_model->add_group_members($data1);
    //                 }
    //                 $admins_ids = array($admin_id);
    //                 $this->chat_model->make_group_admin($group_id, $admins_ids);

    //                 $this->session->set_flashdata('message', 'Group Created successfully.');
    //                 $this->session->set_flashdata('message_type', 'success');
    //             } else {
    //                 $this->session->set_flashdata('message', 'Group could not Created! Try again!');
    //                 $this->session->set_flashdata('message_type', 'error');
    //             }

    //             $response['error'] = false;
    //             $response['message'] = 'Successful';
    //             print_r(json_encode($response));
    //         }
    //     } else {
    //         redirect('admin/login', 'refresh');
    //     }
    // }

    public function update_web_fcm()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {
            $fcm = $this->input->post('web_fcm');
            $user_id = $this->session->userdata('user_id');
            if ($this->chat_model->update_web_fcm($user_id, $fcm)) {

                $response['error'] = false;
                $response['message'] = 'Successful';
                echo json_encode($response);
            } else {
                $response['error'] = true;
                $response['message'] = 'Not Successful';
                echo json_encode($response);
            }
        }
    }

    // public function get_group_members()
    // {
    //     if (!$this->ion_auth->logged_in()) {
    //         redirect('auth', 'refresh');
    //     } else {
    //         $group_id = $this->input->post('group_id');
    //         $users = $this->chat_model->get_group_members($group_id);
    //         if (!empty($users)) {

    //             $response['error'] = false;
    //             $response['data'] = $users;
    //             echo json_encode($response);
    //         } else {
    //             $response['error'] = true;
    //             $response['message'] = 'Not Successful';
    //             echo json_encode($response);
    //         }
    //     }
    // }

    public function send_msg()
    {

        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {
            $user_id = $this->session->userdata('user_id');

            $data = array(
                'type' => $this->input->post('chat_type'),
                'from_id' => $this->session->userdata('user_id'),
                'to_id' => $this->input->post('opposite_user_id'),
                'message' => $this->input->post('chat-input-textarea')
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
                                'user_id' => $this->session->userdata('user_id'),
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


            $messages = $this->chat_model->get_msg_by_id($msg_id, $this->input->post('opposite_user_id'), $this->session->userdata('user_id'), $this->input->post('chat_type'));
            $message = array();
            $i = 0;
            foreach ($messages as $row) {
                $message[$i] = $row;
                $media_files = $this->chat_model->get_media($row['id']);
                $message[$i]['media_files'] = !empty($media_files) ? $media_files : '';
                $message[$i]['text'] = $row['message'];
                $i++;
            }
            $new_msg = $message;

            if (!empty($msg_id)) {

                $to_id = $this->input->post('opposite_user_id');
                $from_id = $this->session->userdata('user_id');

                // if ($to_id == $from_id && $this->input->post('chat_type') == 'person') {
                //     return false;
                // }

                // single user msg
                // if (($this->input->post('chat_type') == 'person') || ($this->input->post('chat_type') == 'supporter')) {
                if (($this->input->post('chat_type') == 'person')) {

                    // this is the user who going to recive FCM msg
                    // $user = $this->users_model->get_user_by_id($to_id);
                    $user = fetch_details('users', ['active' => 1, 'id' => $to_id]);

                    // this is the user who going to send FCM msg 
                    // $senders_info = $this->users_model->get_user_by_id($this->session->userdata('user_id'));
                    $senders_info = fetch_details('users', ['active' => 1, 'id' => $this->session->userdata('user_id')]);

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
                    $data['to'] = isset($user[0]['web_fcm']) ? $user[0]['web_fcm'] : '';



                    //send notification in app

                    $results = fetch_details('users', null, 'fcm_id', 10000, 0, '', '', "id", $this->input->post('opposite_user_id'));
                    $result = $res = array();
                    for ($i = 0; $i <= count($results); $i++) {
                        if (isset($results[$i]['fcm_id']) && !empty($results[$i]['fcm_id']) && ($results[$i]['fcm_id'] != 'NULL')) {
                            $res = array_merge($result, $results);
                        }
                    }

                    $fcm_ids = array();
                    foreach ($res as $fcm_id) {
                        if (!empty($fcm_id)) {
                            $fcm_ids[] = $fcm_id['fcm_id'];
                        }
                    }
                    $registrationIDs = $fcm_ids;
                    $fcmMsg = array(
                        'content_available' => true,
                        'title' => 'New Message from Admin',
                        'body' => $this->input->post('chat-input-textarea'),
                        'type' => "chat",
                        'message' => json_encode($new_msg),
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    );

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
                     * The block that used to live here was the "group chat" delivery path, and it
                     * was a guaranteed fatal error for anyone who reached it:
                     *
                     *   PHP Error - Call to undefined method Chat_model::get_group_members()
                     *
                     * Group chat was removed from this product - `chat_groups` /
                     * `chat_group_members` are not in the schema and both model methods this
                     * branch called (get_group_members(), set_group_msg_as_unread()) are
                     * commented out in Chat_model. But `chat_type` is posted by the client, so
                     * ANY value other than 'person'/'supporter' landed here: a one-line request
                     * with chat_type=group crashed the endpoint with an uncaught Error (verified
                     * live). Chat_model::send_msg() now normalises the type, so the message row
                     * itself is written correctly as a person message; there is simply no group
                     * fan-out left to do.
                     */
                    log_message('debug', 'send_msg: ignoring unsupported chat_type "' . $this->input->post('chat_type') . '" - group chat is not part of this schema.');
                }

                $response['error'] = false;
                $response['message'] = 'Successful';
                $response['msg_id'] = $msg_id;
                $response['new_msg'] = $new_msg;

                echo json_encode($response);
            } else {
                $response['error'] = true;
                $response['message'] = 'Not Successful';
                echo json_encode($response);
            }
        }
    }

    public function mark_msg_read()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {

            $type = $this->input->post('type');
            $to_id = $this->session->userdata('user_id');
            $from_id = $this->input->post('from_id');
            if ($this->chat_model->mark_msg_read($type, $from_id, $to_id)) {
                $response['error'] = false;
                $response['message'] = 'Successful';
                echo json_encode($response);
            } else {
                $response['error'] = true;
                $response['message'] = 'Not Successful';
                echo json_encode($response);
            }
        }
    }

    // public function delete_group()
    // {
    //     if (!$this->ion_auth->logged_in()) {
    //         redirect('auth', 'refresh');
    //     } else {

    //         $user_id = $this->session->userdata('user_id');
    //         $group_id = $this->input->post('grp_id');


    //         if ($this->chat_model->delete_group($group_id, $user_id)) {
    //             $response['error'] = false;
    //             $response['message'] = 'Successful';
    //             echo json_encode($response);
    //         } else {
    //             $response['error'] = true;
    //             $response['message'] = 'Not Successful';
    //             echo json_encode($response);
    //         }
    //     }
    // }

    public function delete_msg()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {

            $workspace_id = $this->session->userdata('workspace_id');
            $from_id = $this->session->userdata('user_id');
            $msg_id = $this->uri->segment(4);

            if (empty($msg_id) || !is_numeric($msg_id) || $msg_id < 1) {
                redirect('chat', 'refresh');
                return false;
                exit(0);
            }

            if ($this->chat_model->delete_msg($from_id, $msg_id)) {
                $response['error'] = false;
                $response['message'] = 'Successful';
                echo json_encode($response);
            } else {
                $response['error'] = true;
                $response['message'] = 'Not Successful';
                echo json_encode($response);
            }
        }
    }

    public function load_chat()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {
            $user_id = $this->session->userdata('user_id');

            $type = $this->input->post('type');
            $to_id = $this->session->userdata('user_id');
            $from_id = $this->input->post('from_id');

            $offset = (!empty($_POST['offset'])) ? $this->input->post('offset') : 0;
            $limit = (!empty($_POST['limit'])) ? $this->input->post('limit') : 100;

            $sort = (!empty($_POST['sort'])) ? $this->input->post('sort') : 'id';
            $order = (!empty($_POST['order'])) ? $this->input->post('order') : 'DESC';

            $search = (!empty($_POST['search'])) ? $this->input->post('search') : '';

            $message = array();

            $messages = $this->chat_model->load_chat($from_id, $to_id, $type,  $offset, $limit, $sort, $order, $search);
            // print_r($from_id);
            // print_r($to_id);
            // print_r($user_id);
            // print_r($offset);
            // print_r($limit);
            // print_r($sort);
            // print_r($order);
            // print_r($search);
            // print_r($messages);
            if ($messages['total_msg'] == 0) {

                $message['error'] = true;
                $message['error_msg'] = 'No Chat OR Msg Found';
                print_r(json_encode($message));
                return false;
            }

            $i = 0;
            $message['total_msg'] = $messages['total_msg'];
            foreach ($messages['msg'] as $row) {
                $message['msg'][$i] = $row;
                $media_files = $this->chat_model->get_media($row['id']);
                $message['msg'][$i]['media_files'] = !empty($media_files) ? $media_files : '';
                $message['msg'][$i]['text'] = $row['message'];
                if ($row['from_id'] == $to_id) {
                    $message['msg'][$i]['position'] = 'right';
                } else {
                    $message['msg'][$i]['position'] = 'left';
                }
                $i++;
            }
            print_r(json_encode($message));
        }
    }

    public function switch_chat()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {
            $type = $this->input->post('type');
            $id = $this->input->post('from_id');
            $users = $this->chat_model->switch_chat($id, $type);
            // $grp_members = $this->chat_model->get_group_members($id);
            // print_R($users);
            // die;

            $user = array();
            $i = 0;
            foreach ($users as $row) {

                $user[$i] = $row;
                if (($type == 'person') || ($type == 'supporter')) {
                    $user[$i]['picture'] = $row['username'];

                    $date = strtotime('now');

                    if ($row['last_online'] > $date) {
                        $user[$i]['is_online'] = 1;
                    } else {
                        $user[$i]['is_online'] = 0;
                    }
                }

                $i++;
            }
            // $user['grp_members'] = $grp_members;

            print_r(json_encode($user));
        }
    }

    public function send_fcm()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {

            $to_id = $this->input->post('receiver_id');
            $from_id = $this->session->userdata('user_id');

            if ($to_id == $from_id) {
                return false;
            }

            $title = $this->input->post('title');
            $type = $this->input->post('type');
            $msg = $this->input->post('msg');
            // $user = $this->users_model->get_user_by_id($to_id);
            $user = fetch_details('users', ['active' => 1, 'id' => $to_id]);

            $message_type = !empty($this->input->post('message_type')) ? $this->input->post('message_type') : 'other';

            $data = $notification = array();
            $fcmFields = [];

            $fcmMsg = array(
                'content_available' => true,
                'title' => 'test',
                'body' => $msg,
                'type' => $type,
                "from_id" => $from_id,
                "to_id" => $to_id,
                "chat_type" => "person"
            );

            $fcmFields = array(
                'registration_ids' => [$user[0]['web_fcm']],  // expects an array of ids
                'priority' => 'high',
                'notification' => $fcmMsg,
                'data' => $fcmMsg,
            );
            // print_r($fcmMsg);
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
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmFields));
            $result = curl_exec($ch);
            curl_close($ch);

            // Was echoing $result (FCM's raw API response) and then $fcmFields - the latter
            // contains the recipient's raw FCM device token (registration_ids), which has no
            // reason to ever reach the client. Already fixed the same way on the seller side.
            print_r(json_encode(['error' => false]));
        }
    }
    /* ================== support-assistant transcripts ==================
     *
     * The storefront chat widget has been writing every message to `chat_messages` and no
     * screen anywhere read it back, so nobody on the team could see what customers asked the
     * bot. These three methods are that missing screen. They sit on this controller and reuse
     * the existing 'chat' permission module rather than introducing a new one, which would
     * need a migration plus a row in every role.
     */

    public function assistant()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
            return;
        }

        $settings = get_settings('system_settings', true);
        $this->data['main_page'] = TABLES . 'assistant-chats';
        $this->data['title'] = 'Assistant Chats | ' . $settings['app_name'];
        $this->data['meta_description'] = 'Support assistant conversations | ' . $settings['app_name'];
        $this->data['assistant_stats'] = $this->chat_model->get_assistant_stats();
        $this->data['assistant_fallback'] = $this->chat_model->get_assistant_fallback_rate();
        $this->load->view('admin/template', $this->data);
    }

    /** Bootstrap-table data source; the model echoes the JSON itself, as the others do. */
    public function assistant_list()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
            return;
        }

        return $this->chat_model->get_assistant_thread_list();
    }

    public function assistant_thread()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
            return;
        }

        // A thread token is hex from random_bytes(), or a legacy CodeIgniter session id. Both
        // are alphanumeric, so anything else is not a thread and is not worth a query.
        $thread = (string) $this->input->get('thread', true);
        if ($thread !== '' && !preg_match('/^[A-Za-z0-9]{1,128}$/', $thread)) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['error' => true, 'message' => 'Invalid conversation reference.']));
            return;
        }

        $user_id = (string) $this->input->get('user_id', true);
        $user_id = ctype_digit($user_id) ? (int) $user_id : 0;

        $messages = $this->chat_model->get_assistant_thread($thread, $user_id);

        $this->output->set_content_type('application/json')->set_output(json_encode([
            'error'    => false,
            'messages' => $messages,
        ]));
    }

    /**
     * Delete one conversation and every message in it.
     *
     * POST-only: a transcript wipe behind a GET would be triggerable by anything that can make
     * the browser fetch a URL, and CI's CSRF filter only guards POST.
     */
    public function assistant_delete()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['error' => true, 'message' => 'Not authorised.']));
            return;
        }

        $thread = (string) $this->input->post('thread', true);
        if ($thread !== '' && !preg_match('/^[A-Za-z0-9]{1,128}$/', $thread)) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['error' => true, 'message' => 'Invalid conversation reference.']));
            return;
        }

        $user_id = (string) $this->input->post('user_id', true);
        $user_id = ctype_digit($user_id) ? (int) $user_id : 0;

        $deleted = $this->chat_model->delete_assistant_thread($thread, $user_id);

        $this->output->set_content_type('application/json')->set_output(json_encode([
            'error'   => false,
            'deleted' => (int) $deleted,
            'message' => $deleted > 0
                ? 'Conversation deleted (' . (int) $deleted . ' messages removed).'
                : 'That conversation had already been removed.',
        ]));
    }
}
