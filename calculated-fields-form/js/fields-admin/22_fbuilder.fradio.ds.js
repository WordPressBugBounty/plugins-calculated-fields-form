	$.fbuilder.typeList.push(
		{
			id:"fradiods",
			name:"Radio Btns DS",
			control_category:20
		}
	);
	$.fbuilder.controls[ 'fradiods' ] = function(){ this.init(); };
	$.extend(
		true,
		$.fbuilder.controls[ 'fradiods' ].prototype,
		$.fbuilder.controls[ 'fradio' ].prototype,
		{
			ftype:"fradiods",
			controlLabel:function(){ return this.name + ' - Radio Buttons DS'; },
			init:function()
				{
				}
		}
	);