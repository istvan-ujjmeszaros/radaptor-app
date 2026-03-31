#!/bin/bash

# Default paths to analyze
paths=(app radaptor config generated public)

# Function to display help message
show_help() {
cat << EOF
Usage: $0 [options] [paths]

Options:
  -l, --level LEVEL       Set PHPStan analysis level (0-9).
  -c, --config FILE       Specify PHPStan configuration file.
  -h, --help              Display this help message.

Paths:
  Additional paths to analyze. If not provided, defaults to ${paths[*]}.
EOF
}

# Parse optional arguments
while [[ $# -gt 0 ]]; do
  key="$1"
  case $key in
    -l|--level)
      level="$2"
      shift # past argument
      shift # past value
      ;;
    -c|--config)
      config="$2"
      shift # past argument
      shift # past value
      ;;
    -h|--help)
      show_help
      exit 0  # Exit after showing help
      ;;
    *)    # unknown option
      paths+=("$1") # add path to analyze
      shift # past argument
      ;;
  esac
done

# Build the PHPStan command with options and paths
command="tools/phpstan analyse"

if [[ -n "$level" ]]; then
    command+=" -l $level"  # Add the level option
fi

if [[ -n "$config" ]]; then
    command+=" -c $config"  # Add the configuration file option
fi

command+=" ${paths[*]}"  # Add all the paths to analyze

# Execute the PHPStan command
echo "Running PHPStan with command: $command"
$command
