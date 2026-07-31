<?php

namespace Core\Admin_API\Models;

use CodeIgniter\Model;

class Admin_APIModel extends Model
{
    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
    }

    public function block_settings($path = "")
    {
        return array(
            "position" => 10000,
            "menu" => view('Core\Admin_API\Views\settings\menu', ['config' => $this->config]),
            "content" => view('Core\Admin_API\Views\settings\content', ['config' => $this->config])
        );
    }

    public function get_list($return_data = true)
    {
        $current_page = (int)((post("page") ?? 1) - 1);
        $per_page = post("per_page") ?? 20;
        $total_items = post("total_items");
        $search = post("search");


        $db = \Config\Database::connect();
        $builder = $db->table(TB_USERS . " as a");
        $builder->join(TB_PLANS . " as b", "a.plan = b.id", "LEFT");
        $builder->join(TB_ROLES . " as c", "a.role = c.id", "LEFT");
        $builder->select('a.id, a.ids, a.is_admin, a.fullname, a.username, a.email, a.plan, a.expiration_date, a.timezone, a.language, a.login_type,  a.status, a.last_login, a.changed, a.created,b.name as plan_name,c.name as role_name');
        if ($search) {
            $array = [
                'a.username' => $search,
                'a.fullname' => $search,
                'a.email' => $search,
                'b.name' => $search,
                'c.name' => $search
            ];
            $builder->orLike($array);
        }

        if (!$return_data) {
            $result =  $builder->countAllResults();
        } else {
            $builder->limit($per_page, $per_page * $current_page);
            $query = $builder->get();
            $result = $query->getResult();
            $query->freeResult();
        }

        return $result;
    }
}
