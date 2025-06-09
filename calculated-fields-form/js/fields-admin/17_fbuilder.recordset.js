	$.fbuilder.typeList.push(
		{
			id:"frecordsetds",
			name:"RecordSet DS",
			control_category:20
		}
	);
	$.fbuilder.controls[ 'frecordsetds' ] = function(){ this.init(); };
	$.extend(
		true,
		$.fbuilder.controls[ 'frecordsetds' ].prototype,
		$.fbuilder.controls[ 'ffields' ].prototype,
		{
			ftype:"frecordsetds",
			_developerNotes:'',
			init : function()
				{
				}
		}
	);