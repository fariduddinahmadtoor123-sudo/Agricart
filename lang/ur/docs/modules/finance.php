<?php

$field = static fn (string $name, string $label, string $type, bool $required, string $rules): array => compact('name', 'label', 'type', 'required', 'rules');

return [
    'overview' => [
        'title' => 'فنانس و اکاؤنٹس — جائزہ',
        'summary' => 'کیش، بینک، ٹرانسفر اور تین سطحی اخراجات: کیٹگریز → ہیڈز → پوسٹ شدہ ادائیگیاں۔',
        'meta' => ['title' => 'فنانس و اکاؤنٹس', 'summary' => 'والٹس، لیجرز، اخراجات اور مالی رپورٹس کا rebuild نقشہ۔', 'routes' => ['/admin/finance/overview'], 'permissions' => ['finance.view'], 'related' => ['purchasing', 'sales', 'reports', 'settings']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'مقصد', 'paragraphs' => ['Finance کاروباری رقم کی منظم نقل و حرکت محفوظ کرتا ہے۔ Bank accounts اور cash book والٹس ہیں؛ transfers اور expense payments ناقابلِ ترمیم ledger اثر بناتے ہیں۔', 'Expense tree لازماً تین سطحی ہے: category درجہ بندی، expense head مستقل خرچ/ذمہ داری کی شناخت، اور EXPAY payment رقم کے ساتھ cash یا bank ledger پوسٹ کرتی ہے۔']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'بنیادی ریکارڈز', 'fields' => [
                $field('expense_categories', 'کیٹگریز', 'master', true, 'operating یا other؛ رقم نہیں۔'),
                $field('expenses', 'Expense heads', 'master', true, 'ایک category؛ EXP-#####؛ رقم نہیں۔'),
                $field('expense_payments', 'ادائیگیاں', 'posted transaction', true, 'EXPAY-#####؛ پوسٹ کے بعد amount/date/wallet لاک۔'),
                $field('bank_accounts / cash_book_transactions', 'Wallet ledgers', 'ledger', true, 'بیلنس posted debit/credit یا IN/OUT سے۔'),
            ]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'عمل', 'steps' => ['فعال categories اور reusable heads بنائیں۔', 'کمپنی کے bank accounts اور opening balances درج کریں۔', 'Head کے خلاف Cash یا فعال Bank سے payment پوسٹ کریں۔', 'Deposit، withdrawal، bank transfer، vendor payment، customer receipt، expense-head payment یا staff funding کے لیے Cash / Bank Transfers استعمال کریں۔', 'Reports میں P&L اور ledgers سے reconciliation کریں۔']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI کی حد', 'paragraphs' => ['AI صرف bilingual master data میں مدد دے سکتا ہے؛ amount، wallet، date، balance یا ledger posting کبھی AI سے نہ ہو۔']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'اختیارات اور اکاؤنٹنگ قواعد', 'list' => ['RBAC: User::hasPermission("finance.view|create|edit|delete")؛ Super Admin bypass۔', 'ہر money posting DB transaction اور مناسب Cache::lock کے اندر ہو۔', 'Dependent records ہوں تو master delete بند؛ posted payment delete نہیں۔']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'روابط', 'list' => ['Purchasing/Suppliers payable movements دیتے ہیں۔', 'Sales/online orders cash یا bank credit کر سکتے ہیں۔', 'Reports posted expense_payments اور ledger transactions پڑھتا ہے؛ heads اکیلے P&L نہیں بدلتے۔', 'Settings → Permissions finance.* کنٹرول کرتا ہے۔']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild نوٹس', 'list' => ['تین سطحی expense design برقرار رکھیں؛ expenses میں amount/date مت ڈالیں۔', 'Cash Book اور Financial Reports ابھی placeholders ہیں؛ موجودہ ledgers پر read-only projections بنائیں، نئے transaction stores نہیں۔', 'تمام actions کو finance.* permissions اور audit trail سے جوڑیں۔']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'قبولیت', 'acceptance' => ['Head save سے wallet balance نہ بدلے۔', 'EXPAY save ایک payment اور ایک matching cash OUT یا bank debit atomically بنائے۔', 'Concurrent posting overdraft یا duplicate movement نہ بنائے۔', 'P&L منتخب مدت کی posted payments سے expense لے۔']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'تکنیکی نقشہ', 'classes' => ['cluster' => 'App\\Filament\\Clusters\\Finance', 'services' => ['App\\Services\\Finance\\CashBookService', 'App\\Services\\Finance\\BankAccountService', 'App\\Services\\Finance\\ExpensePaymentService', 'App\\Services\\Finance\\FundTransferService'], 'tables' => ['expense_categories', 'expenses', 'expense_payments', 'bank_accounts', 'bank_account_transactions', 'cash_book_transactions', 'fund_transfers']]],
        ],
    ],

    'bank-accounts' => [
        'title' => 'Bank Accounts (بینک اکاؤنٹس)', 'summary' => 'کمپنی bank-wallet masters اور posted transaction history۔',
        'meta' => ['routes' => ['/admin/finance/bank-accounts'], 'permissions' => ['finance.view', 'finance.create', 'finance.edit', 'finance.delete'], 'related' => ['cash-bank-transfers', 'expense-payments', 'cash-book', 'financial-reports']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'مقصد', 'paragraphs' => ['ہر حقیقی کمپنی اکاؤنٹ ایک بار درج کریں۔ Master wallet کی شناخت ہے؛ BankAccountTransaction movements اور balance دیتی ہیں۔']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'فیلڈز', 'fields' => [
                $field('account_code', 'Account code', 'string', false, 'خالی ہو تو auto؛ unique۔'),
                $field('name_en / name_ur', 'نام', 'string', true, 'دو لسانی wallet label۔'),
                $field('bank_key / bank_name_en / bank_name_ur', 'بینک', 'key + strings', true, 'Pakistani bank registry۔'),
                $field('account_title', 'Account title', 'string', true, 'قانونی عنوان۔'),
                $field('account_number / iban', 'شناخت', 'string', false, 'identifier type: iban|account_number|till_id؛ duplicate منع۔'),
                $field('opening_balance', 'Opening balance', 'decimal(2)', false, 'Default 0؛ create پر opening ledger entry۔'),
                $field('currency / status / notes', 'کنٹرول', 'string', false, 'PKR default؛ active|inactive۔'),
            ]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'عمل', 'steps' => ['اکاؤنٹ شناخت درج کریں۔', 'Verified opening balance صرف ایک بار دیں۔', 'History سے movements دیکھیں۔', 'تاریخ محفوظ رکھنے کے لیے delete کے بجائے inactive کریں۔']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI', 'paragraphs' => ['AI identifiers یا balances نہ بنائے؛ bank localization deterministic registry سے۔']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'قواعد', 'list' => ['finance.* permissions لازم۔', 'Opening post Cache::lock + DB::transaction میں۔', 'Inactive account نئی payment میں نہیں مگر reports میں رہے۔', 'کم اختیار والے logs/exports میں identifier mask کریں۔']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'روابط', 'list' => ['EXPAY bank debit، Fund Transfers دونوں legs، sales/order receipts credits، reports aggregation۔']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild نوٹس', 'list' => ['Current balance immutable transactions سے derive یا transactionally maintain کریں۔', 'Masking، reconciliation، statement reference اور audit actor شامل کریں۔']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'قبولیت', 'acceptance' => ['Duplicate account reject۔', 'Opening صرف ایک بار post۔', 'Inactive selector سے غائب۔', 'History کا حساب opening + credits − debits کے برابر۔']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'تکنیکی', 'classes' => ['page' => 'App\\Filament\\Clusters\\Finance\\BankAccountsPage', 'livewire' => ['App\\Livewire\\Finance\\ListBankAccounts', 'App\\Livewire\\Finance\\BankAccountForm'], 'service' => 'App\\Services\\Finance\\BankAccountService', 'models' => ['App\\Models\\BankAccount', 'App\\Models\\BankAccountTransaction']]],
        ],
    ],

    'cash-bank-transfers' => [
        'title' => 'Cash / Bank Transfers', 'summary' => 'FT-##### cash deposit، withdrawal، bank transfer، vendor payment، customer receipt، expense-head payment اور purchaser/staff funding۔',
        'meta' => ['routes' => ['/admin/finance/cash-bank-transfers'], 'permissions' => ['finance.view', 'finance.create'], 'related' => ['bank-accounts', 'cash-book', 'suppliers', 'expenses', 'expense-payments']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'مقصد', 'paragraphs' => ['Wallet movements: deposit (کیش → بینک)، withdrawal (بینک → کیش)، transfer (بینک → بینک)، vendor_payment، customer_receipt، expense_payment (expense head → EXPAY)، purchaser_payment (عملہ، بغیر EXPAY)۔']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'فیلڈز', 'fields' => [
                $field('transfer_number', 'نمبر', 'string', false, 'Auto FT-#####۔'),
                $field('transaction_type', 'قسم', 'enum', true, 'deposit|withdrawal|transfer|vendor_payment|customer_receipt|expense_payment|purchaser_payment۔'),
                $field('source_wallet', 'Source', 'enum', false, 'cash|bank؛ deposit پر کیش، withdrawal/transfer پر بینک لاک۔'),
                $field('source_bank_account_id / destination_bank_account_id', 'Bank legs', 'FK', false, 'Type کے مطابق لازم؛ transfer میں دونوں مختلف۔'),
                $field('destination_supplier_id / destination_customer_id / destination_expense_id / destination_purchaser_id', 'وصول کنندہ', 'FK', false, 'Vendor/customer/expense/purchaser type کے مطابق لازم۔'),
                $field('expense_payment_id', 'Linked EXPAY', 'FK', false, 'expense_payment پوسٹ پر تاکہ P&L دو بار نہ گنے۔'),
                $field('amount / transaction_date / transaction_time / notes', 'Posting', 'decimal/date/time/text', true, 'Amount min 0.01؛ time required؛ notes max 2000۔'),
            ]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'عمل', 'steps' => ['Type چنیں۔', 'مطلوب wallet/destination چنیں (expense_payment پر expense head)۔', 'Amount/date/time verify کریں۔', 'Save سے transfer اور ledger legs atomically بنیں؛ expense payment EXPAY اور بل سائیکل بھی مکمل کرے۔']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI', 'paragraphs' => ['مالی posting میں AI نہیں۔']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'قواعد', 'list' => ['FundTransferService DB::transaction؛ expense_payment ExpensePaymentService Cache::lock بھی۔', 'Deposit = cash OUT + bank credit۔', 'Withdrawal = bank debit + cash IN۔', 'Same bank source/destination منع۔', 'موجودہ vendor payment supplier opening_balance گھٹاتی ہے؛ rebuild gap۔', 'expense_payment فعال head چاہتی ہے اور ایک cash/bank OUT EXPAY سے۔', 'purchaser_payment بغیر EXPAY۔']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'روابط', 'list' => ['CashBookService، BankAccountService (بشمول cash_withdrawal)، Supplier، Expense، User۔', 'expense_payment destination_expense_id اور EXPAY سے link۔', 'purchaser_payment User سے؛ EXPAY سے الگ۔']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild نوٹس', 'list' => ['Supplier opening_balance mutation کو immutable supplier-ledger payment سے بدلیں۔', 'Idempotency key اور reversal workflow بنائیں۔']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'قبولیت', 'acceptance' => ['تمام legs balance ہوں۔', 'Expense head ادائیگی ایک EXPAY اور ایک cash/bank قطار بنائے۔', 'Linked expense_payment P&L میں ایک بار گنا جائے۔', 'Retry duplicate نہ کرے۔', 'Insufficient balance پورا transaction fail کرے۔', 'Same-account reject۔']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'تکنیکی', 'classes' => ['page' => 'App\\Filament\\Clusters\\Finance\\CashBankTransfersPage', 'livewire' => ['App\\Livewire\\Finance\\ListFundTransfers', 'App\\Livewire\\Finance\\FundTransferForm'], 'service' => 'App\\Services\\Finance\\FundTransferService', 'table' => 'fund_transfers']],
        ],
    ],

    'expense-categories' => [
        'title' => 'Expense Categories', 'summary' => 'Operating یا other اخراجات کی اوپری درجہ بندی۔',
        'meta' => ['routes' => ['/admin/finance/expense-categories'], 'permissions' => ['finance.view', 'finance.create', 'finance.edit', 'finance.delete'], 'related' => ['expenses', 'expense-payments', 'financial-reports']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'مقصد', 'paragraphs' => ['Categories reusable expense heads کو reporting کے لیے گروپ کرتی ہیں؛ amount/date/wallet نہیں رکھتیں۔']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'فیلڈز', 'fields' => [
                $field('code', 'Code', 'string', false, 'Unique max 32؛ auto EXC-#####۔'),
                $field('name_en / name_ur', 'نام', 'string', true, 'English required max 255؛ Urdu optional۔'),
                $field('kind', 'P&L kind', 'enum', true, 'operating|other۔'),
                $field('status', 'Status', 'enum', true, 'active|inactive۔'),
                $field('notes', 'Notes', 'text', false, 'Max 2000۔'),
            ]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'عمل', 'steps' => ['Category اور kind بنائیں۔', 'اس کے نیچے heads بنائیں۔', 'History ہو تو delete کے بجائے inactive کریں۔']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI', 'paragraphs' => ['AI لازمی نہیں؛ صرف reviewable translation suggestion۔']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'قواعد', 'list' => ['Code unique۔', 'Heads ہوں تو delete بند۔', 'Rebuild میں inactive category کو نئے head سے service level پر روکیں۔']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'روابط', 'list' => ['Heads category سے تعلق رکھتے ہیں؛ P&L payment → head → category/kind سے roll-up۔']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild نوٹس', 'list' => ['kind کو explicit P&L classification رکھیں۔', 'Stable reporting code اور recategorization audit شامل کریں۔']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'قبولیت', 'acceptance' => ['Heads والی category delete نہ ہو۔', 'Totals صرف linked posted payments کے ہوں۔', 'Label change historical payments نہ بدلے۔']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'تکنیکی', 'classes' => ['page' => 'App\\Filament\\Clusters\\Finance\\ExpenseCategoriesPage', 'livewire' => ['App\\Livewire\\Finance\\ListExpenseCategories', 'App\\Livewire\\Finance\\ExpenseCategoryForm'], 'service' => 'App\\Services\\Finance\\ExpenseCategoryService', 'model' => 'App\\Models\\ExpenseCategory', 'table' => 'expense_categories']],
        ],
    ],

    'expenses' => [
        'title' => 'Expense Heads', 'summary' => 'دوسری سطح: reusable EXP-##### اکاؤنٹس؛ معلومات ہیں مگر amount نہیں۔',
        'meta' => ['routes' => ['/admin/finance/expenses'], 'permissions' => ['finance.view', 'finance.create', 'finance.edit', 'finance.delete'], 'related' => ['expense-categories', 'expense-payments']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'مقصد', 'paragraphs' => ['Head مستقل خرچ/ذمہ داری کی شناخت ہے، مثلاً Shop Electricity — Meter 123۔ Save سے رقم نہیں چلتی۔']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'فیلڈز', 'fields' => [
                $field('expense_number', 'نمبر', 'string', false, 'Unique auto EXP-#####۔'),
                $field('expense_category_id', 'Category', 'FK', true, 'موجود ہو؛ نئے record کے لیے active enforce کریں۔'),
                $field('name_en / name_ur', 'نام', 'string', true, 'English required max 255؛ Urdu optional۔'),
                $field('information', 'معلومات', 'text', false, 'Max 5000؛ لمبی نوٹس۔'),
                $field('issue_day', 'ماہانہ ایشو دن', '1-31', false, 'ہر مہینے دہرایا جاتا ہے؛ یاددہانی اسی دن سے۔'),
                $field('due_day', 'ماہانہ آخری دن', '1-31', false, 'ادائیگی کی حد؛ issue سے بڑا/برابر تو اسی مہینے، ورنہ اگلے میں۔'),
                $field('detail_1–4', 'تفصیل ۱–۴', 'string', false, 'Max 120؛ عام مختصر لائنیں (کسٹمر ID، میٹر، اکاؤنٹ، یا چائے/بسکٹ/ٹشو)۔'),
                $field('estimated_cost', 'تخمینی لاگت', 'decimal(14,2)', false, 'منصوبہ بندی کی رقم؛ ایڈٹ ہو سکتی ہے؛ کیش/بینک پوسٹ نہیں۔'),
                $field('status', 'Status', 'enum', true, 'active|inactive۔'),
            ]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'عمل', 'steps' => ['Category چنیں۔', 'Head اور reference information لکھیں۔', 'Save کریں؛ اصل رقم Expense Payments پر دیں۔', 'ختم ہونے پر inactive کریں۔']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI enrichment', 'paragraphs' => ['ExpenseEnrichmentService Urdu نام یا information تجویز کر سکتی ہے؛ amount نہیں۔']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'قواعد', 'list' => ['Amount/date/wallet اس صفحے پر نہیں۔ estimated_cost صرف تخمینہ ہے، رقم نہیں چلتی۔', 'Payments والا head delete نہیں۔', 'Inactive head نئی EXPAY میں نہیں۔']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'روابط', 'list' => ['ExpenseCategory، ExpensePayments، cash/bank transactions، P&L۔']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild نوٹس', 'list' => ['EXP-##### کو EXPAY-##### سے الگ رکھیں۔', 'Category reassignment کے لیے effective dating/audit پر غور کریں۔']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'قبولیت', 'acceptance' => ['Head create/edit سے balance نہ بدلے۔', 'Paid head delete نہ ہو۔', 'Payment selector میں صرف active heads۔']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'تکنیکی', 'classes' => ['page' => 'App\\Filament\\Clusters\\Finance\\ExpensesPage', 'livewire' => ['App\\Livewire\\Finance\\ListExpenses', 'App\\Livewire\\Finance\\ExpenseForm'], 'services' => ['App\\Services\\Finance\\ExpenseService', 'App\\Services\\Finance\\ExpenseEnrichmentService'], 'model' => 'App\\Models\\Expense', 'table' => 'expenses']],
        ],
    ],

    'expense-payments' => [
        'title' => 'Expense Payments', 'summary' => 'تیسری سطح: immutable EXPAY-##### vouchers؛ cash OUT یا bank debit اور P&L۔',
        'meta' => ['routes' => ['/admin/finance/expense-payments'], 'permissions' => ['finance.view', 'finance.create', 'finance.edit'], 'related' => ['expenses', 'cash-book', 'bank-accounts', 'cash-bank-transfers', 'financial-reports']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'مقصد', 'paragraphs' => ['ایک active head کے خلاف ایک حقیقی payment؛ create اسی transaction میں wallet ledger post کرتی ہے، بعد میں صرف notes update۔ یہی EXPAY Cash/Bank Transfers کے expense_payment type سے بھی بن سکتی ہے۔']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'فیلڈز', 'fields' => [
                $field('payment_number', 'Voucher', 'string', false, 'Unique auto EXPAY-#####۔'),
                $field('expense_id', 'Head', 'FK', true, 'موجود اور active۔'),
                $field('amount', 'رقم', 'decimal(2)', true, 'Min 0.01۔'),
                $field('payment_date / payment_time', 'وقت', 'date / time', true, 'Date required؛ time optional H:i۔'),
                $field('source_wallet', 'Wallet', 'enum', true, 'cash|bank۔'),
                $field('bank_account_id', 'Bank account', 'FK', false, 'Bank پر active account لازم؛ cash پر null۔'),
                $field('notes', 'Notes', 'text', false, 'Max 2000؛ post کے بعد صرف یہی editable۔'),
            ]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'عمل', 'steps' => ['Active head چنیں۔', 'Verified amount/date اور wallet دیں۔', 'ایک بار save کر کے EXPAY + ledger بنائیں۔', 'بعد میں صرف notes؛ correction کے لیے rebuild میں reversal۔']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI', 'paragraphs' => ['AI نہیں؛ amount/date/head/wallet انسانی ثبوت سے۔']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'Posting اور security', 'list' => ['Cache::lock("finance-expense-payment:{wallet}",15)->block(10) پھر DB::transaction۔', 'Cash = CashBook OUT؛ Bank = debit۔', 'Create کے بعد amount/date/head/wallet lock؛ update صرف notes۔', 'Delete ہمیشہ reject۔', 'hasPermission("finance.create|edit|view")۔']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'P&L اور ledgers', 'list' => ['Cash/bank transaction expense_payment_id سے link۔', 'Cash/Bank Transfers expense_payment EXPAY بناتی ہے اور fund_transfers.expense_payment_id سیٹ کرتی ہے تاکہ P&L ایک بار گنے۔', 'P&L payment_date کے مطابق amount اور category kind لے۔', 'Expense analysis payment → expense → category۔']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild نوٹس', 'list' => ['posted_at/by، reversal_of_id، attachment اور idempotency key شامل کریں۔', 'Float کے بجائے decimal/minor-unit arithmetic۔', 'Balance check + ledger insert ایک locked transaction میں۔']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'قبولیت', 'acceptance' => ['Cash EXPAY ایک linked OUT۔', 'Bank EXPAY ایک linked debit۔', 'Ledger fail ہو تو payment باقی نہ رہے۔', 'Concurrent overdraft میں صرف جائز post کامیاب۔', 'صرف notes edit؛ delete منع۔']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'تکنیکی', 'classes' => ['page' => 'App\\Filament\\Clusters\\Finance\\ExpensePaymentsPage', 'livewire' => ['App\\Livewire\\Finance\\ListExpensePayments', 'App\\Livewire\\Finance\\ExpensePaymentForm'], 'service' => 'App\\Services\\Finance\\ExpensePaymentService', 'model' => 'App\\Models\\ExpensePayment', 'tables' => ['expense_payments', 'cash_book_transactions', 'bank_account_transactions']]],
        ],
    ],

    'cash-book' => [
        'title' => 'Cash Book', 'summary' => 'Read-only cash ledger اور running balance کا placeholder۔',
        'meta' => ['routes' => ['/admin/finance/cash-book'], 'permissions' => ['finance.view'], 'related' => ['expense-payments', 'cash-bank-transfers']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'مقصد', 'paragraphs' => ['CashBookTransaction IN/OUT، references اور running balance کی planned screen۔ Cluster page ابھی placeholder ہے مگر CashBookService ledger لکھتی ہے۔']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'مجوزہ کالم', 'fields' => [$field('transaction_date / type / amount', 'Movement', 'date / IN|OUT / decimal', true, 'Ledger سے read۔'), $field('description / reference / actor', 'Audit', 'text / relations', true, 'Source سے link؛ copy نہیں۔')]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'عمل', 'steps' => ['Date/type/source filter۔', 'Source voucher کھولیں۔', 'Edit کیے بغیر export/reconcile۔']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI', 'paragraphs' => ['AI posting یا balance calculation نہیں۔']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'قواعد', 'list' => ['عام صارف کے لیے read-only۔', 'Running balance date/id ترتیب سے deterministic۔', 'Correction reversal سے۔']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'روابط', 'list' => ['ExpensePaymentService اور FundTransferService entries لکھتے ہیں؛ Reports یہی ledger پڑھتا ہے۔']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild نوٹس', 'list' => ['cash_book_transactions پر projection؛ دوسرا cash store نہیں۔', 'Opening/closing، source links، export، reconciliation شامل کریں۔']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'قبولیت', 'acceptance' => ['ہر row source تک جائے۔', 'Opening + IN − OUT = closing۔', 'Direct create/edit/delete نہ ہو۔']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'تکنیکی', 'classes' => ['page' => 'App\\Filament\\Clusters\\Finance\\CashBookPage', 'service' => 'App\\Services\\Finance\\CashBookService', 'table' => 'cash_book_transactions', 'status' => 'placeholder UI']],
        ],
    ],

    'financial-reports' => [
        'title' => 'Financial Reports', 'summary' => 'Finance placeholder؛ canonical P&L فی الحال Reports module میں۔',
        'meta' => ['routes' => ['/admin/finance/financial-reports'], 'permissions' => ['finance.view'], 'related' => ['reports', 'expense-payments', 'bank-accounts', 'cash-book']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'مقصد', 'paragraphs' => ['Cash position، bank movement، expense analysis اور P&L کا planned launch point۔ موجودہ صفحہ placeholder؛ implemented output Reports میں۔']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'مجوزہ filters', 'fields' => [$field('date_from / date_to', 'مدت', 'date range', true, 'Payment/transaction date پر یکساں۔'), $field('wallet / bank / category / kind', 'ابعاد', 'filters', false, 'Source data بدلے بغیر drill-down۔')]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'عمل', 'steps' => ['Period چنیں۔', 'Opening/movements/closing/P&L دیکھیں۔', 'Source vouchers تک drill کریں۔', 'Filters metadata کے ساتھ export۔']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI', 'paragraphs' => ['AI deterministic totals کے بعد خلاصہ دے سکتا ہے؛ اعداد calculate/alter نہیں۔']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'قواعد', 'list' => ['finance.view access۔', 'P&L expenses posted expense_payments سے، heads سے نہیں۔', 'Transfer legs income/expense میں double count نہ ہوں۔']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'روابط', 'list' => ['Reports\\ProfitLossReportService canonical ہے۔', 'Cash/Bank services wallet data؛ Purchasing/Sales revenue/COGS context۔']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild نوٹس', 'list' => ['Report services reuse کریں؛ Finance میں logic clone نہ کریں۔', 'Period، timezone، status اور reversal treatment مشترک contract میں۔']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'قبولیت', 'acceptance' => ['ایک filters پر Finance/Reports P&L برابر۔', 'Transfers totals inflate نہ کریں۔', 'ہر total source rows تک جائے۔']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'تکنیکی', 'classes' => ['page' => 'App\\Filament\\Clusters\\Finance\\FinancialReportsPage', 'report_service' => 'App\\Services\\Reports\\ProfitLossReportService', 'status' => 'placeholder UI']],
        ],
    ],
];
