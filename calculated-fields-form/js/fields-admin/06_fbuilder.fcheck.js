	$.fbuilder.typeList.push(
		{
			id:"fcheck",
			name:"Checkboxes",
			control_category:1
		}
	);
	$.fbuilder.controls[ 'fcheck' ] = function(){};
	$.extend(
		true,
		$.fbuilder.controls[ 'fcheck' ].prototype,
		$.fbuilder.controls[ 'ffields' ].prototype,
		{
			title:"Check All That Apply",
			ftype:"fcheck",
			layout:"one_column",
			required:false,
			exclude:false,
			accept_html:false,
            readonly:false,
			toSubmit:'text',

			choices: [],
			choicesVal:[],
			choiceSelected:[],

			merge:1,
			onoff:0,
			quantity:0,
			minQuantity:-1,
			maxQuantity:-1,
			quantity_when_ticked:0,

			max:-1,
			min:-1,
			maxError:"Check no more than {0} boxes",
			minError:"Check at least {0} boxes",
			showDep:false,
			initAdv:function(){
					delete this.advanced.css.input;
					if ( ! ( 'choice' in this.advanced.css ) ) this.advanced.css.choice = {label: 'Choice text',rules:{}};
				},
			init:function()
				{
					this.choices = new Array("First Choice","Second Choice","Third Choice");
					this.choicesVal = new Array("First Choice","Second Choice","Third Choice");
					this.choiceSelected = new Array(false,false,false);
					this.choicesDep = new Array(new Array(),new Array(),new Array());
				},
			showRangeIntance:function(){ return ''; },
			display:function( css_class )
				{
					css_class = css_class || '';
					this.choicesVal = ((typeof(this.choicesVal) != "undefined" && this.choicesVal !== null && this.choicesVal.length)?this.choicesVal:this.choices.slice(0));
					var str = "";
					for (var i=0;i<this.choices.length;i++)
					{
						str += '<div class="'+this.layout+'"><label><input disabled class="field disabled" type="checkbox" '+((this.choiceSelected[i])?"checked":"")+'/> '+cff_sanitize(this.choices[i])+'</label></div>';
					}
					return '<div class="fields '+this.name+' '+this.ftype+' '+css_class+'" id="field'+this.form_identifier+'-'+this.index+'" title="'+this.controlLabel('Checkboxes')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer()+'<label>'+cff_sanitize(this.title, true)+''+((this.required)?"*":"")+'</label><div class="dfield">'+this.showColumnIcon()+str+'<span class="uh">'+cff_sanitize(this.userhelp, true)+'</span></div><div class="clearer"></div></div>';
				},
			editItemEvents:function()
				{
					$(".choice_text").on("change keyup", {obj: this}, function(e)
						{
							if (e.data.obj.choices[$(this).attr("i")] == e.data.obj.choicesVal[$(this).attr("i")])
							{
								$("#"+$(this).attr("id")+"V"+$(this).attr("i")).val($(this).val());
								e.data.obj.choicesVal[$(this).attr("i")]= $(this).val();
							}
							e.data.obj.choices[$(this).attr("i")]= $(this).val();
							$.fbuilder.reloadItems({'field':e.data.obj});
						});
					$(".choice_value").on("change keyup", {obj: this}, function(e)
						{
							e.data.obj.choicesVal[$(this).attr("i")]= $(this).val();
							$.fbuilder.reloadItems({'field':e.data.obj});
						});
					$(".choice_check").on("click", {obj: this}, function(e)
						{
							if ($(this).is(':checked'))
							{
								e.data.obj.choiceSelected[$(this).attr("i")] = true;
							}
							else
							{
								e.data.obj.choiceSelected[$(this).attr("i")] = false;
							}
							$.fbuilder.reloadItems({'field':e.data.obj});
						});
					$("#sLayout").on("change", {obj: this}, function(e)
						{
							e.data.obj.layout = $(this).val();
							$.fbuilder.reloadItems({'field':e.data.obj});
						});
					$(".choice_up").on("click", {obj: this}, function(e)
						{
							var i = $(this).attr("i")*1;
							if (i!=0)
							{
								e.data.obj.choices.splice(i-1, 0, e.data.obj.choices.splice(i, 1)[0]);
								e.data.obj.choicesVal.splice(i-1, 0, e.data.obj.choicesVal.splice(i, 1)[0]);
								e.data.obj.choicesDep.splice(i-1, 0, e.data.obj.choicesDep.splice(i, 1)[0]);
							}
							$.fbuilder.editItem(e.data.obj.index);
							$.fbuilder.reloadItems({'field':e.data.obj});
						});
					$(".choice_down").on("click", {obj: this}, function(e)
						{
							var i = $(this).attr("i")*1;
							var n = $(this).attr("n")*1;
							if (i!=n)
							{
								e.data.obj.choices.splice(i, 0, e.data.obj.choices.splice(i+1, 1)[0]);
								e.data.obj.choicesVal.splice(i, 0, e.data.obj.choicesVal.splice(i+1, 1)[0]);
								e.data.obj.choicesDep.splice(i, 0, e.data.obj.choicesDep.splice(i+1, 1)[0]);
							}
							$.fbuilder.editItem(e.data.obj.index);
							$.fbuilder.reloadItems({'field':e.data.obj});
						});
					$(".choice_removeDep").on("click", {obj: this}, function(e)
						{
							if (e.data.obj.choicesDep[$(this).attr("i")].length == 1)
							{
								e.data.obj.choicesDep[$(this).attr("i")]=[];
							}
							else
							{
								e.data.obj.choicesDep[$(this).attr("i")].splice($(this).attr("j"),1);
							}
							$.fbuilder.editItem(e.data.obj.index);
							$.fbuilder.reloadItems({'field':e.data.obj});
						});
					$(".choice_addDep").on("click", {obj: this}, function(e)
						{
							e.data.obj.choicesDep[$(this).attr("i")].splice($(this).attr("j")*1+1,0,"");
							$.fbuilder.editItem(e.data.obj.index);
							$.fbuilder.reloadItems({'field':e.data.obj});
						});
					$(".choice_remove").on("click", {obj: this}, function(e)
						{
							if (e.data.obj.choices.length==1)
							{
								e.data.obj.choices[0]="";
								e.data.obj.choicesVal[0]="";
								e.data.obj.choicesDep[0]=new Array();
							}
							else
							{
								e.data.obj.choices.splice($(this).attr("i"),1);
								e.data.obj.choicesVal.splice($(this).attr("i"),1);
								e.data.obj.choicesDep.splice($(this).attr("i"),1);
							}
							if (e.data.obj.ftype=="fcheck" && 0 < e.data.obj.choiceSelected.length)
							{
								if (e.data.obj.choiceSelected.length==1)
								{
									e.data.obj.choiceSelected[0]="";
								}
								else
								{
									e.data.obj.choiceSelected.splice($(this).attr("i"),1);
								}
							}
							$.fbuilder.editItem(e.data.obj.index);
							$.fbuilder.reloadItems({'field':e.data.obj});
						});
					$(".choice_add").on("click", {obj: this}, function(e)
						{
							e.data.obj.choices.splice($(this).attr("i")*1+1,0,"");
							e.data.obj.choicesVal.splice($(this).attr("i")*1+1,0,"");
							e.data.obj.choicesDep.splice($(this).attr("i")*1+1,0,new Array());
							if (e.data.obj.ftype=="fcheck")
							{
								e.data.obj.choiceSelected.splice($(this).attr("i")*1+1,0,false);
							}
							$.fbuilder.editItem(e.data.obj.index);
							$.fbuilder.reloadItems({'field':e.data.obj});
						});
					$(".showHideDependencies").on("click", {obj: this}, function(e)
						{
							if (e.data.obj.showDep)
							{
								$(this).parent().removeClass("show");
								$(this).parent().addClass("hide");
								$(this).html("Show Dependencies");
								e.data.obj.showDep = false;
							}
							else
							{
								$(this).parent().addClass("show");
								$(this).parent().removeClass("hide");
								$(this).html("Hide Dependencies");
								e.data.obj.showDep = true;
							}
							$.fbuilder.editItem(e.data.obj.index);
							return false;
						});
					$('.dependencies').on("change", {obj: this}, function(e)
						{
							e.data.obj.choicesDep[$(this).attr("i")][$(this).attr("j")] = $(this).val();
							$.fbuilder.reloadItems({'field':e.data.obj});
						});
					var me 		= this,
						items 	= me.fBuild.getItems(),
						evt 	= [
							{s:'[name="sToSubmit"]', e:"click", l:"toSubmit"},
							{s:'[name="sMerge"]', e:"change", l:"merge", f: function(el){return (el.is(':checked')) ? 1 : 0;}},
							{s:'[name="sOnOff"]', e:"change", l:"onoff", f: function(el){return (el.is(':checked')) ? 1 : 0;}},
							{s:'[name="sQuantity"]', e:"change", l:"quantity", f: function(el){return (el.is(':checked')) ? 1 : 0;}},
							{s:'[name="sQuantityWhenTicked"]', e:"change", l:"quantity_when_ticked", f: function(el){return (el.is(':checked')) ? 1 : 0;}},
							{s:'[name="sMaxQuantity"]', e:"change keyup", l:"maxQuantity", f: function(el){
								var v = el.val();
								return ($.fbuilder.isNumeric(v)) ? Math.max(1,Math.round(v)) : -1;
							}},
							{s:'[name="sMinQuantity"]', e:"change keyup", l:"minQuantity", f: function(el){
								var v = el.val();
								return ($.fbuilder.isNumeric(v)) ? Math.max(1,Math.round(v)) : -1;
							}},
							{s:'[name="sMax"]', e:"change keyup", l:"max", f: function(el){
								var v = el.val();
								return ($.fbuilder.isNumeric(v)) ? Math.round(v) : -1;
							}},
							{s:'[name="sMin"]', e:"change keyup", l:"min", f: function(el){
								var v = el.val();
								return ($.fbuilder.isNumeric(v)) ? Math.round(v) : -1;
							}},
							{s:'[name="sMaxError"]', e:"change keyup", l:"maxError"},
							{s:'[name="sMinError"]', e:"change keyup", l:"minError"}
						];
					$('.dependencies').each(function()
						{
							var str = '<option value="" '+(("" == $(this).attr("dvalue"))?"selected":"")+'></option>', t = '';
							for (var i=0;i<items.length;i++)
                            {
								if (items[i].name != me.name && items[i].ftype != 'fPageBreak' && items[i].ftype != 'frecordsetds')
                                {
									t = ( 'title' in items[i] ) ? String( items[i].title ).trim() : '';
									t = ( '' == t && 'shortlabel' in items[i] ) ? String( items[i].shortlabel ).trim() : t;

									str += '<option value="'+items[i].name+'" '+((items[i].name == $(this).attr("dvalue"))?"selected":"")+'>'+(items[i].name)+(('' != t) ? ' (' + cff_esc_attr(t) + ')' : '')  +'</option>';
                                }
                            }
							$(this).html(str);
						});

					$.fbuilder.controls[ 'ffields' ].prototype.editItemEvents.call(this, evt);
				},
			mergeValues: function()
				{
					return '<div class="choicesSet"><label><input type="checkbox" name="sMerge" '+((this.merge) ? ' CHECKED ' : '')+'/> Merge ticked up options (sum or concatenation) or their values are returned as an array.</label></div>'+
					'<div class="choicesSet"><label><input type="checkbox" name="sOnOff" '+((this.onoff) ? ' CHECKED ' : '')+'/> Display as on/off switch.</label></div>'+
                    '<div class="choicesSet"><label class="large"><input type="checkbox" name="sQuantity" ' + ((this.quantity) ? ' CHECKED ' : '') + '/> Enable quantity boxes for each choice.</label>' +
                    '<label class="large"><input type="checkbox" name="sQuantityWhenTicked" ' + ((this.quantity_when_ticked) ? ' CHECKED ' : '') + '/> Show quantity box only when the choice is ticked.</label>' +
                    '<div class="column width50"><label class="large" for="sMinQuantity">Minimum quantity allowed</label><input type="number" id="sMinQuantity" name="sMinQuantity" value="' + ($.fbuilder.isNumeric(this.minQuantity) && 1 <= this.minQuantity ? this.minQuantity : '') + '" class="large" min="1" /></div>' +
                    '<div class="column width50"><label class="large" for="sMaxQuantity">Maximum quantity allowed</label><input type="number" id="sMaxQuantity" name="sMaxQuantity" value="' + ($.fbuilder.isNumeric(this.maxQuantity) && 1 <= this.maxQuantity ? this.maxQuantity : '') + '" class="large" min="1" /></div>' +
                    '<div class="clearer"></div></div>';
				},
			attributeToSubmit: function()
				{
					return '<div class="choicesSet"><label>Value to Submit</label><div class="column width50"><label><input type="radio" name="sToSubmit" value="text" '+((this.toSubmit == 'text') ? ' CHECKED ' : '')+'/> Choice Text</label></div><div class="column width50"><label><input type="radio" name="sToSubmit" value="value" '+((this.toSubmit == 'value') ? ' CHECKED ' : '')+'/> Choice Value</label></div><div class="clearer"></div></div>';
				},
			minChoices: function()
				{
					return '<label style="margin-bottom:10px;"><input type="number" name="sMin" value="'+(($.fbuilder.isNumeric(this.min) && 0<=this.min) ? this.min : '')+'" style="max-width:60px;" /> Minimum number of choices to be ticked.</label>'+
					'<input type="text" name="sMinError" class="large" value="'+cff_esc_attr(cff_html_decode(this.minError))+'" placeholder="Min choices error messages" aria-label="Min error message" />';
				},
			maxChoices: function()
				{
					return '<label style="margin-bottom:10px;"><input type="number" name="sMax" value="'+(($.fbuilder.isNumeric(this.max) && 0<=this.max) ? this.max : '')+'" style="max-width:60px;" min="1" /> Maximum number of choices to be ticked.</label>'+
					'<input type="text" name="sMaxError" class="large" value="'+cff_esc_attr(cff_html_decode(this.maxError))+'" placeholder="Max choices error messages" aria-label="Max error message" />';
				},
			showChoiceIntance: function()
				{
					this.choicesVal = ((typeof(this.choicesVal) != "undefined" && this.choicesVal !== null && this.choicesVal.length)?this.choicesVal:this.choices.slice(0));
					var l = this.choices,
						lv = this.choicesVal,
						v = this.choiceSelected,
						str = '', str1, j;
					if (!(typeof(this.choicesDep) != "undefined" && this.choicesDep !== null))
					{
						this.choicesDep = new Array();
						for (var i=0;i<l.length;i++)
						{
							this.choicesDep[i] = new Array();
						}
					}
					var d = this.choicesDep;
					for (var i=0;i<l.length;i++)
					{
						str1 = '';
						str += '<div class="choicesEdit"><input class="choice_check" i="'+i+'" type="checkbox" '+((this.choiceSelected[i])?"checked":"")+' title="Choice selected by default" aria-label="Select choice by default" /><input class="choice_text" i="'+i+'" type="text" name="sChoice'+this.name+'" id="sChoice'+this.name+'" value="'+cff_esc_attr(l[i])+'" aria-label="Choice text" /><input class="choice_value" i="'+i+'" type="text" name="sChoice'+this.name+'V'+i+'" id="sChoice'+this.name+'V'+i+'" value="'+cff_esc_attr(lv[i])+'" aria-label="Choice value" /><div class="choice-ctrls"><a class="choice_down ui-icon ui-icon-arrowthick-1-s" i="'+i+'" n="'+(l.length-1)+'" title="Down"></a><a class="choice_up ui-icon ui-icon-arrowthick-1-n" i="'+i+'" title="Up"></a><a class="choice_add ui-icon ui-icon-circle-plus" i="'+i+'" title="Add another choice."></a><a class="choice_remove ui-icon ui-icon-circle-minus" i="'+i+'" title="Delete this choice."></a></div></div>';
						j = d[i].length;
						if(j)
						{
							while(j--)
							{
								str1 = '<div class="choicesEditDep"><span>If selected show:</span> <select class="dependencies" i="'+i+'" j="'+j+'" dname="'+this.name+'" dvalue="'+d[i][j]+'" aria-label="Dependent field"></select><div class="choice-ctrls"><a class="choice_addDep ui-icon ui-icon-circle-plus" i="'+i+'" j="'+j+'" title="Add another dependency."></a><a class="choice_removeDep ui-icon ui-icon-circle-minus" i="'+i+'" j="'+j+'" title="Delete this dependency."></a></div></div>'+str1;
							}
							str += str1;
						}
						else
						{
							str += '<div class="choicesEditDep"><span>If selected show:</span> <select class="dependencies" i="'+i+'" j="'+j+'" dname="'+this.name+'" dvalue="" aria-label="Dependent field"></select><div class="choice-ctrls"><a class="choice_addDep ui-icon ui-icon-circle-plus" i="'+i+'" j="'+j+'" title="Add another dependency."></a><a class="choice_removeDep ui-icon ui-icon-circle-minus" i="'+i+'" j="'+j+'" title="Delete this dependency."></a></div></div>';
						}
					}
					return '<div class="choicesSet '+((this.showDep)?"show":"hide")+'"><label>Choices <a class="helpfbuilder dep video" href="https://www.youtube.com/embed/s4FM59LC-H4?list=PLY-AOoHciOKgZQsqWfkQlHJ21sm3qPF9X" target="_blank">&#9654; help?</a> <a href="" class="showHideDependencies">'+((this.showDep)?"Hide":"Show")+' Dependencies</a></label><div><div class="t">Text</div><div class="t">Value</div><div class="clearer"></div></div>'+str+
                    '<div style="text-align:right;"><input type="button" class="button cff-ai-assistant" value="AI List Generation" onclick="if(\'cff_ai_assistant_open\' in window) cff_ai_assistant_open(\'list\');" style="float:none;"></div>' +
                    this.mergeValues()+this.attributeToSubmit()+'<hr style="margin-top:20px;margin-bottom:20px;" />'+this.minChoices()+this.maxChoices()+'</div>';
				}
	});