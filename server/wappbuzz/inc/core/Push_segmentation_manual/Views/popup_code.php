<div class="modal fade" id="addSegmentationCode" tabindex="-1" role="dialog">
  	<div class="modal-dialog modal-lg modal-dialog-centered">
	    <div class="modal-content">
      		<div class="modal-header">
		        <h5 class="modal-title"><i class="<?php _e( $config['icon'] )?>" style="color: <?php _e( $config['color'] )?>"></i> <?php _e("Segment code")?></h5>
		         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
	      	</div>
	      	<div class="modal-body shadow-none p-0 h-175">
	      		<?php if ($result): ?>
		            <textarea class="code-editor h-175 w-100 d-none" name="embed_code" rows="4"><script>
    (webpush = window.webpush || []).push(['addToSegment', <?php _ec( $result->id )?>, callbackFunction]);
    
    function callbackFunction(result){
        console.log(result.status) // True or False
        //Your Code
    }
</script></textarea>
	      		<?php endif ?>
	      	</div>
	    </div>
  	</div>
</div>

<style type="text/css">
	
	#addSegmentationCode .ace_editor{
		width: 100%!important;
	}

</style>

<script type="text/javascript">
	$(function(){
		setTimeout(function(){
			Core.code_editor();
		}, 300);
	});
</script>