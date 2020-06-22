FROM tvanro/prerender-alpine:6.1.0
MAINTAINER Sean Morris <sean@seanmorr.is>

RUN set -eux; \
	cat ./server.js | grep -v removeScriptTags > ./new-server.js; \
	mv  ./new-server.js ./server.js;
