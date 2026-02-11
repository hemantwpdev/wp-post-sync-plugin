(function( $ ) {
	'use strict';

	$(function() {
		if ( typeof wpstTranslate === 'undefined' ) {
			return;
		}

		const modeRadios = $('input[name="mode"]');
		const hostSettings = $('#host-settings');
		const targetSettings = $('#target-settings');

		// Toggle settings based on mode
		modeRadios.on('change', function() {
			if ($(this).val() === 'host') {
				hostSettings.show();
				targetSettings.hide();
			} else {
				hostSettings.hide();
				targetSettings.show();
			}
		});

		// Add target button
		$('#add-target-btn').on('click', function() {
			const url = $('#new-target-url').val().trim();
			const message = $('#add-target-message');

			if (!url) {
				message.text('Please enter a valid URL').removeClass('success').addClass('error');
				return;
			}

			$.ajax({
				url: wpstTranslate.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpst_add_target',
					url: url,
					nonce: wpstTranslate.nonce,
				},
				success: function(response) {
					if (response.success) {
						message.text('Target added successfully').removeClass('error').addClass('success');
						$('#new-target-url').val('');
						location.reload();
					} else {
						message.text(response.data || 'Failed to add target').removeClass('success').addClass('error');
					}
				},
				error: function() {
					message.text('AJAX error').removeClass('success').addClass('error');
				},
			});
		});

		// Remove target button
		$(document).on('click', '.remove-target', function(e) {
			e.preventDefault();
			const url = $(this).data('url');

			if (!confirm('Are you sure you want to remove this target?')) {
				return;
			}

			$.ajax({
				url: wpstTranslate.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpst_remove_target',
					url: url,
					nonce: wpstTranslate.nonce,
				},
				success: function(response) {
					if (response.success) {
						location.reload();
					}
				},
			});
		});

		// Copy key button
		$(document).on('click', '.copy-key', function(e) {
			e.preventDefault();
			const key = $(this).data('key');
			navigator.clipboard.writeText(key).then(() => {
				alert('Key copied to clipboard');
			});
		});

		// Save settings button
		$('#save-settings-btn').on('click', function() {
			const mode = $('input[name="mode"]:checked').val();
			const message = $('#save-message');

			const data = {
				action: 'wpst_save_settings',
				mode: mode,
				nonce: wpstTranslate.nonce,
			};


			if (mode === 'target') {
				const targetKey = $('#target_key').val() ? $('#target_key').val().trim() : '';
				if (!targetKey) {
					message.text('Target key is required').removeClass('success').addClass('error');
					return;
				}
				if (targetKey.length < 16) {
					message.text('Target key must be at least 16 characters long').removeClass('success').addClass('error');
					return;
				}

				data.target_key = targetKey;
				data.language = $('#language').val() ? $('#language').val().trim() : '';
				data.chatgpt_key = $('#chatgpt_key').val() ? $('#chatgpt_key').val().trim() : '';
			}

			$.ajax({
				url: wpstTranslate.ajaxUrl,
				type: 'POST',
				data: data,
				success: function(response) {
					message.text(response.data || 'Settings saved').removeClass('error').addClass('success');
					setTimeout(() => {
						message.text('').removeClass('success');
					}, 3000);
				},
				error: function() {
					message.text('Error saving settings').removeClass('success').addClass('error');
				},
			});
		});
	});

})( jQuery );
