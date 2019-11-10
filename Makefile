#!make

include .env

REPO    ?=r.cfcr.io/seanmorris
TAG     ?=latest
PROJECT ?=thruput
TARGET  ?=development
YML_FILE?=infra/${TARGET}.yml
COMPOSE ?=export REPO=${REPO} TAG=${TAG} TARGET=${TARGET} \
	&& docker-compose -f ${YML_FILE} -p ${PROJECT}

build:
	${COMPOSE} build

dependencies:
	@ cd infra/ \
	&& docker run --rm \
		-v `pwd`/../:/app \
		composer install --ignore-platform-reqs \
			--no-interaction \
			--prefer-source
update-dependencies:
	@ cd infra/ \
	&& docker run --rm \
		-v `pwd`/../:/app \
		composer update --ignore-platform-reqs \
			--no-interaction \
			--prefer-source
# 	&& docker build ../vendor/seanmorris/subspace/infra/ \
# 		-f ../vendor/seanmorris/subspace/infra/socket.Dockerfile \
# 		-t basic-socket:latest

clean:
	@ rm -rfv ./vendor/


start:
	${COMPOSE}  up -d

start-fg:
	${COMPOSE}  up

stop:
	${COMPOSE} down

restart:
	${COMPOSE} down \
	&& ${COMPOSE} up -d

restart-fg:
	${COMPOSE} down \
	&& ${COMPOSE} up

push:
	${COMPOSE} push

pull:
	${COMPOSE} pull
