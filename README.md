# JoinsGeneratable Behavior
Convenient shortcut for generating joins options from Model associations.
find('join') is available.

## Usage
In the Model
	var $actsAs = array( ... , 'JoinsGeneratable');
In the Controller or other
	$Model->find('join', array('jointo' => 'Model2'));

Example.

Posts hasMany Comment.
Posts habtm Tag.

	$this->Post->find('join', 'jointo' => array('User', 'Tag'));
	// inner joins options will be following
	array(
		array(
			'table' => 'comments',
			'alias' => 'Comment',
			'type' => 'LEFT',
			'conditions' => array('Post.id' => 'Comment.post_id'),
		),
		array(
			'table' => 'posts_tags',
			'alias' => 'PostsTag',
			'type' => 'LEFT',
			'conditions' => array('Post.id' => 'PostsTag.post_id'),
		),
		array(
			'table' => 'tags',
			'alias' => 'Tag',
			'type' => 'LEFT',
			'conditions' => array('PostsTag.tag_id' => 'Tag.id'),
		),
	)