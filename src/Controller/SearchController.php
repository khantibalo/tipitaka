<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\NativeRepository;
use App\Repository\TipitakaTocRepository;
use App\Repository\TipitakaSentencesRepository;
use Doctrine\DBAL\Exception\SyntaxErrorException;


class SearchController extends AbstractController
{
    const LanguagePali=0;
    
    public function search(Request $request,SessionInterface $session,
        TipitakaTocRepository $tocRepository,TipitakaSentencesRepository $sentencesRepository,NativeRepository $nativeRepository)
    {
        $bookmarks_str = $session->get(BookmarksController::session_key,'');
        
        $defaultData = ['scopeChoice' => 'toc'];
        
        $languages=$sentencesRepository->listSearchLanguagesAssoc();
        $alllanguages=array_merge(['pali'=>SearchController::LanguagePali],$languages);
        
        $form = $this->createFormBuilder($defaultData, array('csrf_protection' => false))
        ->add('searchString', TextType::class,['required' => true,'label' => false])
        ->add('scopeChoice', ChoiceType::class,
            ['choices'  => [
                'table of contents' => 'toc',
                'text' => 'text',
                'bookmarks' => 'bkm'],
                'label' => false,
                'expanded'=>true,
                'multiple'=>false
            ])
        ->add('lang',ChoiceType::class,
            ['choices'  => $alllanguages,
                'label' => false,
                'expanded'=>false,
                'multiple'=>false
            ])
        ->add('inTranslated', CheckboxType::class,['required' => false,'label' => false])
        ->add('search', SubmitType::class,['label' => 'Search'])
        ->getForm();
        
        $form->handleRequest($request);
        
        $searchItems=array();
        $scope='';
        $searchString='';        
        $searchError=FALSE;
        
        if ($form->isSubmitted() && $form->isValid()) {         
            $data = $form->getData();
                                    
            $params=array();
            if(!empty($bookmarks_str))
            {
                $params["b"]=$bookmarks_str;
            }
            
            if(!empty($data['searchString']))
            {
                $params["search"]=$data['searchString'];
            }
                       
            $params["scope"]=$data['scopeChoice'];
            
            if($params["scope"]=='text' || $params["scope"]=='bkm')
            {
                $params["lang"]=$data['lang'];
            }
            
            if($params["scope"]=='toc')
            {
                $params["lang"]=$data['lang'];
            }
            
            if(!empty($data['inTranslated']))
            {
                $params["int"]=$data['inTranslated'];
            }
                                    
            $response=$this->redirectToRoute('search', $params);
        }
        else 
        {
            $searchString=$request->get('search','');
            $scope=$request->get('scope');
            $lang=$request->get('lang');
            $inTranslated=$request->get('int',false);
            
            if(!is_null($request->get('b')))
                $bookmarks_str=$request->get('b');
            
            if(!empty($searchString) && !empty($scope))
            {                
                $form->get("searchString")->setData($searchString);
                $form->get("scopeChoice")->setData($scope);
                $form->get("lang")->setData($lang);
                $form->get("inTranslated")->setData($inTranslated=="1" ? true : false);
                                
                if($scope=='toc')
                {
                    if($lang==SearchController::LanguagePali)
                    {
                        $searchItems=$tocRepository->search($searchString,$inTranslated);
                    }
                    else 
                    {
                        $searchItems=$tocRepository->searchLanguage($searchString,$lang,$inTranslated);
                    }
                }
                
                try 
                {                   
                    //these searches may cause exceptions if the search string does not follow MySql Full text search syntax
                    if($scope=='text')
                    {
                        if($lang==SearchController::LanguagePali)
                        {
                            $searchItems=$nativeRepository->searchGlobal($searchString,$inTranslated);
                        }
                        else
                        {
                            $searchItems=$sentencesRepository->searchTranslation($searchString,$lang);
                        }
                    }
                    
                    if($scope=='bkm')
                    {
                        if($lang==SearchController::LanguagePali)
                        {
                            $searchItems=$nativeRepository->searchBookmarks($searchString,$bookmarks_str,$inTranslated);
                        }
                        else
                        {
                            $searchItems=$nativeRepository->searchBookmarksTranslations($searchString,$bookmarks_str,$lang);
                        }
                    }       
                } 
                catch (SyntaxErrorException $e) 
                {
                    $searchError=true;
                }
            }
            
            $translations=array();
            //with this we filter the array, so to place translations in a separate array
            if(sizeof($searchItems)>0 && array_key_exists("translation", $searchItems[0]))
            {
                $paragraphIDs=array();
                $searchItemsFiltered=array();
                
                foreach($searchItems as $searchItem)
                {
                    if(!array_key_exists($searchItem["paragraphid"],$paragraphIDs))
                    {                        
                        $searchItemsFiltered[]=$searchItem;
                        $paragraphIDs[$searchItem["paragraphid"]]=1;
                    }
                    
                    if($searchItem["translation"])
                    {
                        $translations[$searchItem["paragraphid"]][]=$searchItem["translation"];
                    }
                }
                
                $searchItems=$searchItemsFiltered;
            }            
            
            $response=$this->render('search.html.twig', [
                'form' => $form->createView(), 'bookmarks'=>$bookmarks_str,'searchItems'=>$searchItems,
                'scope'=>$scope,'searchString'=>$searchString,'language'=>$lang,'translations'=>$translations,
                'searchError'=>$searchError, 'inTranslated'=>$inTranslated
            ]);
        }
        
        
        return $response;
    }
    
}

