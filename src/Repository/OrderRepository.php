<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    private $security;
    private $params;

    public function __construct(ManagerRegistry $registry, Security $security, ParameterBagInterface $params)
    {
        parent::__construct($registry, Order::class);
        $this->security = $security;
        $this->params = $params;
    }

    public function addOrder($fields)
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();
        // $conn->setAutoCommit(false);

        try {
            $user = $this->security->getUser();

			if ($user) {
				$userId = $user->getId();
				$userGuid = $user->getGuid();
				$userPriceLevel = $user->getPriceLevel();
			} else {
                throw new \Exception('User not auth');
            }

            $sql = "
                SELECT cart.id as cart_id, cart.user_id as cart_user_id, cart.session_id as cart_session_id, 
                cart.product_id as cart_product_id, cart.quantity as cart_quantity,
                product.id as product_id, product.guid as product_guid, product.type as product_type, product.name as product_name,
                product.sku as product_sku, product.brand as product_brand, product.quantity as product_quantity, 
                product.price as product_price, product.price0 as product_price0, product.price1 as product_price1, 
                product.price2 as product_price2, product.storage as product_storage,
                product.weight as product_weight, product.volume as product_volume
                FROM cart 
                LEFT JOIN product ON cart.product_id = product.id
                WHERE cart.user_id = :user_id AND cart.pod_zapros = false ORDER BY cart.id DESC
                ";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
            
            $res = $stmt->executeQuery();
            $products = $res->fetchAllAssociative();

            $summ = 0;
            $weight = 0;
            $productsAr = [];

            $params = [];
			$params['param']['id'] = $userGuid;
			$params['param']['parts'] = [];
            $i = 0;
            // Уровень цен $userPriceLevel
            foreach ($products as $product) {
                $productsAr['products'][] = [
                    'product_id' => $product['product_id'],
                    'name' => $product['product_name'],
                    'sku' => $product['product_sku'],
                    'quantity' => $product['cart_quantity'],
                    'price' => $product['product_' . $userPriceLevel],
                    'weight' => $product['product_weight'],
                    'total' => $product['product_' . $userPriceLevel] * $product['cart_quantity']
                ];
                $summ += $product['product_' . $userPriceLevel] * $product['cart_quantity'];
                $weight += $product['product_weight'];

                $params['param']['parts']['partDetails'][$i]['stock'] = 'ds';
				$params['param']['parts']['partDetails'][$i]['partId'] = $product['product_guid'];
                // $params['param']['parts']['partDetails'][$i]['partPrice'] = $product['product_' . $userPriceLevel];
				$params['param']['parts']['partDetails'][$i]['partQuantity'] = $product['cart_quantity'];
				$i++;
            }

            $params['param']['shippingAndPayment']['typeShipping'] = $fields['shipping_type'];
            $params['param']['shippingAndPayment']['priceShipping'] = $fields['shipping_price'];
            $params['param']['shippingAndPayment']['typePayment'] = $fields['payment_type'];
            $params['param']['shippingAndPayment']['address'] = $fields['shipping_address'];
            $params['param']['shippingAndPayment']['comment'] = $fields['comment'];

            ini_set('soap.wsdl_cache_enabled', 0);

            $client = new \SoapClient($this->params->get('app.soap_auto'), ['trace' => 1]);

			$result = $client->CreateOrder($params);

			$error = '';
			if (isset($result->return->error)) {
				$error = $result->return->error; // получение ошибки
                throw new \Exception('SOAP error:' . $error);
			}

            // Создали заказ в 1С
			if (empty($error) && $result->return) {
				$data = [];
				$data = (array) $result->return->orderDetails;
                $orderGuid = (string) $data['orderNumber'];

				$fieldsExecute = [
                    'user_id' => $userId,
                    'status' => $fields['status'],
                    'total' => $summ,
                    'comment' => $fields['comment'],
                    'shipping_type' => $fields['shipping_type'],
                    'shipping_address' => $fields['shipping_address'],
                    'payment_type' => $fields['payment_type'],
                    'shipping_price' => $fields['shipping_price'],
                    'weight' => $weight,
                    'products' => json_encode($productsAr),
                    'guid' => $orderGuid,
                    'pod_zapros' => $fields['pod_zapros']
                ];

                $sql = '
                    INSERT INTO "order" VALUES (DEFAULT, NOW(), NOW(), :user_id, :status, 
                        :total, :comment, :shipping_type, :shipping_address, :payment_type, :shipping_price, 
                        :weight, :products, :guid, NULL, :pod_zapros)
                    ';

                $stmt = $conn->prepare($sql);
                $stmt->executeStatement($fieldsExecute);
                
                if ($orderId = $conn->lastInsertId()) {
                    // Уменьшаем остатки
                    foreach ($products as $product) {
                        $fieldsExecute = [];
                        $fieldsExecute = [
                            'quantity' => ($product['product_quantity'] - $product['cart_quantity'] < 0 ? 0 : $product['product_quantity'] - $product['cart_quantity']),
                            'product_id' => $product['product_id']
                        ];
                        $sql = '
                            UPDATE product
                            SET quantity = :quantity WHERE id = :product_id';

                        $stmt = $conn->prepare($sql);
                        $stmt->executeStatement($fieldsExecute);
                    }

                    // Удаляем корзину
                    $sql = '
                        DELETE FROM "cart"
                        WHERE user_id = :user_id AND cart.pod_zapros = false';

                    $stmt = $conn->prepare($sql);
                    $stmt->executeStatement(['user_id' => $userId]);
                    
                    $conn->commit();

                    return ['ok' => 'ok', 'orderId' => $orderGuid];

                } else {
                    throw new \Exception('Ошибка! Во время выполнения произошла ошибка! {not create order}');
                }
			} else {
                throw new \Exception('Ошибка! Во время выполнения произошла ошибка! {not create order 1c}');
            }

        } catch (\Exception $e) {
            $conn->rollBack();
            return ['error' => $e->getMessage(), 'line' => $e->getLine(), 'trace' => $e->getTrace()];
        }
    }

    public function addOrderPodZapros($fields)
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();
        // $conn->setAutoCommit(false);

        try {

            $user = $this->security->getUser();

			if ($user) {
				$userId = $user->getId();
				$userGuid = $user->getGuid();
				$userPriceLevel = $user->getPriceLevel();
			} else {
                throw new \Exception('User not auth');
            }

            $sql = "
                SELECT cart.id as cart_id, cart.user_id as cart_user_id, cart.session_id as cart_session_id, 
                cart.product_id as cart_product_id, cart.quantity as cart_quantity,
                product.id as product_id, product.guid as product_guid, product.type as product_type, product.name as product_name,
                product.sku as product_sku, product.brand as product_brand, product.quantity as product_quantity, 
                product.price as product_price, product.price0 as product_price0, product.price1 as product_price1, 
                product.price2 as product_price2, product.storage as product_storage,
                product.weight as product_weight, product.volume as product_volume
                FROM cart 
                LEFT JOIN product ON cart.product_id = product.id
                WHERE cart.user_id = :user_id AND cart.pod_zapros = true ORDER BY cart.id DESC
                ";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
            
            $res = $stmt->executeQuery();
            $products = $res->fetchAllAssociative();

            $summ = 0;
            $weight = 0;
            $productsAr = [];

            $params = [];
			$params['param']['id'] = $userGuid;
			$params['param']['parts'] = [];
            $i = 0;
            // Уровень цен $userPriceLevel
            foreach ($products as $product) {
                $productsAr['products'][] = [
                    'product_id' => $product['product_id'],
                    'name' => $product['product_name'],
                    'sku' => $product['product_sku'],
                    'quantity' => $product['cart_quantity'],
                    'price' => $product['product_' . $userPriceLevel],
                    'weight' => $product['product_weight'],
                    'total' => $product['product_' . $userPriceLevel] * $product['cart_quantity']
                ];
                $summ += $product['product_' . $userPriceLevel] * $product['cart_quantity'];
                $weight += $product['product_weight'];

                $params['param']['parts']['partDetails'][$i]['stock'] = 'gs';
				$params['param']['parts']['partDetails'][$i]['partId'] = $product['product_guid'];
				$params['param']['parts']['partDetails'][$i]['partQuantity'] = $product['cart_quantity'];
				$i++;
            }

            $params['param']['shippingAndPayment']['typeShipping'] = $fields['shipping_type'];
            $params['param']['shippingAndPayment']['priceShipping'] = $fields['shipping_price'];
            $params['param']['shippingAndPayment']['typePayment'] = $fields['payment_type'];
            $params['param']['shippingAndPayment']['address'] = $fields['shipping_address'];
            $params['param']['shippingAndPayment']['comment'] = $fields['comment'];

            ini_set('soap.wsdl_cache_enabled', 0);

            $client = new \SoapClient($this->params->get('app.soap_auto'), ['trace' => 1]);

			$result = $client->CreateOrder($params);

			$error = '';
			if (isset($result->return->error)) {
				$error = $result->return->error; // получение ошибки
                throw new \Exception('SOAP error:' . $error);
			}

            // Создали заказ в 1С
			if (empty($error) && $result->return) {
				$data = [];
				$data = (array) $result->return->orderDetails;
                $orderGuid = (string) $data['orderNumber'];

				$fieldsExecute = [
                    'user_id' => $userId,
                    'status' => $fields['status'],
                    'total' => $summ,
                    'comment' => $fields['comment'],
                    'shipping_type' => $fields['shipping_type'],
                    'shipping_address' => $fields['shipping_address'],
                    'payment_type' => $fields['payment_type'],
                    'shipping_price' => $fields['shipping_price'],
                    'weight' => $weight,
                    'products' => json_encode($productsAr),
                    'guid' => $orderGuid,
                    'pod_zapros' => $fields['pod_zapros']
                ];

                $sql = '
                    INSERT INTO "order" VALUES (DEFAULT, NOW(), NOW(), :user_id, :status, 
                        :total, :comment, :shipping_type, :shipping_address, :payment_type, :shipping_price, 
                        :weight, :products, :guid, NULL, :pod_zapros)
                    ';

                $stmt = $conn->prepare($sql);
                $stmt->executeStatement($fieldsExecute);
                
                if ($orderId = $conn->lastInsertId()) {
                    // Удаляем всю корзину, т.к. этот метод вызывается вторым по счету
                    $sql = '
                        DELETE FROM "cart"
                        WHERE user_id = :user_id AND cart.pod_zapros = true';

                    $stmt = $conn->prepare($sql);
                    $stmt->executeStatement(['user_id' => $userId]);
                    
                    $conn->commit();

                    return ['ok' => 'ok', 'orderId' => $orderGuid];

                } else {
                    throw new \Exception('Ошибка! Во время выполнения произошла ошибка! {not create order}');
                }
			} else {
                throw new \Exception('Ошибка! Во время выполнения произошла ошибка! {not create order 1c}');
            }

        } catch (\Exception $e) {
            $conn->rollBack();
            return ['error' => $e->getMessage(), 'line' => $e->getLine(), 'trace' => $e->getTrace()];
        }
    }

    // /**
    //  * @return Order[] Returns an array of Order objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Order
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
