<?php
namespace App\Repository;

use App\Entity\TipitakaToc;
use App\Enums\TagTypes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use App\Entity\TipitakaTitletypes;


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
        ->select('toc.nodeid,tt.name As typename,toc.title,toc.HasTableView,toc.haschildnodes,s.sourceid as TranslationSourceID,toc.urlfull')
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
        
        $result=$query->getResult();
        
        if(sizeof($result)>0)
        {
            if(!$result[0]['Prev'])
            {
                $result[0]['Prev']=$this->findBackNextNodeWithHiddenParent($nodeid,'Prev',false);
            }
            
            if(!$result[0]['Next'])
            {
                $result[0]['Next']=$this->findBackNextNodeWithHiddenParent($nodeid,'Next',false);
            }
        }
        
        return $result;
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
        
        $result=$query->getResult();
        
        if(sizeof($result)==0)
        {
            $result[0]=array();
        }
        
        if(!$result[0]['Prev'])
        {
            $result[0]['Prev']=$this->findBackNextNodeWithHiddenParent($nodeid,'Prev',true);
        }
        
        if(!$result[0]['Next'])
        {
            $result[0]['Next']=$this->findBackNextNodeWithHiddenParent($nodeid,'Next',true);
        }
            
        return $result;
    }
    
    private function findBackNextNodeWithHiddenParent($nodeid,$pos,$hasTableView)
    {
        $backnextnodeid=NULL;
        
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('parent.IsHidden, node.path,parent.nodeid')
        ->from('App\Entity\TipitakaToc','node')
        ->innerJoin('App\Entity\TipitakaToc', 'parent',Join::WITH,'node.parentid=parent.nodeid')
        ->where('node.nodeid=:id')
        ->getQuery()
        ->setParameter('id', $nodeid);
        
        $result1=$query->getSingleResult();
        
        if($result1['IsHidden'])
        {//continue only if parent is hidden
            //1. find first non-hidden parent
            $parent_nodes=str_replace("\\",",",trim($result1['path'],"\\"));
            
            $query = $entityManager->createQueryBuilder()
            ->select('toc.path')
            ->from('App\Entity\TipitakaToc','toc')
            ->where('toc.nodeid IN (:pn)')
            ->andWhere('toc.nodeid<>:nodeid')
            ->andWhere('toc.IsHidden=0')
            ->orderBy('toc.nodeid','DESC')
            ->getQuery()
            ->setParameter('pn', explode(',',$parent_nodes))
            ->setParameter('nodeid', $nodeid)
            ->setMaxResults(1);   
            
            $result2=$query->getSingleResult();
            
            //find level
            $level=strlen($result1['path'])-strlen(str_replace("\\","",$result1['path']));
            
            //2. Find all child nodes of this parent node with the same level as the original node
            //find back or next node in them
            $query = $entityManager->createQueryBuilder()
            ->select('toc.nodeid')
            ->from('App\Entity\TipitakaToc','toc')
            ->where('toc.path LIKE :path')
            ->andWhere("LENGTH(toc.path) - LENGTH(REPLACE(toc.path, '\\', ''))=:level");
            
            if($hasTableView)
            {
                $query = $query->andWhere('toc.HasTableView=1');
            }
            
            if($pos=='Prev')
            {
                $query = $query->andWhere('toc.nodeid<:nodeid')
                ->orderBy('toc.nodeid','DESC');
            }
            
            if($pos=='Next')
            {
                $query = $query->andWhere('toc.nodeid>:nodeid')
                ->orderBy('toc.nodeid','ASC');
            }
                        
            $path=str_replace("\\","\\\\",$result2['path'])."%";
            $query2 = $query->getQuery()
            ->setMaxResults(1)
            ->setParameter('path', $path)
            ->setParameter('nodeid', $nodeid)
            ->setParameter("level", $level);
            
            $result3=$query2->getOneOrNullResult();
            
            if($result3)
            {
                $backnextnodeid=$result3["nodeid"];
            }
            else 
            {
                $query3 = $query->getQuery()
                ->setMaxResults(1)
                ->setParameter('path', $path)
                ->setParameter('nodeid', $nodeid)
                ->setParameter("level", $level-1);
                
                $result3=$query3->getOneOrNullResult();
                
                if($result3)
                {
                    $backnextnodeid=$result3["nodeid"];
                }
            }
        }      
        else
        {
            //if previous or next node has children, find last or first non-hidden child
            $query = $entityManager->createQueryBuilder()
            ->select('toc.nodeid')
            ->from('App\Entity\TipitakaToc','toc')
            ->where('toc.parentid=:parentid')
            ->andWhere('toc.haschildnodes=1');
            
            if($pos=='Prev')
            {
                $query = $query->andWhere('toc.nodeid<:nodeid')
                ->orderBy('toc.nodeid','DESC');
            }
            
            if($pos=='Next')
            {
                $query = $query->andWhere('toc.nodeid>:nodeid')
                ->orderBy('toc.nodeid','ASC');
            }
            
            $query = $query->getQuery()
            ->setMaxResults(1)
            ->setParameter('parentid', $result1["nodeid"])
            ->setParameter('nodeid', $nodeid);
            
            $result2=$query->getOneOrNullResult();
            if($result2)
            {
                $query = $entityManager->createQueryBuilder()
                ->select('MAX(toc.nodeid) As Prev, MIN(toc.nodeid) As Next')
                ->from('App\Entity\TipitakaToc','toc')
                ->where('toc.parentid=:parentid')
                ->andWhere('toc.IsHidden=0');
                
                if($hasTableView)
                {
                    $query = $query->andWhere('toc.HasTableView=1');
                }
                                
                $query = $query->getQuery()
                ->setMaxResults(1)
                ->setParameter('parentid', $result2["nodeid"]);
                
                $result3=$query->getOneOrNullResult();
                
                if($result3)
                {                    
                    $backnextnodeid=$result3[$pos];
                }
            }
        }
        
        return $backnextnodeid;
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
        
        $node=$nn->getNodeid();
        $urlFull=$this->findUrlFull($node);
        $matches=array();
        
        if(preg_match("|\d+[\.:]([-\d]+)|u", $nn->getName(),$matches))
        {
            $node=$nn->getNodeid();
            $node->setUrlpart($matches[1]);
            $node->setUrlfull($urlFull."/".$matches[1]);
            $entityManager->persist($node);
            $entityManager->flush();
        }
        else 
        {
            if(preg_match("|(\d+).|u", $nn->getName(),$matches))
            {
                $node->setUrlpart($matches[1]);
                $node->setUrlfull($urlFull."/".$matches[1]);
                $entityManager->persist($node);
                $entityManager->flush();
            }
            else
            {
                if(preg_match("|\d+-\d+|u", $nn->getName(),$matches))
                {
                    $node->setUrlpart($matches[0]);
                    $node->setUrlfull($urlFull."/".$matches[0]);
                    $entityManager->persist($node);
                    $entityManager->flush();
                }
                else 
                {
                    if(preg_match("|\d+|u", $nn->getName(),$matches))
                    {
                        $node=$nn->getNodeid();
                        $node->setUrlpart($matches[0]);  
                        $node->setUrlfull($urlFull."/".$matches[0]);
                        $entityManager->persist($node);
                        $entityManager->flush();
                    }
                }
            }
        }        
    }
    
    private function findUrlFull(TipitakaToc $node)
    {
        $urlFull=$node->getUrlfull();
        $parentid=$node->getParentid();
        
        while(!is_null($parentid))
        {
            $node=$this->find($parentid);
            $urlFull=$node->getUrlfull();
            if($urlFull)
            {
                break;
            }
            
            $parentid=$node->getParentid();
        }
        
        return $urlFull;
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
        ->select('toc.nodeid,toc.title,toc.haschildnodes,tt.canview,toc.IsHidden,tt.name as typename,toc.HasTableView,s.sourceid as TranslationSourceID,toc.disableview,toc.urlfull')
        ->addSelect('('.$this->getNamesSubquery()->getDQL().') AS trname')
        ->from('App\Entity\TipitakaToc','toc')
        ->innerJoin('toc.titletypeid', 'tt')
        ->leftJoin('toc.TranslationSourceID', 's');
        
        if($parentid)
        {
            $query=$query->where('toc.parentid=:id and toc.HasTranslation=1')
            ->orderBy('toc.nodeid')
            ->getQuery()
            ->setParameter('locale', $locale)
            ->setParameter('id', $parentid);
        }
        else
        {
            $query=$query->where('toc.parentid is null')
            ->orderBy('toc.nodeid')            
            ->getQuery()
            ->setParameter('locale', $locale);
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
        ->select('toc.nodeid,toc.title,toc.haschildnodes,tt.canview,toc.HasTableView,s.sourceid as TranslationSourceID,toc.disableview,toc.hasprologue,toc.HasTranslation,toc.urlfull')
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
        ->select('toc.nodeid,toc.title,toc.haschildnodes,tt.canview,toc.IsHidden,tt.name as typename,toc.path,s.sourceid as TranslationSourceID,toc.notes,toc.disabletranslalign,toc.urlfull')
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
        ->select('toc.nodeid,toc.title,toc.haschildnodes,tt.canview,toc.IsHidden,tt.name as typename,toc.HasTableView,s.sourceid as TranslationSourceID,toc.urlfull')
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
        ->select('toc.nodeid,toc.title,toc.haschildnodes,tt.canview,toc.IsHidden,tt.name as typename,toc.HasTableView,s.sourceid as TranslationSourceID,toc.urlfull')
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
        ->select('toc.nodeid,toc.title,toc.haschildnodes,toc.HasTableView,s.sourceid as TranslationSourceID,ty.canview,toc.urlfull')
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
        ->select('toc.nodeid,toc.title,s.sourceid as TranslationSourceID,toc.HasTableView,toc.urlfull')
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
    
    public function getTitleType($titletypeid): TipitakaTitletypes
    {
        $entityManager = $this->getEntityManager();
        $query=$entityManager->createQueryBuilder()
        ->select("tt")
        ->from("App\Entity\TipitakaTitletypes", "tt")
        ->where("tt.titletypeid=:ttid")
        ->getQuery()
        ->setParameter("ttid", $titletypeid);
        
        return $query->getOneOrNullResult();
    }
    
    public function updateParentNodeId($nodeid,$parentid)
    {
        //update parentid, path and textpath of the current node
        $node=$this->find($nodeid);
        $parent=$this->find($parentid);
        $node->setParentid($parentid);
        $node->setPath($parent->getPath().$node->getNodeid()."\\");
        $node->setTextPath($parent->getTextPath().' '.$node->getTitle()."\\");
        $this->persistNode($node);
        
        //update path and textpath of all descendant nodes                      
        $childNodes=$this->findBy(['parentid'=>$nodeid]);
        
        foreach($childNodes as $childNode)
        {
            $this->fixChildNodePathsRecursive($node,$childNode);
        }
        
        //update maxpagenumber,minpagenumber,maxvolumenumber,minvolumenumber,haschildnodes,hastranslation of the parent node
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('MAX(toc.MaxPageNumber) As MaxPage,MIN(toc.MinPageNumber) As MinPage,MAX(toc.MaxVolumeNumber) As MaxVolume,MIN(toc.MinVolumeNumber) As MinVolume,MAX(toc.HasTranslation) As HasTranslation,MAX(toc.allowptspage) As allowptspage')
        ->from('App\Entity\TipitakaToc','toc')
        ->where('toc.parentid=:nid')
        ->getQuery()
        ->setParameter('nid',$parentid);
        
        $agg=$query->getOneOrNullResult();
        
        $parent->setMaxPageNumber($agg["MaxPage"]);
        $parent->setMinPageNumber($agg["MinPage"]);
        $parent->setMaxVolumeNumber($agg["MaxVolume"]);
        $parent->setMinVolumeNumber($agg["MinVolume"]);
        $parent->setHaschildnodes(true);
        $parent->setHasTranslation($agg["HasTranslation"]);
        $parent->setAllowptspage($agg["allowptspage"]);
        $this->persistNode($parent);
    }
        
    private function fixChildNodePathsRecursive($parent,$child)
    {        
        $child->setPath($parent->getPath().$child->getNodeid()."\\");
        $child->setTextPath($parent->getTextPath().' '.$child->getTitle()."\\");
        $this->persistNode($child);
                
        $childNodes=$this->findBy(['parentid'=>$child->getNodeid()]);
        
        foreach($childNodes as $childNode)
        {
            $this->fixChildNodePathsRecursive($child,$childNode);
        }
    }   
    
    public function calcThisUrlFull($nodeid,$urlpart)
    {
        $node=$this->find($nodeid);
        $node->setUrlpart($urlpart);
        
        $parentid=$node->getParentid();
        
        do
        {            
            $parent=$this->find($parentid);
            $parentid=$parent->getParentid();
        }
        while($parent && $parentid && empty($parent->getUrlfull()));       
        
        if($parent)
        {
            $node->setUrlFull($parent->getUrlFull()."/".$urlpart);
        }
        
        
        $this->persistNode($node);
    }
    
    public function calcChildUrlFull($nodeid,$urlpart,$urlfull)
    {
        $node=$this->find($nodeid);
        $node->setUrlpart($urlpart);
        $node->setUrlfull($urlfull);
        $this->persistNode($node);
        
        $this->calcChildUrlFullRecursive($nodeid,$urlfull);
    }
    
    private function calcChildUrlFullRecursive($parentid,$parentFullUrl)
    {        
        $childNodes=$this->findBy(['parentid'=>$parentid]);
        $entityManager = $this->getEntityManager();
        $childid=1;
        
        foreach($childNodes as $child)
        {
            if(!$child->getIsHidden())
            {//skip hidden
                if(empty($child->getUrlpart()))
                {//if urlpart is empty try to find it in name
                    $query = $entityManager->createQueryBuilder()
                    ->select('nn')
                    ->from('App\Entity\TipitakaNodeNames','nn')
                    ->where('nn.nodeid=:id')
                    ->getQuery()
                    ->setParameter('id', $child->getNodeid());
                    
                    $names=$query->getResult();
                    if(!empty($names))
                    {
                        $name=current($names);
                        $matches=array();
                        
                        if(preg_match("|\d+[\.:]([-\d]+)|u", $name->getName(),$matches))
                        {
                            $child->setUrlpart($matches[1]);
                            $child->setUrlfull($parentFullUrl."/".$matches[1]);
                            $entityManager->persist($child);
                            $entityManager->flush();
                        }
                        else
                        {
                            if(preg_match("|(\d+).|u", $name->getName(),$matches))
                            {
                                $child->setUrlpart($matches[1]);
                                $child->setUrlfull($parentFullUrl."/".$matches[1]);
                                $entityManager->persist($child);
                                $entityManager->flush();
                            }
                            else
                            {
                                if(preg_match("|\d+-\d+|u", $name->getName(),$matches))
                                {
                                    $child->setUrlpart($matches[0]);
                                    $child->setUrlfull($parentFullUrl."/".$matches[0]);
                                    $entityManager->persist($child);
                                    $entityManager->flush();
                                }
                                else
                                {
                                    if(preg_match("|\d+|u", $name->getName(),$matches))
                                    {
                                        $child->setUrlpart($matches[0]);
                                        $child->setUrlfull($parentFullUrl."/".$matches[0]);
                                        $entityManager->persist($child);
                                        $entityManager->flush();
                                    }
                                }
                            }
                        }
                    }
                    
                    if(empty($child->getUrlpart()) && $child->getHasTableView())
                    {
                        if(preg_match("|\d+-\d+|u", $child->getTitle(),$matches))
                        {
                            $child->setUrlpart($matches[0]);
                            $child->setUrlfull($parentFullUrl."/".$matches[0]);
                            $entityManager->persist($child);
                            $entityManager->flush();
                        }
                        else 
                        {
                            if(preg_match("|(\d+).|u", $child->getTitle(),$matches))
                            {
                                $child->setUrlpart($matches[1]);
                                $child->setUrlfull($parentFullUrl."/".$matches[1]);
                                $entityManager->persist($child);
                                $entityManager->flush();
                            }
                            else
                            {
                                $child->setUrlpart("part/$childid");
                                $child->setUrlfull($parentFullUrl."/part/$childid");
                                $entityManager->persist($child);
                                $entityManager->flush();
                            }
                        }
                    }
                }
                else
                {
                    $child->setUrlfull($parentFullUrl."/".$child->getUrlpart());
                    $entityManager->persist($child);
                    $entityManager->flush();
                }
                
                $this->calcChildUrlFullRecursive($child->getNodeid(),$parentFullUrl."/".$child->getUrlpart());
            }
            else
            {
                $this->calcChildUrlFullRecursive($child->getNodeid(),$parentFullUrl);
            }
            
            $childid++;
        }
        
    }
    
}

