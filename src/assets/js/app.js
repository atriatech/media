const $ = require('jquery');
import { MDCRipple } from '@material/ripple';
import { MDCTopAppBar } from '@material/top-app-bar';
import { MDCList } from "@material/list";
import { MDCMenu } from '@material/menu';
import Swal from 'sweetalert2'
const { DateTime } = require('luxon');
const _ = require('lodash');

let allItems = [];
let currentPath = '';
let activeItems = [];
let ctrlIsPressed = false;
let listLimit = 40;
let listOffset = 0;
let listFinished = false;

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

const singleFile = function (item, random = '') {
	let html = '';
	if (item.status !== undefined) {
		if (item.status === 'uploading') {
			html = `<div class="mdc-card position-relative" data-token="${random}" data-uploading="true">
				<div class="mdc-card__primary-action">`;
			switch (true) {
				case item.mime_type.search('image/') !== -1:
					html += `<div class="mdc-card__media mdc-card__media--square">
						<img src="${item.image}">
					</div>`;
					break;
				case item.mime_type.search('audio/') !== -1:
					html += `<div class="mdc-card__media mdc-card__media--square">
						<img class="type-icon" src="${asset}${_.trim(config.url_prefix, '/')}atriatech/media/extra/icons/svg/043-music-file.svg">
					</div>`;
					break;
				case item.mime_type.search('video/') !== -1:
					html += `<div class="mdc-card__media mdc-card__media--square">
						<img class="type-icon" src="${asset}${_.trim(config.url_prefix, '/')}atriatech/media/extra/icons/svg/035-file-7.svg">
					</div>`;
					break;
				default:
					html += `<div class="mdc-card__media mdc-card__media--square">
						<img class="type-icon" src="${asset}${_.trim(config.url_prefix, '/')}atriatech/media/extra/icons/svg/050-file.svg">
					</div>`;
					break;
			}
			html += `
					<div class="p-2">
						<h3 class="mdc-typography mdc-typography--subtitle2 mdc-theme--on-primary">(Uploading)</h3>
						<p class="custom-subtitle mdc-theme--on-primary">(Uploading)</p>
					</div>
					<div class="upload-progress-wrapper">
						<div class="upload-progress"></div>
					</div>
				</div>
			</div>`;
		} else if (item.status === 'error') {
			html = `<div class="mdc-card position-relative" data-token="${random}" data-uploading="true">
				<div class="mdc-card__primary-action">`;
			switch (true) {
				case item.mime_type.search('image/') !== -1:
					html += `<div class="mdc-card__media mdc-card__media--square">
						<img src="${item.image}">
					</div>`;
					break;
				case item.mime_type.search('audio/') !== -1:
					html += `<div class="mdc-card__media mdc-card__media--square">
						<img class="type-icon" src="${asset}${_.trim(config.url_prefix, '/')}atriatech/media/extra/icons/svg/043-music-file.svg">
					</div>`;
					break;
				case item.mime_type.search('video/') !== -1:
					html += `<div class="mdc-card__media mdc-card__media--square">
						<img class="type-icon" src="${asset}${_.trim(config.url_prefix, '/')}atriatech/media/extra/icons/svg/035-file-7.svg">
					</div>`;
					break;
				default:
					html += `<div class="mdc-card__media mdc-card__media--square">
						<img class="type-icon" src="${asset}${_.trim(config.url_prefix, '/')}atriatech/media/extra/icons/svg/050-file.svg">
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
		}
	} else {
		html = `<div class="mdc-card position-relative" data-path="${item.path}">
				<div class="mdc-card__primary-action">`;
		switch (true) {
			case item.mime_type === 'directory':
				html += `<div class="mdc-card__media mdc-card__media--square">
						<img class="type-icon" src="${asset}${_.trim(config.url_prefix, '/')}atriatech/media/extra/icons/svg/100-folder.svg">
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
						<img class="type-icon" src="${asset}${_.trim(config.url_prefix, '/')}atriatech/media/extra/icons/svg/043-music-file.svg">
					</div>`;
				break;
			case item.mime_type.search('video/') !== -1:
				html += `<div class="mdc-card__media mdc-card__media--square">
						<img class="type-icon" src="${asset}${_.trim(config.url_prefix, '/')}atriatech/media/extra/icons/svg/035-file-7.svg">
					</div>`;
				break;
			default:
				html += `<div class="mdc-card__media mdc-card__media--square">
						<img class="type-icon" src="${asset}${_.trim(config.url_prefix, '/')}atriatech/media/extra/icons/svg/050-file.svg">
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
	}
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

const getFiles = function (path = null, limit = null, offset = null) {
	if (listFinished) {
		return false;
	}

	$('input[name="accept"]').val(getUrlParam('accept'));
	$('#fileInput').attr('accept', getUrlParam('accept'));
	if (path === null || path === '') {
		path = 'public/' + config.upload_folder;
	}
	if (limit === null) {
		limit = 40;
	}
	if (offset === null) {
		offset = 0;
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
			limit,
			offset,
			accept: ((getUrlParam('accept') !== undefined) ? getUrlParam('accept') : config.accept)
		},
		success: function (data) {
			if (data.files.filter(x => x.mime_type !== 'directory').length < listLimit) {
				listFinished = true;
			} else {
				listFinished = false;
				listOffset += listLimit;
			}
			const mediaContent = $('#main-content');
			if (offset === 0) {
				allItems = data.files;
				$('.media-explorer').remove();
				$('.empty-folder').remove();
			} else {
				allItems = _.concat(allItems, data.files);
			}
			if (allItems.length !== 0) {
				if (offset === 0) {
					mediaContent.find('nav').after('<div class="media-explorer d-flex flex-row flex-wrap align-items-start justify-content-start h-auto pb-0"></div>');
				}
				data.files.map((item) => {
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
				$('.selected-item .mdc-card__media').html(`<img class="type-icon" class="type-icon" src="${asset}${_.trim(config.url_prefix, '/')}atriatech/media/extra/icons/svg/100-folder.svg">`);
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
				$('.selected-item .mdc-card__media').html(`<img class="type-icon" src="${asset}${_.trim(config.url_prefix, '/')}atriatech/media/extra/icons/svg/043-music-file.svg">`);
				break;
			case item.mime_type.search('video/') !== -1:
				$('.selected-item .mdc-card__media').html(`<img class="type-icon" src="${asset}${_.trim(config.url_prefix, '/')}atriatech/media/extra/icons/svg/035-file-7.svg">`);
				break;
			default:
				$('.selected-item .mdc-card__media').html(`<img class="type-icon" src="${asset}${_.trim(config.url_prefix, '/')}atriatech/media/extra/icons/svg/050-file.svg">`);
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
	if (!$(this).attr('data-uploading')) {
		const path = $(this).attr('data-path');
		const item = getItem(path);

		if (item.mime_type === 'directory') {
			listFinished = false;
			listLimit = 40;
			listOffset = 0;
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
	}
}).delegate('.media-explorer .mdc-card', 'click', function () {
	if (!$(this).attr('data-uploading')) {
		activeItem(this);
	}
}).delegate('.breadcrumb-item a', 'click', function () {
	const path = $(this).attr('data-path');
	listFinished = false;
	listLimit = 40;
	listOffset = 0;
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

const listElm = document.querySelector('#main-content');

listElm.addEventListener('scroll', function() {
	if (listElm.scrollTop + listElm.clientHeight >= listElm.scrollHeight) {
		getFiles(currentPath, listLimit, listOffset);
	}
});

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
				listFinished = false;
				listLimit = 40;
				listOffset = 0;
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
				listFinished = false;
				listLimit = 40;
				listOffset = 0;
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
				listFinished = false;
				listLimit = 40;
				listOffset = 0;
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

function readURL(input, random) {
	if (input) {
		const reader = new FileReader();

		reader.onload = function(e) {
			const item = {
				basename: input.name,
				image: e.target.result,
				mime_type: input.type,
				status: 'uploading',
			};
			if ($('.media-explorer').length !== 0) {
				$('.media-explorer').find(`.mdc-card:eq(${allItems.filter(x => x.mime_type === 'directory').length - 1})`).after(singleFile(item, random));
			} else {
				$('.empty-folder').remove();
				$('#main-content').find('nav').after('<div class="media-explorer d-flex flex-row flex-wrap align-items-start justify-content-start h-auto pb-0"></div>');
				$('.media-explorer').prepend(singleFile(item, random));
			}
		}

		reader.readAsDataURL(input); // convert to base64 string
	}
}

const ajax_request = function(item, random) {
	const formData = new FormData();
	formData.append('file', item);
	formData.append('path', $('input[name="path"]').val());
	formData.append('accept', $('input[name="accept"]').val());

	let xhr = null;
	$.ajax({
		url: mediaRoute('atriatech.media.uploadFile'),
		type: "POST",
		data: formData,
		contentType: false,
		cache: false,
		processData: false,
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		},
		beforeSend: function () {
		},
		success: function (data) {
			if (data == 'invalid') {
			} else {
				$('[data-token="' + random + '"]').replaceWith(singleFile(data));
				allItems.unshift(data);
			}
		},
		error: function (e) {
			let errorMsg = '';
			if (e.responseJSON !== undefined) {
				if (e.responseJSON.errors && e.responseJSON.errors.file[0]) {
					errorMsg = e.responseJSON.errors.file[0];
				} else if (e.responseJSON.message) {
					errorMsg = e.responseJSON.message;
				} else {
					errorMsg = 'Something went wrong!';
				}
			} else {
				errorMsg = 'Something went wrong!';
			}

			$('[data-token="' + random + '"]').replaceWith(singleFile({
				basename: 'error',
				mime_type: 'other',
				size: errorMsg,
				status: 'error',
			}, random));
		},
		xhr: function () {
			xhr = new window.XMLHttpRequest();
			xhr.upload.addEventListener("progress", function (evt) {
				if (evt.lengthComputable) {
					let percentComplete = evt.loaded / evt.total;
					percentComplete = parseInt((percentComplete * 100).toString(), 10);
					$('[data-token="' + random + '"]').find('.upload-progress').css('width', percentComplete + '%');
				}
			}, false);
			return xhr;
		},
	});
};

const makeid = function(length) {
	let result           = '';
	const characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	const charactersLength = characters.length;
	for ( let i = 0; i < length; i++ ) {
		result += characters.charAt(Math.floor(Math.random() * charactersLength));
	}
	return result;
};

$('#fileInput').change(function () {
	$.map(this.files, function(item, i) {
		const random = makeid(20);
		readURL(item, random);
		ajax_request(item, random);
	});
	$('#fileInput').val('');
});

// $('#cancel-upload').click(function() {
//     xhr.abort();
// });
