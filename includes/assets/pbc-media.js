jQuery(function($){
	$(document).on('click', '.select-image', function(event){
		var current_button = $(this);
		var input_name = current_button.data('name');
		event.preventDefault();

		// check for media manager instance
		if(wp.media.frames.pbc) {
				wp.media.frames.pbc.open();
				return;
		}
		// configuration of the media manager new instance
		wp.media.frames.pbc = wp.media({
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
				var selection = wp.media.frames.pbc.state().get('selection');
				console.log(input_name);

				// no selection
				if (!selection) {
					return;
				}

				// iterate through selected elements
				selection.each(function(attachment) {
					var url = attachment.attributes.url;
					current_button.prev('[name=pdf_image_selected]').val(url);
				});
			};

		wp.media.frames.pbc.on('close', gk_media_set_image);
		wp.media.frames.pbc.on('select', gk_media_set_image);
		wp.media.frames.pbc.open();
	});
});
