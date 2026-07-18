FROM php:8.2-apache

# Installer l'extension PDO MySQL requise pour la base de données
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copier tout le contenu du projet dans le dossier du serveur apache
COPY . /var/www/html/

# Exposer le port 80
EXPOSE 80
