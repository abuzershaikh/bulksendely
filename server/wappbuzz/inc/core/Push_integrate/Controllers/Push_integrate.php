<?php
namespace Core\Push_integrate\Controllers;

class Push_integrate extends \CodeIgniter\Controller
{
    public function __construct(){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: *");
        include get_module_dir( __DIR__ , '../Push/Libraries/vendor/autoload.php');
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push_integrate\Models\Push_integrateModel();
        $this->domain_data = db_get("*", TB_STACKPUSH_DOMAINS, ["id" => PUSH_DOMAIN_ID, "team_id" => TEAM_ID]);
    }
    
    public function index( $page = false ) {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        $data_content = [
            'config'  => $this->config,
            'result'  => $this->domain_data,
        ];

        $data['content'] = view('Core\Push_integrate\Views\content', $data_content);


        return view('Core\Push\Views\index', $data);
    }
}