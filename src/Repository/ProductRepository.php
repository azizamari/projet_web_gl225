<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }
    
    /**
     * @return Paginator Returns a paginated list of products
     */
    public function findByFilters(array $filters = [], int $page = 1, int $limit = 9): Paginator
    {
        $query = $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC');
        
        // Apply genre filters if provided
        if (!empty($filters['genres'])) {
            $query->andWhere('p.genre IN (:genres)')
                  ->setParameter('genres', $filters['genres']);
        }
        
        // Apply price range filter if provided
        if (isset($filters['min_price'])) {
            $query->andWhere('p.price >= :minPrice')
                  ->setParameter('minPrice', $filters['min_price']);
        }
        
        if (isset($filters['max_price'])) {
            $query->andWhere('p.price <= :maxPrice')
                  ->setParameter('maxPrice', $filters['max_price']);
        }
        
        // Add pagination
        $query->setFirstResult(($page - 1) * $limit)
              ->setMaxResults($limit);
        
        return new Paginator($query);
    }
    
    /**
     * Get all unique genres from products
     * @return array Array of unique genres
     */
    public function findAllGenres(): array
    {
        $genres = $this->createQueryBuilder('p')
            ->select('p.genre')
            ->where('p.genre IS NOT NULL')
            ->groupBy('p.genre')
            ->orderBy('p.genre', 'ASC')
            ->getQuery()
            ->getResult();
        
        return array_map(fn($g) => $g['genre'], $genres);
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
