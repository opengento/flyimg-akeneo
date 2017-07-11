#!/usr/bin/env bash

# check if vegeta installed
vegeta -version >/dev/null 2>&1 || { echo >&2 "Benchmark require Vegeta but it's not installed. Aborting."; exit 1; }

# which port
port=8080
# a random name for the container and the image
randName=$(cat /dev/random | LC_CTYPE=C tr -dc "[:lower:]" | head -c 8)

# build the image
docker build -t $randName .

# run the container
docker run -itd -p $port:80 --name $randName $randName

# sleep 2 sec until the container is ready
sleep 2

# install php dependencies
docker exec $randName composer install --no-dev --optimize-autoloader

# run vegeta attack
run() {
  url="http://localhost:$port/upload/$2/Rovinj-Croatia.jpg"
  echo "$1 $url"

  echo "GET $url" | vegeta attack \
    -duration=10s \
    -rate=50 \ | vegeta report
  echo "----"
  sleep 1
}

# run benchmark
run "Crop" "w_200,h_200,c_1"
run "Resize" "w_200,h_200,rz_1"
run "Rotate" "r_-45,w_400,h_400"

# remove the container and the image
docker rm -f $randName
docker rmi -f $randName
