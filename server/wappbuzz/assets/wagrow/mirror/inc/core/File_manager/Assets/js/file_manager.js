"use strict";
function File_manager() {
	var SELF = this;
	var timeout;
	var FM = $(".file-manager");
	var FM_List = $(".fm-list");
	var FM_Widget = FM.find(".fm-widget");
	var FM_Modal = FM.find(".file-manager-modal");
	var FM_Selected = $(".fm-selected-media");
	var FM_Progress_Bar = $(".fm-progress-bar");
	var FM_Lazy = $(".lazy");
	var BODY = $("body");
	var HTML = $("html");

	this.init = function () {
		SELF.upload();
		SELF.action();
		SELF.Dropbox();
		SELF.OneDrive();
		SELF.drag_and_drop_upload();
		SELF.drag_and_drop_select();
		SELF.dialogFileManager();

		$(document).on('core_ajax_load_scroll_done', function(e, container) {
			if (window.FM_pendingSelectFile && container.closest('.fm-widget').length > 0) {
				var matchUrl = window.FM_pendingSelectFile.replace(/\\/g, '/');
				var $target = container.find('.fm-list-item').filter(function() {
					var fileUrl = ($(this).attr('data-file') || "").replace(/\\/g, '/');
					return fileUrl.indexOf(matchUrl) !== -1;
				});
				
				if ($target.length > 0) {
					window.FM_pendingSelectFile = null;
					container.find('.fm-list-item').removeClass("active").find('input').prop("checked", false);
					$target.first().addClass("active").find('input').prop("checked", true);
					
					if (typeof updatePreview === 'function') {
						setTimeout(updatePreview, 100);
					}
				}
			}
		});
	},

		this.action = function () {
			$(".file-manager").find(".fm-input-search").keyup(function (e) {
				clearTimeout(timeout);
				timeout = setTimeout(function () {
					e.preventDefault();
					Core.ajax_load_scroll(true);
				}, 500);
				return false;
			});

			$(document).find(".fm-input-filter").on("change", function (e) {
				e.preventDefault();
				Core.ajax_load_scroll(true);
			});

			$(document).on("click", ".file-manager .fm-list-item:not(.fm-folder-item,.fm-list-item-search)", function (e) {
				if (e.target.tagName != "INPUT") {
					$(this).find("input").trigger("click");
				}
			});

			$(document).on("dblclick", ".fm-list-item:not(.fm-list-item-search)", function () {
				var that = $(this);
				if (!that.hasClass("active")) {
					SELF.addFiles(that);
				} else {
					SELF.unselectFiles(that);
				}
			});

			$(document).on("click", ".file-manager .fm-list-item:not(.fm-list-item-search) input", function () {
				var that = $(this).parents(".fm-list-item");
				if (!that.hasClass("active")) {
					SELF.addFiles(that);
				} else {
					SELF.unselectFiles(that);
				}
			});

			$(document).on("click", ".fm-selected-media .fm-list-item .remove", function () {
				SELF.unselectFiles($(this));
			});

			$(document).on('click', '.file-manager .fm-select-all', function () {
				var that = $(this);

				if ($('input:checkbox').hasClass('fm-check-item')) {
					if (!that.hasClass('checked')) {
						$('.file-manager .fm-list-item input:checkbox').prop('checked', true);
						$('.file-manager .fm-list-item input:checkbox').parents('.fm-list-item').addClass('active');
						that.addClass('checked');
					} else {
						$('.file-manager .fm-list-item input:checkbox').prop('checked', false);
						$('.file-manager .fm-list-item input:checkbox').parents('.fm-list-item').removeClass('active');
						that.removeClass('checked');
					}
				}
				return false;
			});

			$(document).on('click', '.file-manager .fm-delete-all', function () {
				var that = $(this);
				var form = $('.file-manager .fm-form');
				var data = form.serialize() + '&' + $.param({ csrf: csrf });
				var url = that.data('url') || (PATH + 'file_manager/delete');

				if (data.indexOf('ids') != -1) {
					var buttonContent = that.html();
					that.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
					
					$.post(url, data, function (result) {
						that.html(buttonContent).prop('disabled', false);
						Core.ajax_load_scroll(true);
						Core.notify(result.message, result.status);
						$('.file-manager .fm-select-all').removeClass('checked');
					}, 'json').fail(function(jqXHR, textStatus, errorThrown) {
						that.html(buttonContent).prop('disabled', false);
						console.error("Delete failed:", textStatus, errorThrown);
						console.error("Response:", jqXHR.responseText);
						Core.notify("Failed to delete items: " + errorThrown, "error");
					});
				} else {
					Core.notify("Please select an item to delete", "error");
				}
			});
		},

		this.dialogFileManager = function () {
			$(document).on('click', '.btnOpenFileManager', function (e) {
				e.preventDefault();
				var that = $(this);
				var id = that.data('id');
				var select = that.data('select-multi');
				var type = that.data('type');
				var id = that.data('id');
				var url = PATH + 'file_manager/popup/' + type + '/' + select + '/' + id

				$(".file-manager-modal").remove();
				if (!that.hasClass("disabled")) {
					that.addClass("disabled");
					$.get(url, function (data) {
						BODY.append(data);
						$(".file-manager-modal").modal('show');
						Core.call_load_scroll();
						Core.ajax_load_scroll();
						that.removeClass("disabled");
					}).fail(function (jqXHR, textStatus, errorThrown) {
						that.removeClass("disabled");
						console.error("FileManager GET Error:", jqXHR.status, textStatus, errorThrown);
						console.error("Response:", jqXHR.responseText);
						Core.notify("Failed to load File Manager. HTTP: " + jqXHR.status + " " + errorThrown, "error");
					});
				}
				return false;
			});

			$(document).on('click', '.btnOpenMediaInfo', function (e) {
				e.preventDefault();
				var id = $(this).data('id');
				var url = PATH + 'file_manager/media_info/' + id;

				$("#offcanvasMediaInfo").remove();
				$.get(url, function (data) {
					BODY.append(data);
					var offcanvasMediaInfo = document.getElementById('offcanvasMediaInfo')
					var bsOffcanvas = new bootstrap.Offcanvas(offcanvasMediaInfo)
					bsOffcanvas.show();
					setTimeout(function () {
						Core.emoji();
					}, 1000);
				});
				return false;
			});

			$(document).on('click', '.btnAddFiles', function () {
				var transfer = $(this).data("transfer");
				if ($(".file-manager").length > 0) {
					$(".file-manager").find(".fm-list-item").each(function (index, value) {
						var that = $(this);
						if (that.find("input").is(":checked")) {
							var media = that.data("file");
							if (transfer != undefined && transfer != "") {
								$("#" + transfer).val(media).trigger("change");
								$("[name='" + transfer + "']").val(media).trigger("change");
								$("img." + transfer).attr('src', media);
							} else {
								//SELF.addFiles(media);
							}
						}
					});
				}
			});

			$(document).on('click', '.btnOpenSearchMedia', function (e) {
				e.preventDefault();
				var that = $(this);
				var folder_id = $(document).find(".file-manager .fm-input-folder").val();
				var url = PATH + 'file_manager/popup_search_media?folder_id=' + folder_id;


				$(".file-manager-search-media-modal").remove();
				if (!that.hasClass("disabled")) {
					that.addClass("disabled");
					$.get(url, function (data) {
						BODY.append(data);
						$(".file-manager-search-media-modal").modal('show');
						that.removeClass("disabled");
					});
				}
				return false;
			});
		},

		this.drag_and_drop_select = function () {
			$(".fm-selected-media").find(".items").sortable({
				containment: "parent",
				cursor: "-webkit-grabbing",
				distance: 10,
				items: ".fm-list-item",
				placeholder: "fm-list-item item--placeholder",

				stop: function (event, ui) {
					if ($(".fm-selected-media").find(".fm-list-item").length > 0) {
						$(".fm-selected-media").removeClass('droppable');
					} else {
						$(".fm-selected-media").addClass('droppable');
					}
				},

				receive: function (event, ui) {
					ui.helper.remove();
					SELF.addFiles(ui.item);
				},

				update: function () {
					if ($(".fm-selected-media").find(".fm-list-item").length == 0) {
						$(".fm-selected-media").addClass('none');
					}
				}
			});

			FM_Widget.on("mouseover", ".fm-list-item:not(.active,.fm-folder-item)", function (el) {
				var that = $(this);
				that.draggable({
					addClasses: false,
					connectToSortable: $(".fm-selected-media").find(".items"),
					containment: "document",
					revert: "invalid",
					revertDuration: 200,
					distance: 10,
					appendTo: $(".fm-selected-media").find(".items"),
					cursor: "-webkit-grabbing",
					cursorAt: {
						left: 35,
						top: 35
					},
					zIndex: 1000,

					helper: function () {
						var item = that.clone();

						item.find("input").prop('checked', true).hide();
						item.find(".fm-list-media").addClass("rounded");
						item.addClass("border rounded").removeClass('mb-4 ui-draggable-handle ui-droppable active');
						item.append('<button type="button" href="javascript:void(0)" class="remove text-danger"><i class="fal fa-times"></i></button>');

						var copy_item = item.clone();
						copy_item.appendTo(".fm-selected-media .items");
						copy_item.remove();

						return item;
					},

					start: function (event, ui) {
						$(".fm-selected-media").find(".items").sortable("disable");
						FM_List.addClass("draggable");
						$(".fm-selected-media").find(".drophere").show();
						$(".fm-selected-media").find(".drophere .has-action").show();
						$(".fm-selected-media").find(".drophere .no-action").hide();
					},

					stop: function (event, ui) {
						FM_List.removeClass("draggable");
						$(".fm-selected-media").find(".drophere .has-action").hide();
						$(".fm-selected-media").find(".drophere .no-action").show();
						$(".fm-selected-media").find(".items").sortable("enable");
						SELF.checkSelectedEmpty();
						that.draggable("destroy");
					}
				});
			});

			$(document).on("mouseout", ".fm-widget .fm-list-item", function (el) {
				var that = $(this);
				that.removeClass("ui-draggable-handle");
			});
		},

		this.addFiles = function (file) {

			if (!file.hasClass("fm-folder-item")) {
				var id = file.attr("data-id");
				var select_multi = $(".file-manager").attr("data-select-multi");

				if ($(".btnAddFiles").length == 0 || $(".btnAddFiles").data("transfer") == "") {
					if (select_multi != undefined && select_multi == 0) {
						$(".file-manager").find(".fm-list-item").removeClass("active");
						$(".file-manager").find(".fm-list-item input:checkbox").prop('checked', false);
						$(".fm-selected-media .items").html("");
					}
				}

				if (file.hasClass("ui-draggable-handle")) {
					file.draggable("destroy");
				}

				file.removeClass("ui-draggable-handle");

				var item = file.clone();

				file.addClass("active").find("input").prop('checked', true);
				item.find("input").attr("name", "medias[]").prop('checked', true).hide();
				item.find(".fm-list-media").addClass("rounded");
				item.addClass("border rounded").removeClass('mb-4 ui-draggable-handle ui-droppable active');
				item.append('<button type="button" href="javascript:void(0)" class="remove text-danger"><i class="fal fa-times"></i></button>');

				if ($(".btnAddFiles").length == 0 || $(".btnAddFiles").data("transfer") == "") {
					var copy_item = item.clone();
					copy_item.appendTo(".fm-selected-media .items");
				}

				SELF.checkSelectedEmpty();

				return item;
			}
		},

		this.loadSelectedFiles = function (medias) {
			var that = $(".fm-widget");
			if ($(".fm-widget").length == 0) {
				that = $(".fm-selected-media.fm-selected-mini");
			}

			if (that.length == 0) {
				Core.overplay("hide");
				return false;
			}

			var select_multi = that.attr("data-select-multi");
			var params = { csrf: csrf, medias: medias };
			var action = PATH + "file_manager/load_selected_files";

			Core.ajax_post(that, action, params, function (result) {
				Core.overplay("hide");

				if (select_multi != undefined && select_multi == 0) {
					$(".fm-selected-media .items").html(result);
				} else {
					$(".fm-selected-media .items").append(result);
				}

				SELF.checkSelected();
				SELF.checkSelectedEmpty();
				SELF.lazy();
			});
		}

	this.unselectFiles = function (file) {
		var id = file.attr('data-id');
		if (!file.hasClass("fm-list-item")) {
			var id = file.parents(".fm-list-item").attr('data-id');
		}

		file.removeClass("active");
		file.find("input").prop('checked', false);
		file.parents(".fm-list-item").removeClass("active");
		file.find("input").prop('checked', false);
		$('.file-manager .fm-select-all').removeClass('checked');
		$(".fm-selected-media .fm-list-item[data-id='" + id + "']").remove();

		var item = $(".file-manager .fm-widget .fm-list-item[data-id='" + id + "']");
		item.removeClass("active").find("input").prop('checked', false);
		SELF.checkSelectedEmpty();
		return file;
	},

		this.checkSelected = function () {
			$(".fm-selected-media").find(".fm-list-item").each(function (index, value) {
				var that = $(this);
				var id = that.attr("data-id");
				FM_Widget.find(".fm-list-item[data-id='" + id + "']").addClass("active").find("input").prop('checked', true);
			});
		},

		this.checkSelectedEmpty = function () {
			if ($(".fm-selected-media").find(".items .fm-list-item").length > 0) {
				$(".fm-selected-media").find(".drophere").hide();
			} else {
				$(".fm-selected-media").find(".drophere").show();
			}
		},

		this.unselectAllFiles = function () {
			FM_Widget.find(".fm-list-item").removeClass("active");
			FM_Widget.find("input").prop('checked', false);
			$(".fm-selected-media").find(".fm-list-item").remove();
			SELF.checkSelectedEmpty();
		},

		this.upload = function (id) {
			$(document).on('change', '#fileupload, .fm-upload-file', function () {
				var that = $(this);
				var folder = $(".file-manager .fm-input-folder").val();
				var files = this.files;
				var totalfiles = files.length;

				$(".fm-progress-bar").show().css({ width: '10%' }); 

				var processFiles = async function() {
					var form_data = new FormData();
					form_data.append("csrf", csrf);
					form_data.append("folder", folder);

					for (var index = 0; index < totalfiles; index++) {
						var file = files[index];
						// Compress large images to avoid slow upload and display
						if (file.type && file.type.startsWith('image/') && file.size > 500000) {
							try {
								var compressedFile = await window.FM_compressImage(file);
								form_data.append("files[]", compressedFile);
							} catch (e) {
								form_data.append("files[]", file);
							}
						} else {
							form_data.append("files[]", file);
						}
					}
					that.val('');
					SELF.uploadData(form_data);
				};

				processFiles();
				return false;
			});

			//Go to folder
			$(document).on("click", ".fm-folder-item", function () {
				var that = $(this);
				if (!that.hasClass("disabled")) {
					that.addClass("disabled");
					var folder = $(this).attr("data-folder-id");
					$(document).find(".file-manager .fm-input-folder").val(folder);
					Core.ajax_load_scroll(true);
					setTimeout(function () {
						that.removeClass("disabled");
					}, 1000);

				}
				return false;
			});

			//New folder
			FM.on("click", ".fm-btn-new-folder", function () {
				var url = FM.find(".fm-input-new-folder").val();
				FM.find(".fm-input-new-folder").val('');
				FM.find(".fm-box-new-folder").removeClass("active").hide();
				SELF.newFolder(url);
				return false;
			});

			FM.on("click", ".fm-open-new-folder", function () {
				var elment = $(".fm-box-new-folder");
				if (elment.hasClass("active")) {
					elment.removeClass("active").hide();
				} else {
					elment.addClass("active").show();
				}
			});

			//Upload by url
			FM.on("click", ".fm-btn-upload-by-url", function () {
				var url = FM.find(".fm-input-upload-by-url").val();
				FM.find(".fm-input-upload-by-url").val('');
				FM.find(".fm-box-upload-by-url").removeClass("active").hide();
				SELF.saveFile(url);
				return false;
			});

			FM.on("click", ".fm-open-upload-by-url", function () {
				var elment = $(".fm-box-upload-by-url");
				if (elment.hasClass("active")) {
					elment.removeClass("active").hide();
				} else {
					elment.addClass("active").show();
				}
			});
		},

		this.drag_and_drop_upload = function (id) {
			HTML.on("dragover", function (e) {
				e.preventDefault();
				e.stopPropagation();
				$(".fm-upload-area-overplay").hide();
			});

			HTML.on("drop", function (e) {
				e.preventDefault(); e.stopPropagation();
			});

			$('.fm-upload-area-overplay').on('dragleave', function (e) {
				e.stopPropagation();
				e.preventDefault();
				$(".fm-upload-area-overplay").hide();
			});

			FM.on('dragenter', function (e) {
				e.stopPropagation();
				e.preventDefault();
				$(".fm-upload-area-overplay").show();
			});

			$('.fm-upload-area-overplay').on('dragover', function (e) {
				e.stopPropagation();
				e.preventDefault();
				$(".fm-upload-area-overplay").show();
			});

			FM.on('drop', function (e) {
				e.stopPropagation();
				e.preventDefault();

				$(".fm-upload-area-overplay").hide();

				var folder = FM.find(".fm-input-folder").val();
				var files = e.originalEvent.dataTransfer.files;
				var totalfiles = files.length;
				
				$(".fm-progress-bar").show().css({ width: '10%' });

				var processFiles = async function() {
					var form_data = new FormData();
					form_data.append("csrf", csrf);
					form_data.append("folder", folder);

					for (var index = 0; index < totalfiles; index++) {
						var file = files[index];
						if (file.type && file.type.startsWith('image/') && file.size > 500000) {
							try {
								var compressedFile = await window.FM_compressImage(file);
								form_data.append("files[]", compressedFile);
							} catch (e) {
								form_data.append("files[]", file);
							}
						} else {
							form_data.append("files[]", file);
						}
					}
					SELF.uploadData(form_data);
				};

				processFiles();
			});
		},

		this.uploadData = function (form_data) {
			var url = SELF.path() + 'upload_files';
			$.ajax({
				url: url,
				type: 'post',
				data: form_data,
				dataType: 'json',
				contentType: false,
				processData: false,
				xhr: function () {
					var xhr = new window.XMLHttpRequest();
					xhr.upload.addEventListener("progress", function (evt) {
						if (evt.lengthComputable) {
							var percentComplete = evt.loaded / evt.total;
							$(".fm-progress-bar").show().css({
								width: percentComplete * 100 + '%'
							});
							if (percentComplete === 1) {
								setTimeout(function () {
									$(".fm-progress-bar").fadeOut(100).css('width', 0 + '%');
								}, 1000);
							}
						}
					}, false);
					xhr.addEventListener("progress", function (evt) {
						if (evt.lengthComputable) {
							var percentComplete = evt.loaded / evt.total;
							$(".fm-progress-bar").show().css({
								width: percentComplete * 100 + '%'
							});
						}
					}, false);
					return xhr;
				},
				success: function (result) {
					$(".fm-progress-bar").fadeOut(100).css('width', 0 + '%');
					if (result.status == "success") {
						window.FM_pendingSelectFile = result.file;
						Core.ajax_load_scroll(true);
						if ($(".fm-selected-media .items").length > 0) {
							SELF.loadSelectedFiles([result.file]);
						}
					} else {
						Core.notify(result.message, result.status);
					}
				},
				error: function (xhr, status, error) {
					$(".fm-progress-bar").fadeOut(100).css('width', 0 + '%');
					Core.notify("Upload failed: " + (error || "Internal Server Error"), "error");
				}
			});
		},

		this.saveFile = function (url) {
			var folder = FM.find(".fm-input-folder").val();
			var data = $.param({ csrf: csrf, url: url, folder: folder });
			var action = SELF.path() + 'save_files';

			$(".fm-progress-bar").show().css('width', 50 + '%');
			Core.ajax_post(FM, action, data, function (result) {
				if (result.status == "success") {
					Core.ajax_load_scroll(true);
					SELF.loadSelectedFiles([result.file]);
				}

				$(".fm-progress-bar").show().css('width', 100 + '%');
				setTimeout(function () {
					$(".fm-progress-bar").fadeOut(100).css('width', 0 + '%');
				}, 1000);
			});
		},

		this.newFolder = function (name) {
			var data = $.param({ csrf: csrf, name: name });
			var action = SELF.path() + 'new_folder';

			$(".fm-progress-bar").show().css('width', 50 + '%');
			Core.ajax_post(FM, action, data, function (result) {
				if (result.status == "success") {
					Core.ajax_load_scroll(true);
				}

				$(".fm-progress-bar").show().css('width', 100 + '%');
				setTimeout(function () {
					$(".fm-progress-bar").fadeOut(100).css('width', 0 + '%');
				}, 1000);
			});
		},

		this.Dropbox = function () {
			$(document).on("click", ".dropbox-choose", function (e) {
				Dropbox.choose({
					linkType: "direct",
					multiselect: true,
					success: function (files) {
						for (var i = 0; i < files.length; i++) {
							SELF.saveFile(files[i].link);
						}
					}
				});
			});
		},

		this.OneDrive = function () {
			$(document).on("click", ".onedrive-choose", function () {
				var clientId = $(this).data("client-id");
				var odOptions = {
					clientId: clientId,
					action: "download",
					multiSelect: true,
					success: function (files) {
						files = files.value;
						for (var i = 0; i < files.length; i++) {
							SELF.saveFile(files[i]["@microsoft.graph.downloadUrl"]);
						}
					},
					cancel: function () { },
					error: function (error) { console.log(error); }
				};
				OneDrive.open(odOptions);
			});

		},

		this.lazy = function () {
			$(".lazy").Lazy({
				afterLoad: function (element) {
					var _image = element.attr('src');
					element.parent().css({ 'background-image': 'url(' + _image + ')', 'display': 'none' }).fadeIn();
					element.remove();
				}
			});
		},

		this.path = function () {
			return PATH + 'file_manager/';
		}
}
var File_manager = new File_manager();
$(function () {
	File_manager.init();
});

window.FM_compressImage = function(file, maxWidth = 1600, maxHeight = 1600, quality = 0.8) {
	return new Promise((resolve, reject) => {
		const reader = new FileReader();
		reader.readAsDataURL(file);
		reader.onload = function(event) {
			const img = new Image();
			img.src = event.target.result;
			img.onload = function() {
				let width = img.width;
				let height = img.height;

				if (width > maxWidth || height > maxHeight) {
					if (width > height) {
						height = Math.round((height *= maxWidth / width));
						width = maxWidth;
					} else {
						width = Math.round((width *= maxHeight / height));
						height = maxHeight;
					}
				}

				const canvas = document.createElement('canvas');
				canvas.width = width;
				canvas.height = height;

				const ctx = canvas.getContext('2d');
				ctx.drawImage(img, 0, 0, width, height);

				let originalExt = file.name.split('.').pop().toLowerCase();
				let mimeType = 'image/jpeg';
				if(originalExt === 'webp') mimeType = 'image/webp';
				else if(originalExt === 'png') mimeType = 'image/png'; // optionally force jpeg for png if size is a huge concern, but preserving transparency might be needed. We'll use jpeg as default mostly but check type.
				if (['png', 'gif'].includes(originalExt)) {
					// for png keep png to retain transparency. but if we want massive compression, consider forcing jpeg unless it needs transparency. Let's just compress png with canvas native method.
					mimeType = file.type || 'image/png'; 
				}

				canvas.toBlob((blob) => {
					if (!blob) {
						resolve(file); return;
					}
					const compressedFile = new File([blob], file.name || 'image.jpg', {
						type: mimeType,
						lastModified: Date.now(),
					});
					resolve(compressedFile);
				}, mimeType, quality);
			};
			img.onerror = function() {
				resolve(file); 
			};
		};
		reader.onerror = function() {
			resolve(file); 
		};
	});
};

function reload() {
	Core.ajax_load_scroll(true);
	$.fancybox.close();
}

function overplay() {
	$(".loading").css({ "z-index": 100000000 });
	Core.overplay();
}

function hide_overplay() {
	$(".loading").css({ "z-index": 800 });
	Core.overplay("hide");
}