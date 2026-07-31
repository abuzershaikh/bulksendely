<?php
namespace Core\Whatsapp_poll_template\Controllers;

class Whatsapp_poll_template extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Whatsapp_poll_template\Models\Whatsapp_poll_templateModel();
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
                    $item = db_get("*", TB_WHATSAPP_TEMPLATE, ["type" => 3, "ids" => $ids, "team_id" => $team_id]);
                }

                $data['content'] = view('Core\Whatsapp_poll_template\Views\update', ["result" => $item, "config" => $this->config]);
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

                $data['content'] = view('Core\Whatsapp_poll_template\Views\content', $data_content );
                break;
        }

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function widget_menu( $params = [] ){
        if ( !permission("whatsapp_poll_template") ) return "";
        $result = $params['result'];
        return view('Core\Whatsapp_poll_template\Views\widget\menu', ["result" => $result]);
    }

    public function widget_content( $params = [] ){
        if ( !permission("whatsapp_poll_template") ) return "";
        $team_id = get_team("id");
        $btn_templates = db_fetch("*", TB_WHATSAPP_TEMPLATE, ["type" => 3, "team_id" => $team_id]);
        return view('Core\Whatsapp_poll_template\Views\widget\content', ["result" => $params["result"], "btn_templates" => $btn_templates]);
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
            "data" => view('Core\Whatsapp_poll_template\Views\ajax_list', $data)
        ] );
    }

    public function save( $ids = false ){
        $name = post("name");
        $footer = post("footer");
        $medias = post("medias");
        $desc = post("desc");
        //$type = post("desc");
        $advance_options = post("advance_options");
        $btn_msg_type = post("btn_msg_type");
        $btn_msg_display_text = post("btn_msg_display_text");
        $btn_msg_link = post("btn_msg_link");
        $btn_msg_call = post("btn_msg_call");
        $team_id = get_team("id");
        $multi = post("multi_select");

        

        validate('null', __('Poll template name'), $name);

        if($desc==""){
            ms([
                "status" => "error",
                "message" => __('Main description is required')
            ]);
        }

        $btn_template = [];
        $item_button_message = [];
        $poll = [];

        foreach ($btn_msg_display_text as $key => $value) {
            //$value = trim($value);
            
            $poll[] = $btn_msg_display_text[$key];
        }

        $btn_template = [
            "name" => $desc,
            "values" => $poll,
            "selectableCount" => $multi == 0 ? 0 : 1,
        ];

        $item = db_get("*", TB_WHATSAPP_TEMPLATE, ["ids" => $ids, "team_id" => $team_id]);
        if( empty($item) ){
            $data = [
                "ids" => ids(),
                "team_id" => $team_id,
                "type" => 3,
                "name" => $name,
                "data" => json_encode($btn_template),
                "changed" => time(),
                "created" => time(),
            ];
            
            db_insert( TB_WHATSAPP_TEMPLATE, $data );
        }else{
            $data = [
                "name" => $name,
                "data" => json_encode($btn_template),
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