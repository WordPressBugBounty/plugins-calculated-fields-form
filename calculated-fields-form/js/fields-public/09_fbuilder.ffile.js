	$.fbuilder.controls['ffile'] = function(){};
	$.extend(
		$.fbuilder.controls['ffile'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			title:"Untitled",
			ftype:"ffile",
			required:false,
			size:"medium",
			accept:"",
			upload_size:"",
			multiple:false,
			preview: false,
			thumb_width: '80px',
			thumb_height: '',
			_patch: false, // Used only if the submission is being updated to preserves the previous values
			_files_list: [],
			init: function(){
                this.getCSSComponent('files_container_hover', true, '#fbuilder .' + this.name + ' .cff-file-field-container:hover', this.form_identifier);
				this.thumb_width  = cff_sanitize(String(this.thumb_width).trim(), true);
				this.thumb_height = cff_sanitize(String(this.thumb_height).trim(), true);
				var form_identifier = this.form_identifier.replace(/[^\d]/g, '');
				this._patch = ('cpcff_default' in window && form_identifier in cpcff_default) ? true : false;
			},
			show:function()
				{
					this.accept = cff_esc_attr(String(this.accept).trim());
					this.upload_size = cff_esc_attr(String(this.upload_size).trim());
                    let info = '<div class="cff-file-info-container" style="'+cff_esc_attr(this.getCSSComponent('file_info'))+'">',
                        info_separtor = '';
                    if ( this.accept.length ) {
                        info += cff_sanitize(this.accept);
                        info_separtor = '/';
                    }
                    if ( this.upload_size.length ) {
                        let inMB = parseFloat(this.upload_size) / 1024;
                        info += info_separtor + cff_sanitize(!isNaN(inMB) && 1 <= inMB && inMB <= 1024 ? (inMB.toFixed(2)*1) + ' Mb' : this.upload_size + ' Kb');
                    }
                    info += '</div>';
					return '<div class="fields '+cff_esc_attr(this.csslayout)+' '+this.name+' cff-file-field" id="field'+this.form_identifier+'-'+this.index+'" style="'+cff_esc_attr(this.getCSSComponent('container'))+'">'+
                        '<label for="'+this.name+'" style="'+cff_esc_attr(this.getCSSComponent('label'))+'">'+cff_sanitize(this.title, true)+''+((this.required)?"<span class='r'>*</span>":"")+'</label>'+
                        '<div class="dfield">'+
                        '<div class="cff-file-field-container ' + cff_esc_attr(this.size) + '" style="' + cff_esc_attr(this.getCSSComponent('files_container')) +'">'+
                                '<input aria-label="'+cff_esc_attr(this.title)+'" type="file" id="'+this.name+'" name="'+this.name+'[]"'+((this.accept.length) ? ' accept="'+this.accept+'"' : '')+((this.upload_size.length) ? ' upload_size="'+this.upload_size+'"' : '')+' class="field '+((this.required)?" required":"")+'" '+((this.multiple) ? 'multiple' : '')+' />'+
                                '<div id="'+this.name+'_clearer" class="cff-file-clearer"></div>'+
                                ((this._patch) ? '<input type="hidden" id="'+this.name+'_patch" name="'+this.name+'_patch" value="1" />' : '')+
                                '<div class="clearer"></div>'+
                                info+
                            '</div>'+
                            '<span class="uh" style="'+cff_esc_attr(this.getCSSComponent('help'))+'">'+cff_sanitize(this.userhelp, true)+'</span>'+
                        '</div>'+
                    '<div class="clearer"></div></div>';
				},
			after_show:function()
			{
				var me = this;

				if(!('accept' in $.validator.methods))
					$.validator.addMethod("accept", function(value, element, param)
					{
						if(this.optional(element)) return true;
						else{
							param = ( typeof param === "string" && param != "image/*" ) ? param.replace(/,/g, '|') : "png|jpe?g|gif";
							var regExpObj = new RegExp(".("+param+")$", "i");
							for(var i = 0, h = element.files.length; i < h; i++)
								if(!element.files[i].name.match(regExpObj)) return false;
							return true;
						}
					});

				if(!('upload_size' in $.validator.methods))
					$.validator.addMethod("upload_size", function(value, element,params)
					{
						if(this.optional(element)) return true;
						else{
							var total = 0;
							for(var i = 0, h = element.files.length; i < h; i++)
								total += element.files[i].size/1024;
							return (total <= params);
						}
					});

				$('#'+me.name).on( 'click', function(){
					me._files_list = [];
					if ( me.multiple ) {
						for ( var i = 0; i < this.files.length; i++ ) {
							me._files_list.push( this.files[i] );
						}
					}
				});

				$('#'+me.name).on( 'change', function(){

					var h = this.files.length;

					$(this).siblings('span.files-list').remove();
					$('[id="'+me.name+'_patch"]').remove();
					if(1 <= h || me._files_list.length )
					{
						if ( me.multiple && typeof DataTransfer != 'undefined' ) {
							try {
								var _dataTransfer = new DataTransfer(),
									_preventDuplication = {};
								// Copy from files input tags
								for (var i = 0; i < h; i++) {
									_dataTransfer.items.add( this.files[i] );
									_preventDuplication[ this.files[i]['name'] + '|' + this.files[i]['size'] ] = true;
								}

								// Copy from list
								for(var i = 0, k = me._files_list.length; i < k; i++) {
									if ( me._files_list[i]['name'] + '|' + me._files_list[i]['size'] in _preventDuplication ) continue;
									_dataTransfer.items.add( me._files_list[i] );
								}

								this.files = _dataTransfer.files;
								h = this.files.length;
							} catch ( err ) {
								console.log( err );
							}
						}

						var filesContainer = $('<span class="files-list"></span>');
						for(var i = 0; i < h; i++)
						{
							(function(i, file){
								if(me.preview && file.type.match('image.*') && 'FileReader' in window)
								{
									var reader = new FileReader();
									reader.onload = function (e) {
										var img = $('<img style="'+cff_esc_attr(me.getCSSComponent('thumbnail'))+'">');
										img.attr('src', e.target.result).css('maxWidth', '100%');
										if(me.thumb_height != '') img.attr('height', me.thumb_height);
										if(me.thumb_width  != '') img.attr('width', me.thumb_width);
										filesContainer.append($('<span></span>').append(img));
									};
									reader.readAsDataURL(file);
								}
								else if(1 <= h){filesContainer.append($('<span>').text(file.name));}
							})(i, this.files[i]);
						}
						$('#'+this.id+'_clearer').after(filesContainer);
					}
				});

                $('#'+me.name+'_clearer').on( 'click', function(){ me._files_list= []; $('#'+me.name).val('').trigger('change').valid();});
			},
			val: function(raw, no_quotes, disable_ignore_check)
			{
                raw = raw || false;
                no_quotes = no_quotes || false;
				var e = (disable_ignore_check) ? $("[id='"+this.name+"']") : $("[id='"+this.name+"']:not(.ignore)"),
                    result = raw ? [] : '',
                    separator = '';
				if(e.length)
                {
                    if(raw) result = Array.prototype.slice.call(e[0].files);
                    else
                    {
                        for(var i = 0, h = e[0].files.length; i<h; i++)
                        {
                            result += separator+e[0].files[i].name;
                            separator = ', ';
                        }
                        result = $.fbuilder.parseValStr(result, raw, no_quotes);
                    }
                }
				return result;
			}
		}
	);