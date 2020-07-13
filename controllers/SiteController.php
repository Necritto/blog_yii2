<?php

namespace app\controllers;

use app\models\Article;
use app\models\Category;
use app\models\CommentForm;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\data\Pagination;

class SiteController extends Controller
{
  /**
   * {@inheritdoc}
   */
  public function behaviors()
  {
    return [
      'access' => [
        'class' => AccessControl::className(),
        'only' => ['logout'],
        'rules' => [
          [
            'actions' => ['logout'],
            'allow' => true,
            'roles' => ['@'],
          ],
        ],
      ],
      'verbs' => [
        'class' => VerbFilter::className(),
        'actions' => [
          'logout' => ['post'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function actions()
  {
    return [
      'error' => [
        'class' => 'yii\web\ErrorAction',
      ],
      'captcha' => [
        'class' => 'yii\captcha\CaptchaAction',
        'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
      ],
    ];
  }

  /**
   * Displays homepage.
   *
   * @return string
   */
  public function actionIndex()
  {
    $query = Article::find();
    $count = $query->count();
    $pagination = new Pagination(['totalCount' => $count, 'pageSize' => 5]);
    $articles = $query->offset($pagination->offset)->limit($pagination->limit)->all();

    $popular = Article::getPopular();
    $recent = Article::getRecent();
    $categories = Category::getAll();

    return $this->render('index', [
      'articles' => $articles,
      'pagination' => $pagination,
      'popular' => $popular,
      'recent' => $recent,
      'categories' => $categories
    ]);
  }

  public function actionView($id)
  {
    $article = Article::findOne($id);
    $popular = Article::getPopular();
    $recent = Article::getRecent();
    $categories = Category::getAll();
    $comments = $article->getComments()->where(['status' => 1])->all();
    $commentForm = new CommentForm();

    $article->viewedCounter();

    return $this->render('single', [
      'article' => $article,
      'popular' => $popular,
      'recent' => $recent,
      'categories' => $categories,
      'comments' => $comments,
      'commentForm' => $commentForm
    ]);
  }

  public function actionCategory($id)
  {
    $query = Article::find()->where(['category_id' => $id]);
    $count = $query->count();
    $pagination = new Pagination(['totalCount' => $count, 'pageSize' => 10]);
    $articles = $query->offset($pagination->offset)->limit($pagination->limit)->all();

    $popular = Article::getPopular();
    $recent = Article::getRecent();
    $categories = Category::getAll();

    return $this->render('category', [
      'articles' => $articles,
      'pagination' => $pagination,
      'popular' => $popular,
      'recent' => $recent,
      'categories' => $categories
    ]);
  }

  public function actionComment($id)
  {
    $model = new CommentForm();

    if (Yii::$app->request->isPost) {
      $model->load(Yii::$app->request->post());

      if ($model->saveComment($id)) {
        Yii::$app->getSession()->setFlash('comment', 'Your comment will be added soon!');
        return $this->redirect(['site/view', 'id' => $id]);
      }
    }
  }
}
