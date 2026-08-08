<?php

return [
    'adminEmail' => $parameters['adminEmail'],
    'classyCampaignId' => $parameters['classy_campaign_id'],
    'cacheTTL' => (array_key_exists('cacheTTL', $parameters) ? $parameters['cacheTTL'] : 3600),
    'searchCacheTTL' => (array_key_exists('searchCacheTTL', $parameters) ? $parameters['searchCacheTTL'] : 3600),
    'pageSize' => '50',
];
