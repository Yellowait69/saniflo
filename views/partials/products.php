<?php
// Requête pour récupérer les produits triés par ordre d'affichage (à décommenter selon votre contrôleur)
// $products = $pdo->query("SELECT * FROM products ORDER BY display_order ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Sécurité anti-erreur si la variable n'existe pas encore
if (!isset($products)) {
    $products = [];
}
?>

<section id="produits" class="products-section">
    <div class="container">
        <div class="section-title">
            <h2>Nos Produits Partenaires</h2>
            <p>Saniflo vous propose et installe les équipements des plus grandes marques pour garantir votre confort et la durabilité de vos installations.</p>
        </div>

        <div class="product-filters">
            <button class="filter-btn active" data-filter="all"><i class="fas fa-th-large"></i> Tous nos produits</button>
            <button class="filter-btn" data-filter="chaudiere"><i class="fas fa-fire"></i> Chaudières</button>
            <button class="filter-btn" data-filter="pompe"><i class="fas fa-wind"></i> Pompes à chaleur</button>
            <button class="filter-btn" data-filter="adoucisseur"><i class="fas fa-tint"></i> Adoucisseurs</button>
            <button class="filter-btn" data-filter="sanitaire"><i class="fas fa-bath"></i> Sanitaire</button>
        </div>

        <?php if (!empty($products)): ?>
            <div class="product-grid" id="productGrid">
                <?php foreach ($products as $prod):
                    // Classification pour les filtres
                    $catLower = strtolower(trim($prod['category'] ?? ''));
                    $filterClass = 'autre';
                    if (strpos($catLower, 'chaudière') !== false || strpos($catLower, 'chaudiere') !== false) $filterClass = 'chaudiere';
                    elseif (strpos($catLower, 'pompe') !== false || strpos($catLower, 'pac') !== false) $filterClass = 'pompe';
                    elseif (strpos($catLower, 'adoucisseur') !== false) $filterClass = 'adoucisseur';
                    elseif (strpos($catLower, 'sanitaire') !== false || strpos($catLower, 'boiler') !== false) $filterClass = 'sanitaire';

                    // Formatage des points forts (séparés par des tirets) pour le JS
                    $featuresRaw = $prod['features'] ?? '';
                    $featuresArray = array_filter(array_map('trim', explode('-', $featuresRaw)));
                    $featuresJson = htmlspecialchars(json_encode(array_values($featuresArray)), ENT_QUOTES, 'UTF-8');

                    // Image par défaut
                    $imgUrl = !empty($prod['image_url']) ? htmlspecialchars($prod['image_url']) : 'img/no-image.png';
                    ?>

                    <div class="product-card filter-item <?= $filterClass ?>"
                         data-name="<?= htmlspecialchars($prod['name'], ENT_QUOTES) ?>"
                         data-brand="<?= htmlspecialchars($prod['brand'] ?? '', ENT_QUOTES) ?>"
                         data-cat="<?= htmlspecialchars($prod['category'], ENT_QUOTES) ?>"
                         data-desc="<?= htmlspecialchars($prod['description'], ENT_QUOTES) ?>"
                         data-img="<?= $imgUrl ?>"
                         data-pdf="<?= htmlspecialchars($prod['brochure_url'] ?? '') ?>"
                         data-features="<?= $featuresJson ?>">

                        <div class="product-img-wrapper">
                            <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($prod['name']) ?>" loading="lazy" class="product-img">

                            <?php if(!empty($prod['brand'])): ?>
                                <span class="product-brand-badge"><?= htmlspecialchars($prod['brand']) ?></span>
                            <?php endif; ?>

                            <div class="product-overlay">
                                <i class="fas fa-search-plus"></i>
                                <span>Voir la fiche</span>
                            </div>
                        </div>

                        <div class="product-content">
                            <span class="product-cat-text"><?= htmlspecialchars($prod['category']) ?></span>
                            <h3 class="product-title"><?= htmlspecialchars($prod['name']) ?></h3>
                            <button class="btn-product-details">Découvrir ce produit</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="load-more-container">
                <button id="loadMoreProdBtn" class="btn-load-more" style="display: none;">
                    <i class="fas fa-sync-alt"></i> Voir plus de produits
                </button>
            </div>

        <?php else: ?>
            <div class="portfolio-empty" style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                <i class="fas fa-box-open" style="font-size: 3.5rem; color: #e9ecef; margin-bottom: 20px;"></i>
                <h3 style="color: #003366;">Notre catalogue est en cours de mise à jour</h3>
                <p style="color: #6c757d;">Revenez bientôt pour découvrir nos équipements partenaires.</p>
            </div>
        <?php endif; ?>
    </div>

    <div id="productModal" class="portfolio-modal">
        <div class="modal-content product-modal-content">
            <button class="close-modal" aria-label="Fermer"><i class="fas fa-times"></i></button>

            <div class="modal-layout">
                <div class="modal-gallery" style="background: #ffffff; border-right: 1px solid #f0f0f0;">
                    <div class="modal-main-img-container" style="background: transparent; padding: 40px; min-height: 400px;">
                        <img id="pModalImg" src="" alt="Produit Saniflo" style="object-fit: contain; filter: drop-shadow(0 20px 30px rgba(0,0,0,0.1)); max-height: 50vh;">
                    </div>
                </div>

                <div class="modal-info" style="background: #fcfcfc;">
                    <span id="pModalCat" class="product-cat-text" style="display: inline-block; margin-bottom: 10px; background: #eef5fc; padding: 4px 12px; border-radius: 20px;"></span>
                    <h3 id="pModalName" class="modal-title" style="color: #003366; font-size: 2.2rem; margin-bottom: 5px; font-weight: 800;"></h3>
                    <h4 id="pModalBrand" style="color: #0056b3; font-weight: 700; margin-top: 0; margin-bottom: 25px; font-size: 1.2rem; text-transform: uppercase; letter-spacing: 1px;"></h4>

                    <div class="modal-desc-container" style="background: transparent; padding: 0; border: none; margin-bottom: 25px;">
                        <p id="pModalDesc" class="modal-desc" style="color: #555; font-size: 1.05rem; line-height: 1.7;"></p>
                    </div>

                    <div id="pModalFeaturesContainer" style="display: none; margin-bottom: 30px; background: #fff; border: 1px solid #eef5fc; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,86,179,0.05);">
                        <h5 style="color: #003366; margin-top: 0; margin-bottom: 15px; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-check-circle" style="color: #28a745;"></i> Points forts
                        </h5>
                        <ul id="pModalFeatures" style="margin: 0; padding-left: 20px; color: #444; line-height: 1.8; font-weight: 500;"></ul>
                    </div>

                    <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 35px;">
                        <a href="index.php?page=contact#contact-section" class="btn-contact-modal">
                            <i class="fas fa-file-invoice"></i> Obtenir un devis
                        </a>
                        <a id="pModalPdf" href="#" target="_blank" class="btn-pdf-modal" style="display: none;">
                            <i class="fas fa-file-pdf"></i> Fiche technique
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .products-section { background-color: #f8f9fa; padding: 90px 0; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }

        .section-title { text-align: center; margin-bottom: 60px; }
        .section-title h2 { font-size: 2.5rem; color: #003366; font-weight: 700; margin-bottom: 15px; position: relative; display: inline-block; }
        .section-title h2::after { content: ''; position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); width: 60px; height: 4px; background: #0056b3; border-radius: 2px; }
        .section-title p { color: #6c757d; font-size: 1.1rem; max-width: 600px; margin: 0 auto; }

        /* Boutons Filtres */
        .product-filters { display: flex; justify-content: center; flex-wrap: wrap; gap: 12px; margin-bottom: 50px; }
        .product-filters .filter-btn { background: #fff; border: 2px solid #0056b3; color: #0056b3; padding: 10px 22px; border-radius: 50px; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .product-filters .filter-btn:hover { background: #eef5fc; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0, 86, 179, 0.1); }
        .product-filters .filter-btn.active { background: #0056b3; border-color: #0056b3; color: #ffffff; box-shadow: 0 4px 12px rgba(0, 86, 179, 0.3); }

        /* Grille Produits */
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 35px; }
        .product-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.04); border: 1px solid rgba(0, 86, 179, 0.05); transition: all 0.4s ease; display: none; flex-direction: column; cursor: pointer; text-align: center; }
        .product-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0, 86, 179, 0.12); border-color: #cce5ff; }

        .product-img-wrapper { position: relative; width: 100%; height: 240px; background: #ffffff; padding: 25px; display: flex; align-items: center; justify-content: center; overflow: hidden; border-bottom: 1px solid #f8f9fa; }
        .product-img { max-width: 100%; max-height: 100%; object-fit: contain; transition: transform 0.5s ease; mix-blend-mode: multiply; }
        .product-card:hover .product-img { transform: scale(1.08); }

        .product-brand-badge { position: absolute; top: 15px; left: 15px; background: linear-gradient(135deg, #003366, #001a33); color: white; padding: 5px 14px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; z-index: 2; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }

        .product-overlay { position: absolute; inset: 0; background: rgba(0, 86, 179, 0.85); display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; color: white; font-weight: 600; font-size: 1.1rem; z-index: 3; backdrop-filter: blur(2px); }
        .product-card:hover .product-overlay { opacity: 1; }
        .product-overlay i { font-size: 2.5rem; margin-bottom: 10px; transform: translateY(20px); transition: transform 0.3s ease; }
        .product-card:hover .product-overlay i { transform: translateY(0); }

        .product-content { padding: 25px 20px; flex-grow: 1; display: flex; flex-direction: column; align-items: center; }
        .product-cat-text { color: #0056b3; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .product-title { margin: 0 0 20px 0; font-size: 1.3rem; color: #003366; font-weight: 700; line-height: 1.3; transition: color 0.3s; }
        .product-card:hover .product-title { color: #0056b3; }

        .btn-product-details { margin-top: auto; background: transparent; color: #0056b3; border: 2px solid #0056b3; padding: 10px 25px; border-radius: 50px; font-weight: 600; font-size: 0.95rem; transition: all 0.3s ease; width: 100%; }
        .product-card:hover .btn-product-details { background: #0056b3; color: white; box-shadow: 0 4px 10px rgba(0, 86, 179, 0.2); }

        /* Bouton Load More */
        .load-more-container { text-align: center; margin-top: 50px; }
        .btn-load-more { background: transparent; color: #0056b3; border: 2px solid #0056b3; padding: 12px 35px; font-size: 1.05rem; font-weight: 700; border-radius: 50px; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 10px; }
        .btn-load-more:hover { background: #0056b3; color: #fff; transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0, 86, 179, 0.2); }

        /* Modale */
        .portfolio-modal { display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(0, 20, 50, 0.9); backdrop-filter: blur(5px); align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
        .portfolio-modal.show { display: flex; opacity: 1; }
        .modal-content { background: #fff; border-radius: 12px; width: 95%; max-width: 1000px; max-height: 90vh; position: relative; display: flex; flex-direction: column; overflow: hidden; transform: translateY(20px); transition: transform 0.3s ease; box-shadow: 0 25px 50px rgba(0,0,0,0.3); }
        .portfolio-modal.show .modal-content { transform: translateY(0); }
        .close-modal { position: absolute; top: 15px; right: 15px; background: rgba(0, 86, 179, 0.1); border: none; font-size: 1.5rem; color: #0056b3; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; z-index: 10; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
        .close-modal:hover { background: #dc3545; color: white; transform: rotate(90deg); }

        .modal-layout { display: flex; flex-direction: column; height: 100%; overflow-y: auto; }
        @media (min-width: 992px) {
            .modal-layout { flex-direction: row; overflow: hidden; }
            .modal-gallery { width: 50%; display: flex; flex-direction: column; justify-content: center; }
            .modal-info { width: 50%; overflow-y: auto; padding: 50px 40px; }
        }

        .btn-contact-modal { display: inline-flex; align-items: center; gap: 10px; background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: 0.3s; box-shadow: 0 4px 10px rgba(0, 86, 179, 0.2); }
        .btn-contact-modal:hover { background: linear-gradient(135deg, #0056b3, #003366); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 86, 179, 0.3); }

        .btn-pdf-modal { display: inline-flex; align-items: center; gap: 10px; background: #fff; color: #dc3545; border: 2px solid #dc3545; padding: 10px 25px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: 0.3s; }
        .btn-pdf-modal:hover { background: #dc3545; color: white; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.2); }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- GESTION DES FILTRES & VOIR PLUS ---
            const filterBtns = document.querySelectorAll('.product-filters .filter-btn');
            const productItems = Array.from(document.querySelectorAll('.product-grid .filter-item'));
            const loadMoreBtn = document.getElementById('loadMoreProdBtn');

            let currentFilter = 'all';
            let globalLimit = 8; // Affiche 8 produits par défaut

            function updateProductDisplay() {
                let totalVisible = 0;
                let hiddenMatching = 0;

                productItems.forEach(item => {
                    let matches = (currentFilter === 'all' || item.classList.contains(currentFilter));

                    if (matches) {
                        if (totalVisible < globalLimit) {
                            if (item.style.display !== 'flex') {
                                item.style.display = 'flex';
                                item.style.animation = 'none';
                                item.offsetHeight; // Trigger reflow
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

            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentFilter = btn.getAttribute('data-filter');
                    globalLimit = 8;
                    updateProductDisplay();
                });
            });

            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', () => {
                    globalLimit += 8;
                    updateProductDisplay();
                });
            }
            updateProductDisplay();

            // --- GESTION DE LA MODALE PRODUIT ---
            const modal = document.getElementById('productModal');
            const closeBtn = modal.querySelector('.close-modal');

            const mName = document.getElementById('pModalName');
            const mBrand = document.getElementById('pModalBrand');
            const mCat = document.getElementById('pModalCat');
            const mDesc = document.getElementById('pModalDesc');
            const mImg = document.getElementById('pModalImg');
            const mPdf = document.getElementById('pModalPdf');

            const mFeatContainer = document.getElementById('pModalFeaturesContainer');
            const mFeatList = document.getElementById('pModalFeatures');

            productItems.forEach(card => {
                card.addEventListener('click', () => {
                    // Récupération des données
                    mName.textContent = card.getAttribute('data-name');
                    mBrand.textContent = card.getAttribute('data-brand');
                    mCat.textContent = card.getAttribute('data-cat');
                    mDesc.textContent = card.getAttribute('data-desc');

                    // L'URL de l'image est générée en PHP, on la lit directement
                    mImg.src = card.getAttribute('data-img');

                    // Gestion du PDF
                    const pdfUrl = card.getAttribute('data-pdf');
                    if (pdfUrl) {
                        mPdf.href = pdfUrl; // Lien direct
                        mPdf.style.display = 'inline-flex';
                    } else {
                        mPdf.style.display = 'none';
                    }

                    // Gestion de la liste des points forts (JSON)
                    const featuresRaw = card.getAttribute('data-features');
                    const features = featuresRaw ? JSON.parse(featuresRaw) : [];

                    mFeatList.innerHTML = '';
                    if (features.length > 0) {
                        features.forEach(feat => {
                            const li = document.createElement('li');
                            li.textContent = feat;
                            mFeatList.appendChild(li);
                        });
                        mFeatContainer.style.display = 'block';
                    } else {
                        mFeatContainer.style.display = 'none';
                    }

                    // Afficher la modale
                    modal.classList.add('show');
                    document.body.style.overflow = 'hidden';
                });
            });

            // Fonctions de fermeture
            const closePModal = () => {
                modal.classList.remove('show');
                document.body.style.overflow = 'auto';
            };

            closeBtn.addEventListener('click', closePModal);
            modal.addEventListener('click', (e) => { if (e.target === modal) closePModal(); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.classList.contains('show')) closePModal(); });
        });
    </script>
</section>