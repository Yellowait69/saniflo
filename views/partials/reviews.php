<?php
// 1. On tente de récupérer la connexion globale si elle existe déjà
global $pdo;

// Si $pdo n'est pas défini ou n'est pas un objet valide (s'il vaut "true" suite à un require_once)
if (!isset($pdo) || !is_object($pdo)) {
    // On utilise "require" (et non require_once) avec le bon chemin pour forcer PHP à charger l'objet
    $pdo = require __DIR__ . '/../../config/db.php';
}

// 2. On récupère les avis depuis la base de données
$reviewsList = [];
if (is_object($pdo)) {
    $stmtReviews = $pdo->query("SELECT * FROM reviews ORDER BY review_date DESC");
    $reviewsList = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);
}
?>
<section id="avis-google" style="padding: 60px 0; background-color: var(--bg-light); position: relative;">
    <div id="nb-avis-top" style="position: absolute; top: -100px; left: 0;"></div>

    <div class="container">
        <div class="section-title">
            <h2>Avis Clients</h2>
            <p>Ce que nos clients disent de nos interventions</p>
        </div>

        <div class="reviews-wrapper">

            <button type="button" class="slider-btn review-prev" aria-label="Précédent">
                <i class="fas fa-chevron-left"></i>
            </button>

            <div class="reviews-slider" id="reviewsSlider">

                <?php foreach($reviewsList as $review):
                    // Récupération de la première lettre du nom pour l'avatar
                    $initial = strtoupper(substr($review['author_name'], 0, 1));
                    ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="reviewer-avatar" style="background-color: <?= htmlspecialchars($review['avatar_color']) ?>;">
                                <?= htmlspecialchars($initial) ?>
                            </div>
                            <div class="reviewer-info">
                                <h4><?= htmlspecialchars($review['author_name']) ?></h4>
                                <div class="stars">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="review-date"><?= date('d/m/Y', strtotime($review['review_date'])) ?></span>
                            </div>
                            <i class="fab fa-google google-icon"></i>
                        </div>
                        <p class="review-text">"<?= nl2br(htmlspecialchars($review['review_text'])) ?>"</p>
                    </div>
                <?php endforeach; ?>

                <?php if(empty($reviewsList)): ?>
                    <div class="review-card" style="text-align: center; justify-content: center; width: 100%; border-top: 4px solid #ccc;">
                        <p style="color: #777;">Aucun avis pour le moment. Soyez le premier à nous laisser votre impression !</p>
                    </div>
                <?php endif; ?>

            </div>

            <button type="button" class="slider-btn review-next" aria-label="Suivant">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <a href="https://www.google.com/search?sxsrf=ANbL-n4dsJ1JGbQ_pmf-KwHqeqcZBv2FIA:1770215178944&uds=ALYpb_ncDc7jTlmw6Mmq7NjuX5c-6j-X4zt3NMnJFVMiQ55T1Pn4FcwfCgJeSZF9STrP2BnwOWMWYevFY8_d289amRvO0fidJEGnOJcnt_0AAqt4fAuiQc0Rvf3i2rP2TDmgWniQB2wr&q=SANIFLO+SRL+Avis&si=AL3DRZEsmMGCryMMFSHJ3StBhOdZ2-6yYkXd_doETEE1OR-qOTGg_iYuL18QN6_pJseFewXrNA8vbsIAitvvfbCH_FIG87Bc8bt7u2fkosF5HBJRGV0quqAjskNLPOUxV0onLzqxbqQ_&hl=fr-BE&aic=0"
               target="_blank"
               class="btn-secondary btn-google">
                <i class="fab fa-google" style="margin-right: 8px;"></i> Voir tous nos avis sur Google
            </a>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const reviewSlider = document.getElementById('reviewsSlider');
        const reviewPrevBtn = document.querySelector('.review-prev');
        const reviewNextBtn = document.querySelector('.review-next');

        if(reviewSlider && reviewPrevBtn && reviewNextBtn) {
            // Fait défiler vers la droite (largeur d'une carte + espace = environ 405px)
            reviewNextBtn.addEventListener('click', () => {
                reviewSlider.scrollBy({ left: 405, behavior: 'smooth' });
            });
            // Fait défiler vers la gauche
            reviewPrevBtn.addEventListener('click', () => {
                reviewSlider.scrollBy({ left: -405, behavior: 'smooth' });
            });
        }
    });
</script>

<style>
    /* Nouveau Wrapper pour aligner les flèches et le slider */
    .reviews-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
    }

    /* Style des boutons flèches */
    .review-prev, .review-next {
        background: #fff;
        border: 1px solid #eee;
        color: var(--primary-dark);
        width: 45px;
        height: 45px;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .review-prev:hover, .review-next:hover {
        background: var(--primary-blue);
        color: white;
        transform: scale(1.1);
    }

    /* Espacements autour des flèches */
    .review-prev { margin-right: 15px; }
    .review-next { margin-left: 15px; }

    /* Le Slider Horizontal */
    .reviews-slider {
        display: flex;
        overflow-x: auto;
        gap: 25px;
        padding: 10px 5px 30px 5px;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        width: 100%;
    }

    /* Masquer la barre de défilement (Scrollbar) pour un look plus moderne */
    .reviews-slider::-webkit-scrollbar {
        display: none;
    }
    .reviews-slider {
        -ms-overflow-style: none;  /* IE et Edge */
        scrollbar-width: none;  /* Firefox */
    }

    /* Style d'une carte d'avis */
    .review-card {
        flex: 0 0 380px;
        scroll-snap-align: start;
        background: #fff;
        padding: 30px 25px;
        border-radius: var(--radius);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border-top: 4px solid var(--accent-yellow);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: flex;
        flex-direction: column;
        white-space: normal;
    }

    .review-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 112, 205, 0.1);
    }

    /* En-tête de la carte */
    .review-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        position: relative;
    }

    .reviewer-avatar {
        width: 50px;
        height: 50px;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        font-weight: bold;
        margin-right: 15px;
        flex-shrink: 0;
    }

    .reviewer-info h4 {
        margin: 0 0 2px 0;
        color: var(--primary-dark);
        font-size: 1.1rem;
        line-height: 1.2;
    }

    .review-date {
        font-size: 0.8rem;
        color: #888;
        display: block;
        margin-top: 2px;
    }

    .stars {
        color: #fbbc04; /* Jaune Google */
        font-size: 0.9rem;
    }

    .google-icon {
        position: absolute;
        right: 0;
        top: 0;
        color: #4285F4; /* Bleu Google */
        font-size: 1.5rem;
        opacity: 0.8;
    }

    /* Texte de l'avis */
    .review-text {
        color: #555;
        font-size: 0.95rem;
        line-height: 1.6;
        font-style: italic;
        margin: 0;
    }

    /* Bouton Google */
    .btn-google {
        display: inline-block;
        text-decoration: none;
        padding: 12px 30px;
        border: 2px solid var(--primary-blue);
        color: var(--primary-blue);
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s;
        background: transparent;
    }

    .btn-google:hover {
        background-color: var(--primary-blue) !important;
        color: white !important;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 112, 205, 0.2);
    }

    /* Responsive Mobile : Ajuste la largeur des cartes et cache les flèches */
    @media (max-width: 768px) {
        .review-card {
            flex: 0 0 85vw; /* La carte prendra 85% de la largeur de l'écran */
        }
        /* On cache les flèches sur mobile car le tactile (swipe) est plus naturel */
        .review-prev, .review-next {
            display: none;
        }
    }
</style>