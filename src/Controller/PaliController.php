<?php
namespace App\Controller;

use App\Repository\TipitakaParagraphsRepository;
use App\Repository\TipitakaTocRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\TipitakaToc;
use App\Entity\TipitakaParagraphs;

class PaliController extends AbstractController
{
    public function import($parentid,TipitakaTocRepository $tocRepository,Request $request,
        TipitakaParagraphsRepository $paragraphRepository)
    {
        $form = $this->createFormBuilder()
        ->add('title', TextType::class,['required' => true,'label' => false])
        ->add('titlenodiac', TextType::class,['required' => true,'label' => false])
        ->add('palitext', TextareaType::class,['required' => true,'label' => false,'mapped'=>false])
        ->add('save', SubmitType::class,)
        ->getForm();
            
        $form->handleRequest($request);
        $nodeid=null;
        $message=null;
        
        if ($form->isSubmitted() && $form->isValid())
        {
           $node=new TipitakaToc();
           $node->setParentid($parentid);
           $node->setTitle($form->get("title")->getData());
           $node->setTitlenodiac($form->get("titlenodiac")->getData());
           $node->setHaschildnodes(0);
           
           $titleType=$tocRepository->getTitleType(3);
           $node->setTitletypeid($titleType);
           
           $node->setHasTranslation(false);
           $node->setAllowptspage(false);
           $node->setLinkscount(0);
           $node->setHasTableView(0);
           $node->setHasTranslation(0);
           $node->setDisableview(0);
           $node->setHasprologue(0);
           $node->setDisableTranslAlign(0);
           $node->setIsHidden(0);
           
           $tocRepository->persistNode($node);
           
           $parent=$tocRepository->find($parentid);
           $node->setPath($parent->getPath()."\\".$node->getNodeid());
           $node->setTextpath($parent->getTextPath()."\\".$node->getTitle());
           $tocRepository->persistNode($node);

           $nodeid=$node->getNodeid();
           
           $palitext = $form->get('palitext')->getData();
           if(!empty($palitext))
           {
               $paragraphs=array();
               
               $parts=preg_split("/(ʘ|\?“|!“|\.“|\.”|\?”|\?»|!»|\.»|\.’|\?’|!’|\.'\"|\.\"|\.'|\.\s+|\?|!|\r\n|\n|\r)/iu",$palitext,-1,PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
               if($parts)
               {
                   for($i=0;$i<sizeof($parts);$i++)
                   {
                       $paragraph=$parts[$i];
                       
                       if(!empty(trim($paragraph)))
                       {
                           if(preg_match("/^(ʘ|\?“|!“|\.“|\.”|\?”|\?»|!»|\.»|\.’|\?’|!’|\.'\"|\.\"|\.'|\.|\?|!)\s*/iu",$paragraph))
                           {
                               $paragraphs[sizeof($paragraphs)-1]=$paragraphs[sizeof($paragraphs)-1].trim($paragraph);
                           }
                           else
                           {
                               $paragraphs[]=trim(str_ireplace("\u{0000}",".",$paragraph));
                           }
                       }
                   }
               }
               
               foreach($paragraphs as $paragraph)
               {
                   $pt=$paragraphRepository->getParagraphType(1);
                   
                   $tp=new TipitakaParagraphs();
                   
                   $tp->setNodeid($node);
                   $tp->setText($paragraph);
                   $tp->setParagraphtypeid($pt);
                   
                   $paragraphRepository->persist($tp);
               }
           }
           
           $message="success";
        }
            
        $formView=$form->createView();
        
        $response=$this->render('pali_import.html.twig',
            ['form' => $formView, 'message'=>$message,'nodeid'=>$nodeid,
                'node_title'=>$form->get("title")->getData()]
            );
        
        return $response;
    }
}

