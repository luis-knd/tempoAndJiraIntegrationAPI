FROM nginx:stable-alpine

ARG UID
ARG GID

ENV UID=${UID}
ENV GID=${GID}

RUN addgroup -g ${GID} --system lcandelario
RUN adduser -G lcandelario --system -D -s /bin/sh -u ${UID} lcandelario
RUN sed -i "s/user  nginx/user lcandelario/g" /etc/nginx/nginx.conf

ADD ./nginx/default.conf /etc/nginx/conf.d/

RUN mkdir -p /var/www/html