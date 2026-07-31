<?php
$product_id = uri("segment", 2);
if( get_session("frontend_template") ){
    $template = get_session("frontend_template");
}else{
    $template = get_option("frontend_template", "Stackblue");
}

$file = __DIR__."/products/product_".$product_id.".php";

if( file_exists( $file ) ){
	include $file;
}else{
	redirect_to( base_url() );
}
?>
