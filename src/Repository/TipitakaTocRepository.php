<?php
namespace App\Repository;

use App\Entity\TipitakaToc;
use App\Enums\TagTypes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;


class TipitakaTocRepository  extends ServiceEntityRepository
{  
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipitakaToc::class);
    }
    
    
    public function listPathNodes($nodeid)
    {
        $entityManager = $this->getEntityManager();
        $node=$this->find($nodeid);
        
        $path=$node->getPath();
                
        $parent_nodes=str_replace("\\",",",trim($path,"\\"));
        
        $query = $entityManager->createQueryBuilder()
        ->select('toc','tt')
        ->from('App\Entity\TipitakaToc','toc')
        ->innerJoin('toc.titletypeid', 'tt')
        ->where('toc.nodeid IN (:pn)')
        ->orderBy('toc.path')
        ->getQuery()
        ->setParameter('pn', explode(',',$parent_nodes));            
        
        return $query->getResult();
    }
    
    public function listAllChildNodes($nodeid)
    {
        $entityManager = $this->getEntityManager();
        $node=$this->find($nodeid);
                
        $path=$node->getPath()."%";
        $path=str_replace("\\","\\\\",$path);  
        
        $query = $entityManager->createQueryBuilder()
        ->select('toc.nodeid','tt.name As typename','toc.title','toc.HasTableView','toc.haschildnodes',
            's.sourceid as TranslationSourceID')
        ->from('App\Entity\TipitakaToc','toc')
        ->innerJoin('toc.titletypeid', 'tt')
        ->leftJoin('toc.TranslationSourceID', 's')
        ->where('toc.nodeid=:id')
        ->orWhere('toc.path LIKE :path ')
        ->orderBy('toc.nodeid')
        ->getQuery()
        ->setParameter('id',$nodeid)
        ->setParameter('path',$path);        
        
        return $query->getResult();
    }
    
    public function getBackNextNode($nodeid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('MAX(CASE WHEN toc1.nodeid<:id THEN toc1.nodeid ELSE :nul END) as Prev',
            'MIN(CASE WHEN toc1.nodeid>:id THEN toc1.nodeid ELSE :nul END) As Next')
        ->from('App\Entity\TipitakaToc','toc1')
        ->innerJoin('App\Entity\TipitakaToc', 'toc2',Join::WITH,'toc1.parentid=toc2.parentid')
        ->where('toc2.nodeid=:id')
        ->orderBy('toc1.nodeid')
        ->getQuery()
        ->setParameter('id', $nodeid)
        ->setParameter('nul', NULL);
        
        return $query->getResult();
    }
    
    public function getBackNextNodeWithTranslation($nodeid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('MAX(CASE WHEN toc1.nodeid<:id THEN toc1.nodeid ELSE :nul END) as Prev',
            'MIN(CASE WHEN toc1.nodeid>:id THEN toc1.nodeid ELSE :nul END) As Next')
            ->from('App\Entity\TipitakaToc','toc1')
            ->innerJoin('App\Entity\TipitakaToc', 'toc2',Join::WITH,'toc1.parentid=toc2.parentid')
            ->where('toc2.nodeid=:id')
            ->andWhere('toc1.HasTableView=1')
            ->orderBy('toc1.nodeid')
            ->getQuery()
            ->setParameter('id', $nodeid)
            ->setParameter('nul', NULL);
            
            return $query->getResult();
    }
    
    
    public function getNode($id)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('toc','tt')
        ->from('App\Entity\TipitakaToc','toc')
        ->innerJoin('toc.titletypeid', 'tt')
        ->where('toc.nodeid=:id')
        ->getQuery()
        ->setParameter('id',$id);
        
        return $query->getOneOrNullResult();
    }
    
    public function search($strSearchTerm,$inTranslated)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('toc.title,toc.nodeid,tt.canview,toc.IsHidden,tt.name as typename,toc.HasTableView,toc.textpath')
        ->from('App\Entity\TipitakaToc','toc')
        ->innerJoin('toc.titletypeid', 'tt')
        ->where('toc.title LIKE :search');
        
        if($inTranslated)
        {
            $query = $query->andWhere('toc.HasTranslation=1');
        }
        
        $query = $query->getQuery()
        ->setParameter('search','%'.$strSearchTerm.'%');
        
        return $query->getResult();
    }
        
    public function getPTSParagraphId($path,$volume,$page)
    {
        $path=str_replace("\\","\\\\",$path); 
        
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('c.paragraphid')
        ->from('App\Entity\TipitakaPagenumbers','pn')
        ->innerjoin('pn.paragraphid','c')
        ->innerJoin('c.nodeid', 'toc')
        ->where('toc.path LIKE :path')
        ->andWhere('pn.tipitakaissueid=1')
        ->andWhere('pn.volumenumber=:volume')
        ->andWhere('pn.pagenumber=:page')                
        ->getQuery()
        ->setParameters(['path'=> "$path%",'volume'=>$volume,'page'=>$page]);
        
        return $query->getOneOrNullResult();
    }
    
    
    public function listNodeNames($nodeid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('nn.nodenameid,nn.name,l.name as languageName,u.username')
        ->from('App\Entity\TipitakaNodeNames','nn')
        ->innerJoin('nn.languageid', 'l')
        ->innerJoin('nn.authorid', 'u')
        ->where('nn.nodeid=:id')
        ->getQuery()
        ->setParameter('id', $nodeid);
        
        return $query->getResult();
    }
    
    public function getNodeName($nodenameid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('nn','l','toc')
        ->from('App\Entity\TipitakaNodeNames','nn')
        ->innerJoin('nn.languageid', 'l')
        ->innerJoin('nn.nodeid', 'toc')
        ->where('nn.nodenameid=:id')
        ->getQuery()
        ->setParameter('id', $nodenameid);
        
        return $query->getOneOrNullResult();
    }
    
    public function persistNodeName($nn)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($nn);
        $entityManager->flush();
    }
    
    private function getNamesSubquery()
    {
        $entityManager = $this->getEntityManager();
        $subQuery=$entityManager->createQueryBuilder()
        ->select('nn1.name')
        ->from('App\Entity\TipitakaNodeNames','nn1')
        ->innerJoin('nn1.languageid', 'l')
        ->where('l.code=:locale')
        ->andWhere('toc.nodeid=nn1.nodeid');
        
        return $subQuery;
    }
    
    public function listChildNodesWithNamesTranslation($parentid,$locale)
    {                
        $entityManager = $this->getEntityManager();                  
        
        $query = $entityManager->createQueryBuilder()
        ->select('toc.nodeid','toc.title','toc.haschildnodes','tt.canview','toc.IsHidden','tt.name as typename','toc.HasTableView','s.sourceid as TranslationSourceID','toc.disableview')
        ->addSelect('('.$this->getNamesSubquery()->getDQL().') AS trname')
        ->from('App\Entity\TipitakaToc','toc')
        ->innerJoin('toc.titletypeid', 'tt')
        ->leftJoin('toc.TranslationSourceID', 's');
        
        if($parentid)
        {
            $query=$query->where('toc.parentid=:id and toc.HasTranslation=1')
            ->getQuery()
            ->setParameter('locale', $locale)
            ->setParameter('id', $parentid);
        }
        else
        {
            $query=$query->where('toc.parentid is null')
            ->setParameter('locale', $locale)
            ->getQuery();
        }
        
        return $query->getResult();
    }
    
    public function filterHiddenNodes($nodes,$locale)
    {
        $filtered=array();
        
        foreach($nodes as $node)
        {
            if($node['IsHidden'])
            {
                $grand_children=$this->listChildNodesWithNamesTranslation($node['nodeid'],$locale);
                
                $filtered=array_merge($filtered,$grand_children);
            }
            else
            {
                $filtered[]=$node;
            }
        }
        
        return $filtered;
    }
    
    public function listPathNodesWithNamesTranslation($nodeid,$locale)
    {
        //filter out hidden nodes
        $entityManager = $this->getEntityManager();
        $node=$this->find($nodeid);
        
        $path=$node->getPath();
        
        $parent_nodes=str_replace("\\",",",trim($path,"\\"));
        
        $query=$entityManager->createQueryBuilder()
        ->select('toc.nodeid,toc.title,toc.haschildnodes,tt.canview,toc.HasTableView,s.sourceid as TranslationSourceID,toc.disableview,toc.hasprologue')
        ->addSelect('('.$this->getNamesSubquery()->getDQL().') AS trname')
        ->from('App\Entity\TipitakaToc','toc')
        ->innerJoin('toc.titletypeid', 'tt')
        ->leftJoin('toc.TranslationSourceID', 's')
        ->where('toc.IsHidden=0')
        ->andWhere('toc.nodeid IN (:pn)')
        ->orderBy('toc.path')
        ->getQuery()
        ->setParameter('locale', $locale)
        ->setParameter('pn', explode(',',$parent_nodes));  
        
        return $query->getResult();  
    }    
    
    public function persistNode($node)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($node);
        $entityManager->flush();
    }
    
    public function enableTableView($nodeid)
    {
        $node=$this->find($nodeid);
        $node->setHasTableView(true);
        
        $entityManager = $this->getEntityManager();
        $entityManager->persist($node);
        $entityManager->flush();
    }
    
    public function getNodeWithNameTranslation($nodeid,$locale)
    {                        
        $entityManager = $this->getEntityManager();
        $query=$entityManager->createQueryBuilder()
        ->select('toc.nodeid,toc.title,toc.haschildnodes,tt.canview,toc.IsHidden,tt.name as typename,toc.path,s.sourceid as TranslationSourceID,toc.notes')
        ->addSelect('('.$this->getNamesSubquery()->getDQL().') AS trname')
        ->from('App\Entity\TipitakaToc','toc')
        ->innerJoin('toc.titletypeid', 'tt')
        ->leftJoin('toc.TranslationSourceID', 's')
        ->where('toc.nodeid=:id')
        ->getQuery()
        ->setParameter('id', $nodeid)
        ->setParameter('locale', $locale);
        
        return $query->getOneOrNullResult();        
    }
    
    public function listChildNodesWithNames($parentid,$locale)
    {
        $entityManager = $this->getEntityManager();
        $query=$entityManager->createQueryBuilder()
        ->select('toc.nodeid,toc.title,toc.haschildnodes,tt.canview,toc.IsHidden,tt.name as typename,toc.HasTableView,s.sourceid as TranslationSourceID')
        ->addSelect('('.$this->getNamesSubquery()->getDQL().') AS trname')
        ->from('App\Entity\TipitakaToc','toc')
        ->innerJoin('toc.titletypeid', 'tt')
        ->leftJoin('toc.TranslationSourceID', 's');
        
        if($parentid)
        {
            $query=$query->where('toc.parentid=:id')
            ->getQuery()
            ->setParameter('locale', $locale)
            ->setParameter('id', $parentid);
        }
        else
        {
            $query=$query->where('toc.parentid is null')            
            ->getQuery()
            ->setParameter('locale', $locale);
        }
        
        return $query->getResult();                     
    }
    
    public function listAllChildNodesWithNamesTranslation($node,$locale)
    {        
        $path=$node->getPath()."%";
        $path=str_replace("\\","\\\\",$path);  
        
        $entityManager = $this->getEntityManager();
        $query=$entityManager->createQueryBuilder()
        ->select('toc.nodeid,toc.title,toc.haschildnodes,tt.canview,toc.IsHidden,tt.name as typename,toc.HasTableView,s.sourceid as TranslationSourceID')
        ->addSelect('('.$this->getNamesSubquery()->getDQL().') AS trname')
        ->from('App\Entity\TipitakaToc','toc')
        ->innerJoin('toc.titletypeid', 'tt')
        ->leftJoin('toc.TranslationSourceID', 's')
        ->where('toc.nodeid=:id')
        ->orWhere('toc.path LIKE :path')
        ->orderBy('toc.nodeid')
        ->getQuery()
        ->setParameter('locale', $locale)
        ->setParameter('id', $node->getNodeid())
        ->setParameter('path', $path);
        
        return $query->getResult();        
    }
    
    
    public function listNodesByTag($tagid,$locale)
    {
        $entityManager = $this->getEntityManager();
        $query=$entityManager->createQueryBuilder()
        ->select('toc.nodeid,toc.title,toc.haschildnodes,toc.HasTableView,s.sourceid as TranslationSourceID,ty.canview')
        ->addSelect('('.$this->getNamesSubquery()->getDQL().') AS trname')
        ->from('App\Entity\TipitakaTocTags','tt')
        ->innerJoin('tt.nodeid','toc')
        ->innerJoin('toc.titletypeid', 'ty')
        ->innerJoin('tt.tagid', 't')
        ->leftJoin('toc.TranslationSourceID', 's')
        ->where('t.tagid=:tagid')
        ->getQuery()
        ->setParameter('tagid', $tagid)
        ->setParameter('locale', $locale);
        
        return $query->getResult();
    }
        
    public function listRelatedNodes($nodeid,$locale)
    {
        //find all tags assigned to this node of the type "related texts"
        //find all nodes with these tags excluding current
        $entityManager = $this->getEntityManager();
        $query=$entityManager->createQueryBuilder()
        ->select('toc.nodeid,toc.title,s.sourceid as TranslationSourceID,toc.HasTableView')
        ->addSelect('('.$this->getNamesSubquery()->getDQL().') AS trname')
        ->from('App\Entity\TipitakaTocTags','tt')
        ->innerJoin('tt.tagid', 't')
        ->innerJoin('t.tagtypeid', 'ty')
        ->join('App\Entity\TipitakaTocTags','tt1',Join::WITH,'tt1.tagid=tt.tagid')
        ->innerJoin('tt1.nodeid', 'toc')
        ->leftJoin('toc.TranslationSourceID', 's')
        ->where('tt.nodeid=:nid')
        ->andWhere('ty.tagtypeid=:tyid')
        ->andWhere('toc.nodeid!=:nid1')
        ->getQuery()
        ->setParameter('nid', $nodeid)
        ->setParameter('tyid', TagTypes::Related)
        ->setParameter('nid1', $nodeid)
        ->setParameter('locale', $locale);
        
        return $query->getResult();
    }
    
    public function searchLanguage($strSearch,$languageid,$inTranslated)
    {       
        $entityManager = $this->getEntityManager();
        $query=$entityManager->createQueryBuilder()
        ->select('nn.name as title,toc.nodeid,tt.canview,toc.IsHidden,tt.name as typename,toc.HasTableView,toc.textpath')
        ->from('App\Entity\TipitakaNodeNames','nn')
        ->innerJoin('nn.nodeid','toc')
        ->innerJoin('toc.titletypeid', 'tt')
        ->leftJoin('toc.TranslationSourceID', 's')        
        ->where('nn.name LIKE :search')
        ->andWhere('nn.languageid=:lid');
        
        if($inTranslated)
        {
            $query = $query->andWhere('toc.HasTranslation=1');
        }
        
        $query = $query->getQuery()
        ->setParameter('lid', $languageid)
        ->setParameter('search', '%'.$strSearch.'%');
        
        return $query->getResult();
    }
    

}

