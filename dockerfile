FROM tomsik68/xampp:8

# Git installieren
RUN apt-get update && apt-get install -y git

RUN curl -sS https://getcomposer.org/installer | /opt/lampp/bin/php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /opt/lampp/htdocs/ChampionChecker.UI

# Repo auschecken und in Container kopieren. Das PAT (Personal Access Token) kann auf Github generiert werden
# und wird aus einer lokalen .env Datei gelesen, die auf der selben Ebene wie das Dockerfile liegt.
RUN git clone https://$GITHUB_PAT@github.com/NoahYannis/ChampionChecker.UI.git .
RUN /usr/local/bin/composer install --no-dev --optimize-autoloader && /usr/local/bin/composer dump-autoload --optimize

EXPOSE 8080
EXPOSE 8081
