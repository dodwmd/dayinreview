terraform {
  required_providers {
    github = {
      source  = "integrations/github"
      version = ">= 5.0.0"
    }
  }
}

provider "github" {
  owner = var.github_owner
}

resource "github_repository" "this" {
  name        = var.repo_name
  description = var.repo_description
  visibility  = var.repo_visibility
}

resource "github_actions_secret" "repo" {
  for_each        = var.secrets
  repository      = github_repository.this.name
  secret_name     = each.key
  plaintext_value = sensitive(each.value)
}

resource "github_branch_protection" "main" {
  count = var.enable_branch_protection ? 1 : 0

  repository_id       = github_repository.this.node_id
  pattern             = var.protected_branch
  enforce_admins      = var.enforce_admins
  allows_deletions    = var.allows_deletions
  allows_force_pushes = var.allows_force_pushes

  required_status_checks {
    strict   = var.strict_status_checks
    contexts = var.required_status_check_contexts
  }

  required_pull_request_reviews {
    dismiss_stale_reviews           = var.dismiss_stale_reviews
    restrict_dismissals             = var.restrict_dismissals
    required_approving_review_count = var.required_approving_review_count
    require_code_owner_reviews      = var.require_code_owner_reviews
    pull_request_bypassers          = var.bypass_pull_request_users
  }
}

resource "github_branch_protection_bypass_push_access" "bypass" {
  count          = var.enable_branch_protection && length(var.bypass_users) > 0 ? 1 : 0
  repository     = github_repository.this.name
  branch         = var.protected_branch
  users          = var.bypass_users
  teams          = var.bypass_teams
}
