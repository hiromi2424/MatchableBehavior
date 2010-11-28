<?php
class MatchableBehavior extends ModelBehavior {
	var $optionName = 'jointo';
	var $associations = array('hasAndBelongsToMany', 'hasOne', 'hasMany', 'belongsTo');
	var $defaultOptions = array(
		'type' => 'LEFT',
		'unbind' => true,
	);
	var $__cacheJoins = array();
	
	function setup(&$Model, $config = array()) {
		$this->_set($config);
	}
	
	function beforeFind(&$Model, $query){
		if (!isset($query[$this->optionName])) {
			return $query;
		}
		$tojoin = (array)$query[$this->optionName];
		if(!empty($tojoin['clearCache'])){
			$this->__cacheJoins = array();
			unset($tojoin['clearCache']);
		}
		
		$joins = isset($query['joins']) ? $query['joins'] : array();
		$joins = array_merge($joins, $this->prepareJoins($Model, $tojoin));
		$query['joins'] = $joins;
		return $query;
	}
	
	function prepareJoins(&$Model, $tojoin) {
		$tojoin = Set::normalize($tojoin);
		$cacheKey = sha1($Model->alias . serialize($tojoin));
		$isCached = false;
		if (isset($this->__cacheJoins[$cacheKey])) {
			list($joins, $unbinds) = $this->__cacheJoins[$cacheKey];
			$isCached = true;
		} else {
			$joins = array();
			$unbinds = array();
			
			foreach ($tojoin as $alias => $options) {
				foreach ($this->associations as $association) {
					if (isset($Model->{$association}[$alias])) {
						$options = Set::merge($this->defaultOptions, $options);
						
						$additionals = $options;
						unset($additionals['type']);
						unset($additionals['unbind']);
						
						if (!empty($additionals)) {
							$joins = array_merge($joins, $this->prepareJoins($Model->$alias, $additionals));
						}
						$join = $this->__joinsOptions($Model, $alias, $Model->{$association}[$alias], $association, $options);
						if (!empty($join)) {
							$joins = array_merge($joins, $join);
							if ($options['unbind']) {
								$unbinds[$association][] = $alias;
								// $Model->unbindModel(array($association => array($alias)));
							}
						}
					}
				}
			}
			$joins = array_reverse($joins);
		}
		if (!empty($unbinds)) {
			$Model->unbindModel($unbinds);
		}
		if (!$isCached) {
			$this->__cacheJoins[$cacheKey] = array($joins, $unbinds);
		}
		return $joins;
	}
	
	function __joinsOptions(&$Model, $alias, $assoc, $type, $options = array()) {
		$joins = array();
		$foreignKey = $assoc['foreignKey'];
		switch ($type) {
			case 'hasOne':
			case 'hasMany':
				$conditions = array("{$alias}.{$foreignKey} = {$Model->alias}.{$Model->primaryKey}");
				break;
			case 'belongsTo':
				$conditions = array("{$alias}.{$Model->$alias->primaryKey} = {$Model->alias}.{$foreignKey}");
				break;
			case 'hasAndBelongsToMany':
				$joins = $this->__joinsOptions($Model, $assoc['with'], $assoc, 'habtmSecond', $options);
				$conditions = array("{$alias}.{$Model->$alias->primaryKey} = {$assoc['with']}.{$assoc['associationForeignKey']}");
				break;
			case 'habtmSecond':
				$conditions = array("{$alias}.{$foreignKey} = {$Model->alias}.{$Model->primaryKey}");
				break;
		}
		$join = array(
			'table' => $type == 'habtmSecond' ? $assoc['joinTable'] : $Model->{$alias}->table,
			'alias' => $alias,
			'type' => $options['type'],
			'conditions' => $conditions,
		);
		array_unshift($joins, $join);
		return $joins;
	}
}