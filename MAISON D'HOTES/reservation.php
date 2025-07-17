<?php
session_start();

// Configuration de la base de données
$host = 'localhost';
$dbname = 'clos_marsault';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$message = '';
$messageType = '';

// Traitement du formulaire de réservation
if ($_POST && isset($_POST['reserver'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $date_arrivee = $_POST['date_arrivee'];
    $date_depart = $_POST['date_depart'];
    $nb_personnes = (int)$_POST['nb_personnes'];
    $type_chambre = $_POST['type_chambre'];
    $demandes_speciales = trim($_POST['demandes_speciales']);
    
    // Validation des données
    $errors = [];
    
    if (empty($nom)) $errors[] = "Le nom est requis";
    if (empty($prenom)) $errors[] = "Le prénom est requis";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
    if (empty($date_arrivee)) $errors[] = "Date d'arrivée requise";
    if (empty($date_depart)) $errors[] = "Date de départ requise";
    if ($nb_personnes < 1 || $nb_personnes > 6) $errors[] = "Nombre de personnes invalide";
    
    // Vérifier que la date d'arrivée est antérieure à la date de départ
    if (strtotime($date_arrivee) >= strtotime($date_depart)) {
        $errors[] = "La date d'arrivée doit être antérieure à la date de départ";
    }
    
    // Vérifier que la date d'arrivée n'est pas dans le passé
    if (strtotime($date_arrivee) < strtotime(date('Y-m-d'))) {
        $errors[] = "La date d'arrivée ne peut pas être dans le passé";
    }
    
    if (empty($errors)) {
        // Vérifier la disponibilité
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE 
            type_chambre = ? AND statut != 'annulee' AND 
            ((date_arrivee <= ? AND date_depart > ?) OR 
             (date_arrivee < ? AND date_depart >= ?) OR 
             (date_arrivee >= ? AND date_depart <= ?))");
        
        $stmt->execute([$type_chambre, $date_arrivee, $date_arrivee, $date_depart, $date_depart, $date_arrivee, $date_depart]);
        $conflicts = $stmt->fetchColumn();
        
        if ($conflicts > 0) {
            $message = "Désolé, cette chambre n'est pas disponible pour ces dates.";
            $messageType = 'error';
        } else {
            // Insérer la réservation
            $stmt = $pdo->prepare("INSERT INTO reservations 
                (nom, prenom, email, telephone, date_arrivee, date_depart, nb_personnes, type_chambre, demandes_speciales, date_reservation, statut) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'en_attente')");
            
            if ($stmt->execute([$nom, $prenom, $email, $telephone, $date_arrivee, $date_depart, $nb_personnes, $type_chambre, $demandes_speciales])) {
                $reservation_id = $pdo->lastInsertId();
                $message = "Votre réservation a été enregistrée avec succès ! Numéro de réservation : #" . $reservation_id;
                $messageType = 'success';
                
                // Envoyer un email de confirmation (optionnel)
                // mail($email, "Confirmation de réservation - Clos Marsault", "Votre réservation #$reservation_id a été confirmée...");
            } else {
                $message = "Erreur lors de l'enregistrement de la réservation.";
                $messageType = 'error';
            }
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = 'error';
    }
}

// Récupérer les tarifs
$tarifs = [
    'standard' => 85,
    'superieure' => 110,
    'suite' => 150
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - Clos Marsault</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
</head>

<body>
    <header>
        <nav>
            <ul>
                <li id="logo"><a href="index.html">Clos Marsault</a></li>
                <li><a href="index.html#langue">Langue</a></li>
                <li><a href="reservation.php" class="active">Réserver</a></li>
                <li><a href="index.html#contact">Nous contacter</a></li>
            </ul>
        </nav>
    </header>

    <div class="reservation-container">
        <div class="retour">
            <a href="index.html"><i class="fa fa-arrow-left"></i> Retour à l'accueil</a>
        </div>
        
        <div class="reservation-header">
            <h1>Réservation</h1>
            <p>Réservez votre séjour au Clos Marsault</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="reservation-form" id="reservationForm">
            <div class="form-group">
                <label for="nom">Nom *</label>
                <input type="text" id="nom" name="nom" required value="<?= isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="prenom">Prénom *</label>
                <input type="text" id="prenom" name="prenom" required value="<?= isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" value="<?= isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="date_arrivee">Date d'arrivée *</label>
                <input type="date" id="date_arrivee" name="date_arrivee" required min="<?= date('Y-m-d') ?>" value="<?= isset($_POST['date_arrivee']) ? $_POST['date_arrivee'] : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="date_depart">Date de départ *</label>
                <input type="date" id="date_depart" name="date_depart" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= isset($_POST['date_depart']) ? $_POST['date_depart'] : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="nb_personnes">Nombre de personnes *</label>
                <select id="nb_personnes" name="nb_personnes" required>
                    <option value="">Choisir...</option>
                    <?php for($i = 1; $i <= 6; $i++): ?>
                        <option value="<?= $i ?>" <?= (isset($_POST['nb_personnes']) && $_POST['nb_personnes'] == $i) ? 'selected' : '' ?>><?= $i ?> personne<?= $i > 1 ? 's' : '' ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group full-width">
                <label>Type de chambre *</label>
                <div class="chambre-options">
                    <div class="chambre-option" onclick="selectChambre('standard')">
                        <input type="radio" name="type_chambre" value="standard" id="standard" <?= (isset($_POST['type_chambre']) && $_POST['type_chambre'] == 'standard') ? 'checked' : '' ?>>
                        <h4>Chambre Standard</h4>
                        <p>Chambre confortable avec vue sur le jardin</p>
                        <div class="prix"><?= $tarifs['standard'] ?>€ / nuit</div>
                    </div>
                    
                    <div class="chambre-option" onclick="selectChambre('superieure')">
                        <input type="radio" name="type_chambre" value="superieure" id="superieure" <?= (isset($_POST['type_chambre']) && $_POST['type_chambre'] == 'superieure') ? 'checked' : '' ?>>
                        <h4>Chambre Supérieure</h4>
                        <p>Plus d'espace et vue sur les vignobles</p>
                        <div class="prix"><?= $tarifs['superieure'] ?>€ / nuit</div>
                    </div>
                    
                    <div class="chambre-option" onclick="selectChambre('suite')">
                        <input type="radio" name="type_chambre" value="suite" id="suite" <?= (isset($_POST['type_chambre']) && $_POST['type_chambre'] == 'suite') ? 'checked' : '' ?>>
                        <h4>Suite</h4>
                        <p>Luxueuse suite avec salon privatif</p>
                        <div class="prix"><?= $tarifs['suite'] ?>€ / nuit</div>
                    </div>
                </div>
            </div>
            
            <div id="total-sejour" style="display: none;">
                Total du séjour : <span id="montant-total">0</span>€
            </div>
            
            <div class="form-group full-width">
                <label for="demandes_speciales">Demandes spéciales</label>
                <textarea id="demandes_speciales" name="demandes_speciales" rows="4" placeholder="Allergies, préférences alimentaires, demandes particulières..."><?= isset($_POST['demandes_speciales']) ? htmlspecialchars($_POST['demandes_speciales']) : '' ?></textarea>
            </div>
            
            <button type="submit" name="reserver" class="btn-reserver">
                <i class="fa fa-calendar-check-o"></i> Confirmer la réservation
            </button>
        </form>
    </div>

    <script>
        const tarifs = <?= json_encode($tarifs) ?>;
        
        function selectChambre(type) {
            // Décocher toutes les options
            document.querySelectorAll('.chambre-option').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Cocher l'option sélectionnée
            document.getElementById(type).checked = true;
            document.querySelector(`#${type}`).closest('.chambre-option').classList.add('selected');
            
            calculerTotal();
        }
        
        function calculerTotal() {
            const dateArrivee = document.getElementById('date_arrivee').value;
            const dateDepart = document.getElementById('date_depart').value;
            const typeChambre = document.querySelector('input[name="type_chambre"]:checked');
            
            if (dateArrivee && dateDepart && typeChambre) {
                const debut = new Date(dateArrivee);
                const fin = new Date(dateDepart);
                const diffTime = Math.abs(fin - debut);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays > 0) {
                    const prixNuit = tarifs[typeChambre.value];
                    const total = diffDays * prixNuit;
                    
                    document.getElementById('montant-total').textContent = total;
                    document.getElementById('total-sejour').style.display = 'block';
                    document.getElementById('total-sejour').innerHTML = 
                        `Durée : ${diffDays} nuit${diffDays > 1 ? 's' : ''} × ${prixNuit}€ = <span id="montant-total">${total}€</span>`;
                } else {
                    document.getElementById('total-sejour').style.display = 'none';
                }
            }
        }
        
        // Écouteurs d'événements
        document.getElementById('date_arrivee').addEventListener('change', function() {
            // Mettre à jour la date minimale de départ
            const dateArrivee = this.value;
            if (dateArrivee) {
                const dateMin = new Date(dateArrivee);
                dateMin.setDate(dateMin.getDate() + 1);
                document.getElementById('date_depart').min = dateMin.toISOString().split('T')[0];
            }
            calculerTotal();
        });
        
        document.getElementById('date_depart').addEventListener('change', calculerTotal);
        
        document.querySelectorAll('input[name="type_chambre"]').forEach(radio => {
            radio.addEventListener('change', calculerTotal);
        });
        
        // Initialiser les sélections au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const selectedChambre = document.querySelector('input[name="type_chambre"]:checked');
            if (selectedChambre) {
                selectedChambre.closest('.chambre-option').classList.add('selected');
                calculerTotal();
            }
        });
    </script>
</body>
</html>