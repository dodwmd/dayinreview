stack {
  name        = "github"
  description = "Manage this GitHub repo and related resources."
}


generate_hcl "_terramate_generated_main.tf" {
  content {
    module "github_repo" {
      source           = "../../modules/github"
      github_owner     = global.github_owner
      repo_name        = global.repo_name
      repo_description = global.repo_description
      repo_visibility  = global.repo_visibility
      secrets          = data.sops_file.github_secrets.data["secrets"]

      # Branch protection settings
      enable_branch_protection        = true
      protected_branch                = "main"
      enforce_admins                  = false
      allows_deletions                = false
      allows_force_pushes             = false
      strict_status_checks            = true
      required_status_check_contexts  = ["Tests", "Validate"]
      dismiss_stale_reviews           = true
      restrict_dismissals             = false
      required_approving_review_count = 1
      require_code_owner_reviews      = true
      bypass_pull_request_users       = ["dodwmd"]
      bypass_users                    = ["dodwmd"]
      bypass_teams                    = []
    }
  }
}
