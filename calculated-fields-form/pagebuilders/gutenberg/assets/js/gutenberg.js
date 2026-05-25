jQuery(function()
	{
		var $ = jQuery;
		(function( blocks, element ) {
			var el 			= element.createElement,
				InspectorControls = ('blockEditor' in wp) ? wp.blockEditor.InspectorControls : wp.editor.InspectorControls,
				category 	= {slug:'cp-calculated-fields-form', title : 'Calculated Fields Form'},
				{useEffect} = wp.element;

			/* Plugin Category */
			blocks.getCategories().push({slug: 'cpcff', title: 'Calculated Fields Form'});

			/* ICONS */
			const iconCPCFF = el('img', { width: 20, height: 20, src:  "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGlkPSJzdmcxIiB3aWR0aD0iMjBtbSIgaGVpZ2h0PSIyMG1tIiB2aWV3Qm94PSIwIDAgMjAgMjAiPjxnIGlkPSJsYXllcjEiPjxyZWN0IGlkPSJyZWN0MSIgd2lkdGg9IjE5LjAxMyIgaGVpZ2h0PSIxOS4wMTMiIHg9Ii40OTQiIHk9Ii40OTQiIHJ4PSIxLjUyMSIgcnk9IjEuNTIxIiBzdHlsZT0iZmlsbDojOTNjMWVjO2ZpbGwtb3BhY2l0eToxO3N0cm9rZTojMjE0NWU2O3N0cm9rZS13aWR0aDouOTg3MzA1O3N0cm9rZS1kYXNoYXJyYXk6bm9uZTtzdHJva2Utb3BhY2l0eToxIi8+PHBhdGggaWQ9InJlY3QyIiBkPSJNMCAyLjYyNWgyMHYzLjExMkgweiIgc3R5bGU9ImZpbGw6IzIxNDVlNjtmaWxsLW9wYWNpdHk6MTtzdHJva2U6bm9uZTtzdHJva2Utd2lkdGg6LjgzNzcyOTtzdHJva2UtZGFzaGFycmF5Om5vbmU7c3Ryb2tlLW9wYWNpdHk6MSIvPjxnIGlkPSJnNyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMCAuNTMpIj48ZyBpZD0iZzQiPjxwYXRoIGlkPSJyZWN0MyIgZD0iTTguNTEyIDguMDg2aDguMzAzdjIuNjU1SDguNTEyeiIgc3R5bGU9ImZpbGw6I2ZmZjtmaWxsLW9wYWNpdHk6MTtzdHJva2U6IzIxNDVlNjtzdHJva2Utd2lkdGg6Ljk1NTM0NTtzdHJva2UtZGFzaGFycmF5Om5vbmU7c3Ryb2tlLW9wYWNpdHk6MSIvPjxwYXRoIGlkPSJyZWN0NCIgZD0iTTIuOTc2IDguMDI3aDMuNTE5VjEwLjhIMi45NzZ6IiBzdHlsZT0iZmlsbDojMjE0NWU2O2ZpbGwtb3BhY2l0eToxO3N0cm9rZTojMjE0NWU2O3N0cm9rZS13aWR0aDouOTcwNzY2O3N0cm9rZS1kYXNoYXJyYXk6bm9uZTtzdHJva2Utb3BhY2l0eToxIi8+PC9nPjxnIGlkPSJnOSIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMCA1LjI5MikiPjxwYXRoIGlkPSJyZWN0OCIgZD0iTTguNTEyIDguMDg2aDguMzAzdjIuNjU1SDguNTEyeiIgc3R5bGU9ImZpbGw6I2ZmZjtmaWxsLW9wYWNpdHk6MTtzdHJva2U6IzIxNDVlNjtzdHJva2Utd2lkdGg6Ljk1NTM0NTtzdHJva2UtZGFzaGFycmF5Om5vbmU7c3Ryb2tlLW9wYWNpdHk6MSIvPjxwYXRoIGlkPSJyZWN0OSIgZD0iTTIuOTc2IDguMDI3aDMuNTE5VjEwLjhIMi45NzZ6IiBzdHlsZT0iZmlsbDojMjE0NWU2O2ZpbGwtb3BhY2l0eToxO3N0cm9rZTojMjE0NWU2O3N0cm9rZS13aWR0aDouOTcwNzY2O3N0cm9rZS1kYXNoYXJyYXk6bm9uZTtzdHJva2Utb3BhY2l0eToxIi8+PC9nPjwvZz48L2c+PC9zdmc+" } );

			const iconCPCFFV = el('img', { width: 20, height: 20, src:  "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMG1tIiBoZWlnaHQ9IjIwbW0iIHZpZXdCb3g9IjAgMCAyMCAyMCI+PHJlY3Qgd2lkdGg9IjE5LjAxMyIgaGVpZ2h0PSIxOS4wMTMiIHg9Ii40OTQiIHk9Ii40OTQiIHJ4PSIxLjUyMSIgcnk9IjEuNTIxIiBzdHlsZT0iZmlsbDojOTNjMWVjO2ZpbGwtb3BhY2l0eToxO3N0cm9rZTojMjE0NWU2O3N0cm9rZS13aWR0aDouOTg3MzA1O3N0cm9rZS1kYXNoYXJyYXk6bm9uZTtzdHJva2Utb3BhY2l0eToxIi8+PHJlY3Qgd2lkdGg9IjExLjE1MSIgaGVpZ2h0PSIxMS41MzQiIHg9IjQuNDI1IiB5PSI0LjIzMyIgcng9IjEiIHJ5PSIxIiBzdHlsZT0iZmlsbDojMjE0NWU2O2ZpbGwtb3BhY2l0eToxO3N0cm9rZTpub25lO3N0cm9rZS13aWR0aDoxLjIwNDE4O3N0cm9rZS1kYXNoYXJyYXk6bm9uZTtzdHJva2Utb3BhY2l0eToxIi8+PHJlY3Qgd2lkdGg9IjkuMzExIiBoZWlnaHQ9IjkuNjEyIiB4PSI1LjM0NSIgeT0iNS4xOTQiIHJ4PSIuODM1IiByeT0iLjgzMyIgc3R5bGU9ImZpbGw6I2ZmZjtmaWxsLW9wYWNpdHk6MTtzdHJva2U6bm9uZTtzdHJva2Utd2lkdGg6MS4wMDQ1O3N0cm9rZS1kYXNoYXJyYXk6bm9uZTtzdHJva2Utb3BhY2l0eToxIi8+PHBhdGggZD0iTTcuNjg2IDMuNzA4aDQuNjI4djEuNTE2SDcuNjg2em0wIDExLjA5OWg0LjYyOHYxLjUxNkg3LjY4NnoiIHN0eWxlPSJmaWxsOiM5M2MxZWM7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlLXdpZHRoOjEuMTU0NyIvPjxwYXRoIGQ9Im03LjEzIDYuOTc2IDUuNzQgNi4wNDhtLTUuNzQgMCA1Ljc0LTYuMDQ4IiBzdHlsZT0iZmlsbDojOTNjMWVjO2ZpbGwtb3BhY2l0eToxO3N0cm9rZTojMjE0NWU2O3N0cm9rZS13aWR0aDoxO3N0cm9rZS1vcGFjaXR5OjEiLz48L3N2Zz4=" } );

			/* Form's shortcode */
			blocks.registerBlockType( 'cpcff/form-shortcode', {
				title: 'Insert CFF',
				icon: iconCPCFF,
				category: 'cpcff',
				supports: {
					customClassName: false,
					className: false
				},
				attributes: {
					shortcode : {
						type : 'string',
						source : 'text',
						default: '[CP_CALCULATED_FIELDS id=""]'
					}
				},
				edit: function( props ) {
					function generate_shortcode()
					{
						props.setAttributes({'shortcode' : '[CP_CALCULATED_FIELDS id="'+id+'" '+additional+(iframe ? ' iframe="1"' : '')+(template.length ? ' template="'+template+'"' : '' )+']'});
					};

					function set_attributes(evt)
					{
						if(evt.target.id == 'cpcff_inspector_forms_list') // Form id
						{
							id = evt.target.value;
						}
						else if(evt.target.id == 'cpcff_inspector_templates_list') // Template id
						{
							template = evt.target.value;

							// Display thumbnail
							update_template_thumbnail( template );
						}
						else if(evt.target.tagName == 'INPUT' && evt.target.type == 'checkbox') // iFrame
						{
							iframe = evt.target.checked;
						}
						else if(evt.target.tagName == 'INPUT') // Additional attributes
						{
							additional = evt.target.value;
						}
						generate_shortcode();
					};

					function edit_form()
					{
						try
						{
							window.open(cpcff_gutenberg_editor_config.editor+id, '_blank');
						}
						catch(err){}
					};

					function update_template_thumbnail( template ) {
						// Display thumbnail
						$('[id="cpcff_inspector_templates_list"]').next('img').remove();
						if ( template in templates && 'thumbnail' in templates[ template ] && templates[ template ]['thumbnail'] !== '' ) {
							$('[id="cpcff_inspector_templates_list"]').after( '<img src="'+templates[ template ]['thumbnail']+'" style="margin-top:10px;margin-left:50%; transform:translateX(-50%);" />');
						}
					};

					function get_id()
					{
						var output = '',
							shortcode = props.attributes.shortcode,
							m = shortcode.match(/\bid\s*=\s*['"]([^'"]*)['"]/i);
						if(m) output = m[1];
						return output;
					};

					function get_template()
					{
						var output = '',
							shortcode = props.attributes.shortcode,
							m = shortcode.match(/\btemplate\s*=\s*['"]([^'"]*)['"]/i);
						if(m) output = String(m[1]).trim();
						return output;
					};

					function get_additional_atts()
					{
						var output = props.attributes.shortcode;
						output = output.replace(/^\s*\[\s*CP_CALCULATED_FIELDS\s+id\s*=\s*['"][^'"]*['"]\s*/i, '').replace(/\]\s*$/,'').replace(/\s?iframe\s*=\s*"1"\s?/ig, '').replace(/\s?template\s*=\s*"[^"]*"\s?/ig, '');
						return output;
					};

					function get_iframe()
					{
						var output = props.attributes.shortcode;
						return /\biframe\s*=\s*"1"/i.test(output);
					};

					function generate_url_params()
					{
						var shortcode = wp.shortcode.next('CP_CALCULATED_FIELDS', props.attributes.shortcode),
							attrs = shortcode.shortcode.attrs.named,
							output  = attrs['id'];
						for (var i in attrs) {
							if(i == 'id') continue;
							output += '&'+encodeURIComponent( 'template' == i ? 'cff-form-attr-template' : i )+'='+encodeURIComponent(attrs[i]);
						}
						return output;
					};

					// Main function code
					var focus 		= props.isSelected,
						options 	= [],
						templates_options = [
							el(
								'option',
								{key: 'cpcff_inspector_option_templates_', value: ''},
								'-'
							)
						],
						id 			= get_id(),
						first_time  = id == '',
						template 	= get_template(),
						templates   = {},
						additional 	= get_additional_atts(),
						iframe 		= get_iframe(),
						children 	= [];

                    if(
                        'url' in cpcff_gutenberg_editor_config &&
                        typeof cff_gutenberg_editor_config_interval == 'undefined'
                    )
                    {
                        cff_gutenberg_editor_config_interval = setInterval(
                            function(){
                                $cff_backend_url = cpcff_gutenberg_editor_config['url'].split('?')[0];
                                $.getJSON($cff_backend_url, {'cff-action': 'cff-gutenberg-editor-config'}, function(data){
                                    if(typeof data == 'object' && 'url' in data)
                                        cpcff_gutenberg_editor_config = data;
                                });
                            },
                            40000
                        );
                    }

					// Creates options for forms list
					if ( 'forms' in cpcff_gutenberg_editor_config ) {
						for( var form_id in cpcff_gutenberg_editor_config['forms'])
						{
							let config = {key: 'cpcff_inspector_option_'+form_id, value: form_id};

							if( /^\s*$/.test(id)) id = form_id;
							options.push(el('option', config, cpcff_gutenberg_editor_config['forms'][form_id]));
						}
					}

					// Creates options for templates list
					if ( 'templates' in cpcff_gutenberg_editor_config ) {
						templates = cpcff_gutenberg_editor_config['templates'];
						for( var template_id in templates)
						{
							let config = {key: 'cpcff_inspector_option_templates_'+template_id, value: template_id};
							templates_options.push(el('option', config, cpcff_gutenberg_editor_config['templates'][template_id]['title']));
						}
					}

					if( typeof useEffect != 'undefined') {
						useEffect(()=>{
							if(first_time && options.length) generate_shortcode();
						},[]);
					} else if(first_time && options.length) generate_shortcode();

					if(!/^\s*$/.test(id))
					{
						children.push(
							el( 'div', {className: 'cff-iframe-container', key: 'cpcff_form_container', style:{'position':'relative'}},
								el('div', {className: 'cff-iframe-overlay', key: 'cpcff_form_overlay', style:{'position':'absolute','top':0,'right':0,'bottom':0,'left':0}}),
								el('iframe',
									{
										key: 'cpcff_form_iframe',
										src: cpcff_gutenberg_editor_config['url']+generate_url_params(),
										height: '0',
										width: '100%',
										scrolling: 'no'
									}
								)
							)
						);
					}
					else
					{
						children.push(
							el('span',
								{
									key: 'cpcff_form_required'
								},
								'Select at least a form'
							)
						);
					}

					if(!!focus)
					{
						children.push(
							el(
								InspectorControls,
								{
									key: 'cpcff_inspector'
								},
								el(
									'div',
									{
										key: 'cpcff_inspector_container',
										style:{paddingLeft:'20px',paddingRight:'20px'}
									},
									[
										el(
											'span',
											{
												key: 'cpcff_inspector_help',
												style:{fontStyle: 'italic'}
											},
											'If you need help: '
										),
										el(
											'a',
											{
												key		: 'cpcff_inspector_help_link',
												href	: 'https://cff.dwbooster.com/documentation#insertion-page',
												target	: '_blank'
											},
											'CLICK HERE'
										),
										el(
											'hr',
											{
												key : 'cpcff_inspector_separator'
											}
										),
										el(
											'label',
											{
												key : 'cpcff_inspector_forms_label'
											},
											cpcff_gutenberg_editor_config['labels']['forms']
										),
										el(
											'select',
											{
												id  : 'cpcff_inspector_forms_list',
												key : 'cpcff_inspector_forms_list',
												style : {width: '100%'},
												onChange : set_attributes,
												value: id
											},
											options
										),
										el(
											'label',
											{
												key : 'cpcff_inspector_attributes_label',
												style:{ paddingTop:'20px', display:'block' }
											},
											cpcff_gutenberg_editor_config['labels']['attributes']
										),
										el(
											'input',
											{
												type : 'text',
												key : 'cpcff_inspector_text',
												value : get_additional_atts(),
												onChange : set_attributes,
												style: {width:"100%"}
											}
										),
										el(
											'span',
											{
												key : 'cpcff_inspector_attributes_help',
												style:{fontStyle: 'italic'}
											},
											'variable_name="value"'
										),
										el(
											'div',
											{
												key: 'cpcff_inspector_iframe_container',
												style:{
													paddingTop:'20px',
													display:((cpcff_gutenberg_editor_config.is_admin) ? 'block' : 'none')
												}
											},
											el(
												'input',
												{
													type: 'checkbox',
													key: 'cpcff_iframe',
													checked: get_iframe(),
													onChange: set_attributes
												}
											),
											el(
												'span',
												{
													key: 'cpcff_iframe_label'
												},
												cpcff_gutenberg_editor_config['labels']['iframe']
											),
											el(
												'div',
												{
													key: 'cpcff_iframe_description',
													style:{
														padding: '10px',
														border: '1px solid #F0AD4E',
														background: '#fffaf4',
														marginTop: '10px'
													}
												},
												cpcff_gutenberg_editor_config['labels']['iframe_description']
											)
										),
										el(
											'label',
											{
												key : 'cpcff_inspector_templates_label',
												style: { paddingTop:'20px', display:'block' }
											},
											cpcff_gutenberg_editor_config['labels']['templates']
										),
										el(
											'select',
											{
												id  : 'cpcff_inspector_templates_list',
												key : 'cpcff_inspector_templates_list',
												style : {width: '100%'},
												onChange : set_attributes,
												value: template
											},
											templates_options
										),
										el(
											'div',
											{
												key: 'cpcff_inspector_button_container',
												style:{
													paddingTop:'20px',
													paddingBottom:'20px',
													display:((cpcff_gutenberg_editor_config.is_admin) ? 'block' : 'none')
												}
											},
											el(
												'input',
												{
													type: 'button',
													key: 'cpcff_form_editor',
													value: cpcff_gutenberg_editor_config['labels']['edit_form'],
													className: 'button-primary',
													onClick: edit_form
												}
											)
										)
									]
								)
							)
						);
					}
					setTimeout(function(){update_template_thumbnail(template);}, 10);
					return [
						children
					];
				},

				save: function( props ) {
					return props.attributes.shortcode;
				}
			});

			/* variable shortcode */
			blocks.registerBlockType( 'cpcff/variable-shortcode', {
				title: 'Create var from POST or GET',
				icon: iconCPCFFV,
				category: 'cpcff',
				supports: {
					customClassName: false,
					className: false
				},
				attributes: {
					shortcode : {
						type : 'string',
						source : 'text',
						default: '[CP_CALCULATED_FIELDS_VAR name=""]'
					}
				},

				edit: function( props ) {
					var focus = props.isSelected;
					return [
						!!focus && el(
							InspectorControls,
							{
								key: 'cpcff_inspector'
							},
							el(
								'div',
								{
									key: 'cpcff_inspector_container',
									style:{paddingLeft:'20px',paddingRight:'20px'}
								},
								[
									el(
										'span',
										{
											key: 'cpcff_inspector_help',
											style:{fontStyle: 'italic'}
										},
										'If you need help: '
									),
									el(
										'a',
										{
											key		: 'cpcff_inspector_help_link',
											href	: 'https://cff.dwbooster.com/documentation#javascript-variables',
											target	: '_blank'
										},
										'CLICK HERE'
									)
								]
							)
						),
						el(
							'textarea',
							{
								key: 'cpcff_variable_shortcode',
								value: props.attributes.shortcode,
								onChange: function(evt){
									props.setAttributes({shortcode: evt.target.value});
								},
								style: {width:"100%", resize: "vertical"}
							}
						)
					];
				},

				save: function( props ) {
					return props.attributes.shortcode;
				}
			});
		} )(
			window.wp.blocks,
			window.wp.element
		);
	}
);