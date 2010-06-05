# JoinsGeneratable Behavior
##Introduction
Convenient shortcut for generating joins options from Model associations.

* find('join') is available.
* This behaves recursive.


## Usage
In the Model
	var $actsAs = array( ... , 'JoinsGeneratable');
In the Controller or other
	$Model->find('join', array('jointo' => 'Model2'));

Example.
User has Many Posts.
Posts hasMany Comment.
Posts habtm Tag.

	$this->User->find('join', 'jointo' => array(
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