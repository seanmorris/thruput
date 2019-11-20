#!make

-include .env

REPO    ?=r.cfcr.io/seanmorris
PREFIX  ?=is.seanmorr.thruput
TARGET  ?=development
TAG     ?=`git describe --tags`-${TARGET}
PROJECT ?=thruput
YML_FILE?=infra/${TARGET}.yml
COMPOSE ?=export REPO=${REPO} PREFIX=${PREFIX} TAG=${TAG} TARGET=${TARGET} \
	&& docker-compose -f ${YML_FILE} -p ${PROJECT}

build:
	${COMPOSE} build
	@ ${COMPOSE} up --no-start
	@ ${COMPOSE} images -q | while read IMAGE_HASH; do \
		docker image inspect --format="{{index .RepoTags 0}}" $$IMAGE_HASH \
		| sed s/\:.*\$/// \
		| grep "^${REPO}/${PREFIX}" \
		| while read IMAGE_NAME; do \
			docker tag $$IMAGE_HASH $$IMAGE_NAME:latest-${TARGET}; \
		done; \
	done;

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
	make _push
	make _push TAG=latest-${TARGET}

_push:
	${COMPOSE} push

pull:
	${COMPOSE} pull
