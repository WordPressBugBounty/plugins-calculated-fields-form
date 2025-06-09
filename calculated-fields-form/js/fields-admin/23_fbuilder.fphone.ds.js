	$.fbuilder.typeList.push(
		{
			id:"fPhoneds",
			name:"Phone DS",
			control_category:20
		}
	);
	$.fbuilder.controls[ 'fPhoneds' ] = function(){ this.init(); };
	$.extend(
		true,
		$.fbuilder.controls[ 'fPhoneds' ].prototype,
		$.fbuilder.controls[ 'fPhone' ].prototype,
		{
			ftype:"fPhoneds",
			controlLabel:function(){ return this.name + ' - Phone DS'; },
			init : function()
				{
				}
		}
	);