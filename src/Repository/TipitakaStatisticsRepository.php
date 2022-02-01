<?php
namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\TipitakaStatistics;
use Doctrine\ORM\Query\Expr\Join;

class TipitakaStatisticsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipitakaStatistics::class);
    }
    
    public function logRequest(TipitakaStatistics $stat)
    {
        $entityManager = $this->getEntityManager();
        
        $existingStat=null;
        
        if($stat->getNodeid()!=null)
        {
            $query = $entityManager->createQueryBuilder()
            ->select('s')
            ->from('App\Entity\TipitakaStatistics','s')
            ->where('s.accessdate=:ad')
            ->andWhere('s.nodeid=:nid');
            
            if($stat->getPath()==null)
            {
                $query=$query->andWhere('s.path is null');
            }
            else
            {
                $query=$query->andWhere('s.path=:path');
            }
            
            $query=$query->getQuery()
            ->setParameter('ad',$stat->getAccessdate())
            ->setParameter('nid',$stat->getNodeid());
            
            if($stat->getPath()!=null)
            {
                $query=$query->setParameter('path',$stat->getPath());
            }            
            
            $existingStat=$query->getOneOrNullResult();            
        }
        
        if($existingStat==null && $stat->getPath()!=null)
        {
            $query = $entityManager->createQueryBuilder()
            ->select('s')
            ->from('App\Entity\TipitakaStatistics','s')
            ->where('s.accessdate=:ad')
            ->andWhere('s.path=:path')
            ->getQuery()
            ->setParameter('ad',$stat->getAccessdate())
            ->setParameter('path',$stat->getPath());
            
            $existingStat=$query->getOneOrNullResult();
        }
        
        if($existingStat)
        {//increment access count
            $existingStat->setAccesscount($existingStat->getAccesscount()+1);
            $entityManager->persist($existingStat);
        }
        else 
        {
            //nothing to increment, add it
            $stat->setAccesscount(1);
            $entityManager->persist($stat);
        }
        
        $entityManager->flush();  
        
        $query = $entityManager->createQueryBuilder()
        ->delete('App\Entity\TipitakaStatistics','s')
        ->where('DATE_DIFF(CURRENT_DATE(),s.accessdate)>:sl')
        ->getQuery()
        ->setParameter('sl',30);
        
        $query->execute();      
    }
    
    public function listStatsAgg()
    {
        $entityManager = $this->getEntityManager();
        
        $query = $entityManager->createQueryBuilder()
        ->select('s.nodeid,toc.title,s.path,SUM(s.accesscount) As accesscountsum')
        ->from('App\Entity\TipitakaStatistics','s')
        ->leftJoin('App\Entity\TipitakaToc','toc',Join::WITH,'toc.nodeid=s.nodeid')
        ->groupBy("s.nodeid,toc.title,s.path")
        ->orderBy("SUM(s.accesscount)","DESC")
        ->setMaxResults(100)
        ->getQuery();
        
        return $query->getResult();
    }
    
    public function getViewsTotal()
    {
        $result=array();
        
        $entityManager = $this->getEntityManager();
        
        $query = $entityManager->createQueryBuilder()
        ->select('SUM(s.accesscount) As totalViews')
        ->from('App\Entity\TipitakaStatistics','s')
        ->getQuery();
        
        $result["totalViews"]=$query->getOneOrNullResult()["totalViews"];
        
        $query = $entityManager->createQueryBuilder()
        ->select('SUM(s.accesscount) As dayViews')
        ->from('App\Entity\TipitakaStatistics','s')
        ->where('s.accessdate=CURRENT_DATE()')
        ->getQuery();
        
        $result["dayViews"]=$query->getOneOrNullResult()["dayViews"];
        
        return $result;
    }

}

