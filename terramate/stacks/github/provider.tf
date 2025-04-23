terraform {
  required_providers {
    sops = {
      source  = "carlpett/sops"
      version = ">= 0.7.1"
    }
    github = {
      source  = "integrations/github"
      version = ">= 5.0.0"
    }
  }
}

provider "sops" {}

data "sops_file" "github_secrets" {
  source_file = "${path.module}/secrets.sops.yaml"
}

output "sops_data" {
  value     = data.sops_file.github_secrets.data
  sensitive = true
}
