	$.fbuilder.typeList.push(
		{
			id:"fcurrencyds",
			name:"Currency DS",
			control_category:20
		}
	);
	$.fbuilder.controls['fcurrencyds']=function(){  this.init();  };
	$.extend(
		true,
		$.fbuilder.controls['fcurrencyds'].prototype,
		$.fbuilder.controls['fcurrency'].prototype,
		{
			ftype:"fcurrencyds",
			controlLabel:function(){ return this.name + ' - Currency DS'; },
			init : function()
				{
				}
		}
	);