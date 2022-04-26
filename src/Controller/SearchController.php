<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SearchController extends AbstractController
{
    #[Route('/search1', name: 'search1')]
    public function searchType1(Request $request, ProductRepository $productRepository, EntityManagerInterface $entityManager): Response
    {
        $isCertified = $this->isGranted('IS_AUTHENTICATED_REMEMBERED') && $this->isGranted('ROLE_CERTIFIED');
        
        $products = [];
        $text = '';
        $breadcrumbs = [
            ['href' => '/search1', 'text' => 'Результаты поиска']
        ];

        $user = $this->getUser();
        $userPriceLevel = 'price';
        if ($user) {
            $userPriceLevel = $user->getPriceLevel();
        }

        $link = '/search1';
        $linkBack = '/search1';
        $filterType = ['ti'];
        $filterAppl = false;
        $urlParam = [];
        $urlParamBack = [];

        if ($request->query->has('t')) {
            $filterStr = $request->query->get('t');
            $filterAr = explode(':', $filterStr);
        }
        if (isset($filterAr) && isset($filterAr[0]) && in_array($filterAr[0], $filterType)) {
            $filterAppl = true;
            $urlParam['t'] = $filterStr;
        } else {
            $filterAr = ['none'];
        }

        if ($request->query->has('s1')) {
            $text = $request->query->get('s1');
            $urlParam['s1'] = $text;
            $urlParamBack['s1'] = $text;
            if (mb_strlen($text) > 2) {
                $products = $productRepository->searchType($text, 'type1', $filterAr, $userPriceLevel);
            }
        } else {
            $text = 'none';
        }
        if (!empty($urlParam)) {
            $link .= '?' . http_build_query($urlParam);
            $linkBack .= '?' . http_build_query($urlParamBack);
        }

        $filter = [];
        if (isset($products['Шины']) && !empty($products['Шины'])) {
            foreach ($products['Шины'] as $prod) {
                if (isset($filter['Шины'][$prod['tire_category']])) {
                    $filter['Шины'][$prod['tire_category']]++;
                } else {
                    $filter['Шины'][$prod['tire_category']] = 1;
                }
            }
        }
        if (isset($products['Вилы']) && !empty($products['Вилы'])) {
            foreach ($products['Вилы'] as $prod) {
                if (isset($filter['Вилы']['Вилы'])) {
                    $filter['Вилы']['Вилы']++;
                } else {
                    $filter['Вилы']['Вилы'] = 1;
                }
            }
        }
        if (isset($products['АКБ']) && !empty($products['АКБ'])) {
            foreach ($products['АКБ'] as $prod) {
                if (isset($filter['АКБ']['АКБ'])) {
                    $filter['АКБ']['АКБ']++;
                } else {
                    $filter['АКБ']['АКБ'] = 1;
                }
            }
        }
        if (isset($products['Запчасти']) && !empty($products['Запчасти'])) {
            foreach ($products['Запчасти'] as $prod) {
                if (isset($filter['Запчасти']['Запчасти'])) {
                    $filter['Запчасти']['Запчасти']++;
                } else {
                    $filter['Запчасти']['Запчасти'] = 1;
                }
            }
        }
        if (isset($products['Диски и колеса']) && !empty($products['Диски и колеса'])) {
            foreach ($products['Диски и колеса'] as $prod) {
                if (isset($filter['Диски и колеса']['Диски и колеса'])) {
                    $filter['Диски и колеса']['Диски и колеса']++;
                } else {
                    $filter['Диски и колеса']['Диски и колеса'] = 1;
                }
            }
        }

        return $this->render('search/index.html.twig', [
            'products' => $products,
            'text' => $text,
            'breadcrumbs' => $breadcrumbs,
            'isCertified' => $isCertified,
            'filter' => $filter,
            'type' => 'type1',
            'text' => $text,
            'filterAr' => $filterAr,
            'link' => $link,
            'linkBack' => $linkBack,
            'filterAppl' => $filterAppl
        ]);
    }

    #[Route('/search2', name: 'search2')]
    public function searchType(Request $request, ProductRepository $productRepository, EntityManagerInterface $entityManager): Response
    {
        $isCertified = $this->isGranted('IS_AUTHENTICATED_REMEMBERED') && $this->isGranted('ROLE_CERTIFIED');

        $products = [];
        $text = '';
        $breadcrumbs = [
            ['href' => '/search2', 'text' => 'Результаты поиска']
        ];

        $user = $this->getUser();
        $userPriceLevel = 'price';
        if ($user) {
            $userPriceLevel = $user->getPriceLevel();
        }

        $link = '/search2';
        $linkBack = '/search2';
        $filterType = ['ti', 'ac', 'fo', 'pa', 'wh'];
        $filterAppl = false;
        $urlParam = [];
        $urlParamBack = [];

        if ($request->query->has('t')) {
            $filterStr = $request->query->get('t');
            $filterAr = explode(':', $filterStr);
        }
        if (isset($filterAr) && isset($filterAr[0]) && in_array($filterAr[0], $filterType)) {
            $filterAppl = true;
            $urlParam['t'] = $filterStr;
        } else {
            $filterAr = ['none'];
        }

        if ($request->query->has('s2')) {
            $text = $request->query->get('s2');
            $urlParam['s2'] = $text;
            $urlParamBack['s2'] = $text;
            if (mb_strlen($text) > 2) {
                $products = $productRepository->searchType($text, 'type2', $filterAr, $userPriceLevel);
            }
        } else {
            $text = 'none';
        }
        if (!empty($urlParam)) {
            $link .= '?' . http_build_query($urlParam);
            $linkBack .= '?' . http_build_query($urlParamBack);
        }

        $filter = [];
        if (isset($products['Шины']) && !empty($products['Шины'])) {
            foreach ($products['Шины'] as $prod) {
                if (isset($filter['Шины'][$prod['tire_category']])) {
                    $filter['Шины'][$prod['tire_category']]++;
                } else {
                    $filter['Шины'][$prod['tire_category']] = 1;
                }
            }
        }
        if (isset($products['Вилы']) && !empty($products['Вилы'])) {
            foreach ($products['Вилы'] as $prod) {
                if (isset($filter['Вилы']['Вилы'])) {
                    $filter['Вилы']['Вилы']++;
                } else {
                    $filter['Вилы']['Вилы'] = 1;
                }
            }
        }
        if (isset($products['АКБ']) && !empty($products['АКБ'])) {
            foreach ($products['АКБ'] as $prod) {
                if (isset($filter['АКБ']['АКБ'])) {
                    $filter['АКБ']['АКБ']++;
                } else {
                    $filter['АКБ']['АКБ'] = 1;
                }
            }
        }
        if (isset($products['Запчасти']) && !empty($products['Запчасти'])) {
            foreach ($products['Запчасти'] as $prod) {
                if (isset($filter['Запчасти']['Запчасти'])) {
                    $filter['Запчасти']['Запчасти']++;
                } else {
                    $filter['Запчасти']['Запчасти'] = 1;
                }
            }
        }
        if (isset($products['Диски и колеса']) && !empty($products['Диски и колеса'])) {
            foreach ($products['Диски и колеса'] as $prod) {
                if (isset($filter['Диски и колеса']['Диски и колеса'])) {
                    $filter['Диски и колеса']['Диски и колеса']++;
                } else {
                    $filter['Диски и колеса']['Диски и колеса'] = 1;
                }
            }
        }

        return $this->render('search/index.html.twig', [
            'products' => $products,
            'text' => $text,
            'breadcrumbs' => $breadcrumbs,
            'isCertified' => $isCertified,
            'filter' => $filter,
            'type' => 'type2',
            'text' => $text,
            'filterAr' => $filterAr,
            'link' => $link,
            'linkBack' => $linkBack,
            'filterAppl' => $filterAppl
        ]);
    }
}