<?php
namespace App\Repository;

use App\Entity\TipitakaParagraphs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

class TipitakaParagraphsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipitakaParagraphs::class);
    }
    
    private function preparePath($node)
    {
        $path=$node->getPath()."%";
        $path=str_replace("\\","\\\\",$path);
        
        return $path;
    }
    
    
    public function listByNode($node)
    {                        
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('c.paragraphid,c.hastranslation,ct.name as typename,c.paranum,c.caps,c.text,toc.nodeid,c.bold')
        ->from('App\Entity\TipitakaParagraphs','c')
        ->innerJoin('c.nodeid', 'toc')
        ->innerJoin('c.paragraphtypeid', 'ct')
        ->where('toc.nodeid=:id')
        ->orWhere('toc.path LIKE :path ')
        ->orderBy('toc.nodeid')
        ->addOrderBy('c.paragraphid')
        ->getQuery()
        ->setParameter('id',$node->getNodeid())
        ->setParameter('path',$this->preparePath($node));
        
        return $query->getResult();
    }
     
    public function listPageNumbersByNode($node)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('c.paragraphid,pn.pagenumber,pn.position,pn.volumenumber,iss.code as issuecode')
        ->from('App\Entity\TipitakaPagenumbers','pn')
        ->innerJoin('pn.paragraphid', 'c')
        ->innerJoin('c.nodeid', 'toc')
        ->innerJoin('pn.tipitakaissueid','iss')
        ->where('toc.nodeid=:id')
        ->orWhere('toc.path LIKE :path ')
        ->orderBy('toc.nodeid')
        ->getQuery();        
        
        $result=array();
        
        $iterable=$query->iterate(['id'=>$node->getNodeid(),'path'=>$this->preparePath($node)],Query::HYDRATE_ARRAY);
        foreach($iterable as $item)
        {
            $value=array_pop($item);
            $pid=(string)$value['paragraphid'];
            if(!array_key_exists($pid, $result))
            {
                $result[$pid]=array();
            }
            
            $result[$pid][]=$value;
        }
        
        return $result;
    }
    
    public function listPageNumbersByParagraph($id)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('c.paragraphid,pn.pagenumber,pn.position,pn.volumenumber,iss.code as issuecode')
        ->from('App\Entity\TipitakaPagenumbers','pn')
        ->innerJoin('pn.paragraphid', 'c')
        ->innerJoin('c.nodeid', 'toc')
        ->innerJoin('pn.tipitakaissueid','iss')
        ->where('c.paragraphid=:id')
        ->orderBy('toc.nodeid')
        ->getQuery()
        ->setParameter('id',$id);
        
        return $query->getResult();
    }
    
    public function listNotesByNode($node)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('c.paragraphid,n.notetext,n.position')
        ->from('App\Entity\TipitakaNotes','n')
        ->innerJoin('n.paragraphid', 'c')
        ->innerJoin('c.nodeid', 'toc')
        ->where('toc.nodeid=:id')
        ->orWhere('toc.path LIKE :path ')
        ->orderBy('toc.nodeid')
        ->getQuery();
        
        $result=array();
        
        $iterable=$query->iterate(['id'=>$node->getNodeid(),'path'=>$this->preparePath($node)],Query::HYDRATE_ARRAY);
        foreach($iterable as $item)
        {
            $value=array_pop($item);
            $pid=(string)$value['paragraphid'];
            if(!array_key_exists($pid, $result))
            {
                $result[$pid]=array();
            }
            
            $result[$pid][]=$value;
        }
        
        return $result;
    }
    
    public function listNotesByParagraph($id)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('c.paragraphid,n.notetext,n.position')
        ->from('App\Entity\TipitakaNotes','n')
        ->innerJoin('n.paragraphid', 'c')
        ->innerJoin('c.nodeid', 'toc')
        ->where('c.paragraphid=:id')
        ->orderBy('toc.nodeid')
        ->getQuery()
        ->setParameter('id',$id);
        
        return $query->getResult();
    }
    
    public function getParagraph($paragraphid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('c.paragraphid,toc.HasTableView as hastableview,ct.name as typename,c.paranum,c.caps,c.text,toc.nodeid,toc.title as nodetitle,c.bold')
        ->from('App\Entity\TipitakaParagraphs','c')
        ->innerJoin('c.nodeid','toc')
        ->innerJoin('c.paragraphtypeid', 'ct')
        ->where('c.paragraphid=:id')
        ->getQuery()
        ->setParameter('id', $paragraphid);
        
        return $query->getOneOrNullResult();
    }
       
    public function getBackNextParagraph($paragraphid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('c.paragraphid,toc.nodeid')
        ->from('App\Entity\TipitakaParagraphs','c')
        ->innerJoin('c.nodeid', 'toc')
        ->where('c.paragraphid IN(:id)')
        ->orderBy('c.paragraphid')
        ->getQuery()
        ->setParameter('id', [$paragraphid-1,$paragraphid,$paragraphid+1]);
        
        return $query->getResult();
    }

    
    public function listImmediateByNodeId($id)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('c.paragraphid')
        ->from('App\Entity\TipitakaParagraphs','c')
        ->innerJoin('c.nodeid', 'toc')
        ->where('toc.nodeid=:id')
        ->andWhere('c.hastranslation=0')
        ->addOrderBy('c.paragraphid')
        ->getQuery()
        ->setParameter('id',$id);
        
        return $query->getResult();
    }
    
    public function findParagraphByParanum($path,$paranum)
    {
        $path=str_replace("\\","\\\\",$path); 
        
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('c.paragraphid')
        ->from('App\Entity\TipitakaParagraphs','c')
        ->join('c.nodeid','toc')
        ->where('c.paranum=:pn')
        ->andWhere('toc.path LIKE :path')
        ->getQuery()
        ->setParameter('pn',$paranum)
        ->setParameter('path',"$path%");
        
        return $query->getResult();
    }
        
    public function persist(TipitakaParagraphs $paragraph)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($paragraph);
        $entityManager->flush();
    }
}

