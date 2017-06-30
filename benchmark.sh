#!/usr/bin/env bash

port=8080

randName=$(cat /dev/random | LC_CTYPE=C tr -dc "[:lower:]" | head -c 8)

docker build -t $randName .
docker run -itd -p $port:80 --name $randName $randName

sleep 2

docker exec $randName composer install

run() {
  url="http://localhost:$port/upload/$2/Rovinj-Croatia.jpg"
  echo "$1 $url"

  echo "GET $url" | vegeta attack \
    -duration=10s \
    -rate=50 \ | vegeta report
  echo "----"
  sleep 1
}

# Run benchmark
run "Crop" "w_200,h_200,c_1"
run "Resize" "w_200,h_200,rz_1"
run "Rotate" "r_-45,w_400,h_400"

docker rm -f $randName
docker rmi -f $randName
