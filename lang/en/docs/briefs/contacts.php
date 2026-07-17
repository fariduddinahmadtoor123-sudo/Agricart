<?php

/**
 * Simple briefs — Contacts (English tab)
 * Supplier form is the rebuild-grade sample; customers + suppliers + overview follow the same structure.
 */
return [
  'suppliers' => 
  [
    'form' => 
    [
      'title' => 'Supplier form — Add / View / Edit',
      'summary' => 'Three-box sample: (1) current system — essay + UI Flow + FR + fields + Validation + Database + API + Permissions + Edge Cases; (2) my recommendations; (3) Ready-to-Code prompt.',
      'article' => 
      [
        0 => 'Before purchasing can start, you need the person or company that supplies goods. This form builds that supplier master card. On Contacts → Suppliers, Add Supplier opens the shared right-side form panel — not a separate full page. Create, Edit, and View all use the same panel.',
        1 => 'The saved record is later used by Purchasing, Supplier Ledger, vendor fund transfers, and category links. Clean business name, address, and first mobile matter as much as a customer phone.',
        2 => 'Today the system auto-builds the supplier code (usually SUP-#####). At least one of English or Urdu business name is required. At least one of English or Urdu address is required. Country defaults to Pakistan; Pakistani cities come from a list, other countries use a manual city.',
        3 => 'The first contact row is required: name (EN or UR) plus mobile. Extra contacts, bank accounts, categories, credit limit, and opening balance are optional. Enabling portal on create makes email and password required. Profile and card images are optional.',
        4 => 'AI may fill bilingual business name, address, city, and contact names — never phones, bank numbers, balances, categories, email, or passwords. Footer: AI → Save & Add Next → Save & Close → Cancel. View mode is fully read-only.',
      ],
      'fields' => 
      [
        0 => 
        [
          'name' => 'supplier_code',
          'label' => 'Supplier code',
          'note' => 'Auto SUP-#####; not asked from the user',
        ],
        1 => 
        [
          'name' => 'supplier_type',
          'label' => 'Type',
          'note' => 'Optional short text (max 64)',
        ],
        2 => 
        [
          'name' => 'status',
          'label' => 'Status',
          'note' => 'active or inactive; new records usually active',
        ],
        3 => 
        [
          'name' => 'business_name_en',
          'label' => 'Business name — English',
          'note' => 'Required if Urdu is empty',
        ],
        4 => 
        [
          'name' => 'business_name_ur',
          'label' => 'Business name — Urdu',
          'note' => 'Required if English is empty',
        ],
        5 => 
        [
          'name' => 'country_code / country_ur',
          'label' => 'Country',
          'note' => 'Code required; default PK',
        ],
        6 => 
        [
          'name' => 'city_key',
          'label' => 'City (Pakistan)',
          'note' => 'Required from list when PK',
        ],
        7 => 
        [
          'name' => 'city_manual / city_en / city_ur',
          'label' => 'City (other country)',
          'note' => 'Manual city required outside PK',
        ],
        8 => 
        [
          'name' => 'address_en / address_ur',
          'label' => 'Address',
          'note' => 'At least one language; max 2000',
        ],
        9 => 
        [
          'name' => 'contactRows[].contact_name_en/ur',
          'label' => 'Contact name',
          'note' => 'EN or UR required on first row',
        ],
        10 => 
        [
          'name' => 'contactRows[].mobile_number',
          'label' => 'Mobile',
          'note' => 'Required on first row; more rows allowed',
        ],
        11 => 
        [
          'name' => 'category_ids',
          'label' => 'Categories',
          'note' => 'Optional; existing categories; pivot sync',
        ],
        12 => 
        [
          'name' => 'bankRows[]',
          'label' => 'Bank reference',
          'note' => 'Optional: bank, bilingual title, account number',
        ],
        13 => 
        [
          'name' => 'credit_limit',
          'label' => 'Credit limit',
          'note' => 'Optional number',
        ],
        14 => 
        [
          'name' => 'opening_balance',
          'label' => 'Opening balance',
          'note' => 'Optional; blank = 0',
        ],
        15 => 
        [
          'name' => 'portal_enabled',
          'label' => 'Supplier portal',
          'note' => 'Optional on/off',
        ],
        16 => 
        [
          'name' => 'email',
          'label' => 'Email',
          'note' => 'Required + unique when portal on create',
        ],
        17 => 
        [
          'name' => 'password / password_confirmation',
          'label' => 'Password',
          'note' => 'Required on portal create; min 8',
        ],
        18 => 
        [
          'name' => 'profilePhotoUpload',
          'label' => 'Profile photo',
          'note' => 'Optional; max 5MB',
        ],
        19 => 
        [
          'name' => 'cardFrontUpload / cardBackUpload',
          'label' => 'Card images',
          'note' => 'Optional front/back',
        ],
        20 => 
        [
          'name' => 'AI + ai_status',
          'label' => 'AI help',
          'note' => 'Text only; never sensitive numbers',
        ],
        21 => 
        [
          'name' => 'footer',
          'label' => 'Footer actions',
          'note' => 'AI → Save & Add Next → Save & Close → Cancel',
        ],
      ],
      'improvements' => 
      [
        0 => 
        [
          'text' => 'Keep: auto code, bilingual name/address, city list, and first mobile — purchasing breaks without them.',
          'suggest' => 'keep',
        ],
        1 => 
        [
          'text' => 'Improve: replace master opening_balance mutation with ledger entries; post payments once via Finance.',
          'suggest' => 'add',
        ],
        2 => 
        [
          'text' => 'Improve: block delete when purchases/payments exist; use inactive/archive only.',
          'suggest' => 'add',
        ],
        3 => 
        [
          'text' => 'Improve: separate portal login credentials from the supplier master so passwords stay cleaner.',
          'suggest' => 'add',
        ],
        4 => 
        [
          'text' => 'Improve: do not save empty bank rows; mask/audit account numbers.',
          'suggest' => 'add',
        ],
        5 => 
        [
          'text' => 'Remove / not yet: NTN or STRN — do not lengthen the form until tax reporting is real.',
          'suggest' => 'remove',
        ],
        6 => 
        [
          'text' => 'Optional later: payment terms (days) when purchasing has a credit policy; credit limit is enough now.',
          'suggest' => 'optional',
        ],
        7 => 
        [
          'text' => 'Keep: AI, but review before Save; never invent phones/banks/passwords.',
          'suggest' => 'keep',
        ],
      ],
      'technical' => 
      [
        0 => 'Route: /admin/contacts/suppliers',
        1 => 'Livewire: App\\Livewire\\Suppliers\\Form',
        2 => 'Services: SupplierService + SupplierEnrichmentService',
        3 => 'Tables: suppliers, category_supplier',
        4 => 'Permissions: contacts.create / contacts.edit',
        5 => 'formKey: suppliers',
      ],
      'prompt' => 'Build Agricart ERP Contacts → Suppliers Add/Edit Supplier form to match the CURRENT live system. Do not invent improvements — copy today’s behaviour only.

## UI Flow
1. /admin/contacts/suppliers → Add Supplier → shared form panel
2. Modes: create | edit | view (view = read-only)
3. Footer: AI → Save & Add Next → Save & Close → Cancel
4. Design: square corners, Arial, primary #83B735

## Functional Requirements
FR-01..11 as in the brief: supplier master CRUD panel, auto code, bilingual name/address, PK city list, first contact required, optional banks/categories/credit/balance/portal/images, AI text-only, Save & Add Next.

## Fields + Validation (live rules)
1) supplier_code auto SUP-#####
2) supplier_type optional max 64
3) status active|inactive; create default active
4) business_name_en/ur — required_without each other; max 255
5) country_code required default PK; country_ur sync
6) PK → city_key required; non-PK → city_manual required
7) address_en/ur — required_without; max 2000
8) contactRows.0: name EN or UR + mobile required; extra rows optional
9) category_ids optional → category_supplier sync
10) bankRows optional JSON
11) credit_limit / opening_balance nullable numeric; blank opening = 0
12) portal create → unique email required + password min 8 + confirmation
13) edit password only when a new value is entered
14) images optional max 5MB
15) AI: bilingual business/address/city/contact names only; never mobile/bank/balances/categories/email/password/portal
16) ai_status: none|pending|enriched|failed|manual

## Database
- suppliers + category_supplier (columns as in brief)
- contacts / bank_accounts JSON

## API / Integration
- No public REST; Livewire + SupplierService + SupplierEnrichmentService
- events: supplier-form-open / supplier-form-action

## Permissions
- contacts.create / contacts.edit; view = read-only

## Edge Cases
- country change clears city
- AI fills empty fields only
- no hard delete for referenced suppliers
- Save & Add Next resets to empty create

Acceptance: unique code; block save without first mobile/name/address; block portal create without email/password.
List page is out of scope for this prompt.',
      'ui_flow' => 
      [
        0 => 'Go to Contacts → Suppliers list.',
        1 => 'Click Add Supplier → shared right-side form panel opens (no separate full-page URL).',
        2 => 'Create: empty form; Edit/View: load supplier; View = fully read-only.',
        3 => 'Optional: AI fills bilingual name/address/city/contact names (never phone/bank/password).',
        4 => 'Save & Close = save + close; Save & Add Next = save + empty create; Cancel = close without save.',
        5 => 'On success show status / refresh list; on validation fail show danger message.',
      ],
      'functional_requirements' => 
      [
        0 => 'FR-01: Create a supplier master record for Purchasing / Supplier Ledger.',
        1 => 'FR-02: View or edit an existing supplier in the same form panel.',
        2 => 'FR-03: Auto-generate a unique supplier_code (SUP-#####).',
        3 => 'FR-04: Support bilingual business name and address (at least one language required).',
        4 => 'FR-05: Pakistan → city list; other countries → manual city.',
        5 => 'FR-06: Require at least one contact row (name + mobile).',
        6 => 'FR-07: Optional banks, categories, credit limit, opening balance.',
        7 => 'FR-08: Optional supplier portal — email/password required on create when enabled.',
        8 => 'FR-09: Optional profile and card images (max 5MB).',
        9 => 'FR-10: AI enrichment fills text fields only; never invent sensitive data.',
        10 => 'FR-11: Support rapid multi-create via Save & Add Next.',
      ],
      'validation' => 
      [
        0 => 'business_name_en required_without business_name_ur (and reverse); max 255',
        1 => 'address_en required_without address_ur (and reverse); max 2000',
        2 => 'contactRows.0: contact_name_en or _ur required; mobile_number required max 30',
        3 => 'country_code required max 8; status in:active,inactive',
        4 => 'PK → city_key required; non-PK → city_manual required max 255',
        5 => 'supplier_type nullable max 64; credit_limit / opening_balance nullable numeric',
        6 => 'email nullable|email|unique:suppliers; portal+create requires email/password (min 8 + confirmation)',
        7 => 'edit + portal: password only when user enters a new one',
        8 => 'images: nullable|image|max:5120 (5MB)',
        9 => 'deny save without contacts.create / contacts.edit',
      ],
      'database' => 
      [
        0 => 'Table suppliers: id, supplier_code (unique), supplier_type, status(default active), business_name_en/ur, country_code/ur, city_key/en/ur, address_en/ur, credit_limit decimal(15,2), opening_balance decimal(15,2) default 0, contacts JSON, bank_accounts JSON, portal_enabled, email (unique nullable), password (hashed), profile/card paths, ai_status, timestamps',
        1 => 'Table category_supplier: supplier_id + category_id unique pair; cascade on delete',
        2 => 'contacts / bank_accounts stored as JSON arrays',
        3 => 'Indexes: status, city_en, business_name_en',
      ],
      'api' => 
      [
        0 => 'No public REST API — Livewire form panel only (admin session)',
        1 => 'Events: supplier-form-open, supplier-form-action (ai | save_next | save_close)',
        2 => 'Services: SupplierService (create/update/storeUpload/toFormState), SupplierEnrichmentService (AI)',
        3 => 'formKey: suppliers; wired to shared Module Form Panel',
      ],
      'permissions' => 
      [
        0 => 'Create: contacts.create',
        1 => 'Edit: contacts.edit',
        2 => 'View mode: form read-only; save/AI disabled',
        3 => 'Without permission: access denied message; no write',
      ],
      'edge_cases' => 
      [
        0 => 'Changing country clears city fields — user must re-enter',
        1 => 'Name/address in only one language is valid; both empty blocks save',
        2 => 'Portal off → email/password not required',
        3 => 'AI fills empty text fields only; does not overwrite filled values',
        4 => 'Blank opening_balance persists as 0',
        5 => 'After create, mode becomes edit and password fields clear',
        6 => 'Hard delete of referenced supplier is out of scope — use inactive',
        7 => 'Category search shows results only after 2+ characters',
      ],
      'business_rules' => 
      [
        0 => '— To be filled later —',
        1 => '— To be filled later —',
        2 => '— To be filled later —',
        3 => '— To be filled later —',
        4 => '— To be filled later —',
      ],
      'non_functional_requirements' => 
      [
        0 => '— To be filled later —',
        1 => '— To be filled later —',
        2 => '— To be filled later —',
        3 => '— To be filled later —',
        4 => '— To be filled later —',
      ],
      'dependencies' => 
      [
        0 => 'Contacts — Suppliers list/form (master source); Contacts Overview search opens suppliers',
        1 => 'Catalog / Categories — category_supplier pivot; categories linked from supplier form',
        2 => 'Purchasing → Purchases — every purchase requires supplier_id (FK); supplier_balance_snapshot; invoice/payment updates suppliers.opening_balance',
        3 => 'Purchasing → Purchase Quotations — supplier_id required on quotation; supplier on list/print',
        4 => 'Purchasing → Purchase Payment Summary — rows from supplier_id (or manual party); search/add active suppliers',
        5 => 'Purchasing → Goods Received — no separate GRN module; stock updates via purchases.delivery_status (partial/received); supplier is the purchase’s supplier',
        6 => 'Inventory / Stock — stock increases on received purchases; supplier indirect via purchase',
        7 => 'Finance → Fund Transfers — destination_supplier_id (vendor payment); reduces supplier opening_balance',
        8 => 'Finance → Bank Accounts — bank_account_transactions.supplier_id; BankAccountService::paySupplier()',
        9 => 'Finance → Cash / Bank (general) — supplier as vendor payee on payment flows',
        10 => 'Reports → Supplier Ledger — AP movement from purchases, on-invoice payments, fund-transfer vendor payments',
        11 => 'Reports → Seasonal Purchases — purchase dataset (supplier column may be optional/hidden)',
        12 => 'Reports → Profit & Loss / others — no direct supplier master; indirect via purchase/cost data',
        13 => 'Store / Online → Order Dispatch — online_sale_order_supplier_assignments; WhatsApp/mail uses primaryMobile() and email',
        14 => 'Print — purchase and purchase-quotation prints show supplier name, address, phone, balance snapshot',
        15 => 'Print — Purchase Payment Summary print includes supplier/party column',
        16 => 'AI — SupplierEnrichmentService (form AI button); AiLogType::SupplierEnrichment logging',
        17 => 'Contacts Overview — suppliers card (count/link); no dedicated supplier widget on Dashboard module',
        18 => 'Permissions — contacts.create / contacts.edit on this form; purchasing/finance/reports use their own permissions',
        19 => 'Not in codebase yet — no separate Purchase Returns module; Re-order / Purchase Planning have no direct supplier_id link today',
        20 => 'Future — supplier portal_enabled + email/password on master; separate portal login UI may live outside this form',
      ],
    ],
    'list' => 
    [
      'fields' => 
      [
        0 => 
        [
          'name' => 'search',
          'label' => 'Search',
          'note' => 'supplier_code, business_name_en/ur, city_en/ur, email',
        ],
        1 => 
        [
          'name' => 'filter',
          'label' => 'Filter',
          'note' => 'status (active/inactive) + city',
        ],
        2 => 
        [
          'name' => 'columns',
          'label' => 'Columns',
          'note' => 'code, business, contact, city, type, status, categories, created',
        ],
        3 => 
        [
          'name' => 'Add Supplier',
          'label' => 'Add supplier',
          'note' => 'Form panel (create)',
        ],
        4 => 
        [
          'name' => 'row actions',
          'label' => 'Row actions',
          'note' => 'View / Edit / Delete',
        ],
      ],
      'ui_flow' => 
      [
        0 => 'Contacts → Suppliers opens a paginated list (15 per page, newest first).',
        1 => 'Toolbar: search, Filter (status + city), Columns, green Add Supplier.',
        2 => 'Add Supplier → shared form panel (create); row View/Edit open the same panel.',
        3 => 'Delete from a row — only with contacts.delete.',
      ],
      'functional_requirements' => 
      [
        0 => 'FR-01: Paginated list of all suppliers + categories.',
        1 => 'FR-02: Search code/business/city/email.',
        2 => 'FR-03: Filter by status and city.',
        3 => 'FR-04: Column toggle (at least business_name).',
        4 => 'FR-05: Add / View / Edit form panel.',
        5 => 'FR-06: Delete (with permission).',
      ],
      'validation' => 
      [
        0 => 'List page — no form validation',
        1 => 'Delete: only contacts.delete (or Super Admin)',
        2 => 'Empty search/filter → full list',
      ],
      'database' => 
      [
        0 => 'Reads from suppliers table (+ categories relation), paginate 15',
        1 => 'Order: created_at desc then id desc',
      ],
      'api' => 
      [
        0 => 'No public REST — Livewire ListSuppliers',
        1 => 'events: suppliers-search, suppliers-filter, suppliers-clear-filters, suppliers-column-toggle, suppliers-saved',
        2 => 'open-form-panel (formKey: suppliers)',
      ],
      'permissions' => 
      [
        0 => 'List/search: contacts.view',
        1 => 'Add: contacts.create; Edit: contacts.edit; Delete: contacts.delete',
        2 => 'Super Admin bypass',
      ],
      'edge_cases' => 
      [
        0 => 'Current delete removes supplier + files; purchase-reference guard not proven in this list (recommendation)',
        1 => 'Hiding all columns restores business_name',
        2 => 'City filter uses city_key',
      ],
      'business_rules' => 
      [
        0 => '— To be filled later —',
        1 => '— To be filled later —',
        2 => '— To be filled later —',
        3 => '— To be filled later —',
        4 => '— To be filled later —',
      ],
      'non_functional_requirements' => 
      [
        0 => '— To be filled later —',
        1 => '— To be filled later —',
        2 => '— To be filled later —',
        3 => '— To be filled later —',
        4 => '— To be filled later —',
      ],
      'dependencies' => 
      [
        0 => 'Suppliers Form — Add/View/Edit form panel',
        1 => 'Purchasing — supplier selection on purchases',
        2 => 'Reports → Supplier Ledger',
        3 => 'Catalog / Categories — category_supplier',
        4 => 'Contacts Overview — suppliers card',
      ],
    ],
  ],
  'customers' => 
  [
    'form' => 
    [
      'ui_flow' => 
      [
        0 => 'On Contacts → Customers, click Add Customer → shared right-side form panel (create).',
        1 => 'Row View / Edit opens the same panel; View = fully read-only.',
        2 => 'Offline (shop) customers are created here; online customers come from the storefront and several admin fields are restricted.',
        3 => 'phoneRows: one or more mobiles; rows can be added/removed (at least one stays).',
        4 => 'Optional AI button fills bilingual name/address (never phone/balance).',
        5 => 'Footer: AI → Save & Add Next → Save & Close → Cancel; on success dispatch customers-saved + close panel.',
      ],
      'functional_requirements' => 
      [
        0 => 'FR-01: Create an offline customer master used by POS and Customer Ledger.',
        1 => 'FR-02: View or edit an existing customer in the same form panel.',
        2 => 'FR-03: Auto-generate a unique customer_number (CUS-#####).',
        3 => 'FR-04: Optional bilingual names (name_en/ur) and address.',
        4 => 'FR-05: At least one mobile required; numbers normalized and unique.',
        5 => 'FR-06: City from Pakistan city list (city_key → city_en/ur).',
        6 => 'FR-07: Optional opening_balance and credit_limit.',
        7 => 'FR-08: Optional profile photo.',
        8 => 'FR-09: AI enrichment fills text name/address only; no sensitive data.',
        9 => 'FR-10: Rapid multi-create via Save & Add Next.',
        10 => 'FR-11: Online customers limited to restricted fields on admin update (auth fields preserved).',
      ],
      'validation' => 
      [
        0 => 'display_name required max 255',
        1 => 'customer_number nullable unique:customers max 32 (blank → auto CUS-#####)',
        2 => 'name_en / name_ur nullable max 255',
        3 => 'address_quick nullable max 500; address_en / address_ur nullable max 2000',
        4 => 'city_key nullable max 64; city_en / city_ur nullable max 255',
        5 => 'opening_balance nullable numeric (blank → 0); credit_limit nullable numeric min 0 (blank → null)',
        6 => 'email nullable email unique:customers; blank → offline.<code>@contacts.agricart.local',
        7 => 'phoneRows required array min 1; mobile_number nullable max 30; at least one after normalization',
        8 => 'phone unique: within the form and against existing records (strip 92/0, last 10 digits)',
        9 => 'online admin update: only name/address/city/balance/credit/flags/photo — no auth fields',
      ],
      'database' => 
      [
        0 => 'Table customers (base): id, customer_number (unique, nullable), first_name, last_name, email (unique), phone(30), password, address_line1/2, city, state, postal_code, country default Pakistan, accepts_marketing, is_active, email_verified_at, remember_token, timestamps',
        1 => 'Extension (contacts admin): customer_channel (default online), display_name, name_en/ur, address_en/ur, address_quick, city_key/en/ur, opening_balance decimal(15,2) default 0, credit_limit decimal(15,2), contacts JSON, profile_photo_path, ai_status',
        2 => 'Indexes: customer_channel, name_en, city_en',
        3 => 'Offline legacy: first/last from display_name; address_line1 from quick/en; city from city_en/ur; phone stored in contacts JSON + phone = first number',
      ],
      'api' => 
      [
        0 => 'No public REST API — Livewire form panel (admin session)',
        1 => 'events: customer-form-open, customer-form-action (ai|save_next|save_close), customers-saved, close-form-panel, customer-form-busy',
        2 => 'Services: CustomerService (createOffline/update/validateOffline/validateOnlineAdmin/nextCustomerNumber/normalizePhone/assertPhonesUnique/toFormState), CustomerEnrichmentService',
        3 => 'formKey: customers; wired to shared Module Form Panel',
      ],
      'permissions' => 
      [
        0 => 'Create: contacts.create',
        1 => 'Edit: contacts.edit; Super Admin bypass',
        2 => 'View mode: form read-only; save/AI disabled',
        3 => 'Without permission: persist is blocked',
      ],
      'edge_cases' => 
      [
        0 => 'Online customers are not created from admin; update preserves first/last name, email, password, channel',
        1 => 'Blank email → generated offline.<code>@contacts.agricart.local (avoids unique email break)',
        2 => '+92 / 0 / spaced phones resolve to one identity',
        3 => 'Duplicate phone (same form or existing record) blocks save',
        4 => 'Blank opening_balance → 0; blank credit_limit → null',
        5 => 'Photo replacement removes the old file only after successful save',
        6 => 'Current delete removes customer + photo (no ledger guard — see recommendations)',
        7 => 'Selecting a city auto-fills city_en/ur',
      ],
      'business_rules' => 
      [
        0 => '— To be filled later —',
        1 => '— To be filled later —',
        2 => '— To be filled later —',
        3 => '— To be filled later —',
        4 => '— To be filled later —',
      ],
      'non_functional_requirements' => 
      [
        0 => '— To be filled later —',
        1 => '— To be filled later —',
        2 => '— To be filled later —',
        3 => '— To be filled later —',
        4 => '— To be filled later —',
      ],
      'dependencies' => 
      [
        0 => 'Sales / POS — customer_id on sale',
        1 => 'Store / Online Orders — OnlineSaleOrder belongsTo Customer; storefront auth uses the same model',
        2 => 'Reports → Customer Ledger — opening balance + sales/payments',
        3 => 'Media — profile photo public disk (OptimizedImageStorage)',
        4 => 'AI — CustomerEnrichmentService + AiLog',
        5 => 'Contacts Overview — customers card (count/link)',
        6 => 'Permissions — contacts.* (view/create/edit/delete)',
      ],
    ],
    'list' => 
    [
      'ui_flow' => 
      [
        0 => 'Contacts → Customers opens a paginated list (15 per page, newest first).',
        1 => 'Toolbar: search (flex-grow), Filter dropdown, Columns dropdown, green Add Customer.',
        2 => 'Add Customer → shared form panel (create); row View / Edit open the same panel.',
        3 => 'Filter: channel (online/offline) + status (active/inactive); Columns: hide/show columns.',
        4 => 'Delete from a row (with confirm) — only with contacts.delete.',
      ],
      'functional_requirements' => 
      [
        0 => 'FR-01: Paginated list of all customers (online + offline together).',
        1 => 'FR-02: Search: customer_number, display_name, name_en/ur, first/last, phone, email, city.',
        2 => 'FR-03: Filter by channel and status.',
        3 => 'FR-04: Column toggle (at least name stays).',
        4 => 'FR-05: Open Add / View / Edit form panel.',
        5 => 'FR-06: Delete (with permission).',
      ],
      'validation' => 
      [
        0 => 'List page — no form validation',
        1 => 'Delete: only contacts.delete (or Super Admin)',
        2 => 'Empty search/filter → full list',
      ],
      'database' => 
      [
        0 => 'Reads from customers table (paginate 15)',
        1 => 'Order: created_at desc then id desc',
      ],
      'api' => 
      [
        0 => 'No public REST — Livewire ListCustomers',
        1 => 'events: customers-search, customers-filter, customers-clear-filters, customers-column-toggle, customers-saved',
        2 => 'open-form-panel (formKey: customers) opens the form',
      ],
      'permissions' => 
      [
        0 => 'List/search: contacts.view',
        1 => 'Add: contacts.create; Edit: contacts.edit; Delete: contacts.delete',
        2 => 'Super Admin bypass',
      ],
      'edge_cases' => 
      [
        0 => 'Online and offline customers in one list; channel filter separates them',
        1 => 'No ledger guard on delete — suggested in recommendations',
        2 => 'Hiding all columns auto-restores name',
      ],
      'business_rules' => 
      [
        0 => '— To be filled later —',
        1 => '— To be filled later —',
        2 => '— To be filled later —',
        3 => '— To be filled later —',
        4 => '— To be filled later —',
      ],
      'non_functional_requirements' => 
      [
        0 => '— To be filled later —',
        1 => '— To be filled later —',
        2 => '— To be filled later —',
        3 => '— To be filled later —',
        4 => '— To be filled later —',
      ],
      'dependencies' => 
      [
        0 => 'Customers Form — Add/View/Edit form panel',
        1 => 'Sales / POS, Store — customer selection',
        2 => 'Reports → Customer Ledger',
        3 => 'Contacts Overview — customers card',
      ],
    ],
  ],
  'overview' => 
  [
    'page' => 
    [
      'title' => 'Contacts — Overview',
      'summary' => 'Gateway to customer and supplier master data.',
      'article' => 
      [
        0 => 'The Contacts module holds two key lists: Customers and Suppliers. When building a new system, create this master data first, then wire Sales/Purchasing.',
      ],
      'fields' => 
      [
      ],
      'improvements' => 
      [
        0 => 
        [
          'text' => 'Keep only cards/links on Overview; heavy widgets later',
          'suggest' => 'keep',
        ],
      ],
      'technical' => 
      [
        0 => '/admin/contacts/overview',
      ],
      'prompt' => 'Contacts Overview page: short intro + links to Customers and Suppliers. Do not build a heavy dashboard.',
      'ui_flow' => 
      [
        0 => 'Sidebar Contacts → Overview opens.',
        1 => 'Customers and Suppliers cards (count/active) with links.',
        2 => 'From a card, go to the related list (Customers or Suppliers).',
      ],
      'functional_requirements' => 
      [
        0 => 'FR-01: Contacts module landing page.',
        1 => 'FR-02: Navigate to Customers and Suppliers lists.',
        2 => 'FR-03: Short count/active cards (ContactsOverviewService).',
      ],
      'validation' => 
      [
        0 => 'Overview — no form/validation',
      ],
      'database' => 
      [
        0 => 'No direct table; counts customers and suppliers',
      ],
      'api' => 
      [
        0 => 'No public REST — Filament page',
        1 => 'Service: ContactsOverviewService (cards)',
      ],
      'permissions' => 
      [
        0 => 'Access: contacts.view',
      ],
      'edge_cases' => 
      [
        0 => 'With no customers/suppliers the cards show 0',
      ],
      'business_rules' => 
      [
        0 => '— To be filled later —',
        1 => '— To be filled later —',
        2 => '— To be filled later —',
        3 => '— To be filled later —',
        4 => '— To be filled later —',
      ],
      'non_functional_requirements' => 
      [
        0 => '— To be filled later —',
        1 => '— To be filled later —',
        2 => '— To be filled later —',
        3 => '— To be filled later —',
        4 => '— To be filled later —',
      ],
      'dependencies' => 
      [
        0 => 'Customers — customer master',
        1 => 'Suppliers — supplier master',
        2 => 'Sales / Store / Purchasing / Finance / Reports — consumers of these masters',
      ],
    ],
  ],
];
