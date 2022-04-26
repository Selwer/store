<?php

namespace App\Service;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UpdateUser1C
{
    private $userRepository;
    private $entityManager;
    private $security;
    private $params;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager, Security $security, ParameterBagInterface $params)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->params = $params;
    }

    public function addUser($fields) 
    {
        $fieldsInsert = $fields;
        $fieldsInsert['roles'] = '["ROLE_USER"]';
        $fieldsInsert['password'] = $this->userRepository->getHashPassword($fields['password']);
        $fieldsInsert['mobile_phone'] = preg_replace("/[^0-9]/", '', $fields['mobile_phone']);
        $fieldsInsert['certified'] = false;
        $fieldsInsert['blocked'] = false;
        $fieldsInsert['checked'] = false;
        if (isset($fields['opf']) && isset($fields['opfname'])) {
            $fieldsInsert['opfname'] = $fields['opf'] . ' ' . $fields['opfname'];
        }
        
        $userId = $this->userRepository->addUser($fieldsInsert);

        return $userId;
    }

    public function regUser1C($userId, $fields) 
    {
        try {
            ini_set('soap.wsdl_cache_enabled', 0);
            // Создание SOAP-клиента

            $client = new \SoapClient($this->params->get('app.soap_auto'), ['trace' => 1]);

            $params = [];
            $params['param'] = [
                'id' => $fields['guid'],
                'userType' => ($fields['type_user'] == 'fiz' ? 'ФЛ' : 'ЮЛ'),
                'mobilePhone' => substr(preg_replace("/[^0-9]/", '', $fields['mobile_phone']), 1),
                'firstName' => $fields['first_name'],
                'familyName' => $fields['last_name'],
                'secondName' => $fields['patronymic_name'],
                'email' => $fields['email']
            ];
            if ($params['param']['userType'] == 'ЮЛ') {
                $params['param']['inn'] = $fields['inn'];
                if (isset($fields['kpp']) && !empty($fields['kpp'])) {
                    $params['param']['kpp'] =  $fields['kpp'];
                }
                $params['param']['opf'] = $fields['opf'];
                $params['param']['orgName'] = $fields['opfname'];
            }

            $result = $client->UserReg($params);
            $error = '';
            // echo '<pre>result:'; print_r($result); echo '</pre>';
            if (isset($result->return->error)) {
                $error = $result->return->error;
            }
            // echo '<pre>ОШИБКА:'; print_r($error); echo '</pre>';
            if (empty($error) && $result->return) {
                $data2 = [];
                $data2 = (array)$result->return;

                $data['certified'] = intval(filter_var(strval($data2['certified']), FILTER_VALIDATE_BOOLEAN));
                $data['blocked'] = intval(filter_var(strval($data2['blocked']), FILTER_VALIDATE_BOOLEAN));
                $data['checked'] = 0;
                // echo '<pre>РЕЗУЛЬТ:'; print_r($data); echo '<pre>';
                $this->updateUser($userId, $data);

                return true;
            }
        } catch (\SoapFault $e) {
			$err = $e->getMessage();
			return false; 
		}

		return false;
    }

    public function updateUser($userId, $fields) 
    {
        $fieldsUpdate = $fields;
        if ($fields['certified']) {
            $fieldsUpdate['roles'] = '["ROLE_USER","ROLE_CERTIFIED"]';
            if (!empty($fields['opf']) && !empty($fields['opfname'])) {
                $fieldsUpdate['opfname'] = $fields['opf'] . ' ' . $fields['opfname'];
            }
        } else {
            $fieldsUpdate['roles'] = '["ROLE_USER"]';
        }
        $fieldsUpdate['certified'] = $fields['certified'];
        $fieldsUpdate['blocked'] = $fields['blocked'];
        $fieldsUpdate['checked'] = $fields['checked'];
        if (!empty($fields['error'])) {
            $fieldsUpdate['message'] = $fields['error'];
        }
        
        $this->userRepository->updateUser($userId, $fieldsUpdate);
    }

    public function updateUser1C($userId) 
    {
        try {
            $user = $this->userRepository->getUserById($userId);

			ini_set('soap.wsdl_cache_enabled', 0);

            $client = new \SoapClient($this->params->get('app.soap_auto'), ['trace' => 1]);

			$params = [];
			$params['param'] = $user['guid'];

			$result = $client->UserInfo($params); // проверка наличия пользователя в базе

			$error = '';
			if (isset($result->return->error)) {
				$error = $result->return->error; // получение ошибки
			}

			if (empty($error) && $result->return) {
				$data = [];
				$data = (array)$result->return;
				
				$data['certified'] = intval(filter_var(strval($data['certified']), FILTER_VALIDATE_BOOLEAN));
				$data['blocked'] = intval(filter_var(strval($data['blocked']), FILTER_VALIDATE_BOOLEAN));
                if ($data['prices']) {
                    $data['price_level'] = $data['prices'];
                }

				if (!$data['certified'] && !$data['blocked']) {
					$data['checked'] = 0;
				} else {
					$data['checked'] = 1;
				}
				if ($data['certified']) {
					if ($this->needUpdateUserInfo($userId, $data)) {
						$this->updateUser($userId, $data);

						return true;
					}
				} else {
					return true;
				}
			}
		} catch (\SoapFault $e) {
			$err = $e->getMessage();
			return false; 
		}

		return false;
    }

    public function needUpdateUserInfo($userId, $data) 
	{
        $user = $this->userRepository->getUserById($userId);

		if ((isset($data['certified']) && $user['certified'] != $data['certified']) 
			|| (isset($data['blocked']) && $user['blocked'] != $data['blocked'])
			|| (isset($data['prices']) && $user['price_level'] != $data['prices']) 
			|| (isset($data['discount']) && $user['discount'] != $data['discount'])
			|| (isset($data['inn']) && $user['inn'] != $data['inn']) 
			|| (isset($data['kpp']) && $user['kpp'] != $data['kpp'])
			|| (isset($data['opf']) && isset($data['orgName']) && !empty($data['opf']) 
                && !empty($data['orgName']) && $user['opfname'] != $data['opf'] . ' ' . $data['orgName']) 
			|| (isset($data['checked']) && $user['checked'] != $data['checked'])
			|| (isset($data['error']) && $user['message'] != $data['error'])
		) {
			return true;
		}
		return false;
	}
}