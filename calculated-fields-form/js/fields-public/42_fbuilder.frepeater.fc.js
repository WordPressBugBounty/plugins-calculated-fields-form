	$.fbuilder.controls[ 'frepeater' ] = function(){};
	$.extend(
		$.fbuilder.controls[ 'frepeater' ].prototype,
		$.fbuilder.controls[ 'ffields' ].prototype,
		{
			ftype: "frepeater",
			fields: [],
			matrix: [],
			columns: 1,
			align: "top",
			rearrange: 0,

			addButtonLabel: 'Add row',
			removeButtonLabel: 'Remove',
			maxRows: 100,
			n: 0,

			_isPlainObject: function (v) {
				return Object.prototype.toString.call(v) === '[object Object]';
			},

			_triggerChange: function() {
				$('[id="' + this.name + '"]').trigger('change');
			},

			_addToMatrix: function(row) {
				this.matrix.push(row);
				$('[id="' + this.name + '"]').val(JSON.stringify(this.matrix));
				this._triggerChange();
			},

			_removeFromMatrix: function(index) {
				if ( ! index || ! ( index in this.matrix ) ) return;
				let row = this.matrix.splice(index, 1)[0];
				$('[id="' + this.name + '"]').val(JSON.stringify(this.matrix));
				let formObj = $.fbuilder.forms[this.form_identifier];
				for (let i in row) {
					let field = getField(row[i], this.form_identifier);
					if (field) formObj.removeItem(field);
				}
				this._triggerChange();
			},

			_add_row: function (includeRemoveButton) {
				includeRemoveButton = includeRemoveButton || false;
				let row = '' +
					'<div class="cff-repeater-row" style="' + cff_esc_attr(this.getCSSComponent('row')) + '">' +
						'<div class="cff-repeater-fields-container ' + (this.align == 'bottom' ? 'cff-align-container-bottom' : '') + '" style="' + cff_esc_attr(this.getCSSComponent('container')) + '"></div>' +
						'<div class="cff-repeater-row-controls">' +
						'<div class="fields">' +
						(
							includeRemoveButton
							? '<input type="button" class="cff-repeater-remove-row" value="' + cff_esc_attr(this.removeButtonLabel) + '" style="' + cff_esc_attr(this.getCSSComponent('remove_row_button')) + '" />'
							: ''
						) +
						'</div>' +
						'</div>' +
					'</div>';
				this.n++;
				return row;
			},

			_add_to_row: function (e, l) {
				if (! l.length) return;

				// Extract pairs of basic fields names.
				let names = {};
				for (let i in l) {
					names[l[i]['o'].name.match(/fieldname\d+/)[0]] = l[i]['c'].name.match(/fieldname\d+/)[0];
				}

				function escapeRegExp(str) {
					return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
				}

				// Replace fields names in the field attributes.
				function replaceFieldsNames(source) {
					if (typeof source === 'string') {
						for (let k in names) {
							if (source.indexOf(k) !== -1) {
								const pattern = new RegExp(escapeRegExp(k) + '(?!\\d)', 'g');
								source = source.replace(pattern, names[k]);
							}
						}
					} else if (source !== null && typeof source === 'object') {
						for (let k in source) {
							if (typeof source[k] !== 'function') {
								source[k] = replaceFieldsNames(source[k]);
							}
						}
					}
					return source;
				}

				function deepCloneSafe(obj) {
					if (obj === null || typeof obj !== 'object') return obj;

					if (typeof obj === 'function') return obj;

					if (Array.isArray(obj)) {
						return obj.map(item => deepCloneSafe(item));
					}

					const clone = {};
					for (const key of Object.keys(obj)) {
						clone[key] = deepCloneSafe(obj[key]);
					}
					return clone;
				}

				function cloneFieldProperties(originalField, clonedField) {
					for (let k in originalField) {
						if (originalField.hasOwnProperty(k) && typeof originalField[k] !== 'function') {
							clonedField[k] = replaceFieldsNames(deepCloneSafe(originalField[k]));
						}
					}
					return clonedField;
				}

				for (let i in l) {
					// First, clone the original field attributes.
					l[i]['c'] = cloneFieldProperties(l[i]['o'], l[i]['c']);
					$.fbuilder.forms[this.form_identifier].addItem(l[i]['c']);
				}

				this._addToMatrix(names);

				e = e.find('.cff-repeater-fields-container');
				let to_ignore = 0; // Ignores the RecordSet DS and Hidden fields.
				for (let i = 0, h = l.length; i < h; i++) {
					let flag = true;
					let f = l[i]['c'],
						d = $(f.show());

					if (d.hasClass('cff-hidden-field')) { to_ignore++; }
					d.addClass('column' + this.columns);
					if (this.columns > 1) {
						if ((i - to_ignore) % this.columns == 0 && !this.rearrange) {
							d.css('clear', 'left');
							d.appendTo(e);
							if (i - to_ignore && this.align == "bottom") d.before('<div class="cff-row-breaker"></div>');
							flag = false;
						}
					}
					if (flag) d.appendTo(e);
					if ('init' in f) f.init();
					if ('after_show' in f) f.after_show();
				}

				e.find('input.codepeoplecalculatedfield, select.depItemSel, input.depItem').each(function(){
					$(this).trigger('cff-dep-event');
				});
			},

			init: function(){
				if (this.fields.length) {
					let row = {};
					for (let i in this.fields) {
						row[this.fields[i]] = this.fields[i];
					}
					this._addToMatrix(row);
				}
			},

			show: function()
			{
				return '<div class="fields ' + cff_esc_attr(this.csslayout) + ' ' + this.name + ' cff-repeater-field cff-container-field" id="field' + this.form_identifier + '-' + this.index + '" style="' + cff_esc_attr(this.getCSSComponent('field')) + '">' +
					'<input type="hidden" name="' + this.name + '" id="' + this.name + '" value="' + cff_esc_attr(JSON.stringify(this.matrix))+'" />' +
					'<div id="' + this.name + '_fields" class="cff-repeater-rows-container">' +
					this._add_row() +
					'</div>' +
					'<div class="cff-repeater-controls">' +
					'<input id="' + this.name + '_add_button" type="button" class="cff-repeater-add-row" value="' + cff_esc_attr(this.addButtonLabel) + '" style="' + cff_esc_attr(this.getCSSComponent('add_row_button')) + (this.maxRows <= 1 ? 'display:none;' : '') + '" />' +
					'</div>' +
					'<div class="clearer"></div></div>';
			},

			after_show: function( e )
			{
				let me = this;
				$.fbuilder.controls['fcontainer'].prototype.after_show.call(this, $('#' + this.name + '_fields .cff-repeater-fields-container'));

				$('#' + this.name + '_add_button').off('click').on('click', function () {
					me.add_row();
				});

				$('#' + this.name + '_fields').on('click', '.cff-repeater-remove-row', function () {
					let row = $(this).closest('.cff-repeater-row');
					if ( ! row.length ) return;
					let index = row.index();
					me.remove_row(index);
				});

				// Each change on container field must be escalated to the repeater field.
				$(document).on('change', '#' + this.name + '_fields :input:not([name="' + this.name + '"])', function () {
					me._triggerChange();
				});

				// Reset: reconcile matrix/objects/DOM to initial state.
				// Follows the same pattern as fsummary (16_fbuilder.fsummary.js:49).
				let form = me.jQueryRef().closest('form');
				if (form.length) {
					form.on('reset', function () {
						setTimeout(function () {
							// Remove all rows except the initial one (index 0).
							for (let i = me.matrix.length - 1; i >= 1; i--) {
								me.remove_row(i);
							}
							me._triggerChange();
							}, 10
						);
					});
				}
			},

			val: function (raw, no_quotes, disable_ignore_check) {
				let rows = [],
					total_row = {};
				no_quotes = (typeof no_quotes === 'undefined') ? true : no_quotes;
				if( $('[id="' + this.name + '"]').is(".ignorefield,.ignore") ) return false;
				for (let i in this.matrix) {
					rows[i] = {};
					for (let j in this.matrix[i]) {
						let f = getField(this.matrix[i][j], this.form_identifier);
						if (f) {
							let n = String(j).replace(/[^\d]/g, ''),
								v = f.val(raw, no_quotes, disable_ignore_check),
								p = f.val(0, 1, 0);

							rows[i][j] = v;
							rows[i][n] = rows[i][j];
							if( ! ( j in total_row ) ) {
								total_row[j] = 0;
							}
							total_row[j] = SUM(total_row[j], p);
							total_row[n] = total_row[j];
						}
					}
				}
				return {
					rows: rows,
					total: total_row
				};
			},

			setVal: function (v, nochange, _default) {
				_default = _default || false;
				nochange = nochange || false;
				if (typeof v === 'string') {
					try { v = JSON.parse(v); } catch (e) { return; }
				}
				if(! Array.isArray(v)) return;
				for (let i in v) {
					if (0 < this.maxRows && this.maxRows <= i) break;
					if (! this._isPlainObject(v[i])) continue;
					if (! (i in this.matrix)) this.add_row();
					for (let j in v[i]) {
						if (! (j in this.matrix[i])) continue;
						let f = getField(this.matrix[i][j], this.form_identifier);
						if (f) f.setVal(v[i][j], nochange, _default);
					}
				}

				// Shrink: if v is shorter than current matrix, remove surplus rows
				// from end to start (avoids index shifting issues).
				if (v.length < this.matrix.length) {
					for (let i = this.matrix.length - 1, h = v.length; i >= h; i--) {
						this.remove_row(i);
					}
				}

			},

			showHideDep: function (toShow, toHide, hiddenByContainer, interval) {
				if (typeof hiddenByContainer == 'undefined') hiddenByContainer = {};
				var me = this,
					isHidden = (typeof toHide[me.name] != 'undefined' || typeof hiddenByContainer[me.name] != 'undefined'),
					fId,
					result = [];

				for (let i in me.matrix) {
					if ( ! me._isPlainObject(me.matrix[i]) ) continue;
					for (let j in me.matrix[i]) {
						fId = me.matrix[i][j] + me.form_identifier;
						if (!/fieldname/i.test(fId)) continue;
						if (isHidden) {
							if (typeof hiddenByContainer[fId] == 'undefined') hiddenByContainer[fId] = {};
							if (typeof hiddenByContainer[fId][me.name] == 'undefined') {
								hiddenByContainer[fId][me.name] = {};

								if (typeof toHide[fId] == 'undefined') {
									$('.' + fId + ' [id*="' + fId + '"],.' + fId).closest('.fields').addClass('ignorefield').hide();
									$('.' + fId + ' [id*="' + fId + '"]:not(.ignore)').addClass('ignore').trigger('add-ignore');
									result.push(fId);
								}
							}
						}
						else {
							if (typeof hiddenByContainer[fId] != 'undefined') {
								delete hiddenByContainer[fId][me.name];
								if ($.isEmptyObject(hiddenByContainer[fId])) {
									delete hiddenByContainer[fId];
									if (typeof toHide[fId] == 'undefined') {
										$('.' + fId + ' [id*="' + fId + '"],.' + fId).closest('.fields').removeClass('ignorefield').fadeIn(interval || 0);
										$('.' + fId + ' [id*="' + fId + '"].ignore').removeClass('ignore').trigger('remove-ignore');
										result.push(fId);
									}
								}
							}
						}
					}
				}
				return result;
			},

			add_row: function() {
				let me = this;
				if (me.maxRows <= 0 || me.n < me.maxRows) {
					// Cloning fields objects.
					let formObj = $.fbuilder.forms[me.form_identifier],
						getNumber = (n) => parseInt(String(n).match(/\d+$/), 10),
						newFieldNumber = formObj.getItemsNames().reduce((a, b) => {
							a = getNumber(a);
							b = getNumber(b);
							return a > b ? a : b;
						}, 0) + 1;

					let newFields = [],
						newRow = $(me._add_row(true));

					newRow.appendTo('#' + me.name + '_fields');

					for (let i in me.fields) {
						let originalField = formObj.getItem(me.fields[i]);
						if (originalField && originalField.ftype in $.fbuilder.controls) {
							// Cloning field object.
							let clonedField = new $.fbuilder.controls[originalField.ftype]();
							// New field name.
							let newFieldName = 'fieldname' + newFieldNumber + me.form_identifier;
							clonedField.name = newFieldName;
							newFields.push({ o: originalField, c: clonedField });
							newFieldNumber++;
						}
					}
					me._add_to_row(newRow, newFields);
				}
				if (me.maxRows > 0 && me.n >= me.maxRows) {
					$('#' + me.name + '_add_button').hide();
				}
			},

			remove_row: function (index) {
				if (index == 0) return;

				let me = this,
					row = $('#'+me.name+'_fields .cff-repeater-row').eq(index);

				if (row.length) row.remove();
				me._removeFromMatrix(index);
				me.n--;
				if (me.n < me.maxRows) {
					$('#' + me.name + '_add_button').show();
				}
			}
		}
	);