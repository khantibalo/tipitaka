<?php
namespace App\Controller;

use App\Repository\TipitakaSentencesRepository;
use App\Repository\TipitakaTagsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\TipitakaTocRepository;
use App\Security\Roles;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Entity\TipitakaTags;
use App\Entity\TipitakaTagNames;

class TagsController extends AbstractController
{

    
    public function editTag(TipitakaSentencesRepository $sentencesRepository,TranslatorInterface $translator,Request $request,
        TipitakaTagsRepository $tagsRepository,TipitakaTocRepository $tocRepository)
    {
        $nodeid=$request->query->get('nodeid');
        $tagid=$request->query->get('tagid');
        $paliword=$request->query->get('paliword');
        
        $languages=$sentencesRepository->listLanguages();        
        $langOptions=['choices'  => $languages,
            'label' => false,
            'expanded'=>false,
            'multiple'=>false,
            'required'=>true            
        ];
                
        if($tagid==NULL)
        {
            $langOptions['placeholder']=$translator->trans('Choose an option');
        }
                
        $tagTypes=$tagsRepository->listTagTypes();
        $tagTypesTrans=array();
        
        foreach($tagTypes as $tagType)
        {
            $tagTypesTrans[$translator->trans("TagType".$tagType["tagtypeid"])]=$tagType["tagtypeid"];
        }
        
        $tagTypesOptions=['choices'  => $tagTypesTrans,
            'label' => false,
            'expanded'=>false,
            'multiple'=>false,
            'required'=>true
        ];
        
        if($tagid==NULL)
        {
            $tagTypesOptions['placeholder']=$translator->trans('Choose an option');
        }
        
        $fb=$this->createFormBuilder();
        
        $fb=$fb
        ->add('paliname', TextType::class,['required' => false])
        ->add('tagtype', ChoiceType::class,$tagTypesOptions)
        ->add('save', SubmitType::class);
        
        if($tagid==NULL)
        {
            $fb=$fb
            ->add('title', TextType::class,['required' => true])
            ->add('language', ChoiceType::class,$langOptions)            
            ->add('saveAndAssign', SubmitType::class);
        }
        
        $form=$fb->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid())
        {
            $tagtype=$tagsRepository->getTagType($form->get("tagtype")->getData());
            
            if($tagid)
            {
                $tag=$tagsRepository->find($tagid);                            
            }
            else 
            {
                $tag=new TipitakaTags();
            }
            
            $tag->setPaliname($form->get("paliname")->getData());
            $tag->setTagtypeid($tagtype);
            
            try
            {  
                $tagsRepository->updateTag($tag);    
                
                if($tagid==NULL)
                {
                    $language=$sentencesRepository->getLanguage($form->get("language")->getData());
                    $tagName=new TipitakaTagNames();
                    $tagName->setLanguageid($language);
                    $tagName->setTitle($form->get("title")->getData());
                    $tagName->setTagid($tag);
                    $tagsRepository->updateTagName($tagName); 
                }
                
                if($form->has('saveAndAssign') && $form->get('saveAndAssign')->isClicked())
                {
                    if($nodeid)
                    {
                        $node=$tocRepository->find($nodeid);                    
                        $tagsRepository->addTagToNode($node,$tag,$this->getUser());
                    }
                    
                    if($paliword)
                    {                                        
                        $tagsRepository->addTagToPaliword($paliword,$tag,$this->getUser());
                    }
                }
                            
                if($nodeid)
                {
                    $response=$this->redirectToRoute('node_tags',['nodeid'=>$nodeid]);
                }
                
                if($paliword)
                {
                    $response=$this->redirectToRoute('paliword_tags',['paliword'=>$paliword]);
                }
                
                if(!isset($response))
                {
                    $response=$this->redirectToRoute('toc_tags_list');
                }
            }
            catch(\Exception $ex)
            {
                $formView=$form->createView();
                $response=$this->render('tag_edit.html.twig',['nodeid'=>$nodeid, 'form' => $formView,'paliword'=>$paliword,
                    'message'=>$translator->trans('This pali name already exists')
                ]);
            }
        }
        else 
        {        
            if($tagid)
            {//правка
                $tag=$tagsRepository->find($tagid);
                
                $form->get("paliname")->setData($tag->getPaliname());
                $form->get("tagtype")->setData($tag->getTagtypeid()->getTagtypeid());
            }            
            
            $formView=$form->createView();
            $response=$this->render('tag_edit.html.twig',['nodeid'=>$nodeid, 'form' => $formView,'paliword'=>$paliword,
                'message'=>NULL
            ]);
        }        
        
        return $response;
    }
           
    public function tocTagTypesList(Request $request,TipitakaTagsRepository $tagsRepository,
        TipitakaTocRepository $tocRepository)
    {
        $tagTypes=$tagsRepository->listTagTypes();
        $tags=array();
        $nodes=array(); 
        
        return $this->render('toc_tags_list.html.twig', ['tags'=>$tags,'tagTypes'=>$tagTypes,
            'nodes'=>$nodes,'authorRole'=>Roles::Author,'tagtypeid'=>NULL,'tagid'=>NULL]);
    }
    
    public function tocTagsList($tagtypeid, Request $request,TipitakaTagsRepository $tagsRepository,
        TipitakaTocRepository $tocRepository)
    {        
        $tagTypes=$tagsRepository->listTagTypes();
        $tags=array();
        $nodes=array();        

        if($tagtypeid==-1)
        {
            $tags=$tagsRepository->listTocPaliTagsWithStats($request->getLocale());
        }
        else 
        {
            $tags=$tagsRepository->listTocTagsWithStats($request->getLocale(),$tagtypeid);
        }               
        
        return $this->render('toc_tags_list.html.twig', ['tags'=>$tags,'tagTypes'=>$tagTypes,
            'nodes'=>$nodes,'authorRole'=>Roles::Author,'tagtypeid'=>$tagtypeid,'tagid'=>NULL]);
    }    
    
    public function tocTagNodesList($tagid, Request $request,TipitakaTagsRepository $tagsRepository,
        TipitakaTocRepository $tocRepository)
    {       
        $tagTypes=$tagsRepository->listTagTypes();        
        $tags=$tagsRepository->getTocTagWithStats($request->getLocale(),$tagid);
        $nodes=$tocRepository->listNodesByTag($tagid,$request->getLocale());
        
        return $this->render('toc_tags_list.html.twig', ['tags'=>$tags,'tagTypes'=>$tagTypes,
            'nodes'=>$nodes,'authorRole'=>Roles::Author,'tagtypeid'=>NULL,'tagid'=>$tagid]);
    }
    
    public function editTagName(TipitakaSentencesRepository $sentencesRepository,TranslatorInterface $translator,Request $request,
        TipitakaTagsRepository $tagsRepository)
    {
        $tagnameid=$request->query->get('tagnameid');
        $tagid=$request->query->get('tagid');
        
        $languages=$sentencesRepository->listLanguages();
        $langOptions=['choices'  => $languages,
            'label' => false,
            'expanded'=>false,
            'multiple'=>false,
            'required'=>true
        ];
        
        if($tagid==NULL)
        {
            $langOptions['placeholder']=$translator->trans('Choose an option');
        }
                
        $form=$this->createFormBuilder()
        ->add('title', TextType::class,['required' => true])
        ->add('language', ChoiceType::class,$langOptions)
        ->add('save', SubmitType::class)
        ->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid())
        {            
            if($tagnameid==NULL)
            {
                $tagName=new TipitakaTagNames();
                $tag=$tagsRepository->find($tagid); 
                $tagName->setTagid($tag);
            }
            else 
            {
                $tagName=$tagsRepository->getTagNameObj($tagnameid);
                $tagid=$tagName->getTagid();
            }
            
            $language=$sentencesRepository->getLanguage($form->get("language")->getData());            
            $tagName->setLanguageid($language);
            $tagName->setTitle($form->get("title")->getData());
            
            try 
            {                
                $tagsRepository->updateTagName($tagName);            
                           
                $response=$this->redirectToRoute('tag_names',['tagid'=>$tagName->getTagid()->getTagid()]);
            }
            catch(\Exception $ex)
            {
                $response=$this->render('tag_name_edit.html.twig',['tagid'=>$tagid,
                    'form'=>$form->createView(),'message'=>$translator->trans('Name in this language already exists')]);
            }
        }
        else
        {
            if($tagnameid)
            {//правка
                $tagName=$tagsRepository->getTagName($tagnameid);
                
                $form->get("title")->setData($tagName["title"]);
                $form->get("language")->setData($tagName["languageid"]);
                $tagid=$tagName["tagid"];
            }
            
            $formView=$form->createView();
            $response=$this->render('tag_name_edit.html.twig',['form' => $formView,'tagid'=>$tagid
                ,'message'=>''
            ]);
        }
        
        return $response;
    }
    
    public function listTagNames($tagid,TipitakaTagsRepository $tagsRepository)
    {
        $tag=$tagsRepository->find($tagid);
        
        $names=$tagsRepository->listNamesByTag($tagid);
        
        return $this->render('tag_names.html.twig', ['tag'=>$tag,'names'=>$names]);
    }
    
    public function tagsRedirect(Request $request)
    {
        $tagid=$request->query->get('tagid');
        $tagtypeid=$request->query->get('tagtypeid');
        
        if($tagid)
        {
            $response=$this->redirectToRoute('toc_tag_nodes_list',['tagid'=>$tagid],301);
        }
        
        if($tagtypeid)
        {
            $response=$this->redirectToRoute('toc_tags_list',['tagtypeid'=>$tagtypeid],301);
        }
        
        return $response;
    }
}

