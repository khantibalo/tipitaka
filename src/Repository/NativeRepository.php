<?php
namespace App\Repository;

use App\Entity\TipitakaParagraphs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

//this repository is for native queries
//if you want to use another DBMS, you should review them
class NativeRepository extends ServiceEntityRepository
{
    private function getTranslationSelectSubquery(): SqlQueryBuilder
    {
        $qbTranslationSelectSubquery=SqlQueryBuilder::getQueryBuilder()
        ->select("translation")
        ->from("tipitaka_sentence_translations st")
        ->innerJoin("tipitaka_sources so", "st.sourceid=so.sourceid")
        ->andWhere("st.sentenceid=s.sentenceid")
        ->orderBy("so.priority desc")
        ->limit("0,1");
        
        return $qbTranslationSelectSubquery;
    }
    
    private function getTranslationWhereSubquery(): SqlQueryBuilder
    {
        $qbTranslationWhereSubquery=SqlQueryBuilder::getQueryBuilder()
        ->select("st.sentencetranslationid")
        ->from("tipitaka_sentence_translations st")
        ->innerJoin("tipitaka_sources so", "st.sourceid=so.sourceid")
        ->andWhere("st.sentenceid=s.sentenceid");
        
        return $qbTranslationWhereSubquery;
    }
    
    /*     private const translationSearchBaseQuery="SELECT toc.textpath,c.paragraphid, s.sentencetext, ".
     "(SELECT translation FROM tipitaka_sentence_translations st INNER JOIN tipitaka_sources so ON st.sourceid=so.sourceid ".
     "WHERE st.sentenceid=s.sentenceid ORDER BY so.languageid LIMIT 0,1) As translation ".
     "FROM tipitaka_toc toc ".
     "INNER JOIN tipitaka_paragraphs c ON toc.nodeid=c.nodeid  ".
     "INNER JOIN tipitaka_paragraphtypes ct ON c.paragraphtypeid=ct.paragraphtypeid  ".
     "INNER JOIN tipitaka_sentences s ON s.paragraphid=c.paragraphid ".
     "WHERE EXISTS(SELECT st.sentencetranslationid FROM tipitaka_sentence_translations st INNER JOIN tipitaka_sources so ON st.sourceid=so.sourceid ".
     "WHERE st.sentenceid=s.sentenceid) "; */
    
    private function getTranslationSearchBaseQuery(): SqlQueryBuilder
    {               
        $qbFinalQuery=SqlQueryBuilder::getQueryBuilder()
        ->select("toc.textpath,c.paragraphid, s.sentencetext")
        ->selectSubquery($this->getTranslationSelectSubquery(), "translation")
        ->from("tipitaka_toc toc")
        ->innerJoin("tipitaka_paragraphs c ", "toc.nodeid=c.nodeid")
        ->innerJoin("tipitaka_paragraphtypes ct", "c.paragraphtypeid=ct.paragraphtypeid")
        ->innerJoin("tipitaka_sentences s", "s.paragraphid=c.paragraphid")
        ->andWhereSubquery("EXISTS", $this->getTranslationWhereSubquery());
        
        return $qbFinalQuery;
    }
    
    /*     private const globalSearchBaseQuery="SELECT c.nodeid,c.paragraphid, paranum, c.text, c.caps, ct.name As paragraphTypeName,c.hastranslation, ".
     "toc.textpath,s.sentencetext, ".
     "(SELECT translation FROM tipitaka_sentence_translations st INNER JOIN tipitaka_sources so ON st.sourceid=so.sourceid ".
     "WHERE st.sentenceid=s.sentenceid ORDER BY so.languageid LIMIT 0,1) As translation ".
     "FROM tipitaka_toc toc ".
     "INNER JOIN tipitaka_paragraphs c ON toc.nodeid=c.nodeid  ".
     "INNER JOIN tipitaka_paragraphtypes ct ON c.paragraphtypeid=ct.paragraphtypeid  ".
     "LEFT OUTER JOIN tipitaka_sentences s ON s.paragraphid=c.paragraphid "; */
    
    private function getGlobalSearchBaseQuery(): SqlQueryBuilder
    {
        $qbFinalQuery=SqlQueryBuilder::getQueryBuilder()
        ->select("c.nodeid,c.paragraphid, paranum, c.text, c.caps, ct.name As paragraphTypeName,".
         "c.hastranslation,toc.textpath,s.sentencetext")
        ->selectSubquery($this->getTranslationSelectSubquery(), "translation")
        ->from("tipitaka_toc toc")
        ->innerJoin("tipitaka_paragraphs c", "toc.nodeid=c.nodeid")
        ->innerJoin("tipitaka_paragraphtypes ct", "c.paragraphtypeid=ct.paragraphtypeid")
        ->leftJoin("tipitaka_sentences s", "s.paragraphid=c.paragraphid");
        
        return $qbFinalQuery;
    }
        
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipitakaParagraphs::class);
    }
    
    private function getSearchBaseQuery(&$searchString,$inTranslations,$searchMode) : SqlQueryBuilder
    {
        if($inTranslations)
        {
            switch($searchMode)
            {
                case 2:
                    {                        
                        $finalQuery=$this->getTranslationSearchBaseQuery()
                        ->andWhere("s.sentencetext LIKE :ss");
                        
                        $searchString="%$searchString%";
                        break;
                    }
                case 3:
                    {
                        $finalQuery=$this->getTranslationSearchBaseQuery()
                        ->andWhere("s.sentencetext REGEXP CONCAT('\\\\w', :ss, '\\\\b')");
                        
                        $searchString=preg_quote($searchString);
                        break;
                    }
                default:
                    {
                        $finalQuery=SqlQueryBuilder::getQueryBuilder()
                        ->select("toc.textpath,c.paragraphid, ".
                            "MATCH (s.sentencetext) AGAINST (:ss in boolean mode) AS score, s.sentencetext")
                            ->selectSubquery($this->getTranslationSelectSubquery(), "translation")
                            ->from("tipitaka_toc toc")
                            ->innerJoin("tipitaka_paragraphs c", "toc.nodeid=c.nodeid")
                            ->innerJoin("tipitaka_paragraphtypes ct", "c.paragraphtypeid=ct.paragraphtypeid")
                            ->innerJoin("tipitaka_sentences s", "s.paragraphid=c.paragraphid")
                            ->andWhereSubquery("EXISTS", $this->getTranslationWhereSubquery())
                            ->andWhere("MATCH (s.sentencetext) AGAINST (:ss in boolean mode)");
                        
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
                        $finalQuery=$this->getGlobalSearchBaseQuery()
                        ->andWhere("c.text LIKE :ss");
                        
                        $searchString="%$searchString%";
                        break;
                    }
                case 3:
                    {                        
                        $finalQuery=$this->getGlobalSearchBaseQuery()
                        ->andWhere("c.text REGEXP CONCAT('\\\\w', :ss, '\\\\b')");
                        
                        $searchString=preg_quote($searchString);
                        break;
                    }
                default:
                    {                                                
                        $finalQuery=SqlQueryBuilder::getQueryBuilder()
                        ->select("c.nodeid,c.paragraphid, paranum, c.text, c.caps, ct.name As paragraphTypeName,c.hastranslation, ".
                            "MATCH (c.text) AGAINST (:ss in boolean mode) AS score, toc.textpath,s.sentencetext")
                            ->selectSubquery($this->getTranslationSelectSubquery(), "translation")
                            ->from("tipitaka_toc toc")
                            ->innerJoin("tipitaka_paragraphs c", "toc.nodeid=c.nodeid")
                            ->innerJoin("tipitaka_paragraphtypes ct", "c.paragraphtypeid=ct.paragraphtypeid")
                            ->leftJoin("tipitaka_sentences s", "s.paragraphid=c.paragraphid")
                            ->andWhere("MATCH (c.text) AGAINST (:ss in boolean mode)");
                        
                        break;
                    }
            }
            
        }
        
        if($searchMode==1)
        {            
            $finalQuery->orderBy("score DESC");
        }
        
        return $finalQuery;
    }
    
    //this is possible to do with DQL, but the result is very slow query
    public function searchGlobal($searchString,$inTranslations,$searchMode)
    {
        $qbFinalQuery=$this->getSearchBaseQuery($searchString,$inTranslations,$searchMode);                
        
        if($inTranslations)
        {
            $qbFinalQueryWithTranslation=SqlQueryBuilder::getQueryBuilder()
            ->select("*")
            ->fromSubquery($qbFinalQuery,"DT1") //seems like $qbFinalQuery->clone() is not needed gere
            ->andWhere("DT1.translation!=''");
            
            $qbFinalQuery=$qbFinalQueryWithTranslation;
        }
        
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($qbFinalQuery->getSql());
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
        
        $qbFinalQuery=$this->getSearchBaseQuery($searchString,$inTranslations,$searchMode);
        
        $orArray=array();
        
        if(sizeof($paragraph_ids)>0)
        {
            $orArray[]="c.paragraphid IN($paragraph_line)";
        }
        
        if(sizeof($node_ids)>0)
        {            
            $orArray[]=$path_line;
        }
        
        $qbFinalQuery->andWhereOrArray($orArray);
                
        if($inTranslations)
        {            
            $qbFinalQueryWithTranslation=SqlQueryBuilder::getQueryBuilder()
            ->select("*")
            ->fromSubquery($qbFinalQuery,"DT1") //seems like $qbFinalQuery->clone() is not needed here
            ->andWhere("DT1.translation!=''");
            
            $qbFinalQuery=$qbFinalQueryWithTranslation;
        }
        
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($qbFinalQuery->getSql());
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

        $qbFinalQuery=SqlQueryBuilder::getQueryBuilder()
        ->select("st.translation,c.paragraphid,toc.textpath,se.sentencetext")
        ->from("tipitaka_sentence_translations st")
        ->innerJoin("tipitaka_sources so", "st.sourceid=so.sourceid")
        ->innerJoin("tipitaka_languages l", "so.languageid=l.languageid")
        ->innerJoin("tipitaka_sentences se", "st.sentenceid=se.sentenceid")
        ->innerJoin("tipitaka_paragraphs c", "c.paragraphid=se.paragraphid")
        ->innerJoin("tipitaka_toc toc", "toc.nodeid=c.nodeid")
        ->andWhere("st.translation LIKE :ss")
        ->andWhere("l.languageid=:lid");
                
        $orArray=array();

        if(sizeof($paragraph_ids)>0)
        {            
            $orArray[]="c.paragraphid IN($paragraph_line)";
        }
        
        if(sizeof($node_ids)>0)
        {
            $orArray[]=$path_line;
        }
        
        $qbFinalQuery->andWhereOrArray($orArray);
                
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($qbFinalQuery->getSql());
        $result=$stmt->executeQuery(['ss'=>'%'.$searchString.'%','lid'=>$languageid]);
        
        return $result->fetchAllAssociative();
    }
    
    
    public function listByLastUpdTranslation($maxResults,$locale)
    { 
        $qb400LastTranslations=SqlQueryBuilder::getQueryBuilder()
        ->select("ST1.sentenceid,ST1.dateupdated")
        ->from("tipitaka_sentence_translations ST1")
        ->orderBy("dateupdated DESC")
        ->limit("0,400");
        
        $qbLastTranslationsWithDetails=SqlQueryBuilder::getQueryBuilder()
        ->select("T.nodeid,T1.textpath,T.title,C.paragraphid,ST.dateupdated,T.hastableview,T.translationsourceid")
        ->fromSubquery($qb400LastTranslations, "ST")
        ->innerJoin("tipitaka_sentences S", "ST.sentenceid=S.sentenceid")
        ->innerJoin("tipitaka_paragraphs C", "S.paragraphid=C.paragraphid")
        ->innerJoin("tipitaka_toc T", "C.nodeid=T.nodeid")
        ->innerJoin("tipitaka_toc T1", "T.parentid=T1.nodeid");
        
        $qbNodeNames=SqlQueryBuilder::getQueryBuilder()
        ->select("nn1.name")
        ->from("tipitaka_node_names nn1")
        ->innerJoin("tipitaka_languages l", "nn1.languageid=l.languageid")
        ->andWhere("l.code=:locale")
        ->andWhere("DT.nodeid=nn1.nodeid");
                
        $qbLastTranslations=SqlQueryBuilder::getQueryBuilder()
        ->select("DT.textpath,DT.title,MAX(DT.dateupdated) As updated,DT.nodeid,".
            "DT.hastableview as HasTableView,DT.translationsourceid as TranslationSourceID")
        ->selectSubquery($qbNodeNames, "trname")
        ->fromSubquery($qbLastTranslationsWithDetails, "DT")
        ->groupBy("DT.textpath,DT.title,DT.nodeid,DT.hastableview,DT.translationsourceid")
        ->orderBy("MAX(DT.dateupdated) DESC")
        ->limit("0,$maxResults");        
        
        //$stmt = $conn->prepare($sql);
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($qbLastTranslations->getSql());
        $result=$stmt->executeQuery(['locale'=>$locale]);
        
        return $result->fetchAllAssociative();
    }
        
    public function listLastUpdTranslationFeed($maxResults)
    {        
        $qb400LastTranslations=SqlQueryBuilder::getQueryBuilder()
        ->select("ST1.sentenceid,ST1.dateupdated,ST1.userid")
        ->from("tipitaka_sentence_translations ST1")
        ->orderBy("dateupdated DESC")
        ->limit("0,400");
        
        $qbLastTranslationsWithDetails=SqlQueryBuilder::getQueryBuilder()
        ->select("T.nodeid,T1.textpath,T.title,C.paragraphid,ST.dateupdated,U.username")
        ->fromSubquery($qb400LastTranslations, "ST")
        ->innerJoin("tipitaka_sentences S", "ST.sentenceid=S.sentenceid")
        ->innerJoin("tipitaka_paragraphs C", "S.paragraphid=C.paragraphid")
        ->innerJoin("tipitaka_toc T", "C.nodeid=T.nodeid")
        ->innerJoin("tipitaka_toc T1", "T.parentid=T1.nodeid")
        ->innerJoin("tipitaka_users U", "ST.userid=U.userid");
        
        $qbLastTranslationsFeed=SqlQueryBuilder::getQueryBuilder()
        ->select("DT.NodeID as nodeid, DT.textpath As description,DT.title,DT.paragraphid, ".
            "MIN(DT.dateupdated) As pubDate,DT.username As creator")
        ->fromSubquery($qbLastTranslationsWithDetails, "DT")
        ->groupBy("DT.nodeid,DT.textpath,DT.title,DT.paragraphid,DT.username")
        ->orderBy("MAX(DT.dateupdated) DESC")
        ->limit("0,$maxResults");
        
        //$stmt = $conn->prepare($sql);
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($qbLastTranslationsFeed->getSql());
        $result=$stmt->executeQuery();
        
        return $result->fetchAllAssociative();
    }
    
    
    public function searchTermNames($keyword,$dictionaryTypeID,$searchType,$ignoreDiac)
    {
        //how to do this with DQL?
        //https://www.philipphoffmann.de/post/a-bulletproof-pattern-for-creating-doctrine-subqueries-of-any-complexity/
        
        $qbSearchSubquery=SqlQueryBuilder::getQueryBuilder()
        ->select("paliword As UniquePaliword")
        ->from("tipitaka_dictionaryentries");
        
        if(empty($ignoreDiac))
        {
            $qbSearchSubquery->andWhere("paliword LIKE :keyword");
        }
        else
        {
            $qbSearchSubquery->andWhere("paliwordnodiac LIKE :keyword");
        }
        
        if(!empty($dictionaryTypeID))
        {
            $qbSearchSubquery->andWhere("DictionaryTypeID=:dtid");
        }
        
        $qbSearchSubquery->groupBy("paliword");
        
        $qbBuddhadatta=SqlQueryBuilder::getQueryBuilder()
        ->select("d2.explanation_plain")
        ->from("tipitaka_dictionaryentries d2")
        ->andWhere("d2.paliword=T1.uniquepaliword COLLATE 'utf8_bin'")
        ->andWhere("d2.dictionarytypeid=2");
        
        $qbFinal=SqlQueryBuilder::getQueryBuilder()
        ->select("T1.UniquePaliword")
        ->selectSubquery($qbBuddhadatta,"Buddhadatta")
        ->fromSubquery($qbSearchSubquery,"T1");
        
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($qbFinal->getSql());
        
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
                
        $qbTranslationSubquery=SqlQueryBuilder::getQueryBuilder()
        ->select("translation")
        ->from("tipitaka_sentence_translations st")
        ->innerJoin("tipitaka_sources so", "st.sourceid=so.sourceid")
        ->andWhere("st.sentenceid=s.sentenceid")
        ->orderBy("so.languageid")
        ->limit("0,1");
        
        $qbSentenceidSubquery=SqlQueryBuilder::getQueryBuilder()
        ->select("sentenceid")
        ->from("tipitaka_sentence_translations");
        
        $qbAnalyze=SqlQueryBuilder::getQueryBuilder()
        ->select("s.paragraphid,s.sentencetext")
        ->selectSubquery($qbTranslationSubquery, "translation")
        ->from("tipitaka_sentences s")
        ->andWhereSubquery("s.sentenceid IN", $qbSentenceidSubquery)
        ->andWhere("MATCH (s.sentencetext) AGAINST (:st IN NATURAL LANGUAGE MODE)")
        ->limit("0,$maxResults");
        
        $stmt = $conn->prepare($qbAnalyze->getSql());
        $result=$stmt->executeQuery(['st'=>$sentenceText]);
        
        return $result->fetchAllAssociative();
    }
    
    public function listSentencesForQuote($nodeid,$sentenceid,$length)
    {
        $qbNodeSentences=SqlQueryBuilder::getQueryBuilder()
        ->select("s.sentenceid,s.sentencetext,s.commentcount,s.lastcomment,p.paragraphid")
        ->from("tipitaka_sentences s")
        ->innerJoin("tipitaka_paragraphs p", "s.paragraphid=p.paragraphid")
        ->andWhere("p.nodeid=:nodeid")
        ->orderBy("p.paragraphid,s.sentenceid");
        
        $qbQuoteSentences=SqlQueryBuilder::getQueryBuilder()
        ->select("DT.sentenceid,DT.sentencetext,DT.commentcount,DT.lastcomment")
        ->fromSubquery($qbNodeSentences, "DT")
        ->andWhere("DT.sentenceid>=:sentenceid")
        ->orderBy("DT.paragraphid,DT.sentenceid")
        ->limit("0,$length");        
                
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($qbQuoteSentences->getSql());
        $result=$stmt->executeQuery(['nodeid'=>$nodeid,'sentenceid'=>$sentenceid]);
        
        return $result->fetchAllAssociative();
    }
}

