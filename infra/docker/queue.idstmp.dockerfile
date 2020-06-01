$(call TEMPLATE_SHELL, cat vendor/seanmorris/ids/infra/docker/ids.idstmp.dockerfile)

FROM idilic-${TARGET} AS queue-${TARGET}

$(call TEMPLATE_SHELL, cat infra/docker/redis.dockerfragment)
$(call TEMPLATE_SHELL, cat infra/docker/curl.dockerfragment)

COPY . /app
WORKDIR /app

RUN apt install nodejs npm --no-install-recommends -y && npm i -g prenderer@1.1.2

RUN wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb \
	&& apt install ./google-chrome-stable_current_amd64.deb --no-install-recommends -y

CMD ["-vv", "SeanMorris/ThruPut", "warmDaemon"]
