<?php
namespace App\Controller;

use App\Repository\TipitakaCommentsRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\NativeRepository;
use App\Repository\TipitakaStatisticsRepository;

class DefaultController extends AbstractController
{
    public function default(NativeRepository $nativeRepository,TipitakaCommentsRepository $commentsRespository,Request $request,
        CacheItemPoolInterface $pool, TipitakaStatisticsRepository $statisticsRepository)
    {                    
        $lastupdItem=$nativeRepository->listByLastUpdTranslation(40,$request->getLocale());
        
        $comments=$commentsRespository->listLatest(10,$request->getLocale());
                                
        $viewcountItem = $pool->getItem('viewcount');
        if(!$viewcountItem->isHit())
        {
            $viewcountValue=$statisticsRepository->getViewsTotal();
            $viewcountItem->expiresAfter(600);
            $viewcountItem->set($viewcountValue);
            $pool->save($viewcountItem);
        }
                
        return $this->render('index.html.twig',['lastupd'=>$lastupdItem,'comments'=>$comments,
            'viewCount'=>$viewcountItem->get()]);
    }
    
    public function setLocale($locale,Request $request)
    {                
        //if referer is specified, redirect to referer
        if($request->headers->get('referer'))
        {
            $response=$this->redirect($request->headers->get('referer'));
        }
        else 
        {
            $response=$this->redirectToRoute('index');
        }
        
        $response->headers->setCookie(new Cookie('locale',$locale,time() + (3600 * 24*365)));
        
        return $response;
    }    
    
    public function setMobile(Request $request)
    {
        if($request->headers->get('referer'))
        {
            $response=$this->redirect($request->headers->get('referer'));
        }
        else
        {
            $response=$this->redirectToRoute('index');
        }        
        
        $mobile=$request->cookies->get("mobile")=="1" ? "0" : "1";
        
        $response->headers->setCookie(new Cookie('mobile',$mobile,time() + (3600 * 24*365)));
        
        return $response;
    }
}