	$.fbuilder.controls[ 'fqrcode' ]=function(){};
	$.extend(
		$.fbuilder.controls[ 'fqrcode' ].prototype,
		$.fbuilder.controls[ 'ffields' ].prototype,
		{
			title:"",
			ftype:"fqrcode",
			predefined:"",
			required:false,
			readonly:false,
			size:"medium",
			show:function()
				{
					this.predefined = this._getAttr('predefined', true);
					return '<div class="fields '+cff_esc_attr(this.csslayout)+' '+this.name+' cff-qrcode-field" id="field'+this.form_identifier+'-'+this.index+'" style="'+cff_esc_attr(this.getCSSComponent('container'))+'"><label for="'+this.name+'" style="'+cff_esc_attr(this.getCSSComponent('label'))+'">'+cff_sanitize(this.title, true)+''+((this.required)?"<span class='r'>*</span>":"")+'</label><div class="dfield">'+
					'<div id="'+this.name+'_qrcontainer" class="'+cff_esc_attr(this.size)+' cff-qrcode-container"></div>'+
					'<input aria-label="'+cff_esc_attr(this.title)+'" id="'+this.name+'" name="'+this.name+'"'+' class="field '+cff_esc_attr(this.size)+((this.required)?" required":"")+'" '+' '+((this.readonly)?'readonly':'')+' type="text" value="'+cff_esc_attr(this.predefined)+'" style="'+cff_esc_attr(this.getCSSComponent('input'))+'" /><span class="uh" style="'+cff_esc_attr(this.getCSSComponent('help'))+'">'+cff_sanitize(this.userhelp, true)+'</span></div><div class="clearer"></div></div>';
				},
			after_show:function()
				{
					var me = this;
					$('#'+me.name).on(
						'focus',
						function(){
							var qrc = $('#fbuilder .cff-qrcode-container[id="'+me.name+'_qrcontainer"]');
							$('#fbuilder .cff-qrcode-container[id!="'+me.name+'_qrcontainer"]').html('');
							if (
								typeof Html5QrcodeScanner != 'undefined' &&
								qrc.html() == ''
							) {
								( new Html5QrcodeScanner( me.name+'_qrcontainer', { fps: 10 }, false ) ).render(
									function(decodedText, decodedResult){me.setVal(decodedText);$('#html5-qrcode-button-camera-stop').trigger('click');},
									function(error){console.warn(`Code scan error = ${error}`);}
								);
								qrc.prepend('<span class="cff-qrcode-field-close" onclick="fbuilderjQuery(this).parent().html(\'\')">X</span>');
							}
						}
					);

				},
			val:function(raw, no_quotes)
				{
					raw = true;
                    no_quotes = no_quotes || false;
					var e = $('[id="'+this.name+'"]:not(.ignore)');
					if(e.length) return $.fbuilder.parseValStr(e.val(), raw, no_quotes);
					return '';
				}
		}
	);