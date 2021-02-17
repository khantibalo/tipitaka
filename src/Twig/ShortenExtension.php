<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ShortenExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('shorten', [$this, 'shorten']),
        ];
    }
    
    public function shorten($text) {
        $short = $text;
        
        if (mb_strlen ( $short ) > 0) {
            $short = str_replace ( '<br/>', ' ', $text );
            $short = str_replace ( '<br>', ' ', $short );
            
            $short=$this->strip_tags_content($short);
            
            if (mb_strlen ( $short ) > 150)
                $short = mb_substr ( $short, 0, 150 ) . '...';
        }
        
        return $short;
    }
    
    private function strip_tags_content($text, $tags = '', $invert = FALSE) {
        
        preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
        $tags = array_unique($tags[1]);
        
        if(is_array($tags) AND count($tags) > 0) {
            if($invert == FALSE) {
                return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
            }
            else {
                return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text);
            }
        }
        elseif($invert == FALSE) {
            return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
        }
        return $text;
    } 
    
}

