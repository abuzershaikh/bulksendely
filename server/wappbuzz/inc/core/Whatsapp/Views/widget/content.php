<label class="form-label"><?php _e("Media file")?></label>
<?php echo view_cell('\Core\File_manager\Controllers\File_manager::mini', ["type" => "image,video,doc,pdf,audio,other", "select_multi" => 0]) ?>

<script type="text/javascript">
    $(function(){
        File_manager.loadSelectedFiles(["<?php _ec( remove_file_path(  get_data($result, "media") ) )?>"]);
    });
</script>