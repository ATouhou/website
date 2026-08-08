<?php
/** @var app\modules\front\models\Narrator $narrator */

$toArNums = fn(string $s): string => str_replace(
    ['0','1','2','3','4','5','6','7','8','9'],
    ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'],
    $s
);

$tabaqatAr = [
    1 => 'الأولى', 2 => 'الثانية', 3 => 'الثالثة', 4 => 'الرابعة',
    5 => 'الخامسة', 6 => 'السادسة', 7 => 'السابعة', 8 => 'الثامنة',
    9 => 'التاسعة', 10 => 'العاشرة', 11 => 'الحادية عشرة', 12 => 'الثانية عشرة',
];
$tabaqatEn = [
    1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th', 5 => '5th', 6 => '6th',
    7 => '7th', 8 => '8th', 9 => '9th', 10 => '10th', 11 => '11th', 12 => '12th',
];

$hasBirth = !empty($narrator->date_of_birth);
$hasDeath = !empty($narrator->death_year);
$dateEnStr = '';
$arDateStr = '';
if ($hasBirth || $hasDeath) {
    if ($hasBirth && $hasDeath) {
        $dateEnStr = $narrator->date_of_birth . ' – ' . $narrator->death_year . ' AH';
        $arDateStr = $toArNums($narrator->date_of_birth . ' – ' . $narrator->death_year) . ' هـ';
    } elseif ($hasBirth) {
        $dateEnStr = $narrator->date_of_birth . ' AH';
        $arDateStr = $toArNums($narrator->date_of_birth) . ' هـ';
    } else {
        $dateEnStr = 'd. ' . $narrator->death_year . ' AH';
        $arDateStr = 'ت. ' . $toArNums($narrator->death_year) . ' هـ';
    }
}

$tabaka = (int)$narrator->generation;
$hasTabaka = $tabaka > 0;
$tabAr = $tabaqatAr[$tabaka] ?? (string)$tabaka;
$tabEn = $tabaqatEn[$tabaka] ?? $tabaka . 'th';

$enTitle = $narrator::transliterateArabicName($narrator->name ?: $narrator->lineage);
$enKunya = !empty($narrator->kunya)
    ? implode(', ', array_map(fn($p) => $narrator::transliterateArabicName(trim($p)), explode('،', $narrator->kunya)))
    : '';
$enGrade = !empty($narrator->reliability_label) ? $narrator::translateJarhTadil($narrator->reliability_label) : '';
$gradeTier = ($narrator->reliability_grade !== null)
    ? $narrator::getReliabilityGradeTier((int)$narrator->reliability_grade)
    : 'neutral';
$enAltName = !empty($narrator->alt_name) ? $narrator::transliterateArabicName($narrator->alt_name) : '';
$enEpithet = !empty($narrator->epithet)
    ? implode(', ', array_map(fn($p) => $narrator::transliterateArabicName(trim($p)), explode('،', $narrator->epithet)))
    : '';
$hasAltName = !empty($narrator->alt_name);
$hasEpithet = !empty($narrator->epithet);
$hasIkhtilat = !empty($narrator->ikhtilat);
$hasTadlis = !empty($narrator->tadlis);
?>

<section class="hero">
  <div class="hero-row">
    <div>
      <h1 class="hero-title"><?= htmlspecialchars($enTitle) ?></h1>
      <?php if ($hasAltName): ?>
      <p class="hero-alt-name">(<?= htmlspecialchars($enAltName) ?>)</p>
      <?php endif; ?>
    </div>
    <div dir="rtl">
      <h1 class="hero-title arabic"><?= htmlspecialchars($narrator->name ?: $narrator->lineage) ?></h1>
      <?php if ($hasAltName): ?>
      <p class="hero-alt-name arabic">(<?= htmlspecialchars($narrator->alt_name) ?>)</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="hero-row">
    <div class="badges">
      <?php if (!empty($narrator->reliability_label)): ?>
      <div class="pill-grade pill-grade--<?= $gradeTier ?>">
        <?php if ($gradeTier === 'grade-1' || $gradeTier === 'grade-2'): ?><span class="mso mso-sm mso-filled">verified</span><?php endif; ?>
        <span class="pill-text"><?= htmlspecialchars($enGrade) ?></span>
      </div>
      <?php endif; ?>
      <?php if ($hasIkhtilat): ?><span class="pill-flag pill-flag--warning">Ikhtilat</span><?php endif; ?>
      <?php if ($hasTadlis): ?><span class="pill-flag pill-flag--warning">Tadlis</span><?php endif; ?>
    </div>
    <div class="badges" dir="rtl">
      <?php if (!empty($narrator->reliability_label)): ?>
      <div class="pill-grade pill-grade--<?= $gradeTier ?>">
        <?php if ($gradeTier === 'grade-1' || $gradeTier === 'grade-2'): ?><span class="mso mso-sm mso-filled">verified</span>&nbsp;&nbsp;<?php endif; ?>
        <span class="arabic"><?= htmlspecialchars($narrator->reliability_label) ?></span>
      </div>
      <?php endif; ?>
      <?php if ($hasIkhtilat): ?><span class="pill-flag pill-flag--warning arabic">مختلط</span><?php endif; ?>
      <?php if ($hasTadlis): ?><span class="pill-flag pill-flag--warning arabic">مدلّس</span><?php endif; ?>
    </div>
  </div>

  <div class="hero-row">
    <div class="fields">
      <?php if (!empty($narrator->kunya)): ?>
      <div>
        <span class="label">Kunya</span>
        <span class="field"><?= htmlspecialchars($enKunya) ?></span>
      </div>
      <?php endif; ?>
      <?php if ($hasTabaka || ($hasBirth || $hasDeath) || !empty($narrator->gender)): ?>
      <div>
        <?php if ($hasTabaka): ?><span class="label">Generation</span><?php endif; ?>
        <div class="field-line">
          <?php if ($hasTabaka): ?><span class="field"><?= htmlspecialchars($tabEn) ?></span><?php endif; ?>
          <?php if ($hasBirth || $hasDeath): ?><span class="pill-secondary"><?= htmlspecialchars($dateEnStr) ?></span><?php endif; ?>
          <?php if (!empty($narrator->gender)): ?><span class="mso mso-md"><?= $narrator->gender === 'M' ? 'male' : 'female' ?></span><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($hasEpithet): ?>
      <div>
        <span class="label">Title / Byname</span>
        <span class="field"><?= htmlspecialchars($enEpithet) ?></span>
      </div>
      <?php endif; ?>
    </div>
    <div dir="rtl">
      <div class="fields">
        <?php if (!empty($narrator->kunya)): ?>
        <div>
          <span class="label arabic-label">الكنية</span>
          <span class="field arabic"><?= htmlspecialchars($narrator->kunya) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($hasTabaka || ($hasBirth || $hasDeath) || !empty($narrator->gender)): ?>
        <div>
          <?php if ($hasTabaka): ?><span class="label arabic-label">الطبقة</span><?php endif; ?>
          <div class="field-line">
            <?php if ($hasTabaka): ?><span class="field arabic"><?= htmlspecialchars($tabAr) ?></span><?php endif; ?>
            <?php if ($hasBirth || $hasDeath): ?><span class="pill-secondary arabic"><?= htmlspecialchars($arDateStr) ?></span><?php endif; ?>
            <?php if (!empty($narrator->gender)): ?><span class="mso mso-md"><?= $narrator->gender === 'M' ? 'male' : 'female' ?></span><?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
        <?php if ($hasEpithet): ?>
        <div>
          <span class="label arabic-label">اللقب</span>
          <span class="field arabic"><?= htmlspecialchars($narrator->epithet) ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
