<?php
/**
 * Interface cartographique et soumission de signalements
 */
require_once 'config/database.php';

$est_connecte = isset($_SESSION['user_id']);

$requete = $pdo->query("SELECT * FROM signalements ORDER BY date_creation DESC");
$tous_les_signalements = $requete->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    /* ── Carte interactive ──────────────────────────────────────── */
    .carte-wrapper {
        width: 100%;
        overflow: hidden; /* empêche tout débordement horizontal */
        margin-bottom: 2rem;
    }

    #carte-interactive {
        height: 600px;
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        border: 2px solid var(--primary-color);
        display: block; /* évite l'espace blanc sous l'élément (inline par défaut) */
    }

    .popup-form { min-width: 220px; font-family: inherit; }
    .popup-form h3 { margin: 0 0 10px 0; color: var(--primary-color); font-size: 1.1rem; }
    .popup-form label { display: block; margin: 8px 0 3px; font-weight: bold; font-size: 0.85rem; color: #555; }
    .popup-form select, .popup-form input, .popup-form textarea { width: 100%; padding: 6px; margin-bottom: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; }
    .popup-form button { background: var(--secondary-color); color: white; border: none; padding: 8px 10px; width: 100%; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 10px; transition: 0.3s; }
    .popup-form button:hover { background: #27ae60; }

    .leaflet-popup-content h4 { margin: 0 0 6px 0; color: var(--primary-color); }
    .leaflet-popup-content .pop-label { font-weight: bold; color: #555; font-size: 0.8rem; text-transform: uppercase; margin-top: 6px; display: block; }
    .pop-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.82rem; font-weight: bold; color: white; margin-top: 4px; }
</style>

<div class="page-intro">
    <h1>Cartographie Interactive</h1>
    <p>Consultez les incidents signalés ou déclarez un nouveau problème en cliquant sur la carte.</p>
</div>

<div class="carte-wrapper">
    <div id="carte-interactive"></div>
</div>

<script>
    var map = L.map('carte-interactive').setView([-4.3224, 15.3070], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    var popup_active = null;
    var est_connecte = <?php echo $est_connecte ? 'true' : 'false'; ?>;
    
    var les_signalements = <?php echo json_encode($tous_les_signalements); ?>;
    
    function getCouleur(gravite) {
        switch(gravite) {
            case 'Faible': return '#2ecc71';
            case 'Moyenne': return '#f1c40f';
            case 'Haute': return '#e67e22';
            case 'Critique': return '#e74c3c';
            default: return '#95a5a6';
        }
    }

    function creerMarqueur(couleur) {
        return L.divIcon({
            className: '',
            html: '<div style="width:18px;height:18px;background:' + couleur + ';border:2px solid white;border-radius:50%;box-shadow:0 2px 5px rgba(0,0,0,0.5);"></div>',
            iconSize: [18, 18],
            iconAnchor: [9, 9]
        });
    }

    les_signalements.forEach(function(sig) {
        if (sig.latitude && sig.longitude) {
            var couleur = getCouleur(sig.urgence);
            
            var statutColors = {
                'Envoyé': '#95a5a6',
                'Vu': '#3498db',
                'Validé': '#f39c12',
                'Traité': '#2ecc71'
            };
            var couleurStatut = statutColors[sig.statut] || '#999';

            var imageHtml = sig.photo 
                ? '<img src="' + sig.photo + '" alt="Photographie de l\'incident" style="width:100%; max-height:150px; object-fit:cover; border-radius:6px; margin-top:10px;">' 
                : '<p style="font-size:0.8rem; color:#888; font-style:italic;">Aucune photographie fournie.</p>';

            var bulleInfo = `
                <h4>${sig.titre}</h4>
                <p style="margin:5px 0; font-size:0.9rem;">${sig.description || 'Aucune description disponible.'}</p>
                ${imageHtml}
                <div style="margin-top:10px; display:flex; gap:5px; flex-wrap:wrap;">
                    <span class="pop-badge" style="background:${couleur};">Gravité : ${sig.urgence}</span>
                    <span class="pop-badge" style="background:${couleurStatut};">Statut : ${sig.statut}</span>
                </div>
            `;

            L.marker([sig.latitude, sig.longitude], { icon: creerMarqueur(couleur) })
             .addTo(map)
             .bindPopup(bulleInfo, { maxWidth: 260 });
        }
    });

    map.locate({setView: true, maxZoom: 14});
    map.on('locationfound', function(e) {
        L.circleMarker(e.latlng, { radius: 8, fillColor: '#3498db', color: '#fff', weight: 3, fillOpacity: 1 })
         .addTo(map).bindPopup("<b>Position actuelle</b>").openPopup();
    });

    map.on('click', function(e) {
        if (!est_connecte) {
            L.popup().setLatLng(e.latlng).setContent("<b>Authentification requise</b><br>Veuillez vous <a href='login.php'>connecter</a> pour soumettre un signalement.").openOn(map);
            return;
        }

        var lat = e.latlng.lat.toFixed(6);
        var lng = e.latlng.lng.toFixed(6);

        var htmlFormulaire = `
            <div class="popup-form">
                <h3>Formulaire de signalement</h3>
                <p style="font-size:11px; color:#888; margin-bottom:10px;">Coordonnées : ${lat}, ${lng}</p>
                
                <form id="formSignalement" enctype="multipart/form-data">
                    <label>Nature de l'incident :</label>
                    <select name="type_incident" required>
                        <option value="Déchets">Amoncellement de déchets</option>
                        <option value="Inondation">Inondation / Obstruction des canalisations</option>
                        <option value="Érosion">Érosion de terrain</option>
                        <option value="Pollution">Pollution (Eau/Air)</option>
                    </select>

                    <label>Niveau de gravité :</label>
                    <select name="gravite">
                        <option value="Faible">Faible</option>
                        <option value="Moyenne" selected>Moyenne</option>
                        <option value="Haute">Haute</option>
                        <option value="Critique">Critique</option>
                    </select>

                    <label>Description (optionnelle) :</label>
                    <textarea name="description" rows="3" placeholder="Fournissez des détails complémentaires..."></textarea>

                    <label>Photographie justificative :</label>
                    <input type="file" name="photo" accept="image/*">

                    <button type="button" onclick="envoyerSignalement(${lat}, ${lng})">Soumettre le signalement</button>
                </form>
            </div>
        `;

        popup_active = L.popup()
            .setLatLng(e.latlng)
            .setContent(htmlFormulaire)
            .openOn(map);
    });

    function envoyerSignalement(lat, lng) {
        var formulaire = document.getElementById('formSignalement');
        var formData = new FormData(formulaire);
        
        formData.append('latitude', lat);
        formData.append('longitude', lng);

        document.querySelector('.popup-form').innerHTML = '<p style="text-align:center;">Traitement en cours...</p>';

        fetch('api_ajouter_signalement.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                map.closePopup(popup_active);
                window.location.reload();
            } else {
                alert("Erreur de traitement : " + data.message);
                map.closePopup(popup_active);
            }
        })
        .catch(error => {
            console.error('Erreur réseau :', error);
            alert("Une erreur de communication est survenue.");
            map.closePopup(popup_active);
        });
    }
    // Après l'initialisation, forcer Leaflet à recalculer la taille réelle du conteneur
    // (utile si la page est chargée pendant une transition CSS)
    setTimeout(function() { map.invalidateSize(); }, 300);

    // Notifier Leaflet à chaque fois que le menu hamburger change de taille
    // afin qu'il recalcule les tuiles et les dimensions de la carte.
    var hamburgerBtn = document.getElementById('hamburgerBtn');
    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', function() {
            // Délai pour attendre la fin de la transition CSS du menu (300ms)
            setTimeout(function() { map.invalidateSize(); }, 350);
        });
    }

    // Notifier aussi à chaque resize de la fenêtre
    window.addEventListener('resize', function() {
        map.invalidateSize();
    });

</script>

<?php include 'includes/footer.php'; ?>
