FROM tomsik68/xampp:8

# Git installieren
RUN apt-get update && apt-get install -y git

WORKDIR /opt/lampp/htdocs/ChampionChecker.UI

ARG GITHUB_PAT 

# Repo auschecken und in Container kopieren. Das PAT (Personal Access Token) 
# wird aus einer lokalen .env Datei gelesen, die auf der selben Ebene wie das Dockerfile liegt.
RUN git clone https://$GITHUB_PAT@github.com/NoahYannis/ChampionChecker.UI.git .

EXPOSE 8080
EXPOSE 8081
