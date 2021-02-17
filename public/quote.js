
function getPali(quoteclass,paragraphids,baseurl="https://tipitaka.theravada.su/")
{
	var quote=document.getElementsByClassName(quoteclass);
	if(quote)
	{
		callAPI(baseurl+"quote/pali/"+paragraphids,quote);
	}
}

function getSentenceTranslation(quoteclass,sentenceids,baseurl="https://tipitaka.theravada.su/")
{
	var quote=document.getElementsByClassName(quoteclass);
	if(quote)
	{
		callAPI(baseurl+"quote/sentencetranslation/"+sentenceids,quote);
	}	
}

function callAPI(url,quote) {
    var xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.setRequestHeader("Content-type", "application/json");
    xhr.onload = function () 
    {
        if(xhr.status == 200) {
            var response = JSON.parse(xhr.responseText);
            
            for(i=0;i<quote.length;i++)
            	quote[i].innerHTML=response.Text;
        }
    };
    	
    xhr.send();    
}
