<?php
/** @var array $hadithRows */
?>

<ul class="narrator-cluster-link-list">
  <?php foreach ($hadithRows as $row): ?>
  <li>
    <a class="narrator-cluster-link" href="<?= htmlspecialchars($row['permalink']) ?>">
      <?= htmlspecialchars($row['reference']) ?>
    </a>
  </li>
  <?php endforeach; ?>
</ul>
