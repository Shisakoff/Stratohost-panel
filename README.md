# StratoHost

Panel de gestion de serveurs de jeux (type Pterodactyl), pensé pour être
facile à installer. Deux composants :

- **`panel/`** — l'interface web centrale (Laravel + Vue), gère les
  comptes, les nodes, les serveurs, etc.
- **`agent/`** — le démon (Go) installé sur chaque node, qui pilote les
  conteneurs Docker des serveurs de jeu.

OS supportés par les installeurs : **Debian 12** et **Ubuntu 24.04**.

## Installer le panel

Sur la machine qui hébergera le panel :

```bash
git clone --depth 1 https://github.com/Shisakoff/Stratohost-panel.git
cd Stratohost-panel/installer
sudo ./panel-install.sh
```

Le script installe Docker si besoin, génère un `.env`, build et démarre les
conteneurs (panel, base de données, redis, queue, scheduler), te demande de
créer le premier compte admin, puis - par défaut - enregistre **cette même
machine** comme node et installe l'agent dessus, sans rien à copier-coller :
en une seule commande tu obtiens un panel fonctionnel avec un node prêt à
héberger des serveurs de jeu.

## Ajouter un node séparé (autre machine)

Si tu veux un node sur une autre machine que le panel, réponds `n` à la
question posée par `panel-install.sh`, puis depuis le conteneur du panel :

```bash
docker compose exec panel php artisan stratohost:node:create
```

La commande affiche un token à usage unique et la commande complète à
lancer **sur le node** :

```bash
git clone --depth 1 https://github.com/Shisakoff/Stratohost-panel.git
cd Stratohost-panel/installer
sudo ./agent-install.sh --panel-url=... --node-uuid=... --token-id=... --token=...
```

## État actuel du projet

Ce dépôt est en construction incrémentale : voir les phases dans les
commits. La Phase 0 (scaffolding, modèle nodes, agent qui boot et
s'authentifie auprès du panel) est en place ; la gestion réelle des
conteneurs Docker (créer/démarrer/arrêter un serveur de jeu) arrive en
Phase 1.
