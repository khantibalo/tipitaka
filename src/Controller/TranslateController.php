<?php
namespace App\Controller;

use App\Repository\NativeRepository;
use App\Repository\TipitakaParagraphsRepository;
use App\Repository\TipitakaSentencesRepository;
use App\Repository\TipitakaSourcesRepository;
use App\Repository\TipitakaTocRepository;
use App\Repository\UserRepository;
use App\Security\Roles;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use App\Twig\CapitalizeExtension;
use App\Entity\TipitakaSentenceTranslations;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\TipitakaSentences;

class TranslateController extends AbstractController
{
    private $headers=['Content-Type'=>'application/json; charset=UTF-8'];
    
    public function paragraphSplit($id, TipitakaSentencesRepository $sentenceRepository,TipitakaParagraphsRepository $paragraphRepository)
    {
        $this->paragraphSplitInternal($id,$sentenceRepository,$paragraphRepository);
                
        return $this->redirectToRoute('view_paragraph', ["id"=>$id]);
    }
    
    private function paragraphSplitInternal($id, TipitakaSentencesRepository $sentenceRepository,TipitakaParagraphsRepository $paragraphRepository)
    {
        $tc=$sentenceRepository->countSentences($id);
        
        if($tc["tc"]==0)
        {
            $paragraph=$paragraphRepository->find($id);
            $notes=$paragraphRepository->listNotesByParagraph($id);   
            
            $ce=new CapitalizeExtension();
            $text=$ce->capitalize($paragraph->getText(),$paragraph->getCaps());

            $text=$this->applyNotes($text, $notes);
            
            if(!empty($paragraph->getParanum()))
            {
                $text=$paragraph->getParanum().".".$text;
            }
            
            $ar_sentences =preg_split('/(?<=[.?!])\s+(?=[A-ZĀĪŪṬÑṂṆṄḶḌ"\'])/u', $text);
            
            $sentenceRepository->addSentences($paragraph, $ar_sentences);                       
        }
    }
    
    private function applyNotes($text,$notes)
    {
        //copy all formatting objects into one array indexed by position
        $markup=array();
                
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
                    if(array_key_exists('notetext',$markup_item))
                    {
                        $line.="[".$markup_item['notetext']."] ";
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
    
    //TODO: split this function to make is easier to read
    //1. add new source
    //2. edit existing translation
    //3. add new translation to an existing source
    public function translationEdit(Request $request,TipitakaSentencesRepository $sentenceRepository,
        UserRepository $userRepository, TranslatorInterface $translator)
    {        
        $sentenceid=$request->query->get('sentenceid');
        $translationid=$request->query->get('translationid');
        $sourceid=$request->query->get('sourceid');
        $return=$request->query->get('return');
        $showAlign=$request->query->get('showAlign');
        $returnNodeid=$request->query->get('nodeid');
        
        $paragraphid=0;
        $sentenceText='';
        $sourceObj=null;
        
        if($translationid)
        {//editing existing translation
            $translation=$sentenceRepository->getTranslation($translationid);
            $sentenceText=$translation->getSentenceId()->getSentenceText();
            $paragraphid=$translation->getSentenceId()->getParagraphid()->getParagraphid();
            $sourceid=$translation->getSourceId();
            
            $sources=$sentenceRepository->listAllSources();
            
            $sourceOptions=['choices'  => $sources,
                'label' => false,
                'expanded'=>false,
                'multiple'=>false,
                'required' => true,
                'mapped' => false
            ];  
            
            if(!$this->isGranted(Roles::Editor))
            {//if you are not an editor, you can edit translation only if you are its author or is assigned to its source
                $author=$translation->getUserid();
                $currentUser=$this->getUser();
                if($author->getUserid()!=$currentUser->getUserid() && $sourceid->getUserid()->getUserid()!=$currentUser->getUserid())
                {
                    $this->denyAccessUnlessGranted(Roles::Editor);
                }
            }
        }
        
        if($sentenceid)
        {//adding new translation
            $translation=new TipitakaSentenceTranslations();
            $sentence=$sentenceRepository->find($sentenceid);
            $sentenceText=$sentence->getSentenceText();
            $paragraphid=$sentence->getParagraphid()->getParagraphid();
            
            if($sourceid)
            {
                $sources=$sentenceRepository->listAllSources();
            }
            else 
            {
                $sources=$sentenceRepository->listSources($sentenceid);
            }
            
            $sourceOptions=['choices'  => $sources,
                'label' => false,
                'expanded'=>false,
                'multiple'=>false,
                'required' => true,
                'mapped' => false
            ];            
        }
        
        $users=$userRepository->findAllAssoc();
        
        $authorOptions=['choices'  => $users,
            'label' => false,
            'expanded'=>false,
            'multiple'=>false,
            'required' => true,
            'mapped' => false
        ];  
                
        $userSource=$sentenceRepository->findSourceByUserId($this->getUser()->getUserid());
        
        if(!$userSource && !$translationid)
        {
            $sourceOptions['placeholder']=$translator->trans('Choose an option');
        }
        
        $fb = $this->createFormBuilder($translation);
        
        if(is_null($sourceid))
        {
            $fb=$fb->add('source', ChoiceType::class,$sourceOptions);
        }
        else 
        {
            $sourceObj=$sentenceRepository->getSource($sourceid);
            $fb=$fb->add('source', ChoiceType::class,$sourceOptions);
        }
        
        if($this->isGranted(Roles::Editor))
        {
            $fb=$fb->add('author', ChoiceType::class,$authorOptions);
        }
        
        $fb=$fb->add('translation', TextareaType::class,['required' => false,'label' => false,'mapped'=>false])
        //->add('parenttranslationid', IntegerType::class,['required' => false,'label' => false])        
        ->add('save', SubmitType::class)
        ->add('saveAndNext', SubmitType::class);
        $form=$fb->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid())
        {
            $translation = $form->getData();
                                    
            $source=$sentenceRepository->getSource($form->get("source")->getData());
            
            if(!$this->isGranted(Roles::Editor))
            {//if you are not an editor, you can add this translation only to your source
                $user=$source->getUserid();
                $currentUser=$this->getUser();
                if($user->getUserid()!=$currentUser->getUserid())
                {
                    $this->denyAccessUnlessGranted(Roles::Editor);
                }
            }
            
            $translation->setSourceid($source);
            if($this->isGranted(Roles::Editor))
            {
                $author=$userRepository->find($form->get("author")->getData());  
            }
            else 
            {
                $author=$this->getUser();
            }
            
            $translation->setUserid($author);
            
            //if($translationid==NULL)
            //{//set author only if we add a new translation
            //    $translation->setUserid($this->getUser());
            //}
            
            $translation->setDateupdated(new \DateTime());
            
            $translation->setTranslation($form->get("translation")->getData() ?? '');
            
            if($sentenceid)
            {                
                $translation->setSentenceid($sentence);
            }
                                             
            try 
            {                
                $sentenceRepository->persistTranslation($translation);
                
                if($return)
                {                
                    $sentenceid=$translation->getSentenceid()->getSentenceid();
                    
                    if($returnNodeid)
                    {
                        $params=["id"=>$returnNodeid];
                    }
                    else 
                    {
                        $node=$sentenceRepository->getNodeIdBySentenceId($sentenceid);
                        
                        $params=["id"=>$node['nodeid']];
                    }
                    
                    if($showAlign)
                    {
                        $params['showAlign']=$showAlign;
                    }
                    
                    $params['_fragment']="sent$sentenceid";
                    $route='table_view';                
                }
                else 
                {
                    $params=["id"=>$paragraphid];
                    $route='view_paragraph';                  
                }
                
                if($form->get('saveAndNext')->isClicked())
                {
                    $params=array();
                    
                    if($return)
                    {
                        $params['return']=$return;
                    }
                    
                    if($returnNodeid)
                    {
                        $params=["nodeid"=>$returnNodeid];
                    }
                    
                    if($showAlign)
                    {
                        $params['showAlign']=$showAlign;
                    }
                    
                    $sentenceid=$translation->getSentenceid()->getSentenceid();
                    //find the next sentence of the original text
                    $nextSentenceId=$sentenceRepository->getNextSentenceid($sentenceid,!is_null($request->query->get('return')));
                    if($nextSentenceId)
                    {
                        $nextTranslation=$sentenceRepository->getTranslationBySentenceSource($nextSentenceId['sentenceid'],
                            $translation->getSourceid()->getSourceid());
                        if($nextTranslation)
                        {//next sentence already has a translation, we are going to edit it
                            $params['translationid']=$nextTranslation['sentencetranslationid'];
                        }
                        else 
                        {//next sentence doesn't have a translation
                            $params['sentenceid']=$nextSentenceId['sentenceid'];
                            $params['sourceid']=$translation->getSourceid()->getSourceid();
                        }
                    }
                    else 
                    {   //next sentence not found, we are going to edit the same sentence
                        $params['translationid']=$translation->getSentencetranslationid();
                    }                
                    
                    $route='translation_edit';
                }
                
                $response=$this->redirectToRoute($route,$params);
            }
            catch(\Exception $ex)
            {
                $cancelUrl=$this->getCancelUrl($sentenceid,$translationid,$returnNodeid,$showAlign,
                    $paragraphid,$return,$translation,$sentenceRepository);
                
                $response=$this->render('translation_edit.html.twig', ['form' => $form->createView(),
                    'sentenceText'=>$sentenceText,'sourceObj'=>$sourceObj,'cancelUrl'=>$cancelUrl,
                    'message'=>$translator->trans('Translation to this language already exists'),
                    'editorRole'=>Roles::Editor
                ]);
            }
        }
        else
        {
            if($translationid && is_null($sourceid))
            {
                $form->get("source")->setData($translation->getSourceid()->getSourceid());
            }
            else
            {
                if($userSource && is_null($sourceid))
                {
                    $form->get("source")->setData($userSource["sourceid"]);                    
                }
            }
            
            if($translation)
            {
                $form->get("translation")->setData($translation->getTranslation());               
                
                if($translation->getSourceid()!=NULL)
                {
                    $form->get("source")->setData($translation->getSourceid()->getSourceid());
                }
                
                if($this->isGranted(Roles::Editor))
                {
                    if($translation->getUserid()==NULL)
                    {
                        $form->get("author")->setData($this->getUser()->getUserid());
                    }
                    else 
                    {
                        $form->get("author")->setData($translation->getUserid()->getUserid());
                    }      
                }
            }
            
            if($request->query->get('sourceid'))
            {
                $form->get("source")->setData($sourceid);
            }
                    
            $cancelUrl=$this->getCancelUrl($sentenceid,$translationid,$returnNodeid,$showAlign,
                $paragraphid,$return,$translation,$sentenceRepository);
            
            $response=$this->render('translation_edit.html.twig', ['form' => $form->createView(),
                'sentenceText'=>$sentenceText,'sourceObj'=>$sourceObj,'cancelUrl'=>$cancelUrl,
                'message'=>'','editorRole'=>Roles::Editor
            ]);
        }
        
        return $response;
    }     
    
    private function getCancelUrl($sentenceid,$translationid,$returnNodeid,$showAlign,
        $paragraphid,$return,$translation,TipitakaSentencesRepository $sentenceRepository)
    {
        //calculate cancel link Url
        if($return)
        {//we came here from the node page
            $params=array();
            
            if($sentenceid)
            {
                $node=$sentenceRepository->getNodeIdBySentenceId($sentenceid);
            }
            
            if($translationid)
            {
                $node=$sentenceRepository->getNodeIdByTranslationId($translationid);
                $sentenceid=$translation->getSentenceid()->getSentenceid();
            }
            
            if($returnNodeid)
                $params=["id"=>$returnNodeid];
                else
                {
                    $params=["id"=>$node['nodeid']];
                }
                
                if($showAlign)
                {
                    $params['showAlign']=$showAlign;
                }
                
                $params['_fragment']="sent$sentenceid";
                
                $cancelUrl=$this->generateUrl('table_view',$params);
        }
        else
        {//we came here from the paragraph page
            $cancelUrl=$this->generateUrl('view_paragraph',['id'=>$paragraphid]);
        }
        
        return $cancelUrl;
    }
    
    public function nodeSplit($nodeid,TipitakaSentencesRepository $sentenceRepository,TipitakaParagraphsRepository $paragraphRepository,
        TipitakaTocRepository $tocRepository)
    {
        $node=$tocRepository->find($nodeid);
        
        $params=array();
        
        if($node)
        {
            //this is available only for those nodes that have paragraphs
            $arr_paraid=$paragraphRepository->listImmediateByNodeId($nodeid);
            
            foreach($arr_paraid as $paraid)
            {
                $this->paragraphSplitInternal($paraid['paragraphid'],$sentenceRepository,$paragraphRepository);
            }
            
            if(!$node->getHaschildnodes())
            {
                $tocRepository->enableTableView($nodeid);
            }
            
            if(sizeof($arr_paraid)>0)
            {                
                $params=["id"=>$nodeid];

            }
            else 
            {
                $params=["id"=>$nodeid];
            }                        
        }
        else 
        {
            throw $this->createNotFoundException("not found");
        }
        
        return $this->redirectToRoute('table_view',$params);
    }
    
    public function translationImport($sourceid,$nodeid,TipitakaSentencesRepository $sentenceRepository,
        Request $request, UserRepository $userRepository,TipitakaSourcesRepository $sourcesRepository)
    {        
        if(!$this->isGranted(Roles::Editor))
        {//if you are not an editor, you can import only if source are assigned to
            $source=$sourcesRepository->find($sourceid);
            $user=$source->getUserid();
            $currentUser=$this->getUser();
            if($user && $user->getUserid()!=$currentUser->getUserid())
            {
                $this->denyAccessUnlessGranted(Roles::Editor);
            }
        }
                
        $users=$userRepository->findAllAssoc();
        
        $authorOptions=['choices'  => $users,
            'label' => false,
            'expanded'=>false,
            'multiple'=>false,
            'required' => true,
            'mapped' => false
        ];  
        
        $importFileOptions=['mapped' => false,
            'required' => false,
            'label'=>'File (plain text utf8 only):',
            'constraints' => [
                new File([
                    'maxSize' => '1024k',
                    'mimeTypes' => [
                        'text/plain',
                    ],
                    'mimeTypesMessage' => 'Please upload a text file',
                ])
            ]];
        
        $importTextOptions=
            ['required' => false,
                'label' => 'or text',
                'mapped'=>false
            ];
        
        $form = $this->createFormBuilder()
        ->add('importFile', FileType::class, $importFileOptions)
        ->add('importText', TextareaType::class,$importTextOptions)
        ->add('paliskip', IntegerType::class,['required' => true,'label' => false])
        ->add('splitlinebreaksonly', CheckboxType::class,['required' => false])
        ->add('save', SubmitType::class,['label' => 'save']);
        
        if($this->isGranted(Roles::Editor))
        {
            $form=$form->add('author', ChoiceType::class,$authorOptions);
        }
        
        $form=$form->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid())
        {
            // @var UploadedFile importFile
            $importFile = $form->get('importFile')->getData();
            
            if($this->isGranted(Roles::Editor))
            {
                $author=$userRepository->find($form->get("author")->getData());
            }
            else
            {
                $author=$this->getUser();
            }
            
            $paliskip=$form->get("paliskip")->getData();
            $splitlinebreaksonly=$form->get("splitlinebreaksonly")->getData();
            
            if($importFile)//$importFile->guessExtension()=='txt'
            {
                $fileContents=file_get_contents($importFile->getPathname());
                
                $translations=$this->parseText($fileContents,$splitlinebreaksonly);
                
                $sentenceRepository->importTranslations($translations,$sourceid,$nodeid,$author,$paliskip);
            }    
            else 
            {
                $importText = $form->get('importText')->getData();
                if(!empty($importText))
                {
                    $translations=$this->parseText($importText,$splitlinebreaksonly);
                    
                    $sentenceRepository->importTranslations($translations,$sourceid,$nodeid,$author,$paliskip);
                }
            }
                        
            $response=$this->redirectToRoute('table_view', ["id"=>$nodeid,'showAlign'=>'yes']);
        }
        else
        {
            if($this->isGranted(Roles::Editor))
            {
                $form->get("author")->setData($this->getUser()->getUserid());
            }
            
            $form->get("paliskip")->setData(0);
            $response=$this->render('translation_import.html.twig', ['form' => $form->createView(),'nodeid'=>$nodeid]);
        }
        
        return $response;
    }
    
    private function parseText($fileContents,$splitlinebreaksonly)
    {
        if($splitlinebreaksonly)
        {           
            $translations=array();
            
            $parts=preg_split("/(\r\n|\n|\r)/iu",$fileContents,-1,PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            if($parts)
            {
                for($i=0;$i<sizeof($parts);$i++)
                {
                    $translation=$parts[$i];
                    
                    if(!empty(trim($translation)))
                    {
                         $translations[]=trim($translation);                       
                    }
                }
            }
        }
        else
        {
            $fileContents=preg_replace("/(etc|Mr|Ms|Ven|\d)\./mi","$1\u{0000}",$fileContents);
            $fileContents=str_ireplace("...","\u{0000}\u{0000}\u{0000}",$fileContents);
            
            $translations=array();
                    
            $parts=preg_split("/(ʘ|\?“|!“|\.“|\.”|\?”|\?»|!»|\.»|\.’|\?’|!’|\.'\"|\.\"|\.'|\.\s+|\?|!|\r\n|\n|\r)/iu",$fileContents,-1,PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            if($parts)
            {
                for($i=0;$i<sizeof($parts);$i++)
                {
                    $translation=$parts[$i];
                    
                    if(!empty(trim($translation)))
                    {
                        if(preg_match("/^(ʘ|\?“|!“|\.“|\.”|\?”|\?»|!»|\.»|\.’|\?’|!’|\.'\"|\.\"|\.'|\.|\?|!)\s*/iu",$translation))
                        {
                            $translations[sizeof($translations)-1]=$translations[sizeof($translations)-1].trim($translation);
                        }
                        else
                        {
                            $translations[]=trim(str_ireplace("\u{0000}",".",$translation));
                        }
                    }
                }
            }
        }
        
        return $translations;
    }
    
    public function join($sentenceid,TipitakaSentencesRepository $sentenceRepository)
    {
	   $node=$sentenceRepository->getNodeIdBySentenceId($sentenceid);

        $sentenceRepository->join($sentenceid,$this->getUser());
        
        return $this->redirectToRoute('table_view', 
            ["id"=>$node['nodeid'],'showAlign'=>'yes','_fragment' => "sent$sentenceid"]);
    }
    
    public function shiftDown($translationid,TipitakaSentencesRepository $sentenceRepository)
    {        
        $node=$sentenceRepository->getNodeIdByTranslationId($translationid);
        $translation=$sentenceRepository->getTranslation($translationid);
        $sentence=$translation->getSentenceid();
        $sentenceid=$sentence->getSentenceid();
        $source=$translation->getSourceid();
        $author=$translation->getUserid();
        
        if($this->isGranted(Roles::Author))
        {            
            $currentUser=$this->getUser();
            if($author->getUserid()!=$currentUser->getUserid())
            {
                $this->denyAccessUnlessGranted(Roles::Editor);
            }
        }
        
	    $sentenceRepository->translationShiftDown($translationid);    
	    
	    if(!$this->isGranted(Roles::Editor))
	    {
	        $translation=new TipitakaSentenceTranslations();
	        $translation->setSourceid($source);
	        $translation->setUserid($author);
	        $translation->setDateupdated(new \DateTime());
	        $translation->setSentenceid($sentence);
	        $translation->setTranslation('');    
	        $sentenceRepository->persistTranslation($translation);
	    }
	    
	    return $this->redirectToRoute('table_view', ["id"=>$node['nodeid'],'showAlign'=>'yes','_fragment' => "sent$sentenceid"]);        
    }
    
    public function shiftUp($translationid,TipitakaSentencesRepository $sentenceRepository)
    {
	    $node=$sentenceRepository->getNodeIdByTranslationId($translationid);
	    $translation=$sentenceRepository->getTranslation($translationid);
	    $sentenceid=$translation->getSentenceid()->getSentenceid();
	    
	    if($this->isGranted(Roles::Author))
	    {
	        $author=$translation->getUserid();
	        $currentUser=$this->getUser();
	        if($author->getUserid()!=$currentUser->getUserid())
	        {
	            $this->denyAccessUnlessGranted(Roles::Editor);
	        }
	    }
	    
        $sentenceRepository->translationShiftUp($translationid);        
        
        return $this->redirectToRoute('table_view', ["id"=>$node['nodeid'],'showAlign'=>'yes','_fragment' => "sent".($sentenceid-1)]);  
    }
    
    public function translationsFeed(NativeRepository $nativeRepository)
    {
        $title="Tipitaka latest translations";
        $link=$this->generateUrl('translations_feed',[],UrlGeneratorInterface::ABSOLUTE_URL);
        $description='20 latest translations';
        $langcode='RU';
        $items=$nativeRepository->listLastUpdTranslationFeed(20);
        
        for($i=0;$i<sizeof($items);$i++)
        {
            $items[$i]['link']=$this->generateUrl('view_node',['id'=>$items[$i]['nodeid']],UrlGeneratorInterface::ABSOLUTE_URL);
        }
        
        $response=$this->render('rss_feed.html.twig',['title'=>$title,'link'=>$link,'description'=>$description,
            'langcode'=>$langcode,'items'=>$items,'titleFormat'=>'%s'
        ]);
        
        $response->headers->set('Content-Type', 'application/rss+xml');
        
        return $response;
    }
        
    public function ajaxTranslationUpdate(Request $request,TipitakaSentencesRepository $sentenceRepository)
    {
        $response=new Response("OK");
        
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $translation=$sentenceRepository->getTranslation($data['stid']);
            $translation->setTranslation($data['translation']);
            //don't change author
            //$translation->setUserid($this->getUser());            
            $translation->setDateupdated(new \DateTime());
            
            if(!$this->isGranted(Roles::Editor))
            {
                $author=$translation->getUserid();
                $currentUser=$this->getUser();
                $source=$translation->getSourceid();
                if($author->getUserid()!=$currentUser->getUserid() && $source->getUserid()->getUserid()!=$currentUser->getUserid())
                {
                    $this->denyAccessUnlessGranted(Roles::Editor);
                }
            }
            
            $sentenceRepository->persistTranslation($translation);
            
            $response=$this->json([
                'sentenceid'=>$translation->getSentenceid()->getSentenceid(),
                'sourceid'=>$translation->getSourceid()->getSourceid(),
                'dateUpdated'=>$translation->getDateupdated()->format('Y-m-d H:i:s')
            ],200,$this->headers);
        }
        
        return $response;
    }
    
    public function ajaxTranslationAdd(Request $request,TipitakaSentencesRepository $sentenceRepository)
    {
        $response=new Response("OK");
        
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {

            $data = json_decode($request->getContent(), true);
            $translation=new TipitakaSentenceTranslations();
            
            $source=$sentenceRepository->getSource($data['sourceid']);            
            $translation->setSourceid($source);
            
            $sentence=$sentenceRepository->find($data['sentenceid']);
            $translation->setSentenceid($sentence);
            
            $translation->setTranslation($data['translation']);
            $translation->setUserid($this->getUser());
            $translation->setDateupdated(new \DateTime());
            
            if(!$this->isGranted(Roles::Editor))
            {
                $user=$source->getUserid();
                $currentUser=$this->getUser();
                if($user->getUserid()!=$currentUser->getUserid())
                {
                    $this->denyAccessUnlessGranted(Roles::Editor);
                }
            }
            
            $sentenceRepository->persistTranslation($translation);
            
            $response=$this->json([
                'sentencetranslationid'=>$translation->getSentencetranslationid(),
                'dateUpdated'=>$translation->getDateupdated()->format('Y-m-d H:i:s')
            ],200,$this->headers);
        }
        
        return $response;
    }
    
    public function sentenceEdit($sentenceid,Request $request,TipitakaSentencesRepository $sentenceRepository)
    {     
        $sentence=$sentenceRepository->find($sentenceid);
        
        $form = $this->createFormBuilder()
        ->add('sentencetext', TextareaType::class,['required' => false,'label' => false])
        ->add('save', SubmitType::class)
        ->getForm();
            
        $form->handleRequest($request);
        
        $node=$sentenceRepository->getSentenceNodeId($sentenceid);
        
        if ($form->isSubmitted() && $form->isValid())
        {
            $sentence->setSentencetext($form->get("sentencetext")->getData() ?? "");
            $sentenceRepository->updateSentence($sentence);
            
            $response=$this->redirectToRoute('table_view', ["id"=>$node['nodeid'],
                'showAlign'=>'yes','_fragment' => "sent$sentenceid"
            ]);
        }
        else
        {
            $form->get("sentencetext")->setData($sentence->getSentencetext());
            
            $response=$this->render('sentence_edit.html.twig', ['form' => $form->createView(),
                'cancelUrl'=>$this->generateUrl('table_view',['id'=>$node['nodeid']])
            ]);
        }
        
        return $response;
        
    }
    
    public function sentenceShiftDown($sentenceid,Request $request,TipitakaSentencesRepository $sentenceRepository)
    {
        //TODO: this code should be moved to repository class
        //find paragraph
        $sentence=$sentenceRepository->find($sentenceid);
        $paragraph=$sentence->getParagraphid();
        $node=$paragraph->getNodeid();
        //add new sentence
        $blankSentence=new TipitakaSentences();
        $blankSentence->setParagraphid($paragraph);
        $blankSentence->setSentencetext("");
        $blankSentence->setCommentcount(0);
        $sentenceRepository->updateSentence($blankSentence);
        //shift all sentences in this paragraph 1 position above
        $sentences=$sentenceRepository->listSentenceObjByParagraphid($sentenceid,$paragraph->getParagraphid());
        
        for($i=sizeof($sentences)-1;$i>0;$i--)
        {
            $sentences[$i]->setSentencetext($sentences[$i-1]->getSentencetext());
            $sentenceRepository->updateSentence($sentences[$i]);
        }
        //make first sentence empty
        $sentences[0]->setSentencetext("");
        $sentenceRepository->updateSentence($sentences[0]);
        
        return $this->redirectToRoute('table_view',
            ["id"=>$node->getNodeid(),'showAlign'=>'yes','_fragment' => "sent$sentenceid"]);
    }
    
    public function cleanEmptyRows($nodeid,TipitakaSentencesRepository $sentenceRepository,
        Request $request)
    {
        $sentenceRepository->cleanEmptyRows($nodeid);
        
        return $this->redirect($request->headers->get('referer','/'));
    }
}

