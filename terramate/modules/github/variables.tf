variable "github_owner" {
  description = "GitHub owner/org."
  type        = string
}

variable "repo_name" {
  description = "Repository name."
  type        = string
}

variable "repo_description" {
  description = "Repository description."
  type        = string
  default     = ""
}

variable "repo_visibility" {
  description = "Repository visibility (public/private)."
  type        = string
  default     = "private"
}

variable "secrets" {
  description = "Map of repo secrets from SOPS. Keys are the repo secret names, values are the secret values."
  type        = map(string)
  sensitive   = false
}

# Branch protection variables
variable "enable_branch_protection" {
  description = "Enable branch protection rules"
  type        = bool
  default     = true
}

variable "protected_branch" {
  description = "Branch to protect (e.g., main)"
  type        = string
  default     = "main"
}

variable "enforce_admins" {
  description = "Enforce branch protection for administrators"
  type        = bool
  default     = false
}

variable "allows_deletions" {
  description = "Allow branch deletion"
  type        = bool
  default     = false
}

variable "allows_force_pushes" {
  description = "Allow force pushes"
  type        = bool
  default     = false
}

variable "strict_status_checks" {
  description = "Require status checks to pass before merging"
  type        = bool
  default     = true
}

variable "required_status_check_contexts" {
  description = "List of status checks required to pass"
  type        = list(string)
  default     = ["Tests", "Validate"]
}

variable "dismiss_stale_reviews" {
  description = "Dismiss approving reviews when someone pushes a new commit"
  type        = bool
  default     = true
}

variable "restrict_dismissals" {
  description = "Restrict who can dismiss pull request reviews"
  type        = bool
  default     = false
}

variable "required_approving_review_count" {
  description = "Number of approvals required to merge"
  type        = number
  default     = 1
}

variable "require_code_owner_reviews" {
  description = "Require approval from code owners"
  type        = bool
  default     = true
}

variable "bypass_pull_request_users" {
  description = "List of usernames that can bypass pull request requirements"
  type        = list(string)
  default     = ["dodwmd"]
}

variable "bypass_users" {
  description = "List of usernames that can bypass branch protection"
  type        = list(string)
  default     = ["dodwmd"]
}

variable "bypass_teams" {
  description = "List of team slugs that can bypass branch protection"
  type        = list(string)
  default     = []
}
