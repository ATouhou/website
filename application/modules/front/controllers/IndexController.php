<?php

namespace app\modules\front\controllers;

use app\controllers\SController;
use app\modules\front\models\ContactForm;
use Yii;
use yii\web\ForbiddenHttpException;

class IndexController extends SController
{
	protected $_collections;
	protected $_hadithCount;

	/* This function performs page caching  */
    public function behaviors() {
        return [
            [
                   'class' => 'app\components\CdnOriginAndEdgeCache',
                   'except' => ['flush-cache', 'ajaxhadithcount', 'contact', 'captcha'],
                   'duration' => Yii::$app->params['cacheTTL'],
                   'variations' => [ Yii::$app->request->get('id') ],
        
            ],
        ];
    }

    public function actions()
    {
        return [
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

	public function beforeAction($action) {
		if ($action->id == 'ajaxhadithcount') { $this->enableCsrfValidation = false; }
		return parent::beforeAction($action);
	}

    public function actionError() {
        $exception = Yii::$app->errorHandler->exception;
		$this->view->params['_pageType'] = "error";
		return $this->render('error', ['exception' => $exception]);
	}

	public function actionAjaxhadithcount() {
		$postMessage = Yii::$app->request->post('msg');
		Yii::info($postMessage, 'hadithcount');
	}

	public function actionIndex()
	{
		$this->layout = "home";
        $this->_collections = $this->util->getCollectionsInfo('none', true);
        $this->_hadithCount = $this->util->getHadithCount();
        $this->view->params['_pageType'] = "home";

        if (array_key_exists("showCarousel", Yii::$app->params)) {
            $carousel = Yii::$app->params['showCarousel'];
            if (strcmp($carousel, "ramadan") == 0) {
                $carouselParams = ['title' => 'ramadan hadith selection',
                                   'link' => '/ramadan'];
            }
            if (strcmp($carousel, "dhulhijjah") == 0) {
                $carouselParams = ['title' => 'dhul hijjah hadith selection',
                                   'link' => '/dhulhijjah'];
            }
            if (strcmp($carousel, "ashura") == 0) {
                $carouselParams = ['title' => 'muharram/`ashura hadith selection',
                                   'link' => '/ashura'];
            }

            $this->view->params['carouselParams'] = $carouselParams;
        }

		return $this->render('index', ['collections' => $this->_collections]);
	}

	public function actionMaint() {
		$this->layout = "home";
		return $this->render('maint');
	}

	public function actionAbout() {
        $this->pathCrumbs('About', "/about");
        $this->view->params['_pageType'] = "about";
		return $this->render('about');
    }

    public function actionChangeLog() {
        $this->pathCrumbs('Change Log', "/changelog");
        $this->view->params['_pageType'] = "about";
		return $this->render('changelog');
    }

    public function actionNews() {
        $this->pathCrumbs('News', "/news");
        $this->view->params['_pageType'] = "about";
		return $this->render('news');
    }

    public function actionSearchTips() {
        $this->pathCrumbs('Search Tips', "/searchtips");
        $this->view->params['_pageType'] = "searchtips";
        return $this->render('searchtips');
    }


    public function actionSupport() {
        $this->pathCrumbs('Support Us', "/support");
        $this->view->params['_pageType'] = "about";
		return $this->render('support');
	}

    public function actionDevelopers() {
        $this->pathCrumbs('Developers', "/developers");
        $this->view->params['_pageType'] = "about";
		return $this->render('developers');
    }
    
    public function actionDonate() {
        $this->pathCrumbs('Donate', "/donate");
        $this->view->params['_pageType'] = "about";
		return $this->render('donate');
    }

    public function actionContact() {
        $this->pathCrumbs('Contact', "/contact");
        $this->view->params['_pageType'] = "about";
        $form = new ContactForm();
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            // valid data received in $form
            $success = false;
            $result = $form->sendMessage();
            if ($result == 1) { $success = true; } // Supposed to return an integer but it's returning a bool
            return $this->render('contact', ['model' => $form, 'success' => $success]);
        } else {
            // either the page is initially displayed or there is some validation error
            return $this->render('contact', ['model' => $form]);
        }
    }

	public function actionFlushCache() {
		$flushSecret = Yii::$app->params['flushSecret'] ?? '';
		$requestSecret = Yii::$app->request->headers->get('X-Flush-Secret', '');

		if ($flushSecret === '' || !hash_equals($flushSecret, $requestSecret)) {
			throw new ForbiddenHttpException('Invalid flush cache secret.');
		}

		$success = Yii::$app->cache->flush();
		$this->view->params['success'] = $success;
		echo $this->renderPartial('flushcache');
	}
}
