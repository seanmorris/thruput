#!make

REPO ?=r.cfcr.io/seanmorris
TAG  ?=latest

DOCKER_COMMAND ?= export REPO=${REPO} TAG=${TAG} \
	&& docker-compose

build:
	@ cd infra/ \
	&& ${DOCKER_COMMAND} build

dependencies:
	@ cd infra/ \
	&& docker run --rm \
		-v `pwd`/../:/app \
		composer install --ignore-platform-reqs \
			--no-interaction \
			--prefer-source \
	&& docker build ../vendor/seanmorris/subspace/infra/ \
		-f ../vendor/seanmorris/subspace/infra/socket.Dockerfile \
		-t basic-socket:latest

clean:
	@ rm -rfv ./vendor/


start:
	@ cd infra/ \
	&& ${DOCKER_COMMAND} up -d

start-fg:
	@ cd infra/ \
	&& ${DOCKER_COMMAND} up

stop:
	@ cd infra/ \
	&& ${DOCKER_COMMAND} down

restart:
	@ cd infra/ \
	&& ${DOCKER_COMMAND} down \
	&& ${DOCKER_COMMAND} up -d

push:
	@ cd infra/ \
	&& ${DOCKER_COMMAND} push

pull:
	@ cd infra/ \
	&& ${DOCKER_COMMAND} pull
