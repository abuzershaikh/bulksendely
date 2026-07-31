<div class="row justify-content-center">
	<div class="col-xl-7 col-lg-10">
		<nav class="navbar navbar-static-top navbar-expand-lg header-sticky justify-content-between">
      		<a class="navbar-brand" href="<?php _ec( base_url() )?>"><img class="logo auth" src="<?php _ec( get_option("website_logo_color", base_url("assets/img/logo-color.svg")) )?>" alt="logo"></a>
        </nav>
        <form class="actionForm" action="<?php _ec( base_url("auth/signup") )?>" data-redirect="<?php _ec( base_url("login") )?>" method="POST">
			<div class="row">
				<div class="section-title m-0">
					<span class="sub-title"><?php _e("Welcome")?></span>
					<h2 class="title"><?php _e("Sign up")?></h2>
					<p class=""><?php _e("Let's get your account set up")?></p>
				</div>
			  	<div class="form-group col-md-12">
			    	<input type="text" class="form-control" name="fullname" placeholder="<?php _e("Fullname")?>" value="<?php _ec( post("fullname") )?>">
			    	<span class="focus-border"></span>
			  	</div>
			  	<div class="form-group col-md-12">
			    	<input type="text" class="form-control" name="username" placeholder="<?php _e("Enter username")?>">
			    	<span class="focus-border"></span>
			  	</div>
			  	<div class="form-group col-md-12">
			    	<input type="text" class="form-control" name="email" placeholder="<?php _e("Enter email")?>" value="<?php _ec( post("email") )?>">
			    	<span class="focus-border"></span>
			  	</div>
			  	<?php if(get_option('otp_status', 0) == 1): ?>
			  	<div class="form-group col-md-12">
			    	<select name="code" id="code" class="form-control form-select auto-select-cc">
			    		<?php if(!empty(cc_code())){
			    			foreach (cc_code() as $code => $name) { ?>
			    		<option value="<?php _e( $code )?>"><?php _e( $name )?></option>
			    		<?php }} ?>
			    	</select>
			  	</div>
			  	<div class="form-group col-md-12">
			    	<input class="form-control" type="text" id="phone" onInput="edValueKeyPress()" name="number" placeholder="<?php _e("WhatsApp Number")?>">
			    	<span class="focus-border"></span>
			  	</div>
			  	<div class="form-group col-md-12 otp_section" style="display:none;">
			    	<span id="phone_error"></span>
			    	<span><a id="otpc" onclick="sendotp()" class="btn btn-info" style="font-size: 11px; margin-bottom: 5%;">Send OTP</a></span>
			    	<input class="form-control" id="otp_code_input" onInput="otp_code_verfication()" type="number" name="otp" placeholder="<?php _e("Enter OTP")?>">
			    	<span id="verify_response"></span>
			  	</div>
			  	<?php endif; ?>
			  	<div class="form-group col-md-12">
			    	<input type="password" class="form-control" name="password" placeholder="<?php _e("Password")?>">
			    	<span class="focus-border"></span>
			  	</div>
			  	<div class="form-group col-md-12">
			    	<input type="password" class="form-control" name="confirm_password" placeholder="<?php _e("Confirm Password")?>">
			    	<span class="focus-border"></span>
			  	</div>
			  	<div class="form-group col-md-12">
			  		<select name="timezone" class="form-control form-select auto-select-timezone">
			  			<option value=""><?php _e("Select timezone")?></option>
                    	<?php foreach ( tz_list() as $key => $value): ?>
                    		<option value="<?php _e( $key ) ?>" <?php _e( get_user("timezone")==$key?"selected":"" )?> ><?php _e( $value )?></option>
                    	<?php endforeach ?>
                    </select>
                </div>
			  	<div class="form-group col-md-12 form-check mx-3">
			    	<input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms">
			    	<label class="form-check-label ps-1" for="agree_terms"><?php _e("Accept Terms & Conditions")?></label>
			  	</div>
			  	<?php if(get_option('google_recaptcha_status', 0)){?>
				<div class="g-recaptcha  mb-3" data-sitekey="<?=get_option('google_recaptcha_site_key', '')?>"></div>
		    	<script src="https://www.google.com/recaptcha/api.js" async defer></script>
				<?php }?>
			  	<div class="show-message mb-2"></div>
			  	<div class="col-md-12">
			  		<?php if(get_option('otp_status', 0) == 1): ?>
			    	<button type="submit" id="login" onclick="otp_code_verfication()" class="btn btn-primary w-100 mb-3"><?php _e("Sign up")?></button>
			    	<?php else: ?>
			    	<button type="submit" class="btn btn-primary w-100 mb-3"><?php _e("Sign up")?></button>
			    	<?php endif; ?>
			  	</div>
			  	<div class="col-md-12">
			    	<hr>
			    	<p class="mb-0"><?php _e("Already have an account?")?> <a href="<?php _ec( base_url("login") )?>"> <?php _e("Login")?></a></p>
			  	</div>
			</div>
		</form>
	</div>
</div>

<?php if(get_option('otp_status', 0) == 1): ?>
<style>
.disabled {
  pointer-events: none;
}
</style>
<script type="text/javascript">
var otpMessageTemplate = <?php echo json_encode(get_option('otp_message_template', 'Your OTP to Validate Account is {otp}')); ?>;

function otp_code_verfication() {
    var otp_value = document.getElementById("otp_code_input");
    var otp_code_input = otp_value.value;
    var count = otp_code_input.toString().length;
    var login = document.getElementById("login");
    login.disabled = true;
    $("#login").prop("disabled", true);
    if(count > 5){
        var code_generated = document.getElementById("otp_strorage").value;
        if(code_generated == otp_code_input){
            var lblValue = document.getElementById("verify_response");
            var elementExists = document.getElementById('success');
            var warning = document.getElementById('warning');
            if(!elementExists){
                if(warning){ warning.remove(); }
                $('#verify_response').append('<i class="fa fa-check green-color" id="success" style="color: #00c700;"></i>');
                $('.otp_section').append('<input type="text" id="otp_verified" style="display: none;" name="opt_verified" value="1"/>');
            }
            document.getElementById("otp_code_input").disabled = true;
            login.disabled = false;
            $("#login").prop("disabled", false);
        } else {
            var elementExists = document.getElementById('warning');
            if(!elementExists){
                $('#verify_response').append('<i class="fa fa-exclamation-triangle red-color" style="color: #f30b00;" id="warning"></i>');
            }
        }
    }
}

function sendotp() {
    var otp = Math.floor(100000 + Math.random() * 900000);
    var phone = document.getElementById("phone").value;
    var country_code = document.getElementById('code').value;
    var lblValue = document.getElementById("otpc");
    if(!country_code){
        var phone_error = document.getElementById("phone_error");
        phone_error.innerText = "Please choose country";
    } else {
        var phone_number_raw = country_code + phone;
        phone_number = phone_number_raw.replace(/\D/g,"");
        var number = phone_number;
        if (number.substr(0, 2) == '55') {
            var ddd = number.substr(2, 2);
            if (ddd >= 31 && number.length >= 13) {
                number = number.substr(0, 4) + number.substr(5);
            }
        }
        var elementExists = document.getElementById('otp_strorage');
        if(elementExists){
            document.getElementById("otp_strorage").value = otp;
        } else {
            $('.otp_section').append('<input type="text" id="otp_strorage" style="display: none;" name="opt_code" value="'+otp+'"/>');
        }

        var otpMessage = otpMessageTemplate.replace('{otp}', otp);
        $.ajax({
            url: 'auth/sendotp',
            type: 'POST',
            dataType: 'json',
            data: { number: number, message: otpMessage },
            success: function(response) {
                if (response.status == 'success') {
                    lblValue.innerText = 'Sent!';
                } else {
                    lblValue.innerText = 'Error sending!';
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
            }
        });
    }
}

function edValueKeyPress() {
    var edValue = document.getElementById("phone");
    var s = edValue.value;
    var count = s.toString().length;
    if(count > 5){
        $(".otp_section").show(999);
    }
}

$("#otpc").click(function() {
    $("#otpc").addClass("disabled");
});
</script>
<?php endif; ?>