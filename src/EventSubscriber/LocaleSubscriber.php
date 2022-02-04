<?php
namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;


class LocaleSubscriber implements EventSubscriberInterface
{
    private $defaultLocale;
    
    public function __construct($defaultLocale = '',ContainerBagInterface $params)
    {
        if(!$defaultLocale)
        {
            $defaultLocale=$params->get("app.defaultLocale");//see config/services.yaml
        }
        
        $this->defaultLocale = $defaultLocale;
    }
    
    public function onKernelRequest(RequestEvent $event)
    {        
        $request = $event->getRequest();

        $locale = $request->cookies->get('locale');
        
        if ($locale=='ru' || $locale=='en') 
        {
            $request->setLocale($locale);
        }
        else
        {
            $request->setLocale($this->defaultLocale);
        }
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered before (i.e. with a higher priority than) the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}

