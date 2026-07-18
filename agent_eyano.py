"""
Agent Eyano — Serveur FastAPI
Analyse environnementale (texte + image) pour la plateforme Kin La Verte.
Déploiement : Render (0.0.0.0 + PORT env) ou local (127.0.0.1:8090)
"""

import os
import base64
import logging
from io import BytesIO

from fastapi import FastAPI, HTTPException, UploadFile, File
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from openai import OpenAI

# ─── Configuration ────────────────────────────────────────────────────────────

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("eyano")

OPENAI_API_KEY = os.environ.get("OPENAI_API_KEY")
if not OPENAI_API_KEY:
    logger.warning("⚠️  OPENAI_API_KEY non définie — le serveur démarrera quand même, mais les appels IA échoueront.")

# Client créé à la demande pour ne pas bloquer le démarrage
def get_client() -> OpenAI:
    key = os.environ.get("OPENAI_API_KEY")
    if not key:
        raise HTTPException(status_code=503, detail="Clé OpenAI non configurée. Définissez la variable d'environnement OPENAI_API_KEY.")
    return OpenAI(api_key=key)

# Origines autorisées pour CORS
ALLOWED_ORIGINS = os.environ.get(
    "ALLOWED_ORIGINS",
    "http://localhost,http://127.0.0.1,http://localhost:8080,http://127.0.0.1:8080"
).split(",")

# Taille max d'image : 5 Mo
MAX_IMAGE_SIZE = 5 * 1024 * 1024
ALLOWED_CONTENT_TYPES = {"image/jpeg", "image/png", "image/webp"}

SYSTEM_PROMPT = """Tu es Eyano, un assistant environnemental expert dédié à la ville de Kinshasa (RDC).
Tu aides les citoyens à comprendre et gérer les problèmes environnementaux locaux.
Tes domaines d'expertise sont :
- Gestion des déchets et ordures ménagères
- Inondations et drainage urbain
- Pollution de l'air, de l'eau et des sols
- Érosion des sols et ravinement
- Assainissement et hygiène publique
- Sensibilisation écologique

Règles :
- Réponds toujours en français, de manière claire et accessible.
- Donne des conseils pratiques adaptés au contexte africain/kinois.
- Si une image t'est fournie, analyse-la en priorité et identifie le problème environnemental visible.
- Reste bienveillant, éducatif et constructif.
- Si la question est hors sujet environnemental, redirige poliment vers ton domaine."""

# ─── Application FastAPI ───────────────────────────────────────────────────────

app = FastAPI(
    title="Agent Eyano — Kin La Verte",
    description="API d'analyse environnementale pour Kinshasa",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],          # PHP sur même serveur ou Render — ajustez si besoin
    allow_credentials=False,
    allow_methods=["GET", "POST"],
    allow_headers=["*"],
)

# ─── Modèles Pydantic ─────────────────────────────────────────────────────────

class ChatRequest(BaseModel):
    message: str
    historique: list[dict] = []   # [{"role": "user"|"assistant", "content": "..."}]

class ChatResponse(BaseModel):
    reponse: str
    tokens_utilises: int = 0

# ─── Routes ───────────────────────────────────────────────────────────────────

@app.get("/health")
async def health():
    """Vérification de l'état du serveur (utilisé par eyano.php pour détecter le réveil)."""
    return {
        "status": "ok",
        "agent": "Eyano",
        "version": "1.0.0",
        "ia_configuree": bool(OPENAI_API_KEY)
    }


@app.post("/chat", response_model=ChatResponse)
async def chat(req: ChatRequest):
    """
    Conversation textuelle avec Eyano.
    Corps JSON : { "message": "...", "historique": [...] }
    """
    if not OPENAI_API_KEY:
        raise HTTPException(status_code=503, detail="Clé OpenAI non configurée sur le serveur.")

    if not req.message or len(req.message.strip()) == 0:
        raise HTTPException(status_code=400, detail="Le message ne peut pas être vide.")

    if len(req.message) > 2000:
        raise HTTPException(status_code=400, detail="Message trop long (max 2000 caractères).")

    # Construction des messages
    messages = [{"role": "system", "content": SYSTEM_PROMPT}]

    # Ajouter l'historique (limité aux 10 derniers échanges pour rester dans le contexte)
    historique_recent = req.historique[-10:] if len(req.historique) > 10 else req.historique
    for msg in historique_recent:
        if msg.get("role") in ("user", "assistant") and msg.get("content"):
            messages.append({"role": msg["role"], "content": msg["content"]})

    messages.append({"role": "user", "content": req.message.strip()})

    try:
        openai_client = get_client()
        response = openai_client.chat.completions.create(
            model="gpt-4o-mini",
            messages=messages,
            max_tokens=800,
            temperature=0.7,
        )
        reponse_text = response.choices[0].message.content
        tokens = response.usage.total_tokens if response.usage else 0
        logger.info(f"Chat — {tokens} tokens utilisés.")
        return ChatResponse(reponse=reponse_text, tokens_utilises=tokens)

    except Exception as e:
        logger.error(f"Erreur OpenAI /chat : {e}")
        raise HTTPException(status_code=500, detail=f"Erreur lors de la génération de la réponse : {str(e)}")


@app.post("/analyser-image", response_model=ChatResponse)
async def analyser_image(
    fichier: UploadFile = File(...),
    question: str = "Analyse cette image et identifie le problème environnemental visible."
):
    """
    Analyse d'une image environnementale (JPG, PNG, WEBP — max 5 Mo).
    Paramètres : fichier (multipart) + question (form field optionnel).
    """
    if not OPENAI_API_KEY:
        raise HTTPException(status_code=503, detail="Clé OpenAI non configurée sur le serveur.")

    # Validation du type MIME
    content_type = fichier.content_type
    if content_type not in ALLOWED_CONTENT_TYPES:
        raise HTTPException(
            status_code=400,
            detail=f"Format non supporté : {content_type}. Utilisez JPG, PNG ou WEBP."
        )

    # Lecture et validation de la taille
    contenu = await fichier.read()
    if len(contenu) > MAX_IMAGE_SIZE:
        raise HTTPException(
            status_code=413,
            detail=f"Image trop volumineuse ({len(contenu) // 1024} Ko). Maximum : 5 Mo."
        )

    if len(contenu) == 0:
        raise HTTPException(status_code=400, detail="Le fichier image est vide.")

    # Encodage base64
    image_b64 = base64.b64encode(contenu).decode("utf-8")
    image_url = f"data:{content_type};base64,{image_b64}"

    # Validation de la question
    question_propre = question.strip() if question else "Analyse cette image et identifie le problème environnemental visible."
    if len(question_propre) > 500:
        question_propre = question_propre[:500]

    messages = [
        {"role": "system", "content": SYSTEM_PROMPT},
        {
            "role": "user",
            "content": [
                {
                    "type": "text",
                    "text": question_propre
                },
                {
                    "type": "image_url",
                    "image_url": {"url": image_url, "detail": "high"}
                }
            ]
        }
    ]

    try:
        openai_client = get_client()
        response = openai_client.chat.completions.create(
            model="gpt-4o",           # Vision nécessite gpt-4o
            messages=messages,
            max_tokens=1000,
            temperature=0.5,
        )
        reponse_text = response.choices[0].message.content
        tokens = response.usage.total_tokens if response.usage else 0
        logger.info(f"Image analysée ({len(contenu) // 1024} Ko) — {tokens} tokens.")
        return ChatResponse(reponse=reponse_text, tokens_utilises=tokens)

    except Exception as e:
        logger.error(f"Erreur OpenAI /analyser-image : {e}")
        raise HTTPException(status_code=500, detail=f"Erreur lors de l'analyse de l'image : {str(e)}")


# ─── Lancement ────────────────────────────────────────────────────────────────

if __name__ == "__main__":
    import uvicorn
    port = int(os.environ.get("PORT", 8090))
    host = os.environ.get("HOST", "0.0.0.0")
    logger.info(f"🌿 Agent Eyano démarré sur http://{host}:{port}")
    uvicorn.run("agent_eyano:app", host=host, port=port, reload=False)
