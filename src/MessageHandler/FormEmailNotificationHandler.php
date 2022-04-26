<?php

namespace App\MessageHandler;

use App\Repository\FormRepository;
use App\Message\FormEmailNotification;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FormEmailNotificationHandler implements MessageHandlerInterface
{

    private $mailer;
    private $params;
    private $formRepository;

    public function __construct(MailerInterface $mailer, ParameterBagInterface $params, FormRepository $formRepository)
    {
        $this->mailer = $mailer;
        $this->params = $params;
        $this->formRepository = $formRepository;
    }

    public function __invoke(FormEmailNotification $data)
    {
        $emailFrom = $this->params->get('app.email_from');
        $emailFromName = $this->params->get('app.email_from_name');
        $domain = $this->params->get('app.domain');
        $protocolHttp = $this->params->get('app.protocol_http');

        $formRes = $this->formRepository->find($data->getId());
        if ($formRes) {

            $toAddresses = [];
            $subject = '';
            switch ($formRes->getType()) {
                case 'feedback':
                    $toAddresses = ['mail@mail.test'];
                    $subject = 'Заявка с сайта (Обратная связь)';
                    break;

                default:
                    break;
            }

            $fields = $formRes->getFields();
            if (!empty($toAddresses)) {
                $email = (new TemplatedEmail())
                ->from(new Address($emailFrom, $emailFromName))
                ->to(...$toAddresses)
                ->replyTo(new Address($emailFrom, $emailFromName))
                ->subject($subject)
                ->htmlTemplate('email/feedback.html.twig')
                ->context([
                    'formName' => $fields['name'],
                    'formEmail' => $fields['email'],
                    'formPhone' => $fields['phone'],
                    'formComment' => $fields['comment'],
                    'domain' => $domain,
                    'protocol_http' => $protocolHttp
                ]);

                if ($this->mailer->send($email)) {
                    $this->formRepository->updateDataForm($formRes['id']);
                }
            }
        }
    }
}