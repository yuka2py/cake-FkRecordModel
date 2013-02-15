cake-FkRecordModel
==================

This is a plug-in to handle the entity object in the CakePHP 2.x.

Licensed under The GPLv3 License


## Usage

#### /Model/AppModel.php

```php
<?php 

App::uses('FkRecordModel', 'FkRecordModel.Model');

class AppModel extends FkRecordModel {
    // definition...
}

class AppRecord extends FkRecord {
    // definition...
}

?>
```

#### /Model/Post.php

```php
<?php

App::uses('AppModel', 'Model');

class Post extends AppModel {
    /**
     * Define verbose name. It is used in the label name, such as, for example.
     */
    var $verboseName = array(
        'title' => 'Title',
        'description' => 'Description',
    );

    var $hasMany = array(
        'tags' => array(
            'className' => 'PostTag',
            'dependent' => true,
        ),
    );

    // Other definition...
}

class PostRecord extends AppRecord {
    function updateDateBy($format) {
        return date($format, strtotime($this->modified));
    }
}

?>
```

#### /Cotroller/PostsController.php

```php
<?php

class PostsController extends AppController {

    $uses = array('Post');

    function index() {
        $posts = $this->Post->find('all');
        $this->set(compact('posts'));
    }

    function show($id) {
        $post = $this->Post->findById($id);
        $this->set(compact('post'));
    }

    function create() {
        $post = $this->Post->build();
        $this->set(compact('post'));

        if ($this->request->is('post')) {
            $post->setData($this->data);
            if ($post->save()) {
                // processing on success.
            } else {
                // processing on failed.
            }
        }
    }

    function edit($id) {
        $post = $this->Post->findById($id);
        $this->set(compact('post'));

        if ($this->request->is('put')) {
            $post->setData($this->data);
            if ($post->save()) {
                // processing on success.
            } else {
                // processing on failed.
            }
        }
    }
}

?>
```

#### /View/Posts/index.ctp


```php
<?php foreach ($posts as $post) : ?>

    <article id="post-<?php echo $post->getID() ?>">
        <h1><?php echo $post->title ?></h1>
        <p><?php echo $post->description ?></p>
        <p>Updated at: <?php echo $post->updateDateBy('Y/m/s H:i') ?></p>
<?php if (! $post->tags->isEmpty()) : ?>
        <ul>
<?php foreach ($post->tags as $tag) : /* loop for relations */ ?>
            <li><?php echo $tag->name ?></li>
<?php endforeach; # $post->tags ?>
        </ul>
<?php endif; # !$post->tags->isEmpty() ?>
    </article>

<?php endforeach; # $posts ?>
```





#### /View/Posts/edit.ctp

```php
<?php $post->bindFormHelper($this->Form) /* Bind to FormHelper */ ?>
<?php echo $post->Form->create('Post') /* Start form */ ?>
?>
<label>
    <?php echo $post->getVerboseName('title') ?>: 
    <?php echo $post->Form->text('title') ?>
    <?php echo $post->getError('title') ?>
</label>

<label>
    <?php echo $post->getVerboseName('description') ?>: 
    <?php echo $post->Form->textarea('decription') ?>
    <?php echo $post->getError('description') ?>
</label>

<p>
    <?php echo $post->submit('Send') ?>
</p>

<?php echo $post->Form->end() ?>
```

