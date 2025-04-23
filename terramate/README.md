# Terramate Infrastructure

This directory manages infrastructure as code for the project using [Terramate](https://terramate.io/) and [Terraform](https://www.terraform.io/).

[![Terramate](https://github.com/dodwmd/dayinreview/actions/workflows/terramate.yml/badge.svg)](https://github.com/dodwmd/dayinreview/actions/workflows/terramate.yml)

## Structure

- `stacks/` - Contains Terramate stacks (e.g., `github`) for different infra components.
- `modules/` - Contains reusable Terraform modules.

## Secrets

Secrets (e.g., GitHub tokens) are managed with [SOPS](https://github.com/mozilla/sops) and stored as encrypted `.sops.yaml` files.

## State

Terraform state is stored remotely (e.g., in a GitHub-backed S3-compatible bucket).

## CI/CD Integration

The project uses GitHub Actions for CI/CD integration with Terramate:

- **Terramate Workflow**: Automatically validates and plans infrastructure changes when code is pushed to the repository. Located at [.github/workflows/terramate.yml](../../../.github/workflows/terramate.yml).
- **Permissions**: The workflow is configured with `packages: write` permission to allow pushing to GitHub Container Registry.

### Workflow Features

- **Validation**: Checks Terramate stacks for errors without applying changes
- **Planning**: Generates execution plans showing what would change if applied
- **PR Integration**: Adds plan output as comments to pull requests

## Usage

- Edit and encrypt secrets with SOPS.
- Use Terramate commands to plan/apply changes.
- For local development:
  ```bash
  # Validate changes
  terramate run -- terraform validate
  
  # Plan changes
  terramate run -- terraform plan
  ```
