resource "google_secret_manager_secret" "app_key" {
  secret_id = "APP_KEY"
  replication {
    auto {}
  }
  depends_on = [time_sleep.wait_for_apis]
}

resource "google_secret_manager_secret_version" "app_key_version" {
  secret = google_secret_manager_secret.app_key.id
  secret_data = var.app_key
}

resource "google_secret_manager_secret" "db_password" {
  secret_id = "DB_PASSWORD"
  replication {
    auto {}
  }
  depends_on = [time_sleep.wait_for_apis]
}

resource "google_secret_manager_secret_version" "db_password_version" {
  secret = google_secret_manager_secret.db_password.id
  secret_data = var.db_password
}

resource "google_secret_manager_secret" "mail_password" {
  secret_id = "MAIL_PASSWORD"
  replication {
    auto {}
  }
  depends_on = [time_sleep.wait_for_apis]
}

resource "google_secret_manager_secret_version" "mail_password_version" {
  secret = google_secret_manager_secret.mail_password.id
  secret_data = var.mail_password
}

# Allow Cloud Run to access secrets
data "google_project" "project" {}

resource "google_project_iam_member" "secret_accessor" {
  project = var.project_id
  role    = "roles/secretmanager.secretAccessor"
  member  = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
  
  depends_on = [time_sleep.wait_for_apis]
}
