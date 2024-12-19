#! /bin/bash

cd Games;
for i in $(seq 1 20);
do
    if [ -d "$i" ]; then rm -rf "$i";
    fi
done
cd ../HostFiles;
echo "1" > GameIDCounter.txt;
cd ..;
docker compose restart;
