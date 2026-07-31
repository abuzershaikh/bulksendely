<?php

namespace Core\Whatsapp_chatbot\Controllers;

class Whatsapp_chatbot extends \CodeIgniter\Controller
{
    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
        $this->model = new \Core\Whatsapp_chatbot\Models\Whatsapp_chatbotModel();
    }

    public function index($page = false, $account_ids = "", $ids = "")
    {
        $team_id = get_team("id");
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        switch ($page) {
            case 'list':
                $account = db_get("*", TB_ACCOUNTS, ["ids" => $account_ids, "team_id" => $team_id]);

                if (!$account) {
                    redirect_to(get_module_url());
                }

                $item = db_get("*", TB_WHATSAPP_CHATBOT, ["instance_id" => $account->token]);
                if ($item && $item->run == 1) {
                    $run = 1;
                } else {
                    $run = 0;
                }

                $total = $this->model->get_list(false);

                $datatable = [
                    "total_items" => $total,
                    "per_page" => 30,
                    "current_page" => 1,

                ];

                $data_content = [
                    'run' => $run,
                    'account' => $account,
                    'total' => $total,
                    'datatable'  => $datatable,
                    'config'  => $this->config,
                ];

                $data['content'] = view('Core\Whatsapp_chatbot\Views\list', $data_content);
                break;
            case 'ai_settings':
                $account = db_get("*", TB_ACCOUNTS, ["ids" => $account_ids, "team_id" => $team_id]);

                $ai = $this->model->get_ai_settings($account->token);

                $data_content = [
                    'account' => $account,
                    'config'  => $this->config,
                    "ai" => $ai[0] ?? [
                        'status' => 0,
                        'apikey' => '',
                        'temperature' => 0.6,
                        'model' => 'gpt-3.5-turbo'
                    ]
                ];
                $data['content'] = view('Core\Whatsapp_chatbot\Views\ai_settings', $data_content);
                break;
            case 'capturer_report':

                $account = db_get("*", TB_ACCOUNTS, ["ids" => $account_ids, "team_id" => $team_id]);
                $result = db_fetch("*", TB_WHATSAPP_CHATBOT, ['team_id' => $team_id, 'instance_id' => $account->token, 'save_data' => '2']);

                $data_content = [
                    'account' => $account,
                    'result' => $result,
                    'config'  => $this->config,
                ];

                $data['content'] = view('Core\Whatsapp_chatbot\Views\capturer_report', $data_content);

                break;
            case 'update':
                $team_id = get_team("id");
                $account = db_get("*", TB_ACCOUNTS, ["ids" => $account_ids, "team_id" => $team_id]);
                if (empty($account)) {
                    redirect_to(get_module_url());
                }

                $item = false;
                if ($ids) {
                    $item = db_get("*", TB_WHATSAPP_CHATBOT, ["ids" => $ids, "team_id" => $team_id]);
                }

                $data['content'] = view('Core\Whatsapp_chatbot\Views\update', ["result" => $item, "account" => $account, "config" => $this->config]);
                break;

            default:
                $total = $this->model->get_list(false);

                $datatable = [
                    "total_items" => $total,
                    "per_page" => 30,
                    "current_page" => 1,

                ];

                $data_content = [
                    'total' => $total,
                    'datatable'  => $datatable,
                    'config'  => $this->config,
                ];

                $data['content'] = view('Core\Whatsapp_chatbot\Views\content', $data_content);
                break;
        }

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function ajax_list()
    {
        $total_items = $this->model->get_list(false);
        $result = $this->model->get_list(true);


        $data = [
            "result" => $result,
            "config" => $this->config,
        ];
        ms([
            "total_items" => $total_items,
            "data" => view('Core\Whatsapp_chatbot\Views\ajax_list', $data)
        ]);
    }

    public function ajax_list_items($ids = false)
    {
        $team_id = get_team("id");
        $account = db_get("*", TB_ACCOUNTS, ["ids" => $ids, "team_id" => $team_id]);

        $total_items = $this->model->get_list_items(false, $account->token);
        $result = $this->model->get_list_items(true, $account->token);


        $ai = $this->model->get_ai_settings($account->token);



        $data = [
            "account" => $account,
            "result" => $result,
            "config" => $this->config,
            "ai" => $ai
        ];
        ms([
            "total_items" => $total_items,
            "data" => view('Core\Whatsapp_chatbot\Views\ajax_list_items', $data)
        ]);
    }

    public function save_ai($instance_id = false)
    {
        $team_id = get_team("id");

        $item = db_get("*", TB_WHATSAPP_AI, ["instance_id" => $instance_id, "team_id" => $team_id]);

        $status = (int)post("status");
        $apikey = post("apikey") ?? '';
        $initial_promp = post("initial_promp") ?? '';
        $send_to = (int)post('send_to') ?? 1;
        $key_enable = post('key_enable') ?? '';
        $key_disable = post('key_disable') ?? '';
        $temperature = post('temperature') ?? '0.5';
        $model = post('model') ?? 'gpt-3.5-turbo';
                $maxtoken = post('max_tokens') ?? '';
        $unknown_message_action = (int)post('unknown_message_action') ?? 3;
        $unknown_message_reply = post('unknown_message_reply') ?? '';

        if (!empty($item)) {
            $data = [
                "team_id" => $team_id,
                "instance_id" => $instance_id,
                "status" =>  $status,
                "apikey" => $apikey,
                "temperature" => $temperature,
                "model" => $model,
                'key_enable' => $key_enable,
                'key_disable' => $key_disable,
                'max_tokens' => $maxtoken
            ];
            db_update(TB_WHATSAPP_AI, $data, ['instance_id' => $instance_id]);
        } else {
            $data = [
                "team_id" => $team_id,
                "instance_id" => $instance_id,
                "status" => $status,
                "apikey" =>  $apikey,
                "temperature" => $temperature,
                "model" => $model,
                'key_enable' => $key_enable,
                'key_disable' => $key_disable,
                'max_tokens' => $maxtoken
            ];

            $result = db_insert(TB_WHATSAPP_AI, $data);
        }


        ms([
            "status" => "success",
            "message" => __("Success")
        ]);
    }

    public function export_chatbot($instance_id = false)
    {
        $team_id = get_team("id");
        $ids = post("ids") ?? [];
        $tb_temp = TB_WHATSAPP_TEMPLATE;
        $tb_cb = TB_WHATSAPP_CHATBOT;

        if (empty($ids)) {
            ms([
                "status" => "error",
                "message" => __('Please select an item to export')
            ]);
        }

        $db = \Config\Database::connect();
        $sql = "SELECT name, keywords, type_search, template ,type, caption, media, run, sent, send_to, status, presenceTime, presenceType, nextBot, description, use_ai, is_default  FROM {$tb_cb} WHERE ids in ? and team_id = ? ";
        $query = $db->query($sql, array($ids, $team_id));
        $chatbots = $query->getResultArray();
        $query->freeResult();

        $templates = [];
        foreach ($chatbots as $key => $itm) {
            if ($itm['template'] != 0) {
                $sql = "SELECT * FROM {$tb_temp} WHERE team_id = ? AND id = ?";
                $query = $db->query($sql, array($team_id, $itm['template']));
                $template = $query->getRowArray();

                if (isset($template)) {
                    $templates[$itm['template']] = [
                        'type' => $template['type'],
                        'name' => $template['name'],
                        'data' => $template['data']
                    ];
                }
            }
        }

        $return_obj = [
            "version" => '8.0.0',
            'chatbots' => $chatbots,
            'templates' => $templates
        ];

        ms([
            "status" => "success",
            "message" => __("Success"),
            "callback" => '
            <script>
                (function () {
                    const blob =  new Blob([JSON.stringify(' . json_encode($return_obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ')]);
                    const link = document.createElement("a");
                    link.download ="' . $instance_id . '_' . date('Ymd') . '.json' . '";;
                    link.href = window.URL.createObjectURL(blob);
                    link.click();
                    link.remove();                
                })();
            </script>'
        ]);
    }

    public function import_chatbot($instance_id = false)
    {
        $team_id = get_team("id");
        $max_size = 10 * 1024;
        $file_path = "";


        if (!empty($_FILES) && is_array($_FILES['files']['name'])) {
            if (empty($this->request->getFiles())) {
                ms([
                    "status" => "error",
                    "message" => __('Cannot found files json to upload')
                ]);
            }

            $check_mime = $this->validate([
                'files' => [
                    'uploaded[files]',
                    'ext_in[files,json]'
                ],
            ]);

            if (!$check_mime) {
                ms([
                    "status" => "error",
                    "message" => "The filetype you are attempting to upload is not allowed"
                ]);
            }


            $check_size = $this->validate([
                'files' => [
                    'uploaded[files]',
                    'max_size[files,' . $max_size . ']'
                ],
            ]);


            if (!$check_size) {
                ms([
                    "status" => "error",
                    "message" => __(sprintf("Unable to upload a file larger than %sMB", $max_size))
                ]);
            }

            if ($file = $this->request->getFiles()) {
                if (isset($file['files'])) {
                    foreach ($file['files'] as $img) {
                        if ($img->isValid() && !$img->hasMoved()) {
                            $newName = $img->getRandomName();
                            $img->move(WRITEPATH . 'uploads', $newName);
                            $file_path = WRITEPATH . 'uploads/' . $newName;
                        }
                    }
                }
            }
        }

        if ($file_path == "") {
            ms([
                "status" => "error",
                "message" => __("Upload json file failed.")
            ]);
        }

        // Read the JSON file 
        $json = file_get_contents($file_path);

        // Decode the JSON file
        $result = json_decode($json, true);
        $status = unlink($file_path);

        $templatesHs = [];

        if ($result['version'] == '8.0.0') {
            foreach ($result['chatbots'] as $c) {
                if ($c['template'] != '0') {
                    $templatesHs[] = $c['template'];
                    $template = $result['templates'][$c['template']];

                    //$n_ids = ids();
                    $data = [
                        "ids" => ids(),
                        "team_id" => $team_id,
                        "type" => $template['type'],
                        "name" => $template['name'] . '_imported',
                        "data" => $template['data'],
                        "changed" => time(),
                        "created" => time(),
                    ];

                    $n_id = db_insert(TB_WHATSAPP_TEMPLATE, $data);
                    if (isset($n_id)) {
                        $data = [
                            "ids" => ids(),
                            "team_id" => $team_id,
                            "instance_id" => $instance_id,
                            "type" => $c['type'],
                            "name" => $c['name'] . "_imported",
                            "type_search" => $c['type_search'],
                            "template" => $n_id,
                            "keywords" => $c['keywords'],
                            "caption" => $c['caption'],
                            "media" => $c['media'],
                            "run" => $c['run'],
                            "send_to" => $c['send_to'],
                            "status" => $c['status'],
                            "changed" => time(),
                            "created" => time(),
                            "presenceTime" => $c['presenceTime'],
                            "presenceType" => $c['presenceType'],
                            "nextBot" => $c['nextBot'],
                            "description" => $c['description'],
                            "use_ai" => $c['use_ai'],
                            "is_default" => $c['is_default'],
                            "save_data" => $c['save_data'] ?? '0',
                            "inputname" => $c['inputname'] ?? '',
                            "get_api_data" => $c['get_api_data'] ?? '0',
                            "api_url" => $c['api_url'] ?? '',
                            "api_config" => $c['api_config'] ?? '',
                        ];
                        if ($c['is_default'] == 1) {
                            db_update(TB_WHATSAPP_CHATBOT, ['is_default' => 0], ["instance_id" => $instance_id]);
                        }

                        $result_db = db_insert(TB_WHATSAPP_CHATBOT, $data);
                    }
                } else {

                    $data = [
                        "ids" => ids(),
                        "team_id" => $team_id,
                        "instance_id" => $instance_id,
                        "type" => $c['type'],
                        "name" => $c['name'] . "_imported",
                        "type_search" => $c['type_search'],
                        "template" => $c['template'],
                        "keywords" => $c['keywords'],
                        "caption" => $c['caption'],
                        "media" => $c['media'],
                        "run" => $c['run'],
                        "send_to" => $c['send_to'],
                        "status" => $c['status'],
                        "changed" => time(),
                        "created" => time(),
                        "presenceTime" => $c['presenceTime'],
                        "presenceType" => $c['presenceType'],
                        "nextBot" => $c['nextBot'],
                        "description" => $c['description'],
                        "use_ai" => $c['use_ai'],
                        "is_default" => $c['is_default'],
                        "save_data" => $c['save_data'] ?? '0',
                        "inputname" => $c['inputname'] ?? '',
                        "get_api_data" => $c['get_api_data'] ?? '0',
                        "api_url" => $c['api_url'] ?? '',
                        "api_config" => $c['api_config'] ?? '',
                    ];

                    if ($c['is_default'] == 1) {
                        db_update(TB_WHATSAPP_CHATBOT, ['is_default' => 0], ["instance_id" => $instance_id]);
                    }

                    $result_db = db_insert(TB_WHATSAPP_CHATBOT, $data);
                }
            }
        } else {

            ms([
                "status" => "error",
                "message" => __("Invalid JSON chatbot format.")
            ]);
        }

        ms([
            "status" => "success",
            "message" => __("Success"),
        ]);
    }

    public function do_capturer_report($instance_id = false)
    {
        $team_id = get_team("id");
        $ids = post("ids") ?? [];
        $account = db_get("*", TB_ACCOUNTS, ["token" => $instance_id, "team_id" => $team_id]);
        if (empty($ids)) {
            header('Location: /whatsapp_chatbot/index/capturer_report/' . $account->ids);
            exit;
        }
        $tb_cb = TB_WHATSAPP_CHATBOT;

        $db = \Config\Database::connect();
        $sql = "SELECT inputname  FROM {$tb_cb} WHERE ids in ? and team_id = ?";
        $query = $db->query($sql, array($ids, $team_id));
        $chatbots = $query->getResultArray();
        $query->freeResult();

        $delimiter = ',';
        $filename = $instance_id . '.csv';

        $subscribers = db_fetch('chatid,data', TB_WHATSAPP_SUBSCRIBERS, ['instance_id' => $instance_id, 'team_id' => $team_id]);
        $f = fopen('php://memory', 'w');

        fputs($f, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $firstLine = [];
        $firstLine[] = 'chatid';
        $chatbots_order = [];
        foreach ($chatbots as $key => $value) {
            $name = get_data($value, 'inputname');
            if (!in_array($name, $firstLine)) {
                $firstLine[] = $name;
                $chatbots_order[] = $name;
            }
        }
        fputcsv($f, $firstLine, $delimiter);
        foreach ($subscribers as $subscriber) {
            $subscriber = (array)$subscriber;
            $line = [];
            $line[] =  str_replace('@s.whatsapp.net', '', $subscriber['chatid']);

            $data = (array)json_decode($subscriber['data']);
            foreach ($chatbots_order as $key => $value) {
                $line[] =  $data[$value] ?? '';
            }
            fputcsv($f, $line, $delimiter);
        }
        fseek($f, 0);
        header('Content-Type: application/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        fpassthru($f);
    }

    public function save()
    {
        $team_id = get_team("id");
        $ids = post("ids");
        $type = (int)post("type");
        $name = post("name");
        $advance_options = post("advance_options");
        $type_search = (int)post("type_search");
        $template = 0;
        $btn_msg = (int)post("btn_msg");
        $list_msg = (int)post("list_msg");
        $keywords = post("keywords");
        $caption = post("caption");
        $medias = post("medias");
        $send_to = (int)post('send_to');
        $status = (int)post("status");
        $instance_id = post("instance_id");
        $except = post('except');
        $interval_per_post = (int)post("interval_per_post");
        $item = db_get("*", TB_WHATSAPP_CHATBOT, ["ids" => $ids, "team_id" => $team_id]);
        $account = db_get("*", TB_ACCOUNTS, ["token" => $instance_id, "team_id" => $team_id]);


        $presenceTime = (int)post('presenceTime');
        $presenceType = (int)post('presenceType');
        $nextBot = trim(post('nextBot') ?? '');
        $description = trim(post('description') ?? '');
        $saveData = post("save_data");
        $inputName = post("inputname");


        $api_conf = post("api_config");
        $api_url = post("api_url");
        $api_enabled = post("get_api_data");
        
        

        if ($api_enabled == 2) {
            validate('null', __('API Url'), $api_url);
            validate('link', __('API Url'), $api_url);

            if (isset($api_conf['header'])) {
                $headers = $api_conf['header'];
                foreach ($headers as $key => $itm) {
                    validate('null', sprintf(__('header key %s'), $key + 1), $itm['name']);
                    validate('null', sprintf(__('header value %s'), $key + 1), $itm['value']);
                }
            }

            if (isset($api_conf['body'])) {
                $body = $api_conf['body'];
                foreach ($body as $key => $itm) {
                    validate('null', sprintf(__('body key %s'), $key + 1), $itm['name']);
                    validate('null', sprintf(__('body value %s'), $key + 1), $itm['value']);
                }
            }
        } else {
            $api_enabled = 1;
        }


        if ($saveData == 2) {
            validate('null', __('Field Name'), $inputName);
            $inputName = strtolower(str_replace(' ', '_', $inputName));
        } else {
            $saveData = 1;
        }

        $use_ai = (int)post('use_ai');
        $is_default = (int)post('is_default');


        if ($is_default == 1) {
            $saveData = 1;
        }

        validate('null', __('Bot name'), $name);
        validate("max_length", "Bot name", $name, 100);
        validate('null', __('Keywords'), $keywords);
        validate('empty', __('Please select at least a profile'), $account);

        if ($account->status == 0) {
            ms([
                "status" => "error",
                "message" => __("Relogin is required")
            ]);
        }

        switch ($type) {
            case 1:
                if (permission("whatsapp_send_media")) {
                    if (!is_array($medias) && $caption == "") {
                        ms([
                            "status" => "error",
                            "message" => __('Please enter a caption or add a media')
                        ]);
                    }
                } else {
                    validate('null', __('Caption'), $caption);
                }
                break;

            case 2:
                if ($btn_msg == 0) {
                    ms([
                        "status" => "error",
                        "message" => __('Please select a button message option')
                    ]);
                }
                $template = $btn_msg;
                break;

            case 3:
                if ($list_msg == 0) {
                    ms([
                        "status" => "error",
                        "message" => __('Please select a list message option')
                    ]);
                }

                $template = $list_msg;
                break;

            case 4:
                if ($btn_msg == 0) {
                    ms([
                        "status" => "error",
                        "message" => __('Please select a poll message option')
                    ]);
                }
                $template = $btn_msg;
                break;

            default:
                if ($btn_msg == 0) {
                    wa_ms([
                        "status" => "error",
                        "message" => __('Invalid input data')
                    ]);
                }
                break;
        }

        $run = 0;
        $chatbot_item = db_get("*", TB_WHATSAPP_CHATBOT, ["instance_id" => $instance_id, "team_id" => $team_id]);
        if (!empty($chatbot_item) && $chatbot_item->run) {
            $run = 1;
        }

        if (!empty($medias) && permission("whatsapp_send_media")) {
            foreach ($medias as $key => $value) {
                $medias[$key] = get_file_url($value);
            }

            $media = $medias[0];
        } else {
            $media = NULL;
        }
 
        $keywords = wa_keyword_trim($keywords);

        if (!empty($advance_options) && isset($advance_options['shortlink'])) {
            $shortlink_by = shortlink_by(['advance_options' => ['shortlink' => $advance_options['shortlink']]]);
            $caption = shortlink($caption, $shortlink_by);
        }

        if (!empty($item)) {
            $data = [
                "team_id" => $team_id,
                "instance_id" => $instance_id,
                "type" => $type,
                "name" => $name,
                "type_search" => $type_search,
                "template" => $template,
                "keywords" => mb_strtolower($keywords),
                "caption" => $caption,
                "media" => $media,
                "except" => $except,
                "run" => $run,
                "send_to" => $send_to,
                "status" => $status,
                "changed" => time(),
                "presenceTime" => $presenceTime,
                "presenceType" => $presenceType,
                "nextBot" => $nextBot,
                "description" => $description,
                "use_ai" => $use_ai,
                "is_default" => $is_default,
                "save_data" => $saveData,
                "inputname" => $inputName,
                "get_api_data" => $api_enabled,
                "api_url" => $api_url,
                "api_config" => json_encode($api_conf),
            ];



            if ($is_default == 1) {
                db_update(TB_WHATSAPP_CHATBOT, ['is_default' => 0], ["instance_id" => $instance_id]);
            }

            $result = db_update(TB_WHATSAPP_CHATBOT, $data, ["id" => $item->id]);
        } else {
            $chatbot_count = db_get("count(*) as count", TB_WHATSAPP_CHATBOT, ["instance_id" => $instance_id, "team_id" => $team_id])->count;
            if ($chatbot_count >= (int)permission("whatsapp_chatbot_item_limit")) {
                ms([
                    "status" => "error",
                    "message" => sprintf(__('You can only add a maximum of %s chatbot items.'), (int)permission("whatsapp_chatbot_item_limit"))
                ]);
            }

            $data = [
                "ids" => ids(),
                "team_id" => $team_id,
                "instance_id" => $instance_id,
                "type" => $type,
                "name" => $name,
                "type_search" => $type_search,
                "template" => $template,
                "keywords" => mb_strtolower($keywords),
                "caption" => $caption,
                "media" => $media,
                "except" => $except,
                "run" => $run,
                "send_to" => $send_to,
                "status" => $status,
                "changed" => time(),
                "created" => time(),
                "presenceTime" => $presenceTime,
                "presenceType" => $presenceType,
                "nextBot" => $nextBot,
                "description" => $description,
                "use_ai" => $use_ai,
                "is_default" => $is_default,
                "save_data" => $saveData,
                "inputname" => $inputName,
                "get_api_data" => $api_enabled,
                "api_url" => $api_url,
                "api_config" => json_encode($api_conf),
            ];

            if ($is_default == 1) {
                db_update(TB_WHATSAPP_CHATBOT, ['is_default' => 0], ["instance_id" => $instance_id]);
            }

            $result = db_insert(TB_WHATSAPP_CHATBOT, $data);
        }

        ms([
            "status" => "success",
            "message" => __("Success")
        ]);
    }

    public function status($instance_id = false)
    {
        $team_id = get_team('id');
        $chatbot_item = db_get("*", TB_WHATSAPP_CHATBOT, ["instance_id" => $instance_id, "team_id" => $team_id]);

        if (!$chatbot_item) {

            ms([
                "status" => "error",
                "message" => __('Please add at least a chatbot item to can start')
            ]);
        }

        if (!empty($chatbot_item)) {
            if ($chatbot_item->run) {
                db_update(TB_WHATSAPP_CHATBOT, ['run' => 0], ['instance_id' => $instance_id]);
            } else {
                db_update(TB_WHATSAPP_CHATBOT, ['run' => 1], ['instance_id' => $instance_id]);
            }
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }

    public function delete()
    {
        $team_id = get_team("id");
        $ids = post('ids');
        $id = post('id');

       

        if (empty($id) && empty($ids)) {
            ms([
                "status" => "error",
                "message" => __('Please select an item to delete')
            ]);
        }

        if (is_array($ids)) {
            foreach ($ids as $id) {
                db_delete(TB_WHATSAPP_CHATBOT, ['ids' => $id, "team_id" => $team_id]);
            }
        } elseif (is_string($id)) {
            db_delete(TB_WHATSAPP_CHATBOT, ['ids' => $id, "team_id" => $team_id]);
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }
}
