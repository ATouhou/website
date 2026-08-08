<?php
/** @var app\modules\front\models\Narrator $narrator */
/** @var array $clusters */
/** @var int $linksPerCluster */
/** @var int $clusterLimit */
/** @var int $clusterOffset */
/** @var int $totalClusterCount */
/** @var bool $showEmptyMessage */

$clusterLimit = max(1, (int)$clusterLimit);
$clusterOffset = max(0, (int)$clusterOffset);
$totalClusterCount = max(0, (int)$totalClusterCount);
$nextOffset = $clusterOffset + $clusterLimit;
$hasMoreClusters = !empty($clusters) && $nextOffset < $totalClusterCount;
?>

<?= $this->render('_hadith_clusters', [
    'narrator'          => $narrator,
    'clusters'          => $clusters,
    'linksPerCluster'   => $linksPerCluster,
    'showEmptyMessage'  => $showEmptyMessage,
]) ?>

<?php if ($hasMoreClusters): ?>
<button class="narrator-clusters-load-more" type="button" data-offset="<?= (int)$nextOffset ?>">
  <span>Load more narrations</span>
  <span class="arabic" dir="rtl">تحميل المزيد من الأحاديث</span>
</button>
<?php endif; ?>
