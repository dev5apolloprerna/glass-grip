-- =====================================================================
-- Glass Grip : Invoice + A Invoice split, invoice_details table (MySQL)
-- Run these ONLY if you are NOT running `php artisan migrate`.
-- Take a database backup first.
-- =====================================================================

-- ---------------------------------------------------------------------
-- PART 1 : invoices table changes
-- (migration 2026_10_05_000001_add_invoice_type_and_split_to_invoices_table)
-- Check index names first:  SHOW CREATE TABLE invoices;
-- ---------------------------------------------------------------------
ALTER TABLE invoices DROP FOREIGN KEY invoices_quotation_id_foreign;
ALTER TABLE invoices DROP INDEX invoices_quotation_id_unique;
ALTER TABLE invoices DROP INDEX invoices_invoice_number_unique;

ALTER TABLE invoices
    ADD COLUMN invoice_type VARCHAR(20) NOT NULL DEFAULT 'gst' AFTER invoice_number,
    ADD COLUMN split_percentage DECIMAL(5,2) NOT NULL DEFAULT 100.00 AFTER invoice_type;

-- Skip any of these that already exist in your invoices table:
-- ALTER TABLE invoices ADD COLUMN other_reference VARCHAR(255) NULL AFTER split_percentage;
-- ALTER TABLE invoices ADD COLUMN admin_charges DECIMAL(15,2) NOT NULL DEFAULT 0.00;
-- ALTER TABLE invoices ADD COLUMN material_handling_charges DECIMAL(15,2) NOT NULL DEFAULT 0.00;

ALTER TABLE invoices
    ADD CONSTRAINT invoices_quotation_id_foreign
        FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE;

ALTER TABLE invoices
    ADD UNIQUE KEY invoices_quotation_id_invoice_type_unique (quotation_id, invoice_type),
    ADD UNIQUE KEY invoices_invoice_number_invoice_type_unique (invoice_number, invoice_type);

INSERT INTO migrations (migration, batch)
SELECT '2026_10_05_000001_add_invoice_type_and_split_to_invoices_table', IFNULL(MAX(batch), 0) + 1
FROM migrations;

-- ---------------------------------------------------------------------
-- PART 2 : invoice_details table
-- (migration 2026_10_06_000001_create_invoice_details_table)
-- ---------------------------------------------------------------------
CREATE TABLE invoice_details (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    quotation_item_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    despatch_to VARCHAR(255) NULL,
    size_mtr DECIMAL(10,2) NOT NULL,
    no_of_rolls INT UNSIGNED NOT NULL,
    total_mtr DECIMAL(12,2) NOT NULL,
    quotation_price_per_mtr DECIMAL(12,2) NOT NULL,   -- original quotation rate
    quotation_amount DECIMAL(15,2) NOT NULL,          -- original quotation amount
    price_per_mtr DECIMAL(12,2) NOT NULL,             -- rate on this invoice (split %)
    amount DECIMAL(15,2) NOT NULL,                    -- amount on this invoice (split %)
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT invoice_details_invoice_id_foreign
        FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    CONSTRAINT invoice_details_quotation_item_id_foreign
        FOREIGN KEY (quotation_item_id) REFERENCES quotation_items(id) ON DELETE SET NULL,
    CONSTRAINT invoice_details_product_id_foreign
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill line items for invoices that already exist.
-- Invoice (gst)      : rate/amount = p% of quotation value
-- A Invoice (non_gst): rate/amount = quotation value - (100-p)% share  (exact remainder)
INSERT INTO invoice_details
    (invoice_id, quotation_item_id, product_id, despatch_to, size_mtr, no_of_rolls, total_mtr,
     quotation_price_per_mtr, quotation_amount, price_per_mtr, amount, created_at, updated_at)
SELECT
    i.id, qi.id, qi.product_id, qi.despatch_to, qi.size_mtr, qi.no_of_rolls, qi.total_mtr,
    qi.price_per_mtr, qi.amount,
    CASE
        WHEN i.split_percentage >= 100 THEN qi.price_per_mtr
        WHEN i.invoice_type = 'gst' THEN ROUND(qi.price_per_mtr * i.split_percentage / 100, 2)
        ELSE ROUND(qi.price_per_mtr - ROUND(qi.price_per_mtr * (100 - i.split_percentage) / 100, 2), 2)
    END,
    CASE
        WHEN i.split_percentage >= 100 THEN qi.amount
        WHEN i.invoice_type = 'gst' THEN ROUND(qi.amount * i.split_percentage / 100, 2)
        ELSE ROUND(qi.amount - ROUND(qi.amount * (100 - i.split_percentage) / 100, 2), 2)
    END,
    NOW(), NOW()
FROM invoices i
JOIN quotation_items qi ON qi.quotation_id = i.quotation_id
ORDER BY i.id, qi.id;

INSERT INTO migrations (migration, batch)
SELECT '2026_10_06_000001_create_invoice_details_table', IFNULL(MAX(batch), 0) + 1
FROM migrations;

-- ---------------------------------------------------------------------
-- Check : details total must equal invoice sub_total (should return 0 rows)
-- ---------------------------------------------------------------------
SELECT i.id, i.invoice_number, i.invoice_type, i.sub_total, SUM(d.amount) AS details_total
FROM invoices i
LEFT JOIN invoice_details d ON d.invoice_id = i.id
GROUP BY i.id, i.invoice_number, i.invoice_type, i.sub_total
HAVING ABS(i.sub_total - IFNULL(SUM(d.amount), 0)) > 0.01;
