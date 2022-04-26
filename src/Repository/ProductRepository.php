<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function getProductById($id, $priceLevel = 'price')
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT *, " . $priceLevel . " as price_user, property->>'tire_category' as tire_category, property->>'tire_type' as tire_type,
            property->>'tire_size' as tire_size, property->>'tire_diameter' as tire_diameter, 
            property->>'tire_model' as tire_model, property->>'tire_layer' as tire_layer, 
            property->>'tire_execut' as tire_execut, property->>'tire_rim' as tire_rim, 
            property->>'fork_length' as fork_length, property->>'fork_section' as fork_section, 
            property->>'fork_class' as fork_class, property->>'fork_load' as fork_load, 
            property->>'acb_size' as acb_size, property->>'acb_tech' as acb_tech, 
            property->>'acb_type' as acb_type, property->>'acb_seria' as acb_seria, 
            property->>'acb_model' as acb_model
            FROM product WHERE id = :id AND active = true
            ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $res = $stmt->executeQuery();

        return $res->fetchAssociative();
    }

    public function getProductsList($type, $page, $limit, $filter, $priceLevel = 'price')
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT *, " . $priceLevel . " as price_user, property->>'tire_category' as tire_category, property->>'tire_type' as tire_type,
            property->>'tire_size' as tire_size, property->>'tire_diameter' as tire_diameter, 
            property->>'tire_model' as tire_model, property->>'tire_layer' as tire_layer, 
            property->>'tire_execut' as tire_execut, property->>'tire_rim' as tire_rim, 
            property->>'fork_length' as fork_length, property->>'fork_section' as fork_section, 
            property->>'fork_class' as fork_class, property->>'fork_load' as fork_load, 
            property->>'acb_size' as acb_size, property->>'acb_tech' as acb_tech, 
            property->>'acb_type' as acb_type, property->>'acb_seria' as acb_seria, 
            property->>'acb_model' as acb_model
            FROM product WHERE id > 0 AND active = true
            ";
       
        $sql .= " AND type = :type";
        if (isset($filter['brand'])) {
            $sql .= " AND brand = :brand";
        }
        // if (isset($filter['category'])) {
        //     $sql .= ' AND jsonb_exists("category"::jsonb, :category)';
        // }
        if (isset($filter['name'])) {
            $sql .= " AND name = :name";
        }
        if (isset($filter['tire_category'])) {
            $sql .= " AND property->>'tire_category' = :tire_category";
        }
        if (isset($filter['tire_size'])) {
            $sql .= " AND property->>'tire_size' = :tire_size";
        }
        if (isset($filter['tire_diameter'])) {
            $sql .= " AND property->>'tire_diameter' = :tire_diameter";
        }
        if (isset($filter['tire_type'])) {
            $sql .= " AND property->>'tire_type' = :tire_type";
        }
        if (isset($filter['tire_execut'])) {
            $sql .= " AND property->>'tire_execut' = :tire_execut";
        }
        if (isset($filter['acb_tech'])) {
            $sql .= " AND property->>'acb_tech' = :acb_tech";
        }
        if (isset($filter['acb_type'])) {
            $sql .= " AND property->>'acb_type' = :acb_type";
        }

        $sortingAr = [];
        $sorting = 'date_add';
        if (isset($filter['sort'])) {
            if ($type == 'Шины') {
                $sortingAr = ['sku', 'brand', 'tire_category', 'tire_size', 'tire_type', 
                    'tire_diameter', 'tire_model', 'tire_layer', 'tire_execut', 
                    'tire_rim', 'weight', 'volume', 'quantity', 'price', 'price2'];
                if ($key = array_search($filter['sort'], $sortingAr)) {
                    $sorting = $sortingAr[$key];
                }
            } elseif ($type == 'Вилы') {
                $sortingAr = ['sku', 'name', 'brand', 'fork_length', 'fork_section', 
                    'fork_class', 'fork_load', 'quantity', 'price', 'price2'];
                if ($key = array_search($filter['sort'], $sortingAr)) {
                    $sorting = $sortingAr[$key];
                }
            } elseif ($type == 'АКБ') {
                $sortingAr = ['sku', 'name', 'brand', 'acb_size', 'acb_tech', 
                    'acb_type', 'acb_seria', 'acb_model', 'quantity', 'price', 'price2'];
                if ($key = array_search($filter['sort'], $sortingAr)) {
                    $sorting = $sortingAr[$key];
                }
            } elseif ($type == 'Запчасти') {
                $sortingAr = ['sku', 'name', 'brand', 'quantity', 'price', 'price2'];
                if ($key = array_search($filter['sort'], $sortingAr)) {
                    $sorting = $sortingAr[$key];
                }
            } else {
                $sortingAr = ['sku', 'name', 'brand', 'quantity', 'price', 'price2'];
                if ($key = array_search($filter['sort'], $sortingAr)) {
                    $sorting = $sortingAr[$key];
                }
            }
        }

        $sortingN = 'desc';
        if (isset($filter['sort_n']) && ($filter['sort_n'] == 'desc' || $filter['sort_n'] == 'asc')) {
            $sortingN = $filter['sort_n'];
        }
        $sql .= " ORDER BY $sorting $sortingN LIMIT :limit OFFSET :offset";

        $stmt = $conn->prepare($sql);

        $stmt->bindValue(':type', $type, \PDO::PARAM_STR);
        if (isset($filter['brand'])) {
            $stmt->bindValue(':brand', $filter['brand'], \PDO::PARAM_STR);
        }
        // if (isset($filter['category'])) {
        //     $stmt->bindValue(':category', $filter['category']);
        // }
        if (isset($filter['name'])) {
            $stmt->bindValue(':name', $filter['name'], \PDO::PARAM_STR);
        }
        if (isset($filter['tire_category'])) {
            $stmt->bindValue(':tire_category', $filter['tire_category'], \PDO::PARAM_STR);
        }
        if (isset($filter['tire_size'])) {
            $stmt->bindValue(':tire_size', $filter['tire_size'], \PDO::PARAM_INT);
        }
        if (isset($filter['tire_diameter'])) {
            $stmt->bindValue(':tire_diameter', $filter['tire_diameter'], \PDO::PARAM_STR);
        }
        if (isset($filter['tire_type'])) {
            $stmt->bindValue(':tire_type', $filter['tire_type'], \PDO::PARAM_STR);
        }
        if (isset($filter['tire_execut'])) {
            $stmt->bindValue(':tire_execut', $filter['tire_execut'], \PDO::PARAM_STR);
        }
        if (isset($filter['acb_tech'])) {
            $stmt->bindValue(':acb_tech', $filter['acb_tech'], \PDO::PARAM_STR);
        }
        if (isset($filter['acb_type'])) {
            $stmt->bindValue(':acb_type', $filter['acb_type'], \PDO::PARAM_STR);
        }

        // Вот так сортировка работает, только если передавать номер столбца, и тип параметра \PDO::PARAM_INT, более безопасно переделал сверху
        // if (isset($filter['sort'])) {
        //     if ($filter['sort'] == 'tire_size' || $filter['sort'] == 'tire_type' || $filter['sort'] == 'tire_diameter'
        //         || $filter['sort'] == 'tire_model' || $filter['sort'] == 'tire_layer' || $filter['sort'] == 'tire_execut'
        //         || $filter['sort'] == 'tire_rim' || $filter['sort'] == 'fork_length' || $filter['sort'] == 'fork_section'
        //         || $filter['sort'] == 'fork_class' || $filter['sort'] == 'fork_load' || $filter['sort'] == 'acb_size'
        //         || $filter['sort'] == 'acb_tech' || $filter['sort'] == 'acb_type' || $filter['sort'] == 'acb_seria'
        //         || $filter['sort'] == 'acb_model'
        //     ) {
        //         $stmt->bindValue(':sort', "property->>" . "'" . $filter['sort'] . "'", \PDO::PARAM_STR);
        //     } else {
        //         $stmt->bindValue(':sort', $filter['sort'], \PDO::PARAM_STR);
        //     }
        // } else {
        //     $stmt->bindValue(':sort', 'date_add', \PDO::PARAM_STR);
        // }
        
        $stmt->bindValue(':limit', 9999999999, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', 0, \PDO::PARAM_INT);
        $res = $stmt->executeQuery();

        $itemTotal = count($res->fetchAllAssociative());

        $stmt->bindValue(':limit', (int) $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) ((max(1, $page) - 1) * $limit), \PDO::PARAM_INT);
        $res = $stmt->executeQuery();
        $itemPagination = $res->fetchAllAssociative();

        $filtersItem = [];
        if ($type == 'Шины') {
            $filtersItem['brand'] = $this->getProductsFilterParam($type, 'brand', $filter);
            $filtersItem['tire_category'] = $this->getProductsFilterParam($type, 'tire_category', $filter, 'json');
            $filtersItem['tire_size'] = $this->getProductsFilterParam($type, 'tire_size', $filter, 'json');
            $filtersItem['tire_diameter'] = $this->getProductsFilterParam($type, 'tire_diameter', $filter, 'json');
            $filtersItem['tire_type'] = $this->getProductsFilterParam($type, 'tire_type', $filter, 'json');
            $filtersItem['tire_execut'] = $this->getProductsFilterParam($type, 'tire_execut', $filter, 'json');

        } elseif ($type == 'Вилы') {
            $filtersItem['brand'] = $this->getProductsFilterParam($type, 'brand', $filter);
            // $filtersItem['category'] = $this->getProductsFilterParam($type, 'category', $filter);
            $filtersItem['name'] = $this->getProductsFilterParam($type, 'name', $filter);

        } elseif ($type == 'АКБ') {
            $filtersItem['brand'] = $this->getProductsFilterParam($type, 'brand', $filter);
            // $filtersItem['category'] = $this->getProductsFilterParam($type, 'category', $filter);
            $filtersItem['name'] = $this->getProductsFilterParam($type, 'name', $filter);
            $filtersItem['acb_tech'] = $this->getProductsFilterParam($type, 'acb_tech', $filter, 'json');
            $filtersItem['acb_type'] = $this->getProductsFilterParam($type, 'acb_type', $filter, 'json');
            
        } elseif ($type == 'Запчасти') {
            $filtersItem['brand'] = $this->getProductsFilterParam($type, 'brand', $filter);

        } else {
            $filtersItem['brand'] = $this->getProductsFilterParam($type, 'brand', $filter);

        }

        return [
            'items' => $itemPagination,
            'count' => $itemTotal,
            'filters' => $filtersItem
        ];
    }

    public function getProductsFilterParam($type, $param, $filter, $typeData = null)
    {
        $conn = $this->getEntityManager()->getConnection();
        // if ($param == 'category') {
        //     $sql = "
        //         SELECT p, count(*) as c
        //         FROM product
        //         CROSS JOIN LATERAL jsonb_array_elements_text(category::jsonb) as p
        //         WHERE id > 0
        //     ";
        if ($typeData == 'json') {
            $sql = "
                SELECT property->>'$param' as p, COUNT(property->>'$param') as c
                FROM product WHERE id > 0 AND active = '1'
            ";
        } else {
            $sql = "
                SELECT $param as p, COUNT($param) as c
                FROM product WHERE id > 0 AND active = '1'
            ";
        }
        
        $sql .= " AND type = :type";
        if (isset($filter['brand'])) {
            $sql .= " AND brand = :brand";
        }
        // if (isset($filter['category'])) {
        //     $sql .= ' AND jsonb_exists("category"::jsonb, :category)';
        // }
        if (isset($filter['name'])) {
            $sql .= " AND name = :name";
        }
        if (isset($filter['tire_category'])) {
            $sql .= " AND property->>'tire_category' = :tire_category";
        }
        if (isset($filter['tire_size'])) {
            $sql .= " AND property->>'tire_size' = :tire_size";
        }
        if (isset($filter['tire_diameter'])) {
            $sql .= " AND property->>'tire_diameter' = :tire_diameter";
        }
        if (isset($filter['tire_type'])) {
            $sql .= " AND property->>'tire_type' = :tire_type";
        }
        if (isset($filter['tire_execut'])) {
            $sql .= " AND property->>'tire_execut' = :tire_execut";
        }
        if (isset($filter['acb_tech'])) {
            $sql .= " AND property->>'acb_tech' = :acb_tech";
        }
        if (isset($filter['acb_type'])) {
            $sql .= " AND property->>'acb_type' = :acb_type";
        }
        
        $sql .= " GROUP BY p";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':type', $type, \PDO::PARAM_STR);
        if (isset($filter['brand'])) {
            $stmt->bindValue(':brand', $filter['brand'], \PDO::PARAM_STR);
        }
        // if (isset($filter['category'])) {
        //     $stmt->bindValue(':category', $filter['category'], \PDO::PARAM_STR);
        // }
        if (isset($filter['name'])) {
            $stmt->bindValue(':name', $filter['name'], \PDO::PARAM_STR);
        }
        if (isset($filter['tire_category'])) {
            $stmt->bindValue(':tire_category', $filter['tire_category'], \PDO::PARAM_STR);
        }
        if (isset($filter['tire_size'])) {
            $stmt->bindValue(':tire_size', $filter['tire_size'], \PDO::PARAM_INT);
        }
        if (isset($filter['tire_diameter'])) {
            $stmt->bindValue(':tire_diameter', $filter['tire_diameter'], \PDO::PARAM_STR);
        }
        if (isset($filter['tire_type'])) {
            $stmt->bindValue(':tire_type', $filter['tire_type'], \PDO::PARAM_STR);
        }
        if (isset($filter['tire_execut'])) {
            $stmt->bindValue(':tire_execut', $filter['tire_execut'], \PDO::PARAM_STR);
        }
        if (isset($filter['acb_tech'])) {
            $stmt->bindValue(':acb_tech', $filter['acb_tech'], \PDO::PARAM_STR);
        }
        if (isset($filter['acb_type'])) {
            $stmt->bindValue(':acb_type', $filter['acb_type'], \PDO::PARAM_STR);
        }

        $res = $stmt->executeQuery();
        return $res->fetchAllAssociative();

        // createQueryBuilder не подошёл, т.к. не умеет из коробки работать с json
        /*$qb = $this->createQueryBuilder('product')
            ->select('product.' . $param . ' as ' . $param, 'COUNT(product.' . $param . ') as count');

        if (isset($filter['brand'])) {
            $qb->andWhere('product.brand = :brand')
                ->setParameter('brand', $filter['brand']);
        }
        if (isset($filter['category'])) {
            $qb->andWhere('product.category = :category')
                ->setParameter('category', $filter['category']);
        }
        if (isset($filter['tire_size'])) {
            $qb->andWhere('JSON_VALUE(product.property) = :tire_size') // здесь всё отваливается
                ->setParameter('tire_size', $filter['tire_size']);
        }
        $qb->groupBy('product.' . $param);
        $query = $qb->getQuery();
        return $query->execute();*/
    }

    public function isFindProduct($guid)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT *, property->>'tire_category' as tire_category, property->>'tire_type' as tire_type, 
            property->>'tire_size' as tire_size, property->>'tire_diameter' as tire_diameter, 
            property->>'tire_model' as tire_model, property->>'tire_layer' as tire_layer, 
            property->>'tire_execut' as tire_execut, property->>'tire_rim' as tire_rim, 
            property->>'fork_length' as fork_length, property->>'fork_section' as fork_section, 
            property->>'fork_class' as fork_class, property->>'fork_load' as fork_load, 
            property->>'acb_size' as acb_size, property->>'acb_tech' as acb_tech, 
            property->>'acb_type' as acb_type, property->>'acb_seria' as acb_seria, 
            property->>'acb_model' as acb_model
            FROM product WHERE guid = :guid
            ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':guid', $guid, \PDO::PARAM_STR);
        $res = $stmt->executeQuery();

        return $res->fetchAssociative();
    }

    public function isFindProductPartsStorage($sku)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT *, property->>'tire_category' as tire_category, property->>'tire_type' as tire_type, 
            property->>'tire_size' as tire_size, property->>'tire_diameter' as tire_diameter, 
            property->>'tire_model' as tire_model, property->>'tire_layer' as tire_layer, 
            property->>'tire_execut' as tire_execut, property->>'tire_rim' as tire_rim, 
            property->>'fork_length' as fork_length, property->>'fork_section' as fork_section, 
            property->>'fork_class' as fork_class, property->>'fork_load' as fork_load, 
            property->>'acb_size' as acb_size, property->>'acb_tech' as acb_tech, 
            property->>'acb_type' as acb_type, property->>'acb_seria' as acb_seria, 
            property->>'acb_model' as acb_model
            FROM product WHERE guid = :guid AND storage = 'СкладОЗЧ'
            ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':sku', $sku, \PDO::PARAM_STR);
        $res = $stmt->executeQuery();

        return $res->fetchAssociative();
    }

    public function setImportProducts()
    {
        $conn = $this->getEntityManager()->getConnection();
        $fields['import'] = 1;
        $sql = '
            UPDATE product
            SET import = :import, date_import = NOW()';
        
        $stmt = $conn->prepare($sql);
        $stmt->executeStatement($fields);
    }

    public function udpateProduct($id, $fields)
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();
        $qb->update('product');

        if (isset($fields['guid'])) {
            $qb->set('guid', ':guid')
                ->setParameter('guid', $fields['guid']);
        }
        if (isset($fields['type'])) {
            $qb->set('type', ':type')
                ->setParameter('type', $fields['type']);
        }
        if (isset($fields['name'])) {
            $qb->set('name', ':name')
                ->setParameter('name', $fields['name']);
        }
        if (isset($fields['code'])) {
            $qb->set('code', ':code')
                ->setParameter('code', $fields['code']);
        }
        if (isset($fields['sku'])) {
            $qb->set('sku', ':sku')
                ->setParameter('sku', $fields['sku']);
        }
        if (isset($fields['brand'])) {
            $qb->set('brand', ':brand')
                ->setParameter('brand', $fields['brand']);
        }
        if (isset($fields['quantity'])) {
            $qb->set('quantity', ':quantity')
                ->setParameter('quantity', $fields['quantity']);
        }
        if (isset($fields['price'])) {
            $qb->set('price', ':price')
                ->setParameter('price', $fields['price']);
        }
        if (isset($fields['price0'])) {
            $qb->set('price0', ':price0')
                ->setParameter('price0', $fields['price0']);
        }
        if (isset($fields['price1'])) {
            $qb->set('price1', ':price1')
                ->setParameter('price1', $fields['price1']);
        }
        if (isset($fields['price2'])) {
            $qb->set('price2', ':price2')
                ->setParameter('price2', $fields['price2']);
        }
        if (isset($fields['storage'])) {
            $qb->set('storage', ':storage')
                ->setParameter('storage', $fields['storage']);
        }
        if (isset($fields['property'])) {
            $qb->set('property', ':property')
                ->setParameter('property', $fields['property']);
        }
        if (isset($fields['weight'])) {
            $qb->set('weight', ':weight')
                ->setParameter('weight', $fields['weight']);
        }
        if (isset($fields['volume'])) {
            $qb->set('volume', ':volume')
                ->setParameter('volume', $fields['volume']);
        }
        if (isset($fields['image'])) {
            $qb->set('image', ':image')
                ->setParameter('image', $fields['image']);
        }
        if (isset($fields['analog'])) {
            $qb->set('analog', ':analog')
                ->setParameter('analog', $fields['analog']);
        }
        if (isset($fields['import'])) {
            $qb->set('import', ':import')
                ->setParameter('import', $fields['import'], 'integer');
        }
        if (isset($fields['active'])) {
            $qb->set('active', ':active')
                ->setParameter('active', $fields['active'], 'integer');
        }

        $qb->where('id =:id')
            ->setParameter('id', $id);

        $qb->execute();
        // $conn = $this->getEntityManager()->getConnection();
        // $fieldsExecute = [
        //     'id' => $id,
        //     'guid' => $fields['guid'],
        //     'type' => $fields['type'],
        //     'name' => $fields['name'],
        //     'code' => $fields['code'],
        //     'sku' => $fields['sku'],
        //     'brand' => $fields['brand'],
        //     'quantity' => $fields['quantity'],
        //     'price' => $fields['price'],
        //     'price0' => $fields['price0'],
        //     'price1' => $fields['price1'],
        //     'price2' => $fields['price2'],
        //     'storage' => $fields['storage'],
        //     'property' => $fields['property'],
        //     'weight' => $fields['weight'],
        //     'volume' => $fields['volume'],
        //     'image' => $fields['image'],
        //     'import' => $fields['import'],
        //     'active' => $fields['active']
        // ];
        // $sql = '
        //     UPDATE product
        //     SET guid = :guid, type = :type, name = :name, code = :code,
        //     sku = :sku, brand = :brand, quantity = :quantity, price = :price,
        //     price0 = :price0, price1 = :price1, price2 = :price2,
        //     storage = :storage, property = :property, weight = :weight, 
        //     volume = :volume, image = :image, date_modif = NOW(),
        //     import = :import, active = :active, date_import = NOW()
        //     WHERE id = :id';
        
        // $stmt = $conn->prepare($sql);
        // $stmt->executeStatement($fieldsExecute);
    }

    public function addProduct($fields)
    {
        $conn = $this->getEntityManager()->getConnection();
        $fieldsExecute = [
            'guid' => $fields['guid'],
            'type' => $fields['type'],
            'name' => $fields['name'],
            'code' => $fields['code'],
            'sku' => $fields['sku'],
            'brand' => $fields['brand'],
            'quantity' => $fields['quantity'],
            'price' => $fields['price'],
            'price0' => $fields['price0'],
            'price1' => $fields['price1'],
            'price2' => $fields['price2'],
            'storage' => $fields['storage'],
            'property' => $fields['property'],
            'weight' => $fields['weight'],
            'volume' => $fields['volume'],
            'image' => $fields['image'],
            'analog' => $fields['analog'],
            'import' => $fields['import'],
            'active' => $fields['active']
        ];
        $sql = '
            INSERT INTO "product" VALUES (DEFAULT, :guid, :type, :name, :code, :sku, 
            :brand, :quantity, :price, :price0, :price1, :price2, :storage, :property,
            :weight, :volume, :image, :analog, NOW(), NOW(), :import, :active, NOW())
            ';
        
        $stmt = $conn->prepare($sql);
        $stmt->executeStatement($fieldsExecute);
    }

    public function getDeactiveImportProducts()
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT guid, code, sku
            FROM product
            WHERE import = true AND active = true';
        
        $stmt = $conn->prepare($sql);
        $res = $stmt->executeQuery();

        return $res->fetchAllAssociative();
    }

    public function deactiveImportProducts()
    {
        $conn = $this->getEntityManager()->getConnection();
        $fields['import'] = 0;
        $fields['active'] = 0;
        $sql = '
            UPDATE product
            SET import = :import, active = :active, date_import = NOW()
            WHERE import = true AND active = true';
        
        $stmt = $conn->prepare($sql);
        $stmt->executeStatement($fields);
    }

    public function deactiveImportProduct($id)
    {
        $conn = $this->getEntityManager()->getConnection();
        $fieldsExecute = [
            'id' => $id,
            'import' => 0,
            'active' => 0,
        ];
        $sql = '
            UPDATE product
            SET import = :import, active = :active, date_import = NOW()
            WHERE id = :id';
        
        $stmt = $conn->prepare($sql);
        $stmt->executeStatement($fieldsExecute);
    }

    public function searchType($text, $typeSearch, $filterAr, $priceLevel = 'price')
    {
        $products = [];

        $arrTypeProductFilter = ['ti' => 'Шины', 'fo' => 'Вилы', 'ac' => 'АКБ', 'pa' => 'Запчасти', 'wh' => 'Диски и колеса'];
        $arrTypeProduct = ['Шины', 'Вилы', 'АКБ', 'Запчасти', 'Диски и колеса'];
        if ($filterAr[0] != 'none') {
            $arrTypeProduct = [];
            $arrTypeProduct[] = $arrTypeProductFilter[$filterAr[0]];
        } elseif ($typeSearch == 'type1') {
            $arrTypeProduct = ['Шины'];
        }
        foreach ($arrTypeProduct as $type) {
            $products[$type] = $this->searchTypePre($type, $text, $typeSearch, $filterAr, $priceLevel = 'price');
        }

        return $products;
    }

    public function searchTypePre($typeProduct, $text, $typeSearch, $filterAr, $priceLevel = 'price')
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '';
        $sqlMain = "
            SELECT *, " . $priceLevel . " as price_user, property->>'tire_category' as tire_category, property->>'tire_type' as tire_type,
            property->>'tire_size' as tire_size, property->>'tire_size_number' as tire_size_number, 
            property->>'tire_diameter' as tire_diameter, 
            property->>'tire_model' as tire_model, property->>'tire_layer' as tire_layer, 
            property->>'tire_execut' as tire_execut, property->>'tire_rim' as tire_rim, 
            property->>'fork_length' as fork_length, property->>'fork_section' as fork_section, 
            property->>'fork_class' as fork_class, property->>'fork_load' as fork_load, 
            property->>'acb_size' as acb_size, property->>'acb_tech' as acb_tech, 
            property->>'acb_type' as acb_type, property->>'acb_seria' as acb_seria, 
            property->>'acb_model' as acb_model
            FROM product WHERE id > 0 AND active = true
            ";
            
        // Здесь поиск по размеру шины
        if ($typeSearch == 'type1') {

            $sql = $sqlMain;
            $sql .= " AND type = :type";
            $sql .= " AND property->>'tire_size_number' LIKE :text";
            if ($filterAr[0] != 'none') {
                $sql .= " AND property->>'tire_category' = :filter";
            }
            $sql .= " ORDER BY id LIMIT :limit OFFSET :offset";
            $stmt = $conn->prepare($sql);
    
            $stmt->bindValue(':type', $typeProduct, \PDO::PARAM_STR);
            $stmt->bindValue(':text', '%' . preg_replace("/[^0-9]/", '', $text) . '%', \PDO::PARAM_STR);
            if ($filterAr[0] != 'none') {
                $stmt->bindValue(':filter', $filterAr[1], \PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', 9999999999, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', 0, \PDO::PARAM_INT);
            $res = $stmt->executeQuery();
    
            $items = $res->fetchAllAssociative();

        } else {

            // Здесь сложнее из-за аналогов
            // Сперва ищем по точному совпадению (думаем, что поиск по артикулу)
            $sql = $sqlMain;
            $sql .= " AND type = :type";
            $sql .= " AND sku = :text";
            $sql .= " ORDER BY id LIMIT :limit OFFSET :offset";
            $stmt = $conn->prepare($sql);
    
            $stmt->bindValue(':type', $typeProduct, \PDO::PARAM_STR);
            $stmt->bindValue(':text', $text, \PDO::PARAM_STR);
            
            $stmt->bindValue(':limit', 9999999999, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', 0, \PDO::PARAM_INT);
            $res = $stmt->executeQuery();
    
            $items = $res->fetchAllAssociative();

            // Не пустой, нашли точное совпадение по артикулу
            if (!empty($items)) {
                // Проверяем есть ли в наличии
                if ($items[0]['quantity'] <= 0) {
                    // Вот тут начинаем искать аналоги, у которых кол-во товара больше 0
                    $guidFind = $items[0]['guid'];

                    $sql = $sqlMain;
                    $sql .= " AND type = :type";
                    $sql .= ' AND jsonb_exists("analog"::jsonb, :guid_find)';
                    
                    // $sql .= ' AND jsonb_exists_any("analog"::jsonb, array[\'2534d4ad-5cac-11ec-80c2-000c292ba2d5\', \'345\'])';
                    // $sql .= " AND analog ?| array['2534d4ad-5cac-11ec-80c2-000c292ba2d5', '345']";
                    $sql .= " AND quantity > 0";

                    $sql .= " ORDER BY id LIMIT :limit OFFSET :offset";
                    $stmt = $conn->prepare($sql);
            
                    $stmt->bindValue(':type', $typeProduct, \PDO::PARAM_STR);
                    $stmt->bindValue(':guid_find', $guidFind, \PDO::PARAM_STR);
                    
                    $stmt->bindValue(':limit', 9999999999, \PDO::PARAM_INT);
                    $stmt->bindValue(':offset', 0, \PDO::PARAM_INT);
                    $res = $stmt->executeQuery();
            
                    $items = $res->fetchAllAssociative();
                }

            } else {

                // Ищем по вхождению в поле name
                $sql = $sqlMain;
                $sql .= " AND type = :type";
                $sql .= " AND name ILIKE :text2";
                if ($filterAr[0] != 'none' && $filterAr[0] == 'ti') {
                    $sql .= " AND property->>'tire_category' = :filter";
                }
                $sql .= " ORDER BY id LIMIT :limit OFFSET :offset";
                $stmt = $conn->prepare($sql);
        
                $stmt->bindValue(':type', $typeProduct, \PDO::PARAM_STR);
                $stmt->bindValue(':text2', '%' . $text . '%', \PDO::PARAM_STR);
                if ($filterAr[0] != 'none' && $filterAr[0] == 'ti') {
                    $stmt->bindValue(':filter', $filterAr[1], \PDO::PARAM_STR);
                }
                
                $stmt->bindValue(':limit', 9999999999, \PDO::PARAM_INT);
                $stmt->bindValue(':offset', 0, \PDO::PARAM_INT);
                $res = $stmt->executeQuery();
        
                $items = $res->fetchAllAssociative();
            }
        }

        return $items;
    }

    // /**
    //  * @return Product[] Returns an array of Product objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Product
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
