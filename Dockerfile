# On utilise une image officielle PHP avec Apache
FROM php:8.2-apache

# On active le module de réécriture d'URL (très utile pour le PHP dynamique/MVC)
RUN a2enmod rewrite

# On installe les extensions PDO et MySQL pour que PHP puisse parler à une base de données
RUN docker-php-ext-install pdo pdo_mysql

# On copie tout le contenu de votre projet local dans le dossier web du serveur
COPY . /var/www/html/

# On donne les bons droits d'accès aux fichiers
RUN chown -R www-data:www-data /var/www/html/

# On expose le port 80 (port web standard)
EXPOSE 80