# Matchable Behavior
##Introduction
Convenient shortcut for generating joins options from Model associations.

* This behaves recursive.


## Usage
In the Model
	var $actsAs = array( ... , 'Matchable');
In the Controller or other
	$Model->find('all', array('jointo' => 'Model2'));
such a option's name "jointo" can be changed by configure.

### Configure
	var $optionName = 'jointo';
	var $associations = array('hasAndBelongsToMany', 'hasOne', 'hasMany', 'belongsTo');
	var $defaultOptions = array(
		'type' => 'LEFT',
		'unbind' => true,
	);
These property can be configured with Behavior's option.
for example
	$actsAs = array(
		'Matchable' => array(
			'optionNAme' => 'models',
		)
	);

##Example
User has Many Posts.
Posts hasMany Comment.
Posts habtm Tag.

	$this->User->find('all', 'jointo' => array(
		'Post' => array(
			'Tag',
			'Comment',
		)
	));
	// inner joins options will be following
	array(
		array(
			'table' => 'posts',
			'alias' => 'Post',
			'type' => 'LEFT',
			'conditions' => array('User.id = Post.user_id'),
		),
		array(
			'table' => 'comments',
			'alias' => 'Comment',
			'type' => 'LEFT',
			'conditions' => array('Post.id = Comment.post_id'),
		),
		array(
			'table' => 'posts_tags',
			'alias' => 'PostsTag',
			'type' => 'LEFT',
			'conditions' => array('Post.id = PostsTag.post_id'),
		),
		array(
			'table' => 'tags',
			'alias' => 'Tag',
			'type' => 'LEFT',
			'conditions' => array('PostsTag.tag_id = Tag.id'),
		),
	)