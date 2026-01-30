<section id="realisations" style="background-color: #ffffff; padding: 80px 0;">
    <div class="container">
        <div class="section-title">
            <h2>Nos Réalisations</h2>
            <p>Découvrez nos derniers chantiers et installations.</p>
        </div>

        <?php if (!empty($projects)): ?>
            <div class="portfolio-wrapper">

                <button type="button" class="slider-btn portfolio-prev" aria-label="Précédent">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <div class="portfolio-slider" id="portfolioSlider">
                    <?php foreach ($projects as $proj):
                        $date = $proj['date_completion'] ? date('d/m/Y', strtotime($proj['date_completion'])) : '';
                        ?>
                        <div class="portfolio-item">
                            <div class="portfolio-img">
                                <img src="<?= htmlspecialchars($proj['image_url']) ?>" alt="<?= htmlspecialchars($proj['title']) ?>" loading="lazy">
                                <?php if($proj['category']): ?>
                                    <span class="portfolio-category"><?= htmlspecialchars($proj['category']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="portfolio-content">
                                <h3><?= htmlspecialchars($proj['title']) ?></h3>

                                <div class="portfolio-meta">
                                    <?php if($proj['city']): ?>
                                        <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($proj['city']) ?></span>
                                    <?php endif; ?>
                                    <?php if($date): ?>
                                        <span><i class="fas fa-calendar-alt"></i> <?= $date ?></span>
                                    <?php endif; ?>
                                </div>

                                <p><?= nl2br(htmlspecialchars($proj['description'])) ?></p>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" class="slider-btn portfolio-next" aria-label="Suivant">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

        <?php else: ?>
            <p style="text-align:center; color:#777;">Aucune réalisation publiée pour le moment.</p>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const slider = document.getElementById('portfolioSlider');
            // Mise à jour des sélecteurs pour utiliser les nouvelles classes
            const prevBtn = document.querySelector('.portfolio-prev');
            const nextBtn = document.querySelector('.portfolio-next');

            if(slider && prevBtn && nextBtn) {
                nextBtn.addEventListener('click', () => {
                    slider.scrollBy({ left: 360, behavior: 'smooth' });
                });
                prevBtn.addEventListener('click', () => {
                    slider.scrollBy({ left: -360, behavior: 'smooth' });
                });
            }
        });
    </script>
</section>