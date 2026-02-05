resource "google_artifact_registry_repository" "registry" {
  location      = var.region
  repository_id = "korp-repo"
  description   = "Docker repository for Korp API"
  format        = "DOCKER"
  depends_on    = [time_sleep.wait_for_apis]
}
