output "db_private_ip" {
  description = "The private IP address of the Cloud SQL instance"
  value       = google_sql_database_instance.mysql.private_ip_address
}

output "api_url" {
  description = "The URL of the API service"
  value       = google_cloud_run_service.backend.status[0].url
}

output "client_url" {
  description = "The URL of the Client service"
  value       = google_cloud_run_service.frontend.status[0].url
}
