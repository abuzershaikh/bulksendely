<?php
namespace Core\Push_setttings\Models;
use CodeIgniter\Model;

class Push_setttingsModel extends Model
{
	public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }
    
    public function block_settings($path = ""){
        return array(
            "position" => 9301,
            "menu" => view( 'Core\Push_setttings\Views\settings\menu', [ 'config' => $this->config ] ),
            "content" => view( 'Core\Push_setttings\Views\settings\content', [ 'config' => $this->config ] )
        );
    }

    public function block_push_settings($path = ""){
        return [
            "position" => 10000,
            "menu" => view( 'Core\Push_setttings\Views\push_settings\menu', [ 'config' => $this->config ] ),
            "content" => view( 'Core\Push_setttings\Views\push_settings\content', [ 'config' => $this->config ] )
        ];
    }
}
