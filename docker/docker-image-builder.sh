for PHP_VERSION in 8.3; do
    for ENVIRONMENT in prod dev; do
        DOCKERFILE="Dockerfile"
        if [ "$ENVIRONMENT" == "dev" ]; then
            DOCKERFILE="Dockerfile-dev"  # Use Dockerfile-dev for dev builds
        fi

        # Build command with subdirectory and Dockerfile
        docker build --build-arg PHP_VERSION=${PHP_VERSION} --build-arg ENVIRONMENT=${ENVIRONMENT} -f php${PHP_VERSION}/${DOCKERFILE} -t phpfpm:${PHP_VERSION}-${ENVIRONMENT} .

        docker tag phpfpm:${PHP_VERSION}-${ENVIRONMENT} localhost:5555/phpfpm:${PHP_VERSION}-${ENVIRONMENT}
        docker push localhost:5555/phpfpm:${PHP_VERSION}-${ENVIRONMENT}
    done
done
