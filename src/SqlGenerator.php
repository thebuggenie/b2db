<?php

    namespace b2db;

    use b2db\interfaces\QueryInterface;

    /**
     * Criterion class
     *
     * @package b2db
     * @subpackage core
     */
    class SqlGenerator
    {

        protected $sql;

	    protected $values;

	    /**
	     * @var Query
	     */
        protected $query;

	    /**
	     * @param Query $query
	     * @throws Exception
	     */
        public function __construct(Query $query)
        {
            $this->query = $query;
        }

	    /**
	     * @return Table
	     */
        protected function getTable()
        {
        	return $this->query->getTable();
        }

	    /**
	     * Add a specified value
	     *
	     * @param mixed $value
	     */
	    protected function _addValue($value)
	    {
		    if (is_bool($value)) {
			    if (Core::getDriver() == Core::DRIVER_MYSQL) {
				    $this->values[] = (int) $value;
			    } elseif (Core::getDriver() == Core::DRIVER_POSTGRES) {
				    $this->values[] = ($value) ? 'true' : 'false';
			    }
		    } else {
			    $this->values[] = $value;
		    }
	    }

	    protected function generateWherePartSql()
	    {
	    	$sql = '';
		    if ($this->query->hasCriteria()) {
		    	$sql_parts = [];
			    foreach ($this->query->getCriteria() as $criteria) {
				    $sql_parts[] = $criteria->getSql();
			    }

			    if (count($sql_parts) > 1) {
				    $sql = '(' . join(" {$this->query->getMode()} ", $sql_parts) . ')';
			    } else {
				    $sql = $sql_parts[0];
			    }
			    $sql .= ' WHERE ' . $sql;
		    }

		    return $sql;
	    }

	    protected function generateGroupPartSql()
	    {
	    	$sql = '';

		    if ($this->query->hasSortGroups() || ($this->query->hasSortOrders() && $this->query->isCount())) {
			    $group_columns = [];
			    $groups = [];
			    foreach ($this->query->getSortGroups() as $sort_group) {
				    $column_name = $this->query->getSelectionColumn($sort_group->getColumn());
				    $groups[] = Query::quoteIdentifier($column_name) . ' ' . $sort_group->getOrder();
				    if ($this->query->isCount()) {
					    $group_columns[$column_name] = $column_name;
				    }
			    }
			    $sql .= ' GROUP BY ' . implode(', ', $groups);
			    if ($this->query->isCount()) {
				    $sort_orders = [];
				    foreach ($this->query->getSortOrders() as $sort_order) {
					    $column_name = $this->query->getSelectionColumn($sort_order->getColumn());
					    if (!array_key_exists($column_name, $group_columns)) {
						    $sort_orders[] = Query::quoteIdentifier($column_name) . ' ';
					    }
				    }
				    $sql .= implode(', ', $sort_orders);
			    }
		    }

		    return $sql;
	    }

	    protected function generateOrderByPartSql()
	    {
	    	$sql = '';

		    if ($this->query->hasSortOrders()) {
			    $sql_parts = [];
			    foreach ($this->query->getSortOrders() as $sort_order) {
				    if (is_array($sort_order->getOrder())) {
					    $subsort_sql_parts = [];
					    foreach ($sort_order->getOrder() as $sort_elm) {
						    $subsort_sql_parts[] = Query::quoteIdentifier($this->query->getSelectionColumn($sort_order->getColumn())) . '=' . $sort_elm;
					    }
					    $sql_parts[] = implode(',', $subsort_sql_parts);
				    } else {
					    $sort_sql = Query::quoteIdentifier($this->query->getSelectionColumn($sort_order->getColumn()));
					    if (in_array($sort_order->getOrder(), array(QueryColumnSort::SORT_ASC_NUMERIC, QueryColumnSort::SORT_DESC_NUMERIC))) {
						    $sort_sql .= '+0 ' . substr($sort_order->getOrder(), 0, -8);
					    } else {
						    $sort_sql .= ' ' . $sort_order->getOrder();
					    }
					    $sql_parts[] = $sort_sql;
				    }
			    }
			    $sql .= ' ORDER BY ' . implode(', ', $sql_parts);
		    }

	    	return $sql;
	    }

	    /**
	     * Generate the "where" part of the query
	     *
	     * @return string
	     */
	    protected function generateWhereSQL()
	    {
		    $sql = $this->generateWherePartSql();
		    $sql .= $this->generateGroupPartSql();
		    $sql .= $this->generateOrderByPartSql();
		    if ($this->query->isSelect()) {
			    if ($this->query->hasLimit()) {
				    $sql .= ' LIMIT ' . $this->query->getLimit();
			    }
			    if ($this->query->hasOffset()) {
				    $sql .= ' OFFSET ' . $this->query->getOffset();
			    }
		    }

		    return $sql;
	    }

	    /**
	     * Generate the "join" part of the sql
	     *
	     * @return string
	     */
	    protected function generateJoinSQL()
	    {
		    $sql = ' FROM ' . $this->getTable()->getSelectFromSql();
		    foreach ($this->query->getJoins() as $join) {
			    $sql .= ' ' . $join->getJoinType() . ' ' . $join->getTable()->getSelectFromSql();
			    $sql .= ' ON (' . Query::quoteIdentifier($join->getLeftColumn()) . Criterion::EQUALS . Query::quoteIdentifier($join->getRightColumn());

			    if ($join->hasAdditionalCriteria()) {
				    $sql_parts = [];

				    foreach ($join->getAdditionalCriteria() as $criteria) {
					    $sql_parts[] = $criteria->getSql();
				    }

				    $sql .= ' AND ' . join(' AND ', $sql_parts);
			    }

			    $sql .= ')';
		    }

		    return $sql;
	    }

	    /**
	     * Adds all select columns from all available tables in the query
	     */
	    protected function addAllSelectColumns()
	    {
		    foreach ($this->getTable()->getAliasColumns() as $column) {
			    $this->query->addSelectionColumnRaw($column);
		    }

		    foreach ($this->query->getJoins() as $join) {
			    foreach ($join->getTable()->getAliasColumns() as $column) {
				    $this->query->addSelectionColumnRaw($column);
			    }
		    }
	    }

	    /**
	     * Generates "select all" SQL
	     *
	     * @return string
	     */
	    protected function _generateSelectAllSQL()
	    {
		    $sql_parts = [];
		    foreach ($this->query->getSelectionColumns() as $selection) {
			    $sql_parts[] = $selection->getSql();
		    }
		    return implode(', ', $sql_parts);
	    }

	    /**
	     * Generate the "select" part of the query
	     *
	     * @return string
	     */
	    protected function generateSelectSQL()
	    {
		    $sql = ($this->query->isDistinct()) ? 'SELECT DISTINCT ' : 'SELECT ';

		    if ($this->query->isCustomSelection()) {
			    if ($this->query->isDistinct() && Core::getDriver() == Core::DRIVER_POSTGRES) {
				    foreach ($this->query->getSortOrders() as $sort_order) {
					    $this->query->addSelectionColumn($sort_order->getColumn());
				    }
			    }

			    $sql_parts = [];
			    foreach ($this->query->getSelectionColumns() as $column => $selection) {
				    $alias = ($selection->getAlias()) ?? $this->query->getSelectionAlias($column);
				    $sub_sql = $selection->getVariableString();
				    if ($selection->isSpecial()) {
					    $sub_sql .= mb_strtoupper($selection->getSpecial()) . '(' . Query::quoteIdentifier($selection->getColumn()) . ')';
					    if ($selection->hasAdditional())
						    $sub_sql .= ' ' . $selection->getAdditional() . ' ';
					    if (mb_strpos($selection->getSpecial(), '(') !== false)
						    $sub_sql .= ')';
				    } else {
					    $sub_sql .= Query::quoteIdentifier($selection->getColumn());
				    }
				    $sub_sql .= ' AS ' . Query::quoteIdentifier($alias);
				    $sql_parts[] = $sub_sql;
			    }
			    $sql .= implode(', ', $sql_parts);
		    } else {
			    $this->addAllSelectColumns();
			    $sql .= $this->_generateSelectAllSQL();
		    }

		    return $sql;
	    }

	    /**
	     * Generate a "select" query
	     *
	     * @param boolean $all [optional]
	     * @return string
	     */
	    public function getSelectSQL($all = false)
	    {
		    if (!$this->query->getTable() instanceof Table) {
			    throw new Exception('Trying to generate sql when no table is being used.');
		    }
		    $sql = $this->generateSelectSQL();
		    $sql .= $this->generateJoinSQL();
		    if (!$all) {
			    $sql .= $this->generateWhereSQL();
		    }

		    return $sql;
	    }

	    /**
	     * Generate the "count" part of the query
	     *
	     * @return string
	     */
	    protected function generateCountSQL()
	    {
		    $sql = ($this->query->isDistinct()) ? 'SELECT COUNT(DISTINCT ' : 'SELECT COUNT(';
		    $sql .= Query::quoteIdentifier($this->query->getSelectionColumn($this->getTable()->getIdColumn()));
		    $sql .= ') as num_col';

		    return $sql;
	    }

	    /**
	     * Generate a "count" query
	     *
	     * @return string
	     */
	    public function getCountSQL()
	    {
		    if (!$this->query->getTable() instanceof Table) {
			    throw new Exception('Trying to generate sql when no table is being used.');
		    }
		    $sql = $this->generateCountSQL();
		    $sql .= $this->generateJoinSQL();
		    $sql .= $this->generateWhereSQL();

		    return $sql;
	    }

	    /**
	     * Generate the "update" part of the query
	     *
	     * @param Insertion $insertion
	     * @return string
	     */
	    protected function generateUpdateSQL(Insertion $insertion)
	    {
		    $updates = [];
		    foreach ($insertion->getValues() as $column => $value) {
			    $column = mb_substr($column, mb_strpos($column, '.') + 1);
			    $prefix = Query::quoteIdentifier($column);
			    $updates[] = $prefix . Criterion::EQUALS . '?';

			    $this->_addValue($value);
		    }
		    $sql = 'UPDATE ' . Query::quoteIdentifier($this->getTable()->getSqlTableName()) . ' SET ' . implode(', ', $updates);
		    return $sql;
	    }

	    /**
	     * Generate an "update" query
	     *
	     * @param Insertion $insertion
	     * @return string
	     * @throws Exception
	     */
	    public function getUpdateSQL(Insertion $insertion)
	    {
		    if (!$this->query->getTable() instanceof Table) {
			    throw new Exception('Trying to generate sql when no table is being used.');
		    }
		    $sql = $this->generateUpdateSQL($insertion);
		    $sql .= $this->generateWhereSQL();

		    return $sql;
	    }

	    /**
	     * Generate an "insert" query
	     *
	     * @param Insertion $insertion
	     *
	     * @return string
	     */
	    public function getInsertSQL(Insertion $insertion)
	    {
		    if (!$this->query->getTable() instanceof Table) {
			    throw new Exception('Trying to generate sql when no table is being used.');
		    }

		    $inserts = [];
		    $values = [];
		    $table_name = Query::quoteIdentifier($this->getTable()->getSqlTableName());

		    foreach ($insertion->getValues() as $column => $value) {
			    $column = mb_substr($column, mb_strpos($column, '.') + 1);
			    $inserts[] = Query::quoteIdentifier($column);

			    if ($insertion->hasVariable($column)) {
				    $values[] = '@' . $insertion->getVariable($column);
			    } else {
				    $values[] = '?';
				    $this->_addValue($value);
			    }
		    }

		    $inserts = implode(', ', $inserts);
		    $values = implode(', ', $values);

		    $sql = "INSERT INTO {$table_name} ({$inserts}) VALUES ({$values})";

		    return $sql;
	    }

	    /**
	     * Generate a "delete" query
	     *
	     * @return string
	     */
	    public function getDeleteSQL()
	    {
		    if (!$this->query->getTable() instanceof Table) {
			    throw new Exception('Trying to generate sql when no table is being used.');
		    }
		    $sql = 'DELETE FROM ' . Query::quoteIdentifier($this->getTable()->getSqlTableName());
		    $sql .= $this->generateWhereSQL();

		    return $sql;
	    }

    }
