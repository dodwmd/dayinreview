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
