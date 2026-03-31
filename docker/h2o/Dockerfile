FROM debian:bullseye-slim AS builder
WORKDIR /h2o

ARG H2O_COMMIT=a7ee72ea84815f393353e66c02181832fcd8dbb3
#ARG H2O_COMMIT=master

RUN apt-get update
RUN apt-get purge -y libstdc++-8-doc libstdc++6-8-dbg || true
RUN apt-get install -y --no-install-recommends \
    mc \
    htop \
    git build-essential ruby ruby-dev bison make cmake ca-certificates perl libssl-dev zlib1g-dev \
    && update-ca-certificates \
    && gem install rake
RUN git clone https://github.com/h2o/h2o.git . \
    && git checkout ${H2O_COMMIT}
RUN cmake -DWITH_MRUBY=on -DWITH_BUNDLED_SSL=on
RUN make -j 16 install
RUN apt-get install -y --no-install-recommends openssl \
    && mkdir /etc/h2o /etc/h2o/certs \
    && openssl req -x509 -newkey rsa:4096 -sha256 -days 365 -nodes \
        -keyout /etc/h2o/certs/localhost.key -out /etc/h2o/certs/localhost.crt \
        -subj "/CN=localhost" \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

FROM debian:bullseye-slim AS prod
WORKDIR /h2o
RUN apt-get update && apt-get install -y --no-install-recommends ca-certificates openssl perl \
    && chown www-data:www-data /h2o \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
ENV PUBLIC_FOLDER=/app
COPY --from=builder /etc/h2o/certs/localhost.key /h2o
COPY --from=builder /etc/h2o/certs/localhost.crt /h2o
COPY --from=builder /h2o/h2o /h2o
COPY --from=builder /usr/local/share/h2o /usr/local/share/h2o
COPY ./h2o.conf /config/
EXPOSE 80
EXPOSE 443

CMD ["/h2o/h2o", "--conf", "/config/h2o.conf"]
