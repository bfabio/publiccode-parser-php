FROM debian:12-slim

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    php-cli php-dev php-ffi composer make curl \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sSL https://go.dev/dl/go1.24.6.linux-amd64.tar.gz | tar -C /usr/local -xz \
    && ln -sf /usr/local/go/bin/go /usr/bin/go

WORKDIR /app

COPY . .

CMD make build && \
    composer install --prefer-dist --no-interaction && \
    composer run test
