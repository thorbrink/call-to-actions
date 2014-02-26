jQuery(document).ready(function($)
{

	var file_frame;

	$('#call-to-actions .inside').append('<ul id="cta-list"/>');

	var CTA = Backbone.Model.extend({
		
		defaults: function()
		{
			return {
				post_title			: '',
				post_content		: '',
				link				: '',
				link_text			: '',
				ID					: false,
				post_parent			: post_data.id, // Global variable passed from backend.
				featured_image		: false,
				featured_image_url	: false
			}
		},
		
		urlRoot : ajaxurl + '?action=call_to_actions',
		
	});
	
	var ctaView = Backbone.View.extend({
		
		tagName: 'li',
		
		className: 'cta',
		
		events: {
			"keyup input"			: "changed",
			"click .remove"			: "destroyCTA",
			"click .featured-image"	: "openMediaUploader" 
		},
		
		template: _.template('<% if(featured_image_url) { %><figure class="featured-image"><img src="<%= featured_image_url %>" /></figure><% } else { %><a class="featured-image" href="#">Sätt bild</a><% } %><input class="title" type="text" value="<%= post_title %>" placeholder="Titel"/><input class="content" type="text" value="<%= post_content %>" placeholder="Text"/><input class="link" type="text" value="<%= link %>" placeholder="Länk"/><input class="link-text" type="text" value="<%= link_text %>" placeholder="Länktext"/><a href="#" class="remove"></a>'),
		
		initialize: function()
		{
			//this.listenTo(this.model, 'change', this.render);
			this.listenTo(this.model, 'change', this.changed);
			this.listenTo(this.model, 'change:featured_image', this.render);
			this.listenTo(this.model, 'destroy', this.remove);
		},
		
		render: function()
		{
			this.$el.html(this.template(this.model.toJSON()));
			return this;
		},
		
		openMediaUploader: function(event)
		{
			var view = this;
			
			if ( file_frame )
			{
				file_frame.open();
				return;
			}
			
			// Create the media frame.
			file_frame = wp.media.frames.file_frame = wp.media({
				title: jQuery( this ).data( 'uploader_title' ),
				button: {
					text: jQuery( this ).data( 'uploader_button_text' ),
				},
				multiple: false  // Set to true to allow multiple files to be selected
			});
 
			// When an image is selected, run a callback.
			file_frame.on( 'select', function() {
			
				attachment 		= file_frame.state().get('selection').first().toJSON();
				var image_url 	= (attachment.sizes.thumbnail === undefined) ? attachment.sizes.full.url : attachment.sizes.thumbnail.url;

				view.model
					.set({ featured_image : attachment.id, featured_image_url : image_url });
			});
 
			// Finally, open the modal
			file_frame.open();
		},
		
		timer : null,
		
		changed: function(event)
		{
			var view	= this;
		
			if(view.timer) clearTimeout(view.timer);
			
			var delay = 1000;
			
			view.timer = setTimeout(function()
			{	
				var model 	= view.model;
				
				var inputTitle 		= view.$el.find('.title').val();
				var inputContent 	= view.$el.find('.content').val();
				var inputLink		= view.$el.find('.link').val();
				var inputLinkText	= view.$el.find('.link-text').val();
			
				model.save({
				    post_title		: inputTitle,
				    post_content 	: inputContent,
				    link			: inputLink,
				    link_text		: inputLinkText
				}, {
					success: function(model, response)
					{
						if(!view.model.get('ID')) // If is a newly created model, set the id.
						{
							view.model.set({ID : response});
						}
					},
					error: function(model, response)
					{
					}
				});
				
				view.timer = null;
				
			}, delay);
		},
		
		destroyCTA: function(event)
		{
			event.preventDefault();
			
			var model = this.model;
			Backbone.sync('delete', model, {data: JSON.stringify(model)});
			
			this.model.destroy({
				success: function(model, response)
				{

				},
				error: function()
				{

				}
			});	
		}
	});
	
	var bootstrapped_ctas = $.parseJSON(post_data.boostrapped_ctas);
	bootstrapped_ctas_array = [];
	
	// If bootstrapped CTAS exists, make models.
	_.each(bootstrapped_ctas, function(bootstrapped_cta)
	{
		var cta = new CTA({
			ID					: bootstrapped_cta.ID,
			post_parent			: bootstrapped_cta.post_parent,
			post_title			: bootstrapped_cta.post_title,
			post_content		: bootstrapped_cta.post_content,
			link				: bootstrapped_cta.link,
			link_text			: bootstrapped_cta.link_text,
			featured_image		: bootstrapped_cta.featured_image,
			featured_image_url	: bootstrapped_cta.featured_image_url
		});
		var view = new ctaView({ model: cta });
		$("#cta-list").append(view.render().el);
	});
	
	// Add 'add-button'.
	
	var append_button = $('<li class="add-cta"><a id="add-cta" href="#"></a></li>');
	$('#cta-list').append(append_button);
		
	$('#add-cta').on('click', function(event)
	{
		event.preventDefault();
		var cta 	= new CTA();
		var view 	= new ctaView({ model: cta });
		$("#cta-list").append(view.render().el);
		$('#cta-list').append(append_button);
	});
});