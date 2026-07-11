<?php
// Sécurité anti-erreur si les variables n'existent pas encore
if (!isset($projects)) $projects = [];
if (!isset($projectCategories)) $projectCategories = [];
if (!isset($interventionTypes)) $interventionTypes = [];
?>

<section id="realisations" class="portfolio-section">
    <div class="container">
        <div class="section-title">
            <h2>Nos Réalisations</h2>
            <p>Découvrez nos derniers chantiers : du dépannage rapide à l'installation complète.</p>
        </div>

        <div class="portfolio-filters" id="portfolioTypeFilters">
            <button class="filter-btn-type active" data-type="all">
                <i class="fas fa-th-large"></i> Tous nos domaines
            </button>
            <?php foreach ($interventionTypes as $type): ?>
                <button class="filter-btn-type" data-type="<?= htmlspecialchars($type['slug']) ?>">
                    <?= htmlspecialchars($type['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="portfolio-filters sub-filters-container" id="portfolioCatFilters" style="margin-top: -30px; margin-bottom: 40px; gap: 8px;">
            <button class="filter-btn-cat active" data-cat="all">Toutes les catégories</button>
            <?php foreach ($projectCategories as $cat): ?>
                <button class="filter-btn-cat" data-cat="<?= htmlspecialchars($cat['slug']) ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($projects)): ?>
            <div class="portfolio-grid" id="portfolioGrid">
                <?php foreach ($projects as $proj):
                    $date = !empty($proj['date_completion']) ? date('d/m/Y', strtotime($proj['date_completion'])) : '';

                    // Extraction des slugs pour le filtrage Javascript
                    $catSlug = htmlspecialchars($proj['category_slug'] ?? 'autre');
                    $typeSlug = htmlspecialchars($proj['type_slug'] ?? 'autre');

                    // PRÉPARATION DES DONNÉES POUR LA FENÊTRE MODALE
                    $imagesArray = [$proj['image_url']];
                    if (!empty($proj['galerie_images'])) {
                        $extraImages = json_decode($proj['galerie_images'], true);
                        if (is_array($extraImages)) {
                            $imagesArray = array_merge($imagesArray, $extraImages);
                        }
                    }
                    $imagesJson = htmlspecialchars(json_encode($imagesArray), ENT_QUOTES, 'UTF-8');

                    // Préparation des métadonnées
                    $metaHtml = '';
                    if(!empty($proj['city'])) $metaHtml .= "<span class='meta-item text-primary'><i class='fas fa-map-marker-alt'></i> " . htmlspecialchars($proj['city']) . "</span>";
                    if($date) $metaHtml .= "<span class='meta-item text-primary'><i class='fas fa-calendar-check'></i> {$date}</span>";
                    ?>

                    <div class="portfolio-card filter-item"
                         data-type-slug="<?= $typeSlug ?>"
                         data-cat-slug="<?= $catSlug ?>"
                         data-title="<?= htmlspecialchars($proj['title'], ENT_QUOTES) ?>"
                         data-desc="<?= htmlspecialchars($proj['description'], ENT_QUOTES) ?>"
                         data-meta="<?= htmlspecialchars($metaHtml, ENT_QUOTES) ?>"
                         data-images="<?= $imagesJson ?>"
                         data-cat="<?= htmlspecialchars($proj['category_name'] ?? '', ENT_QUOTES) ?>">

                        <div class="portfolio-img-wrapper">
                            <img src="<?= htmlspecialchars($proj['image_url']) ?>" alt="<?= htmlspecialchars($proj['title']) ?>" loading="lazy" class="portfolio-img">

                            <div class="portfolio-overlay">
                                <i class="fas fa-search-plus"></i>
                                <span>Voir les détails</span>
                            </div>

                            <?php if(!empty($proj['category_name'])): ?>
                                <span class="portfolio-badge badge-category badge-primary">
                                    <?= htmlspecialchars($proj['category_name']) ?>
                                </span>
                            <?php endif; ?>

                            <?php if(!empty($proj['type_name'])): ?>
                                <span class="portfolio-badge badge-type badge-secondary">
                                    <i class="fas fa-tools"></i> <?= htmlspecialchars($proj['type_name']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="portfolio-content">
                            <h3 class="portfolio-title"><?= htmlspecialchars($proj['title']) ?></h3>
                            <div class="portfolio-meta">
                                <?= $metaHtml ?>
                            </div>
                            <p class="portfolio-desc"><?= nl2br(htmlspecialchars($proj['description'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="load-more-container">
                <button id="loadMorePortfolioBtn" class="btn-load-more" style="display: none;">
                    <i class="fas fa-sync-alt"></i> Voir plus de chantiers
                </button>
            </div>

        <?php else: ?>
            <div class="portfolio-empty">
                <div class="empty-icon"><i class="fas fa-hard-hat"></i></div>
                <h3>Nos équipes sont sur le terrain !</h3>
                <p>Les photos de nos chantiers seront très bientôt mises en ligne pour vous présenter notre savoir-faire.</p>
            </div>
        <?php endif; ?>
    </div>

    <div id="portfolioModal" class="portfolio-modal">
        <div class="modal-content">
            <button class="close-modal" aria-label="Fermer"><i class="fas fa-times"></i></button>

            <div class="modal-layout">
                <div class="modal-gallery">
                    <div class="modal-main-img-container">
                        <img id="modalMainImg" src="" alt="Chantier Saniflo">
                    </div>
                    <div id="modalThumbnails" class="modal-thumbnails">
                    </div>
                </div>

                <div class="modal-info">
                    <span id="modalCategoryBadge" class="portfolio-badge badge-category badge-primary" style="position: relative; display: inline-block; margin-bottom: 15px; top: 0; left: 0;"></span>
                    <h3 id="modalTitle" class="modal-title"></h3>
                    <div id="modalMeta" class="portfolio-meta modal-meta"></div>

                    <div class="modal-desc-container">
                        <h4 style="color:#003366; font-size:1.05rem; margin-bottom:10px;"><i class="fas fa-align-left text-primary"></i> Détails de l'intervention</h4>
                        <p id="modalDesc" class="modal-desc"></p>
                    </div>

                    <div style="margin-top: 30px;">
                        <a href="index.php?page=contact#contact-section" class="btn-contact-modal">
                            <i class="fas fa-envelope"></i> Demander un devis similaire
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .portfolio-section { background-color: #f8f9fa; padding: 90px 0; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .section-title { text-align: center; margin-bottom: 60px; }
        .section-title h2 { font-size: 2.5rem; color: #003366; font-weight: 700; margin-bottom: 15px; position: relative; display: inline-block; }
        .section-title h2::after { content: ''; position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); width: 60px; height: 4px; background: #0056b3; border-radius: 2px; }
        .section-title p { color: #6c757d; font-size: 1.1rem; max-width: 600px; margin: 0 auto; }

        /* Filtres Principaux */
        .portfolio-filters { display: flex; justify-content: center; flex-wrap: wrap; gap: 12px; margin-bottom: 50px; }
        .filter-btn-type { background: #fff; border: 2px solid #0056b3; color: #0056b3; padding: 10px 22px; border-radius: 50px; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .filter-btn-type:hover { background: #eef5fc; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0, 86, 179, 0.1); }
        .filter-btn-type.active { background: #0056b3; border-color: #0056b3; color: #ffffff; box-shadow: 0 4px 12px rgba(0, 86, 179, 0.3); }

        /* Sous-filtres (Catégories) */
        .filter-btn-cat { background: #eef5fc; border: 1px solid #cce5ff; color: #0056b3; padding: 6px 18px; border-radius: 30px; cursor: pointer; font-weight: 500; font-size: 0.85rem; transition: all 0.2s ease; }
        .filter-btn-cat:hover { background: #ddecff; border-color: #b8daff; }
        .filter-btn-cat.active { background: #0056b3; color: #ffffff; border-color: #0056b3; font-weight: 600; }

        /* Grille & Cartes */
        .portfolio-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 35px; }
        .portfolio-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: all 0.4s ease; display: none; flex-direction: column; cursor: pointer; border: 1px solid rgba(0, 86, 179, 0.05); }
        .portfolio-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0, 86, 179, 0.15); }

        /* Image & Overlay */
        .portfolio-img-wrapper { position: relative; width: 100%; height: 240px; overflow: hidden; }
        .portfolio-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
        .portfolio-overlay { position: absolute; inset: 0; background: rgba(0, 86, 179, 0.8); display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; color: white; font-size: 1.1rem; font-weight: 600; z-index: 3; backdrop-filter: blur(2px); }
        .portfolio-overlay i { font-size: 2.5rem; margin-bottom: 10px; transform: translateY(20px); transition: transform 0.3s ease; }
        .portfolio-card:hover .portfolio-overlay { opacity: 1; }
        .portfolio-card:hover .portfolio-overlay i { transform: translateY(0); }
        .portfolio-card:hover .portfolio-img { transform: scale(1.08); }

        /* Badges */
        .portfolio-badge { position: absolute; padding: 6px 14px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: white; box-shadow: 0 4px 10px rgba(0, 86, 179, 0.2); z-index: 2; }
        .badge-category { top: 15px; left: 15px; }
        .badge-type { top: 15px; right: 15px; }
        .badge-primary { background: linear-gradient(135deg, #007bff, #0056b3); }
        .badge-secondary { background: rgba(0, 51, 102, 0.95); backdrop-filter: blur(4px); }

        /* Contenu Carte */
        .portfolio-content { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }
        .portfolio-title { margin: 0 0 15px 0; font-size: 1.25rem; color: #003366; font-weight: 700; line-height: 1.3; transition: color 0.3s; }
        .portfolio-card:hover .portfolio-title { color: #0056b3; }
        .portfolio-meta { display: flex; gap: 15px; flex-wrap: wrap; font-size: 0.85rem; font-weight: 600; margin-bottom: 15px; border-bottom: 1px solid #f0f0f0; padding-bottom: 15px; }
        .meta-item { display: flex; align-items: center; gap: 6px; }
        .text-primary { color: #0056b3; }
        .portfolio-desc { color: #555; font-size: 0.95rem; line-height: 1.6; margin: 0; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

        /* Bouton Load More */
        .load-more-container { text-align: center; margin-top: 50px; }
        .btn-load-more { background: transparent; color: #0056b3; border: 2px solid #0056b3; padding: 12px 35px; font-size: 1.05rem; font-weight: 700; border-radius: 50px; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 10px; }
        .btn-load-more:hover { background: #0056b3; color: #fff; transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0, 86, 179, 0.2); }

        /* Styles Modale */
        .portfolio-modal { display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(0, 20, 50, 0.9); backdrop-filter: blur(5px); align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
        .portfolio-modal.show { display: flex; opacity: 1; }
        .modal-content { background: #fff; border-radius: 12px; width: 95%; max-width: 1000px; max-height: 90vh; position: relative; display: flex; flex-direction: column; overflow: hidden; transform: translateY(20px); transition: transform 0.3s ease; box-shadow: 0 25px 50px rgba(0,0,0,0.3); }
        .portfolio-modal.show .modal-content { transform: translateY(0); }
        .close-modal { position: absolute; top: 15px; right: 15px; background: rgba(0, 86, 179, 0.1); border: none; font-size: 1.5rem; color: #0056b3; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; z-index: 10; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
        .close-modal:hover { background: #dc3545; color: white; transform: rotate(90deg); }

        .modal-layout { display: flex; flex-direction: column; height: 100%; overflow-y: auto; }
        @media (min-width: 992px) {
            .modal-layout { flex-direction: row; overflow: hidden; }
            .modal-gallery { width: 55%; background: #f8f9fa; display: flex; flex-direction: column; }
            .modal-info { width: 45%; overflow-y: auto; padding: 40px; }
        }

        .modal-info { padding: 30px 20px; }
        .modal-title { font-size: 1.8rem; color: #003366; margin: 0 0 15px 0; font-weight: 800; line-height: 1.2; }
        .modal-meta { border-bottom: 2px solid #eef5fc; margin-bottom: 20px; padding-bottom: 20px; }
        .modal-desc-container { background: #f4f8fc; padding: 20px; border-radius: 8px; border-left: 4px solid #0056b3; }
        .modal-desc { color: #555; font-size: 1rem; line-height: 1.7; margin: 0; white-space: pre-wrap; }

        .modal-main-img-container { flex-grow: 1; display: flex; align-items: center; justify-content: center; padding: 0; background: #001224; min-height: 300px; }
        .modal-main-img-container img { width: 100%; height: 100%; max-height: 60vh; object-fit: contain; }

        .modal-thumbnails { display: flex; gap: 10px; padding: 15px; background: #002244; overflow-x: auto; }
        .thumb-img { width: 80px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer; opacity: 0.5; transition: 0.2s; border: 2px solid transparent; }
        .thumb-img:hover { opacity: 0.8; }
        .thumb-img.active { opacity: 1; border-color: #007bff; }

        .btn-contact-modal { display: inline-flex; align-items: center; gap: 10px; background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: 0.3s; box-shadow: 0 4px 10px rgba(0, 86, 179, 0.2); }
        .btn-contact-modal:hover { background: linear-gradient(135deg, #0056b3, #003366); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 86, 179, 0.3); }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- GESTION DU FILTRAGE À DEUX NIVEAUX (DYNAMIQUE) ---
            const typeBtns = document.querySelectorAll('#portfolioTypeFilters .filter-btn-type');
            const catBtns = document.querySelectorAll('#portfolioCatFilters .filter-btn-cat');
            const portfolioItems = Array.from(document.querySelectorAll('#portfolioGrid .portfolio-card'));
            const loadMoreBtn = document.getElementById('loadMorePortfolioBtn');
            const catContainer = document.getElementById('portfolioCatFilters');

            let currentType = 'all';
            let currentCat = 'all';
            let globalLimit = 6; // On affiche 6 cartes par défaut

            function updateDisplay() {
                let totalVisible = 0;
                let hiddenMatching = 0;

                portfolioItems.forEach(item => {
                    let itemType = item.getAttribute('data-type-slug');
                    let itemCat = item.getAttribute('data-cat-slug');

                    let matchType = (currentType === 'all' || itemType === currentType);
                    let matchCat = (currentCat === 'all' || itemCat === currentCat);

                    if (matchType && matchCat) {
                        if (totalVisible < globalLimit) {
                            if (item.style.display !== 'flex') {
                                item.style.display = 'flex';
                                item.style.animation = 'none';
                                item.offsetHeight; // Reflow
                                item.style.animation = 'fadeIn 0.5s ease forwards';
                            }
                            totalVisible++;
                        } else {
                            item.style.display = 'none';
                            hiddenMatching++;
                        }
                    } else {
                        item.style.display = 'none';
                    }
                });

                if (loadMoreBtn) {
                    loadMoreBtn.style.display = hiddenMatching > 0 ? 'inline-flex' : 'none';
                }
            }

            // Événement clic sur le TYPE (Domaine)
            typeBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    // Mettre à jour l'apparence des boutons Type
                    typeBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentType = btn.getAttribute('data-type');

                    // Déduire quelles catégories sont disponibles pour ce type précis
                    let validCats = new Set();
                    portfolioItems.forEach(item => {
                        if (currentType === 'all' || item.getAttribute('data-type-slug') === currentType) {
                            validCats.add(item.getAttribute('data-cat-slug'));
                        }
                    });

                    // Afficher/Cacher les boutons de catégories
                    let catsAvailable = 0;
                    catBtns.forEach(cBtn => {
                        let cSlug = cBtn.getAttribute('data-cat');
                        if (cSlug === 'all') return; // Le bouton "Toutes" reste toujours là

                        if (validCats.has(cSlug)) {
                            cBtn.style.display = 'inline-flex';
                            catsAvailable++;
                        } else {
                            cBtn.style.display = 'none';
                        }
                    });

                    // Si on a choisi un type spécifique et qu'il y a des catégories, on montre la ligne
                    if(currentType !== 'all' && catsAvailable > 0) {
                        catContainer.style.display = 'flex';
                    } else {
                        catContainer.style.display = 'none';
                    }

                    // Réinitialiser la catégorie sélectionnée à "Toutes"
                    catBtns.forEach(b => b.classList.remove('active'));
                    document.querySelector('#portfolioCatFilters .filter-btn-cat[data-cat="all"]').classList.add('active');
                    currentCat = 'all';

                    globalLimit = 6;
                    updateDisplay();
                });
            });

            // Événement clic sur la CATÉGORIE (Élément)
            catBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    catBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentCat = btn.getAttribute('data-cat');

                    globalLimit = 6;
                    updateDisplay();
                });
            });

            // Bouton Voir Plus
            if(loadMoreBtn) {
                loadMoreBtn.addEventListener('click', () => {
                    globalLimit += 6;
                    updateDisplay();
                });
            }

            // Init au chargement (on cache la ligne des catégories tant qu'aucun type n'est cliqué)
            catContainer.style.display = 'none';
            updateDisplay();

            // --- GESTION DE LA FENÊTRE MODALE ---
            const modal = document.getElementById('portfolioModal');
            const closeModalBtn = document.querySelector('.close-modal');

            const mTitle = document.getElementById('modalTitle');
            const mDesc = document.getElementById('modalDesc');
            const mMeta = document.getElementById('modalMeta');
            const mMainImg = document.getElementById('modalMainImg');
            const mThumbnails = document.getElementById('modalThumbnails');
            const mBadge = document.getElementById('modalCategoryBadge');

            portfolioItems.forEach(card => {
                card.addEventListener('click', () => {
                    const title = card.getAttribute('data-title');
                    const desc = card.getAttribute('data-desc');
                    const meta = card.getAttribute('data-meta');
                    const cat = card.getAttribute('data-cat');
                    const images = JSON.parse(card.getAttribute('data-images') || '[]');

                    mTitle.textContent = title;
                    mDesc.textContent = desc;
                    mMeta.innerHTML = meta;

                    if(cat) {
                        mBadge.textContent = cat;
                        mBadge.style.display = 'inline-block';
                    } else {
                        mBadge.style.display = 'none';
                    }

                    mThumbnails.innerHTML = '';
                    if (images.length > 0) {
                        mMainImg.src = images[0];

                        if (images.length > 1) {
                            images.forEach((imgUrl, index) => {
                                const thumb = document.createElement('img');
                                thumb.src = imgUrl;
                                thumb.className = `thumb-img ${index === 0 ? 'active' : ''}`;
                                thumb.addEventListener('click', () => {
                                    mMainImg.src = imgUrl;
                                    document.querySelectorAll('.thumb-img').forEach(t => t.classList.remove('active'));
                                    thumb.classList.add('active');
                                });
                                mThumbnails.appendChild(thumb);
                            });
                            mThumbnails.style.display = 'flex';
                        } else {
                            mThumbnails.style.display = 'none';
                        }
                    }

                    modal.classList.add('show');
                    document.body.style.overflow = 'hidden';
                });
            });

            const closeModal = () => {
                modal.classList.remove('show');
                document.body.style.overflow = 'auto';
            };

            closeModalBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.classList.contains('show')) closeModal();
            });
        });
    </script>
</section>