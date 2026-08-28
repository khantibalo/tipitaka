<?php
namespace App\Repository;

use App\Entity\TipitakaSources;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
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
           's.excludefromsearch','s.hasformatting','s.priority')
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
    
    
    public function deleteSourceFromNode($nodeid,$sourceid)
    {
        //loop by sentences
        $entityManager = $this->getEntityManager();
        $queryS = $entityManager->createQueryBuilder()
        ->select('s.sentenceid')
        ->from('App\Entity\TipitakaSentences','s')
        ->innerJoin('s.paragraphid','p')
        ->innerJoin('p.nodeid', 'toc')
        ->where('toc.nodeid=:nodeid')
        ->getQuery()
        ->setParameter('nodeid',$nodeid);
        
        $sentences=$queryS->getResult();
        
        $queryST = $entityManager->createQueryBuilder()
        ->select('st')
        ->from('App\Entity\TipitakaSentenceTranslations','st')
        ->innerJoin('st.sentenceid', 's')
        ->innerJoin('st.sourceid', 'so')
        ->where('s.sentenceid=:sentenceid')
        ->andWhere('so.sourceid=:sourceid')
        ->getQuery()
        ->setParameter('sourceid',$sourceid);
        
        foreach($sentences as $sentence)
        {
            $translations=$queryST->setParameter('sentenceid', $sentence['sentenceid'])->getResult();
            foreach($translations as $translation)
            {
                $entityManager->remove($translation);
            }
        }
        
        $entityManager->flush();        
    }
}

