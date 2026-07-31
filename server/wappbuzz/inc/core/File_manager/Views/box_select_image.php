<div class="fm-select-image border p-20 d-inline-block rounded b-r-10 position-relative">
	<?php if ($value != ""): ?>
	<a href="javascript:void(0);" class="fm-sm-remove position-absolute r-27 t-27 text-gray-600 zindex-1 fs-13 b-r-50 border bg-white w-20 h-20 text-center"><i class="fal fa-times p-0"></i></a>
	<?php endif ?>
    <div class="fm-sm-box-img <?php _e($name)?> mb-3 miw-<?php _e($min_width)?> mw-<?php _e($max_width)?> mih-<?php _e($min_width)?> b-r-10 bg-gray-100 bg-select-file bg-add-file btnOpenFileManager cursor-pointer" data-select-multi="0" data-type="image" data-id="<?php _e($name)?>">
        <?php if ($value != ""): ?>
            <img src="<?php _ec( $value )?>" class="w-100">
        <?php endif ?>
    </div>
    <input type="text" name="<?php _e($name)?>" id="<?php _e($name)?>" class="form-control form-control-solid d-none <?php _e($name)?>" placeholder="<?php _e("Select file")?>" value="<?php _ec( $value )?>">
    <div class="input-group w-100 ">
        <button type="button" class="btn btn-light-primary btn-sm btnOpenFileManager w-100 b-r-10" data-select-multi="0" data-type="image" data-id="<?php _e($name)?>">
            <i class="fad fa-folder-open p-r-0 n"></i> <?php _e( "Select" )?>
        </button>
    </div>
</div>