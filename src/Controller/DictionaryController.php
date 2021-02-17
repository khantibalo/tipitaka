<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\TipitakaDictionaryRepository;
use App\Repository\TipitakaSentencesRepository;
use App\Repository\TipitakaTagsRepository;
use App\Security\Roles;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Repository\TipitakaTocRepository;
use App\Entity\TipitakaDictionaryentries;
use App\Entity\TipitakaDictionaryentryUse;
use App\Enums\TagTypes;
use App\Repository\NativeRepository;

class DictionaryController extends AbstractController
{
    public function dictionary(Request $request, TipitakaDictionaryRepository $dictionaryRepository,
        TranslatorInterface $translator, TipitakaTagsRepository $tagsRepository,NativeRepository $nativeRepository)
    {               
        $defaultData = ['dictionaryChoice' => 'a','typeChoice'=>'a','scopeChoice'=>'n'];
        
        $dt=$dictionaryRepository->listDictionaryTypes();
        $dt[$translator->trans('allDictionaries')]='';
        
        $form = $this->createFormBuilder($defaultData)
        ->add('searchString', TextType::class,['required' => true,'label' => false])
        ->add('dictionaryChoice', ChoiceType::class,
            ['choices'  => $dt,
                'label' => false,
                'expanded'=>false,
                'multiple'=>false,
                'required' => true
            ])
        ->add('typeChoice', ChoiceType::class,
            ['choices'  => [$translator->trans('auto')=>'a',
                $translator->trans('begins with')=>'b',
                $translator->trans('contains')=>'c'],
                'label' => false,
                'expanded'=>false,
                'multiple'=>false,
                'required' => true
            ])
        ->add('ignoreDiac', CheckboxType::class,['required' => false,'label' => false])
        ->add('scopeChoice', ChoiceType::class,
            ['choices'  => [$translator->trans('term names')=>'n',
                $translator->trans('dictionary content')=>'c'],
                'label' => false,
                'expanded'=>false,
                'multiple'=>false,
                'required' => true
            ])
        ->add('search', SubmitType::class,['label' => 'SearchButton'])
        ->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) 
        {
            $data = $form->getData();
            
            $params=array();
            
            if(!empty($data['searchString']))
            {
                $params["search"]=$data['searchString'];
            }
            
            if($data['dictionaryChoice'])
            {
                $params["d"]=$data['dictionaryChoice'];
            }
            
            if(!empty($data['typeChoice']))
            {
                $params["t"]=$data['typeChoice'];
            }
            
            if(!empty($data['ignoreDiac']))
            {
                $params["igd"]='on';
            }
            
            if(!empty($data['scopeChoice']))
            {
                $params["s"]=$data['scopeChoice'];
            }
                                    
            $response=$this->redirectToRoute('dictionary', $params);
        }
        else
        {
            $letters=array("-","°","a","b","c","d","e","g","h","i","j","k","l","m","n","o","p","r","s","t","u","v","y","ñ","ā","ī","ū","ḍ","ḷ","ṇ","ṭ");
            $selLetter=$request->get('letter','');            
            $searchString=$request->get('search','');
            $tagid=$request->get('tagid',''); 
            $letterTerms='';
            $foundTerms=array();
            $foundContent=array();
            $tags=array();
            $tagPaliwords=array();
            
            if(!empty($searchString))
            {
                $form->get("searchString")->setData($request->get('search',''));
                $form->get("dictionaryChoice")->setData($request->get('d',''));
                $form->get("typeChoice")->setData($request->get('t','a'));
                $form->get("ignoreDiac")->setData(!empty($request->get('igd','')));
                $form->get("scopeChoice")->setData($request->get('s','n'));
                
                if($request->get('s','n')=='n')
                {
                    $foundTerms=$nativeRepository->searchTermNames($request->get('search',''),
                        $request->get('d',''),$request->get('t','a'),$request->get('igd',''));
                    
                }
                else
                {
                    $foundContent=$dictionaryRepository->searchContent($request->get('search',''),$request->get('d',''));
                }
                
            }
            else 
            {
                if(!empty($selLetter))
                {
                    $letterTerms=$dictionaryRepository->listByLetter($selLetter);
                }
                
                if(!empty($tagid))
                {
                    if($tagid==-1)
                    {
                        $tags=$tagsRepository->listTermTagsWithStats($request->getLocale());
                    }
                    else 
                    {
                        $tags=$tagsRepository->getTermTagsWithStats($request->getLocale(),$tagid);
                        $tagPaliwords=$dictionaryRepository->listByTag($tagid);
                    }
                }
                
                $form->get("dictionaryChoice")->setData('');
                $form->get("typeChoice")->setData('a');
                $form->get("scopeChoice")->setData('n');
            }            
            
            $response=$this->render('dictionary.html.twig', ['form' => $form->createView(),
                'letters'=>$letters,'selLetter'=>$selLetter,'letterTerms'=>$letterTerms,
                'foundTerms'=>$foundTerms,'foundContent'=>$foundContent,
                'keyword'=>$request->get('search',''),'editorRole'=>Roles::Editor,'tagid'=>$tagid,
                'tags'=>$tags,'tagPaliwords'=>$tagPaliwords]);
        }
        
        return $response;
    }
    
    public function term($word, TipitakaDictionaryRepository $dictionaryRepository,TipitakaTagsRepository $tagsRepository,Request $request,
        TipitakaTocRepository $tocRepository)
    {
        $entries=$dictionaryRepository->listByTerm($word);
        $tags=$tagsRepository->listByPaliwordLanguage($word,$request->getLocale());
        $tagPaliwords=$dictionaryRepository->listLinkedPaliwords($word);
        $nodes=array();
        
        $tag=$tagsRepository->findOneBy(["paliname"=>$word]);
        if($tag)
        {
            $nodes=$tocRepository->listNodesByTag($tag->getTagId(),$request->getLocale());
        }
        
        $termExplanations=$dictionaryRepository->listExplanationsByTerm($word);
        $uses=$dictionaryRepository->listUsesByTerm($word);
        
        return $this->render('term.html.twig',['term'=>$word,'entries'=>$entries,'tags'=>$tags,
            'tagPaliwords'=>$tagPaliwords,'editorRole'=>Roles::Editor,'nodes'=>$nodes,'termExplanations'=>$termExplanations,
            'uses'=>$uses
        ]);
    }
    
    public function listPaliwordTags($paliword,TipitakaTagsRepository $tagsRepository,
        Request $request,TranslatorInterface $translator)
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
                $tag=$tagsRepository->find($form->get("tags")->getData());
            
                $tagsRepository->addTagToPaliword($paliword,$tag,$this->getUser());
            }
        }
        
        $formView=$form->createView();
        
        $tags=$tagsRepository->listByPaliword($paliword);
        $names=$tagsRepository->listNamesByPaliword($paliword);
        
        return $this->render('paliword_tags.html.twig',['paliword'=>$paliword,'tags'=>$tags,'names'=>$names,'form' => $formView]);
    }
    
    public function removePaliwordTag($paliword,$tagid,TipitakaTagsRepository $tagsRepository,Request $request)
    {
        $tagsRepository->removePaliwordTag($tagid,$paliword);
        return $this->redirectToRoute('paliword_tags',['paliword'=>$paliword]);
    }
    
    public function editDictionaryEntry(TipitakaDictionaryRepository $dictionaryRepository,Request $request,
        TipitakaSentencesRepository $sentencesRepository)
    {
        $dt=$dictionaryRepository->listDictionaryTypes();
        $entryid=$request->query->get('entryid');
       
        if($entryid)
        {
            $entry=$dictionaryRepository->find($entryid);
        }
        else
        {
            $entry=new TipitakaDictionaryentries();
        }        
        
        $form = $this->createFormBuilder($entry)        
        ->add('dictionarytypeid', ChoiceType::class,
            ['choices'  => $dt,
                'label' => false,
                'expanded'=>false,
                'multiple'=>false,
                'required' => true,
                'mapped'=>false
            ])
        ->add('paliword', TextType::class,['required' => true,'label' => false])
        ->add('explanation', TextareaType::class,['required' => false,'label' => false])
        ->add('explanationids', TextareaType::class,['required' => false,'label' => false])
        ->add('translation', TextType::class,['required' => false,'label' => false])
        ->add('save', SubmitType::class)
        ->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid())
        {
            $dictionarytypeid=$dictionaryRepository->getDictionaryType($form->get("dictionarytypeid")->getData());
            $entry->setDictionarytypeid($dictionarytypeid);            
            
            $dictionaryRepository->persistEntry($entry);
            
            if($entryid)
            {
                $response=$this->redirectToRoute('term',['word'=>$entry->getPaliword()]);
            }
            else 
            {
                $response=$this->redirectToRoute('dictionary');
            }
        }    
        else
        {
            if($entryid==NULL)
            {
                $dictionary=$dictionaryRepository->findDictionaryByCode("R");
                $form->get("dictionarytypeid")->setData($dictionary["dictionarytypeid"]);
            }
            else 
            {
                $form->get("dictionarytypeid")->setData($entry->getDictionarytypeid()->getDictionarytypeid());
            }
                        
            $formView=$form->createView();
            $response=$this->render('dictionaryentry_edit.html.twig',['form' => $formView,'entry'=>$entry]);
        }                
        
        return $response;
    }
    
    public function editDictionaryEntryUse(TipitakaDictionaryRepository $dictionaryRepository,Request $request,
        TipitakaSentencesRepository $sentencesRepository)
    {
        $useid=$request->query->get('useid');
        $entryid=$request->query->get('entryid');
        
        if($useid)
        {
            $use=$dictionaryRepository->getUse($useid);
        }
        else
        {
            $use=new TipitakaDictionaryentryUse();
        }
        
        $form = $this->createFormBuilder($use)
            ->add('sentencetranslationid', IntegerType::class,['required' => true,'label' => false,'mapped'=>false])
            ->add('paliword', TextType::class,['required' => true,'label' => false])
            ->add('translation', TextType::class,['required' => true,'label' => false])
            ->add('save', SubmitType::class)
            ->getForm();
            
        $form->handleRequest($request);
        
        if($entryid)
        {
            $entry=$dictionaryRepository->find($entryid);
            $use->setDictionaryentryid($entry);
        }
        else
        {
            $entry=$use->getDictionaryentryid();
        }
        
        if ($form->isSubmitted() && $form->isValid())
        {            
            $sentencetranslationid=$form->get("sentencetranslationid")->getData();
            
            $translation=$sentencesRepository->getTranslation($sentencetranslationid);
            $use->setSentencetranslationid($translation);
            
            $dictionaryRepository->persistUse($use);
            
            $response=$this->redirectToRoute('term',['word'=>$entry->getPaliword()]);            
        }
        else
        {
            if($useid)
            {
                $form->get("sentencetranslationid")->setData($use->getSentencetranslationid()->getSentencetranslationid());
            }
            
            $formView=$form->createView();
            $response=$this->render('dictionaryentryuse_edit.html.twig',['form' => $formView,'paliword'=>$entry->getPaliword()]);
        }
        
        return $response;
    }
}

