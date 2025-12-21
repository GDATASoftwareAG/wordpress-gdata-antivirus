FROM mcr.microsoft.com/devcontainers/php:8.5

RUN docker-php-ext-install mysqli
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp