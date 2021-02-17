function palichar_onchange(text_field_id,sel_control)
{
	if(sel_control.value!="")
	{
		var field=document.getElementById(text_field_id);
		field.value+=sel_control.value;
		sel_control.value="";
	}
}

function QTEdit(stid)
{
	$('#et'+stid).hide();
	$('#ec'+stid).hide();
	var taEdit =
		$('<textarea/>', {
        id: 'eta'+stid,
        style: 'width:100%',
        rows: 5,
        text: $('#et'+stid).text()
    });
	var bSave =
		$('<button/>', {
        text: saveText, 
        id: 'ebs'+stid,
        click: function() { QTEditSave(stid); }
    });
	var bCancel =
		$('<button/>', {
        text: cancelText, 
        id: 'ebc'+stid,
        click: function() { QTEditCancel(stid); }
    });

	$('#ec'+stid).after(bCancel).after(bSave).after(taEdit);
}

function QTEditCancel(stid)
{
	$('#et'+stid).show();
	$('#ec'+stid).show();
	$('#eta'+stid).remove();
	$('#ebs'+stid).remove();
	$('#ebc'+stid).remove();
}

function QTEditSave(stid)
{
	var newTranslation=$('#eta'+stid).val();
	
	$.ajax({
	    url: ajaxRoot+"/translation/update",
	    data: JSON.stringify({
	        stid: stid,
	        translation: newTranslation
	    }),
	    contentType: "application/json; charset=utf-8",
	    type: "POST",	
	    dataType : "json",
	}).done(function( json ) {		
		if(newTranslation=="")
		{//if empty text was submitted, editing is not possible
			//create 'translate' and 'quick edit' links
			var ncSpan =
				$('<span/>', {
		        id: 'ncse'+json.sentenceid+"so"+json.sourceid
		    });
			
			var lnkTranslate =
				$('<a/>', {
		        href: translateUrl.replace('sentenceidParam',json.sentenceid).
		        	replace('sourceidParam',json.sourceid),
		        text: translateText
		    });
			
			var lnkQuickEdit=
				$('<a/>', {
			        href: "javascript:QTNew("+json.sentenceid+","+json.sourceid+");",
			        text: quickEditText
			    });
			
			ncSpan.append(lnkTranslate);
			ncSpan.append(" ");
			ncSpan.append(lnkQuickEdit);
			$('#ebc'+stid).after(ncSpan);
			
			$('#et'+stid).remove();
			$('#ec'+stid).remove();
			$('#eta'+stid).remove();
			$('#ebs'+stid).remove();
			$('#ebc'+stid).remove();			
		}
		else
		{
			$('#et'+stid).text(newTranslation);
			$('#ecd'+stid).text(json.dateUpdated);
			QTEditCancel(stid);
		}
	}).fail(function( xhr, status, errorThrown ) {
	    alert( saveError );
	    console.log( "Error: " + errorThrown );
	    console.log( "Status: " + status );
	    console.dir( xhr );
    });	
}

function QTNew(sentenceid,sourceid)
{	
	$('#ncse'+sentenceid+'so'+sourceid).hide();
	
	var ntaEdit =
		$('<textarea/>', {
        id: 'ntase'+sentenceid+'so'+sourceid,
        style: 'width:100%',
        rows: 5,
    });
	var bSave =
		$('<button/>', {
        text: saveText, 
        id: 'nbsse'+sentenceid+'so'+sourceid,
        click: function() { QTNewSave(sentenceid,sourceid); }
    });
	var bCancel =
		$('<button/>', {
        text: cancelText, 
        id: 'nbcse'+sentenceid+'so'+sourceid,
        click: function() { QTNewCancel(sentenceid,sourceid); }
    });

	$('#ncse'+sentenceid+'so'+sourceid).after(bCancel).after(bSave).after(ntaEdit);
}

function QTNewSave(sentenceid,sourceid)
{
	var newTranslation=$('#ntase'+sentenceid+'so'+sourceid).val();
	
	$.ajax({
	    url: ajaxRoot+"/translation/add",
	    data: JSON.stringify({
	    	sentenceid: sentenceid,
	    	sourceid: sourceid,
	        translation: newTranslation
	    }),
	    contentType: "application/json; charset=utf-8",
	    type: "POST",	
	    dataType : "json",
	}).done(function( json ) {		
		var etSpan =
			$('<span/>', {
	        id: 'et'+json.sentencetranslationid
	    });
		
		etSpan.text(newTranslation);
		
		var ecSpan =
			$('<span/>', {
	        id: 'ec'+json.sentencetranslationid
	    });
		
		ecSpan.append($('<br/>'));
		var small=$('<small/>');
		var ecdSpan =
			$('<span/>', {
	        id: 'ecd'+json.sentencetranslationid,
	        text: json.dateUpdated
	    });
		
		small.append(ecdSpan);
		ecSpan.append(small);
		ecSpan.append(" ");
		
		//translation edit
		var lnkEdit =
			$('<a/>', {
	        href: editUrl.replace('stidParam',json.sentencetranslationid),
	        text: editText
	    });
		
		ecSpan.append(lnkEdit);
		ecSpan.append(" ");
		
		//quick edit
		var lnkQuickEdit=
			$('<a/>', {
		        href: "javascript:QTEdit("+json.sentencetranslationid+");",
		        text: quickEditText
		    });
		
		ecSpan.append(lnkQuickEdit);
		ecSpan.append(" ");
		
		//show code
		if(codeUrl!='')
		{
			var lnkCode=
				$('<a/>', {
			        href: codeUrl.replace('stidParam',json.sentencetranslationid),
			        text: codeText
			    });
			
			ecSpan.append(lnkCode);
			ecSpan.append($(' '));
		}
		
		//show align
		if(shiftDownLink!='')
		{
			var lnkShiftDown=
				$('<a/>', {
			        href: shiftDownLink.replace('stidParam',json.sentencetranslationid),
			        text: shiftDownText
			    });
			
			ecSpan.append(" | ");
			ecSpan.append(lnkShiftDown);
			ecSpan.append(" | ");
			
			var lnkShiftUp=
				$('<a/>', {
			        href: shiftUpLink.replace('stidParam',json.sentencetranslationid),
			        text: shiftUpText
			    });
			
			ecSpan.append(lnkShiftUp);
		}		
		
		$('#ncse'+sentenceid+'so'+sourceid).after(ecSpan).after(etSpan);
		
		$('#ncse'+sentenceid+'so'+sourceid).remove();
		$('#ntase'+sentenceid+'so'+sourceid).remove();
		$('#nbsse'+sentenceid+'so'+sourceid).remove();
		$('#nbcse'+sentenceid+'so'+sourceid).remove();
		
	}).fail(function( xhr, status, errorThrown ) {
	    alert( saveError );
	    console.log( "Error: " + errorThrown );
	    console.log( "Status: " + status );
	    console.dir( xhr );
    });	
}

function QTNewCancel(sentenceid,sourceid)
{
	$('#ncse'+sentenceid+'so'+sourceid).show();
	$('#ntase'+sentenceid+'so'+sourceid).remove();
	$('#nbsse'+sentenceid+'so'+sourceid).remove();
	$('#nbcse'+sentenceid+'so'+sourceid).remove();
}