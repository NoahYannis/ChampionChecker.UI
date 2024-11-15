FROM tomsik68/xampp:8

# Git installieren
RUN apt-get update && apt-get install -y git

WORKDIR /opt/lampp/htdocs/ChampionChecker.UI

ARG GITHUB_PAT 

# Repo auschecken und in Container kopieren. Weitere Infos zum Erstellen und Verwenden von Perosonal Access Tokens (PAT):
# https://docs.github.com/de/packages/working-with-a-github-packages-registry/working-with-the-container-registry
RUN git clone https://$GITHUB_PAT@github.com/NoahYannis/ChampionChecker.UI.git .

EXPOSE 80
EXPOSE 443
