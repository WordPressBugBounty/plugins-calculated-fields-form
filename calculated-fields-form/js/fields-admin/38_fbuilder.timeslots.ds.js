	$.fbuilder.typeList.push(
		{
			id:"ftimeslotsds",
			name:"Date/Timeslots DS",
			control_category:20
		}
	);
	$.fbuilder.controls['ftimeslotsds'] = function(){ this.init(); };
	$.extend(
		true,
		$.fbuilder.controls['ftimeslotsds'].prototype,
		$.fbuilder.controls['ftimeslots'].prototype,
		{
			ftype:"ftimeslotsds",
            controlLabel:function(){ return this.name + ' - Date/Timeslots DS'; },
			init : function()
				{
				}
	});