<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

// use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
// use App\Entity\User;
use App\Service\UpdateUser1C;

class RegistrationController extends AbstractController
{
    #[Route('/registration', name: 'ap_registration')]
    public function index(): Response
    {
        $breadcrumbs = [
            ['href' => '/registration', 'text' => 'Регистрация']
        ];

        return $this->render('security/registration.html.twig', [
            'breadcrumbs' => $breadcrumbs
        ]);
    }

    #[Route('/registration/step1', name: 'registration_step1', methods: 'POST')]
    public function validate(Request $request, SessionInterface $session, UserRepository $userRepository): Response
	{
        $submittedToken = $request->request->get('ces_');
        if ($this->isCsrfTokenValid('registration', $submittedToken)) {
            $errors = [];
            if (mb_strlen(trim($request->request->get('first_name'))) < 2) {
                $errors['first_name'] = 'Заполните поле Имя';
            }
            if (mb_strlen(trim($request->request->get('last_name'))) < 2) {
                $errors['last_name'] = 'Заполните поле Фамилия';
            }
            if (!filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Заполните поле Email';
            } else {
                if ($userRepository->uniqEmailUser(trim($request->request->get('email')))) {
                    $errors['email'] = 'Пользователь с таким Email уже зарегистрирован';
                }
            }
            if (mb_strlen($request->request->get('mobile_phone')) != 18 || mb_strlen(preg_replace("/[^0-9]/", '', $request->request->get('mobile_phone'))) != 11) {
                $errors['mobile_phone'] = 'Заполните поле Мобильный телефон';
            } else {
                if ($userRepository->uniqPhoneUser(preg_replace("/[^0-9]/", '', $request->request->get('mobile_phone')))) {
                    $errors['mobile_phone'] = 'Пользователь с таким Мобильным телефоном уже зарегистрирован';
                }
            }
            if (mb_strlen(trim($request->request->get('password'))) < 2) {
                $errors['password'] = 'Заполните поле Пароль';
            }
            if (mb_strlen(trim($request->request->get('password_repeat'))) < 2) {
                $errors['password_repeat'] = 'Заполните поле Подтверждение пароля';
            }
            if ($request->request->get('password') !== $request->request->get('password_repeat')) {
                $errors['password_repeat'] = 'Пароль не совпадает с Подтверждением пароля';
            }
            if (!filter_var($request->request->get('agree'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
                $errors['agree'] = 'Прочитайте и примите согласие на обработку персональных данных';
            }

            if ($request->request->get('type_reg') === 'ur1') {
                if (mb_strlen(trim($request->request->get('inn'))) != 10) {
                    $errors['inn'] = 'Заполните поле ИНН';
                }
                if (mb_strlen(trim($request->request->get('kpp'))) != 9) {
                    $errors['kpp'] = 'Заполните поле КПП';
                }
            }
            if ($request->request->get('type_reg') === 'ur2') {
                if (mb_strlen(trim($request->request->get('inn'))) != 12) {
                    $errors['inn'] = 'Заполните поле ИНН';
                }
            }

            $dataFieldsReg = [
                'guid' => preg_replace("/[^0-9]/", '', trim($request->request->get('mobile_phone'))) . '_' . bin2hex(random_bytes(4)),
                'first_name' => trim($request->request->get('first_name')),
                'last_name' => trim($request->request->get('last_name')),
                'patronymic_name' => trim($request->request->get('patronymic_name')),
                'email' => trim($request->request->get('email')),
                'mobile_phone' => trim($request->request->get('mobile_phone')),
                'password' => trim($request->request->get('password')),
                'inn' => trim($request->request->get('inn')),
                'kpp' => trim($request->request->get('kpp')),
                'type_user' => trim($request->request->get('type_reg')),
            ];

            if ($request->request->get('type_reg') === 'ur1' || $request->request->get('type_reg') === 'ur2') {
                $dataSugget = [];
                if (empty($errors)) {
                    $sugget = $this->suggest(['query' => trim($request->request->get('inn')), 'count' => 2]);
                    if (count($sugget['suggestions']) > 0 && isset($sugget['suggestions'][0]['data']['opf']['short']) && isset($sugget['suggestions'][0]['data']['name']['full'])) {
                        $dataSugget['opf'] = $sugget['suggestions'][0]['data']['opf']['short'];
                        $dataSugget['opfname'] = $sugget['suggestions'][0]['data']['name']['full'];

                        $dataFieldsReg['opf'] = $dataSugget['opf'];
                        $dataFieldsReg['opfname'] = $dataSugget['opfname'];
                    } else {
                        $errors['innopf'] = 'Не найдено юрлицо';
                    }
                }
            }

            if (!empty($errors)) {
                return $this->json([
                    'error' => 'error',
                    'data' => $errors
                ], 200);
            } else {
                $request->getSession()->set('reg_data', $dataFieldsReg);

                if ($request->request->get('type_reg') === 'ur1' || $request->request->get('type_reg') === 'ur2') {
                    return $this->json([
                        'ok' => 'ok',
                        'type' => 'ur',
                        'data' => [
                            'opf' => $dataSugget['opf'],
                            'opfname' => $dataSugget['opfname']
                        ]
                    ], 200);
                } else {
                    return $this->json([
                        'ok' => 'ok',
                        'type' => 'fiz'
                    ], 200);
                }
            }
        }

        return $this->json([
            'error' => 'error',
            'data' => []
        ], 200);
	}

    #[Route('/registration/sendemailcode', name: 'send_email_code', methods: 'POST')]
    public function sendConfirmEmailCode(Request $request, SessionInterface $session, MailerInterface $mailer) 
    {
        $submittedToken = $request->request->get('ces_');
        if ($session->has('reg_data') && $session->get('reg_data')['email'] && $this->isCsrfTokenValid('registration', $submittedToken)) {
            // Отправляем код
            // $code = 4444;
            $code = mt_rand(1111, 9999);
            
            $emailFrom = $this->getParameter('app.email_from');
            $emailFromName = $this->getParameter('app.email_from_name');
            $domain = $this->getParameter('app.domain');
            $protocolHttp = $this->getParameter('app.protocol_http');

            $email = (new TemplatedEmail())
                ->from(new Address($emailFrom, $emailFromName))
                ->to($session->get('reg_data')['email'])
                ->replyTo(new Address($emailFrom, $emailFromName))
                ->subject('Код подтверждения Email')
                ->htmlTemplate('email/confirm.html.twig')
                ->context([
                    'code' => $code,
                    'domain' => $domain,
                    'protocol_http' => $protocolHttp
                ]);
            $mailer->send($email);
            $session->set('confirm-code-email', $code);

            return $this->json([
                'ok' => 'ok',
                'email' => $session->get('reg_data')['email']
            ], 200);
        } else {
            return $this->json([
                'error' => 'error2',
                'data' => 'Ошибка',
                'valid1' => $session->has('reg_data'),
                'valid2' => $session->get('reg_data')['email'],
                'valid3' => $this->isCsrfTokenValid('registration', $submittedToken),
            ], 200);
        }
    }

    #[Route('/registration/emailcode', name: 'valid_email_code', methods: 'POST')]
    public function validConfirmEmailCode(Request $request, SessionInterface $session) 
    {
        $submittedToken = $request->request->get('ces_');
        if ($session->has('reg_data') && $session->get('reg_data')['email'] && $this->isCsrfTokenValid('registration', $submittedToken)) {
            if ((int)$request->request->get('code') === (int)$session->get('confirm-code-email')) {
                return $this->json([
                    'ok' => 'ok',
                    'email' => 'Next step'
                ], 200);
            } else {
                return $this->json([
                    'error' => 'code',
                    'data' => 'Неверный код подтверждения Email'
                ], 200);
            }
        } else {
            return $this->json([
                'error' => 'error',
                'data' => 'Ошибка confirm email'
            ], 200);
        }
    }

    #[Route('/registration/sendphonecode', name: 'send_phone_code', methods: 'POST')]
    public function sendConfirmPhoneCode(Request $request, SessionInterface $session) 
    {
        $submittedToken = $request->request->get('ces_');
        if ($session->has('reg_data') && $session->get('reg_data')['mobile_phone'] && $this->isCsrfTokenValid('registration', $submittedToken)) {
            // Отправляем код
            // $code = 5555;
            $code = mt_rand(1111, 9999);

            $ch = curl_init();
            $arSend['login'] = '#######';
            $arSend['psw'] = '#########';
            $arSend['phones'] = $session->get('reg_data')['mobile_phone'];
            $arSend['mes'] = 'Ваш проверочный код: ' . $code;
            $arSend['charset'] = 'utf-8';
            $arSend['sender'] = '########';
            $sSend = http_build_query($arSend);

            curl_setopt($ch, CURLOPT_URL, 'https://smsc.ru/sys/send.php');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/x-www-form-urlencoded;charset=utf-8']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $sSend);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $response = curl_exec($ch);
            curl_close($ch);
            
            $session->set('confirm-code-phone', $code);

            return $this->json([
                'ok' => 'ok',
                'phone' => $session->get('reg_data')['mobile_phone']
            ], 200);
        } else {
            return $this->json([
                'error' => 'error',
                'data' => 'Ошибка confirm phone'
            ], 200);
        }
    }

    #[Route('/registration/phonecode', name: 'valid_phone_code', methods: 'POST')]
    public function validConfirmPhoneCode(Request $request, SessionInterface $session, UserRepository $userRepository, UpdateUser1C $updateUser1C) 
    {
        $submittedToken = $request->request->get('ces_');
        if ($session->has('reg_data') && $session->get('reg_data')['mobile_phone'] && $this->isCsrfTokenValid('registration', $submittedToken)) {
            if ((int)$request->request->get('code') === (int)$session->get('confirm-code-phone')) {
                // Добавляем пользователя
                if ($userId = $updateUser1C->addUser($session->get('reg_data'))) {
                    // Регистрируем в 1C
                    if ($result1C = $updateUser1C->regUser1C($userId, $session->get('reg_data'))) {
                        return $this->json([
                            'ok' => 'ok',
                            'phone' => 'Next step'
                        ], 200);
                    } else {
                        return $this->json([
                            'error' => 'error',
                            'data' => 'Ошибка reg1'
                        ], 200);
                    }
                } else {
                    return $this->json([
                        'error' => 'error',
                        'data' => 'Ошибка reg2'
                    ], 200);
                }
                return $this->json([
                    'ok' => 'ok',
                    'phone' => 'Next step'
                ], 200);
            } else {
                return $this->json([
                    'error' => 'code',
                    'data' => 'Неверный код подтверждения Мобильного телефона'
                ], 200);
            }
        } else {
            return $this->json([
                'error' => 'error',
                'data' => 'Ошибка reg3'
            ], 200);
        }
    }

    #[Route('/registration/sendreg', name: 'send_reg', methods: 'POST')]
    public function sendReg(Request $request, SessionInterface $session, UserRepository $userRepository, UpdateUser1C $updateUser1C) 
    {
        $submittedToken = $request->request->get('ces_');
        if ($session->has('reg_data') && $session->get('reg_data')['mobile_phone'] && $this->isCsrfTokenValid('registration', $submittedToken)) {
            // Добавляем пользователя
            if ($userId = $updateUser1C->addUser($session->get('reg_data'))) {
                // Регистрируем в 1C
                if ($result1C = $updateUser1C->regUser1C($userId, $session->get('reg_data'))) {
                    return $this->json([
                        'ok' => 'ok',
                        'phone' => 'Next step'
                    ], 200);
                } else {
                    return $this->json([
                        'error' => 'error',
                        'data' => 'Ошибка reg1'
                    ], 200);
                }
            } else {
                return $this->json([
                    'error' => 'error',
                    'data' => 'Ошибка reg2'
                ], 200);
            }
            return $this->json([
                'ok' => 'ok',
                'phone' => 'Next step'
            ], 200);
        } else {
            return $this->json([
                'error' => 'error',
                'data' => 'Ошибка reg3'
            ], 200);
        }
    }

    #[Route('/registration/suggetapi', name: 'suggetapi')]
    public function suggetapi(Request $request, SessionInterface $session): Response
	{
		$dadata = [];
		if ($request->request->get('type-cl') === 'ur') {
			if (mb_strlen(trim($request->request->get('inn'))) == 10) {
				$sugget = $this->suggest(['query' => trim($request->request->get('inn')), 'count' => 2]);
				if (count($sugget['suggestions']) > 0 && isset($sugget['suggestions'][0]['data']['opf']['short']) && isset($sugget['suggestions'][0]['data']['name']['full'])) {
					$dadata['opf'] = $sugget['suggestions'][0]['data']['opf']['short'];
					$dadata['opfname'] = $sugget['suggestions'][0]['data']['name']['full'];
				}
			}
		} elseif ($request->request->get('type-cl') === 'ur2') {
			if (mb_strlen(trim($$request->request->get('inn'))) == 12) {
				$sugget = $this->suggest(['query' => trim($$request->request->get('inn')), 'count' => 2]);
				if (count($sugget['suggestions']) > 0 && isset($sugget['suggestions'][0]['data']['opf']['short']) && isset($sugget['suggestions'][0]['data']['name']['full'])) {
					$dadata['opf'] = $sugget['suggestions'][0]['data']['opf']['short'];
					$dadata['opfname'] = $sugget['suggestions'][0]['data']['name']['full'];
				}
			}
		}

        if (!empty($dadata)) {
            return $this->json([
                'ok' => 'ok',
                'data' => $dadata
            ], 200);
        } else {
            return $this->json([
                'error' => 'error'
            ], 200);
        }
	}

    public function suggest($fields)
    {
        $result = false;
        if ($ch = curl_init("https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party")) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Token ############################'
            ]);
            curl_setopt($ch, CURLOPT_POST, 1);
            // json_encode
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result, true);
		}
		
        return $result;
	}
}
