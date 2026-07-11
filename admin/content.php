<?php
require_once 'auth.php';
$pdo = require_once __DIR__ . '/../config/db.php';

// ========================================================================
// 1. CONFIGURATION DES TABLES ET CHAMPS
// ========================================================================
$tablesConfig = [
    'settings' => [
        'name' => 'Paramètres du Site (Textes, Emails, Images)',
        'pk' => 'setting_key',
        'can_add' => false,
        'can_delete' => false,
        'fields' => [
            'setting_label' => ['label' => 'Description du paramètre', 'type' => 'text', 'readonly' => true],
            'setting_value' => ['label' => 'Contenu (Texte ou Image)', 'type' => 'dynamic', 'folder' => 'img/', 'help' => 'Modifiez le texte ou importez une nouvelle image (Max 2 Mo).']
        ]
    ],
    'site_content' => [
        'name' => 'Textes du Site (Accueil)',
        'pk' => 'id',
        'can_add' => false,
        'can_delete' => false,
        'fields' => [
            'content_key' => ['label' => 'Emplacement (Clé technique)', 'type' => 'text', 'readonly' => true],
            'content_value' => ['label' => 'Texte à afficher', 'type' => 'textarea', 'help' => 'Modifiez le texte visible sur le site. Les retours à la ligne sont conservés.']
        ]
    ],
    'services' => [
        'name' => 'Services',
        'pk' => 'id',
        'can_add' => true, 'can_delete' => true,
        'fields' => [
            'title' => ['label' => 'Titre', 'type' => 'text', 'required' => true],
            'icon' => ['label' => 'Icône (FontAwesome)', 'type' => 'text', 'help' => 'Ex: fas fa-fire', 'required' => true],
            'description' => ['label' => 'Description', 'type' => 'textarea', 'required' => true],
            'display_order' => ['label' => 'Ordre d\'affichage', 'type' => 'number']
        ]
    ],
    'pricing' => [
        'name' => 'Tarifs',
        'pk' => 'id',
        'can_add' => true, 'can_delete' => true,
        'fields' => [
            'service_type' => ['label' => 'Code Service', 'type' => 'text', 'required' => true],
            'price_htva' => ['label' => 'Prix HTVA', 'type' => 'number', 'step' => '0.01', 'required' => true],
            'description' => ['label' => 'Description', 'type' => 'text']
        ]
    ],
    'team' => [
        'name' => 'Équipe',
        'pk' => 'id',
        'can_add' => true, 'can_delete' => true,
        'fields' => [
            'name' => ['label' => 'Nom', 'type' => 'text', 'required' => true],
            'role' => ['label' => 'Rôle', 'type' => 'text', 'required' => true],
            'bio' => ['label' => 'Biographie', 'type' => 'textarea'],
            'image_url' => [
                'label' => 'Photo',
                'type' => 'file',
                'folder' => 'img/team/',
                'help' => 'Volume max: 2 Mo. Dimensions recommandées: Format carré (ex: 800x800px).'
            ]
        ]
    ],
    'certifications' => [
        'name' => 'Agréments',
        'pk' => 'id',
        'can_add' => true, 'can_delete' => true,
        'fields' => [
            'region' => ['label' => 'Région', 'type' => 'text', 'required' => true],
            'title' => ['label' => 'Intitulé', 'type' => 'text', 'required' => true],
            'number' => ['label' => 'Numéro', 'type' => 'text']
        ]
    ],
    // --- NOUVELLES TABLES DE CATÉGORIES ET TYPES ---
    'product_types' => [
        'name' => 'Types de Produits (Domaines)',
        'pk' => 'id',
        'can_add' => true, 'can_delete' => true,
        'fields' => [
            'name' => ['label' => 'Nom du type (ex: Sanitaire, Chauffage)', 'type' => 'text', 'required' => true],
            'slug' => ['label' => 'Slug CSS (Identifiant sans espace, ex: sanitaire)', 'type' => 'text', 'required' => true]
        ]
    ],
    'product_categories' => [
        'name' => 'Catégories de Produits (Éléments)',
        'pk' => 'id',
        'can_add' => true, 'can_delete' => true,
        'fields' => [
            'name' => ['label' => 'Nom de la catégorie (ex: Robinet, Chaudière)', 'type' => 'text', 'required' => true],
            'slug' => ['label' => 'Slug CSS (Identifiant sans espace, ex: robinet)', 'type' => 'text', 'required' => true]
        ]
    ],
    'intervention_types' => [
        'name' => 'Types d\'intervention (Portfolio)',
        'pk' => 'id',
        'can_add' => true, 'can_delete' => true,
        'fields' => [
            'name' => ['label' => 'Nom du type (ex: Sanitaire)', 'type' => 'text', 'required' => true],
            'slug' => ['label' => 'Slug CSS (ex: sanitaire)', 'type' => 'text', 'required' => true]
        ]
    ],
    'project_categories' => [
        'name' => 'Catégories de Réalisations (Portfolio)',
        'pk' => 'id',
        'can_add' => true, 'can_delete' => true,
        'fields' => [
            'name' => ['label' => 'Nom de la catégorie (ex: Douche à l\'italienne)', 'type' => 'text', 'required' => true],
            'slug' => ['label' => 'Slug CSS (ex: douche-italienne)', 'type' => 'text', 'required' => true]
        ]
    ],
    // --- FIN NOUVELLES TABLES ---
    'projects' => [
        'name' => 'Réalisations (Portfolio)',
        'pk' => 'id',
        'can_add' => true, 'can_delete' => true,
        'fields' => [
            'title' => ['label' => 'Titre du chantier', 'type' => 'text', 'required' => true],
            'city' => ['label' => 'Ville', 'type' => 'text'],
            'type_intervention_id' => [
                'label' => 'Domaine (Type d\'intervention)',
                'type' => 'select_db',
                'table' => 'intervention_types',
                'key' => 'id',
                'display' => 'name',
                'required' => true
            ],
            'category_id' => [
                'label' => 'Élément (Catégorie)',
                'type' => 'select_db',
                'table' => 'project_categories',
                'key' => 'id',
                'display' => 'name',
                'required' => true
            ],
            'date_completion' => ['label' => 'Date de fin de chantier', 'type' => 'date'],
            'image_url' => [
                'label' => 'Photo principale du chantier',
                'type' => 'file',
                'folder' => 'img/portfolio/',
                'help' => 'Sert de couverture dans la grille (Volume max: 2 Mo).'
            ],
            'galerie_images' => [
                'label' => 'Galerie de photos supplémentaires (Optionnel)',
                'type' => 'multiple_file',
                'folder' => 'img/portfolio/',
                'help' => 'Sélectionnez plusieurs photos en maintenant "CTRL" (ou "CMD"). Les nouvelles photos écraseront l\'ancienne galerie.'
            ],
            'description' => ['label' => 'Description détaillée', 'type' => 'textarea', 'required' => true]
        ]
    ],
    'products' => [
        'name' => 'Catalogue Produits',
        'pk' => 'id',
        'can_add' => true, 'can_delete' => true,
        'fields' => [
            'name' => ['label' => 'Nom du produit', 'type' => 'text', 'required' => true, 'help' => 'Ex: Vitodens 200-W'],
            'type_id' => [
                'label' => 'Domaine (Type)',
                'type' => 'select_db',
                'table' => 'product_types',
                'key' => 'id',
                'display' => 'name',
                'required' => true
            ],
            'category_id' => [
                'label' => 'Élément (Catégorie)',
                'type' => 'select_db',
                'table' => 'product_categories',
                'key' => 'id',
                'display' => 'name',
                'required' => true
            ],
            'brand' => ['label' => 'Marque', 'type' => 'text', 'help' => 'Ex: Viessmann, BWT, Bulex'],
            'image_url' => [
                'label' => 'Image du produit',
                'type' => 'file',
                'folder' => 'img/products/',
                'help' => 'Format PNG détouré (sans fond) ou fond blanc recommandé (Max 2 Mo).'
            ],
            'brochure_url' => [
                'label' => 'Fiche Technique (PDF)',
                'type' => 'file',
                'folder' => 'pdf/',
                'help' => 'Fichier PDF uniquement (Optionnel).'
            ],
            'features' => ['label' => 'Points forts (Liste)', 'type' => 'textarea', 'help' => 'Séparez chaque point fort par un tiret (-) pour qu\'ils s\'affichent sous forme de liste.'],
            'description' => ['label' => 'Description détaillée', 'type' => 'textarea', 'required' => true],
            'display_order' => ['label' => 'Ordre d\'affichage', 'type' => 'number']
        ]
    ],
    'users' => [
        'name' => 'Administrateurs',
        'pk' => 'id',
        'can_add' => true, 'can_delete' => true,
        'fields' => [
            'username' => ['label' => 'Identifiant', 'type' => 'text', 'required' => true],
            'email' => ['label' => 'Email', 'type' => 'email', 'required' => true],
            'password' => ['label' => 'Mot de passe (Laisser vide si inchangé)', 'type' => 'password']
        ]
    ]
];

$currentTable = $_GET['table'] ?? 'settings';
if (!array_key_exists($currentTable, $tablesConfig)) {
    die("Table non autorisée.");
}
$config = $tablesConfig[$currentTable];
$pk = $config['pk'] ?? 'id';
$action = $_GET['action'] ?? 'list';
$id = !empty($_GET['id']) ? $_GET['id'] : null;
$msg = '';

// ========================================================================
// 2. TRAITEMENT DU FORMULAIRE (POST)
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token'])) {
        die("Erreur de sécurité CSRF : L'action a été bloquée.");
    }

    if (isset($_POST['delete_id']) && ($config['can_delete'] ?? true)) {
        // SUPPRESSION
        try {
            $stmt = $pdo->prepare("DELETE FROM $currentTable WHERE $pk = ?");
            $stmt->execute([$_POST['delete_id']]);
            $msg = "Élément supprimé avec succès.";
        } catch (PDOException $e) {
            $msg = "Erreur de suppression : " . $e->getMessage();
        }
    } else {
        // AJOUT OU ÉDITION
        $data = [];
        $fields = array_keys($config['fields']);
        $placeholders = [];
        $updateStr = [];
        $insertFields = [];

        // Récupération de l'item existant pour connaître son état actuel
        $existingItem = [];
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM $currentTable WHERE $pk = ?");
            $stmt->execute([$id]);
            $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        foreach ($fields as $f) {
            $fieldConfig = $config['fields'][$f];

            if (!empty($fieldConfig['readonly'])) {
                continue;
            }

            // GESTION MOT DE PASSE
            if ($currentTable === 'users' && $f === 'password') {
                if (!empty($_POST[$f])) {
                    $data[] = password_hash($_POST[$f], PASSWORD_DEFAULT);
                    $insertFields[] = $f; $placeholders[] = '?'; $updateStr[] = "$f = ?";
                } elseif (!$id) {
                    $msg = "Erreur : Mot de passe requis pour un nouvel administrateur.";
                    break;
                }
                continue;
            }

            // TYPE DYNAMIQUE
            $actualType = $fieldConfig['type'];
            if ($actualType === 'dynamic') {
                $actualType = $existingItem['setting_type'] ?? 'textarea';
            }

            // GESTION UPLOAD FICHIER UNIQUE (IMAGES ET PDF)
            if ($actualType === 'file') {
                $val = $existingItem[$f] ?? null;

                if (isset($_FILES[$f]) && $_FILES[$f]['error'] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES[$f]['tmp_name'];
                    $fileSize = $_FILES[$f]['size'];
                    $maxSize = 2 * 1024 * 1024; // 2 Mo

                    if ($fileSize > $maxSize) {
                        $msg = "Erreur : Le fichier dépasse le volume maximum autorisé de 2 Mo.";
                        break;
                    } else {
                        $fileName = $_FILES[$f]['name'];
                        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'];

                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $tmpName);
                        finfo_close($finfo);
                        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];

                        if (in_array($ext, $allowedExtensions) && in_array($mimeType, $allowedMimeTypes)) {
                            $prefix = ($ext === 'pdf') ? 'doc_' : (($currentTable === 'settings') ? 'site_' : 'img_');
                            $newName = $prefix . uniqid('', true) . '.' . $ext;

                            $folder = $fieldConfig['folder'] ?? 'img/';
                            $targetDir = __DIR__ . '/../public/' . $folder;

                            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

                            if (move_uploaded_file($tmpName, $targetDir . $newName)) {
                                $val = $folder . $newName;
                            } else {
                                $msg = "Erreur d'écriture sur le serveur pour le fichier.";
                                break;
                            }
                        } else {
                            $msg = "Format de fichier non supporté (Uniquement JPG, PNG, WEBP, GIF ou PDF).";
                            break;
                        }
                    }
                } elseif (isset($_FILES[$f]) && $_FILES[$f]['error'] !== UPLOAD_ERR_NO_FILE) {
                    $msg = "Erreur système lors du téléchargement (Code: " . $_FILES[$f]['error'] . ").";
                    break;
                }

                $data[] = $val;
                $insertFields[] = $f; $placeholders[] = '?'; $updateStr[] = "$f = ?";
                continue;
            }

            // GESTION UPLOAD MULTIPLES (GALERIES)
            if ($actualType === 'multiple_file') {
                $val = $existingItem[$f] ?? null;

                if (isset($_FILES[$f]) && !empty($_FILES[$f]['name'][0])) {
                    $uploadedFiles = [];
                    $fileCount = count($_FILES[$f]['name']);
                    $hasError = false;

                    for ($i = 0; $i < $fileCount; $i++) {
                        if ($_FILES[$f]['error'][$i] === UPLOAD_ERR_OK) {
                            $tmpName = $_FILES[$f]['tmp_name'][$i];
                            $fileSize = $_FILES[$f]['size'][$i];
                            $maxSize = 2 * 1024 * 1024; // 2 Mo par fichier

                            if ($fileSize > $maxSize) {
                                $msg = "Erreur : Une des images de la galerie dépasse le volume de 2 Mo.";
                                $hasError = true; break;
                            }

                            $fileName = $_FILES[$f]['name'][$i];
                            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mimeType = finfo_file($finfo, $tmpName);
                            finfo_close($finfo);
                            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

                            if (in_array($ext, $allowedExtensions) && in_array($mimeType, $allowedMimeTypes)) {
                                $newName = 'galerie_' . uniqid('', true) . '.' . $ext;
                                $folder = $fieldConfig['folder'] ?? 'img/';
                                $targetDir = __DIR__ . '/../public/' . $folder;

                                if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

                                if (move_uploaded_file($tmpName, $targetDir . $newName)) {
                                    $uploadedFiles[] = $folder . $newName;
                                }
                            } else {
                                $msg = "Un format de fichier non supporté a été détecté dans la galerie.";
                                $hasError = true; break;
                            }
                        }
                    }

                    if ($hasError) break;

                    if (!empty($uploadedFiles)) {
                        $val = json_encode($uploadedFiles);
                    }
                }

                $data[] = $val;
                $insertFields[] = $f; $placeholders[] = '?'; $updateStr[] = "$f = ?";
                continue;
            }

            // GESTION NORMALE DES TEXTES / NOMBRES / DATES / SELECT_DB
            $val = isset($_POST[$f]) && trim($_POST[$f]) !== '' ? trim($_POST[$f]) : null;
            $data[] = $val;
            $insertFields[] = $f; $placeholders[] = '?'; $updateStr[] = "$f = ?";
        }

        if (empty($msg)) {
            try {
                if ($id) {
                    $data[] = $id;
                    $sql = "UPDATE $currentTable SET " . implode(', ', $updateStr) . " WHERE $pk = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($data);
                    $msg = "Modifications enregistrées avec succès.";
                    $action = 'list';
                } else {
                    $sql = "INSERT INTO $currentTable (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($data);
                    $msg = "Nouvel élément ajouté avec succès.";
                    $action = 'list';
                }
            } catch (PDOException $e) {
                $msg = "Erreur Base de données : " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion : <?= htmlspecialchars($config['name']) ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .help-text { display: block; margin-top: 5px; color: #666; font-size: 0.85rem; font-style: italic; }
        .readonly-field { background-color: #e9ecef; cursor: not-allowed; border: 1px solid #ced4da; }
        .required-asterisk { color: #dc3545; font-weight: bold; margin-left: 3px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 8px; color: #333; }
        input[type="text"], input[type="number"], input[type="date"], input[type="email"], input[type="password"], textarea, select {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-family: inherit; font-size: 1rem; box-sizing: border-box; background: #fff;
        }
    </style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="margin: 0;"><i class="fas fa-database" style="color:var(--primary);"></i> Gestion : <?= htmlspecialchars($config['name']) ?></h2>
        <?php if ($action === 'list' && ($config['can_add'] ?? true)): ?>
            <a href="?table=<?= htmlspecialchars($currentTable) ?>&action=create" class="btn-admin" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: 600;">
                <i class="fas fa-plus"></i> Ajouter un élément
            </a>
        <?php endif; ?>
    </div>

    <?php if (in_array($currentTable, ['products', 'product_categories', 'product_types'])): ?>
        <div style="margin-bottom: 25px; border-bottom: 2px solid #eaeaea; display: flex; gap: 20px;">
            <a href="content.php?table=products" style="padding: 10px 5px; text-decoration: none; color: <?= $currentTable === 'products' ? 'var(--primary)' : '#6c757d' ?>; border-bottom: 3px solid <?= $currentTable === 'products' ? 'var(--primary)' : 'transparent' ?>; font-weight: <?= $currentTable === 'products' ? 'bold' : '500' ?>; transition: 0.2s;">
                <i class="fas fa-box-open"></i> Liste des Produits
            </a>
            <a href="content.php?table=product_categories" style="padding: 10px 5px; text-decoration: none; color: <?= $currentTable === 'product_categories' ? 'var(--primary)' : '#6c757d' ?>; border-bottom: 3px solid <?= $currentTable === 'product_categories' ? 'var(--primary)' : 'transparent' ?>; font-weight: <?= $currentTable === 'product_categories' ? 'bold' : '500' ?>; transition: 0.2s;">
                <i class="fas fa-tags"></i> Catégories (Éléments)
            </a>
            <a href="content.php?table=product_types" style="padding: 10px 5px; text-decoration: none; color: <?= $currentTable === 'product_types' ? 'var(--primary)' : '#6c757d' ?>; border-bottom: 3px solid <?= $currentTable === 'product_types' ? 'var(--primary)' : 'transparent' ?>; font-weight: <?= $currentTable === 'product_types' ? 'bold' : '500' ?>; transition: 0.2s;">
                <i class="fas fa-wrench"></i> Types (Domaines)
            </a>
        </div>
    <?php endif; ?>

    <?php if (in_array($currentTable, ['projects', 'project_categories', 'intervention_types'])): ?>
        <div style="margin-bottom: 25px; border-bottom: 2px solid #eaeaea; display: flex; gap: 20px;">
            <a href="content.php?table=projects" style="padding: 10px 5px; text-decoration: none; color: <?= $currentTable === 'projects' ? 'var(--primary)' : '#6c757d' ?>; border-bottom: 3px solid <?= $currentTable === 'projects' ? 'var(--primary)' : 'transparent' ?>; font-weight: <?= $currentTable === 'projects' ? 'bold' : '500' ?>; transition: 0.2s;">
                <i class="fas fa-camera-retro"></i> Réalisations (Chantiers)
            </a>
            <a href="content.php?table=project_categories" style="padding: 10px 5px; text-decoration: none; color: <?= $currentTable === 'project_categories' ? 'var(--primary)' : '#6c757d' ?>; border-bottom: 3px solid <?= $currentTable === 'project_categories' ? 'var(--primary)' : 'transparent' ?>; font-weight: <?= $currentTable === 'project_categories' ? 'bold' : '500' ?>; transition: 0.2s;">
                <i class="fas fa-tags"></i> Catégories (Éléments)
            </a>
            <a href="content.php?table=intervention_types" style="padding: 10px 5px; text-decoration: none; color: <?= $currentTable === 'intervention_types' ? 'var(--primary)' : '#6c757d' ?>; border-bottom: 3px solid <?= $currentTable === 'intervention_types' ? 'var(--primary)' : 'transparent' ?>; font-weight: <?= $currentTable === 'intervention_types' ? 'bold' : '500' ?>; transition: 0.2s;">
                <i class="fas fa-wrench"></i> Types (Domaines)
            </a>
        </div>
    <?php endif; ?>
    <?php if($msg): ?>
        <div style="background: <?= strpos($msg, 'Erreur') !== false ? '#f8d7da' : '#d4edda' ?>;
                color: <?= strpos($msg, 'Erreur') !== false ? '#721c24' : '#155724' ?>;
                padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 500;
                border-left: 5px solid <?= strpos($msg, 'Erreur') !== false ? '#dc3545' : '#28a745' ?>;">
            <i class="fas <?= strpos($msg, 'Erreur') !== false ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($action === 'edit' || $action === 'create'):
        $item = [];
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM $currentTable WHERE $pk = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        ?>
        <div class="crud-form" style="background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <h3 style="color:var(--primary); border-bottom:2px solid #f0f0f0; padding-bottom:15px; margin-top:0; margin-bottom:25px;">
                <i class="fas <?= $id ? 'fa-pen' : 'fa-plus-circle' ?>"></i> <?= $id ? 'Modifier cet élément' : 'Ajouter un nouvel élément' ?>
            </h3>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?>">

                <?php foreach ($config['fields'] as $key => $fieldConfig):
                    $actualType = $fieldConfig['type'];
                    if ($actualType === 'dynamic') $actualType = $item['setting_type'] ?? 'textarea';
                    $isReadonly = !empty($fieldConfig['readonly']);
                    $isRequired = !empty($fieldConfig['required']) && !$isReadonly;
                    if ($actualType === 'password' && $id) $isRequired = false;
                    ?>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>
                            <?= htmlspecialchars($fieldConfig['label']) ?>
                            <?php if($isRequired) echo '<span class="required-asterisk" title="Champ requis">*</span>'; ?>
                            <?php if($isReadonly) echo '<span style="color:#999; font-size:0.8rem; font-weight:normal; margin-left:10px;">(Lecture seule)</span>'; ?>
                        </label>

                        <?php if ($actualType === 'textarea'): ?>
                            <textarea name="<?= $key ?>" rows="5" <?= $isReadonly ? 'readonly class="readonly-field"' : '' ?> <?= $isRequired ? 'required' : '' ?>><?= htmlspecialchars($item[$key] ?? '') ?></textarea>

                        <?php elseif ($actualType === 'select_db'):
                            $stmtOpt = $pdo->query("SELECT {$fieldConfig['key']}, {$fieldConfig['display']} FROM {$fieldConfig['table']} ORDER BY {$fieldConfig['display']} ASC");
                            $options = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <select name="<?= $key ?>" <?= $isRequired ? 'required' : '' ?> <?= $isReadonly ? 'disabled' : '' ?>>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($options as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt[$fieldConfig['key']]) ?>" <?= (isset($item[$key]) && $item[$key] == $opt[$fieldConfig['key']]) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($opt[$fieldConfig['display']]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                        <?php elseif ($actualType === 'file'): ?>
                            <?php if (!empty($item[$key])): ?>
                                <div style="margin-bottom: 10px; padding: 15px; border: 1px solid #e0e0e0; background:#f9f9f9; display: inline-block; border-radius: 8px;">
                                    <?php if(strtolower(pathinfo($item[$key], PATHINFO_EXTENSION)) === 'pdf'): ?>
                                        <i class="fas fa-file-pdf" style="color:#dc3545; font-size:2rem; vertical-align:middle; margin-right:10px;"></i>
                                        <a href="../public/<?= htmlspecialchars($item[$key]) ?>" target="_blank" style="color:#0056b3; font-weight:bold; text-decoration:none;">Ouvrir le fichier PDF actuel</a>
                                    <?php else: ?>
                                        <img src="../public/<?= htmlspecialchars($item[$key]) ?>" style="max-height: 120px; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);" alt="Aperçu">
                                    <?php endif; ?>
                                </div>
                                <br>
                            <?php endif; ?>
                            <?php if(!$isReadonly): ?>
                                <input type="file" name="<?= $key ?>" accept="image/*,application/pdf" <?= ($isRequired && empty($item[$key])) ? 'required' : '' ?> style="padding: 10px 0;">
                            <?php endif; ?>

                        <?php elseif ($actualType === 'multiple_file'): ?>
                            <?php if (!empty($item[$key])):
                                $galleryImgs = json_decode($item[$key], true);
                                if (is_array($galleryImgs) && !empty($galleryImgs)):
                                    ?>
                                    <div style="margin-bottom: 15px; padding: 15px; border: 1px solid #e0e0e0; background:#f9f9f9; border-radius: 8px;">
                                        <p style="margin-top:0; margin-bottom: 10px; font-weight:600; color:#555; font-size:0.9rem;">Galerie existante :</p>
                                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                            <?php foreach($galleryImgs as $gImg): ?>
                                                <img src="../public/<?= htmlspecialchars($gImg) ?>" style="height: 75px; width: 75px; object-fit: cover; border-radius: 4px; border: 1px solid #ccc; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" alt="Galerie">
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; endif; ?>

                            <?php if(!$isReadonly): ?>
                                <input type="file" name="<?= $key ?>[]" accept="image/*" multiple style="padding: 10px 0;">
                            <?php endif; ?>

                        <?php else: ?>
                            <input type="<?= $actualType ?>"
                                   name="<?= $key ?>"
                                <?= $isReadonly ? 'readonly class="readonly-field"' : '' ?>
                                <?= $isRequired ? 'required' : '' ?>
                                   value="<?= $actualType !== 'password' ? htmlspecialchars($item[$key] ?? '') : '' ?>"
                                <?= ($actualType == 'number' && isset($fieldConfig['step'])) ? 'step="'.$fieldConfig['step'].'"' : '' ?>>
                        <?php endif; ?>

                        <?php if (!empty($fieldConfig['help'])): ?>
                            <span class="help-text"><i class="fas fa-info-circle" style="color:#17a2b8;"></i> <?= htmlspecialchars($fieldConfig['help']) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div style="margin-top:35px; text-align:right; border-top: 1px solid #f0f0f0; padding-top: 20px;">
                    <a href="?table=<?= htmlspecialchars($currentTable) ?>" style="margin-right:20px; color:#6c757d; text-decoration:none; font-weight:600; padding:10px;">Annuler</a>
                    <button type="submit" class="btn-admin" style="background: var(--primary); color: white; border: none; border-radius: 5px; padding: 12px 30px; font-size: 1.05rem; font-weight: bold; cursor: pointer; transition: 0.3s;">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>

    <?php else: // LIST VIEW ?>

        <div class="table-container" style="background: #fff; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                <tr style="background-color: #f4f6f9; border-bottom: 2px solid #dee2e6;">
                    <?php
                    $i = 0;
                    foreach($config['fields'] as $k => $v) {
                        // On ignore volontairement les textarea, mots de passe et champs multiples dans l'aperçu du tableau
                        if($i < 4 && $v['type'] != 'password' && $v['type'] != 'textarea' && $v['type'] != 'multiple_file') {
                            echo "<th style='padding: 15px; color: #495057; font-weight: 600;'>" . htmlspecialchars($v['label']) . "</th>";
                        }
                        $i++;
                    }
                    ?>
                    <th style="width:120px; text-align:right; padding: 15px;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $orderBy = isset($config['fields']['display_order']) ? 'display_order ASC' : "$pk DESC";
                if ($currentTable === 'settings' || $currentTable === 'site_content') $orderBy = "$pk ASC";

                $rows = $pdo->query("SELECT * FROM $currentTable ORDER BY $orderBy")->fetchAll(PDO::FETCH_ASSOC);

                if (empty($rows)) {
                    echo "<tr><td colspan='5' style='text-align:center; padding:30px; color:#888;'>Aucune donnée pour le moment.</td></tr>";
                }

                foreach($rows as $row): ?>
                    <tr style="border-bottom: 1px solid #f0f0f0; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fa';" onmouseout="this.style.backgroundColor='transparent';">
                        <?php
                        $i = 0;
                        foreach($config['fields'] as $k => $v) {
                            if($i < 4 && $v['type'] != 'password' && $v['type'] != 'textarea' && $v['type'] != 'multiple_file') {
                                $val = $row[$k] ?? '';
                                $actualType = $v['type'] === 'dynamic' ? ($row['setting_type'] ?? 'text') : $v['type'];

                                echo "<td style='padding: 15px; vertical-align: middle; color: #444;'>";

                                if ($actualType === 'file' && !empty($val)) {
                                    if(strtolower(pathinfo($val, PATHINFO_EXTENSION)) === 'pdf') {
                                        echo "<a href='../public/" . htmlspecialchars($val) . "' target='_blank' title='Voir le PDF'><i class='fas fa-file-pdf' style='color:#dc3545; font-size:1.8rem;'></i></a>";
                                    } else {
                                        echo "<img src='../public/" . htmlspecialchars($val) . "' style='height: 45px; width: auto; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.15); display:block;'>";
                                    }
                                } elseif ($actualType === 'select_db' && !empty($val)) {
                                    // Requête de résolution de la clé étrangère pour le tableau d'administration
                                    $stmtName = $pdo->prepare("SELECT {$v['display']} FROM {$v['table']} WHERE {$v['key']} = ?");
                                    $stmtName->execute([$val]);
                                    $resolvedName = $stmtName->fetchColumn();
                                    echo htmlspecialchars($resolvedName ?: 'Non lié');
                                } elseif ($actualType === 'date' && !empty($val)) {
                                    echo date('d/m/Y', strtotime($val));
                                } else {
                                    if (strlen((string)$val) > 60) $val = substr((string)$val, 0, 60) . '...';
                                    echo htmlspecialchars((string)$val);
                                }
                                echo "</td>";
                            }
                            $i++;
                        }
                        ?>
                        <td style="text-align:right; padding: 15px; white-space:nowrap;">
                            <a href="?table=<?= htmlspecialchars($currentTable) ?>&action=edit&id=<?= urlencode($row[$pk]) ?>" title="Modifier" style="color: #0056b3; background: #eef2f5; padding: 8px 12px; border-radius: 4px; text-decoration: none; margin-right: 5px; transition: 0.2s;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($config['can_delete'] ?? true): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet élément de façon permanente ?');">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?>">
                                    <input type="hidden" name="delete_id" value="<?= htmlspecialchars($row[$pk]) ?>">
                                    <button type="submit" title="Supprimer" style="color: #dc3545; background: #fae3e5; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; transition: 0.2s;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>