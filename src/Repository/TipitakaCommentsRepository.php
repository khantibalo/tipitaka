<?php
namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use App\Entity\TipitakaComments;

class TipitakaCommentsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipitakaComments::class);
    }
    
    public function listBySentenceId($sentenceid)
    {
        $entityManager = $this->getEntityManager();
        $query=$entityManager->createQueryBuilder()
        ->select('c.commentid As CommentID,c.createddate As CreatedDate,u.username as AuthorName,c.commenttext As CommentText,u.userid As AuthorID,u.allowcommentshtml,c.authorname as UnregName')
        ->from('App\Entity\TipitakaComments','c')        
        ->join('c.sentenceid','s')
        ->leftJoin('c.authorid','u',Join::WITH,'c.authorid=u.userid')
        ->where('s.sentenceid=:sid')          
        ->getQuery()
        ->setParameter('sid', $sentenceid);
        
        return $query->getResult();  
    }
    
    public function add(TipitakaComments $comment)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($comment);
        
        $sentence=$comment->getSentenceid();
        $sentence->setCommentcount($sentence->getCommentCount()+1);
        $sentence->setLastcomment($this->shorten($comment->getCommenttext()));
        $entityManager->persist($sentence);
        
        $entityManager->flush();
    }
    
    public function delete($comment)
    {
        $entityManager = $this->getEntityManager();
        
        $sentence=$comment->getSentenceid();
        $sentence->setCommentcount($sentence->getCommentCount()-1);
        $entityManager->persist($sentence);
        
        $entityManager->remove($comment);
        
        $entityManager->flush();
        
        $query=$entityManager->createQueryBuilder()
        ->select('c.commenttext')
        ->from('App\Entity\TipitakaComments','c')
        ->join('c.sentenceid','s')
        ->where('s.sentenceid=:sid')
        ->orderBy('c.createddate','desc')
        ->setMaxResults(1)
        ->getQuery()
        ->setParameter('sid', $sentence->getSentenceid());
        
        $result=$query->getOneOrNullResult();
        if($result)
        {
            $sentence->setLastcomment($this->shorten($result['commenttext']));
        }
        else
        {
            $sentence->setLastcomment('');
        }
        
        $entityManager->persist($sentence);
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
    
    public function listLatest($maxResults,$locale)
    {
        $entityManager = $this->getEntityManager();
        $query=$entityManager->createQueryBuilder()
        ->select('DATE_DIFF(CURRENT_DATE(),c.createddate) As DaysAgo,c.commenttext As CommentText,s.sentenceid,c.commentid,toc.title')
        ->addSelect('('.$this->getNamesSubquery()->getDQL().') AS trname')
        ->from('App\Entity\TipitakaComments','c')
        ->join('c.sentenceid','s')
        ->join('s.paragraphid','cn')
        ->join('cn.nodeid','toc')
        ->orderBy('c.createddate','desc')
        ->setMaxResults($maxResults)
        ->getQuery()
        ->setParameter('locale', $locale);
                
        return $query->getResult();        
    }
    
    public function listLatestFeed($maxResults)
    {
        $entityManager = $this->getEntityManager();
        $query=$entityManager->createQueryBuilder()
        ->select('c.createddate As pubDate,c.commenttext As description,s.sentenceid,c.commentid,toc.title,a.username as creator')
        ->from('App\Entity\TipitakaComments','c')
        ->join('c.sentenceid','s')
        ->join('s.paragraphid','cn')
        ->join('cn.nodeid','toc')
        ->join('c.authorid','a')
        ->orderBy('c.createddate','desc')
        ->setMaxResults($maxResults)
        ->getQuery();
        
        return $query->getResult();
    }
    
    public function listUserLatest($maxResults,$userid)
    {
        $entityManager = $this->getEntityManager();
        $query=$entityManager->createQueryBuilder()
        ->select('DATE_DIFF(CURRENT_DATE(),c.createddate) as DateDiff,c.commenttext As CommentText,s.sentenceid,c.commentid,toc.title')
        ->from('App\Entity\TipitakaComments','c')
        ->join('c.sentenceid','s')
        ->join('s.paragraphid','cn')
        ->join('cn.nodeid','toc')
        ->orderBy('c.createddate','desc')
        ->where('c.authorid=:uid')
        ->setMaxResults($maxResults)        
        ->getQuery()
        ->setParameter('uid', $userid);        
        
        return $query->getResult();
    }
    
    private function Shorten($text) {
        $short = $text;
        
        if (mb_strlen ( $short ) > 0) {
            $short = str_replace ( '<br/>', ' ', $text );
            $short = str_replace ( '<br>', ' ', $short );
            
            $short=$this->strip_tags_content($short);
            
            if (mb_strlen ( $short ) > 150)
                $short = mb_substr ( $short, 0, 150 ) . '...';
        }
        
        return $short;
    }
    
    private function strip_tags_content($text, $tags = '', $invert = FALSE) {
        
        preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
        $tags = array_unique($tags[1]);
        
        if(is_array($tags) AND count($tags) > 0) {
            if($invert == FALSE) {
                return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
            }
            else {
                return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text);
            }
        }
        elseif($invert == FALSE) {
            return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
        }
        return $text;
    } 
    
    public function listAll($pageid,$pageSize,$locale)
    {
        $firstResult=$pageid*$pageSize;
        
        $entityManager = $this->getEntityManager();
        $query=$entityManager->createQueryBuilder()
        ->select('c.commentid As CommentID,c.createddate As CreatedDate,u.username as AuthorName,c.commenttext As CommentText,u.userid As AuthorID,u.allowcommentshtml,c.authorname as UnregName,s.sentenceid,toc.title')
        ->addSelect('('.$this->getNamesSubquery()->getDQL().') AS trname')
        ->from('App\Entity\TipitakaComments','c')
        ->join('c.sentenceid','s')
        ->leftJoin('c.authorid','u',Join::WITH,'c.authorid=u.userid')
        ->join('s.paragraphid','cn')
        ->join('cn.nodeid','toc')
        ->orderBy('c.createddate','desc')
        ->setFirstResult($firstResult)
        ->setMaxResults($pageSize) 
        ->getQuery()
        ->setParameter('locale', $locale);
        
        return $query->getResult();
    }
}

