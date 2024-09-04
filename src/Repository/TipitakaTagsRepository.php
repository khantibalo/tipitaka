<?php
namespace App\Repository;

use App\Entity\TipitakaTags;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use App\Entity\TipitakaTocTags;
use App\Entity\TipitakaPaliwordTags;
use App\Enums\TagTypes;

class TipitakaTagsRepository extends ServiceEntityRepository
{    
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipitakaTags::class);
    }
    
    public function listByNode($nodeid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('tt.nodetagid,a.username,tt.applydate,tag.tagid,tag.paliname,ty.name as tagtypename,ty.tagtypeid')
        ->from('App\Entity\TipitakaTocTags','tt')
        ->innerJoin('tt.authorid', 'a')
        ->innerJoin('tt.tagid','tag')
        ->innerJoin('tt.nodeid', 'toc')
        ->innerJoin('tag.tagtypeid', 'ty')
        ->where('toc.nodeid=:id')
        ->getQuery()
        ->setParameter('id', $nodeid);
        
        return $query->getResult();
    }
    
    public function listNamesByNode($nodeid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('tt.nodetagid,l.name as languageName,tn.title,tn.tagnameid')
        ->from('App\Entity\TipitakaTagNames','tn')
        ->innerJoin('tn.tagid','tag')
        ->innerJoin('tn.languageid', 'l')
        ->join('App\Entity\TipitakaTocTags', 'tt',Join::WITH,'tag.tagid=tt.tagid')
        ->innerJoin('tt.nodeid', 'toc')
        ->where('toc.nodeid=:id')
        ->getQuery()
        ->setParameter('id', $nodeid);
        
        return $query->getResult();
    }
    
    public function listAssoc($locale,$tagtypeid)
    {
        $results=$this->list($locale,$tagtypeid);
        
        $assoc=array();
        
        foreach($results as $result)
        {
            $assoc[$result['title']]=$result['tagid'];
        }
        
        return $assoc;
    }
    
    public function listTocTagsWithStats($locale,$tagtypeid)
    {
        $entityManager = $this->getEntityManager();
        
        $tocSubquery=$entityManager->createQueryBuilder()
        ->select('COUNT(tt.tagid)')
        ->from('App\Entity\TipitakaTocTags','tt')
        ->where('tt.tagid=t.tagid');
        
        $namesSubquery=$entityManager->createQueryBuilder()
        ->select('COUNT(tn1.tagid)')
        ->from('App\Entity\TipitakaTagNames','tn1')
        ->where('tn1.tagid=t.tagid');
        
        $query = $entityManager->createQueryBuilder()
        ->select('t.tagid,tn.title,ty.tagtypeid, t.paliname')
        ->addSelect('('.$tocSubquery->getDQL().') AS TagCount')
        ->addSelect('('.$namesSubquery->getDQL().') AS NameCount')
        ->from('App\Entity\TipitakaTagNames','tn')
        ->innerJoin('tn.tagid', 't')
        ->innerJoin('tn.languageid','l')
        ->innerJoin('t.tagtypeid', 'ty')
        ->where('l.code=:locale')
        ->andWhere('ty.tagtypeid=:tyid')
        ->orderBy('tn.title')
        ->getQuery()
        ->setParameter('locale', $locale)
        ->setParameter('tyid', $tagtypeid);
                
        return $query->getResult();
    }
    
    public function listTocPaliTagsWithStats($locale)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('t.tagid,tn.title,ty.tagtypeid, COUNT(t.tagid) As TagCount,t.paliname')
        ->from('App\Entity\TipitakaTagNames','tn')
        ->innerJoin('tn.tagid', 't')
        ->innerJoin('tn.languageid','l')
        ->innerJoin('t.tagtypeid', 'ty')
        ->join('App\Entity\TipitakaTocTags','tt',Join::WITH,'tt.tagid=t.tagid')
        ->where('l.code=:locale')
        ->andWhere('t.paliname is not null')
        ->groupBy('t.tagid,tn.title,t.tagtypeid')
        ->orderBy('t.paliname')
        ->getQuery()
        ->setParameter('locale', $locale);
        
        return $query->getResult();
    }
    
    public function getTocTagWithStats($locale,$tagid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('t.tagid,tn.title,ty.tagtypeid, COUNT(t.tagid) As TagCount,t.paliname')
        ->from('App\Entity\TipitakaTagNames','tn')
        ->innerJoin('tn.tagid', 't')
        ->innerJoin('tn.languageid','l')
        ->innerJoin('t.tagtypeid', 'ty')
        ->join('App\Entity\TipitakaTocTags','tt',Join::WITH,'tt.tagid=t.tagid')
        ->where('l.code=:locale')
        ->andWhere('t.tagid=:tagid')
        ->groupBy('t.tagid,tn.title,t.tagtypeid')
        ->orderBy('tn.title')
        ->getQuery()
        ->setParameter('locale', $locale)
        ->setParameter('tagid', $tagid);
        
        return $query->getResult();
    }
        
    public function list($locale,$tagtypeid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('t.tagid,tn.title')
        ->from('App\Entity\TipitakaTagNames','tn')
        ->innerJoin('tn.tagid', 't')
        ->innerJoin('tn.languageid','l')
        ->innerJoin('t.tagtypeid', 'tt')
        ->where('l.code=:locale')
        ->andWhere('tt.tagtypeid=:ttid')
        ->orderBy('tn.title')
        ->getQuery()
        ->setParameter('locale', $locale)
        ->setParameter('ttid', $tagtypeid);
        
        return $query->getResult();
    }
    
    public function updateTagName($tagname)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($tagname);
        $entityManager->flush();
    }
    
    public function updateTag($tag)
    {
        $entityManager = $this->getEntityManager();          
        $entityManager->persist($tag);                        
        $entityManager->flush();      
    }
    
    public function addTagToNode($node,$tag,$userid)
    {
        $entityManager = $this->getEntityManager();  
        
        $toctag=new TipitakaTocTags();
        
        $toctag->setApplydate(new \DateTime());
        $toctag->setAuthorid($userid);
        $toctag->setNodeid($node);
        $toctag->setTagid($tag);
        
        $entityManager->persist($toctag);
        $entityManager->flush();         
    }
    
    public function removeNodeTag($tagid,$nodeid)
    {
        $entityManager = $this->getEntityManager();  
        $query = $entityManager->createQueryBuilder()
        ->select('tt')
        ->from('App\Entity\TipitakaTocTags','tt')
        ->innerJoin('tt.tagid', 't')
        ->innerJoin('tt.nodeid', 'toc')
        ->where('t.tagid=:tagid')
        ->andWhere('toc.nodeid=:nodeid')
        ->getQuery()
        ->setParameter('tagid', $tagid)
        ->setParameter('nodeid', $nodeid);
        
        $nodeTag=$query->getOneOrNullResult();
        
        $entityManager->remove($nodeTag);
        $entityManager->flush();         
    }
    
    public function getTagName($tagnameid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('tn.title,t.paliname,l.languageid,tt.tagtypeid,t.tagid')
        ->from('App\Entity\TipitakaTagNames','tn')
        ->innerJoin('tn.tagid', 't')
        ->innerJoin('tn.languageid', 'l')
        ->innerJoin('t.tagtypeid','tt')
        ->where('tn.tagnameid=:tnid')
        ->getQuery()
        ->setParameter('tnid', $tagnameid);
        
        return $query->getOneOrNullResult();
    }
    
    public function getTagNameObj($tagnameid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('tn')
        ->from('App\Entity\TipitakaTagNames','tn')
        ->where('tn.tagnameid=:tnid')
        ->getQuery()
        ->setParameter('tnid', $tagnameid);
        
        return $query->getOneOrNullResult();
    }
    
    public function listByNodeId($ar_child_node_id,$locale,$path)
    {
        $entityManager = $this->getEntityManager();
        
        $parent_nodes=str_replace("\\",",",trim($path,"\\"));
        
        $query=$entityManager->createQueryBuilder()
        ->select('toc.nodeid,tn.title,t.tagid')
        ->from('App\Entity\TipitakaTocTags','tt')
        ->innerJoin('tt.nodeid','toc')
        ->innerJoin('tt.tagid', 't')
        ->join('App\Entity\TipitakaTagNames','tn',Join::WITH,'tn.tagid=t.tagid')
        ->innerJoin('tn.languageid', 'l')
        ->where('toc.nodeid IN(:cn) or toc.nodeid IN (:pn)')
        ->andWhere('l.code=:locale')
        ->getQuery()
        ->setParameter('cn', $ar_child_node_id)
        ->setParameter('locale', $locale)
        ->setParameter('pn', explode(',',$parent_nodes));
        
        return $query->getResult();
    }
    
    public function getById($tagid,$locale)
    {
        $entityManager = $this->getEntityManager();
        $query=$entityManager->createQueryBuilder()
        ->select('tn.title')
        ->from('App\Entity\TipitakaTagNames','tn')
        ->innerJoin('tn.tagid', 't')
        ->innerJoin('tn.languageid', 'l')
        ->where('t.tagid=:tagid')
        ->andWhere('l.code=:locale')
        ->getQuery()
        ->setParameter('tagid', $tagid)
        ->setParameter('locale', $locale);
        
        return $query->getOneOrNullResult();
    }
    
    public function listTagTypes()
    {
        $entityManager = $this->getEntityManager();
        $query=$entityManager->createQueryBuilder()
        ->select('tt.tagtypeid,tt.name')
        ->from('App\Entity\TipitakaTagtypes','tt')
        ->getQuery();
        
        return $query->getResult();
    }
    
    public function getTagType($tagTypeID)
    {
        $entityManager = $this->getEntityManager();
        $query=$entityManager->createQueryBuilder()
        ->select('tt')
        ->from('App\Entity\TipitakaTagtypes','tt')
        ->where('tt.tagtypeid=:ttid')
        ->getQuery()
        ->setParameter("ttid", $tagTypeID);
        
        return $query->getOneOrNullResult();
    }
    
    public function listNamesByTag($tagid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('l.name as languageName,tn.title,tn.tagnameid')
        ->from('App\Entity\TipitakaTagNames','tn')
        ->innerJoin('tn.tagid','tag')
        ->innerJoin('tn.languageid', 'l')
        ->where('tag.tagid=:id')
        ->getQuery()
        ->setParameter('id', $tagid);
        
        return $query->getResult();
    }
    
    public function addTagToPaliword($paliword,$tag,$userid)
    {
        $entityManager = $this->getEntityManager();
        
        $pwtag=new TipitakaPaliwordTags();        
        
        $pwtag->setApplydate(new \DateTime());
        $pwtag->setAuthorid($userid);
        $pwtag->setPaliword($paliword);
        $pwtag->setTagid($tag);
        
        $entityManager->persist($pwtag);
        $entityManager->flush();
    }
    
    public function removePaliwordTag($tagid,$paliword)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('pt')
        ->from('App\Entity\TipitakaPaliwordTags','pt')
        ->innerJoin('pt.tagid', 't')
        ->where('t.tagid=:tagid')
        ->andWhere('pt.paliword=:paliword')
        ->getQuery()
        ->setParameter('tagid', $tagid)
        ->setParameter('paliword', $paliword);
        
        $pwTag=$query->getOneOrNullResult();
        
        $entityManager->remove($pwTag);
        $entityManager->flush();
    }
    
    public function listByPaliword($paliword)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('pt.paliwordtagid,a.username,pt.applydate,tag.tagid,tag.paliname,ty.name as tagtypename,ty.tagtypeid')
        ->from('App\Entity\TipitakaPaliwordTags','pt')
        ->innerJoin('pt.authorid', 'a')
        ->innerJoin('pt.tagid','tag')
        ->innerJoin('tag.tagtypeid', 'ty')
        ->where('pt.paliword=:paliword')
        ->getQuery()
        ->setParameter('paliword', $paliword);
        
        return $query->getResult();
    }
    
    public function listNamesByPaliword($paliword)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('pt.paliwordtagid,l.name as languageName,tn.title,tn.tagnameid')
        ->from('App\Entity\TipitakaTagNames','tn')
        ->innerJoin('tn.tagid','tag')
        ->innerJoin('tn.languageid', 'l')
        ->join('App\Entity\TipitakaPaliwordTags', 'pt',Join::WITH,'tag.tagid=pt.tagid')
        ->where('pt.paliword=:paliword')
        ->getQuery()
        ->setParameter('paliword', $paliword);
        
        return $query->getResult();
    }
    
    public function listByPaliwordLanguage($paliword,$languagecode)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('tn.title,tag.tagid')
        ->from('App\Entity\TipitakaPaliwordTags','pt')
        ->innerJoin('pt.tagid','tag')
        ->join('App\Entity\TipitakaTagNames', 'tn',Join::WITH,'tag.tagid=tn.tagid')
        ->innerJoin('tn.languageid','l')
        ->where('pt.paliword=:paliword')
        ->andWhere('l.code=:languagecode')
        ->getQuery()
        ->setParameter('paliword', $paliword)
        ->setParameter('languagecode', $languagecode);
        
        return $query->getResult();
    }
    
    public function listTermTagsWithStats($locale)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('t.tagid,tn.title,COUNT(t.tagid) As TagCount,t.paliname')
        ->from('App\Entity\TipitakaTagNames','tn')
        ->innerJoin('tn.tagid', 't')
        ->innerJoin('tn.languageid','l')
        ->join('App\Entity\TipitakaPaliwordTags','pt',Join::WITH,'pt.tagid=t.tagid')
        ->where('l.code=:locale')
        ->groupBy('t.tagid,tn.title,t.tagtypeid')
        ->orderBy('tn.title')
        ->getQuery()
        ->setParameter('locale', $locale);
        
        return $query->getResult();
    }
    
    public function getTermTagsWithStats($locale,$tagid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('t.tagid,tn.title,COUNT(t.tagid) As TagCount,t.paliname')
        ->from('App\Entity\TipitakaTagNames','tn')
        ->innerJoin('tn.tagid', 't')
        ->innerJoin('tn.languageid','l')
        ->join('App\Entity\TipitakaPaliwordTags','pt',Join::WITH,'pt.tagid=t.tagid')
        ->where('l.code=:locale')
        ->andWhere('t.tagid=:tagid')
        ->groupBy('t.tagid,tn.title,t.tagtypeid')
        ->orderBy('tn.title')
        ->getQuery()
        ->setParameter('locale', $locale)
        ->setParameter('tagid', $tagid);
        
        return $query->getResult();
    }
    
    public function listByOneNodeId($nodeid,$locale)
    {
        $entityManager = $this->getEntityManager();
                
        $query=$entityManager->createQueryBuilder()
        ->select('toc.nodeid,tn.title,t.tagid')
        ->from('App\Entity\TipitakaTocTags','tt')
        ->innerJoin('tt.nodeid','toc')
        ->innerJoin('tt.tagid', 't')
        ->innerJoin('t.tagtypeid', 'ty')
        ->join('App\Entity\TipitakaTagNames','tn',Join::WITH,'tn.tagid=t.tagid')
        ->innerJoin('tn.languageid', 'l')
        ->where('toc.nodeid=:nid')
        ->andWhere('l.code=:locale')
        ->andWhere('ty!=:tyid')
        ->getQuery()
        ->setParameter('locale', $locale)
        ->setParameter('nid', $nodeid)
        ->setParameter('tyid', TagTypes::Related);
        
        return $query->getResult();
    }
}

