<?php
namespace App\Controller;

use App\Repository\TipitakaCommentsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\NativeRepository;

class DefaultController extends AbstractController
{
    public function default(NativeRepository $nativeRepository,TipitakaCommentsRepository $commentsRespository,Request $request)
    {       
        $lastupd=$nativeRepository->listByLastUpdTranslation(40,$request->getLocale());
        $comments=$commentsRespository->listLatest(10,$request->getLocale());
        
        return $this->render('index.html.twig',['lastupd'=>$lastupd,'comments'=>$comments]);
    }
    
    public function setLocale($locale)
    {                
        $response=$this->redirectToRoute('index');
        
        $response->headers->setCookie(new Cookie('locale',$locale,time() + (3600 * 24*365)));
        
        return $response;
    }    
}