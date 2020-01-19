# U-Centric API

[![build status](https://gitlab.com/sis-group/u-centric/ucentric-api/badges/master/build.svg)](https://gitlab.com/sis-group/u-centric/ucentric-api/commits/master)
[![coverage report](https://gitlab.com/sis-group/u-centric/ucentric-api/badges/master/coverage.svg)](https://gitlab.com/sis-group/u-centric/ucentric-api/commits/master)

## Prerequisites

*   [Docker](https://docs.docker.com/engine/docker-overview/) >= `v18.00.0-ce`
*   [Docker Compose](https://docs.docker.com/compose/overview/) >= `v1.18.0`

## How to run

1.  Prepare `.env` files for each service.

    You may copy from the default template file (`.env.dist`) in
    the respective directory, then modify and fill in the rest.

2.  Generate [JWT](https://jwt.io/introduction/) keypair.

    ```sh
    make generate-jwt-keys
    ```

3.  Prepare the `docker-compose.override.yaml` file.

    You may copy from the default template file (`docker-compose.override.dev.yml.dist` or `docker-compose.override.prod.yml.dist`
    depending on your environment), then modify as necessary.

4.  Pull the images.

    ```sh
    docker-compose pull
    ```

    Or build them if necessary.

    ```sh
    docker-compose build --pull
    ```

5.  Create and run the services.

    ```sh
    docker-compose up -d
    ```

## Deployment checklist

*   Always use [cryptographically secure random](https://www.random.org/passwords/?num=1&len=24&format=html&rnd=new) passwords,
    and never reuse them in different deployment environments.

*   If possible, each developer should use his/her own credentials for third-party services (e.g. API key or token). Same
    goes to the different deployment environments, where appropriate.

*   JWT keys should remain the same across deployments, otherwise all previously generated JWT tokens will become invalid.
