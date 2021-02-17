<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\TipitakaTocRepository;
use App\Repository\TipitakaParagraphsRepository;

class BookmarksController extends AbstractController
{
    
    const session_key= 'bookmarks';
        
    public function addNode($id,Request $request,SessionInterface $session)
    {                
        return $this->addItem($id,'N',$request,$session);
    }
    
    public function addParagraph($id,Request $request,SessionInterface $session)
    {        
        return $this->addItem($id,'P',$request,$session);
    }
    
    public function removeNode($id,Request $request,SessionInterface $session)
    {
        return $this->removeItem($id,'N',$request,$session);
    }
    
    public function removeParagraph($id,Request $request,SessionInterface $session)
    {
        
        return $this->removeItem($id,'P',$request,$session);
    }
    
    private function addItem($id,$key,Request $request,SessionInterface $session)
    {
        $bookmarks=$this->getBookmarksArray($session);
        
        $bookmarks[]=$key.':'.$id;
        
        $this->setBookmarksArray($bookmarks,$session);
        
        return $this->redirect($request->headers->get('referer','/'));
    }
    
    private function removeItem($id,$key,Request $request,SessionInterface $session)
    {
        $bookmarks=$this->getBookmarksArray($session);
        
        for($i=0;$i<sizeof($bookmarks);$i++)
        {
            $bookmark=explode(":",$bookmarks[$i]);
            
            if(sizeof($bookmark)==2 && $bookmark[0]=$key && $bookmark[1]==$id)
            {
                unset($bookmarks[$i]);
                break;
            }
        }
        
        $this->setBookmarksArray($bookmarks,$session);
        
        return $this->redirect($request->headers->get('referer','/'));
    }
    
    private function getBookmarksArray(SessionInterface $session)
    {
        $s_bookmarks = $session->get(BookmarksController::session_key,'');
        $bookmarks=explode(";", $s_bookmarks);
        
        return $bookmarks;
    }
    
    private function setBookmarksArray($bookmarks,SessionInterface $session)
    {
        $s_bookmarks = implode(";", $bookmarks);
        
        $session->set(BookmarksController::session_key,$s_bookmarks);
    }
    
    public function list(Request $request,TipitakaTocRepository $tocRepository, TipitakaParagraphsRepository $paragraphsRepository,SessionInterface $session)
    {
        $bookmarks_str=$request->get('b');
        
        if(!$bookmarks_str)
        {
            $bookmarks_str = $session->get(BookmarksController::session_key,'');
        }
        
        $bookmarks_ar=explode(";", $bookmarks_str);
        $bookmarks=array();
        
        foreach($bookmarks_ar as $bookmark_item)
        {
            $bookmark=explode(":",$bookmark_item);
            
            if($bookmark[0]=="N")
            {
                $bookmarks[]=$tocRepository->getNode($bookmark[1]);                
            }
            
            if($bookmark[0]=="P")
            {
                $bookmarks[]=$paragraphsRepository->getParagraph($bookmark[1]);  
            }
        }
        
        
        return $this->render('bookmarks.html.twig',
            ['bookmarks'=>$bookmarks,'bs'=>$bookmarks_str]);
    }
}

