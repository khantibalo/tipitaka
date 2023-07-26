<?php
namespace App\Controller;

use App\Repository\NativeRepository;
use App\Repository\TipitakaSentencesRepository;
use App\Repository\TipitakaTocRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\TipitakaParagraphsRepository;
use App\Security\Roles;
use App\Twig\CapitalizeExtension;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\TipitakaSourcesRepository;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Cookie;
use App\Repository\TipitakaTagsRepository;
use App\Repository\TipitakaCollectionsRepository;

class ViewController extends AbstractController
{
    public function nodeView($id, TipitakaTocRepository $tocRepository,TipitakaParagraphsRepository $paragraphsRepository,
        Request $request,TipitakaTagsRepository $tagsRepository)
    {
        $prologue=$request->query->get('prologue');
        
        $node=$tocRepository->findOneBy(['nodeid'=>$id]);
        
        $path_nodes=$tocRepository->listPathNodes($id);
                
        $nodes=$tocRepository->listAllChildNodes($id);
        
        if($prologue)
        {
            $nodes=array($nodes[0]);
        }
        
        $paragraphs=$paragraphsRepository->listByNode($node);
                              
        $pn=$paragraphsRepository->listPageNumbersByNode($node);
        
        $notes=$paragraphsRepository->listNotesByNode($node);        
                        
        $back_id='';
        $next_id='';
        $back_prologue=false;
        
        if($prologue)
        {
            $childNodes=$tocRepository->listAllChildNodes($id);
            $next_id=$childNodes[1]['nodeid'];
        }
        else
        {        
            $backnext=$tocRepository->getBackNextNode($id);
                    
            if(sizeof($backnext)>0)
            {
                if($backnext[0]['Prev'])
                {
                    $back_id=$backnext[0]['Prev'];
                }
                
                if($backnext[0]['Next'])
                {
                    $next_id=$backnext[0]['Next'];
                }
            }
            
            if($back_id=='')
            {
                $parentNode=$path_nodes[sizeof($path_nodes)-2];
                if($parentNode->getHasprologue())
                {
                    $back_id=$parentNode->getNodeid();
                    $back_prologue=true;
                }
            }
        }
        
        $view_settings=$this->getViewSettings('view_node',$id,$back_id,$next_id,$request);
        
        $ci=new CapitalizeExtension();
        
        for($i=0;$i<sizeof($paragraphs);$i++)
        {
            $item=$paragraphs[$i];
            $paragraphs[$i]['text']=$ci->capitalize($item['text'],$item['caps']);
                        
            $pid=(string)$item['paragraphid'];
                                   
            $paragraph_pn=array_key_exists($pid, $pn) ? $pn[$pid] : array();
            $paragraph_notes=array_key_exists($pid, $notes) ? $notes[$pid] : array();
            
            $paragraphs[$i]['text']=$this->formatParagraph($paragraphs[$i]['text'],$paragraphs[$i]['bold'],
                $paragraph_pn,$paragraph_notes,$view_settings);
        }
        
        $tags=$tagsRepository->listByOneNodeId($id,$request->getLocale());
        
        $related=$tocRepository->listRelatedNodes($id,$request->getLocale());
                
        return $this->render('node_view.html.twig',
            ['node'=>$node,'path_nodes'=>$path_nodes,'nodes'=>$nodes,'paragraphs'=>$paragraphs,
                'view_settings'=>$view_settings,'authorRole'=>Roles::Author,'backPrologue'=>$back_prologue,
                'tags'=>$tags,'authorRole'=>Roles::Author,'related'=>$related]
            ); 
    }
    
    public function paragraphView($id, TipitakaTocRepository $tocRepository,TipitakaParagraphsRepository $paragraphsRepository,
        TipitakaSentencesRepository $sentencesRepository,Request $request)
    {
        $paragraph=$paragraphsRepository->getParagraph($id);
                
        if($paragraph)
        {        
            $path_nodes=$tocRepository->listPathNodesWithNamesTranslation($paragraph['nodeid'],$request->getLocale());
                    
            $pn=$paragraphsRepository->listPageNumbersByParagraph($id);
            
            $notes=$paragraphsRepository->listNotesByParagraph($id);   
            
            
            $back_id='';
            $next_id='';
            $backnext=$paragraphsRepository->getBackNextParagraph($id);
            
            if(sizeof($backnext)==3)
            {
                if($backnext[0]['nodeid']==$backnext[1]['nodeid'])
                {
                    $back_id=$backnext[0]['paragraphid'];
                }
                
                if($backnext[2]['nodeid']==$backnext[1]['nodeid'])
                {
                    $next_id=$backnext[2]['paragraphid'];
                }
            }
            
            $sentences=$sentencesRepository->listByParagraphid($id);
            
            $translations=$sentencesRepository->listTranslationsByParagraphid($id);
            
            $ci=new CapitalizeExtension();
            
            $view_settings=$this->getViewSettings('view_paragraph',$id,$back_id,$next_id,$request);
            $paragraph['text']=$ci->capitalize($paragraph['text'],$paragraph['caps']);
            $paragraph['text']=$this->formatParagraph($paragraph['text'],$paragraph['bold'],$pn,$notes,$view_settings);     
            
            $sources=$sentencesRepository->listParagraphSources($id);        
                  
            $response= $this->render('paragraph_view.html.twig',
                ['paragraph'=>$paragraph,'path_nodes'=>$path_nodes,'view_settings'=>$view_settings,
                    'sentences'=>$sentences,'translations'=>$translations,'sources'=>$sources,
                    'showNewSource'=>$request->query->get('showNewSource'),
                    'showCode'=>$request->query->get('showCode'),'authorRole'=>Roles::Author, 'userRole'=>Roles::User,'backPrologue'=>NULL,
                    'editorRole'=>Roles::Editor
                ]);
        }
        else
        {
            $response=new Response('not found',404);
        }
        
        return $response;
    }
           
    private function formatParagraph($text,$boldmarkups,$pagenumbers,$notes,$view_settings)
    {
        //copy all formatting objects into one array indexed by position
        $markup=array();
        
        foreach($pagenumbers as $pagenumber)
        {
            $position=$pagenumber['position'];
            
            if(!array_key_exists($position, $markup))
            {
                $markup[$position]=array();
            }
            
            $markup[$position][]=$pagenumber;
        }
        
        if($boldmarkups)
        {
            $boldPositions=explode(",",$boldmarkups);
            for($i=0;$i<sizeof($boldPositions);$i++)
            {
                $position=$boldPositions[$i];
                
                if(!array_key_exists($position, $markup))
                {
                    $markup[$position]=array();
                }
                
                $markup[$position][]=array("boldpos"=>$position,"isbegin"=>($i % 2==0));
            }
        }
        
        foreach($notes as $note)
        {
            $position=$note['position'];
            
            if(!array_key_exists($position, $markup))
            {
                $markup[$position]=array();
            }
            
            $markup[$position][]=$note;
        }
        
        if(sizeof($markup)>0)
        {
            $result=array();
            $current_pos=0;
            
            $keys=array_keys($markup);
            asort($keys);//sort keys in ascending order
            
            mb_internal_encoding("UTF-8");
            //iterate the array by keys in ascending order to process content from begining
            foreach($keys as $position)
            {
                $line=mb_substr($text, $current_pos,$position-$current_pos);
                
                foreach($markup[$position] as $markup_item)
                {
                    if(array_key_exists('boldpos', $markup_item))
                    {
                        $line.=$markup_item["isbegin"]=="1" ? "<span class=\"bld\">" : "</span>";
                    }
                    
                    if(array_key_exists('pagenumber', $markup_item) && !empty($view_settings['ic']) && $markup_item['issuecode']==$view_settings['ic'])
                    {
                        $line.="<span class=\"pagenum\">[".$markup_item['issuecode']." ".
                            $markup_item['volumenumber'].".".$markup_item['pagenumber']."]</span>";
                    }
                    
                    if(array_key_exists('notetext',$markup_item) && empty($view_settings['notes']))
                    {
                        $line.="<span class=\"note\">[".$markup_item['notetext']."]</span> ";
                    }
                }
                
                $current_pos=$position;
                $result[]=$line;
            }
            
            $result[]=mb_substr($text,$current_pos);
            $formatted=implode($result);
        }
        else
        {
            $formatted=$text;
        }
        
        $formatted=str_replace([" ,"," ."],[",","."],$formatted);
        
        return $formatted;    
    }
    
    
    private function getViewSettings($route,$id,$back_id,$next_id,Request $request)
    {
        $view_settings=array('ic'=>'','notes'=>'','printview'=>'','view_route'=>$route,'id'=>$id,'back_id'=>$back_id,'next_id'=>$next_id);
                
        $view_settings['ic']=$request->query->get('ic','');
        $view_settings['notes']=$request->query->get('notes','');
        $view_settings['printview']=$request->query->get('printview','');
                
        return $view_settings;
    }
    
    public function tableView($id,TipitakaTocRepository $tocRepository,TipitakaSentencesRepository $sentencesRepository,Request $request,
        TipitakaTagsRepository $tagsRepository,TipitakaCollectionsRepository $collectionsRepository)
    {                
        $node=$tocRepository->getNodeWithNameTranslation($id,$request->getLocale());
        
        if($node)
        {
            $prologue=$request->query->get('prologue');
            
            if($prologue)
            {
                $response=$this->tableViewSingle($id,$node,$tocRepository,$sentencesRepository,$request,
                    $prologue,$tagsRepository,$collectionsRepository);
            }
            else 
            {
                if($node['haschildnodes'])
                {
                    $response=$this->tableViewMulti($id,$node,$tocRepository,$sentencesRepository,$request,$tagsRepository);
                }
                else
                {
                    $response=$this->tableViewSingle($id,$node,$tocRepository,$sentencesRepository,$request,
                        $prologue,$tagsRepository,$collectionsRepository);
                }
            }
        }
        else
        {
            $response=new Response('not found',404);
        }
        
        return $response;
    }
    
    private function tableViewSingle($nodeid,$node,TipitakaTocRepository $tocRepository,
        TipitakaSentencesRepository $sentencesRepository,
        Request $request,$prologue,TipitakaTagsRepository $tagsRepository,
        TipitakaCollectionsRepository $collectionsRepository)
    {
        $path_nodes=$tocRepository->listPathNodesWithNamesTranslation($nodeid,$request->getLocale());

        $back_id='';
        $next_id='';
        $back_prologue=false;
        
        if($prologue)
        {
            $childNodes=$tocRepository->listAllChildNodes($nodeid);
            $next_id=$childNodes[1]['nodeid'];
        }
        else 
        {
            $backnext=$tocRepository->getBackNextNodeWithTranslation($nodeid);
            
            if(sizeof($backnext)>0)
            {
                if($backnext[0]['Prev'])
                {
                    $back_id=$backnext[0]['Prev'];
                }
                
                if($backnext[0]['Next'])
                {
                    $next_id=$backnext[0]['Next'];
                }
            }
            
            if($back_id=='')
            {
                $parentNode=$path_nodes[sizeof($path_nodes)-2];
                if($parentNode['hasprologue'])
                {
                    $back_id=$parentNode['nodeid'];
                    $back_prologue=true;
                }
            }
        }
        
        $sentences=$sentencesRepository->listByNodeId($nodeid);
        
        $translations=$sentencesRepository->listTranslationsByNodeId($nodeid);
        
        $sources=$sentencesRepository->listNodeSources($nodeid);
        
        $form=$this->buildSourceForm($sources);        
        $filteredSources=$this->processForm($form,$sources,$request,$nodeid);
        
        $related=$tocRepository->listRelatedNodes($nodeid,$request->getLocale());
        
        $tags=$tagsRepository->listByOneNodeId($nodeid,$request->getLocale());       
                
        $response=$this->render('table_view_single.html.twig', ['node'=>$node,'path_nodes'=>$path_nodes,
            'sentences'=>$sentences,'translations'=>$translations,'sources'=>$filteredSources,'back_id'=>$back_id,
            'next_id'=>$next_id,'showNewSource'=>$request->query->get('showNewSource'),
            'showCode'=>$request->query->get('showCode'),'authorRole'=>Roles::Author,
            'showAlign'=>$request->query->get('showAlign'),'userRole'=>Roles::User,
            'form' => $form->createView(),'allSources'=>$sources,'related'=>$related,
            'adminRole'=>Roles::Admin,'showPali'=>$form->get("pali")->getData(),'showComments'=>$form->get("comments")->getData(),
            'backPrologue'=>$back_prologue,'tags'=>$tags,'editorRole'=>Roles::Editor,
        ]);
        
        if ($form->isSubmitted() && $form->isValid())
        {
            $this->setCookies($response,$form,$nodeid);
        }
        
        return $response;
    }
    
    private function tableViewMulti($nodeid,$node,TipitakaTocRepository $tocRepository,TipitakaSentencesRepository $sentencesRepository,
        Request $request,TipitakaTagsRepository $tagsRepository)
    {        
        $path_nodes=$tocRepository->listPathNodesWithNamesTranslation($nodeid,$request->getLocale());
        
        $immediate_sentences=$sentencesRepository->listByNodeId($nodeid);
        
        $child_sentences=$sentencesRepository->listChildByNodeId($nodeid,$node['path']);
        
        $translations=$sentencesRepository->listChildTranslationsByNodeId($nodeid,$node['path']);
        
        $sources=$sentencesRepository->listChildNodeSources($nodeid,$node['path']);
        
        $child_nodes=$tocRepository->listChildNodesWithNames($nodeid, $request->getLocale());
        
        $form=$this->buildSourceForm($sources);
        $filteredSources=$this->processForm($form,$sources,$request,$nodeid);
        
        $related=$tocRepository->listRelatedNodes($nodeid,$request->getLocale());
        
        $tags=$tagsRepository->listByOneNodeId($nodeid,$request->getLocale());
        
        
        $back_id='';
        $next_id='';
        
        $backnext=$tocRepository->getBackNextNodeWithTranslation($nodeid);
        
        if(sizeof($backnext)>0)
        {
            if($backnext[0]['Prev'])
            {
                $back_id=$backnext[0]['Prev'];
            }
            
            if($backnext[0]['Next'])
            {
                $next_id=$backnext[0]['Next'];
            }
        }
                
        $response=$this->render('table_view_multi.html.twig', ['node'=>$node,'path_nodes'=>$path_nodes,
            'child_sentences'=>$child_sentences,'translations'=>$translations,'sources'=>$filteredSources,
            'showCode'=>$request->query->get('showCode'),'authorRole'=>Roles::Author,
            'userRole'=>Roles::User,'child_nodes'=>$child_nodes,'immediate_sentences'=>$immediate_sentences,
            'showAlign'=>false,'form' => $form->createView(),'allSources'=>$sources,'related'=>$related,
            'adminRole'=>Roles::Admin,'showPali'=>$form->get("pali")->getData(),'showComments'=>$form->get("comments")->getData(),
            'tags'=>$tags,'editorRole'=>Roles::Editor, 'back_id'=>$back_id, 'next_id'=>$next_id
        ]);
        
        if ($form->isSubmitted() && $form->isValid())
        {
            $this->setCookies($response,$form,$nodeid);
        }
        
        return $response;
    }
    
    private function buildSourceForm($sources)
    {
        $choicesAssoc=array();

        foreach ($sources as $source)
        {
            $choicesAssoc[$source["sourcename"]." - ".$source["languagename"]]=$source["sourceid"];
        }
        
        $form = $this->createFormBuilder(null,  array('csrf_protection' => false))
        ->add('sources', ChoiceType::class,
            ['choices'  => $choicesAssoc,
                'label' => false,
                'expanded'=>true,
                'multiple'=>true,
                'required' => false,
                'mapped' => false
            ])
        ->add('pali', CheckboxType::class,['label' => false,'required' => false, 'mapped' => false])
        ->add('comments', CheckboxType::class,['label' => false,'required' => false, 'mapped' => false])
        ->add('update', SubmitType::class)
        ->getForm();
                
        return $form;
    }
    
    private function processForm(&$form,$sources,Request $request,$nodeid)
    {
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid())
        {
            $retSources=array();
            $checkedItems=$form->get("sources")->getData();
            
            foreach ($sources as $source)
            {
                foreach($checkedItems as $checkedItem)
                {
                    if($source["sourceid"]==$checkedItem)
                    {
                       $retSources[]=$source;
                       break;
                    }
                }
            }
        }
        else
        {
            $retSources=array();
            $checked=array();
                        
            $paliCookie=$request->cookies->get("pali$nodeid");                        
            $paliVisible=true;
            
            if($paliCookie!=NULL)
            {
                $paliVisible=$paliCookie=="1";
            }     
            
            $form->get("pali")->setData($paliVisible);
            
            $sourcesCookie=$request->cookies->get("sources".$nodeid);
            
            if($sourcesCookie)
            {                
                foreach ($sources as $source)
                {
                    if(strstr($sourcesCookie, ';'.$source["sourceid"].';')!=FALSE)
                    {
                        $checked[]=$source["sourceid"];
                        $retSources[]=$source;
                    }
                }
            }
            else 
            {
                $user=$this->getUser();
                foreach ($sources as $source)
                {
                    if(!$source["ishidden"] || $user)
                    {
                        if(!$source["hasformatting"])
                        {
                            $checked[]=$source["sourceid"];
                            $retSources[]=$source;
                        }
                    }
                }
            }
            
            $form->get("sources")->setData($checked);
            
            $commentsCookie=$request->cookies->get("comments$nodeid");            
            $commentsVisible=true;
            
            if($commentsCookie!=NULL)
            {
                $commentsVisible=$commentsCookie=="1";
            }  
            
            $form->get("comments")->setData($commentsVisible);                        
        }
        
        return $retSources;
    }
    
    private function setCookies(Response &$response,$form,$nodeid)
    {
        $paliChecked=$form->get("pali")->getData();
        $response->headers->setCookie(new Cookie("pali$nodeid",$paliChecked ? $paliChecked : "0",time() + (3600 * 24*365)));
        
        $commentsChecked=$form->get("comments")->getData();
        $response->headers->setCookie(new Cookie("comments$nodeid",$commentsChecked ? $commentsChecked : "0",time() + (3600 * 24*365)));
        
        $checkedItems=$form->get("sources")->getData();        
        $checkedSources=';'.implode(';',$checkedItems).';';
        $response->headers->setCookie(new Cookie("sources$nodeid",$checkedSources,time() + (3600 * 24*365)));
        
    }
    
    public function translationView($id,Request $request,TipitakaTocRepository $tocRepository,
        TipitakaSentencesRepository $sentencesRepository,TipitakaParagraphsRepository $paragraphsRepository,
        TipitakaSourcesRepository $sourcesRepository,TipitakaTagsRepository $tagsRepository)
    {
        //view и bookmark links are initially hidden, but it is possible to show them
        //this will show a translation from the source that is specified in node edit form
        $node=$tocRepository->getNodeWithNameTranslation($id,$request->getLocale());
                        
        if($node['TranslationSourceID'])
        {
            $nodeObj=$tocRepository->find($id);
            $nodes=$tocRepository->listAllChildNodesWithNamesTranslation($nodeObj,$request->getLocale());//that should be the language of the source            
            $paragraphs=$paragraphsRepository->listByNode($nodeObj);
            $path_nodes=$tocRepository->listPathNodesWithNamesTranslation($id,$request->getLocale());
            $translations=$sentencesRepository->listTranslationsBySourceId($id,$node['path'],$node['TranslationSourceID']);
            $source=$sourcesRepository->find($node['TranslationSourceID']);
            $related=$tocRepository->listRelatedNodes($id,$request->getLocale());
            
            $tags=$tagsRepository->listByOneNodeId($id,$request->getLocale());
            
            $back_id='';
            $next_id='';
            
            $backnext=$tocRepository->getBackNextNodeWithTranslation($id);
            
            //FIXME: this will always link to table view, hovewer, we are in translation mode and it should see
            //if that is available for back or next node and if it does, link to that
            
            if(sizeof($backnext)>0)
            {
                if($backnext[0]['Prev'])
                {
                    $back_id=$backnext[0]['Prev'];
                }
                
                if($backnext[0]['Next'])
                {
                    $next_id=$backnext[0]['Next'];
                }
            }
            
            $response=$this->render('translation_view.html.twig', ['node'=>$node,'path_nodes'=>$path_nodes,
                'paragraphs'=>$paragraphs,'nodes'=>$nodes,'translations'=>$translations,
                'showsidebar'=>false,'source'=>$source,'related'=>$related,'tags'=>$tags,'authorRole'=>Roles::Author,
                'back_id'=>$back_id, 'next_id'=>$next_id
            ]);
        }
        else
        {
            $response=new Response('not found',404);
        }        
        
        return $response;
    }
    
    public function paragraphAnalyze($id, TipitakaTocRepository $tocRepository,TipitakaParagraphsRepository $paragraphsRepository,
        NativeRepository $nativeRepository,Request $request)
    {
        $paragraph=$paragraphsRepository->getParagraph($id);
        
        $path_nodes=$tocRepository->listPathNodesWithNamesTranslation($paragraph['nodeid'],$request->getLocale());
        
        $pn=$paragraphsRepository->listPageNumbersByParagraph($id);
        
        $notes=$paragraphsRepository->listNotesByParagraph($id);
        
        
        $back_id='';
        $next_id='';
        $backnext=$paragraphsRepository->getBackNextParagraph($id);
        
        if(sizeof($backnext)==3)
        {
            if($backnext[0]['nodeid']==$backnext[1]['nodeid'])
            {
                $back_id=$backnext[0]['paragraphid'];
            }
            
            if($backnext[2]['nodeid']==$backnext[1]['nodeid'])
            {
                $next_id=$backnext[2]['paragraphid'];
            }
        }
               
        $ci=new CapitalizeExtension();
        
        $view_settings=$this->getViewSettings('view_paragraph',$id,$back_id,$next_id,$request);
        $paragraph['text']=$ci->capitalize($paragraph['text'],$paragraph['caps']);
        
        //we split the text, but not saving it anywhere
        $ar_sentences =preg_split('/(?<=[.?!])\s+(?=[A-ZĀĪŪṬÑṂṆṄḶḌ"\'])/u', $paragraph['text']);
        
        $analysisResults=array();
        
        foreach ($ar_sentences as $sentenceText)
        {
            $sentenceTextFixed=str_replace("\""," ",$sentenceText);
            $ar_result=$nativeRepository->analyzeSentence($sentenceTextFixed, 1);
            $analysisResult=array_pop($ar_result);
            $analysisResult["origsentencetext"]=$sentenceText;
            
            $analysisResults[]=$analysisResult;
        }
        
        $paragraph['text']=$this->formatParagraph($paragraph['text'],$paragraph['bold'],$pn,$notes,$view_settings);
                
        return $this->render('paragraph_analyze.html.twig',
            ['paragraph'=>$paragraph,'path_nodes'=>$path_nodes,'view_settings'=>$view_settings,
                'authorRole'=>Roles::Author, 'userRole'=>Roles::User,'backPrologue'=>NULL,
                'editorRole'=>Roles::Editor,'analysisResults'=>$analysisResults
            ]);
    }
    
    public function paragraphSentenceAnalyze($id, $ordinal, TipitakaTocRepository $tocRepository,TipitakaParagraphsRepository $paragraphsRepository,
        NativeRepository $nativeRepository,Request $request)
    {
        $paragraph=$paragraphsRepository->getParagraph($id);
        
        $path_nodes=$tocRepository->listPathNodesWithNamesTranslation($paragraph['nodeid'],$request->getLocale());
        
        $pn=$paragraphsRepository->listPageNumbersByParagraph($id);
        
        $notes=$paragraphsRepository->listNotesByParagraph($id);
        
        
        $back_id='';
        $next_id='';
        $backnext=$paragraphsRepository->getBackNextParagraph($id);
        
        if(sizeof($backnext)==3)
        {
            if($backnext[0]['nodeid']==$backnext[1]['nodeid'])
            {
                $back_id=$backnext[0]['paragraphid'];
            }
            
            if($backnext[2]['nodeid']==$backnext[1]['nodeid'])
            {
                $next_id=$backnext[2]['paragraphid'];
            }
        }
        
        $ci=new CapitalizeExtension();
        
        $view_settings=$this->getViewSettings('view_paragraph',$id,$back_id,$next_id,$request);
        $paragraph['text']=$ci->capitalize($paragraph['text'],$paragraph['caps']);

        //we split the text, but not saving it anywhere
        $ar_sentences =preg_split('/(?<=[.?!])\s+(?=[A-ZĀĪŪṬÑṂṆṄḶḌ"\'])/u', $paragraph['text']);
        
        $sentenceTextFixed=str_replace("\""," ",$ar_sentences[$ordinal]);
        $analysisResults=$nativeRepository->analyzeSentence($sentenceTextFixed, 100);
        $sentenceText=$ar_sentences[$ordinal];
        
        $paragraph['text']=$this->formatParagraph($paragraph['text'],$paragraph['bold'],$pn,$notes,$view_settings);        
                
        return $this->render('paragraph_sentence_analyze.html.twig',
            ['paragraph'=>$paragraph,'path_nodes'=>$path_nodes,'view_settings'=>$view_settings,
                'authorRole'=>Roles::Author, 'userRole'=>Roles::User,'backPrologue'=>NULL,
                'editorRole'=>Roles::Editor,'analysisResults'=>$analysisResults,'origsentencetext'=>$sentenceText
            ]);
    }
}

