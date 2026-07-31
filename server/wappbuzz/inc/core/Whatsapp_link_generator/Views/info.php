<?php if ($status == "success") : ?>

	<style>
		div#qrCode canvas {
			height: auto;
			width: 100%;
			max-width: 400px;
		}
	</style>

	<div class="d-md-flex align-items-center mb-4">
		<div class="w-100 d-flex align-items-center me-3 mb-3">
			<div class="w-70 h-70">
				<img src="<?php _ec(get_file_url($account->avatar)) ?>" class="w-100 border b-r-10">
			</div>
			<div class="ms-3">
				<h3><?php _ec($account->name) ?></h3>
				<div><?php _ec($account->pid) ?></div>
			</div>
		</div>
	</div>

	<?php
	//echo json_encode($account) 
	?>

	<ul class="list-group list-group-flush b-r-10" id="app">

		<li class="list-group-item px-4 py-4">
			<div class="fw-6"><?php _e("Text Message") ?></div>
			<div class="form-group mb-2">
				<textarea rows="3" v-model="text_message" placeholder="<?php echo _e('Write your message here') ?>" class="form-control" id="text-message"></textarea>
			</div>

			<div class="input-group mb-3 mt-0">
				<input readonly v-model="text_link" type="text" id="url_generated" class="form-control">
				<div class="input-group-append">
					<button class="btn btn-primary copy_button" data-clipboard-target="#url_generated" type="button"><?php echo _e('Copy') ?></button>
				</div>
			</div>
		</li>

		<li class="list-group-item px-4 py-4">
			<div class="row">
				<div class="col-12 col-md-5">
					<div class="fw-6"><?php _e("Backgroud") ?></div>
					<div class="mb-3">
						<div class="mb-3">
							<input class="form-control" type="color" v-model="qrOptions.backgroundOptions.color" style="height: 44px;" />
						</div>
					</div>
					<div class="fw-6"><?php _e("Dots") ?></div>
					<div class="mb-3">
						<select v-model="qrOptions.dotsOptions.type" class="form-select form-select-solid">
							<option value="square"><?php _e("Square") ?></option>
							<option value="rounded"><?php _e("Rounded") ?></option>
							<option value="extra-rounded"><?php _e("Extra Rounded") ?></option>
							<option value="dots"><?php _e("Dot") ?></option>
							<option value="classy"><?php _e("Classy") ?></option>
							<option value="classy-rounded"><?php _e("Classy Rounded") ?></option>
						</select>
					</div>
					<div class="mb-3">
						<input class="form-control" type="color" v-model="qrOptions.dotsOptions.color" style="height: 44px;" />
					</div>
					<div class="fw-6"><?php _e("Corners Square") ?></div>
					<div class="mb-3">
						<select v-model="qrOptions.cornersSquareOptions.type" class="form-select form-select-solid">
							<option value="square"><?php _e("Square") ?></option>
							<option value="extra-rounded"><?php _e("Rounded") ?></option>
							<option value="dot"><?php _e("Dot") ?></option>
						</select>
					</div>
					<div class="mb-3">
						<input class="form-control" type="color" v-model="qrOptions.cornersSquareOptions.color" style="height: 44px;" />
					</div>
					<div class="fw-6"><?php _e("Corners Dots") ?></div>
					<div class="mb-3">
						<select v-model="qrOptions.cornersDotOptions.type" class="form-select form-select-solid">
							<option value="square"><?php _e("Square") ?></option>
							<option value="dot"><?php _e("Dot") ?></option>
						</select>
					</div>
					<div class="mb-3">
						<input class="form-control" type="color" v-model="qrOptions.cornersDotOptions.color" style="height: 44px;" />
					</div>
					<div class="fw-6"><?php _e("Image") ?></div>
					<div class="mb-3 mt-3">
						<div class="form-check form-switch form-check-custom form-check-solid form-check-primary d-flex d-flex justify-content-between">
							<label class="form-check-label text-gray-600" for="chatbot">
								<?php _e("Hide Dots") ?>
							</label>
							<input class="form-check-input" name="status" type="checkbox" v-model="qrOptions.imageOptions.hideBackgroundDots" id="chatbot">
						</div>
					</div>
					<div class="mb-3">
						<select v-model="qrOptions.imageOptions.imageSize" class="form-select form-select-solid">
							<option value="0.1">10%</option>
							<option value="0.2">20%</option>
							<option value="0.3">30%</option>
							<option value="0.4">40%</option>
						</select>
					</div>
					<div class="mb-3"></div>
					<div class="mb-3">
						<button type="button" class="btn btn-primary btn-block w-100 fileinput-button">
							<i class="fas fa-upload"></i><?php _e("Add/Change Image") ?>
							<input type="file" ref="qrImage" @change="imageChange($event)" class="form-control" id="image">
						</button>
						<button type="button" @click="removeImage()" class="btn btn-danger btn-block w-100 fileinput-button mt-3">
							<i class="fas fa-trash"></i><?php _e("Remove Image") ?>
						</button>
					</div>
				</div>
				<div class="col d-flex flex-column align-items-center justify-content-center">

					<div id="qrCode" ref="qrCode" class="d-flex align-items-center justify-content-center">
					</div>

					<div class="row">
						<button type="button" @click="downloadQr()" class="btn btn-primary w-100 mt-3">
							<i class="fas fa-download"></i><?php _e("Download QR") ?>
						</button>
					</div>

				</div>
			</div>
		</li>

	</ul>

	<div class="fs-12 text-gray-400 mt-4 text-end"><?php _e(sprintf("Last update: %s", datetime_show($account->changed))) ?>
	</div>


	<script type="module">
		import {
			createApp
		} from '<?php _e(get_module_path(__DIR__, "Assets/plugins/vue/vue.esm-browser.prod.js")) ?>';

		import QRCodeStyling from '<?php _e(get_module_path(__DIR__, "Assets/plugins/qr/qr-code-styling.js")) ?>'


		createApp({
			data() {
				return {
					text_message: '',
					text_link: '',
					phone: '<?php _ec($account->username) ?>',
					qrCode: null,
					tout: null,
					qrOptions: {
						width: 400,
						height: 400,
						type: "canvas",
						data: '',
						image: "",
						imageOptions: {
							hideBackgroundDots: true,
							imageSize: 0.2
						},
						dotsOptions: {
							color: "#000000",
							type: "square"
						},
						cornersSquareOptions: {
							color: "#000000",
							type: "square"
						},
						cornersDotOptions: {
							color: "#000000",
							type: "square"
						},
						backgroundOptions: {
							color: "#ffffff",
						},
					}
				}
			},
			watch: {
				['text_message']: function() {
					var that = this;
					clearTimeout(this.tout);
					this.tout = setTimeout(function() {
						var link = 'https://api.whatsapp.com/send?phone=' + that.phone + (that.text_message.trim() != '' ? '&text=' + encodeURI(that.text_message) : '');
						that.qrOptions.data = link;
						that.text_link = link;
						that.qrCode.update(that.qrOptions);
					}, 800);
				},
				...[
					"qrOptions.dotsOptions.color",
					"qrOptions.dotsOptions.type",
					"qrOptions.cornersSquareOptions.color",
					"qrOptions.cornersSquareOptions.type",
					"qrOptions.cornersDotOptions.color",
					"qrOptions.cornersDotOptions.type",
					"qrOptions.backgroundOptions.color",
					"qrOptions.image",
					"qrOptions.imageOptions.imageSize",
					"qrOptions.imageOptions.hideBackgroundDots",
				].reduce((acc, currentKey) => {
					acc[currentKey] = function(newValue) {
						var that = this;
						clearTimeout(this.tout);
						this.tout = setTimeout(function() {
							that.qrCode.update(that.qrOptions);
						}, 200);
					}
					return acc
				}, {}),
			},
			created() {
				var clipboard = new ClipboardJS('.copy_button');
				clipboard.on('success', function(e) {
					e.clearSelection();
				});
				this.qrOptions.data = 'https://api.whatsapp.com/send?phone=' + this.phone;
				this.text_link = this.qrOptions.data;
				this.qrCode = new QRCodeStyling(this.qrOptions);
			},
			mounted: function() {
				this.qrCode.append(this.$refs["qrCode"]);
			},
			methods: {
				imageChange(event) {
					var that = this,
						imageEle = this.$refs["qrImage"];

					if (imageEle.files && imageEle.files[0]) {
						var fr = new FileReader();
						fr.onload = function(e) {
							//console.log(e.target.result);
							that.qrOptions.image = e.target.result;
						}
						fr.readAsDataURL(imageEle.files[0])
					}
				},
				removeImage() {
					this.qrOptions.image = '';
				},
				downloadQr() {
					this.qrCode.download({
						name:'<?php _ec($account->name) ?>_qrcode',
						extesion: 'png'
					});
				}
			}

		}).mount('#app');
	</script>



<?php else : ?>

	<?php if (post("account") != "") : ?>
		<div class="text-center">
			<div class="fs-70 text-danger"><i class="fad fa-exclamation-triangle"></i></div>
			<h3><?php _e("An Unexpected Error Occurred") ?></h3>
			<div><?php _e($message) ?></div>
		</div>

	<?php else : ?>
		<?php _ec($this->include('Core\Whatsapp\Views\empty'), false); ?>
	<?php endif ?>

<?php endif ?>