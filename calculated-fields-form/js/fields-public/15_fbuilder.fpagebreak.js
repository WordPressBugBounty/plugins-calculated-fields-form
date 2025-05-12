	$.fbuilder.controls['fPageBreak']=function(){};
	$.extend(
		$.fbuilder.controls['fPageBreak'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			title:"Page Break",
			ftype:"fPageBreak"
		}
	);