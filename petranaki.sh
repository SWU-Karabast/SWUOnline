#!/bin/bash

set -eo pipefail

export DOCKER_USER="$(id -u):$(id -g)"
export STAGE=dev

case $1 in
  "start")
    echo "Starting Petranaki..."
    docker compose up -d
    echo "Petranaki is running at http://localhost:8080/Arena/MainMenu.php"
    ;;
  "stop")
    echo "Stopping Petranaki..."
    docker compose down
    ;;
  "restart")
    echo "Restarting Petranaki..."
    docker compose restart
    ;;
  *)
    echo "Usage: $0 {start|stop|restart}"
    exit 1
    ;;
esac
