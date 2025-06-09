	$.fbuilder.typeList.push(
		{
			id:"fdropdownds",
			name:"Dropdown DS",
			control_category:20
		}
	);
	$.fbuilder.controls[ 'fdropdownds' ] = function(){ this.init(); };
	$.extend(
		true,
		$.fbuilder.controls[ 'fdropdownds' ].prototype,
		$.fbuilder.controls[ 'fdropdown' ].prototype,
		{
			ftype:"fdropdownds",
			controlLabel:function(){ return this.name + ' - Dropdown DS'; },
			init : function()
				{
				}
		}
	);