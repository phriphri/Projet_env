<?php
require_once 'config/database.php';

// 1. Signalements actifs (vérifiés + en attente de vérification)
$stmtActifs = $pdo->query("SELECT COUNT(*) FROM signalements WHERE statut IN ('En attente', 'Vérifié')");
$actifs = $stmtActifs->fetchColumn() ?: 0;

// 2. Moyenne par jour
$stmtMoyenne = $pdo->query("SELECT COUNT(*) as total, MIN(DATE(date_creation)) as premier_jour FROM signalements");
$rowMoy = $stmtMoyenne->fetch();
$moyenne = 0;
if ($rowMoy['total'] > 0 && $rowMoy['premier_jour']) {
    $jours = max(1, (strtotime(date('Y-m-d')) - strtotime($rowMoy['premier_jour'])) / 86400 + 1);
    $moyenne = round($rowMoy['total'] / $jours, 1);
}

// 3. Zones de collecte (Demandes de collecte actives)
$stmtCollecte = $pdo->query("SELECT COUNT(*) FROM signalements WHERE type = 'Demande de collecte' AND statut IN ('En attente', 'Vérifié')");
$collectes = $stmtCollecte->fetchColumn() ?: 0;

// -- LOGIQUE SANTE ECOLOGIQUE --
$seuils_sante = [
    'stable' => 10,
    'surveillance' => 25,
    'degradee' => 50
];

if ($actifs <= $seuils_sante['stable']) {
    $sante_etat = "Stable";
    $sante_emoji = "🟢";
    $sante_desc = "La situation est sous contrôle.";
    $sante_color = "var(--green-700)";
    $sante_bg = "var(--green-50)";
} elseif ($actifs <= $seuils_sante['surveillance']) {
    $sante_etat = "Sous surveillance";
    $sante_emoji = "🟡";
    $sante_desc = "Quelques problèmes signalés, attention requise.";
    $sante_color = "#e65100";
    $sante_bg = "var(--orange-50)";
} elseif ($actifs <= $seuils_sante['degradee']) {
    $sante_etat = "Dégradée";
    $sante_emoji = "🟠";
    $sante_desc = "De nombreux signalements. Actions urgentes nécessaires.";
    $sante_color = "#e65100";
    $sante_bg = "var(--orange-50)";
} else {
    $sante_etat = "Critique";
    $sante_emoji = "🔴";
    $sante_desc = "Situation alarmante. Intervention prioritaire globale.";
    $sante_color = "#dc2626";
    $sante_bg = "#FEE2E2";
}

// -- METEO (Kinshasa) via Open-Meteo --
$meteo_url = "https://api.open-meteo.com/v1/forecast?latitude=-4.3276&longitude=15.3136&current_weather=true&daily=precipitation_sum&timezone=Africa/Kinshasa";
$meteo_data = @file_get_contents($meteo_url);
if ($meteo_data) {
    $meteo = json_decode($meteo_data, true);
    $temp = $meteo['current_weather']['temperature'] ?? 25; 
    $pluie = $meteo['daily']['precipitation_sum'][0] ?? 0;
} else {
    $temp = 25;
    $pluie = 0;
}

// Logique Risque Température
if ($temp <= 30) {
    $temp_risque = "Risque faible";
    $temp_color = "var(--green-700)";
    $temp_desc = "Température supportable, pas d'alerte canicule.";
} elseif ($temp <= 35) {
    $temp_risque = "Risque modéré";
    $temp_color = "var(--orange-500)";
    $temp_desc = "Il fait chaud. Pensez à vous hydrater et à rester à l'ombre.";
} else {
    $temp_risque = "Risque élevé";
    $temp_color = "#dc2626";
    $temp_desc = "Forte chaleur. Risque élevé de déshydratation et de feux de brousse.";
}

// Logique Risque Pluie
if ($pluie <= 10) {
    $pluie_risque = "Risque faible";
    $pluie_color = "var(--green-700)";
    $pluie_desc = "Peu ou pas de pluie prévue aujourd'hui.";
} elseif ($pluie <= 30) {
    $pluie_risque = "Risque modéré";
    $pluie_color = "var(--orange-500)";
    $pluie_desc = "Pluie modérée. Attention aux accumulations d'eau.";
} else {
    $pluie_risque = "Risque élevé";
    $pluie_color = "#dc2626";
    $pluie_desc = "Fortes pluies. Risque d'inondations, prudence recommandée.";
}

include 'includes/header.php';
?>

<!-- Animations et styles épurés et structurés -->
<style>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animated-section {
    animation: fadeInUp 0.5s ease-out both;
}

.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.2s; }
.delay-3 { animation-delay: 0.3s; }

.main-layout {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 2.5rem;
    margin: 2rem 0;
}

@media (max-width: 768px) {
    .main-layout {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
}

.section-card {
    background: #ffffff;
    border: 1px solid #eaeaea;
    border-radius: 8px;
    padding: 1.8rem;
    margin-bottom: 1.5rem;
    transition: border-color 0.2s ease, transform 0.2s ease;
}

.section-card:hover {
    border-color: var(--green-300);
    transform: translateY(-1px);
}

.badge-green {
    display: inline-block;
    background: #e8f5e9;
    color: var(--green-700);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.25rem 0.6rem;
    border-radius: 4px;
    text-transform: uppercase;
    margin-bottom: 0.75rem;
}

.issue-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.issue-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px dashed #eee;
}

.issue-item:last-child {
    border-bottom: none;
}

.issue-icon {
    font-size: 1.2rem;
    line-height: 1;
}

.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.stat-box {
    border: 1px solid #eee;
    border-radius: 6px;
    padding: 1rem;
    text-align: center;
    background: #fafafa;
    transition: all 0.2s ease;
}

.stat-box:hover {
    background: #fff;
    border-color: var(--green-500);
}

.stat-val {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--gray-800);
}

.stat-lbl {
    font-size: 0.75rem;
    color: var(--gray-500);
    text-transform: uppercase;
    margin-top: 0.25rem;
    letter-spacing: 0.3px;
}

.meteo-panel {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-top: 1rem;
}

@media (max-width: 480px) {
    .meteo-panel {
        grid-template-columns: 1fr;
    }
}

.meteo-item {
    background: #fbfbfb;
    border: 1px solid #f0f0f0;
    border-radius: 6px;
    padding: 1rem;
}
</style>

<div class="animated-section" style="max-width: 1000px; margin: 3rem auto; padding: 0 1.5rem; box-sizing: border-box; width: 100%;">
    
    <!-- En-tête accrocheur mais sobre -->
    <div style="margin-bottom: 2.5rem;">
        <span class="badge-green">Plateforme Citoyenne & Étudiante</span>
        <h1 style="color: var(--green-700); font-size: clamp(1.6rem, 5vw, 2.5rem); margin: 0.5rem 0; font-weight: 800; letter-spacing: -0.5px;">Kin La Verte</h1>
        <p style="font-size: 1.15rem; color: var(--gray-700); line-height: 1.5; max-width: 700px;">
            Suivi environnemental, cartographie des alertes et ressources éducatives pour la préservation écologique de Kinshasa.
        </p>
    </div>

    <!-- Layout principal asymétrique -->
    <div class="main-layout">
        
        <!-- Colonne Gauche (Présentation & Contexte) -->
        <div class="animated-section delay-1">
            <div class="section-card">
                <h2 style="font-size: 1.3rem; color: var(--gray-800); margin-bottom: 0.75rem; font-weight: 700;">La situation à Kinshasa</h2>
                <p style="font-size: 0.95rem; color: var(--gray-600); line-height: 1.6; margin: 0;">
                    La ville de Kinshasa fait face à une urgence écologique majeure. La croissance rapide de la population, combinée à des infrastructures limitées, met une forte pression sur l'environnement urbain. Il est de notre devoir, en tant qu'étudiants et citoyens, de collecter les données et d'agir pour restaurer notre écosystème.
                </p>
            </div>

            <div class="section-card">
                <h2 style="font-size: 1.3rem; color: var(--gray-800); margin-bottom: 0.75rem; font-weight: 700;">📍 Les 24 Communes couvertes</h2>
                <p style="font-size: 0.95rem; color: var(--gray-600); line-height: 1.6; margin: 0;">
                    De Gombe à Maluku, en passant par Kalamu et Lemba, Kin La Verte surveille et agrège les données environnementales des 24 communes de la capitale congolaise. Notre cartographie collaborative permet de visualiser chaque alerte en temps réel.
                </p>
            </div>
        </div>

        <!-- Colonne Droite (Enjeux & Météo) -->
        <div class="animated-section delay-2">
            <div class="section-card">
                <h2 style="font-size: 1.3rem; color: var(--gray-800); margin-bottom: 1rem; font-weight: 700;">⚠️ Les 4 enjeux majeurs</h2>
                <div class="issue-list">
                    <div class="issue-item">
                        <span class="issue-icon">🗑️</span>
                        <div>
                            <strong style="color: var(--gray-800); font-size: 0.9rem;">Gestion des Déchets</strong>
                            <p style="font-size: 0.8rem; color: var(--gray-500); margin: 0.1rem 0 0 0;">Tri insuffisant et prolifération des décharges sauvages.</p>
                        </div>
                    </div>
                    <div class="issue-item">
                        <span class="issue-icon">🌊</span>
                        <div>
                            <strong style="color: var(--gray-800); font-size: 0.9rem;">Inondations récurrentes</strong>
                            <p style="font-size: 0.8rem; color: var(--gray-500); margin: 0.1rem 0 0 0;">Caniveaux bouchés et crues rapides lors des fortes pluies.</p>
                        </div>
                    </div>
                    <div class="issue-item">
                        <span class="issue-icon">🏭</span>
                        <div>
                            <strong style="color: var(--gray-800); font-size: 0.9rem;">Pollution globale</strong>
                            <p style="font-size: 0.8rem; color: var(--gray-500); margin: 0.1rem 0 0 0;">Dégradation continue de la qualité de l'air et des rivières.</p>
                        </div>
                    </div>
                    <div class="issue-item">
                        <span class="issue-icon">⛰️</span>
                        <div>
                            <strong style="color: var(--gray-800); font-size: 0.9rem;">Érosion des sols</strong>
                            <p style="font-size: 0.8rem; color: var(--gray-500); margin: 0.1rem 0 0 0;">Glissements de terrain dus à la déforestation urbaine.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Météo & Prévention -->
    <div class="animated-section delay-2" style="margin-bottom: 2rem;">
        <div class="section-card">
            <h2 style="font-size: 1.3rem; color: var(--gray-800); margin-bottom: 0.5rem; font-weight: 700;">🌤️ Météo & Prévention</h2>
            <div class="meteo-panel">
                <div class="meteo-item">
                    <div style="font-size: 1.1rem; font-weight: bold; color: var(--gray-800);">🌡️ Température</div>
                    <div style="font-size: 1.3rem; font-weight: 800; color: <?php echo $temp_color; ?>; margin: 0.2rem 0;"><?php echo $temp; ?> °C</div>
                    <div style="font-size: 0.85rem; color: var(--gray-600);"><?php echo $temp_risque; ?> - <?php echo $temp_desc; ?></div>
                </div>
                <div class="meteo-item">
                    <div style="font-size: 1.1rem; font-weight: bold; color: var(--gray-800);">🌧️ Précipitations</div>
                    <div style="font-size: 1.3rem; font-weight: 800; color: <?php echo $pluie_color; ?>; margin: 0.2rem 0;"><?php echo $pluie; ?> mm</div>
                    <div style="font-size: 0.85rem; color: var(--gray-600);"><?php echo $pluie_risque; ?> - <?php echo $pluie_desc; ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Statistiques -->
    <div class="animated-section delay-3" style="margin-top: 1rem;">
        <h2 style="font-size: 1.4rem; color: var(--gray-800); margin-bottom: 0.5rem; font-weight: 700; border-bottom: 2px solid #eee; padding-bottom: 0.5rem;">Statistiques de la plateforme</h2>
        <div class="stat-grid">
            <div class="stat-box">
                <div class="stat-val" style="color: <?php echo $sante_color; ?>;"><?php echo $sante_etat; ?></div>
                <div class="stat-lbl">Santé écologique</div>
            </div>
            <div class="stat-box">
                <div class="stat-val"><?php echo $actifs; ?></div>
                <div class="stat-lbl">Alertes actives</div>
            </div>
            <div class="stat-box">
                <div class="stat-val"><?php echo $moyenne; ?></div>
                <div class="stat-lbl">Alertes / jour</div>
            </div>
            <div class="stat-box">
                <div class="stat-val"><?php echo $collectes; ?></div>
                <div class="stat-lbl">Collectes en cours</div>
            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
