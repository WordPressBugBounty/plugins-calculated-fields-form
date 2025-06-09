	$.fbuilder.typeList.push(
		{
			id:"fcheckds",
			name:"Checkboxes DS",
			control_category:20
		}
	);
	$.fbuilder.controls[ 'fcheckds' ] = function(){ this.init(); };
	$.extend(
		true,
		$.fbuilder.controls[ 'fcheckds' ].prototype,
		$.fbuilder.controls[ 'fcheck' ].prototype,
		{
			ftype:"fcheckds",
			controlLabel:function(){ return this.name + ' - Checkboxes DS'; },
			init:function()
				{
				}
	});