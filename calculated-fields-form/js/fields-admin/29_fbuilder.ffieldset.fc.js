	$.fbuilder.typeList.push(
		{
			id:"ffieldset",
			name:"Fieldset",
			control_category:10
		}
	);
	$.fbuilder.controls[ 'ffieldset' ]=function(){};
	$.extend(
		true,
		$.fbuilder.controls[ 'ffieldset' ].prototype,
		$.fbuilder.controls[ 'fcontainer' ].prototype,
		{
			title:"Untitled",
			ftype:"ffieldset",
			_developerNotes:'',
			fields:[],
			columns:1,
			rearrange: 0,
			collapsible:false, // Public
			defaultCollapsed: true, // Public
			collapsed:false, // Admin
            selfClosing:false,
			initAdv:function(){
					delete this.advanced.css.label;
					delete this.advanced.css.input;
					delete this.advanced.css.help;
					if ( ! ( 'legend' in this.advanced.css ) ) this.advanced.css.legend = {label: 'Legend',rules:{}};
					if ( ! ( 'container' in this.advanced.css ) ) this.advanced.css.container = {rules:{}};
					this.advanced.css.container.label = 'Fields container fieldset tag';
				},
			display:function( css_class )
				{
					css_class = css_class || '';
					return '<div class="fields '+this.name+((this.collapsed) ? ' collapsed' : '')+' '+this.ftype+' '+css_class+'" id="field'+this.form_identifier+'-'+this.index+'" title="'+this.controlLabel('Fieldset')+'" style="width:100%;"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer('<div title="Collapse" class="collapse ui-icon ui-icon-folder-collapsed "></div><div title="Uncollapse" class="uncollapse ui-icon ui-icon-folder-open "></div>')+'<div class="dfield" style="width:100%;">'+this.showColumnIcon()+'<FIELDSET class="fcontainer">'+( ( !/^\s*$/.test( this.title ) ) ? '<LEGEND>'+cff_esc_attr(this.title)+'</LEGEND>' : '' )+$.fbuilder.controls['fcontainer'].prototype.columnsSticker.call(this)+'<span class="developer-note">'+cff_esc_attr(this._developerNotes)+'</span><label class="collapsed-label">Collapsed ['+this.name+']</label><div class="fieldscontainer"></div></FIELDSET></div><div class="clearer"></div></div>';
				},
			editItemEvents:function()
				{
					$.fbuilder.controls[ 'fcontainer' ].prototype.editItemEvents.call(this);
					$.fbuilder.controls[ 'ffields' ].prototype.editItemEvents.call(this, [{s:"#sCollapsible",e:"click", l:"collapsible", f:function(el){return el.is(':checked');}}, {s:"#sCollapsedByDefault",e:"click", l:"defaultCollapsed", f:function(el){return el.is(':checked');}}, {s:"#sSelfClosing",e:"click", l:"selfClosing", f:function(el){return el.is(':checked');}}]);
				},
			remove : function()
				{
					return $.fbuilder.controls[ 'fcontainer' ].prototype.remove.call(this);
				},
			duplicateItem: function( currentField, newField )
				{
					return $.fbuilder.controls[ 'fcontainer' ].prototype.duplicateItem.call( this, currentField, newField );
				},
			after_show:function()
				{
					return $.fbuilder.controls[ 'fcontainer' ].prototype.after_show.call(this);
				},
			showTitle: function()
				{
					return '<label>Field Label</label><textarea class="large" name="sTitle" id="sTitle">'+cff_esc_attr(this.title)+'</textarea>';
				},
			showCollapsible:function()
				{
					return '<label><input type="checkbox" name="sCollapsible" id="sCollapsible" '+((this.collapsible)?"checked":"")+'> Make it collapsible</label>'+
					'<label style="padding-left:30px"><input type="checkbox" name="sCollapsedByDefault" id="sCollapsedByDefault" '+((this.defaultCollapsed)?"checked":"")+'> Collapsed by default</label>'+
					'<label style="padding-left:30px"><input type="checkbox" name="sSelfClosing" id="sSelfClosing" '+((this.selfClosing)?"checked":"")+'> Only one opened at a time <br><i>If there are several fieldsets configured as collapsible on the same level, this fieldset will auto-close when another fieldset is opened.</i></label>';
				},
			showSpecialDataInstance: function()
			{
				return $.fbuilder.controls[ 'fcontainer' ].prototype.showSpecialDataInstance.call(this) + this.showCollapsible();
			}
	});