	$.fbuilder.controls['ftextarea'] = function(){};
	$.extend(
		$.fbuilder.controls['ftextarea'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			title:"Untitled",
			ftype:"ftextarea",
            autocomplete:"off",
			predefined:"",
			predefinedClick:false,
			required:false,
			readonly:false,
			size:"medium",
			minlength:"",
			maxlength:"",
            rows:4,
			aiAssistant:false,
			show:function()
				{
					this.minlength = cff_esc_attr(String(this.minlength).trim());
					this.maxlength = cff_esc_attr(String(this.maxlength).trim());
					this.predefined = this._getAttr('predefined', true);
					this.predefined = String(this.predefined).replace(/<(\s*\/\s*)?textarea(\s*\/\s*)?>/gi, '');
					return '<div class="fields '+cff_esc_attr(this.csslayout)+' '+this.name+' cff-textarea-field" id="field'+this.form_identifier+'-'+this.index+'" style="'+cff_esc_attr(this.getCSSComponent('container'))+'"><label for="'+this.name+'" style="'+cff_esc_attr(this.getCSSComponent('label'))+'">'+cff_sanitize(this.title, true)+''+((this.required)?"<span class='r'>*</span>":"")+'</label><div class="dfield"><textarea aria-label="'+cff_esc_attr(this.title)+'" '+((!/^\s*$/.test(this.rows)) ? 'rows='+cff_esc_attr(this.rows) : '')+' id="'+this.name+'" name="'+this.name+'"'+((this.minlength.length) ? ' minlength="'+cff_esc_attr(this.minlength)+'"' : '')+((this.maxlength.length) ? ' maxlength="'+cff_esc_attr(this.maxlength)+'"' : '')+' class="field '+cff_esc_attr(this.size)+((this.required)?" required":"")+'" '+((this.readonly)?'readonly':'')+' autocomplete="'+this.autocomplete+'" style="'+cff_esc_attr(this.getCSSComponent('textarea'))+'">'+((!this.predefinedClick) ? this.predefined : '')+'</textarea>'+
					(this.maxlength.length ? '<div class="'+this.name+'_counter cff-textarea-counter '+cff_esc_attr(this.size)+'"></div>' : '')+
					'<span class="uh" style="'+cff_esc_attr(this.getCSSComponent('help'))+'">'+cff_sanitize(this.userhelp, true)+'</span></div><div class="clearer"></div></div>';
				},
			after_show:function(){
				let e = $('[id="'+this.name+'"]'),
					update_counter = function(e){
						$('.'+e.attr('name')+'_counter').text( e.val().length + '/' + e.attr('maxlength') );
					};

				update_counter(e);
				e.on('keyup change', function(){ update_counter( $(this) ); });
			},
			val:function(raw, no_quotes, disable_ignore_check)
				{
					raw = raw || false;
                    no_quotes = no_quotes || false;
					var e = (disable_ignore_check) ? $('[id="'+this.name+'"]') : $('[id="'+this.name+'"]:not(.ignore)'),
						v = e.length ? e.val() : (raw ? '' : 0);
					v = $.fbuilder.parseValStr(v, raw, no_quotes);
					if(!raw) v = String(v).replace(/[\n\r]+/g, ' ');
					else if(!no_quotes) v = String(v).replace(/^"/, "`").replace(/"$/, "`");
					return v;
				},
			setVal:function( v, nochange )
				{
					let e 	= $( "[id='" + this.name + "']" ),
						bk 	= e.val();
					e.val( String(v).replace(/<(\s*\/\s*)?textarea(\s*\/\s*)?>/gi, '') );
					if ( ! nochange && bk !== e.val() ) e.trigger('change');
				}
		}
	);