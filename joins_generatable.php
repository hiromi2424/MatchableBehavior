<?php
class JoinsGeneratableBehavior extends ModelBehavior {
	var $findJoin = true;
	var $optionName = 'jointo';
	var $associations = array('hasAndBelongsToMany', 'hasOne', 'hasMany', 'belongsTo');
	
	function setup(&$Model, $config = array()){
		$this->_set($config);
		if($this->findJoin){
			$Model->_findMethods['join'] = true;
			$this->mapMethods = array('/_findJoin/' => '_findJoin');
		}
	}
	
	function _findJoin(&$Model, $dummy, $state, $query, $results = array()) {
		if ($state == 'after') {
			return $results;
		}
		
		if (!isset($query[$this->optionName])) {
			return $query;
		}
		
		$tojoin = Set::normalize($query[$this->optionName]);
		
		$joins = isset($query['joins']) ? $query['joins'] : array();
		
		foreach ($tojoin as $alias => $options) {
			// Model::__associations is private property
			foreach ($this->associations as $association) {
				if (isset($Model->{$association}[$alias])) {
					$join = $this->generateJoins($Model, $alias, $Model->{$association}[$alias], $association, $options);
					if (!empty($join)) {
						$joins = Set::merge($joins, $join);
						$Model->unbindModel(array($association => array($alias)));
					}
				}
			}
		}
		$query['joins'] = $joins;
		return $query;
	}
	
	function generateJoins(&$Model, $alias, $assoc, $type, $options = array()) {
		$options = Set::merge(array('type' => 'LEFT'), $options);
		$joins = array();
		$foreignKey = $assoc['foreignKey'];
		switch ($type) {
			case 'hasOne':
			case 'hasMany':
				$conditions = array("{$alias}.{$foreignKey}" => "{$Model->alias}.{$Model->primaryKey}");
				break;
			case 'belongsTo':
				$conditions = array("{$alias}.{$Model->$alias->primaryKey}" => "{$Model->alias}.{$foreignKey}");
				break;
			case 'hasAndBelongsToMany':
				$joins = $this->createJoins($Model, $assoc['with'], $assoc, 'habtmSecond', $options);
				$conditions = array("{$alias}.{$Model->$alias->primaryKey}" => "{$assoc['with']}.{$assoc['associationForeignKey']}");
				break;
			case 'habtmSecond':
				$conditions = array("{$alias}.{$foreignKey}" => "{$Model->alias}.{$Model->primaryKey}");
				break;
		}
		$join = array(
			'table' => $type == 'habtmSecond' ? $assoc['joinTable'] : $Model->{$alias}->table,
			'alias' => $alias,
			'type' => $options['type'],
			'conditions' => $conditions,
		);
		$joins[] = $join;
		return $joins;
	}
}