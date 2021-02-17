<?php
namespace App\Repository;

use App\Entity\TipitakaDictionaryentries;
use App\Entity\TipitakaDictionaryentryUse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

class TipitakaDictionaryRepository  extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipitakaDictionaryentries::class);
    }
    
    public function listDictionaryTypes()
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('dt.dictionarytypeid,dt.name')
        ->from('App\Entity\TipitakaDictionarytypes','dt')
        ->getQuery();
        
        $assoc=array();
        
        foreach($query->getResult() as $result)
        {
            $assoc[$result['name']]=$result['dictionarytypeid'];
        }
        
        return $assoc;
    }
    
    public static function convertNoDiac($paliword)
    {
        $search=["ā","ī","ū","ḍ","ḷ","ṇ","ṭ","ñ","ṃ","ṅ"];
        $replace=["a","i","u","d","l","n","t","n","m","n"];
        
        $lower=mb_convert_case($paliword,MB_CASE_LOWER);
        $nodiac=str_ireplace($search, $replace,$lower);
        
        return $nodiac;
    }
    
    public function searchContent($keyword,$dictionaryTypeID)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('de.paliword','de.explanation','dt.name')
        ->from('App\Entity\TipitakaDictionaryentries','de')
        ->innerJoin('de.dictionarytypeid', 'dt')
        ->where('de.explanation LIKE :keyword');
        
        if(!empty($dictionaryTypeID))
        {
            $query=$query->andWhere('dt.dictionarytypeid=:dt');
        }
        
        $query=$query->getQuery()->setParameter('keyword', "%$keyword%");
        
        if(!empty($dictionaryTypeID))
        {
            $query=$query->setParameter('dt', $dictionaryTypeID);
        }        
        
        return $query->getResult();
    }
    
    public function listByLetter($letter)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('de.paliword')
        ->from('App\Entity\TipitakaDictionaryentries','de')
        ->where('de.paliword LIKE :letter')
        ->groupBy('de.paliword')
        ->getQuery()
        ->setParameter('letter', $letter.'%');
        
        return $query->getResult();
    }
    
    public function listByTerm($term)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('de.explanation,dt.name,de.dictionaryentryid,de.translation')
        ->from('App\Entity\TipitakaDictionaryentries','de')
        ->innerJoin('de.dictionarytypeid', 'dt')
        ->where('de.paliword=:term')
        ->orderBy('dt.sortorder')
        ->getQuery()
        ->setParameter('term', $term);
        
        return $query->getResult();
    }
    
    public function listLinkedPaliwords($paliword)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('pt1.paliword,tag.tagid')
        ->from('App\Entity\TipitakaPaliwordTags','pt')
        ->innerJoin('pt.tagid','tag')
        ->join('App\Entity\TipitakaPaliwordTags', 'pt1',Join::WITH,'tag.tagid=pt1.tagid')
        ->where('pt.paliword=:paliword')
        ->getQuery()
        ->setParameter('paliword', $paliword);
        
        return $query->getResult();
    }
    
    public function listByTag($tagid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('pt.paliword,tag.tagid')
        ->from('App\Entity\TipitakaPaliwordTags','pt')
        ->innerJoin('pt.tagid','tag')
        ->where('tag.tagid=:tagid')
        ->getQuery()
        ->setParameter('tagid', $tagid);
        
        return $query->getResult();
    }
    
    public function getDictionaryType($dictionarytypeid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('dt')
        ->from('App\Entity\TipitakaDictionarytypes','dt')
        ->where('dt.dictionarytypeid=:dtid')
        ->getQuery()
        ->setParameter('dtid', $dictionarytypeid);        
        
        return $query->getOneOrNullResult();
    }
    
    public function persistEntry(TipitakaDictionaryentries $dictionaryentry)
    {     
        $entityManager = $this->getEntityManager();
        
        $paliwordnodiac=self::convertNoDiac($dictionaryentry->getPaliword());
        $dictionaryentry->setPaliwordnodiac($paliwordnodiac);
        
        $explanationPlain=strip_tags($dictionaryentry->getExplanation());
        $dictionaryentry->setExplanationPlain($explanationPlain);
        
        $entityManager->persist($dictionaryentry);
        $entityManager->flush();     
    }
    
    public function findDictionaryByCode(string $code)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('dt.dictionarytypeid')
        ->from('App\Entity\TipitakaDictionarytypes','dt')
        ->where('dt.code=:code')
        ->getQuery()
        ->setParameter('code', $code);
        
        return $query->getOneOrNullResult();
    }
    
    public function listExplanationsByTerm($term)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('de.explanationIds,de.dictionaryentryid')
        ->from('App\Entity\TipitakaDictionaryentries','de')
        ->where('de.paliword=:term')
        ->andWhere("de.explanationIds IS NOT NULL")
        ->andWhere("de.explanationIds!=''")
        ->getQuery()
        ->setParameter('term', $term);
        
        $term_ids=$query->getResult();
        
        $explanations=array();        
        
        foreach($term_ids as $ids)
        {
            $explanation=array();
            $ar_ids=explode(',',$ids['explanationIds']);
            
            $query = $entityManager->createQueryBuilder()
            ->select('st.translation,c.paragraphid')
            ->from('App\Entity\TipitakaSentenceTranslations','st')
            ->innerJoin('st.sentenceid', 's')
            ->innerJoin('s.paragraphid', 'c')
            ->where('st.sentencetranslationid IN(:stid)')
            ->getQuery()
            ->setParameter('stid', $ar_ids);
            
            $explanation["translations"]=$query->getResult();
            $explanation["dictionaryentryid"]=$ids['dictionaryentryid'];
            
            $query = $entityManager->createQueryBuilder()
            ->select('c.paragraphid,toc.title')
            ->from('App\Entity\TipitakaSentenceTranslations','st')
            ->innerJoin('st.sentenceid', 's')
            ->innerJoin('s.paragraphid', 'c')
            ->innerJoin('c.nodeid', 'toc')            
            ->where('st.sentencetranslationid IN(:stid)')
            ->addGroupBy('c.paragraphid,toc.title')
            ->getQuery()
            ->setParameter('stid', $ar_ids);
            $paragraphs=$query->getResult();
            
            $explanation['paragraphs']=$paragraphs;
            
            $explanations[]=$explanation;
        }
        
        return $explanations;
    }
    
    public function getUse($useid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('u')
        ->from('App\Entity\TipitakaDictionaryentryUse','u')
        ->where('u.useid=:uid')
        ->getQuery()
        ->setParameter('uid', $useid);
        
        return $query->getOneOrNullResult();
    }
    
    public function persistUse(TipitakaDictionaryentryUse $use)
    {
        $entityManager = $this->getEntityManager();
                
        $entityManager->persist($use);
        $entityManager->flush();
    }
    
    public function listUsesByTerm($term)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('u.useid,de.dictionaryentryid,u.paliword,u.translation,c.paragraphid,toc.title,s.sentencetext,st.translation as sentencetranslation')
        ->from('App\Entity\TipitakaDictionaryentryUse','u')
        ->innerJoin('u.dictionaryentryid','de')
        ->innerJoin('u.sentencetranslationid', 'st')
        ->innerJoin('st.sentenceid', 's')
        ->innerJoin('s.paragraphid', 'c')
        ->innerJoin('c.nodeid', 'toc')
        ->where('de.paliword=:term')
        ->getQuery()
        ->setParameter('term', $term);
        
        return $query->getResult();
    }
}

