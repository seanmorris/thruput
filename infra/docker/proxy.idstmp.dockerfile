$(call TEMPLATE_SHELL, cat vendor/seanmorris/ids/infra/docker/ids.idstmp.dockerfile)

FROM server-${TARGET} AS proxy-${TARGET}

COPY . /app
WORKDIR /app

RUN ls -al /app

$(call TEMPLATE_SHELL, cat infra/docker/redis.dockerfragment)
$(call TEMPLATE_SHELL, cat infra/docker/curl.dockerfragment)
