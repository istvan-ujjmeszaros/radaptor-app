#!/bin/bash

# Call with the --build-arg parameter to pass arguments to the docker build command:
# Example: ./docker-image-builder-unified.sh --build-arg XDEBUG_COMMIT=35baf95fcef342e7f02119275facf433ded882a6

# Get the script's directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Define PHP versions and environments
PHP_VERSIONS=("8.4") # Add more versions as needed like ("8.3" "8.4")
ENVIRONMENTS=("prod" "dev")
DOCKERFILE_NAME="Dockerfile-unified"

# Loop through each PHP version and environment
for PHP_VERSION in "${PHP_VERSIONS[@]}"; do
    for ENVIRONMENT in "${ENVIRONMENTS[@]}"; do
        echo "-------------------------------------------"
        echo "Building PHP-FPM image for PHP ${PHP_VERSION} in ${ENVIRONMENT} environment..."
        echo "-------------------------------------------"

        # Define Dockerfile path based on PHP version, relative to script location
        DOCKERFILE_PATH="${SCRIPT_DIR}/php${PHP_VERSION}/${DOCKERFILE_NAME}"

        # Define image tags
        IMAGE_TAG="phpfpm:${PHP_VERSION}-${ENVIRONMENT}"
        REGISTRY_TAG="localhost:5555/phpfpm:${PHP_VERSION}-${ENVIRONMENT}"

        # Determine build target based on environment
        if [ "$ENVIRONMENT" == "dev" ]; then
            TARGET="dev"
        else
            TARGET="prod"
        fi

        # Build the Docker image with the appropriate target and additional arguments
        docker build \
            --build-arg PHP_VERSION="${PHP_VERSION}" \
            --build-arg ENVIRONMENT="${ENVIRONMENT}" \
            "$@" \
            --target ${TARGET} \
            -f "${DOCKERFILE_PATH}" \
            -t "${IMAGE_TAG}" \
            "${SCRIPT_DIR}"

        # Tag the image for the registry
        docker tag "${IMAGE_TAG}" "${REGISTRY_TAG}"

        # Push the image to the registry
        docker push "${REGISTRY_TAG}"

        echo "Successfully built and pushed ${REGISTRY_TAG}"
        echo
    done
done
