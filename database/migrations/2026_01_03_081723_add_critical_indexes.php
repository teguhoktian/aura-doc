<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Documents
        DB::statement("CREATE INDEX IF NOT EXISTS documents_loan_id_index ON documents(loan_id)");
        DB::statement("CREATE INDEX IF NOT EXISTS documents_status_index ON documents(status)");
        DB::statement("CREATE INDEX IF NOT EXISTS documents_storage_id_index ON documents(storage_id)");
        DB::statement("CREATE INDEX IF NOT EXISTS documents_document_type_id_index ON documents(document_type_id)");
        DB::statement("CREATE INDEX IF NOT EXISTS documents_expiry_date_index ON documents(expiry_date)");

        // Transactions
        DB::statement("CREATE INDEX IF NOT EXISTS transactions_document_id_index ON transactions(document_id)");
        DB::statement("CREATE INDEX IF NOT EXISTS transactions_user_id_index ON transactions(user_id)");

        // Loans
        DB::statement("CREATE INDEX IF NOT EXISTS loans_branch_id_index ON loans(branch_id)");
        DB::statement("CREATE INDEX IF NOT EXISTS loans_loan_type_id_index ON loans(loan_type_id)");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS documents_loan_id_index");
        DB::statement("DROP INDEX IF EXISTS documents_status_index");
        DB::statement("DROP INDEX IF NOT EXISTS documents_storage_id_index");
        DB::statement("DROP INDEX IF EXISTS documents_document_type_id_index");
        DB::statement("DROP INDEX IF EXISTS documents_expiry_date_index");

        DB::statement("DROP INDEX IF EXISTS transactions_document_id_index");
        DB::statement("DROP INDEX IF EXISTS transactions_user_id_index");

        DB::statement("DROP INDEX IF EXISTS loans_branch_id_index");
        DB::statement("DROP INDEX IF EXISTS loans_loan_type_id_index");
    }
};
