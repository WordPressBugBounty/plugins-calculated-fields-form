	$.fbuilder.typeList.push(
		{
			id:"fhtml",
			name:"HTML Content",
			control_category:1
		}
	);
	$.fbuilder.controls['fhtml']=function(){  this.init();  };
	$.extend(
		true,
		$.fbuilder.controls['fhtml'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			ftype:"fhtml",
			_developerNotes:'',
			allowscript:-1,
			fcontent: "",
			initAdv:function(){
					delete this.advanced.css['label'];
					delete this.advanced.css['input'];
					delete this.advanced.css['help'];
				},
			display:function( css_class )
				{
					css_class = css_class || '';
					let content = this.fcontent
									.replace(/<script\b[^>]*>([\s\S]*?)<\/script>/gi, '')
									.replace(/<style\b[^>]*>([\s\S]*?)<\/style>/gi, '')
									.replace(/(\b)(on[a-z]+)\s*=/gi, "$1_$2=");
					content = cff_sanitize($('<div/>').html(content).find('script,style').remove().end().html());
					content = /^\s*$/.test(content) ? '&lt;HTML&gt;' : content.replace( /<\s*(input|textarea|button|select|radio|checkbox)(\b)/ig, '<$1 disabled $2' );

					return '<div class="fields '+this.name+' '+this.ftype+' '+css_class+' fhtml" id="field'+this.form_identifier+'-'+this.index+'" title="'+this.controlLabel('HTML Content')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer('', false)+'<span class="developer-note">'+$.fbuilder.htmlEncode(this._developerNotes)+'</span>'+this.showColumnIcon()+'<div class="fhtml-content">'+content+'</div><div class="clearer"></div></div>';
				},
			editItemEvents:function()
				{
					var evt=[
						{s:'[name="sAllowscript"]', e:"change", l:"allowscript", f: function(el){return (el.is(':checked')) ? 1 : 0;}},
						{s:"#sContent",e:"change keyup", l:"fcontent"}
					];
					$.fbuilder.controls['ffields'].prototype.editItemEvents.call(this,evt);

					// Code Editor
					if('codeEditor' in wp)
					{
						setTimeout(function(){
                            if($('#tabs-2 .CodeMirror').length) return;
							try{ delete HTMLHint.rules['spec-char-escape']; } catch(err) {}
							var htmlEditorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {}, editor;
							htmlEditorSettings.codemirror = _.extend(
								{},
								htmlEditorSettings.codemirror,
								{
									indentUnit: 2,
									tabSize: 2,
									autoCloseTags: false,
									mode:{name:'htmlmixed'}
								}
							);
							htmlEditorSettings['htmlhint']['spec-char-escape'] = false;
							htmlEditorSettings['htmlhint']['alt-require'] = false;
							htmlEditorSettings['htmlhint']['tag-pair'] = false;
							if($('#sContent').length) {
								editor = wp.codeEditor.initialize($('#sContent'), htmlEditorSettings);
								editor.codemirror.on('change', function(cm){ $('#sContent').val(cm.getValue()).trigger('change');});
								editor.codemirror.on('keydown', function(cm, evt){
									if ( 'Escape' == evt.key && $('.CodeMirror-hint').length ) {
										evt.stopPropagation();
									}
								});
							}

							$('.cff-editor-extend-shrink').on('click', function(){
								let e = $(this).closest('.cff-editor-container'),
									c = e.closest('.ctrlsColumn');
								e.toggleClass('fullscreen');
								if(e.hasClass('fullscreen')) c.css('z-index', 99991);
								else c.css('z-index', 999);
							});

						}, 10);
					}
				},
			showContent:function()
				{
					if( this.allowscript == -1 ) {
						this.allowscript = 0;
						if( /(<script\b)|(\bon[a-z]+\s*=)/i.test( this.fcontent ) ) {
							this.allowscript = 1;
						}
						$('[name="sAllowscript"]').change();
					}
					return '<div><label><input type="checkbox" name="sAllowscript" id="sAllowscript" '+(this.allowscript ? 'CHECKED' : '')+'> Accept JavaScript code in content</label><hr /></div><div class="cff-editor-container"><label style="display:block;" for="sContent"><div class="cff-editor-extend-shrink" title="Fullscreen"></div>HTML Content</label><textarea class="large" name="sContent" id="sContent" style="height:150px;">'+cff_esc_attr(this.fcontent)+'</textarea></div>';
				},
			showAllSettings:function()
				{
					return this.fieldSettingsTabs(this.showFieldType()+this.showName()+this.showContent()+this.showCsslayout());
				}
		}
	);