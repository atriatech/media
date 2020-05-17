const $ = require('jquery');
import { MDCRipple } from '@material/ripple';
import { MDCTopAppBar } from '@material/top-app-bar';
import { MDCList } from "@material/list";
import { MDCMenu } from '@material/menu';
import { MDCLinearProgress } from '@material/linear-progress';
import { MDCLinearProgressFoundation } from '@material/linear-progress/foundation';
import Swal from 'sweetalert2'
const { DateTime } = require('luxon');

let allItems = [];
let currentPath = '';
let activeItems = [];
let ctrlIsPressed = false;

const getUrlParam = function (paramName) {
	const reParam = new RegExp('(?:[\?&]|&)' + paramName + '=([^&]+)', 'i');
	const match = window.location.search.match(reParam);

	return (match && match.length > 1) ? match[1] : null;
};

const updateActiveItems = function () {
	activeItems = [];
	$('.media-explorer .active-item').map((item) => {
		activeItems.push($('.media-explorer .active-item').eq(item).attr('data-path'));
	});

	if (activeItems.length === 0) {
		$('#rename').prop('disabled', true);
		$('#delete').prop('disabled', true);
	}
	if (activeItems.length === 1) {
		if (activeItems[0] === 'public') {
			$('#rename').prop('disabled', true);
			$('#delete').prop('disabled', true);
		} else {
			$('#rename').prop('disabled', false);
			$('#delete').prop('disabled', false);
		}
	}
	if (activeItems.length > 1) {
		$('#rename').prop('disabled', true);
		$('#delete').prop('disabled', false);
	}
};

const getItem = function (path) {
	return allItems.find((x) => x.path === path);
};

const singleFile = function (item) {
	let html = `<div class="mdc-card position-relative" data-path="${item.path}">
				<div class="mdc-card__primary-action">`;
	switch (true) {
		case item.mime_type === 'directory':
			html += `<div class="mdc-card__media mdc-card__media--square">
						<img class="type-icon" src="${asset}atriatech/media/extra/icons/svg/100-folder.svg">
					</div>`;
			break;
		case item.mime_type.search('image/') !== -1:
		    let first_subSize = '';
		    for (const key in item.options.subSizes) {
                first_subSize = key;
                break;
            }
			html += `<div class="mdc-card__media mdc-card__media--square">
						<img src="${(item.options.subSizes !== undefined) ? item.options.subSizes[first_subSize] : item.path}">
					</div>`;
			break;
		case item.mime_type.search('audio/') !== -1:
			html += `<div class="mdc-card__media mdc-card__media--square">
						<img class="type-icon" src="${asset}atriatech/media/extra/icons/svg/043-music-file.svg">
					</div>`;
			break;
		case item.mime_type.search('video/') !== -1:
			html += `<div class="mdc-card__media mdc-card__media--square">
						<img class="type-icon" src="${asset}atriatech/media/extra/icons/svg/035-file-7.svg">
					</div>`;
			break;
		default:
			html += `<div class="mdc-card__media mdc-card__media--square">
						<img class="type-icon" src="${asset}atriatech/media/extra/icons/svg/050-file.svg">
					</div>`;
			break;
	}
	html += `
					<div class="p-2">
						<h3 class="mdc-typography mdc-typography--subtitle2 mdc-theme--on-primary">${item.basename}</h3>
						<p class="custom-subtitle mdc-theme--on-primary">${item.size || ''}</p>
					</div>
				</div>
			</div>`;
	return html;
};

const noItem = function () {
	return `<div class="mdc-theme--on-primary empty-folder">
				<i class="material-icons">folder</i>
				<span>This folder is empty</span>
			</div>`;
};

const breadcrumb = function (breadcrumb) {
	$('.breadcrumb').html('');

	breadcrumb.map((item, index) => {
		if (index === breadcrumb.length - 1) {
			$('.breadcrumb').append(`<li class="breadcrumb-item active">
										<span class="mdc-theme--on-secondary">${item.name}</span>
									</li>`);
		} else {
			$('.breadcrumb').append(`<li class="breadcrumb-item">
										<a href="javascript:;" data-path="${item.path}" class="mdc-theme--on-secondary">${item.name}</a>
									</li>`);
		}
	});
};

const getFiles = function (path = null) {
    $('input[name="accept"]').val(getUrlParam('accept'));
    $('#fileInput').attr('accept', getUrlParam('accept'));
	if (path === null || path === '') {
		path = 'public/' + config.upload_folder;
	}
	currentPath = path;
	$('input[name="path"]').val(currentPath);
	if (path !== '') {
		$('#upload').prop('disabled', false);
		$('#new_folder').prop('disabled', false);
	} else {
		$('#upload').prop('disabled', true);
		$('#new_folder').prop('disabled', true);
	}
	clearSelection();
	$('.media-explorer .mdc-card').removeClass('active-item');
	updateActiveItems();

	$('.data-loading').removeClass('d-none');
	$.ajax({
		method: 'POST',
		url: mediaRoute('atriatech.media.getFiles'),
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		},
		data: {
			path: path,
			accept: ((getUrlParam('accept') !== undefined) ? getUrlParam('accept') : config.accept)
		},
		success: function (data) {
			allItems = data.files;
			const mediaContent = $('#main-content');
			$('.media-explorer').remove();
			$('.empty-folder').remove();
			if (allItems.length !== 0) {
				mediaContent.find('nav').after('<div class="media-explorer d-flex flex-row flex-wrap align-items-start justify-content-start h-auto pb-0"></div>');
				allItems.map((item) => {
					$('.media-explorer').append(singleFile(item));
				});
			} else {
				mediaContent.find('nav').after(noItem());
			}
			breadcrumb(data.breadcrumb);
            $('.data-loading').addClass('d-none');
		}
	});
};

const clearSelection = function () {
	$('.selected-item .mdc-card__media').html(`<div class="no-item-selected">No item selected</div>`);
	$('.item-info').addClass('d-none');
	$('.item-info').find('.mdc-typography').text('');
};

const activeItem = function (elem) {
	const path = $(elem).attr('data-path');

	if (!ctrlIsPressed) {
		$('.media-explorer .mdc-card').removeClass('active-item');
	}
	$(elem).toggleClass('active-item');

	updateActiveItems();
	updateItemInfo();
};

const updateItemInfo = function () {

	if (activeItems.length === 1) {
		const item = getItem(activeItems[0]);
		switch (true) {
			case item.mime_type === 'directory':
				$('.selected-item .mdc-card__media').html(`<img class="type-icon" class="type-icon" src="${asset}atriatech/media/extra/icons/svg/100-folder.svg">`);
				break;
			case item.mime_type.search('image/') !== -1:
                let first_subSize = '';
                for (const key in item.options.subSizes) {
                    first_subSize = key;
                    break;
                }
				$('.selected-item .mdc-card__media').html(`<img src="${(item.options.subSizes !== undefined) ? item.options.subSizes[first_subSize] : item.path}">`);
				break;
			case item.mime_type.search('audio/') !== -1:
				$('.selected-item .mdc-card__media').html(`<img class="type-icon" src="${asset}atriatech/media/extra/icons/svg/043-music-file.svg">`);
				break;
			case item.mime_type.search('video/') !== -1:
				$('.selected-item .mdc-card__media').html(`<img class="type-icon" src="${asset}atriatech/media/extra/icons/svg/035-file-7.svg">`);
				break;
			default:
				$('.selected-item .mdc-card__media').html(`<img class="type-icon" src="${asset}atriatech/media/extra/icons/svg/050-file.svg">`);
				break;
		}

		$('.item-info').removeClass('d-none');

		$('.item-name').text(item.basename);
		$('.item-type').text(`Type: ${item.mime_type}`);

		if (item.mime_type !== 'directory') {
			$('.item-size').text(`Size: ${item.size}`);
			$('.item-visibility').text(`Visibility: ${item.visibility}`);
			$('.item-last-modified').text(`Last modified: ${DateTime.fromISO(item.created_at.replace(' ', 'T'), { zone: 'UTC' }).setZone(DateTime.local().zoneName).toFormat('yyyy-LL-dd HH:mm:ss')}`);
		} else {
			$('.item-size').text('');
			$('.item-visibility').text('');
			$('.item-last-modified').text('');
		}
	} else {
		$('.selected-item .mdc-card__media').html(`<div class="no-item-selected">Multiple items selected</div>`);
		$('.item-info').addClass('d-none');
		$('.item-info').find('.mdc-typography').text('');
	}
};

const initButton = function () {
	const selector = '.mdc-button, .mdc-icon-button';
	[].map.call(document.querySelectorAll(selector), function (el) {
		return new MDCRipple(el);
	});
};

$('body').delegate('.media-explorer .mdc-card', 'dblclick', function () {
	const path = $(this).attr('data-path');
	const item = getItem(path);

	if (item.mime_type === 'directory') {
		getFiles(path);
	} else {
		const refId = getUrlParam('refId');
        const funcNum = getUrlParam('CKEditorFuncNum');
		if (refId !== null) {
			if (refId) {
				const path = $(this).attr('data-path');
				window.opener.mediaManager(refId, path);
				window.close();
			}
		} else if (funcNum !== null) {
			window.opener.CKEDITOR.tools.callFunction(funcNum, path);
			window.close();
		}
	}
}).delegate('.media-explorer .mdc-card', 'click', function () {
	activeItem(this);
}).delegate('.breadcrumb-item a', 'click', function () {
	const path = $(this).attr('data-path');
	getFiles(path);
}).keydown(function (evt) {
	if (evt.keyCode === 39) {
		const active = $('.media-explorer .active-item');

		if (active.length !== 0) {
			if (!$('.media-explorer .mdc-card').last().hasClass('active-item')) {
				activeItem(active.next());
			}
		}
	} else if (evt.keyCode === 37) {
		const active = $('.media-explorer .active-item');

		if (active.length !== 0) {
			if (!$('.media-explorer .mdc-card').first().hasClass('active-item')) {
				activeItem(active.prev());
			}
		}
	}
}).keydown(function (evt) {
	if (evt.ctrlKey) {
		evt.preventDefault();
		ctrlIsPressed = true;
	}

	if (evt.keyCode == 65 && (evt.ctrlKey)) {
		evt.preventDefault();
		$('.item').addClass('item-active');
		updateActiveItems();
	}
}).keyup(function (evt) {
	ctrlIsPressed = false;
});

$('.main-content').click(function (e) {
	if ($(e.target).is('.media-explorer') || $(e.target).is('.main-content')) {
		$('.media-explorer .mdc-card').removeClass('active-item');
		clearSelection();
		updateActiveItems();
	}
});

let linearProgress = null;

$(document).ready(function () {
	initButton();

	const topAppBarElement = document.querySelector('.mdc-top-app-bar');
	new MDCTopAppBar(topAppBarElement);

	// const list = MDCList.attachTo(document.querySelector('.mdc-list'));
	// list.wrapFocus = true;

	// [].map.call(document.querySelectorAll('.mdc-menu'), function(el) {
	// 	const menu = new MDCMenu(el);
	// 	menu.open = true;
	// 	return menu;
	// });

    linearProgress = new MDCLinearProgress(document.querySelector('.mdc-linear-progress'));

	getFiles();
});

$('#upload').click(function () {
	$('#fileInput').trigger('click');
});

const swalMediaInput = Swal.mixin({
	input: 'text',
	heightAuto: false,
	showClass: {
		popup: 'animated zoomIn faster'
	},
	hideClass: {
		popup: 'animated zoomOut faster'
	},
	customClass: {
		container: 'container-class',
		popup: 'popup-class',
		header: 'header-class',
		title: 'title-class',
		closeButton: 'close-button-class',
		icon: 'icon-class',
		image: 'image-class',
		content: 'content-class',
		input: 'input-class',
		actions: 'actions-class',
		confirmButton: 'mdc-theme--secondary-bg',
		cancelButton: 'cancel-button-class',
		footer: 'footer-class'
	},
	showCancelButton: true,
	cancelButtonText: 'Cancel',
	allowOutsideClick: false,
	allowEscapeKey: false,
});
$('#new_folder').click(function () {
	swalMediaInput.fire({
		title: 'New Folder',
		icon: 'info',
		inputPlaceholder: 'Folder Name',
		preConfirm: (folderName) => {
			return new Promise((resolve, error) => {
				$.ajax({
					method: 'POST',
					url: mediaRoute('atriatech.media.newFolder'),
					data: {
						_token: $('meta[name="csrf-token"]').attr('content'),
						folder: folderName,
						currentDir: currentPath,
					},
					success: function (e) {
						resolve();
					},
					error: function(e) {
						error(e);
					}
				});
			}).then(() => {
				getFiles(currentPath);
			}).catch((error) => {
				switch (error.status) {
					case 422:
						Swal.showValidationMessage(
							Object.values(Object.values(error.responseJSON)[1])[0]
						);
						break;
					case 400:
						Swal.showValidationMessage(
							error.responseJSON.err
						);
						break;
				}
			});
		},
		confirmButtonText: 'Create',
		inputValidator: (value) => {
			return new Promise((resolve) => {
				if (value === undefined || value === null || value === '') {
					resolve('Enter folder name!');
				} else {
					resolve();
				}
			})
		}
	});
});

const swalInit = Swal.mixin({
	heightAuto: false,
	showClass: {
		popup: 'animated zoomIn faster'
	},
	hideClass: {
		popup: 'animated zoomOut faster'
	},
	customClass: {
		container: 'container-class',
		popup: 'popup-class',
		header: 'header-class',
		title: 'title-class',
		closeButton: 'close-button-class',
		icon: 'icon-class',
		image: 'image-class',
		content: 'content-class',
		input: 'input-class',
		actions: 'actions-class',
		confirmButton: 'mdc-theme--secondary-bg',
		cancelButton: 'cancel-button-class',
		footer: 'footer-class'
	},
	confirmButtonText: 'Close',
	allowOutsideClick: false,
	allowEscapeKey: false,
});
const swalMediaConfirm = Swal.mixin({
	heightAuto: false,
	showClass: {
		popup: 'animated zoomIn faster'
	},
	hideClass: {
		popup: 'animated zoomOut faster'
	},
	customClass: {
		container: 'container-class',
		popup: 'popup-class',
		header: 'header-class',
		title: 'title-class',
		closeButton: 'close-button-class',
		icon: 'icon-class',
		image: 'image-class',
		content: 'content-class',
		input: 'input-class',
		actions: 'actions-class',
		confirmButton: 'mdc-theme--secondary-bg',
		cancelButton: 'cancel-button-class',
		footer: 'footer-class'
	},
	showCancelButton: true,
	confirmButtonText: 'Yes',
	cancelButtonText: 'No',
	allowOutsideClick: false,
	allowEscapeKey: false,
});
$('#delete').click(function () {
	swalMediaConfirm.fire({
		icon: 'error',
		title: `Delete ${(activeItems.length === 1) ? 'item' : 'items'}`,
		text: `Are you sure you want to delete ${(activeItems.length === 1) ? 'this item' : 'these items'}?`,
		preConfirm: () => {
			return new Promise((resolve, error) => {
				$.ajax({
					method: 'POST',
					url: mediaRoute('atriatech.media.deleteItem'),
					data: {
						_token: $('meta[name="csrf-token"]').attr('content'),
						items: activeItems,
					},
					success: function (e) {
						resolve();
					},
					error: function(e) {
						error(e);
					}
				});
			}).then(() => {
				getFiles(currentPath);
			}).catch((error) => {
				switch (error.status) {
					case 422:
						Swal.showValidationMessage(
							Object.values(Object.values(error.responseJSON)[1])[0]
						);
						break;
					case 400:
						Swal.showValidationMessage(
							error.responseJSON.err
						);
						break;
				}
			});
		},
	});
});

$('#rename').click(function () {
	const newName = activeItems[0].substring(activeItems[0].lastIndexOf('/') + 1);
	swalMediaInput.fire({
		title: 'Rename',
		icon: 'info',
		inputPlaceholder: 'Name',
		inputValue: (newName.lastIndexOf('.') !== -1) ? newName.replace(newName.substring(newName.lastIndexOf('.')), '') : newName,
		preConfirm: (newName) => {
			return new Promise((resolve, error) => {
				$.ajax({
					method: 'POST',
					url: mediaRoute('atriatech.media.renameItem'),
					data: {
						_token: $('meta[name="csrf-token"]').attr('content'),
						item: activeItems[0],
						newName,
					},
					success: function (e) {
						resolve();
					},
					error: function(e) {
						error(e);
					}
				});
			}).then(() => {
				getFiles(currentPath);
			}).catch((error) => {
				switch (error.status) {
					case 422:
						Swal.showValidationMessage(
							Object.values(Object.values(error.responseJSON)[1])[0]
						);
						break;
					case 400:
						Swal.showValidationMessage(
							error.responseJSON.err
						);
						break;
				}
			});
		},
		confirmButtonText: 'Rename',
		inputValidator: (value) => {
			return new Promise((resolve) => {
				if (value === undefined || value === null || value === '') {
					resolve('Enter new name!');
				} else {
					resolve();
				}
			})
		}
	});
});

$('#fileInput').change(function () {
	$('#uploadForm').trigger('submit');
});

let xhr = null;
$('#uploadForm').on('submit', function (e) {
	e.preventDefault();

	$.ajax({
		url: mediaRoute('atriatech.media.uploadFile'),
		type: "POST",
		data: new FormData(this),
		contentType: false,
		cache: false,
		processData: false,
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		},
		beforeSend: function () {
			// loader();
			$('.mdc-linear-progress').removeClass('d-none');
			$('.linear-progress-percent').removeClass('d-none');
            linearProgress.progress = 0;
            $('.linear-progress-percent').text('0%');
            $('#upload').addClass('d-none');
            $('#cancel-upload').removeClass('d-none');
		},
		success: function (data) {
			if (data == 'invalid') {
			} else {
                $("#uploadForm")[0].reset();
                getFiles(currentPath);
                linearProgress.progress = 0;
                $('.linear-progress-percent').text('0%');
                $('.mdc-linear-progress').addClass('d-none');
                $('.linear-progress-percent').addClass('d-none');
                $('#upload').removeClass('d-none');
                $('#cancel-upload').addClass('d-none');
			}
		},
		error: function (e) {
			$("#uploadForm")[0].reset();
            linearProgress.progress = 0;
            $('.linear-progress-percent').text('0%');
            $('.mdc-linear-progress').addClass('d-none');
            $('.linear-progress-percent').addClass('d-none');
            $('#upload').removeClass('d-none');
			$('#cancel-upload').addClass('d-none');
            if (e.responseJSON !== undefined) {
                if (e.responseJSON.errors && e.responseJSON.errors.file[0]) {
                    swalInit.fire({
                        title: 'Error',
                        text: e.responseJSON.errors.file[0],
                        icon: 'error',
                    });
                } else if (e.responseJSON.message) {
					swalInit.fire({
                        title: 'Error',
                        text: e.responseJSON.message,
                        icon: 'error',
                    });
				} else {
                    swalInit.fire({
                        title: 'Error',
                        text: 'Something went wrong!',
                        icon: 'error',
                    });
                }
            }
		},
		xhr: function () {
            xhr = new window.XMLHttpRequest();
			xhr.upload.addEventListener("progress", function (evt) {
				if (evt.lengthComputable) {
					let percentComplete = evt.loaded / evt.total;
					percentComplete = parseInt((percentComplete * 100).toString(), 10);
                    linearProgress.progress = parseFloat((percentComplete / 100).toFixed(2));
                    $('.linear-progress-percent').text(percentComplete + '%');
				}
			}, false);
			return xhr;
		},
	});
});

$('#cancel-upload').click(function() {
    xhr.abort();
});
