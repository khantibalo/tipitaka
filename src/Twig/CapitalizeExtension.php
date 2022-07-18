<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

//the reason for keeping the pali text lowercase is to allow case-insensitive search while honoring retroflex consonants 
class CapitalizeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('capitalize', [$this, 'capitalize']),
        ];
    }
    
    public function capitalize($text,$caps="")
    {
        if($caps!="")
        {
            $ar_caps=explode(",",$caps);
            foreach($ar_caps as $cap)
            {
                $upper=mb_strtoupper(mb_substr($text,(int)$cap,1));
                $text=$this->mb_replace_char($text,(int)$cap,$upper);
            }
        }
        
        return $text;
    }
    
    private function mb_replace_char($text,$pos,$char)
    {
        return mb_substr($text, 0, $pos).$char.mb_substr($text, $pos+1);
    }
}

