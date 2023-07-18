<?php
namespace App\Repository;

class SqlQueryBuilder
{
    private $select;
    private $from;
    private $join;
    private $where;
    private $groupby;
    private $orderby;
    private $limit;
       
    public function __construct(?string $select = null,?string $from = null,?string $join = null,
        ?string $where = null,?string $groupby = null,?string $orderby = null,?string $limit = null)
    {
        $this->select=$select;
        $this->from=$from;
        $this->join=$join;
        $this->where=$where;
        $this->groupby=$groupby;
        $this->orderby=$orderby;
        $this->limit=$limit;
    }
    
    public static function getQueryBuilder(): SqlQueryBuilder
    {
        return new SqlQueryBuilder();
    }
    
    public function getSql()
    {
        $sql= "SELECT ".$this->select;
        $sql.="\n FROM ".$this->from;
        
        if(!empty($this->join))
        {
            $sql.=$this->join;
        }
        
        if(!empty($this->where))
        {
            $sql.="\n WHERE ".$this->where;
        }
        
        if(!empty($this->groupby))
        {
            $sql.="\n GROUP BY ".$this->groupby;
        }        
        
        if(!empty($this->orderby))
        {
            $sql.="\n ORDER BY ".$this->orderby;
        }
        
        if(!empty($this->limit))
        {
            $sql.="\n LIMIT ".$this->limit;
        }
        
        return $sql;
    }
    
    
    public function select($fields): SqlQueryBuilder
    {
        if(!empty($this->select))
        {
            $this->select.=",";
        }
        
        $this->select.=$fields;
        return $this;
    }
    
    public function selectSubquery(SqlQueryBuilder $subquery,$alias): SqlQueryBuilder
    {
        if(!empty($this->select))
        {
            $this->select.=",";
        }
        
        $this->select.="\n(".$subquery->getSql().") AS $alias";
        return $this;
    }
    
    public function from($tablename): SqlQueryBuilder
    {
        $this->from=$tablename;
        return $this;
    }
    
    public function fromAlias($tablename,$alias): SqlQueryBuilder
    {
        $this->from="$tablename AS $alias";
        return $this;
    }
    
    public function fromSubquery(SqlQueryBuilder $subquery,$alias): SqlQueryBuilder
    {
        $this->from="(".$subquery->getSql().") $alias";
        return $this;
    }
    
    public function innerJoin($tablename,$on): SqlQueryBuilder
    {
        $this->join.="\n INNER JOIN $tablename ON $on";
        
        return $this;
    }
    
    public function leftJoin($tablename,$on): SqlQueryBuilder
    {
        $this->join.="\n LEFT OUTER JOIN $tablename ON $on";
        
        return $this;
    }
    
    public function andWhere($filter): SqlQueryBuilder
    {
        if(!empty($this->where))
        {
            $this->where.=" AND ";
        }
        
        $this->where.=$filter;
        return $this;
    }
    
    public function orWhere($filter): SqlQueryBuilder
    {
        if(!empty($this->where))
        {
            $this->where.=" OR ";
        }
        
        $this->where.=$filter;
        return $this;
    }
    
    public function andWhereSubquery($filter,$subquery): SqlQueryBuilder
    {
        if(!empty($this->where))
        {
            $this->where.=" AND ";
        }
        
        $this->where.=$filter." (".$subquery->getSql().")";
        return $this;
    }
    
    public function groupBy($fields): SqlQueryBuilder
    {
        if(!empty($this->groupby))
        {
            $this->groupby.=", ";
        }
        
        $this->groupby.=$fields;
        
        return $this;
    }
    
    public function orderBy($fields): SqlQueryBuilder
    {
        if(!empty($this->orderby))
        {
            $this->orderby.=", ";
        }
        
        $this->orderby.=$fields;
        
        return $this;
    }
    
    public function limit($limit): SqlQueryBuilder
    {
        $this->limit=$limit;
        return $this;
    }
    
    public function clone()
    {
        $qb=new SqlQueryBuilder($this->select,$this->from,$this->join,
            $this->where,$this->groupby,$this->orderby,$this->limit);
        return $qb;
    }
    
    public function andWhereOrArray(array $orArray)
    {
        if(!empty($this->where))
        {
            $this->where.=" AND ";
        }
        
        if(!empty($orArray))
        {
            $this->where.=" (".implode(" OR ",$orArray).")";
        }
        
        return $this;
    }
}

