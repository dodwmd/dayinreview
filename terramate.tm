terramate {
  required_version = ">= 0.4.1"

  config {
    git {
      default_remote    = "origin"
      default_branch    = "master"
      check_untracked   = false
      check_uncommitted = false
      # required to improve stability (occasional failures on Github Actons)
      check_remote = false
    }

    run {
      env {
        TF_PLUGIN_CACHE_DIR = "${terramate.root.path.fs.absolute}/terramate/.terraform-cache"
      }
    }
  }

}
