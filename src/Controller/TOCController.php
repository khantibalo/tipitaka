<?php
namespace App\Controller;


use App\Repository\TipitakaSentencesRepository;
use App\Repository\TipitakaTocRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\TipitakaParagraphsRepository;
use App\Security\Roles;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Entity\TipitakaNodeNames;
use App\Enums\TagTypes;
use App\Repository\TipitakaSourcesRepository;
use App\Repository\TipitakaTagsRepository;
use Symfony\Component\HttpFoundation\Response;

class TOCController extends AbstractController
{
    public function fullToc(TipitakaTocRepository $repository)
    {
        $nodes=$repository->findBy(['parentid'=>NULL]);
        
        
        return $this->render('full_toc.html.twig',
            ['child_nodes'=>$nodes,'showForm'=>false,'authorRole'=>Roles::Author,'expandRoute'=>'full_toc_node']
            );
    }
           
    public function fullTocListById($id,TipitakaTocRepository $tocRepository,Request $request, 
        TipitakaParagraphsRepository $paragraphsRepository,TranslatorInterface $translator)
    {
        $child_nodes=$tocRepository->findBy(['parentid'=>$id]);
        
        $path_nodes=$tocRepository->listPathNodes($id);

        $node=$path_nodes[sizeof($path_nodes)-1];

        $ptsFormView=NULL;
        $pageRangeMin=NULL;
        $pageRangeMax=NULL;
        $response=NULL;
        $paraFormView=NULL;
        $paraFormMessage=NULL;
        
        if(is_numeric($node->getMinVolumeNumber()) && is_numeric($node->getMaxVolumeNumber()) &&
            is_numeric($node->getMinPageNumber()) && is_numeric($node->getMaxPageNumber()))
        {
            $choices=array();
            
            for($i=$node->getMinVolumeNumber();$i<=$node->getMaxVolumeNumber();$i++)
            {
                $choices["$i"]=$i;
            }
            
            if($node->getMinVolumeNumber()==$node->getMaxVolumeNumber())
            {
                $pageRangeMin=$node->getMinPageNumber();
                $pageRangeMax=$node->getMaxPageNumber();                   
            }
            
            $formPTS = $this->createFormBuilder()
            ->add('volume', ChoiceType::class,
                ['choices'  => $choices,
                    'label' => false,
                    'expanded'=>false,
                    'multiple'=>false
                ])
            ->add('page', IntegerType::class,['required' => true,'label' => false])
            ->add('search', SubmitType::class,['label' => $translator->trans('GoButton')])
            ->getForm();
            
            $formPTS->handleRequest($request);
            
            $ptsFormView=$formPTS->createView();
            
            $formPara = $this->createFormBuilder()
            ->add('paranum', IntegerType::class,['required' => true,'label' => false])
            ->add('search', SubmitType::class,['label' => $translator->trans('GoButton')])
            ->getForm();
            
            $formPara->handleRequest($request);
            
            if ($formPTS->isSubmitted() && $formPTS->isValid())
            {
                $data = $formPTS->getData();
                
                $id=$tocRepository->getPTSParagraphId($node->getPath(),$data['volume'],$data['page']);
                
                if($id)
                {                                       
                    $response=$this->redirectToRoute('view_paragraph', ['id'=>$id['paragraphid']]);
                }
            }
            else
            {
                if (!$formPara->isSubmitted())
                {
                    $formPTS->get("volume")->setData($choices[$node->getMinVolumeNumber()]);
                }
            }
                        
            if ($formPara->isSubmitted() && $formPara->isValid())
            {
                $data = $formPara->getData();
                
                $paragraphsList=$paragraphsRepository->findParagraphByParanum($node->getPath(),$data['paranum']);
                
                if(sizeof($paragraphsList)==0)
                {
                    $paraFormMessage=$translator->trans('not found');
                }
                
                if(sizeof($paragraphsList)>1)
                {
                    $paraFormMessage=$translator->trans('more than one paragraph with this number');
                }
                
                if(sizeof($paragraphsList)==1)
                {
                    $response=$this->redirectToRoute('view_paragraph', ['id'=>$paragraphsList[0]['paragraphid']]);
                }
            }
            
            $paraFormView=$formPara->createView();
        }
        
        if(!$response)
        {
            $response=$this->render('full_toc.html.twig',
                ['child_nodes'=>$child_nodes,'path_nodes'=>$path_nodes,'ptsForm' => $ptsFormView,
                    'pageRangeMin'=>$pageRangeMin,'pageRangeMax'=>$pageRangeMax,'showForm'=>($ptsFormView!=NULL),
                    'paraForm'=>$paraFormView,'paraFormMessage'=>$paraFormMessage,'editorRole'=>Roles::Editor, 'expandRoute'=>'full_toc_node'
                    ,'thisNode'=>$node]
                );
        }
        
        return $response;
    }    
    
    public function listNodeNames($nodeid,TipitakaTocRepository $tocRepository,Request $request)
    {
        $nodeNames=$tocRepository->listNodeNames($nodeid);
        
        $node=$tocRepository->getNode($nodeid);            
        
        $response=$this->render('node_names.html.twig',
            ['names'=>$nodeNames,'node'=>$node]);
        
        return $response;
    }
    
    public function editNodeName(Request $request,TipitakaSentencesRepository $sentencesRepository, 
        TipitakaTocRepository $tocRepository,TranslatorInterface $translator)
    {
        $nodeid=$request->query->get('nodeid');
        $nodenameid=$request->query->get('nodenameid');
        
        $choices=$sentencesRepository->listLanguages();
        
        $nodename=new TipitakaNodeNames();
        
        if($nodenameid)
        {
            $nodename=$tocRepository->getNodeName($nodenameid);
        }
        
        $languageOptions=['choices'  => $choices,
            'label' => false,
            'expanded'=>false,
            'multiple'=>false,
            'mapped'=>false
        ];
        
        if($nodeid)
        {
            $languageOptions['placeholder']=$translator->trans('Choose an option');
        }
        
        $form = $this->createFormBuilder($nodename)
        ->add('name', TextType::class,['required' => true,'label' => false])
        ->add('language', ChoiceType::class,$languageOptions)
        ->add('save', SubmitType::class)
        ->getForm();
        
        $form->handleRequest($request);
        
        if($nodeid==NULL)
        {
            $nodeid=$nodename->getNodeid()->getNodeid();
        }
        
        if ($form->isSubmitted() && $form->isValid())
        {
            $languageid=$form->get("language")->getData();                        
            
            $language=$sentencesRepository->getLanguage($languageid);
            $nodename->setLanguageid($language);
            $nodename->setAuthorid($this->getUser());
            
            if($nodename->getNodeid()==NULL)
            {
                $node=$tocRepository->find($nodeid);
                $nodename->setNodeid($node);
            }            
                       
            try 
            {
                $tocRepository->persistNodeName($nodename);
                
                $response=$this->redirectToRoute('node_names',['nodeid'=>$nodeid]);
            }
            catch(\Exception $ex)
            {
                $formView=$form->createView();
                
                $response=$this->render('node_name_edit.html.twig',['nodeid'=>$nodeid,
                    'form'=>$formView,'message'=>$translator->trans('Name in this language already exists')]);                
            }
        }
        else
        {
            if($nodenameid)
            {
                $form->get("language")->setData($nodename->getLanguageid()->getLanguageid());
            }
            
            $formView=$form->createView();
                        
            $response=$this->render('node_name_edit.html.twig',['nodeid'=>$nodeid,'form'=>$formView,'message'=>'']);
        }
        
        return $response;
    }
    
    public function translationToc(TipitakaTocRepository $repository,Request $request)
    {
        //show node titles in user's language if they are available
        $nodes=$repository->listChildNodesWithNamesTranslation(NULL,$request->getLocale());
                        
        return $this->render('translation_toc.html.twig',
            ['child_nodes'=>$nodes,'showForm'=>false,'expandRoute'=>'translation_toc_node','authorRole'=>Roles::Author,'tags'=>array()]
            );
    }
    
    public function translationListById($id,TipitakaTocRepository $tocRepository,Request $request,
        TranslatorInterface $translator,TipitakaTagsRepository $tagsRepository)
    {
        $node=$tocRepository->find($id);
        
        if($node)
        {
            //show node titles in user's language if they are available        
            $child_nodes=$tocRepository->listChildNodesWithNamesTranslation($id,$request->getLocale());
    
            //replace hidden nodes with their child nodes until no hidden nodes are left
            do
            {
                $hiddenNodeFound=false;
                
                $child_nodes=$tocRepository->filterHiddenNodes($child_nodes,$request->getLocale());
                
                foreach($child_nodes as $node)
                {
                    if($node['IsHidden'])
                    {
                        $hiddenNodeFound=true;
                        break;
                    }
                }            
            }
            while($hiddenNodeFound);
                            
            $path_nodes=$tocRepository->listPathNodesWithNamesTranslation($id,$request->getLocale());
            
            $node=$tocRepository->find($id);
            
            $path=$node->getPath();
            
            $ar_child_node_id=array();
            foreach($child_nodes as $child_node)
            {
                $ar_child_node_id[]=$child_node['nodeid'];
            }
            
            $tags=$tagsRepository->listByNodeId($ar_child_node_id,$request->getLocale(),$path);
                    
            $response=$this->render('translation_toc.html.twig',
                ['child_nodes'=>$child_nodes,'path_nodes'=>$path_nodes,'expandRoute'=>'translation_toc_node','authorRole'=>Roles::Author,
                    'tags'=>$tags,'thisNode'=>$node]);
        }
        else 
        {
            $response=new Response('not found',404);
        }
        
        return $response;
    }
    
    
    public function nodeEdit($nodeid,TipitakaSourcesRepository $sourcesRepository,TipitakaTocRepository $tocRepository,
        TranslatorInterface $translator,Request $request)
    {
        $node=$tocRepository->getNode($nodeid);
        
        $route='full_toc_node';       
        
        $sources=$sourcesRepository->listSources();
        
        $sourcesAssoc=array();
        
        foreach($sources as $source)
        {
            $sourcesAssoc[$source['name'].' '.$source['language']]=$source['sourceid'];
        }
        
        $sourcesOptions=['choices'  => $sourcesAssoc,
            'label' => false,
            'expanded'=>false,
            'multiple'=>false,
            'mapped'=>false,
            'required' => false,
            'placeholder'=>$translator->trans('NullOption')
        ];
                
        $form = $this->createFormBuilder($node)
        ->add('title', TextType::class,['required' => true,'label' => false])
        ->add('IsHidden', CheckboxType::class,['label' => false,'required' => false])
        ->add('TranslationSourceID', ChoiceType::class,$sourcesOptions)
        ->add('notes', TextareaType::class,['required' => false,'label' => false])
        ->add('disableview', CheckboxType::class,['label' => false,'required' => false])
        ->add('disabletranslalign', CheckboxType::class,['label' => false,'required' => false])
        ->add('save', SubmitType::class)
        ->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid())
        {           
            $sourceid=$form->get("TranslationSourceID")->getData();
            if($sourceid)
            {
                $source=$sourcesRepository->find($sourceid);
                $node->setTranslationSourceID($source);
            }
            else
            {
                $node->setTranslationSourceID(null);
            }
            
            $tocRepository->persistNode($node);
            
            $response=$this->redirectToRoute($route,['id'=>$node->getNodeid()]);
        }
        else 
        {
            if($node->getTranslationSourceID())
            {
                $form->get("TranslationSourceID")->setData($node->getTranslationSourceID()->getSourceid());
            }
            
            $response=$this->render('node_edit.html.twig', ['form' => $form->createView(),
                'route'=>$route,'node'=>$node]);
        }
        
        return $response;
    }
    
    public function enableTableView($nodeid,TipitakaTocRepository $tocRepository)
    {
        $tocRepository->enableTableView($nodeid);
        return $this->redirectToRoute('view_node',['id'=>$nodeid]);
    }

    public function listNodeTags($nodeid,TipitakaTagsRepository $tagsRepository,TipitakaTocRepository $tocRepository,Request $request,
        TranslatorInterface $translator)
    {
        $tagtypeid=TagTypes::Subject;
        
        $form_data=$request->request->get('form');
        
        if($form_data && array_key_exists('tagtypes', $form_data))
        {
            $tagtypeid=$form_data['tagtypes'];
        }
                
        $choices=$tagsRepository->listAssoc($request->getLocale(),$tagtypeid);
        
        $tagTypes=$tagsRepository->listTagTypes();
        $tagTypesTrans=array();
        
        foreach($tagTypes as $tagType)
        {
            $tagTypesTrans[$translator->trans("TagType".$tagType["tagtypeid"])]=$tagType["tagtypeid"];
        }
        
        $form = $this->createFormBuilder()
        ->add('tagtypes', ChoiceType::class,
            ['choices'  => $tagTypesTrans,
            'label' => false,
            'expanded'=>false,
            'multiple'=>false,
            'required'=>true])
        ->add('update', SubmitType::class,['validate'=>false])
        ->add('tags', ChoiceType::class,
            ['choices'  => $choices,
                'label' => false,
                'expanded'=>false,
                'multiple'=>false,
                'mapped'=>false,
                'required'=>true,
                'placeholder'=>$translator->trans('Choose an option')
            ])
        ->add('save', SubmitType::class)
        ->getForm();
            
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid())
        {
            if($form->get('save')->isClicked())
            {
                $node=$tocRepository->find($nodeid);
                $tag=$tagsRepository->find($form->get("tags")->getData());
                
                try 
                {
                    $tagsRepository->addTagToNode($node,$tag,$this->getUser());
                }
                catch(\Exception $ex)
                {
                    //catch duplicate item index error
                    //do nothing, because the item already exists
                }
            }            
        }
        
        $formView=$form->createView();
        
        $node=$tocRepository->getNodeWithNameTranslation($nodeid, $request->getLocale());
        $tags=$tagsRepository->listByNode($nodeid);
        $names=$tagsRepository->listNamesByNode($nodeid);
        
        return $this->render('node_tags.html.twig',['node'=>$node,'tags'=>$tags,'names'=>$names,'form' => $formView]);
    }
    
    public function removeNodeTag($nodeid,$tagid,TipitakaTagsRepository $tagsRepository,Request $request)
    {
        $tagsRepository->removeNodeTag($tagid,$nodeid);
        return $this->redirectToRoute('node_tags',['nodeid'=>$nodeid]);
    }
}