	$.fbuilder.typeList.push(
		{
			id:"fnumberds",
			name:"Number DS",
			control_category:20
		}
	);
	$.fbuilder.controls['fnumberds']=function(){this.init();};
	$.extend(
		true,
		$.fbuilder.controls['fnumberds'].prototype,
		$.fbuilder.controls['fnumber'].prototype,
		{
			ftype:"fnumberds",
			controlLabel:function(){ return this.name + ' - Number DS'; },
			init : function()
				{
				}
		}
	);