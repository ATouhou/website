<?php

namespace app\components;

use Yii;
use yii\base\ActionFilter;

/**
 * Emits Cache-Control + Cache-Tag for Cloudflare without any server-side
 * page caching. Use this when origin caching is undesirable (e.g. search,
 * where Memcached fragmentation on free-form queries hurts more than it
 * helps and Cloudflare's URL-based key is sufficient).
 */
class CdnEdgeCache extends ActionFilter
{
    public $duration;
    public $cacheTag = 'sunnah';

    public function beforeAction($action)
    {
        CdnHeaders::attach(Yii::$app->response, (int)$this->duration, $this->cacheTag);
        return parent::beforeAction($action);
    }
}
