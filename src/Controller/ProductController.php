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

class ProductController extends AbstractController
{
    #[Route('/catalog/{slug}', name: 'calalogs', requirements: ['slug' => 'tires|acb|forks|parts|wheels'])]
    public function products($slug, Request $request, SessionInterface $session, ProductRepository $productRepository): Response
    {
        $isCertified = $this->isGranted('IS_AUTHENTICATED_REMEMBERED') && $this->isGranted('ROLE_CERTIFIED');

        $breadcrumbs = [];

        $user = $this->getUser();
        $userPriceLevel = 'price';
        if ($user) {
            $userPriceLevel = $user->getPriceLevel();
        }

        $view = $request->get('view', 1);
        $page = $request->get('page', 1);
        $dataRequestParam = [
            'f' => 'y'
        ];
        $dataLinkResetFilter = [];
        if ($request->query->has('view')) {
            $dataRequestParam['view'] = $request->get('view');
            $dataLinkResetFilter['view'] = $request->get('view');
        }
        if ($request->query->has('page')) {
            $dataRequestParam['page'] = $request->get('page');
        }
        if ($request->query->has('sort') && !empty($request->query->get('sort'))) {
            $dataRequestParam['sort'] = $request->get('sort');
            $dataLinkResetFilter['sort'] = $request->get('sort');
        }
        if ($request->query->has('sort_n') && !empty($request->query->get('sort_n'))
            && ($request->query->get('sort_n') == 'asc' || $request->query->get('sort_n') == 'desc')) {
            $dataRequestParam['sort_n'] = $request->get('sort_n');
            $dataLinkResetFilter['sort_n'] = $request->get('sort_n');
        }
        if ($request->query->has('brand') && !empty($request->query->get('brand'))) {
            $dataRequestParam['brand'] = $request->get('brand');
        }

        $itemPage = 12;

        if ($slug == 'tires') {
            $indexPage = '/catalog/tires';
            $type = 'Шины';
            $breadcrumbs[0] = [
                'href' => $indexPage, 'text' => 'Шины'
            ];

            if ($request->query->has('category') && !empty($request->query->get('category'))) {
                $dataRequestParam['category'] = $request->get('category');
            }
            if ($request->query->has('tire_size') && !empty($request->query->get('tire_size'))) {
                $dataRequestParam['tire_size'] = $request->get('tire_size');
            }
            if ($request->query->has('tire_diameter') && !empty($request->query->get('tire_diameter'))) {
                $dataRequestParam['tire_diameter'] = $request->get('tire_diameter');
            }
            if ($request->query->has('tire_type') && !empty($request->query->get('tire_type'))) {
                $dataRequestParam['tire_type'] = $request->get('tire_type');
            }
            if ($request->query->has('tire_execut') && !empty($request->query->get('tire_execut'))) {
                $dataRequestParam['tire_execut'] = $request->get('tire_execut');
            }

        } elseif ($slug == 'acb') {
            $indexPage = '/catalog/acb';
            $type = 'АКБ';
            $breadcrumbs[0] = [
                'href' => $indexPage, 'text' => 'АКБ'
            ];

            if ($request->query->has('category') && !empty($request->query->get('category'))) {
                $dataRequestParam['category'] = $request->get('category');
            }
            if ($request->query->has('name') && !empty($request->query->get('name'))) {
                $dataRequestParam['name'] = $request->get('name');
            }
            if ($request->query->has('acb_type') && !empty($request->query->get('acb_type'))) {
                $dataRequestParam['acb_type'] = $request->get('acb_type');
            }
            if ($request->query->has('acb_tech') && !empty($request->query->get('acb_tech'))) {
                $dataRequestParam['acb_tech'] = $request->get('acb_tech');
            }

        } elseif ($slug == 'forks') {
            $indexPage = '/catalog/forks';
            $type = 'Вилы';
            $breadcrumbs[0] = [
                'href' => $indexPage, 'text' => 'Вилы'
            ];

            if ($request->query->has('category') && !empty($request->query->get('category'))) {
                $dataRequestParam['category'] = $request->get('category');
            }
            if ($request->query->has('name') && !empty($request->query->get('name'))) {
                $dataRequestParam['name'] = $request->get('name');
            }

        } elseif ($slug == 'parts') {
            $indexPage = '/catalog/parts';
            $type = 'Запчасти';
            $breadcrumbs[0] = [
                'href' => $indexPage, 'text' => 'Запасные части'
            ];

            if ($request->query->has('name') && !empty($request->query->get('name'))) {
                $dataRequestParam['name'] = $request->get('name');
            }
        } elseif ($slug == 'wheels') {
            $indexPage = '/catalog/wheels';
            $type = 'Диски и колеса';
            $breadcrumbs[0] = [
                'href' => $indexPage, 'text' => 'Диски и колеса'
            ];

            if ($request->query->has('name') && !empty($request->query->get('name'))) {
                $dataRequestParam['name'] = $request->get('name');
            }
        }

        
        $link = http_build_query($dataRequestParam);

        $dataLinkView = $dataRequestParam;
        if (isset($dataLinkView['view'])) {
            unset($dataLinkView['view']);
        }
        $linkView = http_build_query($dataLinkView);

        $dataLinkSort = $dataRequestParam;
        if (isset($dataLinkSort['sort'])) {
            unset($dataLinkSort['sort']);
        }
        if (isset($dataLinkSort['sort_n'])) {
            unset($dataLinkSort['sort_n']);
        }
        $linkSort = http_build_query($dataLinkSort);

        $dataLinkPagination = $dataRequestParam;
        if (isset($dataLinkPagination['page'])) {
            unset($dataLinkPagination['page']);
        }
        $linkPagination = http_build_query($dataLinkPagination);

        $linkResetFilter = http_build_query($dataLinkResetFilter);

        $productsList = $productRepository->getProductsList($type, $page, $itemPage, $dataRequestParam, $userPriceLevel);
        
        return $this->render('catalog/index_' . $slug . '.html.twig', [
            'link' => $indexPage . '?' . $link,
            'linkView' => $indexPage . '?' . $linkView,
            'linkSort' => $indexPage . '?' . $linkSort,
            'linkResetFilter' => $indexPage . '?' . $linkResetFilter,
            'view' => $view,
            'products' => $productsList['items'],
            'filterSelect' => $dataRequestParam,
            'filters' => $productsList['filters'],
            'pagination' => [
                'itemTotal' => $productsList['count'],
                'itemPage' => $itemPage,
                'page' => $page,
                'indexPage' => $indexPage,
                'view' => $view,
                'sort' => (isset($dataRequestParam['sort']) ? $dataRequestParam['sort'] : ''),
                'sortN' => (isset($dataRequestParam['sort_n']) ? $dataRequestParam['sort_n'] : ''),
                'linkPagination' => $indexPage . '?' . $linkPagination
            ],
            'breadcrumbs' => $breadcrumbs,
            'isCertified' => $isCertified
        ]);
    }

    #[Route('/product-{slug}', name: 'product_detail', requirements: ['slug' => '\w+'])]
    public function detail($slug, Request $request, SessionInterface $session, ProductRepository $productRepository, CartRepository $cartRepository): Response
    {
        $isCertified = $this->isGranted('IS_AUTHENTICATED_REMEMBERED') && $this->isGranted('ROLE_CERTIFIED');

        $breadcrumbs = [];

        $user = $this->getUser();
        $userPriceLevel = 'price';
        if ($user) {
            $userPriceLevel = $user->getPriceLevel();
        }

        $type = 'detail_';
        $product = $productRepository->getProductById($slug, $userPriceLevel);
        if (!$product) {
            throw $this->createNotFoundException('Товар не найден!');
        }

        $user = $this->getUser();
        $userId = null;
        if ($user) {
            $userId = $user->getId();
        }

        $isCart = false;
        $isCart = $cartRepository->productIsCart($request->getSession()->get('cart_user_id'), $product['id'], 0, $userId);

        switch ($product['type']) {
            case 'Шины':
                $type = 'detail_tires';
                $breadcrumbs[0] = ['href' => '/catalog/tires', 'text' => 'Шины'];
                break;
            case 'Вилы':
                $type = 'detail_fork';
                $breadcrumbs[0] = ['href' => '/catalog/forks', 'text' => 'Вилы'];
                break;
            case 'АКБ':
                $type = 'detail_acb';
                $breadcrumbs[0] = ['href' => '/catalog/acb', 'text' => 'АКБ'];
                break;
            case 'Запчасти':
                $type = 'detail_parts';
                $breadcrumbs[0] = ['href' => '/catalog/parts', 'text' => 'Запасные части'];
                break;
            case 'Диски и колеса':
                $type = 'detail_wheels';
                $breadcrumbs[0] = ['href' => '/catalog/wheels', 'text' => 'Диски и колеса'];
                break;   
        }
        $breadcrumbs[1] = ['href' => '', 'text' => $product['name']];

        return $this->render('catalog/detail.html.twig', [
            'product' => $product,
            'is_cart' => $isCart,
            'type' => $type,
            'breadcrumbs' => $breadcrumbs,
            'isCertified' => $isCertified
        ]);
    }

    /*
    #[Route('/old_catalog/tires', name: 'calalog_tires')]
    public function productsTires(Request $request, SessionInterface $session, ProductRepository $productRepository): Response
    {

        $user = $this->getUser();
        $userPriceLevel = 'price';
        if ($user) {
            $userPriceLevel = $user->getPriceLevel();
        }

        $itemPage = 4;
        $indexPage = '/catalog/tires';
        $type = 'Шины';

        $view = $request->get('view', 1);
        $page = $request->get('page', 1);
        $dataRequestParam = [
            'f' => 'y'
        ];
        $dataLinkResetFilter = [];
        if ($request->query->has('view')) {
            $dataRequestParam['view'] = $request->get('view');
            $dataLinkResetFilter['view'] = $request->get('view');
        }
        if ($request->query->has('page')) {
            $dataRequestParam['page'] = $request->get('page');
        }
        if ($request->query->has('sort') && !empty($request->query->get('sort'))) {
            $dataRequestParam['sort'] = $request->get('sort');
            $dataLinkResetFilter['sort'] = $request->get('sort');
        }
        if ($request->query->has('sort_n') && !empty($request->query->get('sort_n'))
            && ($request->query->get('sort_n') == 'asc' || $request->query->get('sort_n') == 'desc')) {
            $dataRequestParam['sort_n'] = $request->get('sort_n');
            $dataLinkResetFilter['sort_n'] = $request->get('sort_n');
        }
        if ($request->query->has('brand') && !empty($request->query->get('brand'))) {
            $dataRequestParam['brand'] = $request->get('brand');
        }
        if ($request->query->has('category') && !empty($request->query->get('category'))) {
            $dataRequestParam['category'] = $request->get('category');
        }
        if ($request->query->has('tire_size') && !empty($request->query->get('tire_size'))) {
            $dataRequestParam['tire_size'] = $request->get('tire_size');
        }
        if ($request->query->has('tire_diameter') && !empty($request->query->get('tire_diameter'))) {
            $dataRequestParam['tire_diameter'] = $request->get('tire_diameter');
        }
        if ($request->query->has('tire_type') && !empty($request->query->get('tire_type'))) {
            $dataRequestParam['tire_type'] = $request->get('tire_type');
        }
        if ($request->query->has('tire_execut') && !empty($request->query->get('tire_execut'))) {
            $dataRequestParam['tire_execut'] = $request->get('tire_execut');
        }
        $link = http_build_query($dataRequestParam);

        $dataLinkView = $dataRequestParam;
        if (isset($dataLinkView['view'])) {
            unset($dataLinkView['view']);
        }
        $linkView = http_build_query($dataLinkView);

        $dataLinkSort = $dataRequestParam;
        if (isset($dataLinkSort['sort'])) {
            unset($dataLinkSort['sort']);
        }
        if (isset($dataLinkSort['sort_n'])) {
            unset($dataLinkSort['sort_n']);
        }
        $linkSort = http_build_query($dataLinkSort);

        $dataLinkPagination = $dataRequestParam;
        if (isset($dataLinkPagination['page'])) {
            unset($dataLinkPagination['page']);
        }
        $linkPagination = http_build_query($dataLinkPagination);

        $linkResetFilter = http_build_query($dataLinkResetFilter);

        $productsList = $productRepository->getProductsList($type, $page, $itemPage, $dataRequestParam, $userPriceLevel);
        dd($productsList);
        return $this->render('catalog/index_tires.html.twig', [
            'link' => $indexPage . '?' . $link,
            'linkView' => $indexPage . '?' . $linkView,
            'linkSort' => $indexPage . '?' . $linkSort,
            'linkResetFilter' => $indexPage . '?' . $linkResetFilter,
            'view' => $view,
            'products' => $productsList['items'],
            'filterSelect' => $dataRequestParam,
            'filters' => $productsList['filters'],
            'pagination' => [
                'itemTotal' => $productsList['count'],
                'itemPage' => $itemPage,
                'page' => $page,
                'indexPage' => $indexPage,
                'view' => $view,
                'sort' => (isset($dataRequestParam['sort']) ? $dataRequestParam['sort'] : ''),
                'sortN' => (isset($dataRequestParam['sort_n']) ? $dataRequestParam['sort_n'] : ''),
                'linkPagination' => $indexPage . '?' . $linkPagination
            ]
        ]);
    }

    #[Route('/old_catalog/acb', name: 'calalog_acb')]
    public function productsAcb(Request $request, SessionInterface $session, ProductRepository $productRepository): Response
    {
        $user = $this->getUser();
        $userPriceLevel = 'price';
        if ($user) {
            $userPriceLevel = $user->getPriceLevel();
        }

        $itemPage = 4;
        $indexPage = '/catalog/acb';
        $type = 'АКБ';

        $view = $request->get('view', 1);
        $page = $request->get('page', 1);
        $dataRequestParam = [
            'f' => 'y'
        ];
        $dataLinkResetFilter = [];
        if ($request->query->has('view')) {
            $dataRequestParam['view'] = $request->get('view');
            $dataLinkResetFilter['view'] = $request->get('view');
        }
        if ($request->query->has('page')) {
            $dataRequestParam['page'] = $request->get('page');
        }
        if ($request->query->has('sort') && !empty($request->query->get('sort'))) {
            $dataRequestParam['sort'] = $request->get('sort');
            $dataLinkResetFilter['sort'] = $request->get('sort');
        }
        if ($request->query->has('sort_n') && !empty($request->query->get('sort_n'))
            && ($request->query->get('sort_n') == 'asc' || $request->query->get('sort_n') == 'desc')) {
            $dataRequestParam['sort_n'] = $request->get('sort_n');
            $dataLinkResetFilter['sort_n'] = $request->get('sort_n');
        }
        if ($request->query->has('brand') && !empty($request->query->get('brand'))) {
            $dataRequestParam['brand'] = $request->get('brand');
        }
        if ($request->query->has('category') && !empty($request->query->get('category'))) {
            $dataRequestParam['category'] = $request->get('category');
        }
        if ($request->query->has('name') && !empty($request->query->get('name'))) {
            $dataRequestParam['name'] = $request->get('name');
        }
        if ($request->query->has('acb_type') && !empty($request->query->get('acb_type'))) {
            $dataRequestParam['acb_type'] = $request->get('acb_type');
        }
        if ($request->query->has('acb_tech') && !empty($request->query->get('acb_tech'))) {
            $dataRequestParam['acb_tech'] = $request->get('acb_tech');
        }
        $link = http_build_query($dataRequestParam);

        $dataLinkView = $dataRequestParam;
        if (isset($dataLinkView['view'])) {
            unset($dataLinkView['view']);
        }
        $linkView = http_build_query($dataLinkView);

        $dataLinkSort = $dataRequestParam;
        if (isset($dataLinkSort['sort'])) {
            unset($dataLinkSort['sort']);
        }
        if (isset($dataLinkSort['sort_n'])) {
            unset($dataLinkSort['sort_n']);
        }
        $linkSort = http_build_query($dataLinkSort);

        $dataLinkPagination = $dataRequestParam;
        if (isset($dataLinkPagination['page'])) {
            unset($dataLinkPagination['page']);
        }
        $linkPagination = http_build_query($dataLinkPagination);

        $linkResetFilter = http_build_query($dataLinkResetFilter);

        $productsList = $productRepository->getProductsList($type, $page, $itemPage, $dataRequestParam, $userPriceLevel);

        dd($productsList);
        
        return $this->render('catalog/index_acb.html.twig', [
            'link' => $indexPage . '?' . $link,
            'linkView' => $indexPage . '?' . $linkView,
            'linkSort' => $indexPage . '?' . $linkSort,
            'linkResetFilter' => $indexPage . '?' . $linkResetFilter,
            'view' => $view,
            'products' => $productsList['items'],
            'filterSelect' => $dataRequestParam,
            'filters' => $productsList['filters'],
            'pagination' => [
                'itemTotal' => $productsList['count'],
                'itemPage' => $itemPage,
                'page' => $page,
                'indexPage' => $indexPage,
                'view' => $view,
                'sort' => (isset($dataRequestParam['sort']) ? $dataRequestParam['sort'] : ''),
                'sortN' => (isset($dataRequestParam['sort_n']) ? $dataRequestParam['sort_n'] : ''),
                'linkPagination' => $indexPage . '?' . $linkPagination
            ]
        ]);
    }

    #[Route('/old_catalog/forks', name: 'calalog_forks')]
    public function productsForks(Request $request, SessionInterface $session, ProductRepository $productRepository): Response
    {
        $user = $this->getUser();
        $userPriceLevel = 'price';
        if ($user) {
            $userPriceLevel = $user->getPriceLevel();
        }

        $itemPage = 4;
        $indexPage = '/catalog/forks';
        $type = 'Вилы';

        $view = $request->get('view', 1);
        $page = $request->get('page', 1);
        $dataRequestParam = [
            'f' => 'y'
        ];
        $dataLinkResetFilter = [];
        if ($request->query->has('view')) {
            $dataRequestParam['view'] = $request->get('view');
            $dataLinkResetFilter['view'] = $request->get('view');
        }
        if ($request->query->has('page')) {
            $dataRequestParam['page'] = $request->get('page');
        }
        if ($request->query->has('sort') && !empty($request->query->get('sort'))) {
            $dataRequestParam['sort'] = $request->get('sort');
            $dataLinkResetFilter['sort'] = $request->get('sort');
        }
        if ($request->query->has('sort_n') && !empty($request->query->get('sort_n'))
            && ($request->query->get('sort_n') == 'asc' || $request->query->get('sort_n') == 'desc')) {
            $dataRequestParam['sort_n'] = $request->get('sort_n');
            $dataLinkResetFilter['sort_n'] = $request->get('sort_n');
        }
        if ($request->query->has('brand') && !empty($request->query->get('brand'))) {
            $dataRequestParam['brand'] = $request->get('brand');
        }
        if ($request->query->has('category') && !empty($request->query->get('category'))) {
            $dataRequestParam['category'] = $request->get('category');
        }
        if ($request->query->has('name') && !empty($request->query->get('name'))) {
            $dataRequestParam['name'] = $request->get('name');
        }
        $link = http_build_query($dataRequestParam);

        $dataLinkView = $dataRequestParam;
        if (isset($dataLinkView['view'])) {
            unset($dataLinkView['view']);
        }
        $linkView = http_build_query($dataLinkView);

        $dataLinkSort = $dataRequestParam;
        if (isset($dataLinkSort['sort'])) {
            unset($dataLinkSort['sort']);
        }
        if (isset($dataLinkSort['sort_n'])) {
            unset($dataLinkSort['sort_n']);
        }
        $linkSort = http_build_query($dataLinkSort);

        $dataLinkPagination = $dataRequestParam;
        if (isset($dataLinkPagination['page'])) {
            unset($dataLinkPagination['page']);
        }
        $linkPagination = http_build_query($dataLinkPagination);

        $linkResetFilter = http_build_query($dataLinkResetFilter);

        $productsList = $productRepository->getProductsList($type, $page, $itemPage, $dataRequestParam, $userPriceLevel);
        
        return $this->render('catalog/index_forks.html.twig', [
            'link' => $indexPage . '?' . $link,
            'linkView' => $indexPage . '?' . $linkView,
            'linkSort' => $indexPage . '?' . $linkSort,
            'linkResetFilter' => $indexPage . '?' . $linkResetFilter,
            'view' => $view,
            'products' => $productsList['items'],
            'filterSelect' => $dataRequestParam,
            'filters' => $productsList['filters'],
            'pagination' => [
                'itemTotal' => $productsList['count'],
                'itemPage' => $itemPage,
                'page' => $page,
                'indexPage' => $indexPage,
                'view' => $view,
                'sort' => (isset($dataRequestParam['sort']) ? $dataRequestParam['sort'] : ''),
                'sortN' => (isset($dataRequestParam['sort_n']) ? $dataRequestParam['sort_n'] : ''),
                'linkPagination' => $indexPage . '?' . $linkPagination
            ]
        ]);
    }

    #[Route('/old_catalog/parts', name: 'calalog_parts')]
    public function productsParts(Request $request, SessionInterface $session, ProductRepository $productRepository): Response
    {
        $user = $this->getUser();
        $userPriceLevel = 'price';
        if ($user) {
            $userPriceLevel = $user->getPriceLevel();
        }

        $itemPage = 4;
        $indexPage = '/catalog/parts';
        $type = 'Запасные части';

        $view = $request->get('view', 1);
        $page = $request->get('page', 1);
        $dataRequestParam = [
            'f' => 'y'
        ];
        $dataLinkResetFilter = [];
        if ($request->query->has('view')) {
            $dataRequestParam['view'] = $request->get('view');
            $dataLinkResetFilter['view'] = $request->get('view');
        }
        if ($request->query->has('page')) {
            $dataRequestParam['page'] = $request->get('page');
        }
        if ($request->query->has('sort') && !empty($request->query->get('sort'))) {
            $dataRequestParam['sort'] = $request->get('sort');
            $dataLinkResetFilter['sort'] = $request->get('sort');
        }
        if ($request->query->has('sort_n') && !empty($request->query->get('sort_n'))
            && ($request->query->get('sort_n') == 'asc' || $request->query->get('sort_n') == 'desc')) {
            $dataRequestParam['sort_n'] = $request->get('sort_n');
            $dataLinkResetFilter['sort_n'] = $request->get('sort_n');
        }
        if ($request->query->has('brand') && !empty($request->query->get('brand'))) {
            $dataRequestParam['brand'] = $request->get('brand');
        }
        $link = http_build_query($dataRequestParam);

        $dataLinkView = $dataRequestParam;
        if (isset($dataLinkView['view'])) {
            unset($dataLinkView['view']);
        }
        $linkView = http_build_query($dataLinkView);

        $dataLinkSort = $dataRequestParam;
        if (isset($dataLinkSort['sort'])) {
            unset($dataLinkSort['sort']);
        }
        if (isset($dataLinkSort['sort_n'])) {
            unset($dataLinkSort['sort_n']);
        }
        $linkSort = http_build_query($dataLinkSort);

        $dataLinkPagination = $dataRequestParam;
        if (isset($dataLinkPagination['page'])) {
            unset($dataLinkPagination['page']);
        }
        $linkPagination = http_build_query($dataLinkPagination);

        $linkResetFilter = http_build_query($dataLinkResetFilter);

        $productsList = $productRepository->getProductsList($type, $page, $itemPage, $dataRequestParam, $userPriceLevel);
        
        return $this->render('catalog/index_parts.html.twig', [
            'link' => $indexPage . '?' . $link,
            'linkView' => $indexPage . '?' . $linkView,
            'linkSort' => $indexPage . '?' . $linkSort,
            'linkResetFilter' => $indexPage . '?' . $linkResetFilter,
            'view' => $view,
            'products' => $productsList['items'],
            'filterSelect' => $dataRequestParam,
            'filters' => $productsList['filters'],
            'pagination' => [
                'itemTotal' => $productsList['count'],
                'itemPage' => $itemPage,
                'page' => $page,
                'indexPage' => $indexPage,
                'view' => $view,
                'sort' => (isset($dataRequestParam['sort']) ? $dataRequestParam['sort'] : ''),
                'sortN' => (isset($dataRequestParam['sort_n']) ? $dataRequestParam['sort_n'] : ''),
                'linkPagination' => $indexPage . '?' . $linkPagination
            ]
        ]);
    }

    #[Route('/old_catalog/wheels', name: 'calalog_wheels')]
    public function productsWheels(Request $request, SessionInterface $session, ProductRepository $productRepository): Response
    {
        $user = $this->getUser();
        $userPriceLevel = 'price';
        if ($user) {
            $userPriceLevel = $user->getPriceLevel();
        }
        
        $itemPage = 4;
        $indexPage = '/catalog/wheels';
        $type = 'Диски и колеса';

        $view = $request->get('view', 1);
        $page = $request->get('page', 1);
        $dataRequestParam = [
            'f' => 'y'
        ];
        $dataLinkResetFilter = [];
        if ($request->query->has('view')) {
            $dataRequestParam['view'] = $request->get('view');
            $dataLinkResetFilter['view'] = $request->get('view');
        }
        if ($request->query->has('page')) {
            $dataRequestParam['page'] = $request->get('page');
        }
        if ($request->query->has('sort') && !empty($request->query->get('sort'))) {
            $dataRequestParam['sort'] = $request->get('sort');
            $dataLinkResetFilter['sort'] = $request->get('sort');
        }
        if ($request->query->has('sort_n') && !empty($request->query->get('sort_n'))
            && ($request->query->get('sort_n') == 'asc' || $request->query->get('sort_n') == 'desc')) {
            $dataRequestParam['sort_n'] = $request->get('sort_n');
            $dataLinkResetFilter['sort_n'] = $request->get('sort_n');
        }
        if ($request->query->has('brand') && !empty($request->query->get('brand'))) {
            $dataRequestParam['brand'] = $request->get('brand');
        }
        if ($request->query->has('category') && !empty($request->query->get('category'))) {
            $dataRequestParam['category'] = $request->get('category');
        }
        if ($request->query->has('tire_size') && !empty($request->query->get('tire_size'))) {
            $dataRequestParam['tire_size'] = $request->get('tire_size');
        }
        if ($request->query->has('tire_diameter') && !empty($request->query->get('tire_diameter'))) {
            $dataRequestParam['tire_diameter'] = $request->get('tire_diameter');
        }
        if ($request->query->has('tire_type') && !empty($request->query->get('tire_type'))) {
            $dataRequestParam['tire_type'] = $request->get('tire_type');
        }
        if ($request->query->has('tire_execut') && !empty($request->query->get('tire_execut'))) {
            $dataRequestParam['tire_execut'] = $request->get('tire_execut');
        }
        $link = http_build_query($dataRequestParam);

        $dataLinkView = $dataRequestParam;
        if (isset($dataLinkView['view'])) {
            unset($dataLinkView['view']);
        }
        $linkView = http_build_query($dataLinkView);

        $dataLinkSort = $dataRequestParam;
        if (isset($dataLinkSort['sort'])) {
            unset($dataLinkSort['sort']);
        }
        if (isset($dataLinkSort['sort_n'])) {
            unset($dataLinkSort['sort_n']);
        }
        $linkSort = http_build_query($dataLinkSort);

        $dataLinkPagination = $dataRequestParam;
        if (isset($dataLinkPagination['page'])) {
            unset($dataLinkPagination['page']);
        }
        $linkPagination = http_build_query($dataLinkPagination);

        $linkResetFilter = http_build_query($dataLinkResetFilter);

        $productsList = $productRepository->getProductsList($type, $page, $itemPage, $dataRequestParam, $userPriceLevel);

        return $this->render('catalog/index_wheels.html.twig', [
            'link' => $indexPage . '?' . $link,
            'linkView' => $indexPage . '?' . $linkView,
            'linkSort' => $indexPage . '?' . $linkSort,
            'linkResetFilter' => $indexPage . '?' . $linkResetFilter,
            'view' => $view,
            'products' => $productsList['items'],
            'filterSelect' => $dataRequestParam,
            'filters' => $productsList['filters'],
            'pagination' => [
                'itemTotal' => $productsList['count'],
                'itemPage' => $itemPage,
                'page' => $page,
                'indexPage' => $indexPage,
                'view' => $view,
                'sort' => (isset($dataRequestParam['sort']) ? $dataRequestParam['sort'] : ''),
                'sortN' => (isset($dataRequestParam['sort_n']) ? $dataRequestParam['sort_n'] : ''),
                'linkPagination' => $indexPage . '?' . $linkPagination
            ]
        ]);
    }
    */
}