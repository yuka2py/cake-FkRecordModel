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

    /**
     * Define verbose name. It is used in the label name, such as, for example.
     */
    var $verboseName = array(
        'title' => 'Title',
        'description' => 'Description',
    );

    // other definition...
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
    // Definition
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
<?php foreach ($posts as $post): ?>

    <h1><?php echo $post->title ?></h1>
    <p><?php echo $post->description ?></p>
    <p>Updated at: <?php echo $post->updateDateBy('Y/m/s H:i') ?></p>

<?php endforeach; ?>
```





#### /View/Posts/edit.ctp

```php
<?php

//Bind to FormHelper
$post->bindFormHelper($this->Form);

echo $post->Form->create('Post');
?>
<label>
    <?php echo $post->getVerboseName('title') /* Print verbose name */ ?>: 
    <?php echo $post->Form->text('decription') ?>
    <?php echo $post->printError('title') /* print error message if has errored only */?>
</label>

<label>
    <?php echo $post->getVerboseName('description') ?>: 
    <?php echo $post->Form->textarea('decription') ?>
    <?php echo $post->printError('description') ?>
</label>

<p>
    <?php echo $post->submit('Send') ?>
</p>
```

