#!/bin/bash
# MediTrack IoT - Script de démarrage rapide
# Usage: ./start.sh

set -e

CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${CYAN}"
echo "╔══════════════════════════════════════╗"
echo "║     🏥 MediTrack IoT - Démarrage     ║"
echo "╚══════════════════════════════════════╝"
echo -e "${NC}"

# ─── Vérifier les prérequis ────────────────────────────
echo -e "${YELLOW}→ Vérification des prérequis...${NC}"

command -v php >/dev/null 2>&1 || { echo -e "${RED}❌ PHP non trouvé${NC}"; exit 1; }
command -v composer >/dev/null 2>&1 || { echo -e "${RED}❌ Composer non trouvé${NC}"; exit 1; }
command -v node >/dev/null 2>&1 || { echo -e "${RED}❌ Node.js non trouvé${NC}"; exit 1; }
command -v mosquitto >/dev/null 2>&1 || echo -e "${YELLOW}⚠️  Mosquitto non trouvé - simulation MQTT désactivée${NC}"

echo -e "${GREEN}✅ Prérequis OK${NC}"

# ─── Setup Backend ─────────────────────────────────────
echo ""
echo -e "${YELLOW}→ Configuration du backend...${NC}"

cd backend

if [ ! -f ".env" ]; then
    cp .env.example .env
    echo -e "${YELLOW}⚠️  Fichier .env créé - veuillez configurer DB_DATABASE, DB_USERNAME, DB_PASSWORD${NC}"
fi

if [ ! -d "vendor" ]; then
    echo "Installation des dépendances PHP..."
    composer install --no-interaction --optimize-autoloader
fi

php artisan key:generate --no-interaction 2>/dev/null || true
php artisan migrate --seed --no-interaction 2>/dev/null || echo -e "${YELLOW}⚠️  Migration ignorée (DB peut-être non configurée)${NC}"

cd ..

# ─── Setup Frontend ────────────────────────────────────
echo ""
echo -e "${YELLOW}→ Configuration du frontend...${NC}"

cd frontend

if [ ! -f ".env" ]; then
    cp .env.example .env
fi

if [ ! -d "node_modules" ]; then
    echo "Installation des dépendances Node.js..."
    npm install
fi

cd ..

# ─── Lancement ─────────────────────────────────────────
echo ""
echo -e "${GREEN}✅ Configuration terminée !${NC}"
echo ""
echo -e "${CYAN}╔══════════════════════════════════════════════════════╗"
echo -e "║  Lancez chaque commande dans un terminal séparé      ║"
echo -e "╠══════════════════════════════════════════════════════╣"
echo -e "║  1. cd backend && php artisan serve                  ║"
echo -e "║  2. cd backend && php artisan reverb:start           ║""
echo -e "║  3. cd backend && php artisan queue:work             ║"
echo -e "║  4. cd backend && php artisan mqtt:listen            ║"
echo -e "║  5. cd frontend && npm run dev                       ║"
echo -e "╠══════════════════════════════════════════════════════╣"
echo -e "║  Frontend → http://localhost:3000                    ║"
echo -e "║  API      → http://localhost:8000/api                ║"
echo -e "║  WebSocket→ ws://localhost:8080                      ║"
echo -e "╚══════════════════════════════════════════════════════╝${NC}"
echo ""
