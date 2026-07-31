<?php
namespace Plugins\Mercadopago\Models;
use CodeIgniter\Model; 

class MercadoPagoModel extends Model 
{
	public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    public function payment_configuration(){
        return [
            'position' => 4000,
            'html' => view( 'Plugins\Mercadopago\Views\payment_configuration', [ 'config' => $this->config ] )
        ];
    }

    public function payment_button(){
        $data = [
            "title" => __("Mercado Pago"),
            "desc" => __("Pagamento Único"),
            "logo" => get_module_path(__DIR__, "Assets/img/logo.jpg"),
            "img" => get_module_path(__DIR__, "Assets/img/success.png"),
            "url" => base_url( $this->config['id']. "/index/" . uri("segment", 3) )
        ];

        return [
            'position' => 10000,
            'html' => view( 'Plugins\Mercadopago\Views\button', $data )
        ];
    }
}
