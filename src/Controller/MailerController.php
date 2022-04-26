<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class MailerController extends AbstractController
{
    #[Route('/mailtest', name: 'mailtest')]
    public function sendEmail(MailerInterface $mailer): Response
    {
        $email = (new TemplatedEmail())
            ->from('webmaster@mail.test')
            ->to(new Address('mail@mail.test'))
            ->subject('Пришла заявка с сайта')

            // path of the Twig template to render
            ->htmlTemplate('email/test.html.twig')

            // pass variables (name => value) to the template
            ->context([
                'expiration_date' => new \DateTime('+7 days'),
                'username' => 'Юрий',
            ]);

        $mailer->send($email);

        return new Response('OK! SEND EMAIL');
    }
}