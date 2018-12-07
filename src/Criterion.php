<?php

    namespace b2db;

    /**
     * Criterion class
     *
     * @package b2db
     * @subpackage core
     */
    class Criterion
    {

        const EQUALS = '=';
        const NOT_EQUALS = '!=';
        const GREATER_THAN = '>';
        const LESS_THAN = '<';
        const GREATER_THAN_EQUAL = '>=';
        const LESS_THAN_EQUAL = '<=';
        const IS_NULL = 'IS NULL';
        const IS_NOT_NULL = 'IS NOT NULL';
        const LIKE = 'LIKE';
        const ILIKE = 'ILIKE';
        const NOT_LIKE = 'NOT LIKE';
        const NOT_ILIKE = 'NOT ILIKE';
        const IN = 'IN';
        const NOT_IN = 'NOT IN';

        protected $column;

        protected $value;

        protected $operator = self::EQUALS;

        protected $variable;

        protected $additional;

        protected $special;

        protected $sql;

	    /**
	     * @var Criteria
	     */
        protected $criteria;

        public static function getOperators()
        {
	        return [
	        	self::EQUALS,
		        self::GREATER_THAN,
		        self::GREATER_THAN_EQUAL,
		        self::ILIKE,
		        self::IN,
		        self::IS_NOT_NULL,
		        self::IS_NULL,
		        self::LESS_THAN,
		        self::LESS_THAN_EQUAL,
		        self::LIKE,
		        self::NOT_EQUALS,
		        self::NOT_ILIKE,
		        self::NOT_IN,
		        self::NOT_LIKE
	        ];
        }

        /**
         * Generate a new criterion
         *
         * @param string $column
         * @param mixed $value[optional]
         * @param string $operator[optional]
         * @param string $variable[optional]
         * @param string $additional[optional]
         * @param string $special[optional]
         */
        public function __construct($column, $value = '', $operator = self::EQUALS, $variable = null, $additional = null, $special = null)
        {
            if ($column !== '') {
                $this->column = $column;
                $this->value = $value;
                if ($operator !== null) {
                    $this->operator = $operator;
                }
                if ($variable !== null) {
                    $this->variable = $variable;
                }
                if ($additional !== null) {
                    $this->additional = $additional;
                }
                if ($special !== null) {
                    $this->special = $special;
                }
            }

            $this->validateOperator();
        }

	    protected function validateOperator()
	    {
		    if (!in_array($this->operator, self::getOperators())) {
			    throw new Exception("Invalid operator", $this->getOperator());
		    }
	    }

	    /**
	     * @param Criteria $criteria
	     */
	    public function setCriteria(Criteria $criteria)
	    {
	    	$this->criteria = $criteria;
	    }

	    /**
	     * @return Criteria
	     */
	    public function getCriteria(): Criteria
	    {
		    return $this->criteria;
	    }

	    /**
         * @return string
         */
        public function getColumn()
        {
            return $this->column;
        }

        /**
         * @param string $column
         */
        public function setColumn($column)
        {
            $this->column = $column;
        }

        /**
         * @return mixed
         */
        public function getValue()
        {
            return $this->value;
        }

        /**
         * @param mixed $value
         */
        public function setValue($value)
        {
            $this->value = $value;
        }

        /**
         * @return string
         */
        public function getOperator()
        {
            return $this->operator;
        }

        /**
         * @param string $operator
         */
        public function setOperator($operator)
        {
            $this->operator = $operator;
            $this->validateOperator();
        }

        /**
         * @return string
         */
        public function getVariable()
        {
            return $this->variable;
        }

        /**
         * @param string $variable
         */
        public function setVariable($variable)
        {
            $this->variable = $variable;
        }

        /**
         * @return string
         */
        public function getAdditional()
        {
            return $this->additional;
        }

        /**
         * @param string $additional
         */
        public function setAdditional($additional)
        {
            $this->additional = $additional;
        }

        /**
         * @return string
         */
        public function getSpecial()
        {
            return $this->special;
        }

        /**
         * @param string $special
         */
        public function setSpecial($special)
        {
            $this->special = $special;
        }

        protected function getQuery()
        {
        	return $this->criteria->getQuery();
        }

        public function isNullTypeOperator()
        {
        	return in_array($this->operator, [self::IS_NOT_NULL, self::IS_NULL]);
        }

        public function isInTypeOperator()
        {
        	return in_array($this->operator, [self::IN, self::NOT_IN]);
        }

	    public function getSql($strip = false)
	    {
	    	if ($this->sql !== null) {
	    		return $this->sql;
		    }

		    $column = ($strip) ? Table::getColumnName($this->column) : $this->getQuery()->getSelectionColumn($this->column);
		    $initial_sql = Query::quoteIdentifier($column);

		    if ($this->special) {
			    $sql = "{$this->special}({$initial_sql})";
		    } else {
		    	$sql = $initial_sql;
		    }

		    if (is_null($this->value) && !$this->isNullTypeOperator()) {
			    $this->operator = ($this->operator == self::EQUALS) ? self::IS_NULL : self::IS_NOT_NULL;
		    } elseif (is_array($this->value) && $this->operator != self::NOT_IN) {
			    $this->operator = self::IN;
		    }

		    $sql .= ' ' . $this->operator;

		    if (!$this->isNullTypeOperator()) {
			    if (is_array($this->value)) {
				    $placeholders = [];
				    for ($cc = 0; $cc < count($this->value); $cc += 1) {
					    $placeholders[] = '?';
				    }
				    $sql .= '(' . implode(', ', $placeholders) . ')';
			    } else {
				    $sql .= $this->isInTypeOperator() ? '(?)' : '?';
			    }
		    }

		    $this->sql = $sql;
		    return $sql;
	    }

    }
