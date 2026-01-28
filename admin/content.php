<?php
require_once 'auth.php';
$pdo = require_once __DIR__ . '/../config/db.php';

// 1. Configuration des tables et de leurs champs (Mapping)
$tablesConfig = [
    'services' => [
        'name' => 'Services',
        'fields' => [
            'title' => ['label' => 'Titre', 'type' => 'text'],
            'icon' => ['label' => 'Icône (FontAwesome)', 'type' => 'text'],
            'description' => ['label' => 'Description', 'type' => 'textarea'],
            'display_order' => ['label' => 'Ordre', 'type' => 'number']
        ]
    ],
    'pricing' => [
        'name' => 'Tarifs',
        'fields' => [
            'service_type' => ['label' => 'Code Service', 'type' => 'text'],
            'price_htva' => ['label' => 'Prix HTVA', 'type' => 'number', 'step' => '0.01'],
            'description' => ['label' => 'Description', 'type' => 'text']
        ]
    ],
    'team' => [
        'name' => 'Équipe',
        'fields' => [
            'name' => ['label' => 'Nom', 'type' => 'text'],
            'role' => ['label' => 'Rôle', 'type' => 'text'],
            'bio' => ['label' => 'Biographie', 'type' => 'textarea'],
            'image_url' => ['label' => 'Chemin Image (ex: img/nom.jpg)', 'type' => 'text']
        ]
    ],
    'certifications' => [
        'name' => 'Agréments',
        'fields' => [
            'region' => ['label' => 'Région', 'type' => 'text'],
            'title' => ['label' => 'Intitulé', 'type' => 'text'],
            'number' => ['label' => 'Numéro', 'type' => 'text']
        ]
    ],
    'projects' => [
        'name' => 'Réalisations (Portfolio)',
        'fields' => [
            'title' => ['label' => 'Titre', 'type' => 'text'],
            'city' => ['label' => 'Ville', 'type' => 'text'],
            'category' => ['label' => 'Catégorie', 'type' => 'text'],
            'date_completion' => ['label' => 'Date Fin', 'type' => 'date'],
            'image_url' => ['label' => 'Chemin Image', 'type' => 'text'],
            'description' => ['label' => 'Description', 'type' => 'textarea']
        ]
    ],
    'users' => [
        'name' => 'Administrateurs',
        'fields' => [
            'username' => ['label' => 'Identifiant', 'type' => 'text'],
            'email' => ['label' => 'Email', 'type' => 'email'],
            'password' => ['label' => 'Mot de passe (Laisser vide si inchangé)', 'type' => 'password']
        ]
    ]
];

// Récupération de la table courante
$currentTable = $_GET['table'] ?? 'services';
if (!array_key_exists($currentTable, $tablesConfig)) {
    die("Table non autorisée.");
}
$config = $tablesConfig[$currentTable];
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$msg = '';

// --- TRAITEMENT POST (Ajout / Modif / Suppression) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
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
        $insertFields = []; // Pour s'assurer de l'ordre correct lors de l'INSERT

        foreach ($fields as $f) {
            // Gestion spéciale mot de passe
            if ($currentTable === 'users' && $f === 'password') {
                if (!empty($_POST[$f])) {
                    $data[] = password_hash($_POST[$f], PASSWORD_DEFAULT);
                    $insertFields[] = $f;
                    $placeholders[] = '?';
                    $updateStr[] = "$f = ?";
                } elseif (!$id) {
                    die("Mot de passe requis pour nouvel utilisateur");
                }
                // Si c'est un update et que le champ password est vide, on ne l'ajoute pas (on garde l'ancien)
                continue;
            }

            // Gestion normale
            $val = $_POST[$f] ?? null;
            $data[] = $val;
            $insertFields[] = $f;
            $placeholders[] = '?';
            $updateStr[] = "$f = ?";
        }

        if ($id) {
            // UPDATE
            $data[] = $id; // Pour le WHERE
            $sql = "UPDATE $currentTable SET " . implode(', ', $updateStr) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            $msg = "Modification enregistrée.";
            $action = 'list';
        } else {
            // INSERT
            $sql = "INSERT INTO $currentTable (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            $msg = "Nouvel élément ajouté.";
            $action = 'list';
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
</head>
<body>

<?php include 'nav.php'; ?>

<div class="container">
    <h2>Gestion : <?= htmlspecialchars($config['name']) ?></h2>

    <?php if($msg): ?>
        <div style="background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:20px; border:1px solid #c3e6cb;">
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
                <?= $id ? 'Modifier' : 'Ajouter' ?>
            </h3>

            <form method="POST">
                <?php foreach ($config['fields'] as $key => $fieldConfig): ?>
                    <div class="form-group">
                        <label><?= htmlspecialchars($fieldConfig['label']) ?></label>
                        <?php if ($fieldConfig['type'] === 'textarea'): ?>
                            <textarea name="<?= $key ?>" rows="5"><?= htmlspecialchars($item[$key] ?? '') ?></textarea>
                        <?php else: ?>
                            <input type="<?= $fieldConfig['type'] ?>"
                                   name="<?= $key ?>"
                                   value="<?= $fieldConfig['type'] !== 'password' ? htmlspecialchars($item[$key] ?? '') : '' ?>"
                                <?= ($fieldConfig['type'] == 'number' && isset($fieldConfig['step'])) ? 'step="'.$fieldConfig['step'].'"' : '' ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div style="margin-top:30px; text-align:right;">
                    <a href="?table=<?= htmlspecialchars($currentTable) ?>" style="margin-right:15px; color:var(--text-muted); font-size:0.9rem;">Annuler</a>
                    <button type="submit" class="btn-admin" style="width:auto; padding:10px 30px;">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>

    <?php else: // LIST VIEW ?>

        <a href="?table=<?= htmlspecialchars($currentTable) ?>&action=create" class="btn-add">
            <i class="fas fa-plus"></i> Ajouter un élément
        </a>

        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <?php
                    // On affiche les 3 premières colonnes
                    $i=0;
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
                $rows = $pdo->query("SELECT * FROM $currentTable ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
                foreach($rows as $row): ?>
                    <tr>
                        <?php
                        $i=0;
                        foreach($config['fields'] as $k => $v) {
                            if($i < 3 && $v['type'] != 'password') {
                                // On tronque le texte s'il est trop long
                                $val = $row[$k] ?? '';
                                if (strlen($val) > 50) $val = substr($val, 0, 50) . '...';
                                echo "<td>" . htmlspecialchars($val) . "</td>";
                            }
                            $i++;
                        }
                        ?>
                        <td style="text-align:right; white-space:nowrap;">
                            <a href="?table=<?= htmlspecialchars($currentTable) ?>&action=edit&id=<?= $row['id'] ?>" class="btn-action btn-edit" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet élément ?');">
                                <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn-action btn-delete" title="Supprimer" style="cursor:pointer;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
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