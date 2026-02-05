resource "google_sql_database_instance" "mysql" {
  name             = "korp-db"
  database_version = "MYSQL_8_0"
  region           = var.region
  depends_on       = [google_service_networking_connection.private_vpc_connection, time_sleep.wait_for_apis]

  settings {
    tier = "db-f1-micro"
    ip_configuration {
      ipv4_enabled    = false
      private_network = google_compute_network.vpc.id
    }
  }
  deletion_protection = false # Set to true for production
}

resource "google_sql_database" "database" {
  name     = "korp"
  instance = google_sql_database_instance.mysql.name
}

resource "google_sql_user" "users" {
  name     = "korp_user"
  instance = google_sql_database_instance.mysql.name
  password = var.db_password
}
