<?php

// ===============================
// DATABASE CONFIGURATION
// ===============================

$host = "localhost";
$dbname = "u333157338_glassgrip_db";
$username = "u333157338_glassgrip_ad";
$password = "Ankit@GlassGrip_9631";

// ===============================
// CONNECT DATABASE
// ===============================

mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die(
        "<h2 style='color:red'>Database Connection Failed</h2>" .
        "<pre>" . htmlspecialchars($conn->connect_error) . "</pre>"
    );
}

$conn->set_charset("utf8mb4");

echo "<h2 style='color:green'>Database Connected Successfully</h2>";


// ===============================
// SQL QUERIES
// ===============================

$queries = [

    // 1
    "ALTER TABLE invoices
     DROP FOREIGN KEY invoices_quotation_id_foreign",

    // 2
    "ALTER TABLE invoices
     DROP INDEX invoices_quotation_id_unique",

    // 3
    "ALTER TABLE invoices
     DROP INDEX invoices_invoice_number_unique",

    // 4
    "ALTER TABLE invoices
     ADD COLUMN invoice_type VARCHAR(20) NOT NULL DEFAULT 'gst'
         AFTER invoice_number,
     ADD COLUMN split_percentage DECIMAL(5,2) NOT NULL DEFAULT 100.00
         AFTER invoice_type",

    // 5
    "ALTER TABLE invoices
     ADD COLUMN other_reference VARCHAR(255) NULL
         AFTER split_percentage,
     ADD COLUMN admin_charges DECIMAL(15,2) NOT NULL DEFAULT 0.00,
     ADD COLUMN material_handling_charges DECIMAL(15,2) NOT NULL DEFAULT 0.00",

    // 6
    "ALTER TABLE invoices
     ADD CONSTRAINT invoices_quotation_id_foreign
     FOREIGN KEY (quotation_id)
     REFERENCES quotations(id)
     ON DELETE CASCADE",

    // 7
    "ALTER TABLE invoices
     ADD UNIQUE KEY invoices_quotation_id_invoice_type_unique
         (quotation_id, invoice_type),
     ADD UNIQUE KEY invoices_invoice_number_invoice_type_unique
         (invoice_number, invoice_type)",

    // 8
    "ALTER TABLE invoices ENGINE = InnoDB",

    // 9
    "ALTER TABLE quotation_items ENGINE = InnoDB",

    // 10
    "ALTER TABLE products ENGINE = InnoDB",

    // 11
    "DROP TABLE IF EXISTS invoice_details",

    // 12
    "CREATE TABLE invoice_details (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        invoice_id BIGINT UNSIGNED NOT NULL,
        quotation_item_id BIGINT UNSIGNED NULL,
        product_id BIGINT UNSIGNED NOT NULL,
        despatch_to VARCHAR(255) NULL,
        size_mtr DECIMAL(10,2) NOT NULL,
        no_of_rolls INT UNSIGNED NOT NULL,
        total_mtr DECIMAL(12,2) NOT NULL,
        quotation_price_per_mtr DECIMAL(12,2) NOT NULL,
        quotation_amount DECIMAL(15,2) NOT NULL,
        price_per_mtr DECIMAL(12,2) NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,

        KEY invoice_details_invoice_id_index (invoice_id),
        KEY invoice_details_quotation_item_id_index (quotation_item_id),
        KEY invoice_details_product_id_index (product_id)

    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci",

    // 13
    "ALTER TABLE invoice_details
     ADD CONSTRAINT invoice_details_invoice_id_foreign
     FOREIGN KEY (invoice_id)
     REFERENCES invoices(id)
     ON DELETE CASCADE,

     ADD CONSTRAINT invoice_details_quotation_item_id_foreign
     FOREIGN KEY (quotation_item_id)
     REFERENCES quotation_items(id)
     ON DELETE SET NULL,

     ADD CONSTRAINT invoice_details_product_id_foreign
     FOREIGN KEY (product_id)
     REFERENCES products(id)
     ON DELETE RESTRICT"
];


// ===============================
// EXECUTE QUERIES
// ===============================

$total = count($queries);
$success = 0;
$failed = 0;

echo "<hr>";
echo "<h3>Executing {$total} queries...</h3>";

foreach ($queries as $index => $sql) {

    $number = $index + 1;

    echo "<div style='margin:15px 0;padding:15px;border:1px solid #ddd'>";

    echo "<strong>Query {$number} of {$total}</strong>";

    if ($conn->query($sql) === TRUE) {

        $success++;

        echo " <span style='color:green;font-weight:bold'>
                SUCCESS
              </span>";

    } else {

        $failed++;

        echo " <span style='color:red;font-weight:bold'>
                FAILED
              </span>";

        echo "<br><br>";

        echo "<strong>Error:</strong>";

        echo "<pre style='background:#f8f8f8;padding:10px;color:red'>"
            . htmlspecialchars($conn->error)
            . "</pre>";

        echo "<strong>SQL:</strong>";

        echo "<pre style='background:#f8f8f8;padding:10px;white-space:pre-wrap'>"
            . htmlspecialchars($sql)
            . "</pre>";
    }

    echo "</div>";
}


// ===============================
// FINAL RESULT
// ===============================

echo "<hr>";

echo "<h2>Execution Completed</h2>";

echo "<p>
        <strong>Total Queries:</strong> {$total}
      </p>";

echo "<p style='color:green'>
        <strong>Successful:</strong> {$success}
      </p>";

echo "<p style='color:red'>
        <strong>Failed:</strong> {$failed}
      </p>";

if ($failed == 0) {

    echo "<h3 style='color:green'>
            All queries executed successfully.
          </h3>";

} else {

    echo "<h3 style='color:red'>
            Some queries failed. Check the errors above.
          </h3>";
}


// ===============================
// CLOSE CONNECTION
// ===============================

$conn->close();
?>