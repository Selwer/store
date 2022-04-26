<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Security;
use App\Service\UpdateUser1C;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CartOrder1C
{
    private $userRepository;
    private $cartRepository;
    private $productRepository;
    private $orderRepository;
    private $entityManager;
    private $security;
	private $updateUser1C;
	private $params;

    public function __construct(
        UserRepository $userRepository, 
        CartRepository $cartRepository, 
        ProductRepository $productRepository, 
        OrderRepository $orderRepository, 
        EntityManagerInterface $entityManager, 
        Security $security,
		UpdateUser1C $updateUser1C,
		ParameterBagInterface $params
    ) {
        $this->userRepository = $userRepository;
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->entityManager = $entityManager;
        $this->security = $security;
		$this->updateUser1C = $updateUser1C;
		$this->params = $params;
    }

    public function CartCheck1C() 
    {
        try {

			$user = $this->security->getUser();

			if ($user) {
				$userId = $user->getId();
				$userGuid = $user->getGuid();
				$userPriceLevel = $user->getPriceLevel();
			} else {
				return false;
			}

			$products = $this->cartRepository->getCartProducts('', 0, $userPriceLevel, $userId);

			ini_set('soap.wsdl_cache_enabled', 0);

            $client = new \SoapClient($this->params->get('app.soap_auto'), ['trace' => 1]);

			$productCheck = [];
			$params = [];
			$params['param']['id'] = $userGuid;
			$params['param']['parts'] = [];
			$i = 0;
            foreach ($products as $product) {
                $params['param']['parts']['partDetails'][$i]['stock'] = 'ds';
				$params['param']['parts']['partDetails'][$i]['partId'] = $product['product_guid'];
				$params['param']['parts']['partDetails'][$i]['partQuantity'] = 1;
				$i++;

				$productCheck[$product['product_guid']] = [
					'id' => $product['product_id'],
					'price' => $product['product_price'],	
					'price0' => $product['product_price0'],		
					'price1' => $product['product_price1'],		
					'price2' => $product['product_price2'],	
					'quantity' => $product['product_quantity']
				];
            }

			$result = $client->BasketCheck($params);

			$error = '';
			if (isset($result->return->error)) {
				$error = $result->return->error; // получение ошибки
			}

			if (empty($error) && $result->return) {
				$data = [];
				$data = (array) $result->return;

				$levelPrice1C = $data['prices'];

				// Если уровень цен у клиента поменялся, то обновляем его данные
				if ($levelPrice1C != $userPriceLevel) {
					$this->updateUser1C->updateUser1C($userId);
				}

				if (!empty($data['parts'])) {
					$parts = (array) $data['parts'];
					if (!empty($parts['partDetails'])) {
						foreach ($parts['partDetails'] as $partObj) {
							$part = (array) $partObj;
							if (!empty($part['partId']) && isset($productCheck[$part['partId']])) {
								if ((!empty($productCheck[$part['partId']][$levelPrice1C]) 
									&& $productCheck[$part['partId']][$levelPrice1C] != $part['partPrice'])
									|| (!empty($productCheck[$part['partId']]['quantity']) 
									&& $productCheck[$part['partId']]['quantity'] != $part['partQuantity'])
								) {
									// То здесь обновляем товар
									$this->productRepository->udpateProduct($productCheck[$part['partId']]['id'], [
										'quantity' => (int) $part['partQuantity'],
										$levelPrice1C => (float) $part['partPrice'],
									]);
								}
							}
						}
					}
				}

				return true;
			}

		} catch (\SoapFault $e) {
			$err = $e->getMessage();
			return false; 
		}

		return false;
    }
}