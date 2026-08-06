	$.fbuilder.controls['fsummary'] = function(){};
	$.extend(
		$.fbuilder.controls['fsummary'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			title:"Summary",
			ftype:"fsummary",
			fields:"",
			fieldsArray:[],
			exclude_empty: false,
			titleClassname:"summary-field-title",
			valueClassname:"summary-field-value",
			init:function() {
				let me = this;
				me.fieldsArray = [];
				if ('string' != typeof me.fields) return;
				let p = String(me.fields.replace(/\,+/g, ',')).trim().split(','),
					l = p.length;
				if(l) {
					for (let i = 0; i < l; i++) {
						p[i] = String(p[i]).trim();
						if (p[i]) {
							me.fieldsArray.push(p[i]);
						}
					}
				}
			},
			show:function() {
				let me = this;
				return '<div class="fields '+cff_esc_attr(me.csslayout)+' '+me.name+' cff-summary-field" id="field'+me.form_identifier+'-'+me.index+'" style="'+cff_esc_attr(me.getCSSComponent('container'))+'">'+((!/^\s*$/.test(me.title)) ? '<h2 style="'+cff_esc_attr(me.getCSSComponent('label'))+'">'+cff_sanitize(me.title, true)+'</h2>': '')+'<div id="'+me.name+'"></div></div>';
			},
			after_show: function () {
				let me = this;
				if (me._summaryInitialized) return;
				me._summaryInitialized = true;
				if (! me.fieldsArray.length) return;

				for (let i = 0, h = me.fieldsArray.length; i < h; i++) {
					let item = getField(me.fieldsArray[i], me.form_identifier);
					if (!item) continue;
					$(document).on('change', '[id*="' + item.name + '"]', function () { me.update(); });
				}
				let form = me.jQueryRef().closest('form');
				$(document).on('showHideDepEvent', form, function (evt) { me.update(); });
				form.on('reset', function () { setTimeout(function () { me.update(); }, 10); });

				me.update();
			},
			update: function () {
				let me = this;
				if (me.fieldsArray.length == 0) {
					$('[id="' + me.name + '"]').html('').trigger('cff-summary-update');
					return;
				}

				let str = '';
				for (let i = 0, h = me.fieldsArray.length; i < h; i++) {
					let item = getField(me.fieldsArray[i], me.form_identifier);
					if (!item) continue;

					if (item.ftype === 'frepeater') {
						for (let rowIdx in item.matrix) {
							for (let childOrig in item.matrix[rowIdx]) {
								let childItem = getField(item.matrix[rowIdx][childOrig], me.form_identifier);
								if (!childItem) continue;
								str += me._renderItem(childItem);
							}
						}
					} else {
						str += me._renderItem(item);
					}
				}

				$('[id="' + me.name + '"]').html(str).trigger('cff-summary-update');
			},
			_renderItem: function(item) {
				let me = this,
					resolvedId = item.name;

				let e = $('[id="' + resolvedId + '"]:not(.ignore),[id^="' + resolvedId + '_rb"]:not(.ignore),[id^="' + resolvedId +'_cb"]:not([type="number"]):not(.ignore)');

				if (!e.length) return '';

				let l = $('[id="'+resolvedId+'"],[id^="'+resolvedId+'_rb"],[id^="'+resolvedId+'_cb"]')
						.closest('.fields')
						.find('label:first')
						.clone()
						.find('.r,.dformat')
						.remove()
						.end(),
					t = String(l.text()).trim().replace(/\:$/,''),
					v = [];

				e.each(function(){
					let e = $(this);
					if(/(checkbox|radio)/i.test(e.attr('type')) && !e.is(':checked')) return;
					else if(e[0].tagName == 'SELECT') {
						let vt = [];
						e.find('option:selected').each(function(){ vt.push($(this).attr('vt')); });
						v.push(vt.join(', '));
					} else {
						if(e.attr('vt')) {
							let q = $('[id="'+e.attr('id')+'_quantity"]');
							v.push(e.attr('vt')+(q.length ? ' ('+Math.max(q.val(),1)+')' : ''));
						} else if(e.attr('summary')) {
							v.push($('#'+resolvedId).closest('.fields').find('.'+e.attr('summary')+resolvedId).html());
						} else {
							let d = $('[id="'+resolvedId+'_date"]');
							if(d.length) {
								if(d.is(':disabled')) v.push(e.val().replace(d.val(),''));
								else v.push(e.val());
							} else {
								if(e.attr('type') == 'file') {
									let f = [];
									$.each(e[0].files, function(i,o){ f.push(o.name); });
									v.push(f.join(', '));
								} else if(!e.hasClass('cpefb_error message')) {
									let c = $('[id="'+resolvedId+'_caption"]');
									if(c.length && !/^\s*$/.test(c.html())) v.push(c.html());
									else if(e.closest('.cff-phone-field').length) {
										let obj = getField(e);
										if(obj) v.push(obj.val(true, true));
									} else v.push(e.val());
								}
							}
						}
					}
				});

				v = v.join(', ');
				if(me.exclude_empty && v == '') return '';

				let str = '<div ref="'+cff_esc_attr(resolvedId)+'" class="cff-summary-item" style="'+cff_esc_attr(me.getCSSComponent('fields_rows'))+'">';
				if(!/^\s*$/.test(t)) {
					str += '<span class="'+cff_esc_attr(me.titleClassname)+' cff-summary-title" style="'+cff_esc_attr(me.getCSSComponent('fields_labels'))+'">'+cff_sanitize(t, true)+'</span>';
				}
				str += '<span class="'+cff_esc_attr(me.valueClassname)+' cff-summary-value" style="'+cff_esc_attr(me.getCSSComponent('fields_values'))+'">'+cff_sanitize(v, true)+'</span>';
				str += '</div>';
				return str;
			}
		}
	);