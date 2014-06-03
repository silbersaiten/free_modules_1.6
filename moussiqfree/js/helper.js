var helper={addslashes:function(str)
{if(!str===undefined)
{str=str.replace(/\\/g,'\\\\');str=str.replace(/\'/g,'\\\'');str=str.replace(/\"/g,'\\"');str=str.replace(/\0/g,'\\0');}
str=str.replace(/\\/g, '\\\\').replace(/\u0008/g, '\\b').replace(/\t/g, '\\t').replace(/\n/g, '\\n').replace(/\f/g, '\\f').replace(/\r/g, '\\r').replace(/'/g, '\\\'').replace(/"/g, '\\"');
return str;},stripslashes:function(str)
{if(!str===undefined)
{str=str.replace(/\\'/g,'\'');str=str.replace(/\\"/g,'"');str=str.replace(/\\0/g,'\0');str=str.replace(/\\\\/g,'\\');}
return str;},trim:function(str)
{//if(!str===undefined)
//{str=str.replace(/^\s\s*/,'').replace(/\s\s*$/,'');}
return str;},getjSonObject:function(data)
{var returnString='{';for(field in data)
{returnString+='"'+field+'": "'+data[field]+'", ';}
returnString=returnString.substr(0,parseInt(returnString.length-2));return returnString+'}, ';},createSetupFieldWrapper:function(currentField)
{currentField.append($(document.createElement('p')));var fields=currentField.find('p');return fields.eq(fields.length-1);},createLabel:function(label)
{return $(document.createElement('label')).html(label);},createTextField:function(className)
{return $(document.createElement('input')).attr({type:'text',size:22,name:className}).addClass(className);},createCheckbox:function(name)
{return $(document.createElement('input')).attr({type:'checkbox',name:name}).addClass(name);},createSetupInputField:function(currentField,label,className,type)
{var contents=this.createSetupFieldWrapper(currentField);contents.append(this.createLabel(label));switch(type)
{case'input':default:contents.append(this.createTextField(className));break;case'checkbox':contents.append(this.createCheckbox(className));break;}},createSetupInputFields:function(currentField,labels,type)
{for(labelName in labels)
helper.createSetupInputField(currentField,labels[labelName],labelName,type);},createSetupCheckboxFields:function(currentField,label,className,values)
{},setInputVal:function(element,inputClasses)
{for(inputName in inputClasses)
{var input=element.find('input.'+inputName);if(input.length>0)
{switch(input.attr('type')){case'text':default:input.val(helper.stripslashes(inputClasses[inputName]));break;case'checkbox':if(parseInt(inputClasses[inputName])==1)
input.attr('checked',true);else
input.attr('checked',false);break;}}}},getInputVal:function(element,inputClasses)
{var result=new Object(),value;for(var i=0;i<inputClasses.length;i++)
{var input=element.find('input.'+inputClasses[i]);if(input.length>0)
{switch(input.attr('type'))
{case'text':default:value=input.val();if(value===undefined)
value='';value=this.addslashes(this.trim(value));break;case'checkbox':value=$(input).is(':checked')?'1':'0';break;}}
else
value='';result[inputClasses[i]]=value;}
return result;}};
