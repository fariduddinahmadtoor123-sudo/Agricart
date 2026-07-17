<?php

$field = static fn (string $name, string $label, string $type, bool $required, string $rules): array => compact('name', 'label', 'type', 'required', 'rules');

return [
    'overview' => [
        'title' => 'Contacts — Overview',
        'summary' => 'Customer and supplier masters shared by sales, store, purchasing, finance, and ledger reports.',
        'meta' => ['routes' => ['/admin/contacts/overview'], 'permissions' => ['contacts.view'], 'related' => ['sales', 'store', 'purchasing', 'finance', 'reports']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'Purpose', 'paragraphs' => ['Contacts is the party master. Customers identify buyers across POS and online channels; suppliers identify vendors, purchasing categories, contacts, portal access, and payment details.']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'Record families', 'fields' => [
                $field('customers', 'Customers', 'master/auth record', true, 'CUS-#####; online or offline channel; phone identity and credit controls.'),
                $field('suppliers', 'Suppliers', 'master/optional portal record', true, 'SUP-#####; bilingual business, contacts, banks, categories, balances.'),
            ]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'Workflow', 'steps' => ['Create or locate the party before a transaction.', 'Verify phone, names, address, opening balance, and credit limit.', 'Attach sales/purchases to the party.', 'Use customer/supplier ledger reports for transaction history and balance.']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI', 'paragraphs' => ['Enrichment can propose bilingual names, addresses, cities, and contact text. It must not invent phone numbers, bank numbers, balances, credit limits, credentials, or legal identity.']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'Rules', 'list' => ['Module RBAC uses contacts.view/create/edit/delete through User::hasPermission().', 'Phone and email uniqueness are identity controls, not cosmetic validation.', 'Historical parties should normally be deactivated rather than deleted.']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'Integrations', 'list' => ['Sales/POS and Store link customers.', 'Purchasing and vendor payments link suppliers.', 'CustomerLedgerReportService and SupplierLedgerReportService aggregate party activity.', 'Catalog categories can be assigned to suppliers.']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild notes', 'list' => ['Adopt one immutable party ID while preserving channel-specific authentication.', 'Replace destructive deletion with active/archive controls when financial references exist.', 'Normalize phone numbers into indexed canonical columns instead of JSON LIKE scans.']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'Acceptance', 'acceptance' => ['A transaction stores the party ID and remains traceable after contact edits.', 'Duplicate canonical phones are rejected where required.', 'Unauthorized contacts.* actions are hidden and rejected server-side.', 'Ledger totals reconcile to source sales, purchases, payments, and opening balance.']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'Technical', 'classes' => ['cluster' => 'App\\Filament\\Clusters\\Contacts', 'models' => ['App\\Models\\Customer', 'App\\Models\\Supplier'], 'services' => ['App\\Services\\Contacts\\CustomerService', 'App\\Services\\Contacts\\SupplierService']]],
        ],
    ],

    'customers' => [
        'title' => 'Customers',
        'summary' => 'Online and offline buyer records for POS, store authentication, credit control, and customer ledgers.',
        'meta' => ['routes' => ['/admin/contacts/customers'], 'permissions' => ['contacts.view', 'contacts.create', 'contacts.edit', 'contacts.delete'], 'related' => ['sales', 'store', 'reports']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'Purpose', 'paragraphs' => ['Maintain a single customer identity used by POS sales and online orders. Offline customers are created in admin; online customers retain storefront authentication fields while admins edit only permitted profile/accounting fields.']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'Fields', 'fields' => [
                $field('customer_number', 'Customer number', 'string', false, 'Unique; auto CUS-##### for offline customers.'),
                $field('customer_channel', 'Channel', 'enum', true, 'online|offline; determines admin edit rules.'),
                $field('display_name / name_en / name_ur', 'Names', 'strings', true, 'Offline display_name required max 255; bilingual names optional.'),
                $field('phoneRows / contacts / phone', 'Phone numbers', 'JSON + legacy primary', true, 'At least one offline phone; max 30; normalized duplicate check across phone and contacts.'),
                $field('address_quick / address_en / address_ur', 'Address', 'text', false, 'Quick max 500; localized max 2000.'),
                $field('city_key / city_en / city_ur', 'City', 'key + strings', false, 'Localized city resolution.'),
                $field('opening_balance', 'Opening balance', 'decimal(2)', false, 'Defaults 0; may be signed.'),
                $field('credit_limit', 'Credit limit', 'decimal(2)', false, 'Nullable; min 0.'),
                $field('email', 'Email', 'email', false, 'Unique. Offline blank email becomes an internal contacts.agricart.local address.'),
                $field('accepts_marketing / is_active', 'Flags', 'boolean', false, 'Marketing consent and transaction eligibility.'),
                $field('profile_photo_path', 'Photo', 'WebP path', false, 'Image max 5MB; stored under customers/photos.'),
                $field('ai_status', 'AI status', 'enum', false, 'none|pending|enriched|failed|manual.'),
            ]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'Workflow', 'steps' => ['Search before create by name, customer number, and normalized phone.', 'Create an offline customer with display name and at least one unique phone.', 'Optionally add bilingual address, city, opening balance, credit limit, email, and photo.', 'At POS/order time select the customer ID; review history in Customer Ledger.', 'Deactivate rather than delete a financially referenced customer.']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI enrichment', 'paragraphs' => ['CustomerEnrichmentService can complete bilingual names/address/city from supplied text. Review every suggestion. Phone, email, balances, consent, active state, and credentials stay deterministic/manual.']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'Validation and access', 'list' => ['List/Form call hasPermission("contacts.{action}") with Super Admin bypass.', 'Offline display_name and one phone are required.', 'Phone normalization strips non-digits, trims Pakistan 92 prefix/length, and rejects duplicates in the form and existing records.', 'Online admin update does not replace storefront first/last name, email, password, or channel.', 'Current delete removes the photo and customer row; rebuild must block deletion when transactions exist.']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'Integrations', 'list' => ['POS/Sales stores customer_id.', 'OnlineSaleOrder belongs to Customer and storefront auth uses the same model.', 'Customer Ledger combines opening balance and sales/payment activity.', 'Profile photo uses public storage.']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild notes', 'list' => ['Move canonical phone(s) from JSON to a customer_phones table with normalized unique index.', 'Represent opening balance as an immutable opening ledger entry, not an editable master amount.', 'Replace generated fake offline email dependency with nullable login identity or separate credentials table.', 'Add FK-aware archive/anonymize flow and audit changes to credit limit/status.']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'Acceptance', 'acceptance' => ['Offline create generates unique CUS-##### and requires one unique phone.', 'Equivalent +92/0/spaced phone forms resolve to one canonical identity.', 'Online admin edit cannot overwrite authentication-owned fields.', 'Photo replacement removes the old file only after successful save.', 'Customer ledger reconciles to source activity and opening entry.']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'Technical', 'classes' => ['page' => 'App\\Filament\\Clusters\\Contacts\\CustomersPage', 'livewire' => ['App\\Livewire\\Customers\\ListCustomers', 'App\\Livewire\\Customers\\Form'], 'services' => ['App\\Services\\Contacts\\CustomerService', 'App\\Services\\Contacts\\CustomerEnrichmentService'], 'model' => 'App\\Models\\Customer', 'table' => 'customers']],
        ],
    ],

    'suppliers' => [
        'title' => 'Suppliers',
        'summary' => 'Vendor masters with bilingual identity, contacts, bank references, categories, portal access, and payable context.',
        'meta' => ['routes' => ['/admin/contacts/suppliers'], 'permissions' => ['contacts.view', 'contacts.create', 'contacts.edit', 'contacts.delete'], 'related' => ['purchasing', 'catalog', 'finance', 'reports']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'Purpose', 'paragraphs' => ['Suppliers identify vendors selected on purchases and supplier-ledger activity. The record also stores contacts, product categories, reference bank accounts, optional portal credentials, and identity images.']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'Fields', 'fields' => [
                $field('supplier_code', 'Supplier code', 'string', false, 'Unique; auto SUP-#####.'),
                $field('supplier_type / status', 'Type and status', 'string / enum', true, 'Type max 64; status active|inactive.'),
                $field('business_name_en / business_name_ur', 'Business name', 'strings', true, 'At least one required; max 255.'),
                $field('country_code / country_ur', 'Country', 'strings', true, 'Country code required max 8.'),
                $field('city_key / city_en / city_ur', 'City', 'key + strings', true, 'Pakistan requires city_key; other countries require city_en.'),
                $field('address_en / address_ur', 'Address', 'text', true, 'At least one required; max 2000.'),
                $field('contacts', 'Contacts', 'JSON rows', true, 'First row needs mobile and English or Urdu contact name.'),
                $field('bank_accounts', 'Bank references', 'JSON rows', false, 'Bank key/name, bilingual account title, account number.'),
                $field('category_ids', 'Supply categories', 'pivot IDs', false, 'Existing categories; sync category_supplier.'),
                $field('credit_limit / opening_balance', 'Account controls', 'decimal(2)', false, 'Numeric; opening defaults 0.'),
                $field('portal_enabled / email / password', 'Supplier portal', 'boolean/credentials', false, 'Portal requires unique email and password min 8 on create.'),
                $field('profile_photo_path / card_front_path / card_back_path', 'Identity media', 'public paths', false, 'Managed uploads; preserve old file if no replacement.'),
                $field('ai_status', 'AI status', 'enum', false, 'none|pending|enriched|failed|manual.'),
            ]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'Workflow', 'steps' => ['Search for the supplier before create.', 'Enter business, location, and required primary contact.', 'Assign supplied catalog categories and optional bank references.', 'Enable portal only with verified unique email and secure password.', 'Select supplier on purchases; review balances and payments in Supplier Ledger.', 'Deactivate instead of deleting a referenced supplier.']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI enrichment', 'paragraphs' => ['SupplierEnrichmentService can draft bilingual business/address/city/contact text. It must not invent mobile numbers, bank account numbers, balances, category assignments, email, password, or portal state.']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'Validation and access', 'list' => ['List/Form use contacts.* permissions with Super Admin bypass.', 'At least one business name, one address, and one valid primary contact are mandatory.', 'Portal-enabled create requires email and password; disabling portal clears email on update.', 'Category IDs must exist and are de-duplicated before pivot sync.', 'Current delete removes stored files and row without demonstrated purchase-reference guard; rebuild must enforce referential archive.']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'Integrations', 'list' => ['Purchases reference supplier_id.', 'category_supplier links catalog categories.', 'FundTransfer vendor_payment links supplier and currently adjusts opening_balance.', 'SupplierLedgerReportService combines purchases/payments/opening position.']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild notes', 'list' => ['Normalize contacts and bank accounts into child tables with validation, masking, and audit history.', 'Represent opening balance and vendor payments as ledger entries; do not decrement the master opening_balance.', 'Separate supplier portal credentials from supplier business master.', 'Block deletion when purchases/payments exist; archive while retaining reporting identity.']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'Acceptance', 'acceptance' => ['Create generates unique SUP-##### and persists category pivot once.', 'Primary contact name and mobile are required.', 'Portal cannot enable without valid unique credentials.', 'A vendor payment appears once in supplier and wallet ledgers.', 'Referenced suppliers cannot be destructively deleted.']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'Technical', 'classes' => ['page' => 'App\\Filament\\Clusters\\Contacts\\SuppliersPage', 'livewire' => ['App\\Livewire\\Suppliers\\ListSuppliers', 'App\\Livewire\\Suppliers\\Form'], 'services' => ['App\\Services\\Contacts\\SupplierService', 'App\\Services\\Contacts\\SupplierEnrichmentService'], 'model' => 'App\\Models\\Supplier', 'tables' => ['suppliers', 'category_supplier']]],
        ],
    ],
];
