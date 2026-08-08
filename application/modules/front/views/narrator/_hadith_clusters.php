<?php
/** @var app\modules\front\models\Narrator $narrator */
/** @var array $clusters */
/** @var int $linksPerCluster */
/** @var bool $showEmptyMessage */
?>

<?php if (empty($clusters)): ?>
<?php if (!isset($showEmptyMessage) || $showEmptyMessage): ?>
<p class="narrator-hadith-empty">No matched narrations found for this filter.</p>
<?php endif; ?>
<?php return; ?>
<?php endif; ?>

<div class="narrator-hadith-clusters">
  <?php foreach ($clusters as $cluster): ?>
  <article class="narrator-hadith-cluster" data-cluster-id="<?= (int)$cluster['clusterID'] ?>">
    <h4 class="narrator-cluster-title arabic" dir="rtl" title="<?= htmlspecialchars($cluster['taraf']) ?>">
      <?= htmlspecialchars($cluster['tarafSnippet']) ?>
    </h4>
    <div class="narrator-cluster-links">
      <?= $this->render('_hadith_cluster_links', [
          'hadithRows' => $cluster['hadithRows'],
      ]) ?>
    </div>
    <?php if (!empty($cluster['hasMore'])): ?>
    <button class="narrator-cluster-expand" type="button" data-cluster-id="<?= (int)$cluster['clusterID'] ?>">
      Show all <?= (int)$cluster['totalHadithCount'] ?> narrations
    </button>
    <?php endif; ?>
  </article>
  <?php endforeach; ?>
</div>
