#!/bin/bash

# Install git hooks from .githooks directory

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$( cd "$SCRIPT_DIR/.." && pwd )"
HOOKS_DIR="$PROJECT_ROOT/.git/hooks"

echo "Installing git hooks..."

# Create symlink for pre-commit hook
if [ -f "$SCRIPT_DIR/pre-commit" ]; then
    ln -sf ../../.githooks/pre-commit "$HOOKS_DIR/pre-commit"
    chmod +x "$HOOKS_DIR/pre-commit"
    echo "✓ pre-commit hook installed"
else
    echo "✗ pre-commit hook not found"
fi

echo "Git hooks installation complete!"
