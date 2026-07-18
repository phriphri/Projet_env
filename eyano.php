<?php
/**
 * eyano.php — Interface de l'Agent Eyano (Version Simplifiée & Moderne)
 * Kin La Verte · Plateforme environnementale Kinshasa
 */
require_once 'config/database.php';

define('EYANO_URL_LOCAL', 'http://127.0.0.1:8090');
define('EYANO_URL_PROD',  'https://agent-eyano.onrender.com');

$eyano_url = EYANO_URL_PROD ?: EYANO_URL_LOCAL;

$agent_actif = false;
$ctx = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
$health = @file_get_contents($eyano_url . '/health', false, $ctx);
if ($health !== false) {
    $data = json_decode($health, true);
    $agent_actif = isset($data['status']) && $data['status'] === 'ok';
}

include 'includes/header.php';
?>

<style>
/* ── Eyano — Simplifié ──────────────────────────────────────────────────────── */
.eyano-container {
    max-width: 760px;
    margin: 1.5rem auto;
    padding: 0 1rem;
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
}

/* En-tête */
.eyano-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--gray-100);
}
.eyano-brand {
    display: flex;
    align-items: center;
    gap: 0.8rem;
}
.eyano-avatar {
    width: 40px; height: 40px;
    background: var(--green-50);
    border: 1px solid var(--green-400);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
}
.eyano-brand h1 { font-size: 1.25rem; color: var(--green-700); margin: 0; }
.eyano-brand p { font-size: 0.82rem; color: var(--gray-500); margin: 0; }

.status-pill {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.25rem 0.65rem;
    border-radius: 99px;
    font-size: 0.75rem; font-weight: 500;
}
.status-pill.ok   { background: var(--green-50); color: #155724; }
.status-pill.off  { background: #FEE2E2; color: #991B1B; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

/* Notice d'inactivité */
.eyano-notice {
    background: #FEF3C7; border: 1px solid #FCD34D;
    border-radius: var(--radius-md);
    padding: 0.65rem 0.95rem; font-size: 0.82rem; color: #92400E;
    display: flex; gap: 0.5rem; align-items: center;
}

/* Boîte de chat */
.chat-box {
    background: var(--white);
    border: 1px solid var(--gray-100);
    border-radius: var(--radius-lg);
    display: flex; flex-direction: column;
    height: 480px;
    box-shadow: var(--shadow-sm);
}
.chat-messages {
    flex: 1; overflow-y: auto;
    padding: 1.25rem;
    display: flex; flex-direction: column;
    gap: 1rem;
}
.msg { display: flex; gap: 0.65rem; max-width: 85%; }
.msg.user  { align-self: flex-end; flex-direction: row-reverse; }
.msg.bot   { align-self: flex-start; }
.msg-icon  {
    width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; margin-top: 2px;
}
.msg.bot  .msg-icon { background: var(--green-50); border: 1px solid var(--green-400); }
.msg.user .msg-icon { background: var(--blue-50); border: 1px solid #90caf9; }
.msg-bubble {
    padding: 0.6rem 0.85rem;
    border-radius: var(--radius-md);
    font-size: 0.85rem; line-height: 1.5;
}
.msg.bot  .msg-bubble { background: var(--gray-50); color: var(--gray-900); border: 1px solid var(--gray-100); border-top-left-radius: 2px; }
.msg.user .msg-bubble { background: var(--green-700); color: var(--white); border-top-right-radius: 2px; }
.msg-bubble p { margin: 0; color: inherit; }
.msg-img { max-width: 180px; border-radius: var(--radius-sm); display: block; margin-bottom: 0.4rem; }

/* Typing indicator */
.typing { display: flex; gap: 4px; padding: 0.4rem 0; }
.typing span { width: 6px; height: 6px; border-radius: 50%; background: var(--green-400); animation: bounce 1.2s infinite; }
.typing span:nth-child(2) { animation-delay: 0.2s; }
.typing span:nth-child(3) { animation-delay: 0.4s; }
@keyframes bounce { 0%, 80%, 100% { transform: translateY(0); opacity: .4; } 40% { transform: translateY(-5px); opacity: 1; } }

/* Previews intégrées (au-dessus de la saisie) */
.media-previews {
    padding: 0.5rem 1rem;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-100);
    display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;
}
.preview-item {
    position: relative; display: inline-block;
}
.preview-item img, .preview-item video {
    height: 70px; width: 90px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--gray-200);
}
.preview-remove {
    position: absolute; top: -6px; right: -6px;
    background: #dc2626; color: white; border: none; border-radius: 50%;
    width: 18px; height: 18px; font-size: 10px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
}

/* Zone de saisie */
.chat-input-area {
    padding: 0.75rem 1rem;
    border-top: 1px solid var(--gray-100);
    display: flex; flex-direction: column; gap: 0.4rem;
}
.chat-input-row {
    display: flex; gap: 0.5rem; align-items: center;
}
#chat-input {
    flex: 1; resize: none; min-height: 38px; max-height: 80px;
    font-size: 0.85rem; padding: 0.5rem 0.75rem;
    border-radius: var(--radius-md); border: 1px solid var(--gray-200);
    line-height: 1.4;
}
#btn-send {
    padding: 0.5rem 1rem; height: 38px;
    background: var(--green-700); color: white;
    border: none; border-radius: var(--radius-md);
    cursor: pointer; font-weight: 500; font-size: 0.85rem;
    transition: background var(--transition);
}
#btn-send:hover:not(:disabled) { background: #256d2b; }
#btn-send:disabled { opacity: 0.5; cursor: not-allowed; }

.chat-actions {
    display: flex; gap: 0.4rem; align-items: center;
}
.action-btn {
    display: inline-flex; align-items: center; gap: 0.25rem;
    padding: 0.25rem 0.55rem;
    border: 1px solid var(--gray-200); border-radius: var(--radius-sm);
    font-size: 0.75rem; font-weight: 500; color: var(--gray-500);
    background: var(--white); cursor: pointer; transition: all var(--transition);
}
.action-btn:hover { border-color: var(--green-400); color: var(--green-700); background: var(--green-50); }

/* Sujets rapides */
.quick-topics {
    display: flex; gap: 0.35rem; flex-wrap: wrap; justify-content: center; margin-top: 0.5rem;
}
.quick-topic {
    padding: 0.25rem 0.6rem;
    background: var(--green-50); color: var(--green-700);
    border: 1px solid var(--green-400); border-radius: 99px;
    font-size: 0.75rem; font-weight: 500; cursor: pointer; transition: all var(--transition);
}
.quick-topic:hover { background: var(--green-700); color: white; }

#canvas-preview { display: none; }
</style>

<div class="eyano-container">

    <!-- En-tête -->
    <div class="eyano-header">
        <div class="eyano-brand">
            <div class="eyano-avatar">🌿</div>
            <div>
                <h1>Assistant Eyano</h1>
                <p>Écologie & Analyse environnementale</p>
            </div>
        </div>
        <div>
            <?php if ($agent_actif): ?>
                <span class="status-pill ok"><span class="status-dot"></span> Actif</span>
            <?php else: ?>
                <span class="status-pill off"><span class="status-dot"></span> Hors ligne</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notice Render -->
    <?php if (!$agent_actif): ?>
    <div class="eyano-notice">
        <span>⏳</span>
        <div>Le serveur se réveille (cela peut prendre 30 secondes). Envoyez un message, il arrivera dès que l'agent sera en ligne.</div>
    </div>
    <?php endif; ?>

    <!-- Chat Box -->
    <div class="chat-box">
        <div class="chat-messages" id="chat-messages">
            <div class="msg bot">
                <div class="msg-icon">🌿</div>
                <div class="msg-bubble">
                    <p>Bonjour ! Je suis <strong>Eyano</strong>. Posez-moi vos questions ou importez une photo (déchets, inondation, pollution...) pour que je l'analyse.</p>
                </div>
            </div>
        </div>

        <!-- Previews Média Intégrées -->
        <div class="media-previews" id="media-previews" style="display: none;">
            <div class="preview-item" id="preview-container">
                <!-- Rempli dynamiquement en JS (img, video) -->
            </div>
        </div>

        <!-- Zone Saisie -->
        <div class="chat-input-area">
            <div class="chat-input-row">
                <textarea id="chat-input" placeholder="Écrivez un message..." rows="1" maxlength="2000"></textarea>
                <button id="btn-send" onclick="envoyerMessage()">Envoyer ↑</button>
            </div>
            
            <div class="chat-actions">
                <button class="action-btn" onclick="document.getElementById('file-import').click()">
                    📎 Ajouter une image
                </button>
                <button class="action-btn" id="btn-camera" onclick="actionCamera()">
                    📷 Prendre une photo
                </button>
                <button class="action-btn" id="btn-snap" onclick="capturerPhoto()" style="display:none; background: var(--green-50); color: var(--green-700); border-color: var(--green-400);">
                    📸 Capturer
                </button>
                <button class="action-btn" onclick="viderChat()" style="margin-left: auto;">
                    🗑 Effacer
                </button>
            </div>

            <!-- Fichier Input Caché -->
            <input type="file" id="file-import" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="previewFichier(event)">
        </div>
    </div>

    <!-- Sujets rapides -->
    <div class="quick-topics">
        <span class="quick-topic" onclick="poserSujet(this)">Gestion des déchets</span>
        <span class="quick-topic" onclick="poserSujet(this)">Inondations à Kinshasa</span>
        <span class="quick-topic" onclick="poserSujet(this)">Érosion des sols</span>
        <span class="quick-topic" onclick="poserSujet(this)">Pollution de l'air</span>
        <span class="quick-topic" onclick="poserSujet(this)">Recyclage plastique</span>
    </div>

    <canvas id="canvas-preview"></canvas>
</div>

<script>
const EYANO_URL = <?php echo json_encode(rtrim($eyano_url, '/')); ?>;
let historique = [];
let stream_camera = null;
let imageBlobActive = null; // Contiendra le blob actif à envoyer (import ou caméra)

const chatMessages = document.getElementById('chat-messages');
const chatInput    = document.getElementById('chat-input');
const btnSend      = document.getElementById('btn-send');
const mediaPreviews = document.getElementById('media-previews');
const previewContainer = document.getElementById('preview-container');

function scrollBas() {
    chatMessages.scrollTo({ top: chatMessages.scrollHeight, behavior: 'smooth' });
}

function ajouterMessage(role, contenu, imgSrc = null) {
    const div = document.createElement('div');
    div.className = `msg ${role}`;
    const icone = role === 'user' ? '👤' : '🌿';
    let imgHtml = imgSrc ? `<img src="${imgSrc}" class="msg-img" alt="image">` : '';
    div.innerHTML = `
        <div class="msg-icon">${icone}</div>
        <div class="msg-bubble">${imgHtml}<p>${contenu}</p></div>`;
    chatMessages.appendChild(div);
    scrollBas();
}

function ajouterTyping() {
    const div = document.createElement('div');
    div.className = 'msg bot';
    div.id = 'typing-indicator';
    div.innerHTML = `<div class="msg-icon">🌿</div>
        <div class="msg-bubble"><div class="typing"><span></span><span></span><span></span></div></div>`;
    chatMessages.appendChild(div);
    scrollBas();
}

function supprimerTyping() {
    const el = document.getElementById('typing-indicator');
    if (el) el.remove();
}

function escapeHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
}

// ── Gestion Média Preview ─────────────────────────────────────────────────────
function removePreview() {
    mediaPreviews.style.display = 'none';
    previewContainer.innerHTML = '';
    imageBlobActive = null;
    document.getElementById('btn-snap').style.display = 'none';
    if (stream_camera) stopCamera();
}

function previewFichier(event) {
    const file = event.target.files[0];
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        alert('Format non supporté (JPG, PNG, WEBP uniquement).');
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        alert('Image trop volumineuse (max 5 Mo).');
        return;
    }

    imageBlobActive = file;
    const url = URL.createObjectURL(file);
    previewContainer.innerHTML = `
        <img src="${url}">
        <button class="preview-remove" onclick="removePreview()">×</button>`;
    mediaPreviews.style.display = 'block';
}

// ── Gestion Caméra ────────────────────────────────────────────────────────────
async function actionCamera() {
    if (stream_camera) {
        stopCamera();
        return;
    }

    try {
        stream_camera = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
        previewContainer.innerHTML = `
            <video id="webcam-view" autoplay playsinline></video>
            <button class="preview-remove" onclick="removePreview()">×</button>`;
        const video = document.getElementById('webcam-view');
        video.srcObject = stream_camera;
        mediaPreviews.style.display = 'block';
        document.getElementById('btn-snap').style.display = 'inline-flex';
        document.getElementById('btn-camera').textContent = '⏹ Arrêter';
    } catch (err) {
        alert("Impossible d'accéder à la caméra : " + err.message);
    }
}

function stopCamera() {
    if (stream_camera) {
        stream_camera.getTracks().forEach(t => t.stop());
        stream_camera = null;
    }
    document.getElementById('btn-camera').textContent = '📷 Prendre une photo';
    document.getElementById('btn-snap').style.display = 'none';
}

function capturerPhoto() {
    const video = document.getElementById('webcam-view');
    const canvas = document.getElementById('canvas-preview');
    if (!video) return;

    canvas.width  = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    canvas.getContext('2d').drawImage(video, 0, 0);

    canvas.toBlob(blob => {
        imageBlobActive = blob;
        const url = URL.createObjectURL(blob);
        stopCamera();
        previewContainer.innerHTML = `
            <img src="${url}">
            <button class="preview-remove" onclick="removePreview()">×</button>`;
    }, 'image/jpeg', 0.85);
}

// ── Envoi ────────────────────────────────────────────────────────────────────
async function envoyerMessage() {
    const text = chatInput.value.trim();
    if (!text && !imageBlobActive) return;

    chatInput.value = '';
    chatInput.style.height = 'auto';
    btnSend.disabled = true;

    const labelUrl = imageBlobActive ? URL.createObjectURL(imageBlobActive) : null;
    ajouterMessage('user', text ? escapeHtml(text) : 'Analyse de cette photo...', labelUrl);

    ajouterTyping();

    try {
        let resp;
        if (imageBlobActive) {
            // Requête vision (analyser-image)
            const formData = new FormData();
            formData.append('fichier', imageBlobActive, 'photo.jpg');
            formData.append('question', text || 'Analyse cette image et identifie le problème environnemental visible.');
            resp = await fetch(EYANO_URL + '/analyser-image', { method: 'POST', body: formData });
        } else {
            // Requête texte simple (chat)
            historique.push({ role: 'user', content: text });
            resp = await fetch(EYANO_URL + '/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text, historique: historique.slice(-10) })
            });
        }

        supprimerTyping();
        removePreview();

        if (!resp.ok) {
            const err = await resp.json().catch(() => ({}));
            ajouterMessage('bot', `⚠️ ${err.detail || 'Erreur du serveur.'}`);
        } else {
            const data = await resp.json();
            ajouterMessage('bot', escapeHtml(data.reponse));
            historique.push({ role: 'assistant', content: data.reponse });
        }
    } catch (e) {
        supprimerTyping();
        ajouterMessage('bot', '⏳ Le serveur se réveille ou ne répond pas. Réessayez dans un instant.');
    }

    btnSend.disabled = false;
    chatInput.focus();
}

chatInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); envoyerMessage(); }
});

chatInput.addEventListener('input', () => {
    chatInput.style.height = 'auto';
    chatInput.style.height = Math.min(chatInput.scrollHeight, 80) + 'px';
});

function poserSujet(el) {
    chatInput.value = `Parle-moi de la situation concernant : ${el.textContent}`;
    chatInput.focus();
    chatInput.dispatchEvent(new Event('input'));
}

function viderChat() {
    if (!confirm('Effacer la conversation ?')) return;
    historique = [];
    chatMessages.innerHTML = '';
    removePreview();
    ajouterMessage('bot', 'Conversation effacée. En quoi puis-je vous aider ? 🌿');
}
</script>

<?php include 'includes/footer.php'; ?>
