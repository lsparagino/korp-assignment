variable "project_id" {
  description = "The GCP Project ID"
  type        = string
}

variable "region" {
  description = "The GCP region"
  type        = string
  default     = "asia-southeast1"
}

variable "db_password" {
  description = "Password for the database user"
  type        = string
  sensitive   = true
}

variable "app_key" {
  description = "Laravel APP_KEY"
  type        = string
  sensitive   = true
}

# Mailer Configuration
variable "mail_host" {
  type    = string
  default = "smtp-relay.gmail.com"
}

variable "mail_port" {
  type    = string
  default = "465"
}

variable "mail_encryption" {
  type    = string
  default = "tls"
}

variable "mail_username" {
  type    = string
  default = "luca@sparagino.it"
}

variable "mail_password" {
  type      = string
  sensitive = true
}

variable "mail_from_address" {
  type    = string
  default = "noreply@sparagino.it"
}

variable "api_image_tag" {
  description = "Tag for the backend API image"
  type        = string
  default     = "latest"
}

variable "client_image_tag" {
  description = "Tag for the frontend client image"
  type        = string
  default     = "latest"
}

variable "app_name" {
  description = "The name of the application"
  type        = string
  default     = "Korp"
}
