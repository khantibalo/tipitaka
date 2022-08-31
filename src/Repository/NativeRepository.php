<?php
namespace App\Repository;

use App\Entity\TipitakaParagraphs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

//this repository is for native queries
//if you want to use another DBMS, you should review them
class NativeRepository extends ServiceEntityRepository
{
    private const translationSearchBaseQuery="SELECT toc.textpath,c.paragraphid, s.sentencetext, ".
        "(SELECT translation FROM tipitaka_sentence_translations st INNER JOIN tipitaka_sources so ON st.sourceid=so.sourceid ".
        "WHERE st.sentenceid=s.sentenceid ORDER BY so.languageid LIMIT 0,1) As translation ".
        "FROM tipitaka_toc toc INNER JOIN tipitaka_paragraphs c ON toc.nodeid=c.nodeid  ".
        "INNER JOIN tipitaka_paragraphtypes ct ON c.paragraphtypeid=ct.paragraphtypeid  ".
        "INNER JOIN tipitaka_sentences s ON s.paragraphid=c.paragraphid ".
        "WHERE EXISTS(SELECT st.sentencetranslationid FROM tipitaka_sentence_translations st INNER JOIN tipitaka_sources so ON st.sourceid=so.sourceid ".
        "WHERE st.sentenceid=s.sentenceid) ";
    
    private const globalSearchBaseQuery="SELECT c.nodeid,c.paragraphid, paranum, c.text, c.caps, ct.name As paragraphTypeName,c.hastranslation, ".
        "toc.textpath,s.sentencetext, ".
        "(SELECT translation FROM tipitaka_sentence_translations st INNER JOIN tipitaka_sources so ON st.sourceid=so.sourceid ".
        "WHERE st.sentenceid=s.sentenceid ORDER BY so.languageid LIMIT 0,1) As translation ".
        "FROM tipitaka_toc toc INNER JOIN tipitaka_paragraphs c ON toc.nodeid=c.nodeid  ".
        "INNER JOIN tipitaka_paragraphtypes ct ON c.paragraphtypeid=ct.paragraphtypeid  ".
        "LEFT OUTER JOIN tipitaka_sentences s ON s.paragraphid=c.paragraphid ";
    
    private const translationSearchBaseQueryFullText="SELECT toc.textpath,c.paragraphid, ".
        "MATCH (s.sentencetext) AGAINST (:ss in boolean mode) AS score, s.sentencetext, ".
        "(SELECT translation FROM tipitaka_sentence_translations st INNER JOIN tipitaka_sources so ON st.sourceid=so.sourceid ".
        "WHERE st.sentenceid=s.sentenceid ORDER BY so.languageid LIMIT 0,1) As translation ".
        "FROM tipitaka_toc toc INNER JOIN tipitaka_paragraphs c ON toc.nodeid=c.nodeid  ".
        "INNER JOIN tipitaka_paragraphtypes ct ON c.paragraphtypeid=ct.paragraphtypeid  ".
        "INNER JOIN tipitaka_sentences s ON s.paragraphid=c.paragraphid ".
        "WHERE EXISTS(SELECT st.sentencetranslationid FROM tipitaka_sentence_translations st INNER JOIN tipitaka_sources so ON st.sourceid=so.sourceid ".
                    "WHERE st.sentenceid=s.sentenceid) and MATCH (s.sentencetext) AGAINST (:ss in boolean mode) ";
    
    private const globalSearchBaseQueryFullText="SELECT c.nodeid,c.paragraphid, paranum, c.text, c.caps, ct.name As paragraphTypeName,c.hastranslation, ".
        "MATCH (c.text) AGAINST (:ss in boolean mode) AS score, toc.textpath,s.sentencetext, ".
        "(SELECT translation FROM tipitaka_sentence_translations st INNER JOIN tipitaka_sources so ON st.sourceid=so.sourceid ".
        "WHERE st.sentenceid=s.sentenceid ORDER BY so.languageid LIMIT 0,1) As translation ".
        "FROM tipitaka_toc toc INNER JOIN tipitaka_paragraphs c ON toc.nodeid=c.nodeid  ".
        "INNER JOIN tipitaka_paragraphtypes ct ON c.paragraphtypeid=ct.paragraphtypeid  ".
        "LEFT OUTER JOIN tipitaka_sentences s ON s.paragraphid=c.paragraphid ".
        "WHERE MATCH (c.text) AGAINST (:ss in boolean mode) ";
    
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipitakaParagraphs::class);
    }
    
    //this is possible to do with DQL, but the result is very slow query
    public function searchGlobal($searchString,$inTranslations,$searchMode)
    {
        $conn = $this->getEntityManager()->getConnection();

        if($inTranslations)
        {            
            switch($searchMode)
            {
                case 2:
                {
                    $sql=NativeRepository::translationSearchBaseQuery." and s.sentencetext LIKE :ss"; 
                    
                    $searchString="%$searchString%";
                    break;
                }
                case 3:
                {
                    $sql=NativeRepository::translationSearchBaseQuery." and s.sentencetext REGEXP CONCAT('\\\\w', :ss, '\\\\b')";
                    
                    $searchString=preg_quote($searchString);
                    break;
                }
                default:
                {
                    $sql=NativeRepository::translationSearchBaseQueryFullText;
                    break;
                }
            }                                    
        }
        else
        {            
            switch($searchMode)
            {
                case 2:
                {
                    $sql=NativeRepository::globalSearchBaseQuery." WHERE c.text LIKE :ss";
                    
                    $searchString="%$searchString%";
                    break;
                }
                case 3:
                {
                    $sql=NativeRepository::globalSearchBaseQuery." WHERE c.text REGEXP CONCAT('\\\\w', :ss, '\\\\b')";
                    
                    $searchString=preg_quote($searchString);
                    break;
                }
                default:
                {
                    $sql=NativeRepository::globalSearchBaseQueryFullText;
                    break;
                }
            }
                    
            //$sql.=" AND c.hastranslation=1 ";
        }
        
        if($searchMode==1)
        {
            $sql.=" ORDER BY score desc";
        }
        
        if($inTranslations)
        {
            $sql="SELECT * FROM (".$sql.") DT1 WHERE DT1.translation!=''";
        }
        
        $stmt = $conn->prepare($sql);
        $result=$stmt->executeQuery(['ss'=>mb_convert_case($searchString,MB_CASE_LOWER)]);
        
        return $result->fetchAllAssociative();
    }
    
        
    public function searchBookmarks($searchString,$str_bookmarks,$inTranslations,$searchMode=1)
    {
        //convert bookmarks into arrays
        $node_ids=array();
        $paragraph_ids=array();
        
        $bookmarks=explode(";", $str_bookmarks);
        
        foreach($bookmarks as $bookmark_item)
        {
            $bookmark=explode(":",$bookmark_item);
            
            if($bookmark[0]=="N" && is_numeric($bookmark[1]))
            {
                $node_ids[]=$bookmark[1];
            }
            
            if($bookmark[0]=="P" && is_numeric($bookmark[1]))
            {
                $paragraph_ids[]=$bookmark[1];
            }
        }
        //prepare final query
        $entityManager = $this->getEntityManager();
        
        //search in nodes
        //find node paths
        $queryNodes = $entityManager->createQueryBuilder()
        ->select('toc')
        ->from('App\Entity\TipitakaToc','toc')
        ->where('toc.nodeid IN(:id)')
        ->getQuery()
        ->setParameter(':id', $node_ids);
        
        $ar_paths=$queryNodes->getResult();
        
        $ar_paths_only=array();
        foreach($ar_paths as $ar_paths_item)
        {
            $ar_paths_only[]=$ar_paths_item->getPath();
        }
        
        $path_line="Path LIKE '".implode("%' OR Path LIKE '",$ar_paths_only)."%'";
        $path_line=str_replace("\\","\\\\\\\\",$path_line);
        
        $paragraph_line=implode(",",$paragraph_ids);
        
        if($inTranslations)
        {           
            switch($searchMode)
            {
                case 2:
                    {
                        $sql=NativeRepository::translationSearchBaseQuery." and s.sentencetext LIKE :ss ";
                        
                        $searchString="%$searchString%";
                        break;
                    }
                case 3:
                    {
                        $sql=NativeRepository::translationSearchBaseQuery." and s.sentencetext REGEXP CONCAT('\\\\w', :ss, '\\\\b') ";
                        
                        $searchString=preg_quote($searchString);
                        break;
                    }
                default:
                    {
                        $sql=NativeRepository::translationSearchBaseQueryFullText;
                        break;
                    }
            }            
        }
        else
        {           
            switch($searchMode)
            {
                case 2:
                    {
                        $sql=NativeRepository::globalSearchBaseQuery." WHERE c.text LIKE :ss ";  
                        
                        $searchString="%$searchString%";
                        break;
                    }
                case 3:
                    {
                        $sql=NativeRepository::globalSearchBaseQuery." WHERE c.text REGEXP CONCAT('\\\\w', :ss, '\\\\b') ";  
                        
                        $searchString=preg_quote($searchString);
                        break;
                    }
                default:
                    {
                        $sql=NativeRepository::globalSearchBaseQueryFullText;  
                        break;
                    }
            }
      
        }
        
        $sql.=" AND (";
        $query="";
        if(sizeof($paragraph_ids)>0)
        {
            $query=" c.paragraphid IN($paragraph_line)";
        }
        
        if(sizeof($node_ids)>0)
        {
            if(!empty($query))
            {
                $query.=" OR ";
            }
                
            $query.=$path_line;
        }
        
        if($searchMode==1)
        {
            $sql.=$query.") ORDER BY score desc";
        }
        else 
        {
            $sql.=$query.") ";
        }
        
        if($inTranslations)
        {
            $sql="SELECT * FROM (".$sql.") DT1 WHERE DT1.translation!=''";
        }
        
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $result=$stmt->executeQuery(['ss'=>mb_convert_case($searchString,MB_CASE_LOWER)]);
        
        return $result->fetchAllAssociative();
    }    
    
    
    public function searchBookmarksTranslations($searchString,$str_bookmarks,$languageid)
    {
        //convert bookmarks into arrays
        $node_ids=array();
        $paragraph_ids=array();
        
        $bookmarks=explode(";", $str_bookmarks);
        
        foreach($bookmarks as $bookmark_item)
        {
            $bookmark=explode(":",$bookmark_item);
            
            if($bookmark[0]=="N" && is_numeric($bookmark[1]))
            {
                $node_ids[]=$bookmark[1];
            }
            
            if($bookmark[0]=="P" && is_numeric($bookmark[1]))
            {
                $paragraph_ids[]=$bookmark[1];
            }
        }
        //prepare final query
        $entityManager = $this->getEntityManager();
        
        //search in nodes
        //find node paths
        $queryNodes = $entityManager->createQueryBuilder()
        ->select('toc')
        ->from('App\Entity\TipitakaToc','toc')
        ->where('toc.nodeid IN(:id)')
        ->getQuery()
        ->setParameter(':id', $node_ids);
        
        $ar_paths=$queryNodes->getResult();
        
        $ar_paths_only=array();
        foreach($ar_paths as $ar_paths_item)
        {
            $ar_paths_only[]=$ar_paths_item->getPath();
        }
        
        $path_line="Path LIKE '".implode("%' OR Path LIKE '",$ar_paths_only)."%'";
        $path_line=str_replace("\\","\\\\\\\\",$path_line);
        
        $paragraph_line=implode(",",$paragraph_ids);
                
        $sql="SELECT st.translation,c.paragraphid,toc.textpath,se.sentencetext ".
            "FROM tipitaka_sentence_translations st ".
            "INNER JOIN tipitaka_sources so ON st.sourceid=so.sourceid ".
            "INNER JOIN tipitaka_languages l ON so.languageid=l.languageid ".
            "INNER JOIN tipitaka_sentences se ON st.sentenceid=se.sentenceid ".
            "INNER JOIN tipitaka_paragraphs c ON c.paragraphid=se.paragraphid ".
            "INNER JOIN tipitaka_toc toc ON toc.nodeid=c.nodeid ".
            "WHERE st.translation LIKE :ss AND l.languageid=:lid";
        
        $sql.=" AND (";
        $query="";
        if(sizeof($paragraph_ids)>0)
        {
            $query=" c.paragraphid IN($paragraph_line)";
        }
        
        if(sizeof($node_ids)>0)
        {
            if(!empty($query))
                $query.=" OR ";
                
                $query.=$path_line;
        }
        
        $sql.=$query.")";
        
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $result=$stmt->executeQuery(['ss'=>'%'.$searchString.'%','lid'=>$languageid]);
        
        return $result->fetchAllAssociative();
    }
    
    
    public function listByLastUpdTranslation($maxResults,$locale)
    {            
        $conn = $this->getEntityManager()->getConnection();
        
        //list paragraphs
        /*
        $sql="SELECT DT.textpath,DT.title,DT.paragraphid, MAX(DT.dateupdated) As updated,".
            "(SELECT nn1.name FROM tipitaka_node_names nn1 INNER JOIN tipitaka_languages l ON nn1.languageid=l.languageid WHERE l.code=:locale AND DT.nodeid=nn1.nodeid) AS trname ".
            "FROM ( SELECT T.nodeid,T1.textpath,T.title,C.paragraphid,ST.dateupdated ".
            "FROM tipitaka_sentence_translations ST INNER JOIN tipitaka_sentences S ON ST.sentenceid=S.sentenceid ".
            "INNER JOIN tipitaka_paragraphs C ON S.paragraphid=C.paragraphid ".
            "INNER JOIN tipitaka_toc T ON C.nodeid=T.nodeid ".
            "INNER JOIN tipitaka_toc T1 ON T.parentid=T1.nodeid ".
            "ORDER BY dateupdated DESC LIMIT 0,200) DT ".
            "GROUP BY DT.textpath,DT.title,DT.paragraphid ".
            "ORDER BY MAX(DT.dateupdated) DESC ".
            "LIMIT 0,20 ";
        */
        
        $sql="SELECT DT.textpath,DT.title,MAX(DT.dateupdated) As updated,DT.nodeid,DT.hastableview as HasTableView,DT.translationsourceid as TranslationSourceID,".
            "(SELECT nn1.name FROM tipitaka_node_names nn1 INNER JOIN tipitaka_languages l ON nn1.languageid=l.languageid WHERE l.code=:locale AND DT.nodeid=nn1.nodeid) AS trname ".
            "FROM ( SELECT T.nodeid,T1.textpath,T.title,C.paragraphid,ST.dateupdated,T.hastableview,T.translationsourceid ".
            "FROM (SELECT ST1.sentenceid,ST1.dateupdated FROM tipitaka_sentence_translations ST1 ORDER BY dateupdated DESC LIMIT 0,400) ST INNER JOIN tipitaka_sentences S ON ST.sentenceid=S.sentenceid ".
            "INNER JOIN tipitaka_paragraphs C ON S.paragraphid=C.paragraphid ".
            "INNER JOIN tipitaka_toc T ON C.nodeid=T.nodeid ".
            "INNER JOIN tipitaka_toc T1 ON T.parentid=T1.nodeid) DT ".
            "GROUP BY DT.textpath,DT.title,DT.nodeid,DT.hastableview,DT.translationsourceid ".
            "ORDER BY MAX(DT.dateupdated) DESC ".
            "LIMIT 0,$maxResults ";
        
        $stmt = $conn->prepare($sql);
        $result=$stmt->executeQuery(['locale'=>$locale]);
        
        return $result->fetchAllAssociative();
    }
        
    public function listLastUpdTranslationFeed($maxResults)
    {
        
        $conn = $this->getEntityManager()->getConnection();
        
        $sql="SELECT DT.NodeID as nodeid, DT.textpath As description,DT.title,DT.paragraphid, MIN(DT.dateupdated) As pubDate,DT.username As creator ".
            "FROM ( SELECT T.nodeid,T1.textpath,T.title,C.paragraphid,ST.dateupdated,U.username ".
            "FROM (SELECT ST1.sentenceid,ST1.dateupdated,ST1.userid FROM tipitaka_sentence_translations ST1 ORDER BY dateupdated DESC LIMIT 0,400) ST INNER JOIN tipitaka_sentences S ON ST.sentenceid=S.sentenceid ".
            "INNER JOIN tipitaka_paragraphs C ON S.paragraphid=C.paragraphid ".
            "INNER JOIN tipitaka_toc T ON C.nodeid=T.nodeid ".
            "INNER JOIN tipitaka_toc T1 ON T.parentid=T1.nodeid ".
            "INNER JOIN tipitaka_users U ON ST.userid=U.userid) DT ".
            "GROUP BY DT.nodeid,DT.textpath,DT.title,DT.paragraphid,DT.username ".
            "ORDER BY MAX(DT.dateupdated) DESC ".
            "LIMIT 0,$maxResults ";
        
        $stmt = $conn->prepare($sql);
        $result=$stmt->executeQuery();
        
        return $result->fetchAllAssociative();
    }
    
    
    public function searchTermNames($keyword,$dictionaryTypeID,$searchType,$ignoreDiac)
    {
        //how to do this with DQL?
        //https://www.philipphoffmann.de/post/a-bulletproof-pattern-for-creating-doctrine-subqueries-of-any-complexity/
        /*
         $entityManager = $this->getEntityManager();
         $qb = $entityManager->createQueryBuilder();
         
         $q1=$qb->select('de.paliword')
         ->from('App\Entity\TipitakaDictionaryentries','de');
         
         if(!empty($ignoreDiac))
         {
         $q1=$q1->where('de.paliwordnodiac LIKE :keyword');
         }
         else
         {
         $q1=$q1->where('de.paliword LIKE :keyword');
         }
         
         if(!empty($dictionaryTypeID))
         {
         $q1=$q1->andWhere('de.dictionarytypeid=:dtid');
         }
         
         $q1=$q1->groupBy('de.paliword');
         
         $q2=$qb->select('d2.explanation_plain','d2.paliword')
         ->from('App\Entity\TipitakaDictionaryentries','d2')
         
         $q3=$qb->select('T1.paliword')->from($q1->getDQL(),'T1');
         //$q3=$q1;
         
         */
        
        
        
        $conn = $this->getEntityManager()->getConnection();
        $sql="SELECT paliword As UniquePaliword ".
            "FROM tipitaka_dictionaryentries ";
        
        if(!empty($ignoreDiac))
        {
            $sql.=" WHERE paliwordnodiac LIKE :keyword";
        }
        else
        {
            $sql.=" WHERE paliword LIKE :keyword";
        }
        
        if(!empty($dictionaryTypeID))
        {
            $sql.=" AND DictionaryTypeID=:dtid";
        }
        
        $sql.=" GROUP BY paliword ";
        
        
        $sql="SELECT T1.UniquePaliword, ".
            "(SELECT d2.explanation_plain FROM tipitaka_dictionaryentries d2 WHERE d2.paliword=T1.uniquepaliword COLLATE 'utf8_bin' AND d2.dictionarytypeid=2) As Buddhadatta ".
            "FROM ($sql) T1";
        
        $stmt = $conn->prepare($sql);
        
        if(!empty($ignoreDiac))
        {
            $keyword=TipitakaDictionaryRepository::convertNoDiac($keyword);
        }
        else
        {
            $keyword=mb_convert_case($keyword,MB_CASE_LOWER);
        }
        
        $keyword.="%";
        
        if($searchType=="c")
        {
            $keyword="%".$keyword;
        }
        
        $params=array('keyword'=>$keyword);
        
        if(!empty($dictionaryTypeID))
        {
            $params['dtid']=$dictionaryTypeID;
        }
        
        $result=$stmt->executeQuery($params);
        
        $rows=$result->fetchAllAssociative();
        
        if(sizeof($rows)==0 && $searchType=='a')
        {
            while(sizeof($rows)==0 && mb_strlen($keyword)>2)
            {
                $keyword=mb_substr($keyword, 0,mb_strlen($keyword)-2)."%";
                
                $params['keyword']=$keyword;
                $result=$stmt->executeQuery($params);
                
                $rows=$result->fetchAllAssociative();
            }
        }
        
        return $rows;
    }
       
    public function analyzeSentence($sentenceText,$maxResults)
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql="SELECT s.paragraphid,s.sentencetext, ". 
             "(SELECT translation FROM tipitaka_sentence_translations st INNER JOIN tipitaka_sources so ON st.sourceid=so.sourceid ". 
             "WHERE st.sentenceid=s.sentenceid ORDER BY so.languageid LIMIT 0,1) As translation ".
             "FROM `tipitaka_sentences` s WHERE s.sentenceid IN (SELECT sentenceid FROM tipitaka_sentence_translations) AND ".
             "MATCH (s.sentencetext) AGAINST (:st IN NATURAL LANGUAGE MODE) ".
             "LIMIT 0,$maxResults ";
        
        $stmt = $conn->prepare($sql);
        $result=$stmt->executeQuery(['st'=>$sentenceText]);
        
        return $result->fetchAllAssociative();
    }
    
    public function listSentencesForQuote($nodeid,$sentenceid,$length)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql="SELECT DT.sentenceid,DT.sentencetext,DT.commentcount,DT.lastcomment ".
        "FROM (SELECT s.sentenceid,s.sentencetext,s.commentcount,s.lastcomment,p.paragraphid ".
            "FROM tipitaka_sentences s INNER JOIN tipitaka_paragraphs p on s.paragraphid=p.paragraphid ".
            "WHERE p.nodeid=:nodeid ORDER BY p.paragraphid,s.sentenceid) DT ".
        "WHERE DT.sentenceid>=:sentenceid ".
        "ORDER BY DT.paragraphid,DT.sentenceid ".
        "LIMIT 0,$length ";
                
        $stmt = $conn->prepare($sql);
        $result=$stmt->executeQuery(['nodeid'=>$nodeid,'sentenceid'=>$sentenceid]);
        
        return $result->fetchAllAssociative();
    }
}

