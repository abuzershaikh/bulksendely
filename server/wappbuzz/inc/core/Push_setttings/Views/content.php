<div class="container my-5">
        <?php
        if( isset($block_tab['content']) ){
            _ec( $block_tab['content'] );
        }
        ?>
        <div class="m-t-25">
            <button type="submit" class="btn btn-primary"><?php _e("Save")?></button>
        </div>
</div>