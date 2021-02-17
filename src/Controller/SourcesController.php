<?php
namespace App\Controller;

use App\Repository\TipitakaSentencesRepository;
use App\Repository\TipitakaSourcesRepository;
use App\Security\Roles;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\TipitakaSources;

class SourcesController extends AbstractController
{
    
    public function list(TipitakaSourcesRepository $sourcesRepository)
    {
        $sources=$sourcesRepository->listSources();
        
        return $this->render('sources_list.html.twig', ['sources'=>$sources,'authorRole'=>Roles::Author,
            'adminRole'=>Roles::Admin,'editorRole'=>Roles::Editor]);
    }
    
    public function edit(Request $request, TipitakaSourcesRepository $sourcesRepository,TipitakaSentencesRepository $sentencesRepository)
    {
        $languages=$sentencesRepository->listLanguages();
        
        $sourceid=$request->query->get('sourceid');
        
        if($sourceid)
        {
            $source=$sourcesRepository->find($sourceid);
        }
        else
        {
            $source=new TipitakaSources();
        }
        
        $form = $this->createFormBuilder($source)
        ->add('name', TextType::class,['required' => true,'label' => 'Name:'])
        ->add('language', ChoiceType::class,
            ['choices'  => $languages,
                'label' => 'Language:',
                'expanded'=>false,
                'multiple'=>false,
                'required' => true,
                'mapped'=>false
            ])
            ->add('ishidden', CheckboxType::class,['required' => false])
            ->add('excludefromsearch', CheckboxType::class,['required' => false])
            ->add('hasformatting', CheckboxType::class,['required' => false])
            ->add('save', SubmitType::class,['label' => 'save'])
            ->getForm();
            
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid())
            {
                $language=$sentencesRepository->getLanguage($form->get("language")->getData());
                $source->setLanguageid($language);
                
                $sourcesRepository->addSource($source);
                
                $response=$this->redirectToRoute('sources_list');
            }
            else
            {
                if($sourceid)
                {
                    $form->get("language")->setData($source->getLanguageid()->getLanguageid());
                }
                
                $response=$this->render('source_edit.html.twig', ['form' => $form->createView()]);
            }
            
            return $response;
    }
}

