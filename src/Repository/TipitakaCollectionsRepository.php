<?php
namespace App\Repository;

use App\Entity\TipitakaCollectionItems;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use App\Entity\TipitakaCollectionItemNames;

class TipitakaCollectionsRepository  extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipitakaCollectionItems::class);
    }
    
    public function listCollections($locale)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('i.collectionitemid,cin.name')
        ->from('App\Entity\TipitakaCollectionItemNames','cin')
        ->innerJoin('cin.collectionitemid', 'i')
        ->innerJoin('cin.languageid', 'l')
        ->where('l.code=:locale')
        ->andWhere('i.parentid IS NULL')
        ->orderBy('i.vieworder')
        ->getQuery()
        ->setParameter('locale', $locale);
        
        return $query->getResult();
    }
    
    public function fetchCollection($collectionitemid,$locale)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('i.collectionitemid,cin.name,i.notes,i.defaultview')
        ->from('App\Entity\TipitakaCollectionItemNames','cin')
        ->innerJoin('cin.collectionitemid', 'i')
        ->innerJoin('cin.languageid', 'l')
        ->where('l.code=:locale')
        ->andWhere('i.parentid IS NULL')
        ->andWhere('i.collectionitemid=:cid')
        ->orderBy('i.vieworder')
        ->getQuery()
        ->setParameter('locale', $locale)
        ->setParameter('cid', $collectionitemid);
        
        return $query->getSingleResult();
    }
    
    public function getCollectionItemName($collectionitemid,$locale)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('i.collectionitemid,cin.name')
        ->from('App\Entity\TipitakaCollectionItemNames','cin')
        ->innerJoin('cin.collectionitemid', 'i')
        ->innerJoin('cin.languageid', 'l')
        ->where('l.code=:locale')
        ->andWhere('i.collectionitemid=:cid')
        ->orderBy('i.vieworder')
        ->getQuery()
        ->setParameter('locale', $locale)
        ->setParameter('cid', $collectionitemid);
        
        return $query->getOneOrNullResult();
    }
    
    public function updateCollectionItem(TipitakaCollectionItems $item)
    {
        $entityManager = $this->getEntityManager();  
        $entityManager->persist($item);
        $entityManager->flush();   
    }
    
    public function createItemName($collectionitem,$name,$language)
    {
        $entityManager = $this->getEntityManager();   
        $nameItem=new TipitakaCollectionItemNames();
        $nameItem->setCollectionitemid($collectionitem);
        $nameItem->setName($name);
        $nameItem->setLanguageid($language);
        
        $entityManager->persist($nameItem);
        $entityManager->flush();  
    }
    
    public function listCollectionItems($collectionid,$locale)
    {
        $entityManager = $this->getEntityManager();
        
        $nodeNameSubQuery=$entityManager->createQueryBuilder()
        ->select('nn1.name')
        ->from('App\Entity\TipitakaNodeNames','nn1')
        ->innerJoin('nn1.languageid', 'l')
        ->where('l.code=:locale')
        ->andWhere('toc.nodeid=nn1.nodeid');
        
        $colItemNameSubQuery=$entityManager->createQueryBuilder()
        ->select('cin1.name')
        ->from('App\Entity\TipitakaCollectionItemNames','cin1')
        ->innerJoin('cin1.languageid', 'l1')
        ->where('l1.code=:locale')
        ->andWhere('ci.collectionitemid=cin1.collectionitemid');
        
        $altPaliNameSubQuery=$entityManager->createQueryBuilder()
        ->select('cin2.name')
        ->from('App\Entity\TipitakaCollectionItemNames','cin2')
        ->innerJoin('cin2.languageid', 'l2')
        ->where("l2.code='pi'")
        ->andWhere('ci.collectionitemid=cin2.collectionitemid');
                
        $query = $entityManager->createQueryBuilder()
        ->select('ci.collectionitemid,ci.vieworder,ci.level,toc.nodeid,toc.HasTableView,so.sourceid as TranslationSourceID,ci.parentid,toc.title,ci.limitrows,ci.hidetitleprint,ci.hidepalinameprint')
        ->addSelect('('.$nodeNameSubQuery->getDQL().') AS nodeName')
        ->addSelect('('.$colItemNameSubQuery->getDQL().') AS colItemName')
        ->addSelect('('.$altPaliNameSubQuery->getDQL().') AS altPaliName')
        ->from('App\Entity\TipitakaCollectionItems','ci')
        ->leftJoin('ci.nodeid', 'toc')
        ->leftJoin('toc.TranslationSourceID','so')
        ->where('ci.parentid=:collectionid')
        ->orderBy('ci.vieworder')
        ->getQuery()
        ->setParameter('locale', $locale)
        ->setParameter('collectionid', $collectionid);
        
        return $query->getResult();
    }
    
    public function deleteCollectionItem($collectionItem)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($collectionItem);
        $entityManager->flush();  
    }
    
    public function listNames($itemid)
    {
        $entityManager = $this->getEntityManager();
        
        $query = $entityManager->createQueryBuilder()
        ->select('cin.itemnameid,cin.name,l.name as languageName')
        ->from('App\Entity\TipitakaCollectionItemNames','cin')
        ->innerJoin('cin.languageid', 'l')
        ->where('cin.collectionitemid=:itemid')
        ->getQuery()
        ->setParameter('itemid', $itemid);
        
        return $query->getResult();
    }
    
    public function getItemName($itemnameid)
    {
        $entityManager = $this->getEntityManager();
        
        $query = $entityManager->createQueryBuilder()
        ->select('cin')
        ->from('App\Entity\TipitakaCollectionItemNames','cin')
        ->where('cin.itemnameid=:itemnameid')
        ->getQuery()
        ->setParameter('itemnameid', $itemnameid);
        
        return $query->getOneOrNullResult();
    }
    
    public function persistItemName($itemname)
    {
        $entityManager = $this->getEntityManager(); 
        $entityManager->persist($itemname);
        $entityManager->flush(); 
    }
    
    public function deleteCollectionItemName($itemname)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($itemname);
        $entityManager->flush();  
    }
    
    public function listParagraphs($collectionid)
    {
        $entityManager = $this->getEntityManager();
        
        $query = $entityManager->createQueryBuilder()
        ->select('ci.collectionitemid,c.paragraphid,ct.name as paragraphtype')
        ->from('App\Entity\TipitakaCollectionItems','ci')
        ->innerJoin('App\Entity\TipitakaParagraphs', 'c',Join::WITH,'ci.nodeid=c.nodeid')    
        ->innerJoin('ci.nodeid', 'toc')
        ->innerJoin('c.paragraphtypeid', 'ct')
        ->where('ci.parentid=:collectionid')
        ->getQuery()
        ->setParameter('collectionid', $collectionid);
        
        return $query->getResult();
    }
    
    public function listSentences($nodeid,$limitrows)
    {
        $entityManager = $this->getEntityManager();
        
        $query = $entityManager->createQueryBuilder()
        ->select('c.paragraphid,s.sentenceid,s.sentencetext,s.commentcount,s.lastcomment')
        ->from('App\Entity\TipitakaSentences','s')
        ->innerJoin('s.paragraphid', 'c')
        ->innerJoin('c.nodeid', 'toc')
        ->where('toc.nodeid=:nodeid')
        ->andWhere('s.sentenceid IN(:limitrows)')
        ->getQuery()
        ->setParameter('nodeid', $nodeid)
        ->setParameter('limitrows', explode(',',$limitrows));
        
        return $query->getResult();
    }
    
    public function listCommentsByCollectionIdForPrint($collectionid)
    {
        $entityManager = $this->getEntityManager();
        $query=$entityManager->createQueryBuilder()
        ->select('co.commentid,co.commenttext,co.forprint,s.sentenceid')
        ->from('App\Entity\TipitakaComments','co')
        ->innerjoin('co.sentenceid','s')
        ->innerJoin('s.paragraphid', 'c')
        ->innerJoin('c.nodeid', 'toc')
        ->innerJoin('App\Entity\TipitakaCollectionItems', 'ci',Join::WITH,'ci.nodeid=toc.nodeid')
        ->where('ci.parentid=:collectionid')
        ->andWhere('co.forprint!=0')
        ->getQuery()
        ->setParameter('collectionid', $collectionid);
        
        return $query->getResult();
    }
    
    public function getBackNextCollectionItem($collectionitemid)
    {
        $entityManager = $this->getEntityManager();        
                       
        $collectionItem=$this->find($collectionitemid);
        
        //FIXME: better way of doing this proivided we can have items with the same vieworder
        
        $results = $entityManager->createQueryBuilder()
        ->select("ci.collectionitemid")
        ->from('App\Entity\TipitakaCollectionItems','ci')
        ->where('ci.parentid=:collectionid')
        ->andWhere('ci.nodeid is not null')
        ->addOrderBy('ci.vieworder')
        ->getQuery()
        ->setParameter('collectionid', $collectionItem->getParentid())
        ->getResult();
        
        $backid=null;
        $nextid=null;
        
        for($i=0;$i<sizeof($results);$i++)
        {
            if($results[$i]["collectionitemid"]==$collectionitemid)
            {
                if($i!=0)
                {
                    $backid=$results[$i-1]["collectionitemid"];
                }
                
                if($i!=sizeof($results)-1)
                {
                    $nextid=$results[$i+1]["collectionitemid"];
                }
                
                break;
            }
        }
                
        return ['back_id'=>$backid,'next_id'=>$nextid];
    }
    
    public function getChapterName($collectionitemid,$locale)
    {
        $entityManager = $this->getEntityManager();
        
        $collectionItem=$this->find($collectionitemid);
        
        $result = $entityManager->createQueryBuilder()
        ->select('cin.name')
        ->from('App\Entity\TipitakaCollectionItems','ci')
        ->innerJoin('App\Entity\TipitakaCollectionItemNames','cin',Join::WITH,'ci.collectionitemid=cin.collectionitemid')
        ->innerJoin('cin.collectionitemid', 'i')
        ->innerJoin('cin.languageid', 'l')
        ->where('ci.parentid=:collectionid')
        ->andWhere('ci.nodeid is null')
        ->andWhere('ci.vieworder<:vieworder')
        ->andWhere('l.code=:locale')
        ->andWhere('ci.level<:level')
        ->addOrderBy('ci.vieworder','DESC')
        ->getQuery()
        ->setParameter('collectionid', $collectionItem->getParentid())
        ->setParameter('locale', $locale)
        ->setParameter('vieworder', $collectionItem->getVieworder())
        ->setParameter('level', $collectionItem->getLevel())
        ->setMaxResults(1)
        ->getOneOrNullResult();
        

        return $result;
    }
}

