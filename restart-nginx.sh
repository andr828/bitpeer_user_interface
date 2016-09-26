#!/usr/bin/env bash

sudo service docker restart;
cd ./docker
docker-compose up -d
docker start -a bitpeer &
