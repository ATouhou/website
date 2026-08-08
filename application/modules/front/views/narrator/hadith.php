<?php
/** @var yii\web\View $this */
/** @var app\modules\front\models\Narrator $narrator */
/** @var array $availableCollections */
/** @var array $selectedCollectionIds */
/** @var array $counts */
/** @var array $clusters */
/** @var int $linksPerCluster */
/** @var int $clusterLimit */
/** @var int $clusterOffset */

$selectedSet = array_flip($selectedCollectionIds);
$allSelected = empty($selectedCollectionIds);

$this->registerJs(<<<'JS'
(() => {
  const page = document.querySelector("[data-narrator-hadith-page]");
  if (!page) return;

  const narratorId = page.dataset.narratorId;
  const results = page.querySelector("#narrator-hadith-results");
  const filterButtons = [...page.querySelectorAll(".narrator-filter-pill")];

  const selectedIds = () => filterButtons
    .filter((btn) => btn.dataset.collectionId && btn.getAttribute("aria-pressed") === "true")
    .map((btn) => btn.dataset.collectionId);

  const slugsForIds = (ids) => {
    const selected = new Set(ids);
    return filterButtons
      .filter((btn) => btn.dataset.collectionId && selected.has(btn.dataset.collectionId))
      .map((btn) => btn.dataset.collectionSlug || btn.dataset.collectionId);
  };

  const idsForTokens = (tokens) => {
    const normalized = tokens.map((token) => token.toLowerCase());
    const ids = [];
    const seen = new Set();
    filterButtons.forEach((btn) => {
      if (!btn.dataset.collectionId) return;
      const matched = normalized.includes(btn.dataset.collectionId) || normalized.includes(btn.dataset.collectionSlug);
      if (matched && !seen.has(btn.dataset.collectionId)) {
        seen.add(btn.dataset.collectionId);
        ids.push(btn.dataset.collectionId);
      }
    });
    return ids;
  };

  const urlCollectionQuery = (ids) => slugsForIds(ids).map(encodeURIComponent).join(",");
  const ajaxQueryFor = (ids, offset = 0) => {
    const params = new URLSearchParams();
    if (ids.length) params.set("collections", ids.join(","));
    if (offset > 0) params.set("offset", offset);
    const query = params.toString();
    return query ? "?" + query : "";
  };

  const setFilters = (ids) => {
    const known = new Set(filterButtons.filter((btn) => btn.dataset.collectionId).map((btn) => btn.dataset.collectionId));
    const set = new Set(ids.filter((id) => known.has(id)));
    filterButtons.forEach((btn) => {
      const pressed = btn.dataset.filterAll ? set.size === 0 : set.has(btn.dataset.collectionId);
      btn.setAttribute("aria-pressed", pressed ? "true" : "false");
    });
  };

  const updateUrl = (ids) => {
    const url = new URL(window.location.href);
    url.searchParams.delete("collections");
    const search = url.searchParams.toString();
    const collections = urlCollectionQuery(ids);
    const nextSearch = [
      search,
      collections ? "collections=" + collections : ""
    ].filter(Boolean).join("&");
    history.pushState({ collections: ids }, "", url.pathname + (nextSearch ? "?" + nextSearch : "") + url.hash);
  };

  const loadResults = (ids, push) => {
    page.classList.add("narrator-hadith-loading");
    fetch(`/narrator/${narratorId}/hadith/list${ajaxQueryFor(ids)}`, {
      headers: { "X-Requested-With": "XMLHttpRequest" }
    })
      .then((response) => response.text())
      .then((html) => {
        results.innerHTML = html;
        if (push) updateUrl(ids);
      })
      .catch(() => {
        results.innerHTML = '<p class="narrator-hadith-error">Unable to load narrations.</p>';
      })
      .finally(() => page.classList.remove("narrator-hadith-loading"));
  };

  page.addEventListener("click", (event) => {
    const filter = event.target.closest(".narrator-filter-pill");
    if (filter) {
      event.preventDefault();
      if (filter.dataset.filterAll) {
        setFilters([]);
        loadResults([], true);
        return;
      }

      if (!selectedIds().length) {
        setFilters([filter.dataset.collectionId]);
      } else {
        filter.setAttribute("aria-pressed", filter.getAttribute("aria-pressed") === "true" ? "false" : "true");
      }
      const ids = selectedIds();
      if (!ids.length) setFilters([]);
      loadResults(ids, true);
      return;
    }

    const loadMore = event.target.closest(".narrator-clusters-load-more");
    if (loadMore) {
      event.preventDefault();
      const offset = parseInt(loadMore.dataset.offset || "0", 10);
      const grid = results.querySelector(".narrator-hadith-clusters");
      const originalHtml = loadMore.innerHTML;
      loadMore.disabled = true;
      loadMore.innerHTML = '<span>Loading more narrations ...</span><span class="arabic" dir="rtl">جار تحميل المزيد من الأحاديث ...</span>';
      fetch(`/narrator/${narratorId}/hadith/list${ajaxQueryFor(selectedIds(), offset)}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
      })
        .then((response) => response.text())
        .then((html) => {
          const holder = document.createElement("div");
          holder.innerHTML = html;
          const nextGrid = holder.querySelector(".narrator-hadith-clusters");
          if (grid && nextGrid) {
            [...nextGrid.children].forEach((cluster) => grid.appendChild(cluster));
          }

          const nextButton = holder.querySelector(".narrator-clusters-load-more");
          if (nextButton) loadMore.replaceWith(nextButton);
          else loadMore.remove();
        })
        .catch(() => {
          loadMore.disabled = false;
          loadMore.innerHTML = originalHtml;
        });
      return;
    }

    const expand = event.target.closest(".narrator-cluster-expand");
    if (expand) {
      event.preventDefault();
      const clusterId = expand.dataset.clusterId;
      const links = expand.closest(".narrator-hadith-cluster").querySelector(".narrator-cluster-links");
      expand.disabled = true;
      fetch(`/narrator/${narratorId}/hadith/cluster/${clusterId}${ajaxQueryFor(selectedIds())}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
      })
        .then((response) => response.text())
        .then((html) => {
          links.innerHTML = html;
          expand.remove();
        })
        .catch(() => {
          expand.disabled = false;
          expand.textContent = "Unable to load";
        });
    }
  });

  window.addEventListener("popstate", () => {
    const collections = (new URL(window.location.href)).searchParams.get("collections");
    const parsed = collections ? collections.split(",").filter(Boolean) : [];
    setFilters(idsForTokens(parsed));
    loadResults(selectedIds(), false);
  });
})();
JS, \yii\web\View::POS_END);
?>

<div class="narrator-page" data-narrator-hadith-page data-narrator-id="<?= (int)$narrator->narrator_id ?>">
<div class="container">

<?= $this->render('_hero', ['narrator' => $narrator]) ?>

<section class="mb-section">
  <div class="section-head">
    <h3 class="section-title">Hadith Narrated</h3>
    <h3 class="section-title section-title--ar arabic" dir="rtl">الأحاديث المروية</h3>
  </div>

  <?php if (!empty($availableCollections)): ?>
  <div class="narrator-filter-pills" aria-label="Filter narrated hadith by collection">
    <button class="narrator-filter-pill" type="button" data-filter-all="1" aria-pressed="<?= $allSelected ? 'true' : 'false' ?>">
      <span>All</span>
      <span class="arabic" dir="rtl">الكل</span>
    </button>
    <?php foreach ($availableCollections as $collection): ?>
    <button class="narrator-filter-pill" type="button" data-collection-id="<?= (int)$collection['collectionID'] ?>" data-collection-slug="<?= htmlspecialchars($collection['name']) ?>" aria-pressed="<?= isset($selectedSet[(int)$collection['collectionID']]) ? 'true' : 'false' ?>">
      <span><?= htmlspecialchars($collection['englishTitle']) ?></span>
      <span class="arabic" dir="rtl"><?= htmlspecialchars($collection['arabicTitle']) ?></span>
    </button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div id="narrator-hadith-results">
    <?= $this->render('_hadith_results', [
        'narrator'              => $narrator,
        'selectedCollectionIds' => $selectedCollectionIds,
        'counts'                => $counts,
        'clusters'              => $clusters,
        'linksPerCluster'       => $linksPerCluster,
        'clusterLimit'          => $clusterLimit,
        'clusterOffset'         => $clusterOffset,
    ]) ?>
  </div>
</section>

</div>
</div>
