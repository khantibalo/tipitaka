<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Repository\TipitakaCollectionsRepository;
use App\Repository\TipitakaSentencesRepository;
use App\Security\Roles;
use App\Entity\TipitakaCollectionItems;
use App\Repository\TipitakaTocRepository;
use App\Entity\TipitakaCollectionItemNames;
use App\Enums\Languages;

class CollectionController extends AbstractController
{
    public function list(Request $request,TipitakaCollectionsRepository $collectionsRepository,TranslatorInterface $translator,
        TipitakaSentencesRepository $sentencesRepository)
    {                
        $itemid=$request->query->get('itemid');
        $collectionItems=array();
        
        if($itemid)
        {
            $collectionItems=$collectionsRepository->listCollectionItems($itemid,$request->getLocale());
            $collections=$collectionsRepository->fetchCollection($itemid,$request->getLocale());

            $form = $this->createFormBuilder()
            ->add('shownav', CheckboxType::class,['required' => false,'label' => false,'data'=>true])
            ->add('rendermode', ChoiceType::class,
                ['choices'  => [
                    'display print view' => 'disp',
                    'download print view' => 'down'],
                    'label' => false,
                    'expanded'=>true,
                    'multiple'=>false,
                    'data'=>'disp'
                ])
            ->add('table', SubmitType::class)
            ->add('paper', SubmitType::class)
            ->add('translation', SubmitType::class);
            
            $form=$form->getForm();
            
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid())
            {
                $printviewtype="table";
                
                if($form->get("table")->isClicked())
                {
                    $printviewtype="table";
                }
                
                if($form->get("paper")->isClicked())
                {
                    $printviewtype="paper";
                }
                
                if($form->get("translation")->isClicked())
                {
                    $printviewtype="translation";
                }
                
                $templates=["table"=>"collection_print_table.html.twig",
                    "paper"=>"collection_print_paper.html.twig",                    
                    "translation"=>"collection_print_translation.html.twig"];               
                
                //all paragraphs that belong to nodes in this collection
                //all sentences that belong to these paragraphs
                //sentencetranslations for these sentences - we need to find which sourceids are needed
                $paragraphs=$collectionsRepository->listParagraphs($itemid);                               
                $sentences=array();//$collectionsRepository->listSentences($itemid);
                $comments=$collectionsRepository->listCommentsByCollectionIdForPrint($itemid);
                $language=$sentencesRepository->getLanguageByCode($request->getLocale());
                
                $paliTranslations=array();
                $otherTranslations=array();
                
                $long_oe=array();
                $long_oe[0]='/([oe])(.)(?=[aiuoeāīū<])/si';
                $long_oe[1]='/([oe])(ḷ)(?=[aiuoeāīū<])/si';
                $long_oe[2]='/([oe])(ḷh)(?=[aiuoeāīū<])/si';
                $long_oe[3]='/([oe])(ṇ)(?=[aiuoeāīū<])/si';
                $long_oe[4]='/([oe])([};,:\. \]\)])/si';
                $long_oe[5]='/([oe])$/si';
                $long_oe[6]='/([oe])(["\'].)(?=[aiuoeāīū<])/si';
                
                foreach($collectionItems as $collectionItem)
                {
                    if($collectionItem['nodeid'])
                    {
                        $paliSource=NULL;
                        $translSource=NULL;
                        
                        $sources=$sentencesRepository->listNodeSources($collectionItem['nodeid']);
                        foreach($sources as $source)
                        {
                            if($source['languageid']==Languages::Pali)
                            {
                                if($paliSource)
                                {//we have already found a pali source
                                    if($source['hasformatting'])
                                    {//we found a source with formatting - use it instead
                                        $paliSource=$source;
                                    }
                                }
                                else
                                {//this is the first pali source we have found - use it
                                    $paliSource=$source;
                                }
                            }
                            
                            if($source['languageid']==$language->getLanguageid())
                            {
                                if($translSource)
                                {
                                    if(mb_stristr($source['sourcename'], "khantibalo")!=FALSE)
                                    {//this will give priority for sources that have "khantibalo" in their names
                                        $translSource=$source;
                                    }
                                }
                                else
                                {//this is the first translation source we have found - use it
                                    $translSource=$source;
                                }
                            }
                        }
                                                
                        if($paliSource)
                        {
                            //find in this node all translations of this source
                            $pali=$sentencesRepository->listTranslationsBySourceId($collectionItem['nodeid'],'none',$paliSource['sourceid']);  
                            
                            $HasFormatting=$paliSource['hasformatting'];
                        }
                        else
                        {//no pali source found among translations, use the one that in sentences
                            $pali=$sentencesRepository->listSentencesAsTranslations($collectionItem['nodeid']);        
                            $HasFormatting=false;
                        }
                        
                        for($i=0;$i<sizeof($pali);$i++)
                        {
                            $pali[$i]["translation"]=$this->formatPali($pali[$i]["translation"], $long_oe,$HasFormatting);
                        }
                        
                        $paliTranslations[$collectionItem['nodeid']]=$pali;
                        
                            
                        if($translSource)
                        {
                            //find in this node all translations of this source
                            $other=$sentencesRepository->listTranslationsBySourceId($collectionItem['nodeid'],'none',$translSource['sourceid']);
                            $otherTranslations[$collectionItem['nodeid']]=$other;
                        }
                        
                        if($collectionItem['limitrows'] && trim($collectionItem['limitrows'])!="")
                        {
                            $nodeSentences=$collectionsRepository->listSentences($collectionItem['nodeid'],$collectionItem['limitrows']);
                        }
                        else
                        {
                            $nodeSentences=$sentencesRepository->listByNodeId($collectionItem['nodeid']);
                        }
                        
                        if($paliSource==NULL)
                        {
                            for($i=0;$i<sizeof($nodeSentences);$i++)
                            {
                                $nodeSentences[$i]["sentencetext"]=$this->formatPali($nodeSentences[$i]["sentencetext"], $long_oe,false);
                            }
                        }
                        
                        for($i=0;$i<sizeof($nodeSentences);$i++)
                        {
                            $nodeSentences[$i]["collectionitemid"]=$collectionItem["collectionitemid"];
                        }
                        
                        $sentences=array_merge($sentences,$nodeSentences);
                    }
                }
                

                $response=$this->render($templates[$printviewtype], ['collection'=>$collections[0],
                    'collectionItems'=>$collectionItems,'paragraphs'=>$paragraphs,'sentences'=>$sentences,
                    'paliTranslations'=>$paliTranslations,'otherTranslations'=>$otherTranslations,
                    'comments'=>$comments,'shownav'=>$form->get("shownav")->getData()
                ]);     
                    
                if($form->get("rendermode")->getData()=="down")
                {
                    $disposition = HeaderUtils::makeDisposition(
                        HeaderUtils::DISPOSITION_ATTACHMENT,
                        $collections[0]["name"].'.html',
                        'collection.html'
                        );
                    
                    $response->headers->set('Content-Disposition', $disposition);
                }
            }
            else 
            {            
                $formView=$form->createView();
                
                $response=$this->render('collections_list.html.twig', ['collections'=>$collections,'collectionItems'=>$collectionItems,
                    'authorRole'=>Roles::Author,'form' => $formView]);
            }
        }
        else
        {
            $collections=$collectionsRepository->listCollections($request->getLocale());
            $response=$this->render('collections_list.html.twig', ['collections'=>$collections,'collectionItems'=>$collectionItems,
                'authorRole'=>Roles::Author]);
        }
        
        return $response;
    }
    
    public function edit(Request $request, TipitakaCollectionsRepository $collectionsRepository,TranslatorInterface $translator,
        TipitakaSentencesRepository $sentencesRepository,TipitakaTocRepository $tipitakaTocRepository)
    {

        $itemid=$request->query->get('itemid');
        $parentid=$request->query->get('parentid');
        $folder=$request->query->get('folder');

        //all params null = create collection
        //$parentid and $folder not null = create folder
        //$parentid not null = create item
        //$itemid is not null = edit item, folder or collection
                
        $languages=$sentencesRepository->listLanguages();
        $langOptions=['choices'  => $languages,
            'label' => false,
            'expanded'=>false,
            'multiple'=>false,
            'required'=>true
        ];
        
        if(is_null($itemid))
        {
            $langOptions['placeholder']=$translator->trans('Choose an option');
        }
        
        $form = $this->createFormBuilder();
                
        if($itemid)
        {//editing
            $item=$collectionsRepository->find($itemid);
            if($item->getParentid() && $item->getNodeid())
            {//link
                $form=$form
                ->add('nodeid', IntegerType::class,['required' => true])
                ->add('limitrows', TextareaType::class,['required' => false,'label' => false,'mapped'=>false]);
            }
            
            if($item->getParentid()==NULL)
            {
                $form=$form->add('notes', TextareaType::class,['required' => false]);                
            }
        }
        else
        {//add new
            if($parentid)
            {//folder or link
                if($folder)
                {
                    $form=$form
                    ->add('name', TextType::class,['required' => true])
                    ->add('language', ChoiceType::class,$langOptions);
                }
                else 
                {
                    $form=$form
                    ->add('nodeid', IntegerType::class,['required' => true])
                    ->add('limitrows', TextareaType::class,['required' => false,'label' => false,'mapped'=>false]);
                }
            }
            else 
            {//collection
                $form=$form
                ->add('name', TextType::class,['required' => true])
                ->add('language', ChoiceType::class,$langOptions)
                ->add('notes', TextareaType::class,['required' => false,'label' => false]);
            }
        }        

        $form=$form
        ->add('vieworder', IntegerType::class,['required' => true])
        ->add('level', IntegerType::class,['required' => true])
        ->add('save', SubmitType::class);
        
        if($itemid)
        {
            $form=$form->add('delete', SubmitType::class);
        }
        
        $form=$form->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid())
        {
            if($form->get('save')->isClicked())
            {
                if($itemid==NULL)
                {
                    $item=new TipitakaCollectionItems();        
                    
                    $item->setParentid($parentid);
                    $item->setAuthorid($this->getUser());
                }
                else 
                {
                    $item=$collectionsRepository->find($itemid);                
                }
                
                if($form->has("nodeid"))
                {
                    $node=$tipitakaTocRepository->find($form->get("nodeid")->getData());
                    $item->setNodeid($node);
                    $item->setLimitrows($form->get("limitrows")->getData());
                }
                
                $item->setVieworder($form->get("vieworder")->getData());
                $item->setLevel($form->get("level")->getData());
                
                if($form->has("notes"))
                {
                    $item->setNotes($form->get("notes")->getData());
                }
                
                $collectionsRepository->updateCollectionItem($item);
                
                if($form->has("language"))
                {
                    $language=$sentencesRepository->getLanguage($form->get("language")->getData());
                    $collectionsRepository->createItemName($item,$form->get("name")->getData(),$language);
                }
            }
            
            if($form->has("delete") && $form->get('delete')->isClicked())
            {
                $item=$collectionsRepository->find($itemid); 
                $collectionsRepository->deleteCollectionItem($item);
            }
            
            $params=array();
            if($itemid==NULL)
            {
                if($parentid!=null)
                {
                    $params['itemid']=$parentid;
                }
            }
            else 
            {
                $params['itemid']=$item->getParentid();
            }
            
            $response=$this->redirectToRoute('collections_list',$params);
        }
        else
        {            
            if($itemid!=NULL)
            {
                $item=$collectionsRepository->find($itemid);
                $form->get("vieworder")->setData($item->getVieworder());
                $form->get("level")->setData($item->getLevel());
                
                if($item->getNodeid()!=NULL)
                {
                    $form->get("nodeid")->setData($item->getNodeid()->getNodeid());
                    $form->get("limitrows")->setData($item->getLimitrows());
                }   
                
                if($item->getParentid()==NULL)
                {
                    $form->get("notes")->setData($item->getNotes());
                }
            }
            
            $formView=$form->createView();
            $response=$this->render('collection_item_edit.html.twig',['itemid'=>$itemid,'parentid'=>$parentid,
                'folder'=>$folder,'form' => $formView]);
        }
        
        return $response;
    }
    
    public function namesList($itemid,TipitakaCollectionsRepository $collectionsRepository)
    {
        $names=$collectionsRepository->listNames($itemid);     
        
        $item=$collectionsRepository->find($itemid);
        if($item->getParentid())
        {
            $collectionid=$item->getParentid();
        }
        else 
        {
            $collectionid=$itemid;
        }
        
        return $this->render('collection_item_names.html.twig', ['names'=>$names,'itemid'=>$itemid,'collectionid'=>$collectionid]);
    }
    
    public function nameEdit(Request $request,TipitakaSentencesRepository $sentencesRepository,
        TipitakaCollectionsRepository $collectionsRepository,TranslatorInterface $translator)
    {
        $itemid=$request->query->get('itemid');
        $itemnameid=$request->query->get('itemnameid');
        
        $choices=$sentencesRepository->listLanguages();
        
        $itemname=new TipitakaCollectionItemNames();
        
        if($itemnameid)
        {
            $itemname=$collectionsRepository->getItemName($itemnameid);
        }
        
        $languageOptions=['choices'  => $choices,
            'label' => false,
            'expanded'=>false,
            'multiple'=>false,
            'mapped'=>false
        ];
        
        if($itemid)
        {
            $languageOptions['placeholder']=$translator->trans('Choose an option');
        }
        
        $form = $this->createFormBuilder($itemname)
        ->add('name', TextType::class,['required' => true,'label' => false])
        ->add('language', ChoiceType::class,$languageOptions)
        ->add('save', SubmitType::class);
        
        if($itemnameid)
        {
            $form=$form->add('delete', SubmitType::class);
        }
        
        $form=$form->getForm();
        
        $form->handleRequest($request);
        
        if($itemid==NULL)
        {
            $itemid=$itemname->getCollectionitemid()->getCollectionitemid();
        }
        
        if ($form->isSubmitted() && $form->isValid())
        {
            if($form->get('save')->isClicked())
            {
                $languageid=$form->get("language")->getData();
                
                $language=$sentencesRepository->getLanguage($languageid);
                $itemname->setLanguageid($language);
                
                if($itemname->getCollectionitemid()==NULL)
                {
                    $item=$collectionsRepository->find($itemid);
                    $itemname->setCollectionitemid($item);
                }
                
                try
                {
                    $collectionsRepository->persistItemName($itemname);
                    
                    $response=$this->redirectToRoute('collection_item_names',['itemid'=>$itemid]);
                }
                catch(\Exception $ex)
                {               
                    $response=$this->render('collection_item_name_edit.html.twig',['itemnameid'=>$itemnameid,'itemid'=>$itemid,
                        'form'=>$form->createView(),'message'=>$translator->trans('Name in this language already exists')]);
                }
            }
            
            if($form->has("delete") && $form->get('delete')->isClicked())
            {
                $collectionsRepository->deleteCollectionItemName($itemname);
                $response=$this->redirectToRoute('collection_item_names',['itemid'=>$itemid]);
            }
        }
        else
        {
            if($itemnameid)
            {
                $form->get("language")->setData($itemname->getLanguageid()->getLanguageid());
            }
                        
            $response=$this->render('collection_item_name_edit.html.twig',['itemnameid'=>$itemnameid,'itemid'=>$itemid,
                'form'=>$form->createView(),'message'=>'']);
        }
        
        return $response;
    }
    
    private function formatPali($text,$long_oe,$hasformatting)
    {
        $formatted=$text;
        
        if(strpbrk($text, '<>')==FALSE)//!$hasformatting
        {//no tags
            $formatted=preg_replace('/([oe])(\'?)(kh|gh|ch|jh|th|ṭh|dh|ḍh|ph|bh)([aiuoeāīū])/si', '<b>$1</b>$2$3$4', $text);
            $formatted=preg_replace($long_oe, '<b>$1</b>$2', $formatted);
        }
        
        //double consonant with h
        $formatted=preg_replace('/(m|n|ṇ|ñ|ṅ|s|v|l|r|y|ṃ)(h)/si', '<u>$1$2</u>', $formatted);
        
        return $formatted;
    }
}

