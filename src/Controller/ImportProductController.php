<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\ProductRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ImportProductController extends AbstractController
{

    #[Route('/import-product', name: 'import_product')]
    public function index(Request $request, ProductRepository $productRepository, MailerInterface $mailer): Response
    {
        $coutLogs = [
            'error' => 0,
            'add' => 0,
            'update' => 0,
            'deact' => 0,
            'total' => 0,
            'start' => date('Y-m-d H:i:s'),
            'end' => ''
        ];
        $logs = [];
        $logs[] = $this->writeLogMess('Выгрузка начата');

        $dir = $this->getParameter('kernel.project_dir');
        $file = $dir . '/public/import/Stock.xml';

        try {

            if (file_exists($file)) {
                $xmlObjects = simplexml_load_file($file);

                $countAll = '';
                $countAdd = 0;
                $countModific = 0;
                $countDeactiv = 0;
                $countError = 0;

                // Два новых поля
                //     import
                //     active


                //     Начинаем импорт
                //     всем product set import = 1
                //     UPDATE product
                //         SET import = 1
                        

                //     далее в цикле парсим xml,
                //     xml->guid, если есть нашли в product.guid, то обновляем поля и set import = 0, active = 1


                //     далее после цикла нужно деактивировать не найденные в xml
                //     UPDATE product
                //         SET import = 0, active = 0 WHERE import = 1 AND active = 1

                $copyImg = $this->runCopyImage();
                if (!$copyImg) {
                    $logs[] = $this->writeLogMess('Ошибка распаковки архива с изображениями');
                }

                $productRepository->setImportProducts();
                
                foreach ($xmlObjects as $item) {

                    $coutLogs['total']++;

                    $fields = [];
                    $guid = '';
                    $guid = (string)$item->{'guid'};
                    if (empty($guid)) {
                        $logs[] = $this->writeLogMess('Ошибка нет guid'
                            . ' (' . $item->{'brand'} . ' ' . $item->{'name'} . ' ' . $item->{'sku'} . ')');
                        $coutLogs['error']++;
                        continue;
                    }

                    $arType = ['ШИНЫ', 'Запчасти', 'ВИЛЫ', 'АКБ'];
                    $type = '';
                    $type = (string)$item->{'type'};
                    if (empty($type) || !in_array($type, $arType)) {
                        $logs[] = $this->writeLogMess('Ошибка нет type'
                            . ' (guid: ' . $item->{'guid'} . ', code: ' . $item->{'code'} . ', артикул: ' . $item->{'sku'} . ')');
                        $coutLogs['error']++;
                        continue;
                    } else {
                        switch ($type) {
                            case 'ШИНЫ':
                                $type = 'Шины';
                                break;
                            case 'ВИЛЫ':
                                $type = 'Вилы';
                                break;
                            case 'АКБ':
                                $type = 'АКБ';
                                break;
                            case 'Запчасти':
                                $type = 'Запчасти';
                                break;
                            case 'Диски и колеса':
                                $type = 'Диски и колеса';
                                break;   
                        }
                    }

                    $price = 0;
                    $price0 = 0;
                    $price1 = 0;
                    $price2 = 0;
                    if ((int)$item->{'quantity'} > 0) {
                        if (empty($item->prices)) {
                            $logs[] = $this->writeLogMess('Ошибка нет цен'
                                . ' (guid: ' . $item->{'guid'} . ', code: ' . $item->{'code'} . ', артикул: ' . $item->{'sku'} . ')');
                            $coutLogs['error']++;
                            continue;
                        }
                        if (empty($item->prices->price)) {
                            $logs[] = $this->writeLogMess('Ошибка нет Розничная цена'
                                . ' (guid: ' . $item->{'guid'} . ', code: ' . $item->{'code'} . ', артикул: ' . $item->{'sku'} . ')');
                            $coutLogs['error']++;
                            continue;
                        } else {
                            $price = (string)$item->prices->price;
                        }
                        if (empty($item->prices->price0)) {
                            $logs[] = $this->writeLogMess('Ошибка нет Оптовая Спец0'
                                . ' (guid: ' . $item->{'guid'} . ', code: ' . $item->{'code'} . ', артикул: ' . $item->{'sku'} . ')');
                            $coutLogs['error']++;
                            continue;
                        } else {
                            $price0 = (string)$item->prices->price0;
                        }
                        if (empty($item->prices->price1)) {
                            $logs[] = $this->writeLogMess('Ошибка нет Оптовая Спец1'
                                . ' (guid: ' . $item->{'guid'} . ', code: ' . $item->{'code'} . ', артикул: ' . $item->{'sku'} . ')');
                            $coutLogs['error']++;
                            continue;
                        } else {
                            $price1 = (string)$item->prices->price1;
                        }
                        if (empty($item->prices->price2)) {
                            $logs[] = $this->writeLogMess('Ошибка нет Оптовая Спец2'
                                . ' (guid: ' . $item->{'guid'} . ', code: ' . $item->{'code'} . ', артикул: ' . $item->{'sku'} . ')');
                            $coutLogs['error']++;
                            continue;
                        } else {
                            $price2 = (string)$item->prices->price2;
                        }
                    }

                    $imagesJson = '';
                    $images = [];
                    if (!empty($item->images)) {
                        foreach ($item->images->{'image'} as $img) {
                            $images[] = (string)$img;
                        }
                    }
                    $imagesJson = json_encode($images, JSON_UNESCAPED_UNICODE);

                    $propertiesJson = '';
                    $properties = [];
                    $properties = [
                        'tire_category' => (!empty($item->properties->{'tire_category'}) ? (string)$item->properties->{'tire_category'} : ''),
                        'tire_size' => (!empty($item->properties->{'tire_size'}) ? (string)$item->properties->{'tire_size'} : ''),
                        'tire_size_number' => (!empty($item->properties->{'tire_size'}) ? preg_replace("/[^0-9]/", '', (string)$item->properties->{'tire_size'}) : ''),
                        'tire_type' => (!empty($item->properties->{'tire_type'}) ? (string)$item->properties->{'tire_type'} : ''),
                        'tire_diameter' => (!empty($item->properties->{'tire_diameter'}) ? (string)$item->properties->{'tire_diameter'} : ''),
                        'tire_model' => (!empty($item->properties->{'tire_model'}) ? (string)$item->properties->{'tire_model'} : ''),
                        'tire_layer' => (!empty($item->properties->{'tire_layer'}) ? (string)$item->properties->{'tire_layer'} : ''),
                        'tire_execut' => (!empty($item->properties->{'tire_execut'}) ? (string)$item->properties->{'tire_execut'} : ''),
                        'tire_rim' => (!empty($item->properties->{'tire_rim'}) ? (string)$item->properties->{'tire_rim'} : ''),
                        'fork_length' => (!empty($item->properties->{'fork_length'}) ? (string)$item->properties->{'fork_length'} : ''),
                        'fork_section' => (!empty($item->properties->{'fork_section'}) ? (string)$item->properties->{'fork_section'} : ''),
                        'fork_class' => (!empty($item->properties->{'fork_class'}) ? (string)$item->properties->{'fork_class'} : ''),
                        'fork_load' => (!empty($item->properties->{'fork_load'}) ? (string)$item->properties->{'fork_load'} : ''),
                        'acb_size' => (!empty($item->properties->{'acb_size'}) ? (string)$item->properties->{'acb_size'} : ''),
                        'acb_tech' => (!empty($item->properties->{'acb_tech'}) ? (string)$item->properties->{'acb_tech'} : ''),
                        'acb_type' => (!empty($item->properties->{'acb_type'}) ? (string)$item->properties->{'acb_type'} : ''),
                        'acb_seria' => (!empty($item->properties->{'acb_seria'}) ? (string)$item->properties->{'acb_seria'} : ''),
                        'acb_model' => (!empty($item->properties->{'acb_model'}) ? (string)$item->properties->{'acb_model'} : ''),
                    ];
                    $propertiesJson = json_encode($properties, JSON_UNESCAPED_UNICODE);

                    $analogJson = '';
                    $analogs = [];
                    if (!empty($item->analogs)) {
                        foreach ($item->analogs->{'analog'} as $analog) {
                            $analogs[] = (string)$analog;
                        }
                    }
                    $analogJson = json_encode($analogs, JSON_UNESCAPED_UNICODE);

                    $fields = [
                        'guid' => $guid,
                        'type' => $type,
                        'name' => (string)$item->{'name'},
                        'code' => (string)$item->{'code'},
                        'sku' => (string)$item->{'sku'},
                        'brand' => (string)$item->{'brand'},
                        'weight' => (string)$item->{'weight'},
                        'volume' => (string)$item->{'volume'},
                        'quantity' => (string)$item->{'quantity'},
                        'price' => $price,
                        'price0' => $price0,
                        'price1' => $price1,
                        'price2' => $price2,
                        'storage' => (string)$item->{'storage'},
                        'image' => $imagesJson,
                        'analog' => $analogJson,
                        'property' => $propertiesJson,
                        'import' => 0,
                        'active' => 1
                    ];


                    // if ($productPartsStorage['storage'] = 'СкладОПА') {
                    //     continue;
                    // }
                    /*
                    // Проверяем если уже такой товар по sku
                    if ($productPartsStorage = $productRepository->isFindProductPartsStorage($fields['sku'])) {
                        if ($productPartsStorage['storage'] = 'СкладОПА' && $fields['storage'] == 'СкладОЗЧ') {
                            // Деактивируем такой же товар со склада СкладОПА, т.к СкладОЗЧ в приоритете 
                            $productRepository->deactiveImportProduct($productPartsStorage['id']);
                            $logs[] = $this->writeLogMess('Деактивировали товар со СкладОПА '
                            . ' (guid: ' . $item->{'guid'} . ', code: ' . $item->{'code'} . ', артикул: ' . $item->{'sku'} . ')');
                            continue;

                        } elseif ($productPartsStorage['storage'] = 'СкладОЗЧ' && $fields['storage'] == 'СкладОПА') {
                            // Пропускаем товар со СкладОПА, т.к СкладОЗЧ в приоритете 

                            $logs[] = $this->writeLogMess('Пропускаем товар со СкладОПА '
                            . ' (guid: ' . $item->{'guid'} . ', code: ' . $item->{'code'} . ', артикул: ' . $item->{'sku'} . ')');
                            continue;

                        }
                    }
                    */

                    // Если найден продукт, узнаем нужно ли обновление
                    if ($product = $productRepository->isFindProduct($guid)) {

                        $poductDataBase = $product['guid'].'|'.$product['type'].'|'.$product['name'].'|'.$product['code'].'|'.$product['sku']
                            .'|'.$product['brand'].'|'.(float) $product['weight'].'|'.(float) $product['volume'].'|'.$product['quantity'].'|'.(float) $product['price']
                            .'|'.(float) $product['price0'].'|'.(float) $product['price1'].'|'.(float) $product['price2'].'|'.$product['storage']
                            .'|'.$product['image'].'|'.$product['analog'].'|'.$product['property'].'|'.$product['active'];
                        
                        $poductDataImport = $fields['guid'].'|'.$fields['type'].'|'.$product['name'].'|'.$fields['code'].'|'.$fields['sku']
                            .'|'.$fields['brand'].'|'.(float) $fields['weight'].'|'.(float) $fields['volume'].'|'.$fields['quantity'].'|'.(float) $fields['price']
                            .'|'.(float) $fields['price0'].'|'.(float) $fields['price1'].'|'.(float) $fields['price2'].'|'.$fields['storage']
                            .'|'.$fields['image'].'|'.$fields['analog'].'|'.$fields['property'].'|'.$fields['active'];

                        // echo '<br>---<br>';
                        // echo '#' . $poductDataBase . '#';
                        // echo '<br>';
                        // echo '#' . $poductDataImport .'#';
                        // echo '<br>---<br>';

                        // Нужно обновлять
                        if ($poductDataBase != $poductDataImport) {
                            echo '<br>НУЖНО ОБНОВИТЬ<br>';
                            $productRepository->udpateProduct($product['id'], $fields);
                        
                            $logs[] = $this->writeLogMess('Обновлен товар '
                                . ' (guid: ' . $item->{'guid'} . ', code: ' . $item->{'code'} . ', артикул: ' . $item->{'sku'} . ')');
                            $coutLogs['update']++;
                        } else {
                            // Устанавливаем флаг импорт в false
                            $productRepository->udpateProduct($product['id'], ['import' => 0]);
                        }
                    } else {

                        $productRepository->addProduct($fields);

                        $logs[] = $this->writeLogMess('Добавлен товар '
                            . ' (guid: ' . $item->{'guid'} . ', code: ' . $item->{'code'} . ', артикул: ' . $item->{'sku'} . ')');
                        $coutLogs['add']++;
                    }

                    // echo '<pre>';
                    // print_r($fields);
                    // echo '</pre><br><hr></br>';    
                }

                $deactivProd = $productRepository->getDeactiveImportProducts();
                foreach ($deactivProd as $dprod) {
                    $logs[] = $this->writeLogMess('Деактивирован товар '
                        . ' (guid: ' . $dprod['guid'] . ', code: ' . $dprod['code'] . ', артикул: ' . $dprod['sku'] . ')');
                    $coutLogs['deact']++;
                }
                $productRepository->deactiveImportProducts();
                $logs[] = $this->writeLogMess('Выгрузка закончена');
                $coutLogs['end'] = date('Y-m-d H:i:s'); 

            } else {
                throw new \Exception('Не удалось открыть файл xml файл');
            }

            $this->sendEmailEnd($logs, $coutLogs, $mailer);

        } catch(\Throwable $t) {

            $logs[] = $this->writeLogMess('Выгрузка прервана по ошибке');
            $logs[] = $this->writeLogMess($t->getMessage());
            echo $t->getMessage(), "\n";

            $this->sendEmailEnd($logs, $coutLogs, $mailer);
        }

        return new Response('Обновили');
    }

    public function writeLogMess($mess)
    {
        return date('Y-d-m H:i:s') . ' ' . $mess;
    }

    public function sendEmailEnd($logs, $coutLogs, $mailer)
    {
        $emailFrom = $this->getParameter('app.email_from');
        $emailFromName = $this->getParameter('app.email_from_name');
        $domain = $this->getParameter('app.domain');
        $protocolHttp = $this->getParameter('app.protocol_http');

        $toAddresses = ['email@email.test'];
        $subject = 'Лог выгрузки товаров';

        $dir = $this->getParameter('kernel.project_dir');
        $fileName = date('Y_m_d_H_i_s') . '_' . bin2hex(random_bytes(2)) . '.txt';
        $file = $dir . '/public/import_logs/' . $fileName;

        foreach ($logs as $log) {
            file_put_contents($file, $log . "\r\n", FILE_APPEND);
        }

        $email = (new TemplatedEmail())
            ->from(new Address($emailFrom, $emailFromName))
            ->to(...$toAddresses)
            ->replyTo(new Address($emailFrom, $emailFromName))
            ->subject($subject)
            ->htmlTemplate('email/report_import.html.twig')
            ->context([
                'coutLogs' => $coutLogs,
                'file' => $fileName,
                'domain' => $domain,
                'protocol_http' => $protocolHttp
            ]);

        $mailer->send($email);
    }

    public function runCopyImage()
	{
		// Добавляем изображения из папки импорта в папку media
		// Копируем архив в папку с изображениями
        $dir = $this->getParameter('kernel.project_dir');
        $file = $dir . '/public/import/images.zip';

		if (file_exists($file)) {
			$newName = $dir . '/public/media/images.zip';
			if (copy($file, $newName)) {
				
				// Распаковываем архив с файлами
				$zip = new \ZipArchive;
				$res = $zip->open($newName);
				if ($res === TRUE) {
					$zip->extractTo($dir . '/public/media/');
					$zip->close();

					unlink($newName);

					return true;
				} 

				unlink($newName);
			}

		} else {
			
			return false;
		}

		return false;
	}

    #[Route('/file-l', name: 'file_logs')]
    public function getFileLog(Request $request)
    {
        $fileName = $request->get('file', 'none.txt');
        $dir = $this->getParameter('kernel.project_dir');
        $file = $dir . '/public/import_logs/' . $fileName;

        if (file_exists($file)) {
            $response = new BinaryFileResponse($file);

            $response->headers->set('Content-Type', 'text/plain');
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $fileName
            );

            return $response;
        } else {
            return new Response('Файл не найден!');
        } 
    }
}