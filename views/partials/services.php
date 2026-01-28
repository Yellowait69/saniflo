<section id="services">
    <div class="container">
        <div class="section-title">
            <h2>Nos Prestations</h2>
            <p>Des solutions durables pour votre confort thermique et sanitaire</p>
        </div>
        <div class="services-grid">
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $row): ?>
                    <div class="service-card">
                        <div class="icon-box"><i class="fas <?= htmlspecialchars($row['icon']) ?>"></i></div>
                        <h3><?= htmlspecialchars($row['title']) ?></h3>
                        <p><?= htmlspecialchars($row['description']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; width:100%;">Nos services incluent : Installation chaudières...</p>
            <?php endif; ?>
        </div>
    </div>
</section>