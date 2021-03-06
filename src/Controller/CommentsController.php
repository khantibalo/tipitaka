<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Repository\TipitakaCommentsRepository;
use App\Repository\TipitakaSentencesRepository;
use App\Repository\TipitakaTocRepository;
use App\Security\Roles;
use App\Entity\TipitakaComments;
use Symfony\Component\HttpFoundation\Response;

class CommentsController  extends AbstractController
{   
    public function listBySentence($sentenceid,TipitakaSentencesRepository $sentenceRepository,
        TipitakaCommentsRepository $commentsRepository,Request $request,TipitakaTocRepository $tocRepository)
    {                
        $sentence=$sentenceRepository->find($sentenceid);
                
        if($sentence)
        {
            $paragraphid=NULL;
            $nodeid=NULL;
            $return=$request->query->get('return');
            $node=$sentenceRepository->getNodeIdBySentenceId($sentenceid);
            $path_nodes=$tocRepository->listPathNodesWithNamesTranslation($node['nodeid'],$request->getLocale());
            
            if($return=='node')
            {
                $nodeid=$node['nodeid'];                
            }
            else 
            {
                $paragraphid=$sentence->getParagraphid()->getParagraphid();
            }
            
            $sentenceText=$sentence->getSentencetext();
            $translations=$sentenceRepository->listTranslationsBySentenceId($sentenceid);
            $comments=$commentsRepository->listBySentenceId($sentenceid);
            
            $form = $this->createFormBuilder()
            ->add('comment', TextareaType::class,['required' => true,'label' => false,'mapped'=>false])
            ->add('submit', SubmitType::class,['label' => 'save'])
            ->getForm();
            
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid())
            {                               
                $comment=new TipitakaComments();
                
                $comment->setSentenceid($sentence);
                $comment->setCreateddate(new \DateTime());                
                $comment->setAuthorid($this->getUser());
                $comment->setCommenttext($form->get('comment')->getData());

                $commentsRepository->add($comment);
                
                $params=['sentenceid'=>$sentenceid];
                
                if($return)
                {
                    $params['return']=$return;
                }
                
                $response=$this->redirectToRoute('comments',$params);
            }
            else 
            {                       
                $userid=$this->getUser();
                if($userid)
                {
                    $userid=$userid->getUserid();
                }
                
                $response=$this->render('comments.html.twig', ['form' => $form->createView(),
                    'sentenceText'=>$sentenceText,'path_nodes'=>$path_nodes,'paragraphid'=>$paragraphid,'nodeid'=>$nodeid,
                    'translations'=>$translations,'comments'=>$comments,'userRole'=>Roles::User,'adminRole'=>Roles::Admin,
                    'userid'=>$userid,'sentenceid'=>$sentenceid,'return'=>$return
                ]);
            }
        }
        else 
        {
            throw $this->createNotFoundException("not found");
        }
        
        return $response;
    }
    
    public function commentDelete($commentid,TipitakaCommentsRepository $commentsRepository,Request $request)
    {        
        $return=$request->query->get('return');
        $comment=$commentsRepository->find($commentid);
        
        if($comment)
        {//comment author or admin
            if($comment->getAuthorid()->getUserid()==$this->getUser()->getUserid() || $this->isGranted(Roles::Admin))
            {            
                $commentsRepository->delete($comment);            
            }
            
            $params=['sentenceid'=>$comment->getSentenceid()->getSentenceid()];

            if($return)
            {
                $params['return']=$return;
            }
            
            $response=$this->redirectToRoute('comments',$params);
        }
        else
        {
            throw $this->createNotFoundException("not found");
        }
        
        return $response;
    }
    
    public function commentsFeed(TipitakaCommentsRepository $commentsRepository)
    {
        $title="Tipitaka latest comments";
        $link=$this->generateUrl('comments_feed',[],UrlGeneratorInterface::ABSOLUTE_URL);
        $description='20 latest comments';
        $langcode='RU';
        $items=$commentsRepository->listLatestFeed(20);
        
        for($i=0;$i<sizeof($items);$i++)
        {
            $items[$i]['link']=$this->generateUrl('comments',['sentenceid'=>$items[$i]['sentenceid'],
                '_fragment'=>"c".$items[$i]['commentid']
            ],UrlGeneratorInterface::ABSOLUTE_URL);
        }
        
        $response=$this->render('rss_feed.html.twig',['title'=>$title,'link'=>$link,'description'=>$description,
            'langcode'=>$langcode,'items'=>$items,'titleFormat'=>'Комментарий к главе "%s"'
        ]);
        
        $response->headers->set('Content-Type', 'application/rss+xml');
        
        return $response;
    }
    
    public function legacyRedirect($legacyid,TipitakaSentencesRepository $sentencesRepository)
    {
        $sentence=$sentencesRepository->findByLegacyid($legacyid);
        
        if(sizeof($sentence)>0)
        {
            $params=['sentenceid'=>$sentence[0]->getSentenceid()];
                
            $response=$this->redirectToRoute('comments',$params);
        }
        else
        {
            $response=new Response("not found",404);
        }        
        
        return $response;
    }
}

