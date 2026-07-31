<?php

namespace Core\Extended_Modules\Controllers;

class Extended_Modules extends \CodeIgniter\Controller
{
    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
        $this->model = new \Core\Extended_Modules\Models\Extended_ModulesModel();
    }

    public function index()
    {
        return view('Core\Extended_Modules\Views\content', []);
    }
}
