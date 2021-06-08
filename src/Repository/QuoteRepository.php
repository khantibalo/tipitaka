<?php
namespace App\Repository;

use App\Entity\TipitakaParagraphs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class QuoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipitakaParagraphs::class);
    }
    
    public function listParagraphs(array $ids)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('c.text As Text,c.caps')
        ->from('App\Entity\TipitakaParagraphs','c')
        ->where('c.paragraphid IN(:ci)')
        ->getQuery()
        ->setParameter('ci', $ids);
        
        return $query->getResult();
    }
        
    public function listSentenceTranslations(array $ids)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('st.translation As Text')
        ->from('App\Entity\TipitakaSentenceTranslations','st')
        ->innerJoin('st.sentenceid', 's')
        ->innerJoin('s.paragraphid','c')
        ->where('st.sentencetranslationid IN(:ci)')
        ->orderBy('c.paragraphid')
        ->addOrderBy('s.sentenceid')
        ->getQuery()
        ->setParameter('ci', $ids);
        
        
        return $query->getResult();
    }
}

