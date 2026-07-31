<?php

namespace Core\Blog_internal\Models;

use CodeIgniter\Model;

class Blog_internalModel extends Model
{
    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
    }

    public function get_list($return_data = true)
    {
        $current_page = (int)(post("current_page") - 1);
        $per_page = post("per_page");
        $total_items = post("total_items");
        $keyword = post("keyword");

        $db = \Config\Database::connect();
        $builder = $db->table(TB_BLOGS);
        $builder->select('*');



        $builder->Where(['internal' => '1']);


        if ($keyword) {
            $array = [
                'title' => $keyword,
                'content' => $keyword
            ];
            $builder->orLike($array);
        }

        $builder->orderBy('id', 'ASC');

        if (!$return_data) {
            $result =  $builder->countAllResults();
        } else {
            $builder->limit($per_page, $per_page * $current_page);
            $builder->orderBy("created", "DESC");
            $query = $builder->get();
            $result = $query->getResult();
            $query->freeResult();
        }

        return $result;
    }
}
