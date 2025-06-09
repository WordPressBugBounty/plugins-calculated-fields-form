	$.fbuilder.typeList.push(
		{
			id:"ftextareads",
			name:"Text Area DS",
			control_category:20
		}
	);
	$.fbuilder.controls[ 'ftextareads' ] = function(){ this.init(); };
	$.extend(
		true,
		$.fbuilder.controls[ 'ftextareads' ].prototype,
		$.fbuilder.controls[ 'ftextarea' ].prototype,
		{
			ftype:"ftextareads",
			controlLabel:function(){ return this.name + ' - Text Area DS'; },
			init : function()
				{
				}
		}
	);