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
    }
  }
}
