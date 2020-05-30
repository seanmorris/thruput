#!make
.PHONY: init
MAKEFLAGS += --no-builtin-rules --always-make

SHELL   = /bin/bash
PROJECT =thruput
REPO    =seanmorris

-include vendor/seanmorris/ids/Makefile
