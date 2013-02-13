cake-FkRecordModel
==================

This is a plug-in to handle the entity object in the CakePHP 2.x.

Licensed under The GPLv3 License


## Usage

#### /Model/AppModel.php

    App::uses('FkRecordModel', 'FkRecordModel.Model');

    class AppModel extends FkRecordModel {
        // Definition
    }

    class AppRecord extends FkRecord {
        // Definition
    }


#### /Model/Post.php

    App::uses('AppModel', 'Model');

    class Post extends AppModel {
        // Definition
    }

    class Post extends AppRecord {
        function updateDateBy($format) {
            return date($format, strtotime($this->modified));
        }
    }


#### /Cotroller/PostsController.php

    class PostsController extends AppController {

        $uses = array('Post');

        function index() {
            $posts = $this->find('all');
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


#### /View/Posts/index.ctp

    <?php foreach ($posts as $post): ?>

        <h1><?php echo $post->title ?></h1>
        <p><?php echo $post->description ?></p>
        <p>Updated at: <?php echo $post->updateDateBy('Y/m/s H:i') ?></p>

    <?php endforeach; ?>


