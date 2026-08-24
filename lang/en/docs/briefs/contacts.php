<?php

/**
 * Contacts user guides — Overview (EN) + AI Prompt + Notes (empty for later).
 */
return [
    'overview' => [
        'page' => [
            'title' => 'Contacts — Overview',
            'overview' => [
                'This is the landing page for the Contacts module. It shows short cards with counts and links to Customers, Suppliers, and Carriers.',
                'Use it to jump into the right master list. It is not a heavy dashboard — keep cards and links only.',
            ],
            'prompt' => <<<'PROMPT'
Build Agricart Contacts → Overview as a simple landing page.

Requirements:
- Short intro text for the Contacts module
- Cards/links to Customers, Suppliers, and Carriers (with counts if available)
- No heavy charts or widgets
- Route under /admin/contacts/overview
- Permission: contacts.view
PROMPT,
            'notes' => [],
        ],
    ],

    'customers' => [
        'list' => [
            'title' => 'Customers — List',
            'overview' => [
                'This page lists all customers (online storefront and offline/shop) in one table. Open Contacts → Customers.',
                'Use search, Filter (channel + status), Columns, and green Add Customer. Add / View / Edit open the shared right-side form panel. Delete needs contacts.delete.',
                'Pagination is 15 per page, newest first. Search covers customer number, names, phone, email, and city.',
            ],
            'prompt' => <<<'PROMPT'
Build Agricart Contacts → Customers list page to match the live system.

UI:
- Paginated table (15/page, newest first)
- Toolbar: search, Filter (channel online/offline + status active/inactive), Columns toggle, Add Customer
- Row actions: View / Edit / Delete
- Add / View / Edit open shared form panel (formKey: customers)

Behaviour:
- Search: customer_number, display_name, name_en/ur, first/last, phone, email, city
- Permissions: contacts.view / create / edit / delete
- Events: customers-search, customers-filter, customers-clear-filters, customers-column-toggle, customers-saved
- Livewire: ListCustomers
PROMPT,
            'notes' => [],
        ],
        'form' => [
            'title' => 'Customers — Add / Edit form',
            'overview' => [
                'This form creates and edits customer master records used by POS, online orders, and Customer Ledger. It opens in the shared form panel (not a separate full page).',
                'Required: display name and at least one mobile number. Customer number is usually auto CUS-#####. Names and address can be bilingual (EN/UR). City comes from the Pakistan city list when used.',
                'Optional: opening balance, credit limit, profile photo. AI may fill bilingual name/address text only — never phones or balances. Online customers are not created here; admin edits on online records are limited.',
                'Footer: AI → Save & Add Next → Save & Close → Cancel. View mode is read-only.',
            ],
            'prompt' => <<<'PROMPT'
Build Agricart Contacts → Customers Add/Edit form (shared form panel) to match the live system. Do not invent extra features.

UI flow:
1. Contacts → Customers → Add Customer opens form panel (create)
2. Modes: create | edit | view (view = read-only)
3. Footer: AI → Save & Add Next → Save & Close → Cancel

Required behaviour:
- Auto customer_number CUS-##### when blank
- display_name required
- At least one phone row; normalize and enforce unique mobiles
- Optional name_en/ur, address fields, city_key → city_en/ur
- Optional opening_balance (blank → 0) and credit_limit
- Optional profile photo
- Offline create from admin; online customers only restricted admin update
- AI fills bilingual name/address text only — never phone/balance/email/password
- Permissions: contacts.create / contacts.edit
- Services: CustomerService + CustomerEnrichmentService
- formKey: customers
PROMPT,
            'notes' => [],
        ],
    ],

    'suppliers' => [
        'list' => [
            'title' => 'Suppliers — List',
            'overview' => [
                'This page lists supplier master records used by Purchasing and Supplier Ledger. Open Contacts → Suppliers.',
                'Toolbar: search, Filter (status + city), Columns, Add Supplier. Add / View / Edit use the shared form panel. Delete needs contacts.delete.',
                'Search covers supplier code, business name (EN/UR), city, and email.',
            ],
            'prompt' => <<<'PROMPT'
Build Agricart Contacts → Suppliers list page to match the live system.

UI:
- Paginated list (15/page, newest first)
- Toolbar: search, Filter (status + city), Columns, Add Supplier
- Row actions: View / Edit / Delete
- Opens shared form panel (formKey: suppliers)

Behaviour:
- Search: supplier_code, business_name_en/ur, city_en/ur, email
- Permissions: contacts.view / create / edit / delete
- Livewire: ListSuppliers
- Events: suppliers-search, suppliers-filter, suppliers-clear-filters, suppliers-column-toggle, suppliers-saved
PROMPT,
            'notes' => [],
        ],
        'form' => [
            'title' => 'Suppliers — Add / Edit form',
            'overview' => [
                'This form builds the supplier master card before purchasing. It opens in the shared right-side form panel from Contacts → Suppliers.',
                'Required: bilingual business name (EN or UR), bilingual address (EN or UR), country (default Pakistan), city (list for PK, manual otherwise), and first contact (name EN or UR + mobile).',
                'Code is auto SUP-#####. Optional: type, status, categories, bank accounts, credit limit, opening balance, portal (email/password required on create when enabled), profile/card images.',
                'AI fills bilingual business name, address, city, and contact names only — never phones, bank numbers, balances, categories, email, or passwords. Footer: AI → Save & Add Next → Save & Close → Cancel.',
            ],
            'prompt' => <<<'PROMPT'
Build Agricart ERP Contacts → Suppliers Add/Edit Supplier form to match the CURRENT live system. Do not invent improvements.

UI Flow:
1. /admin/contacts/suppliers → Add Supplier → shared form panel
2. Modes: create | edit | view (view = read-only)
3. Footer: AI → Save & Add Next → Save & Close → Cancel

Rules:
1) supplier_code auto SUP-#####
2) supplier_type optional max 64
3) status active|inactive; create default active
4) business_name_en/ur — required_without each other; max 255
5) country_code required default PK; country_ur sync
6) PK → city_key required; non-PK → city_manual required
7) address_en/ur — required_without; max 2000
8) contactRows.0: name EN or UR + mobile required; extra rows optional
9) category_ids optional → category_supplier sync
10) bankRows optional
11) credit_limit / opening_balance nullable numeric; blank opening = 0
12) portal create → unique email + password min 8 + confirmation
13) images optional max 5MB
14) AI: bilingual business/address/city/contact names only; never mobile/bank/balances/categories/email/password/portal

Services: SupplierService + SupplierEnrichmentService
Permissions: contacts.create / contacts.edit
formKey: suppliers
PROMPT,
            'notes' => [],
        ],
    ],

    'transport-companies' => [
        'list' => [
            'title' => 'Carriers — List',
            'overview' => [
                'This page lists transport and goods companies (courier, goods company/adda, freight) used as contact masters — not suppliers or customers.',
                'Open Contacts → Carriers. Use search, filters, columns, and Add. Add / View / Edit open the shared form panel. Delete needs contacts.delete.',
            ],
            'prompt' => <<<'PROMPT'
Build Agricart Contacts → Carriers list page to match the live system.

UI:
- Paginated list under /admin/contacts/transport-companies
- Toolbar: search, filter, columns, Add Carriers
- Row actions: View / Edit / Delete
- Opens shared form panel (formKey: transport-companies)

Behaviour:
- Search code, business names, cities, phones as implemented
- Permissions: contacts.view / create / edit / delete
- Livewire: ListTransportCompanies
- Refresh on transport-companies-saved (or equivalent) event
PROMPT,
            'notes' => [],
        ],
        'form' => [
            'title' => 'Carriers — Add / Edit form',
            'overview' => [
                'This form stores a transport/goods company master: courier, goods company (adda), or freight. It is separate from Suppliers and Customers.',
                'Meta: auto transport code, type (courier / goods_company / freight), status, optional credit limit and opening balance.',
                'Origin business: bilingual business name and address (EN or UR required), country (default PK), city next to country (PK city list or manual). Branches: same business name; per branch address EN/UR plus one or more contacts (name EN/UR + phone) on a compact one-line layout with small + buttons.',
                'Destination Business Address: mirror of origin (name, address, country, city, destination branches). Also HQ contacts, optional banks, optional images.',
                'AI reuses supplier-style enrichment for bilingual text fields only — never invent phones, banks, or balances. Routes UI is not shown; save routes as empty. Footer matches other contact forms (AI → Save & Add Next → Save & Close → Cancel).',
            ],
            'prompt' => <<<'PROMPT'
Build Agricart Contacts → Carriers Add/Edit form (shared form panel) to match the CURRENT live system.

Context:
- Not a supplier or customer — transport/courier/goods/freight master
- Route: /admin/contacts/transport-companies
- Livewire: App\Livewire\TransportCompanies\Form
- Service: TransportCompanyService
- AI: reuse SupplierEnrichmentService pattern (text only)
- formKey: transport-companies
- Types from config/transport_types.php: courier, goods_company, freight

UI Flow:
1. List → Add opens form panel (create); row View/Edit same panel
2. Modes: create | edit | view (read-only)
3. Footer: AI → Save & Add Next → Save & Close → Cancel

Fields / behaviour:
1) transport_code auto-generated
2) transport_type: courier | goods_company | freight
3) status active|inactive; default active on create
4) Origin: business_name_en/ur (required_without), address_en/ur (required_without), country_code default PK, city next to country (city_key for PK, manual otherwise)
5) Origin branches: JSON rows — address_en/ur + contacts[{contact_name_en/ur, mobile_number}]; compact one-line UI; small + to add person; small + to add branch
6) Destination Business Address section: destination_business_name_en/ur, destination_address_en/ur, destination country/city, destinationBranchRows (same shape as origin branches)
7) HQ contactRows: name + mobile pattern like suppliers
8) Optional bankRows, credit_limit, opening_balance, profile/card images
9) Do NOT show city-to-city routes UI; persist routes as []
10) AI fills bilingual names/addresses/cities/contact names only — never phones, banks, balances
11) Permissions: contacts.create / contacts.edit

Acceptance: can save origin + destination with branches; list refreshes after save; view mode read-only.
PROMPT,
            'notes' => [],
        ],
    ],
];
