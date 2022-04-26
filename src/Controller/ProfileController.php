<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\UpdateUser1C;
use App\Form\ChangePasswordFormType;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile')]
    public function index(Request $request, UpdateUser1C $updateUser1C): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        // if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
        //     dd('Доступ запрещен');
        // }
        // if (!$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY')) {
        // }
        $breadcrumbs = [
            ['href' => '/profile', 'text' => 'Личный кабинет']
        ];

        $user = $this->getUser();
        $userId = $user->getId();
        $updateUser1C->updateUser1C($userId);

        return $this->render('profile/index.html.twig', [
            'breadcrumbs' => $breadcrumbs
        ]);
    }

    #[Route('/profile/info', name: 'profile_info')]
    public function profileInfo(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $breadcrumbs = [
            ['href' => '/profile', 'text' => 'Личный кабинет'],
            ['href' => '/profile/info', 'text' => 'Данные аккаунта']
        ];

        $user = $this->getUser();

        return $this->render('profile/info.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'user' => $user
        ]);
    }

    #[Route('/profile/repass', name: 'profile_repass')]
    public function profileRepass(Request $request, UserPasswordHasherInterface $passwordEncoder): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $breadcrumbs = [
            ['href' => '/profile', 'text' => 'Личный кабинет'],
            ['href' => '/profile/repass', 'text' => 'Смена пароля']
        ];

        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);
  
        if ($form->isSubmitted() && $form->isValid()) {
            // Encode the plain password, and set it.
            // $encodedPassword = $passwordEncoder->encodePassword(
            $encodedPassword = $passwordEncoder->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            $user->setPassword($encodedPassword);
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash(
                'notice',
                'Пароль был изменен!'
            );

            return $this->redirectToRoute('profile_repass');
        }

        return $this->render('profile/repass.html.twig', [
            'resetForm' => $form->createView(),
            'breadcrumbs' => $breadcrumbs
        ]);
    }

    #[Route('/profile/orders', name: 'profile_orders')]
    public function profileOrders(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $breadcrumbs = [
            ['href' => '/profile', 'text' => 'Личный кабинет'],
            ['href' => '/profile/orders', 'text' => 'История заказов']
        ];

        $user = $this->getUser();
        $userId = $user->getId();
        $userGuid = $user->getGuid();

        $indexPage = '/profile/orders';

        $itemPage = 10;
        $dataRequestParam = [
            'f' => 'y'
        ];
        $filter = [
            'numorder' => false,
            'status' => false,
            'date_from' => false,
            'date_to' => false
        ];
        $filterNeed = false;
        $page = $request->get('page', 1);
        if ($request->query->has('numorder') && !empty($request->get('numorder'))) {
            $dataRequestParam['numorder'] = $request->get('numorder');
            $filterNeed = true;
        }
        if ($request->query->has('status') && !empty($request->get('status'))) {
            $dataRequestParam['status'] = $request->get('status');
            $filterNeed = true;
        }
        if ($request->query->has('date_from') && !empty($request->get('date_from'))) {
            $dataRequestParam['date_from'] = $request->get('date_from');
            $filterNeed = true;
        }
        if ($request->query->has('date_to') && !empty($request->get('date_to'))) {
            $dataRequestParam['date_to'] = $request->get('date_to');
            $filterNeed = true;
        }
        $link = http_build_query($dataRequestParam);
        
        $dataLinkPagination = $dataRequestParam;
        if (isset($dataLinkPagination['page'])) {
            unset($dataLinkPagination['page']);
        }
        $linkPagination = http_build_query($dataLinkPagination);

        try {

			ini_set('soap.wsdl_cache_enabled', 0);

            $client = new \SoapClient($this->getParameter('app.soap_auto'), ['trace' => 1]);

			$params = [];
			$params['param'] = $userGuid;

			$result = $client->GetOrders($params);

			$error = '';
			if (isset($result->return->error)) {
				$error = $result->return->error; // получение ошибки

                return $this->render('profile/orders.html.twig', [
                    'breadcrumbs' => $breadcrumbs,
                    'error' => $error
                ]);
			}

			if (empty($error) && $result->return) {
				$dataSource = [];
                $dataAr = [];
                $data = [];
				$orders = (array) $result->return->orderDetails;

                if (!isset($orders[0])) {
                    $dataSource[0] = $orders;
                } else {
                    $dataSource = $orders;
                }

                usort($dataSource, function ($a, $b) 
                {
                    if (intval(preg_replace("/[^0-9]/", '', $a->{'orderNumber'})) == intval(preg_replace("/[^0-9]/", '', $b->{'orderNumber'}))) {
                        return 0;
                    }
                    return (intval(preg_replace("/[^0-9]/", '', $a->{'orderNumber'})) < intval(preg_replace("/[^0-9]/", '', $b->{'orderNumber'}))) ? 1 : -1;
                });

                // фильтруем
                if ($filterNeed) {
                    foreach ($dataSource as $item) {
                        $filterRes = false;
                        if ($request->query->has('numorder') && !empty($request->get('numorder'))) {
                            // if ((string)$item->{'orderNumber'} == $dataRequestParam['numorder']) {
                            if (stripos((string)$item->{'orderNumber'}, $dataRequestParam['numorder']) !== false) {
                                $filter['numorder'] = true;
                            } else {
                                $filter['numorder'] = false;
                            }
                        } else {
                            $filter['numorder'] = true;
                        }
                        if ($request->query->has('status') && !empty($request->get('status'))) {
                            if ((string)$item->{'orderStatus'} == $dataRequestParam['status']) {
                                $filter['status'] = true;
                            } else {
                                $filter['status'] = false;
                            }
                        } else {
                            $filter['status'] = true;
                        }
                        if ($request->query->has('date_from') && !empty($request->get('date_from'))) {
                            $dateFrom1 = strtotime($request->get('date_from'));
                            $dateFrom2 = strtotime((string)$item->{'orderDate'});
                            if ($dateFrom2 >= $dateFrom1) {
                                $filter['date_from'] = true;
                            } else {
                                $filter['date_from'] = false;
                            }
                        } else {
                            $filter['date_from'] = true;
                        }
                        if ($request->query->has('date_to') && !empty($request->get('date_to'))) {
                            $dateTo1 = strtotime($request->get('date_to'));
                            $dateTo2 = strtotime((string)$item->{'orderDate'});
                            if ($dateTo2 <= $dateTo1) {
                                $filter['date_to'] = true;
                            } else {
                                $filter['date_to'] = false;
                            }
                        } else {
                            $filter['date_to'] = true;
                        }
                        if ($filter['numorder'] && $filter['status'] && $filter['date_from'] && $filter['date_to']) {
                            $dataAr[] = $item;
                        }
                    }
                } else {
                    $dataAr = $dataSource;
                }

                $status = ['Заявка', 'Заказано'];

                // Пагинация
                for ($i = ($page-1)*$itemPage; $i < $page*$itemPage; $i++) {
                    if (isset($dataAr[$i])) {
                        $data[] = $dataAr[$i];
                    }
                }

				return $this->render('profile/orders.html.twig', [
                    'breadcrumbs' => $breadcrumbs,
                    'link' => $indexPage . '?' . $link,
                    'orders' => $data,
                    'filter' => $dataRequestParam,
                    'status' => $status,
                    'pagination' => [
                        'itemTotal' => count($dataAr),
                        'itemPage' => $itemPage,
                        'page' => $page,
                        'indexPage' => $indexPage,
                        'view' => 1,
                        'sort' => (isset($dataRequestParam['sort']) ? $dataRequestParam['sort'] : ''),
                        'sortN' => (isset($dataRequestParam['sort_n']) ? $dataRequestParam['sort_n'] : ''),
                        'linkPagination' => $indexPage . '?' . $linkPagination
                    ],
                ]);
			}

		} catch (\SoapFault $e) {
			$err = $e->getMessage();

			return $this->render('profile/orders.html.twig', [
                'breadcrumbs' => $breadcrumbs,
                'error' => $err
            ]);
		}

        return $this->render('profile/orders.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'error' => 'Ошибка получения данных!'
        ]);
    }

    #[Route('/profile/order/{slug}', name: 'profile_orderid')]
    public function profileGetOrderByGuid($slug, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $orderInfo = explode('|', urldecode($slug));

        $breadcrumbs = [
            ['href' => '/profile', 'text' => 'Личный кабинет'],
            ['href' => '/profile/orders', 'text' => 'История заказов'],
            ['href' => '/profile/order/' . $slug, 'text' => 'Заказ №' . $orderInfo[0]]
        ];

        $parts = [];
        $info = [];
        $order = [
            'guid' => $orderInfo[0],
            'date' => $orderInfo[1]
        ];

        $user = $this->getUser();
        $userId = $user->getId();
        $userGuid = $user->getGuid();

        try {
			ini_set('soap.wsdl_cache_enabled', 0);

            $client = new \SoapClient($this->getParameter('app.soap_auto'), ['trace' => 1]);

			$params = [];
			$params['param']['orderNumber'] = $orderInfo[0];
            $params['param']['orderDate'] = $orderInfo[1];
            $params['id'] = $userGuid;

			$result = $client->GetOrderInfo($params);

			$error = '';
			if (isset($result->return->error)) {
				$error = $result->return->error; // получение ошибки

                return $this->render('profile/order_item.html.twig', [
                    'breadcrumbs' => $breadcrumbs,
                    'error' => $error
                ]);
			}

			if (empty($error) && $result->return) {
				$data = [];
				$parts = (array) $result->return->parts->partDetails;
                $info = (array) $result->return->shippingAndPayment;
                $orderStatus = (string) $result->return->orderStatus;
                $orderSum = (float) $result->return->orderSum;

                $file = '';
                if (isset($result->return->РrintInvoice)) {
                    $printInvoiceAr = (array) $result->return->РrintInvoice;
                    // здесь преобразуем и делаем pdf файл
                    // $file = $printInvoiceAr['binaryData'];
                }

                if (!isset($parts[0])) {
                    $data[0] = $parts;
                } else {
                    $data = $parts;
                }

                return $this->render('profile/order_item.html.twig', [
                    'breadcrumbs' => $breadcrumbs,
                    'order' => $order,
                    'parts' => $data,
                    'info' => $info,
                    'status' => $orderStatus,
                    'summ' => $orderSum,
                    'files' => $file
                ]);
			}

		} catch (\SoapFault $e) {
			$err = $e->getMessage();

            return $this->render('profile/order_item.html.twig', [
                'breadcrumbs' => $breadcrumbs,
                'error' => $err
            ]);
		}

        throw $this->createNotFoundException('Заказ не найден!');
    }

    /*
    #[Route('/profile/orders', name: 'profile_orders')]
    public function profileOrders(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $breadcrumbs = [
            ['href' => '/profile', 'text' => 'Личный кабинет'],
            ['href' => '/profile/orders', 'text' => 'История заказов']
        ];

        $user = $this->getUser();
        $userId = $user->getId();

        return $this->render('profile/orders.html.twig', [
            'breadcrumbs' => $breadcrumbs
        ]);
    }

    #[Route('/profile/getorders', name: 'profile_get_orders', methods: 'POST')]
    public function profileGetOrders(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        try {

			$user = $this->getUser();

			if ($user) {
				$userId = $user->getId();
				$userGuid = $user->getGuid();
			} else {
				return $this->json([
                    'error' => 'Доступ запрещён!'
                ], 200);
			}

			ini_set('soap.wsdl_cache_enabled', 0);

            $client = new \SoapClient($this->getParameter('app.soap_auto'), ['trace' => 1]);

			$params = [];
			$params['param'] = $userGuid;

			$result = $client->GetOrders($params);

			$error = '';
			if (isset($result->return->error)) {
				$error = $result->return->error; // получение ошибки

                return $this->json([
                    'error' => $error
                ], 200);
			}

			if (empty($error) && $result->return) {
				$data = [];
				$data = (array) $result->return->orderDetails;

				return $this->json([
                    'ok' => 'ok',
                    'data' => $data
                ], 200);
			}

		} catch (\SoapFault $e) {
			$err = $e->getMessage();

			return $this->json([
                'error' => $e->getMessage()
            ], 200);
		}

        return $this->json([
            'error' => 'Доступ запрещён!'
        ], 200);
    }
    */
}
