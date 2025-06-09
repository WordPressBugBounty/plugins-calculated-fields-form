	$.fbuilder.typeList.push(
		{
			id:"fdatatableds",
			name:"DataTable DS",
			control_category:20
		}
	);
	$.fbuilder.controls['fdatatableds'] = function(){ this.init(); };
	$.extend(
		true,
		$.fbuilder.controls['fdatatableds'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			ftype:"fdatatableds",
            title:"Data table",
			init:function()
				{
				}
	});