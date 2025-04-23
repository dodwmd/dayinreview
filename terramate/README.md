# Terramate Infrastructure

This directory manages infrastructure as code for the project using [Terramate](https://terramate.io/) and [Terraform](https://www.terraform.io/).

## Structure

- `stacks/` - Contains Terramate stacks (e.g., `github`) for different infra components.
- `modules/` - Contains reusable Terraform modules.

## Secrets

Secrets (e.g., GitHub tokens) are managed with [SOPS](https://github.com/mozilla/sops) and stored as encrypted `.sops.yaml` files.

## State

Terraform state is stored remotely (e.g., in a GitHub-backed S3-compatible bucket).

## Usage

- Edit and encrypt secrets with SOPS.
- Use Terramate commands to plan/apply changes.
