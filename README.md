# romaingenin.fr

Site perso one page (DA « 1b » : grille suisse, Archivo, vert forêt) en **Symfony 8 + Tailwind CSS 4**, avec un petit back-office **EasyAdmin** et un déploiement **Docker Compose / Coolify**.

La maquette d'origine (export Claude Design) est conservée dans [`design/`](design/) pour référence.

## Contenu éditable dans `/admin`

| Menu | Contenu |
|---|---|
| Profil, poste & photo | nom, poste occupé + sous-titre, photo, localisation, intro, compétences, loisirs, e-mail, téléphone, LinkedIn, Malt (+ interrupteur pour l'afficher ou non), **CV en PDF** (bouton « CV » dans le menu et « Télécharger mon CV » dans le contact, masqués sans fichier) |
| Projets | projets **pro** (grille de cartes) et **perso** (grande carte type StudioAstra) : visuel, client, rôle, technos, lien, ordre, publié/brouillon |
| Expériences | poste, entreprise, dates (fin vide = « auj. »), description, ordre, publié |
| Certifications | intitulé, précision, mise en avant (bloc plein vert) |
| Formation | diplôme, établissement, années |

Les sections vides sont masquées et la numérotation (01, 02…) se recalcule toute seule.
Les fichiers envoyés sont stockés dans `public/uploads/images` et `public/uploads/cv` (volume Docker `uploads`).

## Déploiement sur Coolify

1. **New Resource → Public/Private repository** → Build Pack **Docker Compose** (fichier `docker-compose.yaml`).
2. Dans **Environment Variables**, renseigner :
   - `ADMIN_EMAIL` : identifiant de connexion à l'admin
   - `ADMIN_PASSWORD` : 12 caractères minimum

   `SERVICE_PASSWORD_POSTGRES` et `SERVICE_PASSWORD_64_APPSECRET` sont générés automatiquement par Coolify.
3. Sur le service `app`, définir le domaine (ex. `https://romaingenin.fr`), port **80**.
4. Deploy.

Au démarrage, le conteneur :
- attend Postgres ;
- joue les migrations ;
- insère le contenu de la maquette si la base est vide (`app:seed`) ;
- crée ou met à jour le compte admin à partir de `ADMIN_EMAIL` / `ADMIN_PASSWORD` (`app:admin`).

Pour changer le mot de passe, modifie la variable puis redéploie.

Pensez à activer les **backups** du volume `database_data` (et `uploads`) dans Coolify.

## Développement local

Prérequis : PHP 8.4 (pdo_pgsql, intl), Composer, PostgreSQL.

```bash
composer install
cp .env .env.local   # puis ajuster DATABASE_URL, APP_SECRET, ADMIN_EMAIL, ADMIN_PASSWORD
php bin/console doctrine:migrations:migrate -n
php bin/console app:seed
php bin/console app:admin            # ou : app:admin moi@exemple.fr "mot-de-passe-long"
php bin/console tailwind:build --watch   # dans un autre terminal
symfony serve                            # ou : php -S 127.0.0.1:8000 -t public
```

Ou tout en Docker :

```bash
ADMIN_EMAIL=admin@example.com ADMIN_PASSWORD=changez-moi-svp \
  docker compose -f docker-compose.yaml -f docker-compose.local.yaml up --build
# → http://localhost:8080  et  http://localhost:8080/admin
```

## Où modifier quoi

- Couleurs / police (tokens Tailwind) : `assets/styles/app.css` (`@theme`)
- Page : `templates/home/index.html.twig`
- Admin : `src/Controller/Admin/`
- Contenu initial : `src/Command/SeedCommand.php` (photo : `resources/seed/photo.webp`)
- Tailwind est figé en `v4.3.0` dans `config/packages/symfonycasts_tailwind.yaml`
