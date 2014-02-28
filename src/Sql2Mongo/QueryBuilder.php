<?php 
namespace Sql2Mongo;
/*
The MIT License (MIT)

Copyright (c) 2014 Frederic ROBINET

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/
/*
 * @link http://github.com/robinef
 *
 * @package Sql2Mongo
 * @author robinef@gmail.com
 */
class QueryBuilder {

	/**
	 * @const ORDER DESC val
	 */
	const ORDER_DESC = -1;

	/**
	 * @const ORDER ASC val
	 */
	const ORDER_ASC = 1;
	
	/**
	 * @const Operator superior
	 */	
	const OPERATOR_SUP = '>';
	
	/**
	 * @const Operator inferior
	 */	
	const OPERATOR_INF = '<';
	
	/**
	 * @const Operator equel
	 */	
	const OPERATOR_EQUAL = '=';
	
	/**
	 * @const Operator different
	 */	
	const OPERATOR_DIFF = '!=';
	
	/**
	 * @var \MongoClient PHP Mongo client
	 */
	private $_client = null;

	/**
	 * @var boolean Perform select
	 */
	private $_select = false;
	
	/**
	 * @ var boolean Perform group
	 */
	private $_group = false;
	
	/**
	 * @var string Collection name
	 */
	private $_collection = null;
	
	/**
	 * @var array[] fields to group
	 */
	private $_groupFields = array();
	
	/**
	 * @var array[] fields to be selected
	 */
	private $_selectFields = array();
	/**
	 * @var array[] Where fields
	 */
	private $_whereFields = array();
	
	/**
	 * @var array[] Between fields
	 */
	private $_betweenFields = array();
	
	/**
	 * @var array[] Sort fields
	 */
	private $_sortFields = array();

	/**
	 * @var array[] Sum fields
	 */	
	private $_addSum = array();
	
	/**
	 * @var int Limit
	 */
	private $_limit = null;

	/**
	 * @var int Count
	 */
	private $_count = null;	
	
	/**
	 * @var Keys 
	 */
	private $_keys = array();

	/**
	 * Ctor
	 *
	 * @param MongoDB $mongoClient Php mongo db
	 * @see http://www.php.net/manual/fr/class.mongoclient.php
	 */
	public function __construct($mongoDb) {
		if($mongoDb instanceof \MongoDB) {
			$this->_client = $mongoDb;
		} else {
			throw new Exception(_('Client is not a PHP MongoClient'));
		}
	}
	
	/**
	 * Sum val
	 *
	 * @param string $sumVal
	 */
	public function sum($sumVal) {

		if(isset($sumVal) && is_string($sumVal)) {
			$this->_addSum[$sumVal] = $sumVal;
		}
	}

	/**
	 * Add From table
	 *
	 * @param string $tableName 
	 * @param Array[] $tableValues Table values
	 */
	public function from($tableName, $tableValues = null) {
		if(isset($tableName) && is_string($tableName)) {
			$this->_collection = $tableName;
		}

		if(isset($tableValues) && is_array($tableValues)) {
			foreach($tableValues as $val) {
				$this->_selectFields[$val] = 1;
			}
		}
		return $this;
	}
	
	
	
	/**
	 * Add where condition
	 *
	 * @param string $field
	 * @param string $operator
	 * @param string $value
	 */
	public function where($field, $operator, $value) {
		if(isset($field) && is_string($field) && isset($value)) {
			//If date then convert
			if(strtotime($value)) {
				$value = new \MongoDate(strtotime($value));
			}
			$this->_whereFields[$field] = array('op'=> $operator, 'val' => $value);
		}
		return $this;
	}
	
	/**
	 * Adding limit to number of results
	 *
	 * @param int $limit
	 */
	public function limit($limit) {
	
		if(is_int($limit)) {
			$this->_limit = $limit;
		}
		return $this;
	}
	
	/**
	 * Perform a select Table
	 */
	public function select() {
		$this->_select = true;
		return $this;
	}
	
	/**
	 * Perform a count
	 */
	public function count() {
		$this->_count = true;
		return $this;
	}	
	
	/**
	 * Add Between 
	 *
	 * @param string $field Field name
	 * @param string $value1 First value
	 * @param string $value2 Second value
	 */
	public function between($field, $value1, $value2) {
		if(strtotime($value1)) {
			$value1 = new \MongoDate(strtotime($value1));
		}
		if(strtotime($value2)) {
			$value2 = new \MongoDate(strtotime($value2));
		}
		$this->_betweenFields[$field] = array($value1, $value2);
		return $this;
	}
	
	/**
	 * Group by
	 *
	 * @param string $field
	 * @param string $order
	 */
	public function group($field) {
		$this->_group = true;
		if(is_string($field)) {
			array_push($this->_keys, $field);
		}
		return $this;
	}
	
	/**
	 * Add sort
	 *
	 * @param string $field Field to sort
	 * @param string $way DESC or ASC
	 */
	public function order($field, $order = self::ORDER_DESC) {
		if(isset($field) && is_string($field)) {
			$this->_sortFields[$field] = $order;
		}
	}
	
	/**
	 * Perform Mongo command
	 *
	 * @return MongoCursor|array[]
	 */
	public function query() {
		$cursor = null;
		$initial = array();

		
		$collection = new \MongoCollection($this->_client, $this->_collection);
		/**
		 * GROUP BY
		 */
		if($this->_group) {
			//Prepare group keys

			foreach($this->_selectFields as $key => $value) {
				$keys[$key] = 1;
			}
			foreach($this->_keys as $key) {
				$keys[$key] = 1;
			}

			//Prepare reduce function
	
			$reduce = "function(curr, result) {"; 
			if(is_array($this->_addSum) && count($this->_addSum) > 0) {
				foreach($this->_addSum as $sumVal) {
			
					$reduce .= sprintf("result.%sTotal += curr.%s;", $sumVal, $sumVal);
					$initTotal = sprintf("%sTotal", $sumVal);
					$initial[$initTotal] = 0;
				}
			//Prepare conditions
				
			} else {
				foreach($this->_keys as $key) {
					$initial["items_".md5($key)] = array();
					$reduce .= sprintf("result.items_%s.push(curr.%s);",md5($key), $key);
				}	
			}
			$reduce .= "}";
			$condition = array('condition'=> array());
			foreach($this->_whereFields as $field => $item) {
				switch($item['op']) {
					case self::OPERATOR_EQUAL:
						if(ctype_digit($item['val'])) {
							$condition['condition'][$field] = $item['val'];
						} else {
							$condition['condition'][$field] = $item['val'];
						}
						break;
					case self::OPERATOR_SUP:
						$condition['condition'][$field] = array('$gt' => (int)$item['val']);
						break;
					case self::OPERATOR_DIFF:
						$condition['condition'][$field] = array('$ne' => (int)$item['val']);
						break;
				}
				
			}
			foreach($this->_betweenFields as $field => $value) {
				$condition['condition'][$field] = array('$gte' => $value[0],
				'$lt' => $value[1]);
			}

			$cursor = $collection->group($keys, $initial, $reduce, $condition);
			/**
			 * SELECT
			 */
		} else if($this->_select) {
			$condition = array();

			foreach($this->_whereFields as $field => $value) {
				if(ctype_digit($value['val'])) {
					$condition[$field] = (int)$value['val'];
				} else {
					$condition[$field] = $value['val'];
				}
			}
			foreach($this->_betweenFields as $field => $value) {
				$condition[$field] = array('$gte' => $value[0],
				'$lt' => $value[1]);
			}
			
			//COUNT
			if($this->_count) {
				$cursor = $collection->find($condition)->count(true);
			} else {
				$cursor = $collection->find($condition, $this->_selectFields);
			}
			
		}

		if($cursor instanceof \MongoCursor) {
			/**
			 * Cannot ORDER after grouping
			 * @TODO MapReduce or sort manually
			 *
			 */
			$cursor->sort($this->_sortFields);
			//LIMIT
			if($this->_limit) {
				$cursor->limit($this->_limit);
			}

		}
		return $cursor;
	}
}
?>