FROM php:8.2-apache

# Copie tout le contenu de votre projet dans le dossier web du serveur
COPY . /var/www/html/

# Expose le port 80 pour que le site soit accessible
EXPOSE 80
