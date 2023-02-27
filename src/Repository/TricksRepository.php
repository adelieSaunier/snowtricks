<?php

namespace App\Repository;

use App\Entity\Tricks;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tricks>
 *
 * @method Tricks|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tricks|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tricks[]    findAll()
 * @method Tricks[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TricksRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tricks::class);
    }

    public function findTricksPaginated(int $page, string $slug, int $limit = 6 ):array
    {
        $limit = abs($limit);
        $result = [];
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('c', 't')
            ->from('App\Entity\Tricks', 't')
            ->join('t.categories', 'c')
            ->where("c.slug = '$slug'")
            ->setMaxResults($limit)
            ->setFirstResult( ($page * $limit) - $limit ); // Pour avoir le 1er article de la page en cours 
        
        $paginator = new Paginator($query);  
        $data = $paginator->getQuery()->getResult();


        //verif qu'il y a des données dans data
        if (empty($data)) {
            return $result;
        }
        //calcul du nombre de pages
        $pages = ceil( $paginator->count() / $limit );

        // On remplit le tableaau result 
        // Keys : data, nombre de pages, page en cours, limite
        $result['data'] = $data;
        $result['pages'] = $pages;
        $result['page'] = $page;
        $result['limit'] = $limit;
        return $result;

    }

    public function findTricksPaginatedMainPage(int $page, int $limit = 10 ):array
    {
        $limit = abs($limit);
        $result = [];
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('t')
            ->from('App\Entity\Tricks', 't')
            ->setMaxResults($limit)
            ->setFirstResult( ($page * $limit) - $limit ); 
        
        $paginator = new Paginator($query);  
        $data = $paginator->getQuery()->getResult();

        //verif qu'il y a des données dans data
        if (empty($data)) {
            return $result;
        }
        //calcul du nombre de pages
        $pages = ceil( $paginator->count() / $limit );

        // On remplit le tableaau result 
        // Keys : data, nombre de pages, page en cours, limite
        $result['data'] = $data;
        $result['pages'] = $pages;
        $result['page'] = $page;
        $result['limit'] = $limit;

        return $result;
    }


    public function save(Tricks $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Tricks $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Tricks[] Returns an array of Tricks objects
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

//    public function findOneBySomeField($value): ?Tricks
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
