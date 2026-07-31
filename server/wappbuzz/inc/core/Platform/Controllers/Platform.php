<?php
namespace Core\Platform\Controllers;

class Platform extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    public function index($page = ""){
        if(get_session("platform")){
            $platform_id = (int)get_session("platform");
            $platform = db_get("*", TB_PLATFORM, ["platform_id" => $platform_id], "platform_id", "DESC");
        }else{
            $platform = db_get("*", TB_PLATFORM, [], "is_default", "DESC");
        }

        if(!$platform) redirect_to( base_url() );

        set_session(["platform" => $platform->platform_id]);
        redirect_to( base_url( $platform->default_page ) );
    }

    public function no_permissions(){
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        $data['content'] = view('Core\Platform\Views\no_permissions', []);
        return view('Core\Platform\Views\index', $data);
    }

    public function settings($page = ""){
        $result = db_fetch("*", TB_PLATFORM, "", "id", "ASC");
        
        $data = [
            "result" => $result,
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        $data['content'] = view('Core\Platform\Views\content', ["result" => $result]);

        return view('Core\Platform\Views\index', $data);
    }

    public function set_default(){
        $id = post("id");

        $check = db_get("*", TB_PLATFORM, [ "id" => $id ]);

        if(!$check){
            ms([
                "status" => "success",
                "message" => __("This platform does not exist.")
            ]);
        }

        if($check->status == 0){
            ms([
                "status" => "error",
                "message" => __("This platform is hidden so you cannot set it as default")
            ]);
        }

        db_update(TB_PLATFORM, ["is_default" => 0], ["id !=" => $id]);
        db_update(TB_PLATFORM, ["is_default" => 1], ["id" => $id]);

        ms([
            "status" => "success",
            "message" => __("Success")
        ]);
    }

    public function status($status = 0){
        $id = (int)post("id");

        $check_default = db_fetch("*", TB_PLATFORM, [ "id" => $id, "is_default" => 1 ]);
        if($check_default){
            ms([
                "status" => "error",
                "message" => __("This platform is default you can not hide it.")
            ]);
        }
        
        db_update(TB_PLATFORM, ["status" => (int)$status], ["id" => $id]);
        
        ms([
            "status" => "success",
            "message" => __("Success")
        ]);
    }
}