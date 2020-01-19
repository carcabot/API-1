FROM xlazer/api-platform

ENV PHPIREDIS_FILENAME phpiredis.tar.gz

RUN set -eux \
	&& apk add --no-cache \
        libssl1.0 \
		hiredis-dev \
	&& apk add --no-cache --virtual .build-deps \
		$PHPIZE_DEPS \
	    libxml2-dev \
        openssl-dev \
	&& docker-php-ext-install -j$(nproc) \
		soap \
		bcmath \
	&& pecl install \
		ds \
		mongodb \
	&& curl -fSL "https://api.github.com/repos/nrk/phpiredis/tarball" -o "$PHPIREDIS_FILENAME" \
	&& mkdir -p /usr/local/src/phpiredis \
	&& tar -xf "$PHPIREDIS_FILENAME" -C /usr/local/src/phpiredis --strip-components=1 \
	&& rm "$PHPIREDIS_FILENAME" \
	&& cd /usr/local/src/phpiredis \
	&& phpize \
	&& gnuArch="$(dpkg-architecture --query DEB_BUILD_GNU_TYPE)" \
	&& ./configure \
		--build="$gnuArch" \
		--enable-phpiredis \
	&& make -j "$(nproc)" \
	&& make install \
	&& cd /root \
	&& rm -rf /usr/local/src/phpiredis \
	&& pecl clear-cache \
	&& docker-php-ext-enable \
		ds \
		mongodb \
	&& apk del .build-deps

ENV CONFD_VERSION 0.11.0

RUN curl -fSL https://github.com/kelseyhightower/confd/releases/download/v$CONFD_VERSION/confd-$CONFD_VERSION-linux-amd64 -o /usr/local/bin/confd \
	&& chmod +x /usr/local/bin/confd

# https://blackfire.io/docs/integrations/docker#enabling-the-php-probe
RUN version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") \
	&& curl -A "Docker" -o blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/alpine/amd64/$version \
	&& mkdir blackfire-probe \
	&& tar -xzpf blackfire-probe.tar.gz -C blackfire-probe \
	&& rm blackfire-probe.tar.gz \
	&& mv blackfire-probe/blackfire*.so $(php -r "echo ini_get('extension_dir');")/blackfire.so \
	&& rm -r blackfire-probe

# COPY docker/app/php.ini /usr/local/etc/php/php.ini
# COPY docker/app/php-fpm.d/child-processes.conf /usr/local/etc/php-fpm.d/child-processes.conf

WORKDIR /srv/api

# build for production
ARG APP_ENV=prod

# prevent the reinstallation of vendors at every changes in the source code
COPY composer.json composer.lock ./
RUN set -eux; \
	composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress --no-suggest; \
	composer clear-cache

COPY . ./

RUN set -eux; \
	composer dump-autoload --classmap-authoritative --no-dev

RUN set -eux; \
	mkdir -p public/uploads public/internal \
	&& mkdir -p var/cache var/log var/tmp

RUN apk add --no-cache --virtual .healthcheck-deps \
		fcgi

RUN curl -fSL https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck -o /usr/local/bin/docker-healthcheck \
	&& chmod +x /usr/local/bin/docker-healthcheck

HEALTHCHECK CMD ["docker-healthcheck"]

VOLUME /srv/api/public/uploads /srv/api/public/internal

COPY docker/app/confd /usr/local/etc/confd/
COPY docker/app/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY docker/app/docker-worker-entrypoint.sh /usr/local/bin/docker-worker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-worker-entrypoint
