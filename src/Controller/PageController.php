<?php

namespace App\Controller;

use App\Message\FormEmailNotification;
use App\Entity\Form;
use App\Form\FeedbackFormType;
use App\Repository\FormRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Messenger\MessageBusInterface;

class PageController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(Request $request): Response
    {
        return $this->render('page/main.html.twig', []);
    }

    #[Route('/formlogin', name: 'form_login')]
    public function formLogin(): Response
    {
        return $this->render('form/login.html.twig');
    }

    #[Route('/login_old', name: 'login_old')]
    public function loginOld(): Response
    {
        return $this->render('security/login.html.twig');
    }

    #[Route('/contacts', name: 'contacts')]
    public function contacts(Request $request, FormRepository $formRepository, MessageBusInterface $bus): Response
    {
        $breadcrumbs = [
            ['href' => '/contacts', 'text' => 'Контакты']
        ];

        $feedback = new Form();
        $form = $this->createForm(FeedbackFormType::class, $feedback);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            
            // g-recaptcha-response
            $captcha = false;
            if (!empty($request->request->get('g-recaptcha-response'))) {
                $url = 'https://www.google.com/recaptcha/api/siteverify';
                $key = '############';
                $query = [
                    'secret' => $key,
                    'response' => $request->request->get('g-recaptcha-response'),
                    'remoteip' => $request->server->get('REMOTE_ADDR')
                ];
            
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url); 
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
                curl_setopt($ch, CURLOPT_POST, true); 
                curl_setopt($ch, CURLOPT_POSTFIELDS, $query); 
                $data = json_decode(curl_exec($ch), true); 
                curl_close($ch);
            
                if ($data['success']) {
                    $captcha = true;
                }
            }
            
            if (!$captcha) {
                $this->addFlash(
                    'error',
                    'Подтвердите что Вы не робот, пройдите каптчу!'
                );
                // dd($captcha);
            }
            
            if (empty(trim($form->get('name')->getData()))) {
                $form->get('name')->addError(new FormError('Поле Имя не заполнено'));
            }
            if (!filter_var($form->get('email')->getData(), FILTER_VALIDATE_EMAIL)) {
                $form->get('email')->addError(new FormError('Проверьте корректность заполнения поля Email'));
            }
            if (empty(trim($form->get('phone')->getData()))) {
                $form->get('phone')->addError(new FormError('Поле Телефон не заполнено'));
            }
            if (empty(trim($form->get('comment')->getData()))) {
                $form->get('comment')->addError(new FormError('Поле Ваш комментарий не заполнено'));
            }

            $fieldsForm = [
                'name' => $form->get('name')->getData(),
                'email' => $form->get('email')->getData(),
                'phone' => $form->get('phone')->getData(),
                'comment' => $form->get('comment')->getData(),
            ];

            if ($form->isValid() && $captcha) {
                $fields = [
                    'type' => 'feedback',
                    'fields' => json_encode($fieldsForm),
                    'send_email' => 0
                ];
                $resId = $formRepository->addDataForm($fields);
                if ($resId) {
                    $bus->dispatch(new FormEmailNotification($resId));

                    $this->addFlash(
                        'notice',
                        'Ваша заявка была отправлена!'
                    );

                    return $this->redirectToRoute('contacts', [
                        '_fragment' => 'contact_form'
                    ]);
                }
            }
        }

        return $this->render('page/contacts.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'feedback_form' => $form->createView()
        ]);
    }

    #[Route('/shipping', name: 'shipping')]
    public function shipping(Request $request, SessionInterface $session): Response
    {
        $breadcrumbs = [
            ['href' => '/shipping', 'text' => 'Доставка']
        ];
        return $this->render('page/shipping.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            
        ]);
    }

    #[Route('/service', name: 'service')]
    public function services(Request $request, SessionInterface $session): Response
    {
        $breadcrumbs = [
            ['href' => '/service', 'text' => 'Сервис']
        ];
        return $this->render('page/service.html.twig', [
            'breadcrumbs' => $breadcrumbs
        ]);
    }
}
