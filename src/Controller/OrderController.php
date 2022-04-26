<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Service\CartOrder1C;

class OrderController extends AbstractController
{
    #[Route('/order', name: 'order')]
    public function index(Request $request, SessionInterface $session, ProductRepository $productRepository, 
        OrderRepository $orderRepository, CartRepository $cartRepository, EntityManagerInterface $entityManager,
        CartOrder1C $cartOrder1C
    ): Response 
    {
        $errors = [];
        $orders = [];
        $breadcrumbs = [
            ['href' => '/order', 'text' => 'Оформление заказа']
        ];

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $isCertified = $this->isGranted('ROLE_CERTIFIED');
        if (!$isCertified) {
            return $this->render('order/denied.html.twig', [
                'breadcrumbs' => $breadcrumbs
            ]);
        }
        $user = $this->getUser();
        $userId = $user->getId();
        $userPriceLevel = $user->getPriceLevel();

        $cartOrder1C->CartCheck1C();

        $quantity = [];
        $quantityPodZapros = [];

        $products = $cartRepository->getCartProducts($request->getSession()->get('cart_user_id'), 0, $userPriceLevel, $userId);
        $productsPodZapros = $cartRepository->getCartProducts($request->getSession()->get('cart_user_id'), 1, $userPriceLevel, $userId);
        foreach ($products as $product) {
            $quantity[$product['product_id']] = $product['cart_quantity'];
        }
        foreach ($productsPodZapros as $productZ) {
            $quantityPodZapros[$productZ['product_id']] = $productZ['cart_quantity'];
        }

        /*
        Логика: 
        Товар делится как бы на две корзины, одна в которой есть все товары из 
        наличия на складе, вторая под запрос, если в корзину добавили товаров ольшем, чем есть в наличии
        */
        if ($quantity == null) {
            $quantity = [];
        }
        if ($quantityPodZapros == null) {
            $quantityPodZapros = [];
        }

        foreach ($quantity as $productId => $count) {
            $count = intval($count);
            $product = $productRepository->getProductById($productId, $userPriceLevel);
            if (!empty($product['id'])) {

                if ($count > $product['quantity']) {
                    $errors[$productId]['text'] = 'Внимание! Был создан запрос с недостающим кол-вом товара';
                    $errors[$productId]['value'] = $count;
                }

                // Если кол-во товара из корзины больше, чем есть в наличии, то проверяем, есть ли уже этот товар под заказ,
                // если есть уже под заказ, то увеличиваем его значением в наличие на разность (кол-во в корзине минус кол-во товара в наличие).
                // Если не было товара под заказ, то создаем новый с кол-вом как выше (кол-во в корзине минус кол-во товара в наличие).
                if ($count > $product['quantity']) {
                    if (!empty($quantityPodZapros[$productId])) {
                        $quantityPodZapros[$productId] = $quantityPodZapros[$productId] + ($count - $product['quantity']);
                    } else {
                        $quantityPodZapros[$productId] = ($count - $product['quantity']);
                    }
                }
                // Если кол-во товара из корзины равно нулю, то удаляем товар из корзины (не под запрос)
                if ($count <= 0) {
                    $cartRepository->removeProduct($request->getSession()->get('cart_user_id'), $productId, 0, $userId);

                // Если кол-во товара на складе равно нулю, то удаляем товар из корзины (не под запрос)
                } elseif ($product['quantity'] <= 0) {
                    $cartRepository->removeProduct($request->getSession()->get('cart_user_id'), $productId, 0, $userId);

                // Обновляем кол-во товара в корзине (не под запрос)
                } else {
                    // Обновляем кол-во товара в корзине (не под запрос)
                    if ($count > $product['quantity']) {
                        $cartRepository->updateCartProduct($request->getSession()->get('cart_user_id'), $productId, $product['quantity'], $prices = [], 0, $userId);
                    } else {
                        $cartRepository->updateCartProduct($request->getSession()->get('cart_user_id'), $productId, $count, $prices = [], 0, $userId);
                    }
                }

            // Товар вообще не найден, удаляем его из корзины (не под запрос)
            } else {
                $cartRepository->removeProduct($request->getSession()->get('cart_user_id'), $productId, 0, $userId);
            }
        }
        
        foreach ($quantityPodZapros as $productIdZ => $countZ) {
            $countZ = intval($countZ);
            $productZ = $productRepository->getProductById($productIdZ, $userPriceLevel);
            if (!empty($productZ['id'])) {

                // Если кол-во товара из корзины равно нулю, то удаляем товар из корзины (под запрос)
                if ($countZ <= 0) {
                    $cartRepository->removeProduct($request->getSession()->get('cart_user_id'), $productIdZ, 1, $userId);

                // Обновляем или добавляем новый товар в корзине (под запрос)
                } else {
                    if ($cartRepository->productIsCart($request->getSession()->get('cart_user_id'), $productIdZ, 1, $userId)) {
                        $cartRepository->updateCartProduct($request->getSession()->get('cart_user_id'), $productIdZ, $countZ, $prices = [], 1, $userId);
                    } else {
                        $cartRepository->addProduct($request->getSession()->get('cart_user_id'), $productIdZ, $countZ, 1, $userId);
                    }
                }

            // Товар вообще не найден, удаляем его из корзины (не под запрос)
            } else {
                $cartRepository->removeProduct($request->getSession()->get('cart_user_id'), $productIdZ, 1, $userId);
            }
        }
        
        $products = $cartRepository->getCartProducts($request->getSession()->get('cart_user_id'), 0, $userPriceLevel, $userId);
        $productsPodZapros = $cartRepository->getCartProducts($request->getSession()->get('cart_user_id'), 1, $userPriceLevel, $userId);

        if ($request->request->has('order') && $request->request->get('order') == 'yes') {

            $shipping = $request->request->get('shipping1');
            $address = $request->request->get('address1');
            $comment = $request->request->get('comment1');

            $shipping2 = $request->request->get('shipping2');
            $address2 = $request->request->get('address2');
            $comment2 = $request->request->get('comment2');

            $fields = [
                'status' => 1,
                'comment' => $comment,
                'shipping_type' => $shipping,
                'shipping_address' => $address,
                'payment_type' => 'bank',
                'shipping_price' => 0,
                'pod_zapros' => 0
            ];

            $fields2 = [
                'status' => 1,
                'comment' => 'Под запрос! ' . $comment2,
                'shipping_type' => $shipping2,
                'shipping_address' => $address2,
                'payment_type' => 'bank',
                'shipping_price' => 0,
                'pod_zapros' => 1
            ];

            if (count($products) > 0) {
                // Создаем основной заказ
                $orderRes = $orderRepository->addOrder($fields);
            }

            if (count($productsPodZapros) > 0) {
                // Создаем под заказ
                $order2Res = $orderRepository->addOrderPodZapros($fields2);
            }

            // echo '<pre>';
            // print_r($orderRes);
            // echo '</pre>';

            // echo '<pre>';
            // print_r($order2Res);
            // echo '</pre>';

            // dd('yes finish');

            if ((isset($orderRes) && isset($orderRes['ok'])) || (isset($order2Res) && isset($order2Res['ok']))) {
                $create = true;
                $paramArr = [];
                if (isset($orderRes['ok'])) {
                    $paramArr['order'] = $orderRes;
                }
                if (isset($order2Res['ok'])) {
                    $paramArr['orderPodzapros'] = $order2Res;
                }
                $paramArr['breadcrumbs'] = $breadcrumbs;
                return $this->render('order/create.html.twig', $paramArr);

            } else {
                if (isset($orderRes) && isset($orderRes['error'])) {
                    $errors = $orderRes['error'];
                } elseif (isset($order2Res) && isset($order2Res['error'])) {
                    $errors = $order2Res['error'];
                }
            }
        }

        return $this->render('order/index.html.twig', [
            'products' => $products,
            'productsPodZapros' => $productsPodZapros,
            'errors' => $errors,
            'breadcrumbs' => $breadcrumbs
        ]);
    }
}