<?php
namespace Core\Push_setttings\Controllers;

class Push_setttings extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }
    
    public function index( $page = false ) {
        $block_push_settings = $this->request->block_push_settings;

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "blocks" => $block_push_settings
        ];

        if( isset($block_push_settings[$page]) && isset($block_push_settings[$page]) ){
            $data['content'] = view('Core\Push_setttings\Views\content', [ 'block_tab' => $block_push_settings[$page] ]);
        }else{
            $data['content'] = view('Core\Push_setttings\Views\empty');
        }

        return view('Core\Push_setttings\Views\index', $data);
    }

    public function save(){
        $data = $this->request->getPost();

        if(is_array($data)){
            foreach ($data as $key => $value) {
                if($key != 'csrf'){
                    update_option( $key, trim( htmlspecialchars( $value ) ) );
                }
            }
        }

        ms([
            "status"  => "success",
            "message" => __('Success'),
        ]);
    }
}