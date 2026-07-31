<?php

namespace Core\Blog_internal\Controllers;

class Blog_internal extends \CodeIgniter\Controller
{
    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
        $this->model = new \Core\Blog_internal\Models\Blog_internalModel();
    }



    public function index($page = false, $ids = false)
    {
        $result = $this->model->get_list(true);;

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "result" => $result
        ];

        switch ($page) {
            case 'show':

                $item = db_get('*', TB_BLOGS, ['ids' => $ids]);

                $data['content'] = view('Core\Blog_internal\Views\show', [
                    "item" => $item,
                ]);
                break;

            default:
                $data['content'] = view('Core\Blog_internal\Views\empty', []);
                break;
        }

        return view('Core\Blog_internal\Views\index', $data);
    }
}
