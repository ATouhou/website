<?php
/** @var app\modules\front\models\Narrator $narrator */
/** @var array $narratedHadith */

$rows = $narratedHadith['rows'] ?? [];
if (empty($rows)) {
    return;
}

$toArNums = fn(string $s): string => str_replace(
    ['0','1','2','3','4','5','6','7','8','9'],
    ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'],
    $s
);
$narrationCount = is_numeric($narrator->narration_count ?? null) ? (int)$narrator->narration_count : 0;
$hasNarrationCount = $narrationCount > 0;
$narrationCountAr = $toArNums(str_replace(',', '٬', number_format($narrationCount))) . ' حديثًا';
?>

<section class="mb-section">
  <div class="section-head">
    <h3 class="section-title"><?= $hasNarrationCount ? htmlspecialchars(number_format($narrationCount) . ' Hadith Narrated') : 'Hadith Narrated' ?></h3>
    <h3 class="section-title section-title--ar arabic" dir="rtl"><?= $hasNarrationCount ? htmlspecialchars($narrationCountAr . ' مرويًا') : 'الأحاديث المروية' ?></h3>
  </div>

  <ul class="narrated-hadith-list">
    <?php foreach ($rows as $row): ?>
    <li class="narrated-hadith-row">
      <a class="narrated-hadith-link" href="<?= htmlspecialchars($row['permalink']) ?>">
        <span class="narrated-hadith-ref"><?= htmlspecialchars($row['reference']) ?></span>
        <span class="narrated-hadith-snippet arabic" dir="rtl"><?= htmlspecialchars($row['tarafSnippet'] ?? '') ?></span>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>
  <div class="narrated-hadith-more">
    <a class="narrated-hadith-more-link" href="/narrator/<?= (int)$narrator->narrator_id ?>/hadith">View all narrations</a>
    <a class="narrated-hadith-more-link arabic" dir="rtl" href="/narrator/<?= (int)$narrator->narrator_id ?>/hadith">عرض جميع الأحاديث المروية</a>
  </div>
</section>
