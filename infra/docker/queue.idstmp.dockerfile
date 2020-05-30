$(call TEMPLATE_SHELL, cat vendor/seanmorris/ids/infra/docker/ids.idstmp.dockerfile)

FROM idilic-${TARGET} AS queue-${TARGET}

$(call TEMPLATE_SHELL, cat infra/docker/redis.dockerfragment)
$(call TEMPLATE_SHELL, cat infra/docker/curl.dockerfragment)

COPY . /app
WORKDIR /app

RUN ls -al /app

CMD ["-vv", "SeanMorris/ThruPut", "warmDaemon"]
