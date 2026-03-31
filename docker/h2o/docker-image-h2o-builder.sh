#!/bin/bash

# Call with the --build-arg parameter to pass arguments to the docker build command:
# Example: ./docker-image-h2o-builder.sh --build-arg H2O_COMMIT=216ba9c93e3d34e9041dae169b9b3b110f08fee7

# Get the script's directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

docker build -f "${SCRIPT_DIR}/Dockerfile" "$@" -t h2o:latest "${SCRIPT_DIR}"
docker tag h2o:latest localhost:5555/h2o:latest
docker push localhost:5555/h2o:latest
