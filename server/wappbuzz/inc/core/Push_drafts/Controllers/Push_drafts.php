<?php
namespace Core\Push_drafts\Controllers;

class Push_drafts extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push_drafts\Models\Push_draftsModel();
    }
    
    public function index( $page = false ) {
        $total = $this->model->list("all", false);

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

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "content" => view('Core\Push_drafts\Views\list', $data_content)
        ];

        return view('Core\Push_drafts\Views\index', $data);
    }

    public function ajax_list(){
        $total_items = $this->model->list("all", false);
        $result = $this->model->list("all", true);
        $data = [
            "result" => $result,
            "config" => $this->config
        ];
        ms( [
            "total_items" => $total_items,
            "data" => view('Core\Push_drafts\Views\ajax_list', $data)
        ] );
    }
}