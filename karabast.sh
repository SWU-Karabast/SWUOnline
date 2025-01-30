#!/bin/bash

set -eo pipefail

export DOCKER_USER="$(id -u):$(id -g)"
export STAGE=dev

case $1 in
  "start")
    echo "Starting Karabast..."
    docker compose up -d
    echo "Karabast is running at http://localhost:8080/SWUOnline/MainMenu.php"
    ;;
  "stop")
    echo "Stopping Karabast..."
    docker compose down
    ;;
  "restart")
    echo "Restarting Karabast..."
    docker compose restart
    ;;
  *)
    echo "Usage: $0 {start|stop|restart}"
    exit 1
    ;;
esac
