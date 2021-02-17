<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Repository\TipitakaCommentsRepository;
use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use App\Security\Roles;
use App\Entity\TipitakaUsers;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends AbstractController
{
    public function edit($id,UserRepository $ur,Request $request,EncoderFactoryInterface $factory)
    {               
        $user=$ur->find($id);
                
        $form = $this->createFormBuilder($user)
        ->add('username', TextType::class,['required' => true,'label' => false])        
        ->add('email', EmailType::class,['required' => true,'label' => false])
        ->add('roles', TextType::class,['label' => false,'mapped'=>false])
        ->add('allowcommentshtml', CheckboxType::class,['required' => false,'label' => false])
        ->add('isenabled', CheckboxType::class,['required' => false,'label' => false])
        ->add('save', SubmitType::class,['label' => 'Save'])
        ->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) 
        {                                  
            $roles=explode(",",$form->get("roles")->getData());
            
            if($roles==NULL)
            {
                $roles="ROLE_USER";
            }
            
            $user->setRoles($roles);
            
            $ur->persist($user);
            $response=$this->redirectToRoute('userlist');            
        }       
        else 
        {
            $roles=$user->getRoles();
            $form->get("roles")->setData(implode(",",$roles));
            $response=$this->render('user_edit.html.twig', ['form' => $form->createView()]);
        }
        
        return $response;
    }
    
    public function register(UserRepository $userRepository,Request $request,EncoderFactoryInterface $encoderFactory,
        TranslatorInterface $translator, GuardAuthenticatorHandler $guard, LoginFormAuthenticator $login)
    {
        if($this->getParameter('app.enable_registration'))//see config/services.yaml
        {        
            $form = $this->createFormBuilder()
            ->add('username', TextType::class,['required' => true,'label' => false])
            ->add('password', PasswordType::class,['required' => true,'label' => false])
            ->add('email', EmailType::class,['required' => true,'label' => false])
            ->add('save', SubmitType::class,['label' => 'RegisterAccountButton'])
            ->getForm();
            
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid())
            {
                $data = $form->getData();
                
                $user = new TipitakaUsers();
                
                $user->setUsername($data['username']);
                
                $encoder = $encoderFactory->getEncoder($user);
                $password = $encoder->encodePassword($data['password'], $user->getSalt());
                $user->setPassword($password);    
                
                $user->setEmail($data['email']);                
                $user->setRoles(["ROLE_USER"]);                
                $user->setAllowcommentshtml(false);                
                $user->setIsenabled(true);
                
                try
                {
                    $userRepository->persist($user);
                    
                    $guard->authenticateUserAndHandleSuccess($user,$request,$login,'main');
                    
                    $response=$this->redirectToRoute('user');
                }
                catch(\Exception $ex)
                {                                
                    $response=$this->render('register.html.twig', ['form' => $form->createView(),
                        'message'=>$translator->trans('Username already exists')//$ex->getMessage()
                    ]);
                }                                    
            }
            else 
            {
                $response=$this->render('register.html.twig', ['form' => $form->createView(),'message'=>'']);
            }
        }
        else 
        {
            $response=$this->redirectToRoute('index');
        }
        
        return $response;
    }
    
    public function list(UserRepository $ur)
    {  
        $users=$ur->findAll();
        return $this->render('users_list.html.twig', ['users'=>$users,'authorRole'=>Roles::Author,
            'adminRole'=>Roles::Admin,'editorRole'=>Roles::Editor]);
    }
    
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        
        $registrationEnabled=$this->getParameter('app.enable_registration');
        
        return $this->render('login.html.twig', ['last_username' => $lastUsername, 'error' => $error,
            'registrationEnabled'=>$registrationEnabled
        ]);
    }
    
    public function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }
    
    public function comments(TipitakaCommentsRepository $commentsRepository)
    {
        $comments=$commentsRepository->listUserLatest(50,$this->getUser()->getUserid());
        
        return $this->render('user_comments.html.twig',['authorRole'=>Roles::Author,'comments'=>$comments,
            'adminRole'=>Roles::Admin,'editorRole'=>Roles::Editor]);
    }
}

