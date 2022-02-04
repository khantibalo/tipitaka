<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HighlightExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('highlight', [$this, 'highlight']),
        ];
    }
    
    public function highlight($haystack, $needle,$word=false)
    {
        if(!$word)
        {
            $needle=str_replace(["\"","*"], "", $needle);
        }
        
        // return $haystack if there is no highlight color or strings given, nothing to do.
        if (strlen($haystack) < 1 || strlen($needle) < 1) {
            return $haystack;
        }
        $matches=array();
        
        if($word)
        {
            $needles =preg_split('/[ ,;\.\"\']/u', $needle);
            $needles=array_unique($needles);
            
            foreach($needles as $needleItem)
            {
                if($needleItem)
                {
                    $matches=array();
                    $needleItemQuote=preg_quote($needleItem);
                    preg_match_all("/$needleItemQuote/iu", $haystack, $matches);
                    if (is_array($matches[0]) && count($matches[0]) >= 1) {
                        foreach ($matches[0] as $match) {
                            $haystack = str_replace($match, '<span class="match">'.$match.'</span>', $haystack);
                        }
                    }
                }
            } 
        }
        else 
        {            
            $matches=array();
            preg_match_all("/$needle/iu", $haystack, $matches);//this was originally $needle+
            if (is_array($matches[0]) && count($matches[0]) >= 1) 
            {
                foreach ($matches[0] as $match) {
                    $haystack = str_ireplace($match, '<span class="match">'.$match.'</span>', $haystack);
                }
            }
        }                                       
        
        return $haystack;
    }
    
}

