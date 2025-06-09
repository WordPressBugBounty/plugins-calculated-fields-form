	$.fbuilder.typeList.push(
		{
			id:"ftextds",
			name:"Line Text DS",
			control_category:20
		}
	);
	$.fbuilder.controls[ 'ftextds' ]=function(){  this.init();  };
	$.extend(
		true,
		$.fbuilder.controls[ 'ftextds' ].prototype,
		$.fbuilder.controls[ 'ftext' ].prototype,
		{
			ftype:"ftextds",
			controlLabel:function(){ return this.name + ' - Line Text DS'; },
			init : function()
				{
				}
		}
	);