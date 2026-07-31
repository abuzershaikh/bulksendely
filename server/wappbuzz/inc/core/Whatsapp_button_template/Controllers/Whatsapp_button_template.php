<?php
namespace Core\Whatsapp_button_template\Controllers;

class Whatsapp_button_template extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Whatsapp_button_template\Models\Whatsapp_button_templateModel();
    }
    
    public function index( $page = false, $ids = false ) {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        switch ( $page ) {
            case 'update':
                $item = false;
                if( $ids ){
                    $team_id = get_team("id");
                    $item = db_get("*", TB_WHATSAPP_TEMPLATE, ["type" => 2, "ids" => $ids, "team_id" => $team_id]);
                }

                $data['content'] = view('Core\Whatsapp_button_template\Views\update', ["result" => $item, "config" => $this->config]);
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

                $data['content'] = view('Core\Whatsapp_button_template\Views\content', $data_content );
                break;
        }

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function widget_menu( $params = [] ){
        if ( !permission("whatsapp_button_template") ) return "";
        $result = $params['result'];
        return view('Core\Whatsapp_button_template\Views\widget\menu', ["result" => $result]);
    }

    public function widget_content( $params = [] ){
        if ( !permission("whatsapp_button_template") ) return "";
        $team_id = get_team("id");
        $btn_templates = db_fetch("*", TB_WHATSAPP_TEMPLATE, ["type" => 2, "team_id" => $team_id]);
        return view('Core\Whatsapp_button_template\Views\widget\content', ["result" => $params["result"], "btn_templates" => $btn_templates]);
    }

    public function ajax_list(){
        $total_items = $this->model->get_list(false);
        $result = $this->model->get_list(true);
        $data = [
            "result" => $result,
            "config" => $this->config
        ];
        ms( [
            "total_items" => $total_items,
            "data" => view('Core\Whatsapp_button_template\Views\ajax_list', $data)
        ] );
    }

    public function save( $ids = false ){
        $name = post("name");
        $footer = post("footer");
        $title = post("title");
        $medias = post("medias");
        $desc = post("desc");
        $advance_options = post("advance_options");
        $btn_msg_type = post("btn_msg_type");
        $btn_msg_display_text = post("btn_msg_display_text");
        $btn_msg_link = post("btn_msg_link");
        $btn_msg_copy = post("btn_msg_copy");
        $btn_msg_call = post("btn_msg_call");
        $team_id = get_team("id");

        $shortlink_by = false;
        if(!empty($advance_options) && isset($advance_options['shortlink'])){
            $shortlink_by = shortlink_by(['advance_options' => [ 'shortlink' => $advance_options['shortlink'] ]]);
        }

        validate('null', __('Button template name'), $name);

        if($desc==""){
            ms([
                "status" => "error",
                "message" => __('Main description is required')
            ]);
        }

        if( empty($btn_msg_type) ){
            ms([
                "status" => "error",
                "message" => __('Add at least one button item')
            ]);
        }

        if(count($btn_msg_type) > 3){
            ms([
                "status" => "error",
                "message" => __('Only up to 3 button items allowed')
            ]);
        }

        $btn_template = [];
        $item_button_message = [];

        foreach ($btn_msg_type as $key => $value) {
            $value = trim($value);


            switch ($value) {
                case 1:
                    if( !isset($btn_msg_display_text[$key]) || $btn_msg_display_text[$key] == "" ){
                        ms([
                            "status" => "error",
                            "message" => sprintf( __("Button %s: Please enter display text") , $key )
                        ]);
                    }

                    $item_button_message[] = [
                        "index" => $key,
                        "quickReplyButton" => [
                            "displayText" => $btn_msg_display_text[$key],
                            "id" => uniqid()
                        ]
                    ];
                    break;

                case 2:
                    if( !isset($btn_msg_display_text[$key]) || $btn_msg_display_text[$key] == "" ){
                        ms([
                            "status" => "error",
                            "message" => sprintf( __("Button %s: Please enter display text"), $key )
                        ]);
                    }

                    if (!isset($btn_msg_link[$key]) || filter_var($btn_msg_link[$key], FILTER_VALIDATE_URL) === FALSE) {
                        ms([
                            "status" => "error",
                            "message" => sprintf( __( "Button %s: Invalid URL"), $key )
                        ]);
                    }

                    $item_button_message[] = [
                        "index" => $key,
                        "urlButton" => [
                            "displayText" => $btn_msg_display_text[$key],
                            "url" => $btn_msg_link[$key],
                        ]
                    ];
                    break;

                case 3:
                    if( !isset($btn_msg_display_text[$key]) || $btn_msg_display_text[$key] == "" ){
                        ms([
                            "status" => "error",
                            "message" => sprintf( __( "Button %s: Please enter display text"), $key )
                        ]);
                    }

                    if ( !isset($btn_msg_call[$key]) || !isValidTelephoneNumber($btn_msg_call[$key]) ){
                        ms([
                            "status" => "error",
                            "message" => sprintf( __( "Button %s: Invalid phone number") , $key )
                        ]);
                    }

                    if ( $btn_msg_call[$key] == "" ){
                        ms([
                            "status" => "error",
                            "message" => sprintf( __( "Button %s: Phone number is required") , $key )
                        ]);
                    }

                    $item_button_message[] = [
                        "index" => $key,
                        "callButton" => [
                            "displayText" => $btn_msg_display_text[$key],
                            "phoneNumber" => $btn_msg_call[$key],
                        ]
                    ];
                    break;
                    
                case 4:
                    if( !isset($btn_msg_display_text[$key]) || $btn_msg_display_text[$key] == "" ){
                        ms([
                            "status" => "error",
                            "message" => sprintf( __("Button %s: Please enter display text"), $key )
                        ]);
                    }

                    if( !isset($btn_msg_copy[$key]) || $btn_msg_copy[$key] == "" ){
                        ms([
                            "status" => "error",
                            "message" => sprintf( __("Button %s: Please enter copy code"), $key )
                        ]);
                    }

                    $item_button_message[] = [
                        "index" => $key,
                        "urlButton" => [
                            "displayText" => $btn_msg_display_text[$key],
                            "url" => "https://www.whatsapp.com/otp/code/?otp_type=COPY_CODE&code=" . urlencode($btn_msg_copy[$key]),
                            "disabled" => false
                        ]
                    ];
                    break;

                
                default:
                    ms([
                        "status" => "error",
                        "message" => __('The type button item incorrect')
                    ]);
                    break;
            }

            if($value == ""){
                ms([
                    "status" => "error",
                    "message" => __('The option name is required')
                ]);
            }
        }

        // Create button template structure
        // This structure is compatible with WhatsApp Business API
        $btn_template = [
            "templateButtons" => $item_button_message
        ];

        $desc = shortlink($desc, $shortlink_by);
        $footer = shortlink($footer, $shortlink_by);
        $title = shortlink($title, $shortlink_by);

        // Add footer if provided
        if($footer != ""){
            $btn_template["footer"] = $footer;
        }

        // Add title if it's not the default value
        if($title != "Botão"){
            $btn_template["title"] = $title;
        }

        // Handle message content - text or image with caption
        // For better WhatsApp compatibility, we separate text-only and image messages
        if(!empty($medias) && permission("whatsapp_send_media")){
            // Image button message - use media_url instead of image object
            $btn_template["media_url"] = get_file_url($medias[0]);
            $btn_template["caption"] = $desc;
            // Set media type flag for backend processing
            $btn_template["has_media"] = true;
        }else{
            // Text-only button message
            $btn_template["text"] = $desc;
        }

        $item = db_get("*", TB_WHATSAPP_TEMPLATE, ["ids" => $ids, "team_id" => $team_id]);
        if( empty($item) ){
            $data = [
                "ids" => ids(),
                "team_id" => $team_id,
                "type" => 2,
                "name" => $name,
                "data" => json_encode($btn_template, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                "changed" => time(),
                "created" => time(),
            ];

            db_insert( TB_WHATSAPP_TEMPLATE, $data );
        }else{
            $data = [
                "name" => $name,
                "data" => json_encode($btn_template, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                "changed" => time(),
            ];

            db_update( TB_WHATSAPP_TEMPLATE, $data, ["ids" => $ids] );
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }

    public function delete(){
        $team_id = get_team("id");
        $ids = post('id');

        if( empty($ids) ){
            ms([
                "status" => "error",
                "message" => __('Please select an item to delete')
            ]);
        }

        if( is_array($ids) ){
            foreach ($ids as $id) {
                db_delete(TB_WHATSAPP_TEMPLATE, ['ids' => $id, "team_id" => $team_id]);
            }
        }
        elseif( is_string($ids) )
        {
            db_delete(TB_WHATSAPP_TEMPLATE, ['ids' => $ids, "team_id" => $team_id]);
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }
}