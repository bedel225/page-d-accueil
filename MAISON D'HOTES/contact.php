<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars(trim($_POST['nom'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    if (!empty($nom) && filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($message)) {
        $to = $email;
        $subject = "Confirmation de votre message";
        $body = "Bonjour $nom,\n\nMerci pour votre message ! Nous vous répondrons bientôt.\n\nVotre message :\n$message";
        $headers = "From: contact@clos-marsault.fr";

        if (mail($to, $subject, $body, $headers)) {
            echo "✅ Merci $nom, votre message a bien été envoyé ! Une confirmation vous a été envoyée par e-mail.";
        } else {
            echo "❌ Une erreur est survenue lors de l'envoi de l'e-mail.";
        }
    } else {
        echo "⚠️ Veuillez remplir tous les champs correctement.";
    }
} else {
    echo "❌ Requête invalide.";
}
