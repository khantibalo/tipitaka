<?php
namespace App\Repository;

use App\Entity\TipitakaSentences;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use App\Entity\TipitakaSentenceTranslations;

class TipitakaSentencesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipitakaSentences::class);
    }
    
    public function listByParagraphid($paragraphid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('s.sentenceid,s.sentencetext,s.commentcount,s.lastcomment')
        ->from('App\Entity\TipitakaSentences','s')
        ->where('s.paragraphid=:id')
        ->orderBy('s.sentenceid')
        ->getQuery()
        ->setParameter('id',$paragraphid);
        
        return $query->getResult();
    }
    
    public function listTranslationsByParagraphid($paragraphid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('s.sentenceid,t.translation,so.name as sourcename,t.dateupdated,t.sentencetranslationid,so.sourceid,u.userid')
        ->from('App\Entity\TipitakaSentenceTranslations','t')
        ->innerJoin('t.sentenceid', 's')
        ->innerJoin('t.sourceid','so')
        ->innerJoin('t.userid', 'u')
        ->where('s.paragraphid=:id')
        ->orderBy('s.sentenceid')
        ->getQuery()
        ->setParameter('id',$paragraphid);
        
        return $query->getResult();
    }
    
    public function countSentences($paragraphid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('COUNT(s) as tc')
        ->from('App\Entity\TipitakaSentences','s')
        ->where('s.paragraphid=:id')
        ->getQuery()
        ->setParameter('id',$paragraphid);
        
        return $query->getOneOrNullResult();
    }
    
    public function addSentences($paragraph,$ar_sentences)
    {
        $entityManager = $this->getEntityManager();   
                        
        foreach($ar_sentences as $sentenceText)
        {
            $sentence=new TipitakaSentences();
            
            $sentence->setParagraphid($paragraph);
            $sentence->setSentencetext($sentenceText);
            $sentence->setCommentcount(0);
            
            $entityManager->persist($sentence);   
        }
                
        $entityManager->flush();                
    }

    public function listLanguages()
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('l.languageid,l.name')
        ->from('App\Entity\TipitakaLanguages','l')
        ->getQuery();
        
        $assoc=array();
        
        foreach($query->getResult() as $result)
        {
            $assoc[$result['name']]=$result['languageid'];
        }
        
        return $assoc;
    }
    
    public function getTranslation($translationId): ?TipitakaSentenceTranslations
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('st','s','u')
        ->from('App\Entity\TipitakaSentenceTranslations','st')
        ->innerJoin('st.sentenceid', 's')
        ->innerJoin('st.userid','u')
        ->where('st.sentencetranslationid=:stid')
        ->getQuery()
        ->setParameter('stid', $translationId);
        
        return $query->getOneOrNullResult();
    }
        
    public function getLanguage($id)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('l')
        ->from('App\Entity\TipitakaLanguages','l')
        ->where('l.languageid=:id')
        ->getQuery()
        ->setParameter('id',$id);
        
        return $query->getOneOrNullResult();
    }
    
    public function getLanguageByCode($code)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('l')
        ->from('App\Entity\TipitakaLanguages','l')
        ->where('l.code=:code')
        ->getQuery()
        ->setParameter('code',$code);
        
        return $query->getOneOrNullResult();
    }
    
    public function findSourceByUserId($id)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('s.sourceid')
        ->from('App\Entity\TipitakaSources','s')
        ->where('s.userid=:id')
        ->getQuery()
        ->setParameter('id',$id);
        
        return $query->getOneOrNullResult();
    }
    
    public function listSources($sentenceId)
    {        
        $entityManager = $this->getEntityManager();
        
        //find paragraphid
        $querycid=$queryExclude = $entityManager->createQueryBuilder()
        ->select('c.paragraphid')
        ->from('App\Entity\TipitakaSentences','s1')
        ->innerJoin('s1.paragraphid', 'c')
        ->where('s1.sentenceid=:id')
        ->getQuery()
        ->setParameter('id',$sentenceId);
        
        $cidResult=$querycid->getOneOrNullResult();
        $cid=$cidResult['paragraphid'];
        //this will exclude already present sources 
        $queryExclude = $entityManager->createQueryBuilder()
        ->select('so1.sourceid')
        ->from('App\Entity\TipitakaSentenceTranslations','st1')
        ->innerJoin('st1.sourceid', 'so1')
        ->innerJoin('st1.sentenceid','s1')
        ->where('s1.paragraphid=:id')
        ->getQuery();
                        
        $query = $entityManager->createQueryBuilder()
        ->select('s.name as sourcename,l.name as languagename,s.sourceid')
        ->from('App\Entity\TipitakaSources','s')
        ->innerJoin('s.languageid', 'l')
        ->where('s.sourceid NOT IN('.$queryExclude->getDQL().')')
        ->addOrderBy('s.name')
        ->getQuery()
        ->setParameter('id',$cid);
        
        $assoc=array();
        
        foreach($query->getResult() as $result)
        {
            $assoc[$result["sourcename"].' '.$result["languagename"]]=$result["sourceid"];
        }
        
        return $assoc;
    }
    
    public function listAllSources()
    { 
        $entityManager = $this->getEntityManager();
        
        $query = $entityManager->createQueryBuilder()
        ->select('s.name as sourcename,l.name as languagename,s.sourceid')
        ->from('App\Entity\TipitakaSources','s')
        ->innerJoin('s.languageid', 'l')
        ->addOrderBy('s.name')
        ->getQuery();
        
        $assoc=array();
        
        foreach($query->getResult() as $result)
        {
            $assoc[$result["sourcename"].' '.$result["languagename"]]=$result["sourceid"];
        }
        
        return $assoc;
    }
    
    public function getSource($id)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('s','l')
        ->from('App\Entity\TipitakaSources','s')
        ->innerJoin('s.languageid', 'l')
        ->where('s.sourceid=:id')
        ->getQuery()
        ->setParameter('id',$id);
        
        return $query->getOneOrNullResult();
    }
    
    public function listParagraphSources($paragraphId)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('so.sourceid,so.name As sourcename,l.name As languagename,so.hasformatting')
        ->from('App\Entity\TipitakaSentenceTranslations','st')
        ->innerJoin('st.sentenceid','s')
        ->innerJoin('st.sourceid','so')
        ->innerJoin('so.languageid','l')
        ->where('s.paragraphid=:id')
        ->groupBy('so.sourceid,so.name,l.name')
        ->orderBy('l.languageid','desc')
        ->addOrderBy('so.name')
        ->getQuery()
        ->setParameter('id',$paragraphId);
        
        return $query->getResult();
    }
    
    public function listByNodeId($nodeid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('c.paragraphid,s.sentenceid,s.sentencetext,s.commentcount,s.lastcomment')
        ->from('App\Entity\TipitakaSentences','s')
        ->innerJoin('s.paragraphid','c')
        ->innerJoin('c.nodeid', 'toc')
        ->where('toc.nodeid=:id')
        ->orderBy('c.paragraphid,s.sentenceid')
        ->getQuery()
        ->setParameter('id',$nodeid);
        
        return $query->getResult();
    }
    
    public function listTranslationsByNodeId($nodeid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('s.sentenceid,t.translation,so.name as sourcename,t.dateupdated,t.sentencetranslationid,so.sourceid,u.userid')
        ->from('App\Entity\TipitakaSentenceTranslations','t')
        ->innerJoin('t.sentenceid', 's')
        ->innerJoin('t.sourceid','so')
        ->innerJoin('s.paragraphid', 'c')
        ->innerJoin('c.nodeid','toc')
        ->innerJoin('t.userid','u')
        ->where('toc.nodeid=:id')
        ->getQuery()
        ->setParameter('id',$nodeid);
        
        return $query->getResult();
    }
    
    public function listNodeSources($nodeid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('so.sourceid,so.name As sourcename,l.name As languagename,so.hasformatting,so.ishidden,l.languageid,u.userid')
        ->from('App\Entity\TipitakaSentenceTranslations','st')
        ->innerJoin('st.sentenceid','s')
        ->innerJoin('st.sourceid','so')
        ->innerJoin('so.languageid','l')
        ->innerJoin('s.paragraphid', 'c')
        ->innerJoin('c.nodeid', 'toc')
        ->leftJoin('so.userid', 'u')
        ->where('toc.nodeid=:id')
        ->groupBy('so.sourceid,so.name,l.name')
        ->orderBy('l.languageid','desc')
        ->addOrderBy('so.name')
        ->getQuery()
        ->setParameter('id',$nodeid);
        
        return $query->getResult();
    }
    
    public function getNodeIdBySentenceId($sentenceid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('toc.nodeid')
        ->from('App\Entity\TipitakaSentences','s')
        ->innerJoin('s.paragraphid', 'c')
        ->innerJoin('c.nodeid', 'toc')
        ->where('s.sentenceid=:id')
        ->getQuery()
        ->setParameter('id',$sentenceid);
        
        return $query->getOneOrNullResult();
    }
    
    public function getNodeIdByTranslationId($translationid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('toc.nodeid')
        ->from('App\Entity\TipitakaSentenceTranslations','st')
        ->innerJoin('st.sentenceid','s')
        ->innerJoin('s.paragraphid', 'c')
        ->innerJoin('c.nodeid', 'toc')
        ->where('st.sentencetranslationid=:id')
        ->getQuery()
        ->setParameter('id',$translationid);
        
        return $query->getOneOrNullResult();
    }
    
    public function importTranslations($translations,$sourceid,$nodeid,$user,$paliskip)
    {
        //loop by sentences that belong to this node paragraphs                       
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('s')
        ->from('App\Entity\TipitakaSentences','s')
        ->innerJoin('s.paragraphid', 'c')
        ->innerJoin('c.nodeid', 'toc')
        ->where('toc.nodeid=:id')
        ->orderBy('c.paragraphid,s.sentenceid')
        ->getQuery()
        ->setFirstResult($paliskip)
        ->setParameter('id',$nodeid);
        
        $sentences=$query->getResult();
        
        $trQuery=$entityManager->createQueryBuilder()
        ->select('st')
        ->from('App\Entity\TipitakaSentenceTranslations','st')
        ->where('st.sentenceid=:sentenceid')
        ->andWhere('st.sourceid=:sourceid')
        ->getQuery();
        
        $source=$this->getSource($sourceid);
        
        for($i=0;$i<sizeof($translations);$i++)
        {
            if($i>=sizeof($sentences))
            {
                //add empty sentences to the last paragraph
                $paragraphid=$sentences[sizeof($sentences)-1]->getParagraphid();
                
                $sentence=new TipitakaSentences();
                $sentence->setParagraphid($paragraphid);
                $sentence->setSentencetext('');
                $sentence->setCommentcount(0);
                
                $entityManager->persist($sentence);
                $entityManager->flush();
                
                $sentences[]=$sentence;
            }
            
            //if we already have a translation - edit it, otherwise create new
            $translation=$trQuery->setParameter('sentenceid', $sentences[$i]->getSentenceid())
            ->setParameter('sourceid',$sourceid)->getOneOrNullResult();
            if($translation)
            {//TODO: this may be a translation of another author we may have no permission to edit
                $translation->setTranslation($translations[$i] ?? '');
                $translation->setUserid($user);
                $translation->setDateupdated(new \DateTime()); 
            }
            else
            {
                $translation=new TipitakaSentenceTranslations();                    
                $translation->setSourceid($source);                    
                $translation->setUserid($user);                    
                $translation->setDateupdated(new \DateTime());                    
                $translation->setSentenceid($sentences[$i]);
                $translation->setTranslation($translations[$i] ?? '');                                        
            }
    
            $this->persistTranslation($translation);
            
        }        
        
        $entityManager->flush(); 
    }
    
    public function join($sentenceid,$user)
    {
        //0. find next sentence
        $sentence=$this->find($sentenceid);
        $node=$this->getNodeIdBySentenceId($sentenceid);
        $sources=$this->listNodeSources($node["nodeid"]);
                
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('s')
        ->from('App\Entity\TipitakaSentences','s')
        ->innerJoin('s.paragraphid', 'c')
        ->innerJoin('c.nodeid', 'toc')
        ->where('s.sentenceid>:sid')
        ->andWhere('s.paragraphid=:cid')
        ->andWhere('toc.nodeid=:nodeid')
        ->setMaxResults(1)
        ->getQuery()
        ->setParameter('sid', $sentence->getSentenceid())
        ->setParameter('cid', $sentence->getParagraphid())
        ->setParameter('nodeid', $node['nodeid']);
        
        $next=$query->getOneOrNullResult();
        if($next)
        {
            //1. join translations            
            $qTranslation = $entityManager->createQueryBuilder()
            ->select('st')
            ->from('App\Entity\TipitakaSentenceTranslations','st')
            ->join('st.sentenceid','s')
            ->where('s.sentenceid=:sid')
            ->andWhere('st.sourceid=:oid')
            ->getQuery();
                        
            //iterate over sources
            //find translation to the next sentence with this source and then join them
            foreach($sources as $source)
            {
                $thisTranslation=$qTranslation->setParameter('sid', $sentence->getSentenceid())
                ->setParameter('oid', $source['sourceid'])
                ->getOneOrNullResult();
                
                $nextTranslation=$qTranslation->setParameter('sid', $next->getSentenceid())
                ->setParameter('oid', $source['sourceid'])
                ->getOneOrNullResult();
                
                if($thisTranslation && $nextTranslation)
                {//both translations exist. merge text, then remove second one
                    $thisTranslation->setTranslation($thisTranslation->getTranslation().' '.$nextTranslation->getTranslation());
                    $thisTranslation->setUserid($user);
                    $thisTranslation->setDateupdated(new \DateTime());  
                    
                    $entityManager->persist($thisTranslation);
                    
                    $entityManager->remove($nextTranslation);
                }
                
                if(is_null($thisTranslation) && $nextTranslation)
                {//no translation for this, but there is a translation for next. just change sentence
                    $nextTranslation->setSentenceid($sentence);
                    $nextTranslation->setUserid($user);
                    $nextTranslation->setDateupdated(new \DateTime());
                    
                    $entityManager->persist($nextTranslation);                    
                }
            }            
            
            //2. join sentences
            $sentence->setSentencetext($sentence->getSentencetext().' '.$next->getSentencetext());
            $entityManager->persist($sentence);
            
            $entityManager->remove($next);
        }
        
        $entityManager->flush();
    }
    
    public function translationShiftDown($translationid)
    {        
        
        $entityManager = $this->getEntityManager();
                
        $query = $entityManager->createQueryBuilder()
        ->select('so.sourceid,toc.nodeid,s.sentenceid')
        ->from('App\Entity\TipitakaSentenceTranslations','st')        
        ->innerJoin('st.sourceid', 'so')
        ->innerJoin('st.sentenceid','s')
        ->innerJoin('s.paragraphid','c')
        ->innerJoin('c.nodeid','toc')
        ->where('st.sentencetranslationid=:stid')
        ->getQuery()
        ->setParameter('stid', $translationid);
        
        $sentenceInfo=$query->getOneOrNullResult();
        //find all translations from the end to the current translation in this source
        $query = $entityManager->createQueryBuilder()
        ->select('st','s')
        ->from('App\Entity\TipitakaSentenceTranslations','st')
        ->innerJoin('st.sentenceid', 's')
        ->innerJoin('s.paragraphid','c')
        ->innerJoin('c.nodeid', 'toc')
        ->where('s.sentenceid>=:sid')
        ->andWhere('st.sourceid=:soid')
        ->andWhere('toc.nodeid=:nodeid')
        ->orderBy('c.paragraphid','desc')
        ->orderBy('s.sentenceid','desc')
        ->getQuery()
        ->setParameter('sid', $sentenceInfo['sentenceid'])
        ->setParameter('soid', $sentenceInfo['sourceid'])
        ->setParameter('nodeid', $sentenceInfo['nodeid']);
        
        $translations=$query->getResult();
        
        //next sentence of the original text
        $qNextSentence=$entityManager->createQueryBuilder()
        ->select('s')
        ->from('App\Entity\TipitakaSentences','s')
        ->join('s.paragraphid','c')
        ->where('s.sentenceid>:sid')
        ->andWhere('c.nodeid=:nid')
        ->orderBy('s.sentenceid')
        ->setMaxResults(1)
        ->getQuery();
        
        //iterate translations from the end
        foreach($translations as $translation)
        {//finding next sentence of the original text        
            $next=$qNextSentence
            ->setParameter('sid', $translation->getSentenceid()->getSentenceid())
            ->setParameter('nid', $sentenceInfo["nodeid"])
            ->getOneOrNullResult();
                       
            if($next)
            {//this translation now assigned to the next sentence
                $translation->setSentenceid($next);
            }
            else 
            {//create new empty sentence and assign this translation to it
                $sentence=new TipitakaSentences();
                $sentence->setParagraphid($translation->getSentenceid()->getParagraphid());
                $sentence->setSentencetext('');
                $sentence->setCommentcount(0);
                
                $entityManager->persist($sentence);
                $entityManager->flush();
                
                $translation->setSentenceid($sentence);
            }
            
            $this->persistTranslation($translation);
        }
        
        $entityManager->flush();
    }
    
    public function translationShiftUp($translationid)
    {
        $entityManager = $this->getEntityManager();
        
        $query = $entityManager->createQueryBuilder()
        ->select('so.sourceid,toc.nodeid,s.sentenceid')
        ->from('App\Entity\TipitakaSentenceTranslations','st')
        ->innerJoin('st.sourceid', 'so')
        ->innerJoin('st.sentenceid','s')
        ->innerJoin('s.paragraphid','c')
        ->innerJoin('c.nodeid','toc')
        ->where('st.sentencetranslationid=:stid')
        ->getQuery()
        ->setParameter('stid', $translationid);
        
        $sentenceInfo=$query->getOneOrNullResult();
        //find previous sentence. if we are already at the top - do nothing
        $qPrevSentence=$entityManager->createQueryBuilder()
        ->select('s')
        ->from('App\Entity\TipitakaSentences','s')
        ->join('s.paragraphid','toc')
        ->where('s.sentenceid<:sid')
        ->andWhere('toc.nodeid=:nid')
        ->orderBy('s.sentenceid','desc')
        ->setMaxResults(1)
        ->getQuery();    
        
        $prev=$qPrevSentence
        ->setParameter('sid', $sentenceInfo['sentenceid'])
        ->setParameter('nid', $sentenceInfo["nodeid"])
        ->getOneOrNullResult();
        if($prev)
        {
            //find its translation in this source
            $prevTranslation=$entityManager->createQueryBuilder()
            ->select('st')
            ->from('App\Entity\TipitakaSentenceTranslations','st')
            ->where('st.sentenceid=:sid')
            ->andWhere('st.sourceid=:soid')
            ->getQuery()
            ->setParameter('sid', $prev->getSentenceid())
            ->setParameter('soid', $sentenceInfo['sourceid'])
            ->getOneOrNullResult();
            
            $translation = $entityManager->createQueryBuilder()
            ->select('st')
            ->from('App\Entity\TipitakaSentenceTranslations','st')
            ->where('st.sentencetranslationid=:stid')
            ->getQuery()
            ->setParameter('stid', $translationid)
            ->getOneOrNullResult();
            
            if($prevTranslation)
            {
                //if it is found - merge its text with previous translation text and delete this translation                
                $prevTranslation->setTranslation($prevTranslation->getTranslation()." ".$translation->getTranslation());
                $entityManager->persist($prevTranslation);
                
                $entityManager->remove($translation);
                $entityManager->flush();
            }
            else
            {//no translation above. just assign this translation to the previous sentence
                $translation->setSentenceid($prev);
                $entityManager->persist($translation);
                $entityManager->flush();
            }
            
            //iterate other translations
            $query = $entityManager->createQueryBuilder()
            ->select('st')
            ->from('App\Entity\TipitakaSentenceTranslations','st')
            ->innerJoin('st.sentenceid', 's')
            ->innerJoin('s.paragraphid','c')
            ->innerJoin('c.nodeid', 'toc')
            ->where('s.sentenceid>=:sid')
            ->andWhere('st.sourceid=:soid')
            ->andWhere('toc.nodeid=:nodeid')
            ->orderBy('c.paragraphid')
            ->addOrderBy('s.sentenceid')
            ->getQuery()
            ->setParameter('sid', $sentenceInfo['sentenceid'])
            ->setParameter('soid', $sentenceInfo['sourceid'])
            ->setParameter('nodeid', $sentenceInfo['nodeid']);
            
            $translations=$query->getResult();
            
            //assign them to the sentence above
            foreach($translations as $translation)
            {
                $prev=$qPrevSentence
                ->setParameter('sid', $translation->getSentenceid()->getSentenceid())
                ->setParameter('nid', $sentenceInfo["nodeid"])
                ->getOneOrNullResult();
                
                $translation->setSentenceid($prev);
                $entityManager->persist($translation);
            }
            
            $entityManager->flush();
        }        
    }
    
    public function getNextSentenceId($sentenceid,$is_node)
    {        
        $entityManager = $this->getEntityManager();
        
        if($is_node==TRUE)
        {                                    
            $query = $entityManager->createQueryBuilder()
            ->select('toc.nodeid')
            ->from('App\Entity\TipitakaSentences','s')
            ->innerJoin('s.paragraphid','c')
            ->innerJoin('c.nodeid','toc')
            ->where('s.sentenceid=:sid')
            ->getQuery()
            ->setParameter('sid', $sentenceid);
            $sentenceInfo=$query->getOneOrNullResult();
            
            $qNextSentence=$entityManager->createQueryBuilder()
            ->select('s.sentenceid')
            ->from('App\Entity\TipitakaSentences','s')
            ->join('s.paragraphid','c')
            ->where('s.sentenceid>:sid')
            ->andWhere('c.nodeid=:nid')
            ->orderBy('s.sentenceid')
            ->setMaxResults(1)
            ->getQuery();     
            
            $next=$qNextSentence
            ->setParameter('sid', $sentenceid)
            ->setParameter('nid', $sentenceInfo["nodeid"])
            ->getOneOrNullResult();                        
        }
        else 
        {
            $query = $entityManager->createQueryBuilder()
            ->select('c.paragraphid')
            ->from('App\Entity\TipitakaSentences','s')
            ->innerJoin('s.paragraphid','c')
            ->where('s.sentenceid=:sid')
            ->getQuery()
            ->setParameter('sid', $sentenceid);
            $sentenceInfo=$query->getOneOrNullResult();
            
            $qNextSentence=$entityManager->createQueryBuilder()
            ->select('s.sentenceid')
            ->from('App\Entity\TipitakaSentences','s')
            ->join('s.paragraphid','c')
            ->where('s.sentenceid>:sid')
            ->andWhere('c.paragraphid=:cid')
            ->orderBy('s.sentenceid')
            ->setMaxResults(1)
            ->getQuery();
            
            $next=$qNextSentence
            ->setParameter('sid', $sentenceid)
            ->setParameter('cid', $sentenceInfo["paragraphid"])
            ->getOneOrNullResult();  
        }
        
        return $next;
    }
    
    public function getTranslationBySentenceSource($sentenceid,$sourceid)
    {
        $entityManager = $this->getEntityManager();
        $query=$entityManager->createQueryBuilder()
        ->select('st.sentencetranslationid')
        ->from('App\Entity\TipitakaSentenceTranslations','st')
        ->join('st.sentenceid','s')
        ->join('st.sourceid','so')
        ->where('s.sentenceid=:sid')
        ->andWhere('so.sourceid=:soid')        
        ->getQuery()
        ->setParameter('sid', $sentenceid)
        ->setParameter('soid', $sourceid);
        
        return $query->getOneOrNullResult();  
    }
    
    public function listTranslationsBySentenceId($sentenceid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('t.translation,so.name as sourcename,so.hasformatting')
        ->from('App\Entity\TipitakaSentenceTranslations','t')
        ->innerJoin('t.sentenceid', 's')
        ->innerJoin('t.sourceid','so')
        ->where('s.sentenceid=:id')
        ->orderBy('so.name')
        ->getQuery()
        ->setParameter('id',$sentenceid);
        
        return $query->getResult();
    }
    
    public function persistTranslation(TipitakaSentenceTranslations $translation)
    {
        $entityManager = $this->getEntityManager();
        
        if($translation->getSentencetranslationid()==NULL)
        {//if this is a new translation, check if we have already have a translation here если добавляем, то проверить, что у этого предложения в этом источнике нет перевода
            $query = $entityManager->createQueryBuilder()
            ->select('t')
            ->from('App\Entity\TipitakaSentenceTranslations','t')
            ->where('t.sentenceid=:seid')
            ->andWhere('t.sourceid=:soid')
            ->getQuery()
            ->setParameter('seid', $translation->getSentenceid()->getSentenceid())
            ->setParameter('soid', $translation->getSourceid()->getSourceid());
            
            $oldTranslation=$query->getOneOrNullResult();
            
            if($oldTranslation)
            {
                $oldTranslation->setTranslation($translation->getTranslation());
                $oldTranslation->setDateupdated($translation->getDateupdated());
                $oldTranslation->setUserid($translation->getUserid());
                $entityManager->persist($oldTranslation);
            }
            else
            {
                $entityManager->persist($translation);
            }
        }
        else 
        {//editing translation     
            //if text is empty, remove row from the database
            if($translation->getTranslation()=="")
            {
                $entityManager->remove($translation);
            }
            else 
            {            
                $entityManager->persist($translation);
            }
        }
        
        $entityManager->flush();
        
        $sentenceid=$translation->getSentenceid()->getSentenceid();
        
        $this->setParagraphHasTranslation($sentenceid);
        $this->setParentNodesHasTranslation($sentenceid);
    }
    
    public function setParentNodesHasTranslation($sentenceid)
    {
        //check hastranslation flag of the node. if not set - set it recursively
        //traverse the tree up to the root until we find a node with hastranslation flag set
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('toc')
        ->from('App\Entity\TipitakaToc','toc')
        ->innerJoin('App\Entity\TipitakaParagraphs', 'c',Join::WITH,'toc.nodeid=c.nodeid')
        ->innerJoin('App\Entity\TipitakaSentences', 's',Join::WITH,'c.paragraphid=s.paragraphid')
        ->where('s.sentenceid=:id')
        ->getQuery()
        ->setParameter('id', $sentenceid);
        
        $node=$query->getOneOrNullResult();
        
        $qParent=$entityManager->createQueryBuilder()
        ->select('toc')
        ->from('App\Entity\TipitakaToc','toc')
        ->where('toc.nodeid=:id')
        ->getQuery();
        
        while($node && !$node->getHasTranslation())
        {
            $node->setHastranslation(true);
            $entityManager->persist($node);
            
            $node=$qParent->setParameter('id', $node->getParentid())->getOneOrNullResult();
        }
        
        $entityManager->flush();  
    }
        
    public function setParagraphHasTranslation($sentenceid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('c')        
        ->from('App\Entity\TipitakaParagraphs','c')
        ->innerJoin('App\Entity\TipitakaSentences', 's',Join::WITH,'c.paragraphid=s.paragraphid')
        ->where('s.sentenceid=:id')
        ->getQuery()
        ->setParameter('id', $sentenceid);
        
        $paragraph=$query->getOneOrNullResult();
        
        if(!$paragraph->getHastranslation())
        {
            $paragraph->setHastranslation(true);
            $entityManager->persist($paragraph);
            $entityManager->flush();  
        }
    }
        
    private function preparePath($nodePath)
    {
        $path=$nodePath."%";
        $path=str_replace("\\","\\\\",$path);
        
        return $path;
    }
    
    public function listChildNodeSources($nodeid,$nodePath)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('so.sourceid,so.name As sourcename,l.name As languagename,so.hasformatting,so.ishidden,u.userid')
        ->from('App\Entity\TipitakaSentenceTranslations','st')
        ->innerJoin('st.sentenceid','s')
        ->innerJoin('st.sourceid','so')
        ->innerJoin('so.languageid','l')
        ->innerJoin('s.paragraphid', 'c')
        ->innerJoin('c.nodeid', 'toc')
        ->leftJoin('so.userid','u')
        ->where('toc.nodeid=:id')
        ->orWhere('toc.path LIKE :path ')
        ->groupBy('so.sourceid,so.name,l.name')
        ->orderBy('l.languageid','desc')
        ->addOrderBy('so.name')
        ->getQuery()
        ->setParameter('id',$nodeid)
        ->setParameter('path',$this->preparePath($nodePath));
        
        return $query->getResult();
    }
    
    public function listChildTranslationsByNodeId($nodeid,$nodePath)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('s.sentenceid,t.translation,so.name as sourcename,t.dateupdated,t.sentencetranslationid,so.sourceid,u.userid')
        ->from('App\Entity\TipitakaSentenceTranslations','t')
        ->innerJoin('t.sentenceid', 's')
        ->innerJoin('t.sourceid','so')
        ->innerJoin('s.paragraphid', 'c')
        ->innerJoin('c.nodeid','toc')
        ->innerJoin('t.userid','u')
        ->where('toc.nodeid=:id')
        ->orWhere('toc.path LIKE :path ')
        ->getQuery()
        ->setParameter('id',$nodeid)
        ->setParameter('path',$this->preparePath($nodePath));
        
        return $query->getResult();
    }
    
    public function listChildByNodeId($nodeid,$nodePath)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('c.paragraphid,s.sentenceid,s.sentencetext,s.commentcount,s.lastcomment,toc.nodeid')
        ->from('App\Entity\TipitakaSentences','s')
        ->innerJoin('s.paragraphid','c')
        ->innerJoin('c.nodeid', 'toc')
        ->where('toc.nodeid=:id')
        ->orWhere('toc.path LIKE :path ')
        ->orderBy('c.paragraphid,s.sentenceid')
        ->getQuery()
        ->setParameter('id',$nodeid)
        ->setParameter('path',$this->preparePath($nodePath));
        
        return $query->getResult();
    }
    
    public function listTranslationsBySourceId($nodeid,$nodePath,$sourceid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('s.sentenceid,t.translation,c.paragraphid')
        ->from('App\Entity\TipitakaSentenceTranslations','t')
        ->innerJoin('t.sentenceid', 's')
        ->innerJoin('t.sourceid','so')
        ->innerJoin('s.paragraphid', 'c')
        ->innerJoin('c.nodeid','toc')
        ->where('so.sourceid=:soid and toc.nodeid=:id')
        ->orWhere('so.sourceid=:soid and toc.path LIKE :path ')
        ->getQuery()
        ->setParameter('id',$nodeid)
        ->setParameter('path',$this->preparePath($nodePath))
        ->setParameter('soid',$sourceid);
        
        return $query->getResult();
    }
    
    public function listSearchLanguagesAssoc()
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('l.languageid,l.name')
        ->from('App\Entity\TipitakaSources','s')
        ->innerJoin('s.languageid','l')
        ->where('s.excludefromsearch=0')
        ->groupBy('l.languageid,l.name')       
        ->getQuery();
        
        $results=$query->getResult();
        $assoc=array();
        
        foreach ($results as $result)
        {
            $assoc[$result['name']]=$result['languageid'];
        }
                
        return $assoc;
    }
        
    public function searchTranslation($searchString,$languageid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('st.translation,c.paragraphid,toc.textpath,se.sentencetext')
        ->from('App\Entity\TipitakaSentenceTranslations','st')
        ->innerJoin('st.sourceid', 'so')
        ->innerJoin('so.languageid', 'l')
        ->innerJoin('st.sentenceid','se')
        ->innerJoin('se.paragraphid','c')
        ->innerJoin('c.nodeid','toc')
        ->where("st.translation like :st")
        ->andWhere('l.languageid=:lid')
        ->getQuery()
        ->setParameter('st', '%'.$searchString.'%')
        ->setParameter('lid', $languageid);
        
        return $query->getResult();
    }
    
    
    public function updateSentence($sentence)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($sentence);
        $entityManager->flush();
    }
    
    public function getSentenceNodeId($sentenceId)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('toc.nodeid')
        ->from('App\Entity\TipitakaSentences','s')
        ->innerJoin('s.paragraphid', 'c')
        ->innerJoin('c.nodeid','toc')
        ->where('s.sentenceid=:sid')
        ->getQuery()
        ->setParameter('sid', $sentenceId);
        
        return $query->getOneOrNullResult();
    }
    
    public function listTranslationsRows($sentencetranslationid,$rows)
    {
        //find node
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('toc.nodeid,so.sourceid,s.sentenceid,c.paragraphid')
        ->from('App\Entity\TipitakaSentenceTranslations','st')
        ->innerJoin('st.sentenceid', 's')
        ->innerJoin('s.paragraphid', 'c')
        ->innerJoin('c.nodeid', 'toc')
        ->innerJoin('st.sourceid', 'so')
        ->where('st.sentencetranslationid=:stid')
        ->getQuery()
        ->setParameter('stid', $sentencetranslationid);
        
        $node=$query->getOneOrNullResult();
                
        //find sentences in this node with this source, sort and select
        
        $query = $entityManager->createQueryBuilder()
        ->select('st.sentencetranslationid as id')
        ->from('App\Entity\TipitakaSentenceTranslations','st')
        ->innerJoin('st.sentenceid', 's')
        ->innerJoin('s.paragraphid', 'c')
        ->innerJoin('c.nodeid', 'toc')
        ->innerJoin('st.sourceid', 'so')
        ->where('toc.nodeid=:nodeid')
        ->andWhere('so.sourceid=:sourceid')
        ->andWhere('s.sentenceid>=:sentenceid')
        ->andWhere('c.paragraphid>=:paragraphid')
        ->orderBy('c.paragraphid')
        ->addOrderBy('s.sentenceid')        
        ->getQuery()
        ->setParameter('nodeid', $node["nodeid"])
        ->setParameter('sourceid', $node["sourceid"])
        ->setParameter('sentenceid', $node["sentenceid"])
        ->setParameter('paragraphid', $node["paragraphid"])
        ->setMaxResults($rows);
        
        return $query->getResult();
    }
    
    public function listSentencesAsTranslations($nodeid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('s.sentenceid,s.sentencetext as translation,c.paragraphid')
        ->from('App\Entity\TipitakaSentences','s')
        ->innerJoin('s.paragraphid', 'c')
        ->innerJoin('c.nodeid','toc')
        ->where('toc.nodeid=:id')
        ->getQuery()
        ->setParameter('id',$nodeid);
        
        return $query->getResult();
    }
    
    public function listSentenceObjByParagraphid($sentenceid,$paragraphid)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQueryBuilder()
        ->select('s')
        ->from('App\Entity\TipitakaSentences','s')
        ->where('s.paragraphid=:cid')
        ->andWhere('s.sentenceid>=:sid')
        ->orderBy('s.sentenceid')
        ->getQuery()
        ->setParameter('cid',$paragraphid)
        ->setParameter('sid',$sentenceid);
        
        return $query->getResult();
    }
    
        

}

