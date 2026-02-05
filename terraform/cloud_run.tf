# Backend API Service
resource "google_cloud_run_service" "backend" {
  name     = "korp-api"
  location = var.region

  template {
    spec {
      containers {
        image = "${var.region}-docker.pkg.dev/${var.project_id}/${google_artifact_registry_repository.registry.repository_id}/api:latest"
        
        env {
          name  = "APP_ENV"
          value = "production"
        }
        env {
          name  = "APP_DEBUG"
          value = "false"
        }
        env {
          name  = "LOG_CHANNEL"
          value = "stderr"
        }
        env {
          name  = "APP_URL"
          value = "https://api-korp.sparagino.it"
        }
        env {
          name  = "CLIENT_URL"
          value = "https://client-korp.sparagino.it"
        }
        env {
          name = "APP_KEY"
          value_from {
            secret_key_ref {
              name = google_secret_manager_secret.app_key.secret_id
              key  = "latest"
            }
          }
        }
        env {
          name  = "DB_CONNECTION"
          value = "mysql"
        }
        env {
          name  = "DB_HOST"
          value = google_sql_database_instance.mysql.private_ip_address
        }
        env {
          name  = "DB_DATABASE"
          value = google_sql_database.database.name
        }
        env {
          name  = "DB_USERNAME"
          value = google_sql_user.users.name
        }
        env {
          name = "DB_PASSWORD"
          value_from {
            secret_key_ref {
              name = google_secret_manager_secret.db_password.secret_id
              key  = "latest"
            }
          }
        }
        env {
          name  = "MAIL_HOST"
          value = var.mail_host
        }
        env {
          name  = "MAIL_PORT"
          value = var.mail_port
        }
        env {
          name  = "MAIL_ENCRYPTION"
          value = var.mail_encryption
        }
        env {
          name  = "MAIL_USERNAME"
          value = var.mail_username
        }
        env {
          name = "MAIL_PASSWORD"
          value_from {
            secret_key_ref {
              name = google_secret_manager_secret.mail_password.secret_id
              key  = "latest"
            }
          }
        }
        env {
          name  = "MAIL_FROM_ADDRESS"
          value = var.mail_from_address
        }

        resources {
          limits = {
            cpu    = "2000m"
            memory = "1024Mi"
          }
        }
      }
    }

    metadata {
      annotations = {
        "run.googleapis.com/vpc-access-connector" = google_vpc_access_connector.connector.name
        "run.googleapis.com/vpc-access-egress"    = "private-ranges-only"
        "run.googleapis.com/execution-environment" = "gen2"
        "run.googleapis.com/cpu-throttling"       = "false"
        "autoscaling.knative.dev/minScale"         = "1"
      }
    }
  }

  traffic {
    percent         = 100
    latest_revision = true
  }

  depends_on = [google_project_service.run, time_sleep.wait_for_apis]
}

# Frontend Client Service
resource "google_cloud_run_service" "frontend" {
  name     = "korp-client"
  location = var.region

  template {
    spec {
      containers {
        image = "${var.region}-docker.pkg.dev/${var.project_id}/${google_artifact_registry_repository.registry.repository_id}/client:latest"
        resources {
          limits = {
            cpu    = "1000m"
            memory = "256Mi"
          }
        }
      }
    }
  }

  traffic {
    percent         = 100
    latest_revision = true
  }

  depends_on = [google_project_service.run, time_sleep.wait_for_apis]
}


# Domain Mappings
resource "google_cloud_run_domain_mapping" "api_domain" {
  location = var.region
  name     = "api-korp.sparagino.it"

  metadata {
    namespace = var.project_id
  }

  spec {
    route_name = google_cloud_run_service.backend.name
  }
}

resource "google_cloud_run_domain_mapping" "client_domain" {
  location = var.region
  name     = "client-korp.sparagino.it"

  metadata {
    namespace = var.project_id
  }

  spec {
    route_name = google_cloud_run_service.frontend.name
  }
}
