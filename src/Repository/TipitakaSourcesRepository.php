<?php
namespace App\Repository;

use App\Entity\TipitakaSources;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

class TipitakaSourcesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipitakaSources::class);
    }
    
    public function listSources()
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('s.sourceid','s.name','l.name as language','s.ishidden','u.username',
           's.excludefromsearch','s.hasformatting')
        ->from('App\Entity\TipitakaSources','s')
        ->innerJoin('s.languageid','l')
        ->leftJoin('App\Entity\TipitakaUsers', 'u', Join::WITH,'s.userid=u.userid')
        ->orderBy('s.name')
        ->getQuery();
        
        return $query->getResult();
    }
    
    public function addSource($source)
    {
        $entityManager = $this->getEntityManager();   
        $entityManager->persist($source); 
        $entityManager->flush();  
    }
}

