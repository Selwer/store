
Когда нет сложных запросов, использую Query Builder

```php
//php code
public function findAllDoc(int $page = 1, int $pageSize = 10, array $filter = []): Paginator
{
    $qb = $this->createQueryBuilder('d')
        ->addSelect('COUNT(d.id)')
        ->leftJoin('App\Entity\Event', 'e', 'WITH', 'e.doc_guid = d.guid')
        ->groupBy('d.id')
        ->orderBy('d.date_add', 'DESC');

    if (isset($filter['guid']) && !empty($filter['guid'])) {
        $qb->andWhere('d.guid = :guid')
            ->setParameter('guid', $filter['guid']);
    } else {
        if (isset($filter['client_email']) && !empty($filter['client_email'])) {
            $qb->andWhere('d.client_email = :client_email')
                ->setParameter('client_email', $filter['client_email']);
        }
        if (isset($filter['client_fio']) && !empty($filter['client_fio'])) {
            $qb->andWhere('d.client_fio = :client_fio')
                ->setParameter('client_fio', $filter['client_fio']);
        }
    }

    return (new Paginator($qb, $pageSize))->paginate($page);
}
```
