jQuery(function($){
	$(document).on('click', '.select-image', function(event){
		var current_button = $(this);
		var input_name = current_button.data('name');
		event.preventDefault();

		// Create unique frame name for each button
		var frame_name = 'pbc_' + input_name;
		
		// check for media manager instance
		if(wp.media.frames[frame_name]) {
			wp.media.frames[frame_name].open();
			return;
		}
		
		// configuration of the media manager new instance
		wp.media.frames[frame_name] = wp.media({
			title: 'Select image',
			multiple: false,
			library: {
				type: 'image'
			},
			button: {
				text: 'Use selected image'
			}
		});

		// Function used for the image selection and media manager closing
		var gk_media_set_image = function() {
			var selection = wp.media.frames[frame_name].state().get('selection');
			
			// no selection
			if (!selection) {
				return;
			}

			// iterate through selected elements
			selection.each(function(attachment) {
				var url = attachment.attributes.url;
				$('input[name="' + input_name + '"]').val(url);
                $('input[name="' + input_name + '"]').attr('data-imageId', attachment.attributes.id);
			});
		};

		// Set the selected image when opening the media frame
		wp.media.frames[frame_name].on('open', function() {
			var current_value = $('input[name="' + input_name + '"]').attr('data-imageId');
			if (current_value) {
				var attachment = wp.media.attachment(current_value);
				attachment.fetch();
				var selection = wp.media.frames[frame_name].state().get('selection');
				selection.add(attachment);
			}
		});

		wp.media.frames[frame_name].on('close', gk_media_set_image);
		wp.media.frames[frame_name].on('select', gk_media_set_image);
		wp.media.frames[frame_name].open();
	});
});
