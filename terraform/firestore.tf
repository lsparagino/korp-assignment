# Enable Firestore API
resource "google_project_service" "firestore" {
  service            = "firestore.googleapis.com"
  disable_on_destroy = false
}

# Firestore Database (Native Mode) — dedicated to audit logs
resource "google_firestore_database" "audit" {
  name        = "audit-logs"
  location_id = var.region
  type        = "FIRESTORE_NATIVE"
  depends_on  = [google_project_service.firestore]
}

# ── Composite Indexes ─────────────────────────────────────────────
# Two indexes cover all query combinations:
# 1. Base: company_id + created_at (unfiltered or date-range only)
# 2. Tags: company_id + filter_tags (ARRAY_CONTAINS) + created_at
#    Covers category-only, severity-only, and category+severity filters

resource "google_firestore_index" "company_created" {
  database   = google_firestore_database.audit.name
  collection = "audit_logs"

  fields {
    field_path = "company_id"
    order      = "ASCENDING"
  }

  fields {
    field_path = "created_at"
    order      = "DESCENDING"
  }
}

resource "google_firestore_index" "company_tags_created" {
  database   = google_firestore_database.audit.name
  collection = "audit_logs"

  fields {
    field_path = "company_id"
    order      = "ASCENDING"
  }

  fields {
    field_path   = "filter_tags"
    array_config = "CONTAINS"
  }

  fields {
    field_path = "created_at"
    order      = "DESCENDING"
  }
}

# ── TTL Policy ────────────────────────────────────────────────────
# Automatically delete audit log documents 7 days after creation.
# The `expires_at` field is written as a Firestore Timestamp by AuditService.

resource "google_firestore_field" "audit_logs_ttl" {
  database   = google_firestore_database.audit.name
  collection = "audit_logs"
  field      = "expires_at"

  ttl_config {}

  # Prevent Terraform from managing single-field index config
  index_config {}
}

# ── IAM: Write-only custom role (true immutability) ────────────────
# Excludes datastore.entities.update and datastore.entities.delete
# so even a compromised API cannot alter or remove historical logs.

resource "google_project_iam_custom_role" "audit_writer" {
  role_id = "auditLogWriter"
  title   = "Audit Log Writer"
  permissions = [
    "datastore.entities.create",
    "datastore.entities.allocateIds",
    "datastore.entities.list",
    "datastore.entities.get",
    "datastore.indexes.list",
    "datastore.databases.get",
    "datastore.databases.list",
  ]
}

resource "google_project_iam_member" "cloud_run_audit_writer" {
  project = var.project_id
  role    = google_project_iam_custom_role.audit_writer.id
  member  = "serviceAccount:${data.google_project.project.number}-compute@developer.gserviceaccount.com"
}
