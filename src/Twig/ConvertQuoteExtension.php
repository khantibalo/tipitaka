<?php
namespace App\Twig;

use App\Repository\QuoteRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ConvertQuoteExtension extends AbstractExtension
{
    protected $quoteRepository;
    
    public function __construct(QuoteRepository $quoteRepository)
    {
        $this->quoteRepository = $quoteRepository;
    }
    
    
    public function getFilters(): array
    {
        return [
            new TwigFilter('convertquote', [$this, 'convertquote']),
        ];
    }
    
    public function convertquote($text) {
        $text=preg_replace("|<script src=\"https://tipitaka.theravada.su/quote.js\"[^<]+</script>|", "", $text);
        $matches=array();
        
        //quote translation
        preg_match_all("|<script type=\"text/javascript\">getSentenceTranslation\(\"SentenceTransl(\d+)\",\"([^\"]+)|",$text,$matches,PREG_SET_ORDER);
        
        foreach($matches as $match)
        {
            $replace=$this->getSentenceTranslation($match[2]);
            $text=preg_replace("|(<blockquote class=\"SentenceTransl".$match[1]."\">)[^<]*|", '${1}'.$replace, $text);           
        }
        
        $text=preg_replace("|<script type=\"text/javascript\">getSentenceTranslation[^<]+</script>|", "", $text);
        
        //quote pali
        preg_match_all("|<script type=\"text/javascript\">getPali\(\"TipParagraphs(\d+)\",\"([^\"]+)|",$text,$matches,PREG_SET_ORDER);
        
        foreach($matches as $match)
        {
            $replace=$this->getPali($match[2]);
            $text=preg_replace("|(<blockquote class=\"TipParagraphs".$match[1]."\">)[^<]*|", '${1}'.$replace, $text);            
        }
        
        $text=preg_replace("|<script type=\"text/javascript\">getPali[^<]+</script>|", "", $text);
        
        return $text;
    }
    
    #FIXME: this code is duplicate from QuoteRepository. seems like it should be converted into a service and used here
    public function getPali($paragraphids)
    {
        $idlist=$this->parseNumericList($paragraphids);
        
        if($idlist)
        {
            $ce=new CapitalizeExtension();
            $ar_text=$this->quoteRepository->listParagraphs($idlist);
            $ar_caps=array();
            foreach($ar_text as $item)
            {
                $ar_caps[]["Text"]=$ce->capitalize($item["Text"], $item["caps"]);
            }
            
            $joined=$this->joinText($ar_caps);
        }
        
        return $joined;
    }
    
    public function getSentenceTranslation($translationids)
    {
        $idlist=$this->parseNumericList($translationids);
        
        if($idlist)
        {
            $ar_text=$this->quoteRepository->listSentenceTranslations($idlist);
            $joined=$this->joinText($ar_text);
        }
        
        return $joined;
    }
    
    private function parseNumericList($list)
    {
        $numbers=explode(',',$list);
        
        foreach($numbers as $number)
        {
            if(!is_numeric($number))
            {
                $list=null;
                break;
            }
        }
        
        return $numbers;
    }
    
    private function joinText($qr)
    {
        $paragraphs=array();
        foreach($qr as $item)
        {
            $paragraphs[]=$item["Text"];
        }
        
        return implode("<br>",$paragraphs);
    }
}

