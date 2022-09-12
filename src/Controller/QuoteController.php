<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\QuoteRepository;
use App\Repository\TipitakaParagraphsRepository;
use App\Repository\TipitakaSentencesRepository;
use App\Repository\TipitakaTocRepository;
use App\Security\Roles;
use App\Twig\CapitalizeExtension;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\NativeRepository;

class QuoteController extends AbstractController
{   
    private $headers=['Access-Control-Allow-Origin'=>'*',
        'Access-Control-Allow-Headers'=>'content-type',
        'Content-Type'=>'application/json; charset=UTF-8'];
    
    public function quotePali($paragraphids,QuoteRepository $qr)
    {
        $idlist=$this->parseNumericList($paragraphids);
        
        if($idlist)
        {
            $ce=new CapitalizeExtension();
            $ar_text=$qr->listParagraphs($idlist);
            $ar_caps=array();
            foreach($ar_text as $item)
            {
                $ar_caps[]["Text"]=$ce->capitalize($item["Text"], $item["caps"]);
            }
            
            $joined=$this->joinText($ar_caps);
        }
        
        return $this->json(['Text'=>$joined],200,$this->headers);
    }
        
    public function quoteSentenceTranslation($translationids,QuoteRepository $qr)
    {
        $idlist=$this->parseNumericList($translationids);
        
        if($idlist)
        {
            $ar_text=$qr->listSentenceTranslations($idlist);
            $joined=$this->joinText($ar_text);
        }
        
        return $this->json(['Text'=>$joined],200,$this->headers);
    }
    
    public function getCode(Request $request,TipitakaParagraphsRepository $paragraphsRepository,
        TipitakaSentencesRepository $sentencesRepository)
    {                  
        $formView=NULL;
        
        
        if($request->get('pali'))
        {            
            $paraid=$request->get('pali');
            $id=[0=>['id'=>$request->get('pali')]];
            $paragraph=$paragraphsRepository->getParagraph($request->get('pali'));            
            $title=$paragraph['nodetitle'];	
            $class_key='TipParagraphs';
            $function_name='getPali';
            $sentenceid=0;
        }                
                
        if($request->get('sentencetranslation'))
        {
            $paraid=$request->get('paragraphid');
            $id=$request->get('paragraphid');                
            $paragraph=$paragraphsRepository->getParagraph($id);
            $title=$paragraph['nodetitle'];            
            $class_key='SentenceTransl';
            $function_name='getSentenceTranslation';
            $translation=$sentencesRepository->getTranslation($request->get('sentencetranslation'));
            $sentenceid=$translation->getSentenceid()->getSentenceid();
            $id=[0=>['id'=>$request->get('sentencetranslation'),'sentenceid'=>$sentenceid]];
            
            $form = $this->createFormBuilder()
            ->add('rows', IntegerType::class,['required' => false,'label' => false])
            ->add('update', SubmitType::class)
            ->getForm();
            
            $form->handleRequest($request);           
            
            if ($form->isSubmitted() && $form->isValid())
            {
                $id=$sentencesRepository->listTranslationsRows($request->get('sentencetranslation'),
                    $form->get('rows')->getData());
            }
            else
            {
                $form->get('rows')->setData(1);
            }
            
            $formView=$form->createView();
        }
        
        return $this->render('quote_code.html.twig', ['ids'=>$id,'title'=>$title, 
            'class_key'=>$class_key,'function_name'=>$function_name,'paraid'=>$paraid,'form'=>$formView,
            'sentenceid'=>$sentenceid
        ]);
    }
        
    private function parseNumericList($list)
    {
        $numbers=explode(',',$list);
        
        foreach($numbers as $number)
        {
            if(!is_numeric($number))
            {
                $list=null;
                break;
            }
        }
        
        return $numbers;
    }
    
    private function joinText($qr)
    {        
        $paragraphs=array();
        foreach($qr as $item)
        {
            $paragraphs[]=$item["Text"];
        }
        
        return implode("<br>",$paragraphs);
    }
    
    function aggregate($query_result)
    {
        $agg=array();
        if(sizeof($query_result)>0)
        {
            $agg[]=$query_result[0];
            $paragraphid=$query_result[0]["paragraphid"];
            
            for($i=1;$i<sizeof($query_result);$i++)
            {
                if($query_result[$i]["paragraphid"]==$paragraphid)
                {
                    $agg[sizeof($agg)-1]["Text"]=$agg[sizeof($agg)-1]["Text"]." ".$query_result[$i]["Text"];
                }
                else
                {
                    $agg[]=$query_result[$i];
                    $paragraphid=$query_result[$i]["paragraphid"];
                }
            }
        }
        
        return $agg;
    }
    
    public function quoteSentences($sentenceid,$length, TipitakaTocRepository $tocRepository,
        TipitakaParagraphsRepository $paragraphsRepository, NativeRepository $nativeRepository,
        TipitakaSentencesRepository $sentencesRepository,Request $request)
    {
        $sentence=$sentencesRepository->find($sentenceid);
        
        if($sentence)
        {              
            $sentencedata=$sentencesRepository->getNodeIdParagraphIdBySentenceId($sentenceid);
            $nodeid=$sentencedata["nodeid"];
            $paragraph=$paragraphsRepository->getParagraph($sentencedata["paragraphid"]);
            
            $path_nodes=$tocRepository->listPathNodesWithNamesTranslation($nodeid,$request->getLocale());
            $sources=$sentencesRepository->listParagraphSources($sentencedata["paragraphid"]);
            
            $sentences=$nativeRepository->listSentencesForQuote($nodeid,$sentenceid,$length);
            //this will pull out all the translations for the node when we will usually need only some
            $translations=$sentencesRepository->listTranslationsByNodeId($nodeid);
                        
            $response= $this->render('quote_view.html.twig',
                ['paragraph'=>$paragraph,'path_nodes'=>$path_nodes,
                    'sentences'=>$sentences,'translations'=>$translations,'sources'=>$sources,
                    'userRole'=>Roles::User
                ]);
        }
        else
        {
            $response=new Response('not found',404);
        }
        
        return $response;
    }
}

