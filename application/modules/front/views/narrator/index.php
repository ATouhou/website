<?php
/** @var yii\web\View $this */
/** @var app\modules\front\models\Narrator $narrator */
/** @var array $criticOpinions  [['name' => string, 'opinion' => string], ...] */
/** @var array $teacherRows    [['narrator_id' => int, 'byname' => string], ...] */
/** @var array $studentRows    [['narrator_id' => int, 'byname' => string], ...] */
/** @var array $tarjamaBlocks  [['label' => string, 'html' => string], ...] */
/** @var array $narratedHadith ['clusters' => array, 'rows' => array, 'limit' => int] */

$this->registerJs(<<<'JS'
document.addEventListener("click", (e) => {
  const toggle = e.target.closest(".show-all-btn, .accordion-btn");
  if (toggle) {
    const body = toggle.nextElementSibling;
    const icon = toggle.querySelector(".show-all-icon, .accordion-icon");
    body.classList.toggle("hidden");
    if (icon) icon.textContent = body.classList.contains("hidden") ? "expand_more" : "expand_less";
    return;
  }

  const more = e.target.closest(".read-more-btn");
  if (more) {
    const expander = more.previousElementSibling;
    const fade = expander.querySelector(".tarjama-fade");
    expander.style.maxHeight = "none";
    fade?.remove();
    more.remove();
  }
});
JS, \yii\web\View::POS_END);

// Pre-compute English transliterations for bio fields
$enLineage    = !empty($narrator->lineage)
    ? implode(', ', array_map(
        fn($p) => $narrator::transliterateArabicName(trim($p)),
        explode('،', $narrator->lineage)
      ))
    : '';
$enProfession = !empty($narrator->profession)        ? $narrator::translateProfession($narrator->profession)           : '';
$enSchool     = !empty($narrator->legal_school)
    ? implode(', ', array_map(
        fn($p) => $narrator::transliterateArabicName(trim($p)),
        explode('،', $narrator->legal_school)
      ))
    : '';
$enResidence  = !empty($narrator->residence)         ? $narrator::translateResidence($narrator->residence)              : '';
$enNasab      = !empty($narrator->nasab)
    ? implode(', ', array_map(
        fn($p) => $narrator::transliterateArabicName(trim($p)),
        explode('،', $narrator->nasab)
      ))
    : '';
$enDescriptor = !empty($narrator->descriptor)
    ? $narrator::translateDescriptor($narrator->descriptor)
    : '';
$hasDescriptor = !empty($narrator->descriptor);

// Teachers / students: first 5 visible, remainder hidden
$PREVIEW = 5;
$teacherPreview = array_slice($teacherRows, 0, $PREVIEW);
$teacherRest    = array_slice($teacherRows, $PREVIEW);
$teacherTotal   = count($teacherRows);
$studentPreview = array_slice($studentRows, 0, $PREVIEW);
$studentRest    = array_slice($studentRows, $PREVIEW);
$studentTotal   = count($studentRows);
?>

<div class="narrator-page">
<div class="container">

<?= $this->render('_hero', ['narrator' => $narrator]) ?>

<?php
$hasLineage   = !empty($narrator->lineage);
$hasProfession = !empty($narrator->profession);
$hasSchool    = !empty($narrator->legal_school);
$hasResidence = !empty($narrator->residence);
$hasNasab     = !empty($narrator->nasab);
$hasMeta      = $hasProfession || $hasSchool || $hasResidence || $hasDescriptor;
$hasPills   = $narrator->in_bukhari || $narrator->in_muslim;
$showBio    = $hasLineage || $hasMeta || $hasPills;
?>

<?php if ($showBio): ?>
<!-- ═══════════════════════════════════════════════════ BIO BLOCK -->
<section class="bio-block mb-section">
<div class="bio-rows">

  <!-- Lineage row -->
  <?php if ($hasLineage): ?>
  <div class="bio-row">
    <div>
      <span class="label label--m3">Narrator Lineage</span>
      <p class="lineage"><?= htmlspecialchars($enLineage) ?></p>
    </div>
    <div dir="rtl">
      <span class="label arabic-label label--m3">الاسم الكامل</span>
      <p class="lineage arabic"><?= htmlspecialchars($narrator->lineage) ?></p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Meta row -->
  <?php if ($hasMeta): ?>
  <div class="bio-row bio-row--sb">
    <div class="meta-grid">
      <?php if ($hasProfession): ?>
      <div>
        <span class="label label--m1">Profession</span>
        <p class="field"><?= htmlspecialchars($enProfession) ?></p>
      </div>
      <?php endif; ?>
      <?php if ($hasSchool): ?>
      <div>
        <span class="label label--m1">School</span>
        <p class="field"><?= htmlspecialchars($enSchool) ?></p>
      </div>
      <?php endif; ?>
      <?php if ($hasResidence): ?>
      <div>
        <span class="label label--m1">Cities/Regions</span>
        <p class="field"><?= htmlspecialchars($enResidence) ?></p>
      </div>
      <?php endif; ?>
      <?php if ($hasDescriptor): ?>
      <div>
        <span class="label label--m1">Description</span>
        <p class="field"><?= htmlspecialchars($enDescriptor) ?></p>
      </div>
      <?php endif; ?>
    </div>
    <div class="meta-grid" dir="rtl">
      <?php if ($hasProfession): ?>
      <div>
        <span class="label arabic-label label--m1">الصنعة</span>
        <p class="field arabic"><?= htmlspecialchars($narrator->profession) ?></p>
      </div>
      <?php endif; ?>
      <?php if ($hasSchool): ?>
      <div>
        <span class="label arabic-label label--m1">المذهب</span>
        <p class="field arabic"><?= htmlspecialchars($narrator->legal_school) ?></p>
      </div>
      <?php endif; ?>
      <?php if ($hasResidence): ?>
      <div>
        <span class="label arabic-label label--m1">بلد الإقامة</span>
        <p class="field arabic"><?= htmlspecialchars($narrator->residence) ?></p>
      </div>
      <?php endif; ?>
      <?php if ($hasDescriptor): ?>
      <div>
        <span class="label arabic-label label--m1">الوصف</span>
        <p class="field arabic"><?= htmlspecialchars($narrator->descriptor) ?></p>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Nasab row -->
  <?php if ($hasNasab): ?>
  <div class="bio-row bio-row--sb">
    <div>
      <span class="label label--m1">Affiliations</span>
      <p class="field"><?= htmlspecialchars($enNasab) ?></p>
    </div>
    <div dir="rtl">
      <span class="label arabic-label label--m1">النسب والنسبة</span>
      <p class="field arabic"><?= htmlspecialchars(implode('، ', array_map('trim', explode('،', $narrator->nasab)))) ?></p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Pills row -->
  <?php if ($hasPills): ?>
  <div class="bio-row bio-row--sb">
    <div class="pills">
      <?php if ($narrator->in_bukhari): ?>
      <span class="pill-outline">Narrates in al-Bukhari</span>
      <?php endif; ?>
      <?php if ($narrator->in_muslim): ?>
      <span class="pill-outline">Narrates in Muslim</span>
      <?php endif; ?>
    </div>
    <div class="pills" dir="rtl">
      <?php if ($narrator->in_bukhari): ?>
      <span class="pill-outline arabic">أخرج له البخاري</span>
      <?php endif; ?>
      <?php if ($narrator->in_muslim): ?>
      <span class="pill-outline arabic">أخرج له مسلم</span>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

</div>
</section>
<!-- ════════════════════════════════════════════════════════════ -->
<?php endif; ?>

<?php if (!empty($criticOpinions)): ?>
<!-- ════════════════════════════════════════ JARH & TA'DIL -->
<?php
$OPINION_PREVIEW = 5;
$opinionPreview  = array_slice($criticOpinions, 0, $OPINION_PREVIEW);
$opinionRest     = array_slice($criticOpinions, $OPINION_PREVIEW);
$opinionTotal    = count($criticOpinions);
?>
<section class="mb-section">
  <div class="section-head">
    <h3 class="section-title">Critical Appraisals</h3>
    <h3 class="section-title section-title--ar arabic" dir="rtl">الجرح والتعديل</h3>
  </div>
  <div class="stack-4" dir="rtl">
    <?php foreach ($opinionPreview as $opinion): ?>
    <div class="verdict verdict--ar">
      <span class="arabic verdict-scholar"><?= htmlspecialchars($opinion['name']) ?></span>
      <p class="critic-quote arabic"><?= htmlspecialchars($opinion['opinion']) ?></p>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if (!empty($opinionRest)): ?>
  <div dir="rtl">
    <button class="show-all-btn">
      <span class="mso mso-base show-all-icon">expand_more</span>
      عرض جميع الآراء (<?= $opinionTotal ?>)
    </button>
    <div class="show-all-body hidden">
      <div class="stack-4" dir="rtl">
        <?php foreach ($opinionRest as $opinion): ?>
        <div class="verdict verdict--ar">
          <span class="arabic verdict-scholar"><?= htmlspecialchars($opinion['name']) ?></span>
          <p class="critic-quote arabic"><?= htmlspecialchars($opinion['opinion']) ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</section>
<!-- ════════════════════════════════════════════════════════════ -->
<?php endif; ?>

<?php if ($teacherTotal > 0 || $studentTotal > 0): ?>
<!-- ══════════════════════════════════ TEACHERS & STUDENTS -->
<div class="stack-8 mb-section">

  <?php if ($teacherTotal > 0): ?>
  <section class="panel panel--teachers">
    <div class="panel-head">
      <h4 class="panel-label">
        <span class="mso mso-sm">history_edu</span> Teachers
      </h4>
      <span class="panel-label-ar arabic">المشايخ</span>
    </div>
    <ul>
      <?php foreach ($teacherPreview as $row): ?>
      <li class="row">
        <a href="/narrator/<?= (int)$row['narrator_id'] ?>" class="name-en"><?= htmlspecialchars($narrator::transliterateArabicName($row['name'])) ?></a>
        <a href="/narrator/<?= (int)$row['narrator_id'] ?>" class="name-ar arabic">
          <?= htmlspecialchars($row['name']) ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php if (!empty($teacherRest)): ?>
    <button class="show-all-btn">
      <span class="mso mso-base show-all-icon">expand_more</span>
      Show all teachers (<?= $teacherTotal ?>)
    </button>
    <div class="show-all-body hidden">
      <ul>
        <?php foreach ($teacherRest as $row): ?>
        <li class="row">
          <a href="/narrator/<?= (int)$row['narrator_id'] ?>" class="name-en"><?= htmlspecialchars($narrator::transliterateArabicName($row['name'])) ?></a>
          <a href="/narrator/<?= (int)$row['narrator_id'] ?>" class="name-ar arabic">
            <?= htmlspecialchars($row['name']) ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($studentTotal > 0): ?>
  <section class="panel panel--students">
    <div class="panel-head">
      <h4 class="panel-label">
        <span class="mso mso-sm">group</span> Students
      </h4>
      <span class="panel-label-ar arabic">التلاميذ</span>
    </div>
    <ul>
      <?php foreach ($studentPreview as $row): ?>
      <li class="row">
        <a href="/narrator/<?= (int)$row['narrator_id'] ?>" class="name-en"><?= htmlspecialchars($narrator::transliterateArabicName($row['name'])) ?></a>
        <a href="/narrator/<?= (int)$row['narrator_id'] ?>" class="name-ar arabic">
          <?= htmlspecialchars($row['name']) ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php if (!empty($studentRest)): ?>
    <button class="show-all-btn">
      <span class="mso mso-base show-all-icon">expand_more</span>
      Show all students (<?= $studentTotal ?>)
    </button>
    <div class="show-all-body hidden">
      <ul>
        <?php foreach ($studentRest as $row): ?>
        <li class="row">
          <a href="/narrator/<?= (int)$row['narrator_id'] ?>" class="name-en"><?= htmlspecialchars($narrator::transliterateArabicName($row['name'])) ?></a>
          <a href="/narrator/<?= (int)$row['narrator_id'] ?>" class="name-ar arabic">
            <?= htmlspecialchars($row['name']) ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

</div>
<!-- ════════════════════════════════════════════════════════════ -->
<?php endif; ?>

<?= $this->render('_hadith_preview', [
    'narrator'       => $narrator,
    'narratedHadith' => $narratedHadith,
]) ?>

<?php if (!empty($tarjamaBlocks)): ?>
<!-- ══════════════════════════════════════ TARJAMA ACCORDIONS -->
<section class="stack-4">
  <div class="mt-big">
    <div class="section-head">
      <h3 class="section-title">Rijal Texts</h3>
      <h3 class="section-title section-title--ar arabic" dir="rtl">الأقوال الرجالية</h3>
    </div>
    <?php foreach ($tarjamaBlocks as $block): ?>
    <div class="accordion">
      <button class="accordion-btn">
        <span class="accordion-title arabic" dir="rtl"><?= htmlspecialchars($block['label']) ?></span>
        <span class="mso accordion-icon">expand_more</span>
      </button>
      <div class="accordion-body hidden" dir="rtl">
        <div class="tarjama-expander">
          <?= $block['html'] ?>
          <div class="tarjama-fade"></div>
        </div>
        <button class="read-more-btn">
          <span class="mso mso-base">expand_more</span>اقرأ المزيد
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<!-- ════════════════════════════════════════════════════════════ -->
<?php endif; ?>

</div><!-- .container -->
</div><!-- .narrator-page -->
