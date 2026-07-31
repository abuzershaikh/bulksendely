<div class="login signup container m-b-100 border-top">
	
	<div class="mw-1000 shadow mx-auto b-r-20">
		<form class="actionForm" action="<?php _ec( base_url("auth/signup") )?>" data-redirect="<?php _ec( base_url("login") )?>" method="POST">
			<div class="row no-gutters">
				<div class="col-md-6 justify-content-center align-items-center">
					<div class="d-flex justify-content-center align-items-center h-100">
						<div class="p-50 w-100">
							<div class="headline mb-4">
								<h2 class="fs-25 fw-6 mb-0"><?php _e("Signup")?></h2>
								<div class="text-gray-600"><?php _e("Let's get your account set up")?></div>
							</div>

							<div class="mb-3">
								<input type="text" name="fullname" class="form-control h-45 b-r-6 border-gray-200" value="" placeholder="<?php _e("Fullname")?>">
							</div>

							<div class="mb-3">
								<input type="text" name="username" class="form-control h-45 b-r-6 border-gray-200" value="" placeholder="<?php _e("Username")?>">
							</div>

							<div class="mb-3">
								<input type="text" name="email" class="form-control h-45 b-r-6 border-gray-200" value="" placeholder="<?php _e("Email")?>">
							</div>

							<?php if(get_option('otp_status', 0) == 1): ?>
							<div class="mb-3">
								<select name="code" id="code" class="form-control h-45 b-r-6 border-gray-200 auto-select-cc">
									<?php if(!empty(cc_code())){
										foreach (cc_code() as $code => $name) { ?>
									<option value="<?php _e( $code )?>"><?php _e( $name )?></option>
									<?php }} ?>
								</select>
							</div>

							<div class="mb-3">
								<input class="form-control h-45 b-r-6 border-gray-200" type="text" id="phone" onInput="edValueKeyPress()" name="number" placeholder="<?php _e("WhatsApp Number")?>">
							</div>

							<div class="form-group otp_section" style="display:none;">
								<span id="phone_error"></span>
								<span><a id="otpc" onclick="sendotp()" class="btn btn-info" style="font-size: 11px; margin-bottom: 5%; margin-top: -12px;">Send OTP</a></span>
								<input class="form-control h-45 b-r-6 border-gray-200" id="otp_code_input" onInput="otp_code_verfication()" type="number" name="otp" placeholder="<?php _e("Enter OTP")?>">
								<span id="verify_response"></span>
							</div>
							<?php endif; ?>

							<div class="mb-3">
								<input type="password" name="password" class="form-control h-45 b-r-6 border-gray-200" value="" placeholder="<?php _e("Password")?>">
							</div>

							<div class="mb-3">
								<input type="password" name="confirm_password" class="form-control h-45 b-r-6 border-gray-200" value="" placeholder="<?php _e("Confirm Password")?>">
							</div>

							<div class="mb-3">
								<select class="form-control h-45 b-r-6 border-gray-200 text-gray-600" name="timezone">
									<option value=""><?php _e("Select timezone")?></option>
									<?php foreach ( tz_list() as $key => $value): ?>
			                    		<option value="<?php _e( $key ) ?>" <?php _e( get_user("timezone")==$key?"selected":"" )?> ><?php _e( $value )?></option>
			                    	<?php endforeach ?>
								</select>
							</div>

							<div class="mb-3">
								<div class="d-flex justify-content-between">
									<div class="form-check">
									  	<input class="form-check-input m-t-5" type="checkbox" value="1" name="agree_terms" id="agree_terms">
									  	<label class="form-check-label" for="remember">
									    	<?php _e("Accept Terms & Conditions")?>
									  	</label>
									</div>
									
								</div>
							</div>

							<?php if(get_option('google_recaptcha_status', 0)){?>
							<div class="g-recaptcha  mb-3" data-sitekey="<?=get_option('google_recaptcha_site_key', '')?>"></div>
					    	<script src="https://www.google.com/recaptcha/api.js" async defer></script>
							<?php }?>

							<div class="show-message mb-2"></div>

							<div class="mb-3">
								<?php if(get_option('otp_status', 0) == 1): ?>
								<button type="submit" id="login" onclick="otp_code_verfication()" class="btn mb-2 btn-dark w-100 mb-md-3 fw-6 text-uppercase fs-16">
									<?php _e("Signup")?>
								</button>
								<?php else: ?>
								<button type="submit" class="btn mb-2 btn-dark w-100 mb-md-3 fw-6 text-uppercase fs-16">
									<?php _e("Signup")?>
								</button>
								<?php endif; ?>
							</div>


							<div>
								<?php _e("Already have an account?")?> <a href="<?php _ec( base_url("login") )?>"><?php _e("Login")?></a>
							</div>
						</div>
					</div>
				</div>
				<div class="col-md-6 ">
					<?php require_once "slogan_signup.php"; ?>
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