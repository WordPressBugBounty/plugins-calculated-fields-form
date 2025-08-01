	$.fbuilder.typeList.push(
		{
			id:"fradio",
			name:"Radio Buttons",
			control_category:1
		}
	);
	$.fbuilder.controls[ 'fradio' ] = function(){};
	$.extend(
		true,
		$.fbuilder.controls[ 'fradio' ].prototype,
		$.fbuilder.controls[ 'ffields' ].prototype,
		{
			title:"Select a Choice",
			ftype:"fradio",
			layout:"one_column",
			required:false,
			exclude:false,
			accept_html:false,
            readonly:false,
			toSubmit:'text',
			choiceSelected:"",
			showDep:false,
			untickAccepted:true,
            onoff:0,
			nextPage:false,
			initAdv:function(){
					delete this.advanced.css.input;
					if ( ! ( 'choice' in this.advanced.css ) ) this.advanced.css.choice = {label: 'Choice text',rules:{}};
				},
			init:function()
				{
					this.choices = new Array("First Choice","Second Choice","Third Choice");
					this.choicesVal = new Array("First Choice","Second Choice","Third Choice");
					this.choicesDep = new Array(new Array(),new Array(),new Array());
				},
			display:function( css_class )
				{
					css_class = css_class || '';
					this.choicesVal = ((typeof(this.choicesVal) != "undefined" && this.choicesVal !== null)?this.choicesVal:this.choices.slice(0));
					var str = "";
					for (var i=0;i<this.choices.length;i++)
					{
						str += '<div class="'+this.layout+'"><label><input disabled class="field disabled" type="radio" i="'+i+'"  '+(( this.choices[i]+' - '+this.choicesVal[i]==this.choiceSelected)?"checked":"")+'/> '+cff_sanitize(this.choices[i])+'</label></div>';
					}
					return '<div class="fields '+this.name+' '+this.ftype+' '+css_class+'" id="field'+this.form_identifier+'-'+this.index+'" title="'+this.controlLabel('Radio Buttons')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer()+'<label>'+cff_sanitize(this.title, true)+''+((this.required)?"*":"")+'</label><div class="dfield">'+this.showColumnIcon()+str+'<span class="uh">'+cff_sanitize(this.userhelp, true)+'</span></div><div class="clearer"></div></div>';
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
					$(".choice_radio").on("mousedown", function(){$(this).data('previous-status', $(this).is(':checked'));});
					$(".choice_radio").on("click", {obj: this}, function(e)
						{
							var el = $(this),
								i = el.attr("i");

							el.prop('checked', !el.data('previous-status'));
							e.data.obj.choiceSelected = (el.is(':checked')) ? e.data.obj.choices[i] + ' - ' + e.data.obj.choicesVal[i] : "";
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
							var i = $(this).attr("i");

							if( e.data.obj.choices[ i ] + ' - ' + e.data.obj.choicesVal[ i ] == e.data.obj.choiceSelected )
							{
								e.data.obj.choiceSelected = "";
							}

							if (e.data.obj.choices.length==1)
							{
								e.data.obj.choices[0]="";
								e.data.obj.choicesVal[0]="";
								e.data.obj.choicesDep[0]=new Array();
							}
							else
							{
								e.data.obj.choices.splice(i,1);
								e.data.obj.choicesVal.splice(i,1);
								e.data.obj.choicesDep.splice(i,1);
							}
							$.fbuilder.editItem(e.data.obj.index);
							$.fbuilder.reloadItems({'field':e.data.obj});
						});
					$(".choice_add").on("click", {obj: this}, function(e)
						{
							var i = $(this).attr("i")*1+1;

							e.data.obj.choices.splice(i,0,"");
							e.data.obj.choicesVal.splice(i,0,"");
							e.data.obj.choicesDep.splice(i,0,new Array());
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
					var me		= this,
						items 	= me.fBuild.getItems(),
						evt 	= [{s:'[name="sToSubmit"]', e:"click", l:"toSubmit"},
								{s:'[name="sOnOff"]', e:"change", l:"onoff", f: function(el){return (el.is(':checked')) ? 1 : 0;}},
								{s:'[name="sNextPage"]', e:"change", l:"nextPage", f: function(el){return (el.is(':checked')) ? 1 : 0;}},
                                {s:'[name="sUntickAccepted"]',e:"click", l:"untickAccepted",f:function(el){return el.is(':checked');}}];
					$('.dependencies').each(function()
						{
							var str = '<option value="" '+(("" == $(this).attr("dvalue"))?"selected":"")+'></option>', t = '';
							for (var i=0;i<items.length;i++)
							{
								if (items[i].name != me.name && items[i].ftype != 'fPageBreak' && items[i].ftype != 'frecordsetds')
								{
									t = ( 'title' in items[i] ) ? String( items[i].title ).trim() : '';
									t = ( '' == t && 'shortlabel' in items[i] ) ? String( items[i].shortlabel ).trim() : t;

									str += '<option value="'+items[i].name+'" '+((items[i].name == $(this).attr("dvalue"))?"selected":"")+'>'+(items[i].name)+ (('' != t) ? ' (' + cff_esc_attr(t) + ')' : '') + ' </option>';
								}
							}
							$(this).html(str);
						});
					$.fbuilder.controls[ 'ffields' ].prototype.editItemEvents.call(this,evt);
				},
			attributeToSubmit: function()
				{
					return '<div class="choicesSet"><label><input type="checkbox" name="sOnOff" '+((this.onoff) ? ' CHECKED ' : '')+'/> Display as on/off switch.</label></div>'+
                    '<div class="choicesSet"><label>Value to Submit</label><div class="column width50"><label><input type="radio" name="sToSubmit" value="text" '+((this.toSubmit == 'text') ? ' CHECKED ' : '')+'/> Choice Text</label></div><div class="column width50"><label><input type="radio" name="sToSubmit" value="value" '+((this.toSubmit == 'value') ? ' CHECKED ' : '')+'/> Choice Value</label></div><div class="clearer"></div></div>';
				},
			allowUntick: function()
				{
					return '<div><label><input type="checkbox" name="sUntickAccepted" '+((this.untickAccepted) ? ' CHECKED ' : '')+'/> Allow Untick Choices</label></div>';
				},
			showChoiceIntance: function()
				{
					this.choicesVal = ((typeof(this.choicesVal) != "undefined" && this.choicesVal !== null)?this.choicesVal:this.choices.slice(0));
					var l = this.choices,
						lv = this.choicesVal,
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
						str += '<div class="choicesEdit"><input class="choice_radio" i="'+i+'" type="radio" '+((this.choiceSelected==l[i]+' - '+lv[i])?"checked":"")+' name="choice_radio" title="Choice selected by default" aria-label="Select choice by default" /><input class="choice_text" i="'+i+'" type="text" name="sChoice'+this.name+'" id="sChoice'+this.name+'" value="'+cff_esc_attr(l[i])+'" aria-label="Choice text" /><input class="choice_value" i="'+i+'" type="text" name="sChoice'+this.name+'V'+i+'" id="sChoice'+this.name+'V'+i+'" value="'+cff_esc_attr(lv[i])+'" aria-label="Choice value" /><div class="choice-ctrls"><a class="choice_down ui-icon ui-icon-arrowthick-1-s" i="'+i+'" n="'+(l.length-1)+'" title="Down"></a><a class="choice_up ui-icon ui-icon-arrowthick-1-n" i="'+i+'" title="Up"></a><a class="choice_add ui-icon ui-icon-circle-plus" i="'+i+'" title="Add another choice."></a><a class="choice_remove ui-icon ui-icon-circle-minus" i="'+i+'" title="Delete this choice."></a></div></div>';
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
					return '<div class="choicesSet '+((this.showDep)?"show":"hide")+'"><label>Choices <a class="helpfbuilder dep" text="Dependencies are used to show/hide other fields depending of the option selected in this field.">help?</a> <a href="" class="showHideDependencies">'+((this.showDep)?"Hide":"Show")+' Dependencies</a></label><div><div class="t">Text</div><div class="t">Value</div><div class="clearer"></div></div>'+str+this.attributeToSubmit()+this.allowUntick()+'</div>';
				}
	});