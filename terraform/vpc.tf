resource "google_compute_network" "vpc" {
  name                    = "korp-vpc"
  auto_create_subnetworks = false
  depends_on              = [time_sleep.wait_for_apis]
}

resource "google_compute_subnetwork" "subnet" {
  name          = "korp-subnet"
  ip_cidr_range = "10.0.0.0/24"
  network       = google_compute_network.vpc.id
  region        = var.region
}

# Required for Cloud Run to access Cloud SQL via private IP
resource "google_vpc_access_connector" "connector" {
  name          = "korp-connector"
  ip_cidr_range = "10.8.0.0/28"
  network       = google_compute_network.vpc.name
  region        = var.region
  depends_on    = [google_project_service.vpcaccess]
}

# Private IP range for Cloud SQL
resource "google_compute_global_address" "private_ip_address" {
  name          = "korp-private-ip-address"
  purpose       = "VPC_PEERING"
  address_type  = "INTERNAL"
  prefix_length = 16
  network       = google_compute_network.vpc.id
}

resource "google_service_networking_connection" "private_vpc_connection" {
  network                 = google_compute_network.vpc.id
  service                 = "servicenetworking.googleapis.com"
  reserved_peering_ranges = [google_compute_global_address.private_ip_address.name]
}
