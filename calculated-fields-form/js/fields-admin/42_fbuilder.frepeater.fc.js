	$.fbuilder.typeList.push(
		{
			id: "frepeater",
			name: "Repeater",
			control_category: 10
		}
	);

	$.fbuilder.controls[ 'frepeater' ] = function(){};
	$.extend(
		true,
		$.fbuilder.controls[ 'frepeater' ].prototype,
		$.fbuilder.controls[ 'fcontainer' ].prototype,
		{
			ftype: "frepeater",
			_developerNotes: '',
			shortlabel:"",
			fields: [],
			columns: 1,
			align: "top",
			rearrange: 0,

			// Repeater-specific settings.
			// User feedback: NO title (like Div), so this object has no `title` property.
			addButtonLabel: 'Add row',
			removeButtonLabel: 'Remove',
			maxRows: 100, // 0 = unlimited

			initAdv: function () {
				delete this.advanced.css.label;
				delete this.advanced.css.input;
				delete this.advanced.css.help;
				if (!('add_row_button' in this.advanced.css)) this.advanced.css.add_row_button = { label: 'Add row button', rules: {} };
				if (!('remove_row_button' in this.advanced.css)) this.advanced.css.remove_row_button = { label: 'Remove row button', rules: {} };
				if (!('row' in this.advanced.css)) this.advanced.css.row = { label: 'Fields row', rules: {} };
				if (!('container' in this.advanced.css)) this.advanced.css.container = { rules: {} };
				this.advanced.css.container.label = 'Fields container in row';
				if (!('field' in this.advanced.css)) this.advanced.css['field'] = { 'label': 'Field', 'rules': {} };
			},
			_instructions: function() {
				let field_name       = this.name;
				let contained_name   = this.fields.length ? this.fields[0] : 'fieldname123';
				let contained_number = contained_name.replace( /[^\d]/g, '' );

				return '<div style="border:1px solid red; color:red; padding:8px;margin:8px 0;text-align:center;">Experimental Control</div>' +
					'<details style="margin-top:8px;padding-bottom:8px;margin-bottom:8px !important;font-size:12px;">' +
					'<summary style="color:var(--wp-admin-theme-color);cursor:pointer;">How to use this Repeater</summary>' +
					'<p style="font-size:12px;border-left:1px solid #ccc;padding-left:8px;">' +
						'<b>Inside equations</b>, you can access the matrix of rows and fields using the syntax:<br><b>'+field_name+'[\'rows\']</b><br><br>For example, to access the value of '+contained_name+' in the third row (the row index starts at zero), use:<br><b>'+field_name+'[\'rows\'][2]['+contained_name+'|n]</b><br><br><i>The |n modifier tells the plugin that you are referring directly to the field name.</i><br><br>If you find it easier, you can use just the numeric component of the field name:<br><b>'+field_name+'[\'rows\'][2]['+contained_number+']' +
						'</b><br><br>' +
						'To access the array of column sums, use:<br><b>'+field_name+'[\'total\']</b><br><br>For example, to get the sum of all values of '+contained_name+' across every row, use:<br><b>'+field_name+'[\'total\']['+contained_name+'|n]</b><br>- or -<br><b>'+field_name+'[\'total\']['+contained_number+']' +
						'</b><br><br>' +
						'<b>To include the Repeater in notification emails</b>, use <b>&lt;%'+field_name+'%&gt;</b> directly and the plugin will include all the information from the inner fields. Or, if you want to control what gets included and how it looks, use the row/endrow block. For example:' +
						'<br><br><b>' +
						'&lt;%'+field_name+'_row%&gt;<br>' +
						'&lt;b&gt;&lt;%'+contained_name+'_label%&gt;&lt;/b&gt;: &lt;i&gt;&lt;%'+contained_name+'_value%&gt;&lt;/i&gt;<br>' +
						'&lt;%'+field_name+'_endrow%&gt;' +
						'</b>' +
					'</p>' +
				'</details>';
			},
			showTitle: function() { return ''; },
			showUserhelp: function() { return ''; },
			showShortLabel:function()
			{
				return this._instructions() + $.fbuilder.showSettings.showShortLabel(this.shortlabel);
			},
			showSpecialDataInstance: function()
			{
				var me = this;
				return $.fbuilder.controls[ 'fcontainer' ].prototype.showSpecialDataInstance.call( this )
					+ '<hr/>'
					+ '<div><label>Add Button Label</label>'
					+ '<input type="text" name="sAddButtonLabel" id="sAddButtonLabel" value="' + cff_esc_attr( me.addButtonLabel ) + '" class="large"></div>'
					+ '<div><label>Remove Button Label</label>'
					+ '<input type="text" name="sRemoveButtonLabel" id="sRemoveButtonLabel" value="' + cff_esc_attr( me.removeButtonLabel ) + '" class="large"></div>'
					+ '<div><label>Max Rows (0 = unlimited)</label>'
					+ '<input type="number" name="sMaxRows" id="sMaxRows" value="' + cff_esc_attr( me.maxRows ) + '" min="0" class="large"></div>';
			},

			editItemEvents: function()
			{
				var me = this;
				$.fbuilder.controls[ 'fcontainer' ].prototype.editItemEvents.call( this );
				$.fbuilder.controls[ 'ffields' ].prototype.editItemEvents.call(
					this,
					[
						{ s: "#sAddButtonLabel",    e: "change keyup", l: "addButtonLabel" },
						{ s: "#sRemoveButtonLabel", e: "change keyup", l: "removeButtonLabel" },
						{ s: "#sMaxRows",           e: "change keyup", l: "maxRows",
						  f: function( el ) { return Math.max( 0, parseInt( el.val(), 10 ) || 0); } }
					]
				);
			},

			acceptedChild: function( childType )
			{
				var containers = [ 'fdiv', 'ffieldset', 'fpopup', 'frepeater' ];
				if ( containers.indexOf( childType ) !== -1 ) {
					this.alertInvalidChild( 'container' );
					return false;
				}
				if ( childType === 'fPageBreak' ) {
					this.alertInvalidChild( 'pagebreak' );
					return false;
				}
				var dsTypes = [];
				if ( typeof $.fbuilder.categoryList !== 'undefined'
					&& $.fbuilder.categoryList[ 20 ]
					&& $.fbuilder.categoryList[ 20 ].typeList ) {
					$.each( $.fbuilder.categoryList[ 20 ].typeList, function( _, t ) {
						if (t in $.fbuilder.typeList) {
							dsTypes.push($.fbuilder.typeList[t].id);
						}
					} );
				}
				if ( dsTypes.indexOf( childType ) !== -1 ) {
					this.alertInvalidChild( 'datasource' );
					return false;
				}
				return true;
			},

			alertInvalidChild: function( kind )
			{
				var msgs = {
					container:  'Repeater controls do not support other container controls inside them. Please add container controls outside the Repeater.',
					pagebreak:  'Repeater controls do not support page break controls inside them. Please add the page break control outside the Repeater.',
					datasource: 'Repeater controls do not support Data Source controls inside them. Please add Data Source controls outside the Repeater.'
				};
				if ( typeof console !== 'undefined' ) console.warn( '[CFF frepeater] rejected child:', kind );
				alert( msgs[ kind ] || 'Invalid child control type for a Repeater.' );
			},

			// Lifecycle methods — inherit from fcontainer. Mirrors the fieldset/popup pattern.
			remove: function()
			{
				return $.fbuilder.controls[ 'fcontainer' ].prototype.remove.call( this );
			},
			duplicateItem: function( currentField, newField )
			{
				return $.fbuilder.controls[ 'fcontainer' ].prototype.duplicateItem.call( this, currentField, newField );
			},
			after_show: function()
			{
				return $.fbuilder.controls[ 'fcontainer' ].prototype.after_show.call( this );
			},

			// Admin-builder DOM representation. Mirrors Div/Popup layout (the dfield + fcontainer
			// shell that the sortable walks) plus a Repeater badge so the admin can identify it.
			display: function( css_class )
			{
				css_class = css_class || '';
				return '<div data-control="' + this.ftype +'" class="fields ' + this.name + ( ( this.collapsed ) ? ' collapsed' : '' ) + ' ' + this.ftype + ' ' + css_class + '" id="field' + this.form_identifier + '-' + this.index + '" title="' + this.controlLabel( 'Repeater' ) + '" style="width:100%;">'
					+ '<div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'
					+ this.iconsContainer( '<div title="Collapse (Ctrl+L)" class="collapse ui-icon ui-icon-folder-collapsed "></div><div title="Uncollapse (Ctrl+U)" class="uncollapse ui-icon ui-icon-folder-open "></div>' )
					+ $.fbuilder.controls[ 'fcontainer' ].prototype.columnsSticker.call( this )
					+ '<div class="dfield" style="width:100%;">'
					+ this.showColumnIcon()
					+ '<div class="fcontainer cff-repeater-admin">'
					+ '<span class="developer-note">' + cff_esc_attr( this._developerNotes ) + '</span>'
					+ '<div class="cff-repeater-badge" style="margin:6px 0;padding:4px 8px;background:#f0f6fc;border-left:3px solid #2271b1;font-size:12px;">'
					+ '<strong>Repeater</strong> &mdash; max ' + ( this.maxRows === 0 ? '&infin;' : this.maxRows ) + ' rows &middot; ' + cff_esc_attr( this.fields.length ) + ' inner field(s)'
					+ '</div>'
					+ '<label class="collapsed-label">Collapsed [' + this.name + ']</label>'
					+ '<div class="fieldscontainer"></div>'
					+ '</div></div><div class="clearer"></div></div>';
			}
		}
	);