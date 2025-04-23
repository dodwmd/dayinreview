// TERRAMATE: GENERATED AUTOMATICALLY DO NOT EDIT

module "github_repo" {
  allows_deletions    = false
  allows_force_pushes = false
  bypass_pull_request_users = [
    "dodwmd",
  ]
  bypass_teams = [
  ]
  bypass_users = [
    "dodwmd",
  ]
  dismiss_stale_reviews           = true
  enable_branch_protection        = true
  enforce_admins                  = false
  github_owner                    = "dodwmd"
  protected_branch                = "main"
  repo_description                = "Vibe Workmates Repository"
  repo_name                       = "workmates"
  repo_visibility                 = "private"
  require_code_owner_reviews      = true
  required_approving_review_count = 1
  required_status_check_contexts = [
    "Tests",
    "Validate",
  ]
  restrict_dismissals  = false
  secrets              = data.sops_file.github_secrets.data["secrets"]
  source               = "../../modules/github"
  strict_status_checks = true
}
