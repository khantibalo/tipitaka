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
use App\Repository\NativeRepository;
use App\Repository\TipitakaCollectionsRepository;
use App\Repository\TipitakaParagraphsRepository;
use App\Repository\TipitakaSentencesRepository;
use App\Security\Roles;
use App\Entity\TipitakaCollectionItems;
use App\Repository\TipitakaTocRepository;
use App\Entity\TipitakaCollectionItemNames;
use App\Enums\Languages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

class CollectionController extends AbstractController
{
    public function list(Request $request,TipitakaCollectionsRepository $collectionsRepository,TranslatorInterface $translator,
        TipitakaSentencesRepository $sentencesRepository)
    {
        $collectionItems=array();
        
        $collections=$collectionsRepository->listCollections($request->getLocale());
        $response=$this->render('collections_list.html.twig', ['collections'=>$collections,'collectionItems'=>$collectionItems,
            'authorRole'=>Roles::Author, 'editorRole'=>Roles::Editor]);
        
        return $response;
    }
    
    
    public function viewCollection($collectionid,Request $request,TipitakaCollectionsRepository $collectionsRepository,
        TranslatorInterface $translator, TipitakaSentencesRepository $sentencesRepository)
    {                
        $collectionItems=array();
    
        $collectionItems=$collectionsRepository->listCollectionItems($collectionid,$request->getLocale());
        $collection=$collectionsRepository->fetchCollection($collectionid,$request->getLocale());

        $form = $this->createFormBuilder()
        ->add('shownav', CheckboxType::class,['required' => false,'label' => false,'data'=>true])
        ->add('rendermode', ChoiceType::class,
            ['choices'  => [
                'display print view' => 'disp',
                'download html' => 'html',
                'download fb2'=>'fb2',
                'download pdf'=>'pdf'],
                'label' => false,
                'expanded'=>true,
                'multiple'=>false,
                'data'=>'disp'
            ])
        ->add('layout', ChoiceType::class,
            ['choices'  => [
                'PrintViewTableDesc' => 'table',
                'PrintViewPaperDesc' => 'paper',
                'PrintViewTranslationDesc' => 'tran'],
                'label' => false,
                'expanded'=>true,
                'multiple'=>false,
                'data'=>'table'
            ])
        ->add('submit', SubmitType::class);
        
        $form=$form->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid())
        {
            $printviewtype=$form->get("layout")->getData();
            $rendermode=$form->get("rendermode")->getData();
            
            if($rendermode=="disp" || $rendermode=="html" || $rendermode=="pdf")
            {
                $templates=["table"=>"collection_print_table.html.twig",
                    "paper"=>"collection_print_paper.html.twig",                    
                    "tran"=>"collection_print_translation.html.twig"];
            }

            if($rendermode=="fb2")
            {
                $templates=["table"=>"collection_print_table_fb2.xml.twig",
                    "paper"=>"collection_print_paper_fb2.xml.twig",
                    "tran"=>"collection_print_translation_fb2.xml.twig"];
            }
            
            //all paragraphs that belong to nodes in this collection
            //all sentences that belong to these paragraphs
            //sentencetranslations for these sentences - we need to find which sourceids are needed
            $paragraphs=$collectionsRepository->listParagraphs($collectionid);                               
            $sentences=array();//$collectionsRepository->listSentences($itemid);
            $comments=$collectionsRepository->listCommentsByCollectionIdForPrint($collectionid);
 //           $language=$sentencesRepository->getLanguageByCode($request->getLocale());
            
            $paliTranslations=array();
            $otherTranslations=array();
            
            $long_oe=array();
            $long_oe[0]='/([oe])(.)(?=[aiuoeāīū<])/si';
            $long_oe[1]='/([oe])(ḷ)(?=[aiuoeāīū<])/si';
            $long_oe[2]='/([oe])(ḷh)(?=[aiuoeāīū<])/si';
            $long_oe[3]='/([oe])(ṇ)(?=[aiuoeāīū<])/si';
            $long_oe[4]='/([oe])([};,:\. \]\)\?])/si';
            $long_oe[5]='/([oe])$/si';
            $long_oe[6]='/([oe])(["\'].)(?=[aiuoeāīū<])/si';
            
            foreach($collectionItems as $collectionItem)
            {
                if($collectionItem['nodeid'])
                {
                    $paliSource=NULL;
                    
                    try 
                    {
                        $translSource=$sentencesRepository->getNodeSourceTopPriority($collectionItem['nodeid']);
                    } 
                    catch (\Exception $e) 
                    {                        
                        $response = new Response();
                        $response->setStatusCode(Response::HTTP_NOT_FOUND);
                        $response->setContent("node id=".$collectionItem['nodeid']." has no translations");
                        return $response;
                    }
                    
                    
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
            
            $params=['collection'=>$collection,
                'collectionItems'=>$collectionItems,'paragraphs'=>$paragraphs,'sentences'=>$sentences,
                'paliTranslations'=>$paliTranslations,'otherTranslations'=>$otherTranslations,
                'comments'=>$comments,'shownav'=>$form->get("shownav")->getData()
            ];
            
            switch($rendermode)
            {
                case "disp":
                    $response=$this->render($templates[$printviewtype], $params);
                    break;
                case "html":
                    $response=$this->render($templates[$printviewtype], $params);
                    
                    $disposition = HeaderUtils::makeDisposition(
                        HeaderUtils::DISPOSITION_ATTACHMENT,
                        $collection["name"].'.html',
                        'collection.html'
                        );
                    
                    $response->headers->set('Content-Disposition', $disposition);
                    break;
                case "fb2":
                    $response=$this->render($templates[$printviewtype], $params);
                    
                    $disposition = HeaderUtils::makeDisposition(
                        HeaderUtils::DISPOSITION_ATTACHMENT,
                        $collection["name"].'.fb2',
                        'collection.fb2'
                        );
                    
                    $response->headers->set('Content-Type', 'application/fictionbook2+zip');
                    $response->headers->set('Content-Disposition', $disposition);
                    break;
                case "pdf":
                    $params["shownav"]=0;
                                       
                    $html=$this->renderView($templates[$printviewtype], $params);
                    
                    //tried dompdf for this. it has a problem with bold text flow
                    //mpdf works flawlessly!
                    
                    $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
                    $fontDir = $defaultConfig['fontDir'];
                    $fontDir=array_merge($fontDir, [
                        $this->getParameter('kernel.project_dir') . '/fonts/',
                    ]);
                    
                    $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
                    $fontData = $defaultFontConfig['fontdata'];
                    $fontData=$fontData + [ // lowercase letters only in font key
                        'liberationsans' => [
                            'R' => 'LiberationSans-Regular.ttf',
                            'I' => 'LiberationSans-Italic.ttf',
                            'B' => 'LiberationSans-Bold.ttf',
                            'BI' => 'LiberationSans-BoldItalic.ttf',
                        ],
                        'liberationserif' => [
                            'R' => 'LiberationSerif-Regular.ttf',
                            'I' => 'LiberationSerif-Italic.ttf',
                            'B' => 'LiberationSerif-Bold.ttf',
                            'BI' => 'LiberationSerif-BoldItalic.ttf',
                        ] ];
                    
                    
                    $mpdf = new \Mpdf\Mpdf([
                        'fontDir' => $fontDir,
                        'fontdata' => $fontData,
                        'default_font' => 'libsans'
                    ]);   
                    
                    $mpdf->h2bookmarks = array('H1'=>0, 'H2'=>1, 'H3'=>2, 'H4'=>3, 'H5'=>4, 'H6'=>5);
                    $mpdf->h2toc = array('H1' =>0, 'H2' =>1, 'H3' =>2,'H4'=>3,'H5' => 4,'H6'=>5); 
                    
                    $mpdf->defaultfooterfontstyle="R";
                    $mpdf->setFooter('|{PAGENO}|');
                    $mpdf->WriteHTML($html);
                    
                    if($form->get("shownav")->getData())
                    {                        
                        $tocParams=['links'=>true, 
                            'toc-bookmarkText'=>$translator->trans("table of contents")];
                        $mpdf->TOCpagebreakByArray($tocParams);                        
                    }
                    
                    $pdfEncodedContent=$mpdf->OutputBinaryData();
                    
                    $response=new Response();
                    $response->setContent($pdfEncodedContent);
                    $disposition = HeaderUtils::makeDisposition(
                        HeaderUtils::DISPOSITION_ATTACHMENT,
                        $collection["name"].'.pdf',
                        'collection.pdf'
                        );
                    
                    $response->headers->set('Content-Disposition', $disposition);
		    $response->headers->set('Content-Type', 'application/pdf');
                    
                    //$response->setStatusCode(Response::HTTP_NOT_FOUND);
                    //$response->setContent("node id=".$collectionItem['nodeid']." has no translations");
                    break;                
            }            
        }
        else 
        {//view collection           
            $formView=$form->createView();
            
            $response=$this->render('collection_view.html.twig', ['collection'=>$collection,'collectionItems'=>$collectionItems,
                'authorRole'=>Roles::Author,'form' => $formView, 'editorRole'=>Roles::Editor, 'collectionid'=> $collectionid]);
        }
        
        return $response;
    }
    
    public function editCollection(Request $request, TipitakaCollectionsRepository $collectionsRepository,TranslatorInterface $translator,
        TipitakaSentencesRepository $sentencesRepository,TipitakaTocRepository $tipitakaTocRepository)
    {
        $itemid=$request->query->get('itemid');
                        
        $form = $this->createFormBuilder();
                
        if($itemid)
        {
            $form=$form->add('delete', SubmitType::class);
        }
        else
        {//add new
            $languages=$sentencesRepository->listLanguages();
            $langOptions=['choices'  => $languages,
                'label' => false,
                'expanded'=>false,
                'multiple'=>false,
                'required'=>true
            ];
            $langOptions['placeholder']=$translator->trans('Choose an option');
            
            $form=$form
            ->add('name', TextType::class,['required' => true])
            ->add('language', ChoiceType::class,$langOptions);
        }
        
        $form=$form
        ->add('notes', TextareaType::class,['required' => false,'label' => false])
        ->add('defaultview', ChoiceType::class,
            ['choices'  => [$translator->trans('coll_view_mode1')=>'1',
                $translator->trans('coll_view_mode2')=>'2'],
                'label' => false,
                'expanded'=>false,
                'multiple'=>false,
                'required' => true
            ])
        ->add('vieworder', IntegerType::class,['required' => true])
        ->add('save', SubmitType::class);
                
        $form=$form->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid())
        {
            if($form->get('save')->isClicked())
            {
                if(is_null($itemid))
                {
                    $item=new TipitakaCollectionItems();                    
                    $item->setAuthorid($this->getUser());
                }
                else
                {
                    $item=$collectionsRepository->find($itemid);
                }
                                
                $item->setVieworder($form->get("vieworder")->getData());
                $item->setNotes($form->get("notes")->getData());
                $item->setDefaultview($form->get("defaultview")->getData());
                
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
                        
            $response=$this->redirectToRoute('collections_list');
        }
        else
        {
            if($itemid!=NULL)
            {
                $item=$collectionsRepository->find($itemid);
                $form->get("vieworder")->setData($item->getVieworder());

                $form->get("notes")->setData($item->getNotes());
                $form->get("defaultview")->setData($item->getDefaultview());
            }
            
            $formView=$form->createView();
            $response=$this->render('collection_edit.html.twig',['itemid'=>$itemid, 'form' => $formView]);
        }
        
        return $response;
    }
        
    
    
    public function editFolder(Request $request, TipitakaCollectionsRepository $collectionsRepository,TranslatorInterface $translator,
        TipitakaSentencesRepository $sentencesRepository,TipitakaTocRepository $tipitakaTocRepository)
    {        
        $itemid=$request->query->get('itemid');
        $parentid=$request->query->get('parentid');
        
        //all params null = create collection
        //$parentid and $folder not null = create folder
        //$parentid not null = create item
        //$itemid is not null = edit item, folder or collection
        
        $form = $this->createFormBuilder();
        
        if($itemid)
        {//editing
            $form=$form->add('delete', SubmitType::class);
        }
        else
        {//add new
            
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
            
            $form=$form
            ->add('name', TextType::class,['required' => true])
            ->add('language', ChoiceType::class,$langOptions);            
        }
        
        $form=$form
        ->add('vieworder', IntegerType::class,['required' => true])
        ->add('level', IntegerType::class,['required' => true])
        ->add('save', SubmitType::class);        
        
        $form=$form->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid())
        {
            if($form->get('save')->isClicked())
            {
                if(is_null($itemid))
                {
                    $item=new TipitakaCollectionItems();
                    
                    $item->setParentid($parentid);
                    $item->setAuthorid($this->getUser());
                }
                else
                {
                    $item=$collectionsRepository->find($itemid);
                }
                                
                $item->setVieworder($form->get("vieworder")->getData());
                $item->setLevel($form->get("level")->getData());                
                
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
            }
            
            $formView=$form->createView();
            $response=$this->render('collection_folder_edit.html.twig',['itemid'=>$itemid,'form' => $formView]);
        }
        
        return $response;
    }
    
    
    
    public function editItem(Request $request, TipitakaCollectionsRepository $collectionsRepository,TranslatorInterface $translator,
        TipitakaSentencesRepository $sentencesRepository,TipitakaTocRepository $tipitakaTocRepository)
    {

        $itemid=$request->query->get('itemid');
        $parentid=$request->query->get('parentid');
        
        $form = $this->createFormBuilder();
        $form=$form
        ->add('nodeid', IntegerType::class,['required' => true])
        ->add('limitrows', TextareaType::class,['required' => false,'label' => false,'mapped'=>false])
        ->add('hidetitleprint', CheckboxType::class,['label' => false,'required' => false])
        ->add('hidepalinameprint', CheckboxType::class,['label' => false,'required' => false])
        ->add('vieworder', IntegerType::class,['required' => true])
        ->add('notes', TextareaType::class,['required' => false,'label' => false])
        ->add('notesBottom', TextareaType::class,['required' => false,'label' => false])
        ->add('level', IntegerType::class,['required' => true])
        ->add('save', SubmitType::class);
        
        if($itemid)
        {//editing
            $item=$collectionsRepository->find($itemid);
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
                

                $node=$tipitakaTocRepository->find($form->get("nodeid")->getData());
                $item->setNodeid($node);
                $item->setLimitrows($form->get("limitrows")->getData());
                $item->setHidetitleprint($form->get("hidetitleprint")->getData());
                $item->setHidepalinameprint($form->get("hidepalinameprint")->getData());
                $item->setVieworder($form->get("vieworder")->getData());
                $item->setLevel($form->get("level")->getData());       
                $item->setNotes($form->get("notes")->getData());
                $item->setNotesBottom($form->get("notesBottom")->getData());
                
                $collectionsRepository->updateCollectionItem($item);                
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
                $form->get("nodeid")->setData($item->getNodeid()->getNodeid());
                $form->get("limitrows")->setData($item->getLimitrows());
                $form->get("hidetitleprint")->setData($item->getHidetitleprint());
                $form->get("hidepalinameprint")->setData($item->getHidepalinameprint());     
                $form->get("notes")->setData($item->getNotes());
                $form->get("notesBottom")->setData($item->getNotesBottom());
            }
            
            $formView=$form->createView();
            $response=$this->render('collection_item_edit.html.twig',['itemid'=>$itemid,'parentid'=>$parentid,
                'form' => $formView]);
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
        $formatted=preg_replace('/(ā|ī|ū|Ā|Ī|Ū)/si', '<b>$1</b>', $formatted);        

        return $formatted;
    }
    
    public function viewCollectionItem($collectionitemid,TipitakaTocRepository $tocRepository,
        TipitakaParagraphsRepository $paragraphsRepository, NativeRepository $nativeRepository,
        TipitakaSentencesRepository $sentencesRepository,Request $request,
        TipitakaCollectionsRepository $collectionsRepository)
    {
        $collectionItem=$collectionsRepository->find($collectionitemid);

        if($collectionItem->getParentid() && $collectionItem->getNodeid())
        {
            $collection=$collectionsRepository->fetchCollection($collectionItem->getParentid(),$request->getLocale());
            $collectionItemName=NULL;
            $collectionItemNameResult=$collectionsRepository->getCollectionItemName($collectionitemid,$request->getLocale());
            if($collectionItemNameResult)
            {
                $collectionItemName=$collectionItemNameResult["name"];
            }            
            
            $nodeid=$collectionItem->getNodeid();
            $node=$tocRepository->getNodeWithNameTranslation($nodeid,$request->getLocale());
            $path_nodes=$tocRepository->listPathNodesWithNamesTranslation($nodeid,$request->getLocale());
                        
            if($collectionItem->getLimitrows() && trim($collectionItem->getLimitrows())!="")
            {
                $sentences=$collectionsRepository->listSentences($collectionItem->getNodeid(),$collectionItem->getLimitrows());
            }
            else
            {
                $sentences=$sentencesRepository->listByNodeId($collectionItem->getNodeid());
            }
            
            $coll_view_mode=$request->cookies->get('coll_view_mode'.$collection["collectionitemid"],
                $collection["defaultview"]);
            $form = $this->createFormBuilder(null,  array('csrf_protection' => false))
            ->add('update', SubmitType::class)
            ->getForm();
            
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid())
            {
                switch($coll_view_mode)
                {
                    case 1:
                        $coll_view_mode=2;
                        break;
                    case 2:
                        $coll_view_mode=1;
                        break;
                }
            }
                        
            $paragraphs=NULL;
            
            switch($coll_view_mode)
            {
                case 1:                
                    $translations=$sentencesRepository->listTranslationsByNodeId($nodeid);    
                    break;                
                case 2:
                    $translationsource=$sentencesRepository->getNodeSourceTopPriority($nodeid);
                    $translations=$sentencesRepository->listTranslationsBySourceId($nodeid,$node['path'],
                        $translationsource['sourceid']);
                    $nodeObj=$tocRepository->find($nodeid);
                    $paragraphs=$paragraphsRepository->listByNode($nodeObj);
                    break;
            }
            
            $sources=$sentencesRepository->listNodeSources($nodeid);
            
            $related=$tocRepository->listRelatedNodes($nodeid,$request->getLocale());
            
            $coll_backnext=$collectionsRepository->getBackNextCollectionItem($collectionitemid);
            $coll_back_id=$coll_backnext["back_id"];
            $coll_next_id=$coll_backnext["next_id"];
            
            $chapter_name="";
            $chapterObj=$collectionsRepository->getChapterName($collectionitemid,$request->getLocale());
            if($chapterObj)
            {
                $chapter_name=$chapterObj["name"];
            }
            
            $response=$this->render('collection_item_view.html.twig', ['node'=>$node,'path_nodes'=>$path_nodes,
                'sentences'=>$sentences,'translations'=>$translations, 'authorRole'=>Roles::Author, 'userRole'=>Roles::User,
                'editorRole'=>Roles::Editor, 'sources'=>$sources,'collection'=>$collection,
                'coll_back_id'=>$coll_back_id,'coll_next_id'=>$coll_next_id,'collectionItem'=>$collectionItem,
                'showCode'=>false,'showAlign'=>false,'collectionItemName'=>$collectionItemName,
                'paragraphs'=>$paragraphs,'related'=>$related,'coll_view_mode' => $coll_view_mode,
                'form' => $form->createView(), 'chapter_name'=>$chapter_name
            ]);   
            $response->headers->setCookie(new Cookie('coll_view_mode'.$collection["collectionitemid"],
                $coll_view_mode,time() + (3600 * 24*365)));
        }
        else
        {
            $response=new Response('not found',404);
        }
        
        return $response;
    }
    
    public function collectionRedirect(Request $request)
    {
        return $this->redirectToRoute('collection_view',['collectionid'=>$request->query->get("itemid")],301);
    }
    
    public function viewCollectionMobile($collectionid,Request $request)
    {   
        $response=$this->redirectToRoute('collection_view',['collectionid'=>$collectionid],301);
        $response->headers->setCookie(new Cookie('mobile','1',time() + (3600 * 24*365)));
        return $response;
    }
}

