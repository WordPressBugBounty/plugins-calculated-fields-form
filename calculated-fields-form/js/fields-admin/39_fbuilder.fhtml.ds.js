	$.fbuilder.typeList.push(
		{
			id:"fhtmlds",
			name:"HTML Cont. DS",
			control_category:20
		}
	);
	$.fbuilder.controls['fhtmlds'] = function(){ this.init(); };
	$.extend(
		true,
		$.fbuilder.controls['fhtmlds'].prototype,
		$.fbuilder.controls['fhtml'].prototype,
		{
			ftype:"fhtmlds",
            controlLabel:function(){ return this.name + ' - HTML Content DS'; },
			init:function()
				{
				}
	});