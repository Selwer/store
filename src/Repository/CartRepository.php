<?php

namespace App\Repository;

use App\Entity\Cart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Cart|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cart|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cart[]    findAll()
 * @method Cart[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    public function getCartProducts($sessionId, $podZapros, $priceLevel = 'price', $userId = null)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT cart.id as cart_id, cart.user_id as cart_user_id, cart.session_id as cart_session_id, 
            cart.product_id as cart_product_id, cart.quantity as cart_quantity, cart.pod_zapros as cart_pod_zapros,
            product.id as product_id, product.guid as product_guid, product.code as product_code,
            product.type as product_type, product.name as product_name,
            product.sku as product_sku, product.brand as product_brand, product.quantity as product_quantity,
            product.price as product_price, product.price0 as product_price0, 
            product.price1 as product_price1, product.price2 as product_price2, 
            product." . $priceLevel . " as product_price_user, product.storage as product_storage,
            product.weight as product_weight, product.volume as product_volume
            FROM cart 
            LEFT JOIN product ON cart.product_id = product.id
            WHERE cart.pod_zapros = :pod_zapros AND product.active = true
            ";
        if ($userId != null) {
            $sql .= " AND user_id = :user_id";
            $sql .= " ORDER BY cart.id DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        } else {
            $sql .= " AND session_id = :session_id";
            $sql .= " ORDER BY cart.id DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':session_id', $sessionId, \PDO::PARAM_STR);
        }
        $stmt->bindValue(':pod_zapros', $podZapros, \PDO::PARAM_INT);
        $res = $stmt->executeQuery();

        return $res->fetchAllAssociative();
    }

    public function getCartCountProducts($sessionId, $userId = null)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT SUM(cart.quantity) as count
            FROM cart
            LEFT JOIN product ON cart.product_id = product.id
            WHERE product.active = true
            ";
        if ($userId != null) {
            $sql .= " AND user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        } else {
            $sql .= " AND session_id = :session_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':session_id', $sessionId, \PDO::PARAM_STR);
        }
        $res = $stmt->executeQuery();

        return $res->fetchAssociative()['count'];
    }

    public function addProduct($sessionId, $productId, $quantity, $podZapros, $userId = null)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            INSERT INTO cart VALUES (DEFAULT, :user_id, :session_id, :product_id, :quantity, NOW(), :pod_zapros)
            ';

        $stmt = $conn->prepare($sql);
        $stmt->executeStatement([
            'session_id' => $sessionId,
            'product_id' => $productId,
            'quantity' => $quantity,
            // 'user_id' => isset($userId) ? $userId : null,
            'user_id' => $userId,
            'pod_zapros' => $podZapros
        ]);

        return $conn->lastInsertId();
    }

    public function updateCartProduct($sessionId, $productId, $quantity, $price, $podZapros, $userId = null)
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();
        $qb->update('"cart"')
            ->set('quantity', ':quantity')
            ->setParameter('quantity', $quantity, 'integer');

        if (!empty($price)) {
            $qb->set('price', ':price')
                ->set('price1', ':price1')
                ->set('price2', ':price2')
                ->set('price3', ':price3')
                ->setParameter('price', $price['price'])
                ->setParameter('price1', $price['price1'])
                ->setParameter('price2', $price['price2'])
                ->setParameter('price3', $price['price3']);
        }

        if ($userId != null) {
            $qb->where('user_id =:user_id')
                ->andWhere('product_id = :product_id')
                ->andWhere('pod_zapros = :pod_zapros')
                ->setParameter('user_id', $userId)
                ->setParameter('product_id', $productId)
                ->setParameter('pod_zapros', $podZapros);
        } else {
            $qb->where('session_id = :session_id')
                ->andWhere('product_id = :product_id')
                ->andWhere('pod_zapros = :pod_zapros')
                ->setParameter('session_id', $sessionId)
                ->setParameter('product_id', $productId)
                ->setParameter('pod_zapros', $podZapros);
        }

        $qb->execute();
    }

    public function upateQuantityProduct($sessionId, $productId, $quantity, $podZapros, $userId = null)
    {
        $fieldsSql = [];
        $fieldsSql['quantity'] = (int)$quantity;
        $fieldsSql['product_id'] = (int)$productId;
        $fieldsSql['pod_zapros'] = $podZapros;
        $conn = $this->getEntityManager()->getConnection();

        if ($userId != null) {
            $fieldsSql['user_id'] = $userId;
            $sql = '
                UPDATE cart
                SET quantity = quantity + :quantity WHERE product_id = :product_id AND pod_zapros = :pod_zapros AND user_id = :user_id';
        } else {
            $fieldsSql['session_id'] = $sessionId;
            $sql = '
                UPDATE cart
                SET quantity = quantity + :quantity WHERE product_id = :product_id AND pod_zapros = :pod_zapros AND session_id = :session_id';
        }

        $stmt = $conn->prepare($sql);
        $stmt->executeStatement($fieldsSql);
    }

    public function cartOnwer($sessionId, $userId)
    {
        $conn = $this->getEntityManager()->getConnection();

        // В корзине добавляем user_id к уже добавленным товарам
        $sql = '
            UPDATE cart
            SET user_id = :user_id WHERE session_id = :session_id';

        $stmt = $conn->prepare($sql);
        $stmt->executeStatement([
            'session_id' => $sessionId,
            'user_id' => $userId
        ]);
    }

    public function removeProduct($sessionId, $productId, $podZapros, $userId = null)
    {
        $fieldsSql = [];
        $fieldsSql['product_id'] = $productId;
        $fieldsSql['pod_zapros'] = $podZapros;
        $conn = $this->getEntityManager()->getConnection();

        if ($userId != null) {
            $fieldsSql['user_id'] = $userId;
            $sql = '
                DELETE FROM cart
                WHERE product_id = :product_id AND pod_zapros = :pod_zapros AND user_id = :user_id';
        } else {
            $fieldsSql['session_id'] = $sessionId;
            $sql = '
                DELETE FROM cart
                WHERE product_id = :product_id AND pod_zapros = :pod_zapros AND session_id = :session_id';
        }

        $stmt = $conn->prepare($sql);
        $stmt->executeStatement($fieldsSql);
    }

    public function isProduct($productId)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT count(*) as count
            FROM product WHERE id = :id
            ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':id', $productId, \PDO::PARAM_INT);
        $res = $stmt->executeQuery();

        $resB = $res->fetchAssociative();
        if (isset($resB) && !empty($resB['count']) && $resB['count'] > 0) {
            return true;
        }

        return false;
    }

    public function productIsCart($sessionId, $productId, $podZapros, $userId = null)
    {
        $fieldsSql = [];
        $fieldsSql['product_id'] = $productId;
        $fieldsSql['pod_zapros'] = $podZapros;
        $conn = $this->getEntityManager()->getConnection();

        if ($userId != null) {
            $fieldsSql['user_id'] = $userId;
            $sql = '
                SELECT count(*) as count
                FROM cart WHERE product_id = :product_id AND pod_zapros = :pod_zapros AND user_id = :user_id
                ';
        } else {
            $fieldsSql['session_id'] = $sessionId;
            $sql = '
                SELECT count(*) as count
                FROM cart WHERE product_id = :product_id AND pod_zapros = :pod_zapros AND session_id = :session_id
                ';
        }

        $stmt = $conn->prepare($sql);
        $res = $stmt->executeQuery($fieldsSql);

        $resB = $res->fetchAssociative();
        if (isset($resB) && !empty($resB['count']) && $resB['count'] > 0) {
            return true;
        }

        return false;
    }

    // /**
    //  * @return Cart[] Returns an array of Cart objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Cart
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
