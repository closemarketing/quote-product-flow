jQuery(function($){
	$(document).on('click', '.select-image', function(event){
		var current_button = $(this);
		var input_name = current_button.data('name');
		event.preventDefault();

		// Create unique frame name for each button
		var frame_name = 'qpfw_' + input_name;
		
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
		var gk_media_set_image = function(event) {
			
			var selection = wp.media.frames[frame_name].state().get('selection');
			// no selection
			if (!selection) {
				return;
			}
            
			// iterate through selected elements
			selection.each(function(attachment) {
                if(attachment.attributes.mime == 'image/jpeg' || attachment.attributes.mime == 'image/png' || attachment.attributes.mime == 'image/webp') {
                    var url = attachment.attributes.url;
                    $('input[name="' + input_name + '"]').val(url);
                    $('input[name="' + input_name + '"]').parents('fieldset').find('.qpfw_field_preview').html('<img src="' + url + '" alt="Image Preview" /><span class="qpfw_field_preview_remove">&times;</span>');
                    $('input[name="' + input_name + '"]').attr('data-imageId', attachment.attributes.id);
                } else {
                    if(event == 'select') {
                        alert(qpfw_media_strings.no_image_selected);
                        return;
                    }
                }
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

		wp.media.frames[frame_name].on('select', function() {
			gk_media_set_image('select');
		});
		wp.media.frames[frame_name].on('close', function() {
			gk_media_set_image('close');
		});
		wp.media.frames[frame_name].open();
	});
    $(document).on('click', '.qpfw_field_preview_remove', function(event){
        var current_button = $(this);
        var input_name = current_button.parents('fieldset').find('input');
        input_name.val('');
        input_name.attr('data-imageId', '');
        current_button.parents('fieldset').find('.qpfw_field_preview').html('');
    });
    $(document).find('.qpfw_color_picker').each(function(){
        $(this).wpColorPicker();
    });
});
