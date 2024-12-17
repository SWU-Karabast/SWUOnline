#! /bin/bash

cd Games;
for i in $(seq 1 100);
do
    if [ -d $i ]; then rm -rf $i;
    else break;
    fi
done
cd ../HostFiles;
echo "1" > GameIDCounter.txt;
cd ..;
docker compose restart;
