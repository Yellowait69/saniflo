<?php
require_once 'auth.php';
$pdo = require_once __DIR__ . '/../config/db.php';

// ========================================================================
// 1. CONFIGURATION DES TABLES ET CHAMPS (MAPPING AMÉLIORÉ)
// ========================================================================
$tablesConfig = [
    'settings' => [
        'name' => 'Paramètres du Site (Textes, Emails, Images)',
        'can_add' => false,    // On empêche d'ajouter de nouveaux paramètres techniques
        'can_delete' => false, // On empêche de casser le site en supprimant une clé
        'fields' => [
            'setting_label' => ['label' => 'Description du paramètre', 'type' => 'text', 'readonly' => true],
            'setting_value' => ['label' => 'Contenu (Texte ou Image)', 'type' => 'dynamic', 'folder' => 'img/', 'help' => 'Modifiez le texte ou importez une nouvelle image (Max 2 Mo).']
        ]
    ],
    'services' => [
        'name' => 'Services',
        'can_add' => true, 'can_delete' => true,
        'fields' => [
            'title' => ['label' => 'Titre', 'type' => 'text'],
            'icon' => ['label' => 'Icône (FontAwesome)', 'type' => 'text', 'help' => 'Ex: fas fa-fire'],
            'description' => ['label' => 'Description', 'type' => 'textarea'],
            'display_order' => ['label' => 'Ordre d\'affichage', 'type' => 'number']
        ]
    ],
    'pricing' => [
        'name' => 'Tarifs',
        'can_add' => true, 'can_delete' => true,
        'fields' => [
            'service_type' => ['label' => 'Code Service', 'type' => 'text'],
            'price_htva' => ['label' => 'Prix HTVA', 'type' => 'number', 'step' => '0.01'],
            'description' => ['label' => 'Description', 'type' => 'text']
        ]
    ],
    'team' => [
        'name' => 'Équipe',
        'can_add' => true, 'can_delete' => true,
        'fields' => [
            'name' => ['label' => 'Nom', 'type' => 'text'],
            'role' => ['label' => 'Rôle', 'type' => 'text'],
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
        'can_add' => true, 'can_delete' => true,
        'fields' => [
            'region' => ['label' => 'Région', 'type' => 'text'],
            'title' => ['label' => 'Intitulé', 'type' => 'text'],
            'number' => ['label' => 'Numéro', 'type' => 'text']
        ]
    ],
    'projects' => [
        'name' => 'Réalisations (Portfolio)',
        'can_add' => true, 'can_delete' => true,
        'fields' => [
            'title' => ['label' => 'Titre du chantier', 'type' => 'text'],
            'city' => ['label' => 'Ville', 'type' => 'text'],
            'category' => ['label' => 'Catégorie', 'type' => 'text'],
            'date_completion' => ['label' => 'Date Fin', 'type' => 'date'],
            'image_url' => [
                'label' => 'Photo du chantier',
                'type' => 'file',
                'folder' => 'img/portfolio/',
                'help' => 'Volume max: 2 Mo. Dimensions recommandées: Format paysage (ex: 1200x800px).'
            ],
            'description' => ['label' => 'Description détaillée', 'type' => 'textarea']
        ]
    ],
    'users' => [
        'name' => 'Administrateurs',
        'can_add' => true, 'can_delete' => true,
        'fields' => [
            'username' => ['label' => 'Identifiant', 'type' => 'text'],
            'email' => ['label' => 'Email', 'type' => 'email'],
            'password' => ['label' => 'Mot de passe (Laisser vide si inchangé)', 'type' => 'password']
        ]
    ]
];

$currentTable = $_GET['table'] ?? 'settings';
if (!array_key_exists($currentTable, $tablesConfig)) {
    die("Table non autorisée.");
}
$config = $tablesConfig[$currentTable];
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
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
        $stmt = $pdo->prepare("DELETE FROM $currentTable WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $msg = "Élément supprimé avec succès.";
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
            $stmt = $pdo->prepare("SELECT * FROM $currentTable WHERE id = ?");
            $stmt->execute([$id]);
            $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        foreach ($fields as $f) {
            $fieldConfig = $config['fields'][$f];

            // Ne pas traiter les champs en lecture seule (ex: setting_label)
            if (!empty($fieldConfig['readonly'])) {
                continue;
            }

            // GESTION MOT DE PASSE
            if ($currentTable === 'users' && $f === 'password') {
                if (!empty($_POST[$f])) {
                    $data[] = password_hash($_POST[$f], PASSWORD_DEFAULT);
                    $insertFields[] = $f; $placeholders[] = '?'; $updateStr[] = "$f = ?";
                } elseif (!$id) {
                    die("Mot de passe requis pour nouvel utilisateur");
                }
                continue;
            }

            // DÉTERMINATION DU TYPE RÉEL (Pour le champ dynamique des settings)
            $actualType = $fieldConfig['type'];
            if ($actualType === 'dynamic') {
                $actualType = $existingItem['type'] ?? 'textarea'; // Lit le type depuis la DB
            }

            // GESTION UPLOAD FICHIER (IMAGES)
            if ($actualType === 'file') {
                $val = $existingItem[$f] ?? '';

                if (isset($_FILES[$f]) && $_FILES[$f]['error'] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES[$f]['tmp_name'];
                    $fileSize = $_FILES[$f]['size'];

                    // Limite de volume (2 Mo)
                    $maxSize = 2 * 1024 * 1024;

                    if ($fileSize > $maxSize) {
                        $msg = "Erreur : L'image dépasse le volume maximum autorisé de 2 Mo.";
                    } else {
                        $fileName = $_FILES[$f]['name'];
                        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $tmpName);
                        finfo_close($finfo);

                        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

                        if (in_array($ext, $allowedExtensions) && in_array($mimeType, $allowedMimeTypes)) {

                            // Nom sécurisé et dossier de destination configurable
                            $prefix = ($currentTable === 'settings') ? 'site_' : 'img_';
                            $newName = $prefix . uniqid('', true) . '.' . $ext;

                            $folder = $fieldConfig['folder'] ?? 'img/';
                            $targetDir = __DIR__ . '/../public/' . $folder;

                            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

                            if (move_uploaded_file($tmpName, $targetDir . $newName)) {
                                $val = $folder . $newName; // Chemin relatif pour le web
                            } else {
                                $msg = "Erreur d'écriture sur le serveur.";
                            }
                        } else {
                            $msg = "Format de fichier non supporté (Uniquement JPG, PNG, WEBP).";
                        }
                    }
                } elseif (isset($_FILES[$f]) && $_FILES[$f]['error'] !== UPLOAD_ERR_NO_FILE) {
                    $msg = "Erreur système lors du téléchargement (Code: " . $_FILES[$f]['error'] . ").";
                }

                $data[] = $val;
                $insertFields[] = $f; $placeholders[] = '?'; $updateStr[] = "$f = ?";
                continue;
            }

            // GESTION NORMALE (TEXTE)
            $val = $_POST[$f] ?? null;
            $data[] = $val;
            $insertFields[] = $f; $placeholders[] = '?'; $updateStr[] = "$f = ?";
        }

        // Exécution SQL si aucune erreur d'upload
        if (empty($msg) || strpos($msg, 'succès') !== false) {
            try {
                if ($id) {
                    $data[] = $id;
                    $sql = "UPDATE $currentTable SET " . implode(', ', $updateStr) . " WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($data);
                    $msg = "Modifications enregistrées avec succès.";
                    $action = 'list';
                } else {
                    $sql = "INSERT INTO $currentTable (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($data);
                    $msg = "Nouvel élément ajouté.";
                    $action = 'list';
                }
            } catch (Exception $e) {
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
        .readonly-field { background-color: #e9ecef; cursor: not-allowed; }
    </style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="container">
    <h2><i class="fas fa-cogs"></i> Gestion : <?= htmlspecialchars($config['name']) ?></h2>

    <?php if($msg): ?>
        <div style="background: <?= strpos($msg, 'Erreur') !== false ? '#f8d7da' : '#d4edda' ?>;
                color: <?= strpos($msg, 'Erreur') !== false ? '#721c24' : '#155724' ?>;
                padding:15px; border-radius:5px; margin-bottom:20px;
                border:1px solid <?= strpos($msg, 'Erreur') !== false ? '#f5c6cb' : '#c3e6cb' ?>;">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($action === 'edit' || $action === 'create'):
        $item = [];
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM $currentTable WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        ?>
        <div class="crud-form">
            <h3 style="color:var(--primary); border-bottom:2px solid var(--secondary); padding-bottom:10px; margin-bottom:20px;">
                <?= $id ? 'Modifier cet élément' : 'Ajouter un élément' ?>
            </h3>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?>">

                <?php foreach ($config['fields'] as $key => $fieldConfig):

                    // Détection du type dynamique pour les paramètres
                    $actualType = $fieldConfig['type'];
                    if ($actualType === 'dynamic') {
                        $actualType = $item['type'] ?? 'textarea'; // Par défaut textarea
                    }
                    $isReadonly = !empty($fieldConfig['readonly']);
                    ?>
                    <div class="form-group">
                        <label>
                            <?= htmlspecialchars($fieldConfig['label']) ?>
                            <?php if($isReadonly) echo '<span style="color:#999; font-size:0.8rem;">(Non modifiable)</span>'; ?>
                        </label>

                        <?php if ($actualType === 'textarea'): ?>
                            <textarea name="<?= $key ?>" rows="6" <?= $isReadonly ? 'readonly class="readonly-field"' : '' ?>><?= htmlspecialchars($item[$key] ?? '') ?></textarea>

                        <?php elseif ($actualType === 'file'): ?>
                            <?php if (!empty($item[$key])): ?>
                                <div style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; background:#fff; display: inline-block; border-radius: 5px;">
                                    <img src="../public/<?= htmlspecialchars($item[$key]) ?>" style="max-height: 150px; display: block;" alt="Aperçu">
                                    <small style="color: #666; display:block; margin-top:5px;">Fichier actuel : <?= htmlspecialchars($item[$key]) ?></small>
                                </div>
                                <br>
                            <?php endif; ?>

                            <?php if(!$isReadonly): ?>
                                <input type="file" name="<?= $key ?>" accept="image/*">
                            <?php endif; ?>

                        <?php else: ?>
                            <input type="<?= $actualType ?>"
                                   name="<?= $key ?>"
                                <?= $isReadonly ? 'readonly class="readonly-field"' : '' ?>
                                   value="<?= $actualType !== 'password' ? htmlspecialchars($item[$key] ?? '') : '' ?>"
                                <?= ($actualType == 'number' && isset($fieldConfig['step'])) ? 'step="'.$fieldConfig['step'].'"' : '' ?>>
                        <?php endif; ?>

                        <?php if (!empty($fieldConfig['help'])): ?>
                            <span class="help-text"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($fieldConfig['help']) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div style="margin-top:30px; text-align:right;">
                    <a href="?table=<?= htmlspecialchars($currentTable) ?>" style="margin-right:15px; color:var(--text-muted); font-size:1rem; text-decoration:none;">Annuler</a>
                    <button type="submit" class="btn-admin" style="width:auto; padding:12px 30px; font-size:1.1rem; cursor:pointer;">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>

    <?php else: // LIST VIEW ?>

        <?php if ($config['can_add'] ?? true): ?>
            <a href="?table=<?= htmlspecialchars($currentTable) ?>&action=create" class="btn-add">
                <i class="fas fa-plus"></i> Ajouter un élément
            </a>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <?php
                    $i = 0;
                    foreach($config['fields'] as $k => $v) {
                        if($i < 3 && $v['type'] != 'password') {
                            echo "<th>" . htmlspecialchars($v['label']) . "</th>";
                        }
                        $i++;
                    }
                    ?>
                    <th style="width:120px; text-align:right;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $rows = $pdo->query("SELECT * FROM $currentTable ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
                foreach($rows as $row): ?>
                    <tr>
                        <?php
                        $i = 0;
                        foreach($config['fields'] as $k => $v) {
                            if($i < 3 && $v['type'] != 'password') {
                                $val = $row[$k] ?? '';

                                // Détection type réel
                                $actualType = $v['type'] === 'dynamic' ? ($row['type'] ?? 'text') : $v['type'];

                                if ($actualType === 'file' && !empty($val)) {
                                    echo "<td><img src='../public/" . htmlspecialchars($val) . "' style='height: 50px; width: auto; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'></td>";
                                } else {
                                    if (strlen($val) > 70) $val = substr($val, 0, 70) . '...';
                                    echo "<td>" . htmlspecialchars($val) . "</td>";
                                }
                            }
                            $i++;
                        }
                        ?>
                        <td style="text-align:right; white-space:nowrap;">
                            <a href="?table=<?= htmlspecialchars($currentTable) ?>&action=edit&id=<?= $row['id'] ?>" class="btn-action btn-edit" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($config['can_delete'] ?? true): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet élément de façon permanente ?');">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?>">
                                    <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn-action btn-delete" title="Supprimer" style="cursor:pointer;">
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

