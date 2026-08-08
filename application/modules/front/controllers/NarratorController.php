<?php

namespace app\modules\front\controllers;

use app\controllers\SController;
use app\modules\front\models\Narrator;
use Yii;
use yii\web\NotFoundHttpException;

class NarratorController extends SController
{
    public function behaviors()
    {
        return [
            [
                'class'      => 'app\components\CdnOriginAndEdgeCache',
                'except'     => ['hadith-list', 'hadith-cluster'],
                'duration'   => Yii::$app->params['cacheTTL'],
                'variations' => [
                    Yii::$app->request->pathInfo,
                    Yii::$app->request->get('nid'),
                    Narrator::normalizeCollectionParam(Yii::$app->request->get('collections')),
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    public function actionIndex($nid)
    {
        $narrator = $this->loadNarrator($nid);
        $this->setNarratorPageMeta($narrator, $nid);

        $summaryMap     = Narrator::getSummaryMap();
        $criticOpinions = $narrator->getCriticOpinions();
        $tarjamaBlocks  = $narrator->getTarjamaBlocks();
        $teacherRows    = $this->resolveRows($narrator->getTeacherIds(), $summaryMap);
        $studentRows    = $this->resolveRows($narrator->getStudentIds(), $summaryMap);
        $narratedHadith = $narrator->getNarratedHadithPreview($this->util);

        return $this->render('index', [
            'narrator'       => $narrator,
            'criticOpinions' => $criticOpinions,
            'teacherRows'    => $teacherRows,
            'studentRows'    => $studentRows,
            'tarjamaBlocks'  => $tarjamaBlocks,
            'narratedHadith' => $narratedHadith,
        ]);
    }

    public function actionList()
    {
        $this->view->params['_pageType'] = 'narrator';
        $this->pathCrumbs('Narrators', '/narrators');

        return $this->render('list');
    }

    public function actionHadith($nid)
    {
        $narrator = $this->loadNarrator($nid);
        $this->setNarratorPageMeta($narrator, $nid, 'Hadith Narrated');

        $availableCollections = $narrator->getNarratedHadithAvailableCollections();
        $selectedCollectionIds = $this->resolveNarratedHadithCollectionIds(
            Yii::$app->request->get('collections'),
            $availableCollections
        );
        $clusterLimit = $this->getNarratedHadithClusterPageSize();
        $counts = $narrator->getNarratedHadithCounts($selectedCollectionIds);
        $clusters = $narrator->getNarratedHadithClusters($this->util, $selectedCollectionIds, 7, $clusterLimit);

        return $this->render('hadith', [
            'narrator'              => $narrator,
            'availableCollections'  => $availableCollections,
            'selectedCollectionIds' => $selectedCollectionIds,
            'counts'                => $counts,
            'clusters'              => $clusters,
            'linksPerCluster'       => 7,
            'clusterLimit'          => $clusterLimit,
            'clusterOffset'         => 0,
        ]);
    }

    public function actionHadithList($nid)
    {
        $narrator = $this->loadNarrator($nid);
        $availableCollections = $narrator->getNarratedHadithAvailableCollections();
        $selectedCollectionIds = $narrator->normalizeNarratedHadithCollectionIds(
            Yii::$app->request->get('collections'),
            $availableCollections
        );
        $clusterLimit = $this->getNarratedHadithClusterPageSize();
        $clusterOffset = max(0, (int)Yii::$app->request->get('offset', 0));
        $counts = $narrator->getNarratedHadithCounts($selectedCollectionIds);
        $clusters = $narrator->getNarratedHadithClusters($this->util, $selectedCollectionIds, 7, $clusterLimit, $clusterOffset);

        if ($clusterOffset > 0) {
            return $this->renderPartial('_hadith_cluster_page', [
                'narrator'           => $narrator,
                'clusters'           => $clusters,
                'linksPerCluster'    => 7,
                'clusterLimit'       => $clusterLimit,
                'clusterOffset'      => $clusterOffset,
                'totalClusterCount'  => (int)($counts['matchedCount'] ?? 0),
                'showEmptyMessage'   => false,
            ]);
        }

        return $this->renderPartial('_hadith_results', [
            'narrator'              => $narrator,
            'selectedCollectionIds' => $selectedCollectionIds,
            'counts'                => $counts,
            'clusters'              => $clusters,
            'linksPerCluster'       => 7,
            'clusterLimit'          => $clusterLimit,
            'clusterOffset'         => 0,
        ]);
    }

    public function actionHadithCluster($nid, $clusterId)
    {
        $narrator = $this->loadNarrator($nid);
        $availableCollections = $narrator->getNarratedHadithAvailableCollections();
        $selectedCollectionIds = $narrator->normalizeNarratedHadithCollectionIds(
            Yii::$app->request->get('collections'),
            $availableCollections
        );
        $cluster = $narrator->getNarratedHadithClusterLinks($this->util, $clusterId, $selectedCollectionIds);

        return $this->renderPartial('_hadith_cluster_links', [
            'hadithRows' => $cluster['hadithRows'],
        ]);
    }

    private function loadNarrator($nid)
    {
        $narrator = Narrator::findByNarratorId($nid);
        if ($narrator === null) {
            throw new NotFoundHttpException('Narrator not found.');
        }
        return $narrator;
    }

    private function getNarratedHadithClusterPageSize()
    {
        return 30;
    }

    private function resolveNarratedHadithCollectionIds($collectionParam, array $availableCollections)
    {
        if ($collectionParam === null || $collectionParam === '' || $collectionParam === 'all') {
            return [];
        }

        $parts = is_array($collectionParam)
            ? $collectionParam
            : preg_split('/\s*,\s*/', (string)$collectionParam, -1, PREG_SPLIT_NO_EMPTY);

        $idsBySlug = [];
        $allowedIds = [];
        foreach ($availableCollections as $collection) {
            $collectionId = (int)$collection['collectionID'];
            $allowedIds[$collectionId] = true;
            $idsBySlug[strtolower($collection['name'])] = $collectionId;
        }

        $selected = [];
        foreach ($parts as $part) {
            $token = strtolower(trim((string)$part));
            if ($token === '' || $token === 'all') {
                continue;
            }

            $id = ctype_digit($token) ? (int)$token : ($idsBySlug[$token] ?? 0);
            if ($id > 0 && isset($allowedIds[$id])) {
                $selected[$id] = true;
            }
        }

        $selected = array_keys($selected);
        sort($selected, SORT_NUMERIC);
        return $selected;
    }

    private function setNarratorPageMeta(Narrator $narrator, $nid, $section = null)
    {
        $this->view->params['_pageType'] = 'narrator';
        $arabicName = $narrator->name ?: $narrator->lineage;
        $enName     = Narrator::transliterateArabicName($arabicName);
        if ($section !== null) {
            $this->pathCrumbs($section, '');
        }
        $this->pathCrumbs($enName . ' — ' . $arabicName, '/narrator/' . (int)$nid);
        $this->pathCrumbs('Narrators', '/narrators');

        $ogDesc = trim(
            ($narrator->name ?? '')
            . ($narrator->reliability_label ? ' — ' . $narrator->reliability_label : '')
        );
        if ($ogDesc !== '') {
            $this->view->params['_ogDesc'] = $ogDesc;
        }
    }

    /**
     * Resolves an array of narrator_ids against the summary map, returning only
     * rows found in the map. Orphan ids (not in the map) are silently skipped.
     *
     * @param  int[]  $ids
     * @param  array  $summaryMap  narrator_id => byname
     * @return array  [['narrator_id' => int, 'byname' => string], ...]
     */
    private function resolveRows(array $ids, array $summaryMap)
    {
        $rows = [];
        foreach ($ids as $id) {
            if (isset($summaryMap[$id])) {
                $rows[] = ['narrator_id' => $id, 'name' => $summaryMap[$id]];
            }
        }
        return $rows;
    }
}
