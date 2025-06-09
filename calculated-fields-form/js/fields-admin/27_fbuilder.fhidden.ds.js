	$.fbuilder.typeList.push(
		{
			id:"fhiddends",
			name:"Hidden DS",
			control_category:20
		}
	);
	$.fbuilder.controls[ 'fhiddends' ]=function(){ this.init(); };
	$.extend(
		true,
		$.fbuilder.controls[ 'fhiddends' ].prototype,
		$.fbuilder.controls[ 'fhidden' ].prototype,
		{
			ftype:"fhiddends",
			controlLabel:function(){ return this.name + ' - Hidden DS'; },
			init : function()
				{
				}
		}
	);