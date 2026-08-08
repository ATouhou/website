<?php

namespace app\components;

use Yii;

/**
 * Caches the rendered page in Yii's `cache` component (Memcached in prod) AND
 * emits Cache-Control + Cache-Tag so Cloudflare caches at the edge.
 *
 * All cacheable routes share the same tag so a single Cloudflare "Purge by Tag"
 * API call invalidates the whole site. On non-Enterprise plans Cache-Tag is
 * ignored and `purge_everything` is the equivalent.
 */
class CdnOriginAndEdgeCache extends \yii\filters\PageCache
{
    public $cacheTag = 'sunnah';

    public function beforeAction($action)
    {
        CdnHeaders::attach(Yii::$app->response, (int)$this->duration, $this->cacheTag);
        return parent::beforeAction($action);
    }
}
