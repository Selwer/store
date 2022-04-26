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
use App\Service\CartOrder1C;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'cart')]
    public function index(Request $request, SessionInterface $session, ProductRepository $productRepository, CartRepository $cartRepository, CartOrder1C $cartOrder1C): Response
    {
        $isLogin = $this->isGranted('IS_AUTHENTICATED_REMEMBERED');
        $isCertified = $this->isGranted('ROLE_CERTIFIED');

        $errors = [];
        $breadcrumbs = [
            ['href' => '/cart', 'text' => 'Корзина покупок']
        ];

        $user = $this->getUser();
        $userId = null;
        $userPriceLevel = 'price';
        if ($user) {
            $userId = $user->getId();
            $userPriceLevel = $user->getPriceLevel();
        }

        $cartOrder1C->CartCheck1C();

        $quantity = [];
        $quantityPodZapros = [];
        
        // Если нажали пересчитать
        if ($request->request->has('cart') && $request->request->get('cart') == 'yes' 
            && ($request->request->has('calc') || $request->request->has('order'))) {

            $quantity = $request->request->get('quantity');
            $quantityPodZapros = $request->request->get('quantity_z');
        } else {

            $products = $cartRepository->getCartProducts($request->getSession()->get('cart_user_id'), 0, $userPriceLevel, $userId);
            $productsPodZapros = $cartRepository->getCartProducts($request->getSession()->get('cart_user_id'), 1, $userPriceLevel, $userId);
            foreach ($products as $product) {
                $quantity[$product['product_id']] = $product['cart_quantity'];
            }
            foreach ($productsPodZapros as $productZ) {
                $quantityPodZapros[$productZ['product_id']] = $productZ['cart_quantity'];
            }
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
        
        if ($request->request->has('cart') && $request->request->get('cart') == 'yes' && $request->request->has('order')) {
            // if (empty($errors)) {
                return $this->redirectToRoute('order', []);
            // }
        }

        return $this->render('cart/index.html.twig', [
            'products' => $products,
            'productsPodZapros' => $productsPodZapros,
            'errors' => $errors,
            'breadcrumbs' => $breadcrumbs,
            'isLogin' => $isLogin,
            'isCertified' => $isCertified
        ]);
    }

    #[Route('/cartadd', name: 'cartadd')]
    public function add(Request $request, SessionInterface $session, CartRepository $cartRepository)
    {
        if ($request->request->has('add') && $request->request->has('product') && $request->request->get('add') == 'yes') {
            $productId = $request->request->get('product');
            $quantity = $request->request->get('quantity', 1);
            $quantity = intval($quantity);

            // проверяем есть ли такой товар в product
            if ($cartRepository->isProduct($productId)) {

                $user = $this->getUser();
                $userId = null;
                if ($user) {
                    $userId = $user->getId();
                }

                // проверяем есть ли уже в корзине
                if ($cartRepository->productIsCart($request->getSession()->get('cart_user_id'), $productId, 0, $userId)) {
                    // уже есть, в корзине увеличивываем кол-во товара
                    $cartRepository->upateQuantityProduct($request->getSession()->get('cart_user_id'), $productId, $quantity, 0, $userId);
                } else {
                    // добавляем в корзину
                    $cartRepository->addProduct($request->getSession()->get('cart_user_id'), $productId, $quantity, 0, $userId);
                }
                
                return $this->json([
                    'ok' => 'ok',
                    'data' => 'ok'
                ], 200);
            }
        }
        
        return $this->json([
            'error' => 'error'
        ], 200);
    }

    #[Route('/cartcount', name: 'cartcount')]
    public function cartcount(Request $request, SessionInterface $session, CartRepository $cartRepository)
    {
        if ($request->request->has('count') && $request->request->get('count') == 'yes') {

            $user = $this->getUser();
            $userId = null;
            if ($user) {
                $userId = $user->getId();
            }

            $count = $cartRepository->getCartCountProducts($request->getSession()->get('cart_user_id'), $userId);

            return $this->json([
                'ok' => 'ok',
                'data' => ($count > 0 ? $count : 0)
            ], 200);
        }

        return $this->json([
            'error' => 'error'
        ], 200);
    }
}