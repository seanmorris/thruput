#!make
.PHONY: init
MAKEFLAGS += --no-builtin-rules --always-make

SHELL   = /bin/bash
PROJECT =thruput
REPO    =r.cfcr.io/seanmorris

-include vendor/seanmorris/ids/Makefile
