<?php
namespace Core\Platform\Models;
use CodeIgniter\Model;

class PlatformModel extends Model
{
	public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    public function block_settings($path = ""){
        return array(
            "position" => 9300,
            "menu" => view( 'Core\Platform\Views\settings\menu', [ 'config' => $this->config ] ),
            "content" => view( 'Core\Platform\Views\settings\content', [ 'config' => $this->config ] )
        );
    }
}
