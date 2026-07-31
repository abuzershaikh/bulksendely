<?php $platforms = db_fetch("*", TB_PLATFORM, ["status" => 1]); ?>

<?php

if (count($platforms) <= 1) {
    if( file_exists(__DIR__. "/products/product_".$platforms[0]->platform_id.".php") ){
        include_once "products/product_".$platforms[0]->platform_id.".php"; 
    }
    
} else {
    include_once "main.php"; 
}
?>