<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HighlightExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('highlight', [$this, 'highlight']),
        ];
    }
    
    public function highlight($haystack, $needle)
    {
        $needle=str_replace(["\"","*"], "", $needle);
        
        // return $haystack if there is no highlight color or strings given, nothing to do.
        if (strlen($haystack) < 1 || strlen($needle) < 1) {
            return $haystack;
        }
        $matches=array();
        preg_match_all("/$needle+/iu", $haystack, $matches);
        if (is_array($matches[0]) && count($matches[0]) >= 1) {
            foreach ($matches[0] as $match) {
                $haystack = str_ireplace($match, '<span class="match">'.$match.'</span>', $haystack);
            }
        }
        return $haystack;
    }
}

