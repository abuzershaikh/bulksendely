<li class="nav-item me-0">
     <label for="type_poll" class="nav-link bg-active-primary text-gray-700 px-4 py-3 b-r-6 text-active-white <?php _ec( get_data($result, "type") == 4?"active":"" ) ?>" data-bs-toggle="pill" data-bs-target="#wa_poll" type="poll" role="tab"><?php _e("Poll")?></label>
     <input class="d-none" type="radio" name="type" id="type_poll" <?php _ec( (get_data($result, "type") == 4)?"checked='true'":"" ) ?> value="4">
</li>