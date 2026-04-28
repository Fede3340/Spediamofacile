CREATE TABLE "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "surname" varchar not null,
  "email" varchar not null,
  "telephone_number" varchar not null,
  "role" varchar not null,
  "customer_id" varchar,
  "email_verified_at" datetime,
  "stripe_account_id" varchar,
  "stripe_charges_enabled" tinyint(1) not null default '0',
  "stripe_payouts_enabled" tinyint(1) not null default '0',
  "stripe_details_submitted" tinyint(1) not null default '0',
  "stripe_capabilities" text,
  "stripe_requirements" text,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "referral_code" varchar,
  "verification_code" varchar,
  "verification_code_expires_at" datetime,
  "referred_by" varchar,
  "user_type" varchar not null default 'privato',
  "google_id" varchar,
  "avatar" varchar,
  "facebook_id" varchar,
  "apple_id" varchar,
  "deleted_at" datetime,
  "privacy_accepted_at" datetime,
  "phone_number" varchar,
  "phone_number_verified_at" datetime
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE "personal_access_tokens"(
  "id" integer primary key autoincrement not null,
  "tokenable_type" varchar not null,
  "tokenable_id" integer not null,
  "name" varchar not null,
  "token" varchar not null,
  "abilities" text,
  "last_used_at" datetime,
  "expires_at" datetime,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" on "personal_access_tokens"(
  "tokenable_type",
  "tokenable_id"
);
CREATE UNIQUE INDEX "personal_access_tokens_token_unique" on "personal_access_tokens"(
  "token"
);
CREATE TABLE "billing_addresses"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "address" varchar not null,
  "city" varchar not null,
  "province_name" varchar not null,
  "postal_code" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  "is_business" tinyint(1) not null default '0',
  "company_name" varchar,
  "fiscal_code" varchar,
  "vat_number" varchar,
  "sdi_code" varchar not null default '0000000',
  "pec_email" varchar,
  "country" varchar not null default 'IT'
);
CREATE TABLE "locations"(
  "id" integer primary key autoincrement not null,
  "postal_code" varchar not null,
  "place_name" varchar not null,
  "province" varchar not null,
  "country_code" varchar not null default 'IT'
);
CREATE INDEX "locations_postal_code_index" on "locations"("postal_code");
CREATE INDEX "locations_place_name_index" on "locations"("place_name");
CREATE TABLE "package_addresses"(
  "id" integer primary key autoincrement not null,
  "type" varchar not null,
  "name" varchar not null,
  "additional_information" varchar,
  "address" varchar not null,
  "number_type" varchar not null,
  "address_number" varchar not null,
  "intercom_code" varchar,
  "country" varchar not null,
  "city" varchar not null,
  "postal_code" varchar not null,
  "province" varchar not null,
  "telephone_number" varchar not null,
  "email" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE "services"(
  "id" integer primary key autoincrement not null,
  "service_type" varchar not null,
  "date" varchar,
  "time" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "service_data" text
);
CREATE TABLE "packages"(
  "id" integer primary key autoincrement not null,
  "package_type" varchar not null,
  "quantity" integer not null,
  "weight" varchar not null,
  "first_size" varchar not null,
  "second_size" varchar not null,
  "third_size" varchar not null,
  "weight_price" varchar,
  "volume_price" varchar,
  "single_price" varchar,
  "origin_address_id" integer not null,
  "destination_address_id" integer not null,
  "service_id" integer not null,
  "user_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "content_description" varchar,
  foreign key("origin_address_id") references "package_addresses"("id"),
  foreign key("destination_address_id") references "package_addresses"("id"),
  foreign key("service_id") references "services"("id"),
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE "cart_user"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "package_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  "abandoned_cart_sent_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("package_id") references "packages"("id") on delete cascade
);
CREATE TABLE "orders"(
  "id" integer primary key autoincrement not null,
  "status" varchar not null default 'pending',
  "subtotal" integer not null,
  "user_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  "brt_parcel_id" varchar,
  "brt_numeric_sender_reference" varchar,
  "brt_tracking_url" varchar,
  "brt_label_base64" text,
  "brt_pudo_id" varchar,
  "is_cod" tinyint(1) not null default '0',
  "cod_amount" integer,
  "brt_error" text,
  "brt_tracking_number" varchar,
  "brt_parcel_number_to" varchar,
  "brt_departure_depot" varchar,
  "brt_arrival_terminal" varchar,
  "brt_arrival_depot" varchar,
  "brt_delivery_zone" varchar,
  "brt_series_number" varchar,
  "brt_service_type" varchar,
  "brt_raw_response" text,
  "refund_status" varchar,
  "refund_amount" integer,
  "refund_method" varchar,
  "refund_reason" varchar,
  "refunded_at" datetime,
  "cancellation_fee" integer,
  "payment_method" varchar,
  "stripe_payment_intent_id" varchar,
  "pickup_status" varchar,
  "pickup_reference" varchar,
  "pickup_requested_at" datetime,
  "pickup_time_slot" varchar,
  "pickup_notes" varchar,
  "bordero_status" varchar,
  "bordero_reference" varchar,
  "bordero_document_base64" text,
  "bordero_document_mime" varchar,
  "bordero_document_filename" varchar,
  "documents_status" varchar,
  "documents_sent_customer_at" datetime,
  "documents_sent_admin_at" datetime,
  "execution_error" text,
  "nowpayments_invoice_id" varchar,
  "brt_last_tracking_check" datetime,
  "billing_data" text,
  "brt_all_labels" text,
  "cod_payment_type" varchar,
  "deleted_at" datetime,
  "client_submission_id" varchar,
  "pricing_signature" varchar,
  "pricing_snapshot_version" integer,
  "pricing_snapshot" text,
  "coupon_code" varchar,
  "sdi_status" varchar,
  "sdi_xml_path" varchar,
  "sdi_transmission_id" varchar,
  "sdi_invoice_number" varchar,
  "sdi_sent_at" datetime,
  "sdi_accepted_at" datetime,
  "sdi_rejected_at" datetime,
  "sdi_last_error" text,
  "bank_transfer_confirmed_at" datetime,
  "bank_transfer_reference" varchar,
  "bank_transfer_confirmed_by" integer,
  "pickup_date" date,
  "insurance_amount_cents" integer,
  "cod_incasso_type" varchar,
  foreign key("user_id") references "users"("id")
);
CREATE TABLE "transactions"(
  "id" integer primary key autoincrement not null,
  "order_id" integer not null,
  "ext_id" varchar,
  "type" varchar,
  "status" varchar,
  "provider_status" varchar,
  "failure_code" varchar,
  "failure_message" varchar,
  "total" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("order_id") references "orders"("id")
);
CREATE TABLE "package_order"(
  "id" integer primary key autoincrement not null,
  "order_id" integer not null,
  "package_id" integer not null,
  "quantity" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("order_id") references "orders"("id"),
  foreign key("package_id") references "packages"("id") on delete cascade
);
CREATE TABLE "coupons"(
  "id" integer primary key autoincrement not null,
  "code" varchar not null,
  "stripe_connected_account_id" varchar,
  "percentage" numeric not null,
  "active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  "expires_at" datetime,
  "max_uses" integer,
  "max_uses_per_user" integer,
  "uses_count" integer not null default '0'
);
CREATE UNIQUE INDEX "coupons_code_unique" on "coupons"("code");
CREATE TABLE "user_addresses"(
  "id" integer primary key autoincrement not null,
  "type" varchar not null,
  "name" varchar not null,
  "additional_information" varchar,
  "address" varchar not null,
  "number_type" varchar not null,
  "address_number" varchar not null,
  "intercom_code" varchar,
  "country" varchar not null,
  "city" varchar not null,
  "postal_code" varchar not null,
  "province" varchar not null,
  "telephone_number" varchar not null,
  "email" varchar,
  "default" tinyint(1) not null default '0',
  "user_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "users_referral_code_unique" on "users"("referral_code");
CREATE TABLE "referral_usages"(
  "id" integer primary key autoincrement not null,
  "buyer_id" integer not null,
  "pro_user_id" integer not null,
  "referral_code" varchar not null,
  "order_id" integer,
  "order_amount" numeric not null,
  "discount_amount" numeric not null,
  "commission_amount" numeric not null,
  "status" varchar not null default 'pending',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("buyer_id") references "users"("id") on delete cascade,
  foreign key("pro_user_id") references "users"("id") on delete cascade,
  foreign key("order_id") references "orders"("id") on delete set null
);
CREATE TABLE "withdrawal_requests"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "amount" numeric not null,
  "currency" varchar not null default 'EUR',
  "status" varchar not null default 'pending',
  "admin_notes" text,
  "reviewed_at" datetime,
  "reviewed_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("reviewed_by") references "users"("id") on delete set null
);
CREATE TABLE "wallet_movements"(
  "id" integer primary key autoincrement not null,
  "type" varchar not null,
  "amount" numeric not null,
  "currency" varchar not null default('EUR'),
  "status" varchar not null default('confirmed'),
  "idempotency_key" varchar not null,
  "reference" varchar,
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  "user_id" integer,
  "source" varchar,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "wallet_movements_idempotency_key_unique" on "wallet_movements"(
  "idempotency_key"
);
CREATE TABLE "settings"(
  "id" integer primary key autoincrement not null,
  "key" varchar not null,
  "value" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "settings_key_unique" on "settings"("key");
CREATE TABLE "saved_shipments"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "package_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("package_id") references "packages"("id") on delete cascade
);
CREATE TABLE "contact_messages"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "surname" varchar not null,
  "email" varchar not null,
  "telephone_number" varchar,
  "address" varchar,
  "message" text not null,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "subject" varchar
);
CREATE TABLE "pro_requests"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "company_name" varchar default '',
  "vat_number" varchar default '',
  "message" text,
  "status" varchar check("status" in('pending', 'approved', 'rejected')) not null default 'pending',
  "reviewed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE "price_bands"(
  "id" integer primary key autoincrement not null,
  "type" varchar check("type" in('weight', 'volume')) not null,
  "min_value" numeric not null,
  "max_value" numeric not null,
  "base_price" integer not null,
  "discount_price" integer,
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "show_discount" tinyint(1) not null default '1'
);
CREATE UNIQUE INDEX "users_google_id_unique" on "users"("google_id");
CREATE TABLE "user_notification_preferences"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "referral_site_enabled" tinyint(1) not null default '1',
  "referral_email_enabled" tinyint(1) not null default '0',
  "referral_sms_enabled" tinyint(1) not null default '0',
  "email_opt_in_at" datetime,
  "sms_opt_in_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "sms_order_updates" tinyint(1) not null default '0',
  "sms_marketing" tinyint(1) not null default '0',
  "push_order_updates" tinyint(1) not null default '0',
  "push_marketing" tinyint(1) not null default '0',
  "push_opt_in_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "user_notification_preferences_user_id_unique" on "user_notification_preferences"(
  "user_id"
);
CREATE TABLE "user_notifications"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "type" varchar not null,
  "title" varchar not null,
  "body" text not null,
  "payload" text,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "user_notifications_user_id_type_index" on "user_notifications"(
  "user_id",
  "type"
);
CREATE TABLE "articles"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "slug" varchar not null,
  "type" varchar not null default 'guide',
  "meta_description" text,
  "intro" text,
  "sections" text,
  "faqs" text,
  "featured_image" varchar,
  "icon" varchar,
  "is_published" tinyint(1) not null default('1'),
  "sort_order" integer not null default('0'),
  "created_at" datetime,
  "updated_at" datetime,
  "banner_image" varchar,
  "banner_title" varchar,
  "banner_subtitle" varchar,
  "banner_cta_text" varchar,
  "banner_cta_url" varchar,
  "banner_bg_color" varchar not null default '#095866',
  "banner_text_color" varchar not null default '#ffffff',
  "banner_position" varchar not null default 'homepage_top'
);
CREATE UNIQUE INDEX "articles_slug_unique" on "articles"("slug");
CREATE UNIQUE INDEX "referral_usages_order_id_unique" on "referral_usages"(
  "order_id"
);
CREATE INDEX "locations_country_code_index" on "locations"("country_code");
CREATE TABLE "pudo_points"(
  "id" integer primary key autoincrement not null,
  "pudo_id" varchar not null,
  "name" varchar not null,
  "address" varchar not null,
  "city" varchar not null,
  "zip_code" varchar not null,
  "province" varchar not null,
  "country" varchar not null default 'ITA',
  "latitude" numeric,
  "longitude" numeric,
  "phone" varchar,
  "email" varchar,
  "opening_hours" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "pudo_points_pudo_id_unique" on "pudo_points"("pudo_id");
CREATE INDEX "pudo_points_city_index" on "pudo_points"("city");
CREATE INDEX "pudo_points_zip_code_index" on "pudo_points"("zip_code");
CREATE INDEX "pudo_points_is_active_index" on "pudo_points"("is_active");
CREATE UNIQUE INDEX "users_facebook_id_unique" on "users"("facebook_id");
CREATE UNIQUE INDEX "users_apple_id_unique" on "users"("apple_id");
CREATE INDEX "orders_user_status_idx" on "orders"("user_id", "status");
CREATE INDEX "orders_created_at_idx" on "orders"("created_at");
CREATE INDEX "orders_brt_parcel_idx" on "orders"("brt_parcel_id");
CREATE INDEX "wallet_user_status_type_idx" on "wallet_movements"(
  "user_id",
  "status",
  "type"
);
CREATE INDEX "users_referral_code_idx" on "users"("referral_code");
CREATE INDEX "coupons_code_idx" on "coupons"("code");
CREATE INDEX "locations_city_postal_idx" on "locations"(
  "place_name",
  "postal_code"
);
CREATE INDEX "orders_user_submission_idx" on "orders"(
  "user_id",
  "client_submission_id"
);
CREATE UNIQUE INDEX "package_order_unique" on "package_order"(
  "order_id",
  "package_id"
);
CREATE TABLE "cookie_consents"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "analytics" tinyint(1) not null default '0',
  "marketing" tinyint(1) not null default '0',
  "functional" tinyint(1) not null default '0',
  "ip_address" varchar,
  "user_agent" varchar,
  "consented_at" datetime not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "cookie_consents_user_id_index" on "cookie_consents"("user_id");
CREATE INDEX "cart_user_user_package_idx" on "cart_user"(
  "user_id",
  "package_id"
);
CREATE INDEX "orders_status_idx" on "orders"("status");
CREATE INDEX "orders_brt_tracking_number_idx" on "orders"(
  "brt_tracking_number"
);
CREATE INDEX "wallet_movements_user_created_idx" on "wallet_movements"(
  "user_id",
  "created_at"
);
CREATE TABLE "stripe_webhook_events"(
  "id" integer primary key autoincrement not null,
  "stripe_event_id" varchar not null,
  "event_type" varchar not null,
  "processed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE INDEX "stripe_webhook_events_processed_at_index" on "stripe_webhook_events"(
  "processed_at"
);
CREATE UNIQUE INDEX "stripe_webhook_events_stripe_event_id_unique" on "stripe_webhook_events"(
  "stripe_event_id"
);
CREATE INDEX "stripe_webhook_events_event_type_index" on "stripe_webhook_events"(
  "event_type"
);
CREATE UNIQUE INDEX "transactions_ext_id_unique" on "transactions"("ext_id");
CREATE INDEX "orders_user_id_index" on "orders"("user_id");
CREATE INDEX "orders_status_index" on "orders"("status");
CREATE INDEX "orders_created_at_index" on "orders"("created_at");
CREATE INDEX "orders_user_id_status_index" on "orders"("user_id", "status");
CREATE TABLE "coupon_user"(
  "id" integer primary key autoincrement not null,
  "coupon_id" integer not null,
  "user_id" integer not null,
  "order_id" integer,
  "used_at" datetime not null default CURRENT_TIMESTAMP,
  foreign key("coupon_id") references "coupons"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("order_id") references "orders"("id") on delete set null
);
CREATE INDEX "coupon_user_coupon_id_user_id_index" on "coupon_user"(
  "coupon_id",
  "user_id"
);
CREATE TABLE "brt_webhook_events"(
  "id" integer primary key autoincrement not null,
  "fingerprint" varchar not null,
  "parcel_id" varchar not null,
  "status" varchar not null,
  "event_timestamp" varchar not null,
  "processed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE INDEX "brt_webhook_events_processed_at_index" on "brt_webhook_events"(
  "processed_at"
);
CREATE UNIQUE INDEX "brt_webhook_events_fingerprint_unique" on "brt_webhook_events"(
  "fingerprint"
);
CREATE INDEX "brt_webhook_events_parcel_id_index" on "brt_webhook_events"(
  "parcel_id"
);
CREATE INDEX "orders_sdi_status_index" on "orders"("sdi_status");
CREATE INDEX "orders_sdi_transmission_id_index" on "orders"(
  "sdi_transmission_id"
);
CREATE TABLE "invoice_archive"(
  "id" integer primary key autoincrement not null,
  "order_id" integer,
  "document_type" varchar not null,
  "file_path" varchar not null,
  "mime_type" varchar not null default 'application/xml',
  "sha256_hash" varchar not null,
  "size_bytes" integer not null default '0',
  "invoice_number" varchar,
  "invoice_date" date,
  "archive_status" varchar not null default 'pending',
  "provider" varchar,
  "provider_reference" varchar,
  "retain_until" date not null,
  "metadata" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("order_id") references "orders"("id") on delete set null
);
CREATE INDEX "invoice_archive_document_type_archive_status_index" on "invoice_archive"(
  "document_type",
  "archive_status"
);
CREATE INDEX "invoice_archive_invoice_number_index" on "invoice_archive"(
  "invoice_number"
);
CREATE INDEX "invoice_archive_invoice_date_index" on "invoice_archive"(
  "invoice_date"
);
CREATE INDEX "invoice_archive_archive_status_index" on "invoice_archive"(
  "archive_status"
);
CREATE INDEX "invoice_archive_retain_until_index" on "invoice_archive"(
  "retain_until"
);
CREATE TABLE "claims"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "order_id" integer not null,
  "claim_type" varchar check("claim_type" in('damage', 'loss', 'delay', 'wrong_delivery', 'other')) not null default 'other',
  "status" varchar check("status" in('open', 'in_review', 'resolved', 'rejected')) not null default 'open',
  "description" text not null,
  "resolution_notes" text,
  "resolved_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("order_id") references "orders"("id") on delete cascade
);
CREATE INDEX "claims_user_id_status_index" on "claims"("user_id", "status");
CREATE INDEX "claims_order_id_index" on "claims"("order_id");
CREATE INDEX "claims_status_created_at_index" on "claims"(
  "status",
  "created_at"
);
CREATE TABLE "claim_attachments"(
  "id" integer primary key autoincrement not null,
  "claim_id" integer not null,
  "path" varchar not null,
  "original_name" varchar,
  "mime_type" varchar not null,
  "size_bytes" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("claim_id") references "claims"("id") on delete cascade
);
CREATE INDEX "claim_attachments_claim_id_index" on "claim_attachments"(
  "claim_id"
);
CREATE INDEX "orders_bank_transfer_confirmed_at_index" on "orders"(
  "bank_transfer_confirmed_at"
);
CREATE TABLE "audit_logs"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "actor_type" varchar not null default 'user',
  "action" varchar not null,
  "target_type" varchar,
  "target_id" integer,
  "ip" varchar,
  "user_agent" varchar,
  "context" text,
  "created_at" datetime,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "audit_logs_target_idx" on "audit_logs"(
  "target_type",
  "target_id"
);
CREATE INDEX "audit_logs_action_time_idx" on "audit_logs"(
  "action",
  "created_at"
);
CREATE INDEX "audit_logs_user_id_index" on "audit_logs"("user_id");
CREATE INDEX "audit_logs_action_index" on "audit_logs"("action");
CREATE INDEX "audit_logs_created_at_index" on "audit_logs"("created_at");
CREATE TABLE "pro_api_keys"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "name" varchar not null,
  "key_hash" varchar not null,
  "last_four" varchar not null,
  "scopes" text not null,
  "last_used_at" datetime,
  "revoked_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "pro_api_keys_user_id_revoked_at_index" on "pro_api_keys"(
  "user_id",
  "revoked_at"
);
CREATE UNIQUE INDEX "pro_api_keys_key_hash_unique" on "pro_api_keys"(
  "key_hash"
);
CREATE TABLE "push_subscriptions"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "endpoint" text not null,
  "p256dh" varchar not null,
  "auth" varchar not null,
  "user_agent" varchar,
  "last_used_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "endpoint_hash" varchar not null,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "push_subscriptions_user_id_index" on "push_subscriptions"(
  "user_id"
);
CREATE UNIQUE INDEX "push_subscriptions_endpoint_hash_unique" on "push_subscriptions"(
  "endpoint_hash"
);
CREATE TABLE "invoice_counters"(
  "id" integer primary key autoincrement not null,
  "prefix" varchar not null default 'INV',
  "year" integer not null,
  "last_number" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "invoice_counters_prefix_year_unique" on "invoice_counters"(
  "prefix",
  "year"
);
CREATE INDEX "wallet_movements_status_index" on "wallet_movements"("status");
CREATE INDEX "contact_messages_created_at_index" on "contact_messages"(
  "created_at"
);
CREATE INDEX "contact_messages_read_at_index" on "contact_messages"("read_at");

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2025_02_01_000000_create_wallet_movements_table',1);
INSERT INTO migrations VALUES(5,'2025_05_10_151347_create_personal_access_tokens_table',1);
INSERT INTO migrations VALUES(6,'2025_05_10_152026_add_two_factor_columns_to_users_table',1);
INSERT INTO migrations VALUES(7,'2025_08_04_133132_create_email_verifications_table',1);
INSERT INTO migrations VALUES(8,'2025_08_16_120205_create_billing_addresses_table',1);
INSERT INTO migrations VALUES(9,'2025_09_15_172512_create_locations_table',1);
INSERT INTO migrations VALUES(10,'2025_09_18_075747_create_package_addresses_table',1);
INSERT INTO migrations VALUES(11,'2025_09_18_075748_create_services_table',1);
INSERT INTO migrations VALUES(12,'2025_09_18_075749_create_packages_table',1);
INSERT INTO migrations VALUES(13,'2025_09_18_083157_create_cart_user_table',1);
INSERT INTO migrations VALUES(14,'2025_09_18_100646_create_orders_table',1);
INSERT INTO migrations VALUES(15,'2025_09_18_101738_create_transactions_table',1);
INSERT INTO migrations VALUES(16,'2025_09_18_101824_create_package_order_table',1);
INSERT INTO migrations VALUES(17,'2025_12_11_175039_create_coupons_table',1);
INSERT INTO migrations VALUES(18,'2026_01_21_180536_create_user_addresses_table',1);
INSERT INTO migrations VALUES(19,'2026_02_11_000001_add_wallet_referral_system',1);
INSERT INTO migrations VALUES(20,'2026_02_12_000001_create_settings_table',1);
INSERT INTO migrations VALUES(21,'2026_02_12_100000_create_saved_shipments_table',1);
INSERT INTO migrations VALUES(22,'2026_02_12_200000_add_verification_code_to_users',1);
INSERT INTO migrations VALUES(23,'2026_02_12_300000_create_contact_messages_table',1);
INSERT INTO migrations VALUES(24,'2026_02_13_000001_create_pro_requests_table',1);
INSERT INTO migrations VALUES(25,'2026_02_13_100000_fix_packages_table_nullable_user',1);
INSERT INTO migrations VALUES(26,'2026_02_13_200000_add_content_description_to_packages_table',1);
INSERT INTO migrations VALUES(27,'2026_02_13_200000_add_referred_by_to_users',1);
INSERT INTO migrations VALUES(28,'2026_02_13_300000_add_brt_fields_to_orders',1);
INSERT INTO migrations VALUES(29,'2026_02_14_100000_add_brt_error_to_orders',1);
INSERT INTO migrations VALUES(30,'2026_02_14_200000_add_brt_response_fields_to_orders',1);
INSERT INTO migrations VALUES(31,'2026_02_14_300000_add_refund_fields_to_orders',1);
INSERT INTO migrations VALUES(32,'2026_02_15_100000_add_service_data_to_services_table',1);
INSERT INTO migrations VALUES(33,'2026_02_15_200000_create_articles_table',1);
INSERT INTO migrations VALUES(34,'2026_02_15_300000_create_price_bands_table',1);
INSERT INTO migrations VALUES(35,'2026_02_15_400000_add_user_type_to_users_table',1);
INSERT INTO migrations VALUES(36,'2026_02_15_500000_add_google_id_to_users_table',1);
INSERT INTO migrations VALUES(37,'2026_02_16_100000_add_show_discount_to_price_bands',1);
INSERT INTO migrations VALUES(38,'2026_02_16_210000_add_execution_fields_to_orders_table',1);
INSERT INTO migrations VALUES(39,'2026_02_16_210100_create_user_notification_preferences_table',1);
INSERT INTO migrations VALUES(40,'2026_02_16_210200_create_user_notifications_table',1);
INSERT INTO migrations VALUES(41,'2026_02_20_120000_add_nowpayments_invoice_id_to_orders_table',1);
INSERT INTO migrations VALUES(42,'2026_02_27_100000_add_banner_fields_to_articles_table',1);
INSERT INTO migrations VALUES(43,'2026_02_27_100000_add_brt_last_tracking_check_to_orders_table',1);
INSERT INTO migrations VALUES(44,'2026_02_28_100000_add_unique_order_id_to_referral_usages',1);
INSERT INTO migrations VALUES(45,'2026_02_28_100100_add_expires_at_to_coupons_table',1);
INSERT INTO migrations VALUES(46,'2026_02_28_160450_add_country_code_to_locations_table',1);
INSERT INTO migrations VALUES(47,'2026_03_03_100000_create_pudo_points_table',1);
INSERT INTO migrations VALUES(48,'2026_03_25_120000_add_subject_to_contact_messages_table',2);
INSERT INTO migrations VALUES(49,'2026_03_26_210500_add_billing_data_to_orders_table',3);
INSERT INTO migrations VALUES(50,'2026_03_26_221500_add_facebook_id_to_users_table',3);
INSERT INTO migrations VALUES(51,'2026_03_27_110000_add_apple_id_to_users_table',4);
INSERT INTO migrations VALUES(52,'2026_03_31_000000_add_performance_indices',5);
INSERT INTO migrations VALUES(53,'2026_03_31_100000_add_brt_all_labels_to_orders_table',6);
INSERT INTO migrations VALUES(54,'2026_03_31_100001_add_cod_payment_type_to_orders_table',6);
INSERT INTO migrations VALUES(55,'2026_03_31_200000_add_soft_deletes_to_orders',6);
INSERT INTO migrations VALUES(56,'2026_04_02_000000_add_submission_and_pricing_snapshot_to_orders_table',7);
INSERT INTO migrations VALUES(57,'2026_04_04_000000_add_unique_constraint_to_package_order_table',8);
INSERT INTO migrations VALUES(58,'2026_04_05_000000_create_cookie_consents_table',9);
INSERT INTO migrations VALUES(59,'2026_04_05_100000_add_cart_user_and_orders_performance_indices',10);
INSERT INTO migrations VALUES(60,'2026_04_05_100000_add_soft_deletes_to_users_table',10);
INSERT INTO migrations VALUES(61,'2026_04_06_000000_create_stripe_webhook_events_table',10);
INSERT INTO migrations VALUES(62,'2026_04_07_000001_add_indexes_to_orders_table',11);
INSERT INTO migrations VALUES(63,'2026_04_11_000001_add_privacy_accepted_at_to_users_table',12);
INSERT INTO migrations VALUES(64,'2026_04_11_100000_add_coupon_anti_abuse_fields',12);
INSERT INTO migrations VALUES(65,'2026_02_13_210000_add_referred_by_to_users',13);
INSERT INTO migrations VALUES(66,'2026_04_17_100000_create_sessions_table',13);
INSERT INTO migrations VALUES(67,'2026_04_17_110000_create_brt_webhook_events_table',13);
INSERT INTO migrations VALUES(68,'2026_04_18_100000_add_fiscal_fields_to_billing_addresses',13);
INSERT INTO migrations VALUES(69,'2026_04_18_100100_add_sdi_fields_to_orders',13);
INSERT INTO migrations VALUES(70,'2026_04_18_100200_create_invoice_archive_table',13);
INSERT INTO migrations VALUES(71,'2026_04_18_110000_create_claims_table',13);
INSERT INTO migrations VALUES(72,'2026_04_18_110100_create_claim_attachments_table',13);
INSERT INTO migrations VALUES(73,'2026_04_18_110200_add_bank_transfer_fields_to_orders',13);
INSERT INTO migrations VALUES(74,'2026_04_18_110300_add_pickup_date_to_orders',13);
INSERT INTO migrations VALUES(75,'2026_04_18_120000_add_insurance_and_cod_fields_to_orders_table',13);
INSERT INTO migrations VALUES(76,'2026_04_18_140000_create_audit_logs_table',13);
INSERT INTO migrations VALUES(77,'2026_04_18_140050_add_phone_number_to_users_table',13);
INSERT INTO migrations VALUES(78,'2026_04_18_140060_create_pro_api_keys_table',13);
INSERT INTO migrations VALUES(79,'2026_04_18_140100_add_sms_and_push_to_user_notification_preferences',13);
INSERT INTO migrations VALUES(80,'2026_04_18_140200_create_push_subscriptions_table',13);
INSERT INTO migrations VALUES(81,'2026_04_18_150000_create_invoice_counters_table',13);
INSERT INTO migrations VALUES(82,'2026_04_20_000000_encrypt_existing_stripe_account_ids',13);
INSERT INTO migrations VALUES(83,'2026_04_19_100000_add_abandoned_cart_sent_at_to_cart_user_table',14);
INSERT INTO migrations VALUES(85,'2026_04_20_100000_remove_two_factor_columns_from_users_table',15);
INSERT INTO migrations VALUES(86,'2026_04_25_120000_add_performance_indexes',15);
