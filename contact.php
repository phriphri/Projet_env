<?php
/**
 * Page Nous Contacter
 */
require_once 'config/database.php';

$message_succes = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Traitement basique du formulaire (simulation)
    $nom = $_POST['nom'] ?? '';
    $email = $_POST['email'] ?? '';
    $sujet = $_POST['sujet'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (!empty($nom) && !empty($email) && !empty($message)) {
        $message_succes = "Merci $nom ! Votre message a bien été envoyé à l'équipe Kin La Verte.";
    }
}

include 'includes/header.php';
?>

<div style="max-width: 600px; margin: 3rem auto; padding: 0 1.5rem;">
    <h1 style="color: var(--green-700); font-size: 2.2rem; margin-bottom: 0.5rem; border-bottom: 2px solid var(--green-200); padding-bottom: 0.5rem;">Nous Contacter</h1>
    <p style="font-size: 1rem; color: var(--gray-700); line-height: 1.6; margin-bottom: 2rem;">
        Kin La Verte est un projet étudiant citoyen né de la volonté de préserver la beauté et la santé de notre capitale. 
        Pour toute idée, question ou proposition de collaboration, écrivez-nous.
    </p>

    <div style="margin-bottom: 2rem;">
        <strong style="color: var(--gray-800);">Contact direct :</strong> 
        <a href="mailto:elite123@gmail.com" style="color: var(--green-600); text-decoration: none;">elite123@gmail.com</a>
    </div>

    <h2 style="font-size: 1.4rem; color: var(--gray-800); margin-bottom: 1rem;">Envoyer un message</h2>
    
    <?php if ($message_succes): ?>
        <div style="background: #e8f5e9; color: #2e7d32; padding: 1rem; border: 1px solid #a5d6a7; margin-bottom: 1.5rem;">
            <?php echo htmlspecialchars($message_succes); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" style="display: flex; flex-direction: column; gap: 1rem;">
        <div>
            <label style="display: block; margin-bottom: 0.3rem; font-weight: bold; color: var(--gray-700);">Votre nom</label>
            <input type="text" name="nom" required style="width: 100%; padding: 0.6rem; border: 1px solid #ccc; font-family: inherit;">
        </div>

        <div>
            <label style="display: block; margin-bottom: 0.3rem; font-weight: bold; color: var(--gray-700);">Votre e-mail</label>
            <input type="email" name="email" required style="width: 100%; padding: 0.6rem; border: 1px solid #ccc; font-family: inherit;">
        </div>
        
        <div>
            <label style="display: block; margin-bottom: 0.3rem; font-weight: bold; color: var(--gray-700);">Sujet</label>
            <input type="text" name="sujet" style="width: 100%; padding: 0.6rem; border: 1px solid #ccc; font-family: inherit;">
        </div>

        <div>
            <label style="display: block; margin-bottom: 0.3rem; font-weight: bold; color: var(--gray-700);">Message</label>
            <textarea name="message" required rows="6" style="width: 100%; padding: 0.6rem; border: 1px solid #ccc; font-family: inherit; resize: vertical;"></textarea>
        </div>

        <button type="submit" style="background: var(--green-700); color: white; border: none; padding: 0.8rem; font-size: 1rem; cursor: pointer; margin-top: 0.5rem;">
            Envoyer
        </button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
