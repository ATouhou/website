<?php

namespace app\components;

use Yii;
use yii\web\Response;

/**
 * Cloudflare cache-header helper.
 *
 * Single source of truth for which status codes are CDN-cacheable and what
 * headers we emit. 301s are included because they're permanent and stable;
 * 302s and error codes are not.
 */
class CdnHeaders
{
    public static function attach(Response $response, int $duration, string $cacheTag): void
    {
        $response->on(Response::EVENT_BEFORE_SEND, function ($event) use ($duration, $cacheTag) {
            /** @var Response $r */
            $r = $event->sender;
            if ($r->statusCode !== 200 && $r->statusCode !== 301) {
                return;
            }
            $r->headers->set('Cache-Control', 'public, max-age=28800');
            $r->headers->set('Cloudflare-CDN-Cache-Control', 'public, max-age=' . $duration);
            $r->headers->set('Cache-Tag', $cacheTag);
            // Strip the CSRF cookie. The layout's csrfMetaTags() call generates a
            // token on every uncached response, which sets Set-Cookie and causes
            // Cloudflare to bypass the edge cache. Safe here because every action
            // that runs through this helper is a GET-only public page with no
            // POST form (controllers that do POST — e.g. contact, ajaxhadithcount —
            // are in their filter's `except` list).
            // Second arg `false` removes the cookie from the response collection
            // without emitting a deletion Set-Cookie header — the default `true`
            // would still send Set-Cookie (with a past expiry) and defeat the point.
            $r->cookies->remove(Yii::$app->request->csrfParam, false);
        });
    }
}
