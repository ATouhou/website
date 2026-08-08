<?php
/** @var app\modules\front\models\Narrator $narrator */
/** @var array $selectedCollectionIds */
/** @var array $counts */
/** @var array $clusters */
/** @var int $linksPerCluster */
/** @var int $clusterLimit */
/** @var int $clusterOffset */

$totalNarrated = (int)($counts['totalNarrated'] ?? 0);
$matchedCount = (int)($counts['matchedCount'] ?? 0);
$allMatchedCount = (int)($counts['allMatchedCount'] ?? $matchedCount);
$isFiltered = !empty($counts['isFiltered']);
?>

<div class="narrator-hadith-counts">
  <?php if ($totalNarrated > 0): ?>
  <div class="narrator-count-pill">
    <span class="narrator-count-value"><?= htmlspecialchars(number_format($totalNarrated)) ?></span>
    <span class="narrator-count-label">Total narrated</span>
  </div>
  <?php endif; ?>
  <div class="narrator-count-pill narrator-count-pill--matched">
    <span class="narrator-count-value"><?= htmlspecialchars(number_format($matchedCount)) ?></span>
    <span class="narrator-count-label"><?= $isFiltered ? 'in selected collections' : 'on Sunnah.com' ?></span>
    <?php if ($isFiltered): ?>
    <span class="narrator-count-note">of <?= htmlspecialchars(number_format($allMatchedCount)) ?> on sunnah.com</span>
    <?php endif; ?>
  </div>
</div>

<?= $this->render('_hadith_cluster_page', [
    'narrator'           => $narrator,
    'clusters'           => $clusters,
    'linksPerCluster'    => $linksPerCluster,
    'clusterLimit'       => $clusterLimit,
    'clusterOffset'      => $clusterOffset,
    'totalClusterCount'  => $matchedCount,
    'showEmptyMessage'   => true,
]) ?>
