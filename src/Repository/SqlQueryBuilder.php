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
    
    
    public function select($fields)
    {
        if(!empty($this->select))
        {
            $this->select.=",";
        }
        
        $this->select.=$fields;
        return $this;
    }
    
    public function selectSubquery(SqlQueryBuilder $subquery,$alias)
    {
        if(!empty($this->select))
        {
            $this->select.=",";
        }
        
        $this->select.="\n(".$subquery->getSql().") AS $alias";
        return $this;
    }
    
    public function from($tablename)
    {
        $this->from=$tablename;
        return $this;
    }
    
    public function fromAlias($tablename,$alias)
    {
        $this->from="$tablename AS $alias";
        return $this;
    }
    
    public function fromSubquery(SqlQueryBuilder $subquery,$alias)
    {
        $this->from="(".$subquery->getSql().") $alias";
        return $this;
    }
    
    public function innerJoin($tablename,$on)
    {
        $this->join.="\n INNER JOIN $tablename ON $on";
        
        return $this;
    }
    
    public function leftJoin($tablename,$on)
    {
        $this->join.="\n LEFT OUTER JOIN $tablename ON $on";
        
        return $this;
    }
    
    public function andWhere($filter)
    {
        if(!empty($this->where))
        {
            $this->where.=" AND ";
        }
        
        $this->where.=$filter;
        return $this;
    }
    
    public function orWhere($filter)
    {
        if(!empty($this->where))
        {
            $this->where.=" OR ";
        }
        
        $this->where.=$filter;
        return $this;
    }
    
    public function andWhereSubquery($filter,$subquery)
    {
        if(!empty($this->where))
        {
            $this->where.=" AND ";
        }
        
        $this->where.=$filter." (".$subquery->getSql().")";
        return $this;
    }
    
    public function groupBy($fields)
    {
        if(!empty($this->groupby))
        {
            $this->groupby.=", ";
        }
        
        $this->groupby.=$fields;
        
        return $this;
    }
    
    public function orderBy($fields)
    {
        if(!empty($this->orderby))
        {
            $this->orderby.=", ";
        }
        
        $this->orderby.=$fields;
        
        return $this;
    }
    
    public function limit($limit)
    {
        $this->limit=$limit;
        return $this;
    }
}

