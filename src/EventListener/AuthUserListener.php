<?php

namespace App\EventListener;

use App\Repository\UserRepository;
use App\Repository\CartRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Core\Security;

class AuthUserListener implements EventSubscriberInterface
{
    private $userRepository;

    public function __construct(UserRepository $userRepository, CartRepository $cartRepository, Security $security)
    {
        $this->userRepository = $userRepository;
        $this->cartRepository = $cartRepository;
        $this->security = $security;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();

        $request = $event->getRequest();

        // Проверяем наличие корзины у посетителя
        if (!$request->getSession()->has('cart_user_id')) {
            // Заранее генируем куку
            $userCartIdGenerat = bin2hex(random_bytes(16));
            $cookie = Cookie::create('cart_user_id')
                ->withValue($userCartIdGenerat)
                ->withExpires(new \DateTime('now +1 month'))
                ->withPath('/')
                ->withSameSite('lax')
                ->withSecure(true) // только будет работать на https
                ->withHttpOnly(false); // нельзя работать через javascript

            // Если кука есть, проверяем её минимально на валидность, создаем сесс.переменную
            if ($request->cookies->has('cart_user_id')) {
                $userCartId = $request->cookies->get('cart_user_id');
                // dd($userCartId);
                if (strlen($userCartId) == 32 && preg_match("/[a-z]+/", $userCartId) 
                    && preg_match("/[0-9]+/", $userCartId)) {

                    $request->getSession()->set('cart_user_id', $userCartId);
                } else {
                    $request->getSession()->set('cart_user_id', $userCartIdGenerat);
                    // Создаем куку корзины
                    $response = new Response();    
                    $response->headers->setCookie($cookie);
                    $response->sendHeaders();
                }
            } else {
                $request->getSession()->set('cart_user_id', $userCartIdGenerat);
                // Создаем куку корзины
                $response = new Response();    
                $response->headers->setCookie($cookie);
                $response->sendHeaders();
            }
        }
        
    }

    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => ['onKernelRequest', 0]
        ];
    }
}