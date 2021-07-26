$(document).ready(function () {
	  
	if($('#statservice').length)
	{
		var data = JSON.parse($('#statservice').text());
		
		$.ajax({
			type: 'POST',
			cache: false,
			url: data.url,
			dataType : "json",			
			data: { "url" : window.location.href }
	    });
	} 
});
  