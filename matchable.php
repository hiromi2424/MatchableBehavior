<?php
class MatchableBehavior extends ModelBehavior {
	var $findMethod = 'matches';
	var $optionName = 'jointo';
	var $associations = array('hasAndBelongsToMany', 'hasOne', 'hasMany', 'belongsTo');
	var $defaultOptions = array(
		'type' => 'LEFT',
		'unbind' => true,
	);
	
	function setup(&$Model, $config = array()) {
		$this->_set($config);
		if ($this->findMethod) {
			$Model->_findMethods[$this->findMethod] = true;
			$this->mapMethods = array("/_find{$this->findMethod}/" => '_findMathces');
		}
	}
	
	function _findMatches(&$Model, $dummy, $state, $query, $results = array()) {
		if ($state == 'after') {
			return $results;
		}
		
		if (!isset($query[$this->optionName])) {
			return $query;
		}
		
		$joins = isset($query['joins']) ? $query['joins'] : array();
		$joins = Set::merge($joins, $this->prepareJoins($Model, $query[$this->optionName]));
		$query['joins'] = $joins;
		return $query;
	}
	
	function prepareJoins(&$Model, $tojoin) {
		$tojoin = Set::normalize($tojoin);
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
		if (!empty($unbinds)) {
			$Model->unbindModel($unbinds);
		}
		return array_reverse($joins);
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