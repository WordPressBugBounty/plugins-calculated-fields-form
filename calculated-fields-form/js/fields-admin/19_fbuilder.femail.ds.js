	$.fbuilder.typeList.push(
		{
			id:"femailds",
			name:"Email DS",
			control_category:20
		}
	);
	$.fbuilder.controls[ 'femailds'] = function(){ this.init(); };
	$.extend(
		true,
		$.fbuilder.controls[ 'femailds' ].prototype,
		$.fbuilder.controls[ 'femail' ].prototype,
		{
			ftype:"femailds",
			controlLabel:function(){ return this.name + ' - Email DS'; },
			init : function()
				{
				}
	});