<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\TipitakaStatistics;
use App\Repository\TipitakaStatisticsRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use App\Repository\TipitakaParagraphsRepository;
use App\Repository\TipitakaSentencesRepository;
use App\Security\Roles;

class StatisticsController extends AbstractController
{
    public function logRequest(Request $request,TipitakaStatisticsRepository $statisticsRepository,RouterInterface $router,
        TipitakaParagraphsRepository $paragraphsRepository,TipitakaSentencesRepository $sentencesRepository)
    {
        $url= $request->get("url");
                        
        if(filter_var($url, FILTER_VALIDATE_URL))
        {
            $stat=new TipitakaStatistics();
            
            $stat->setAccessdate((new \DateTime())->setTime(0,0));
                        
            $path=parse_url($url,PHP_URL_PATH);
            $stat->setPath($url);
            
            $path=str_replace("/tipitaka","",$path);
            
            $params=$router->match($path);
                        
            if(isset($params["_route"]))
            {
                switch($params["_route"])
                {
                    case "view_node":
                    case "table_view":
                    case "translation_view":
                        $stat->setNodeid($params["id"]);
                        $stat->setPath(NULL);
                        break;
                    case "full_toc_node":
                    case "translation_toc_node":                        
                        $stat->setNodeid($params["id"]);
                        break;
                    case "view_paragraph":
                        $paragraph=$paragraphsRepository->getParagraph($params["id"]);
                        $stat->setNodeid($paragraph["nodeid"]);
                        break;
                    case "comments":
                        $sentence=$sentencesRepository->getNodeIdBySentenceId($params["sentenceid"]);
                        $stat->setNodeid($sentence["nodeid"]);
                        break;
                }                                
            }            
            
            $statisticsRepository->logRequest($stat);
        }
        
        return new Response("OK");
    }
    
    public function viewStatsAgg(TipitakaStatisticsRepository $statisticsRepository)
    {
        return $this->render('statistics_agg.html.twig',
            ['stats'=>$statisticsRepository->listStatsAgg(),'adminRole'=>Roles::Admin,'editorRole'=>Roles::Editor,
                'authorRole'=>Roles::Author
            ]);
    }
}

