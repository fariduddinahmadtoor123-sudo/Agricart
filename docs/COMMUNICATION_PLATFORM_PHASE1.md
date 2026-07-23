# Agricart Communication Platform — Phase 1

Phase 1 delivers the **generic Communication Core** only: schema, registry, services, permissions, ports, and tests.  
No product chat UI and no migration of Staff Inbox / Agricart Inbox.

## Goals

- One extensible engine for all future channels (customer, staff, supplier, brand, notes, system, task, …).
- Add a new channel by **config/DB definition**, not by rewriting core services.
- Multi-record linking via `comm_conversation_links` (registry keys, not hard FKs).
- Polymorphic participants via actor type keys (`user`, `customer`, `supplier`, `system`, …).
- Future-ready ports: notifier, presence, AI, calls (Null adapters today).
- Existing Staff Inbox and Agricart Inbox remain unchanged.

## Tables (`comm_*`)

| Table | Purpose |
|-------|---------|
| `comm_channels` | Channel registry + policy flags |
| `comm_conversations` | Threads |
| `comm_participants` | Actor membership + read cursors |
| `comm_messages` | Text / system / attachment messages |
| `comm_attachments` | Stored files |
| `comm_conversation_links` | Many morph links to ERP records |
| `comm_task_links` | Task bridge stub |
| `comm_delivery_outbox` | Empty delivery queue for later adapters |

## Config

- `config/communication.php` — actor types, linkable types, MIME groups, default channel seeds, attachment limits.
- Env (optional):
  - `COMMUNICATION_ENGINE_ENABLED=true`
  - `COMMUNICATION_ATTACHMENT_DISK=local`
  - `COMMUNICATION_MAX_ATTACHMENT_BYTES=10485760`

## How to add a new channel (no core rewrite)

1. Insert/upsert a row in `comm_channels` **or** add under `communication.default_channels` and run:

```bash
php artisan db:seed --class=CommunicationChannelSeeder
```

2. Optionally add a permission key in `PermissionRegistry` (group `comms`) and re-seed roles.
3. Use `ConversationService::create('your_channel_key', ...)`.

## How to add a new linkable module

1. Register in `config/communication.php` → `linkable_types`.
2. Map staff ACL in `linkable_permissions` (e.g. `'purchase' => 'purchasing.view'`).
3. Call `LinkService::attach($conversation, $actor, 'purchase', $id)`.

## How to add a new actor type

1. Register in `config/communication.php` → `actor_types`.
2. Allow that type on relevant channels (`allowed_actor_types`).
3. Use `new ActorRef('dealer', $id)` (or add a helper on `ActorRef`).

## Services

| Class | Role |
|-------|------|
| `ChannelRegistry` | Resolve/sync channels; actor/linkable/MIME maps |
| `ConversationService` | Create / close / reopen / archive |
| `ParticipantService` | Add / leave / roles |
| `MessageService` | Text, system (idempotent `event_key`), reply, edit, soft delete |
| `AttachmentService` | MIME policy + storage |
| `LinkService` | Attach/detach + reverse lookup by record |
| `ReadStateService` | Mark read + unread counts (`last_read_message_id`) |
| `TaskBridgeService` | Create/complete task stubs |
| `CommunicationAuthorizer` | Channel / conversation / participant / record gates |

## Ports (Phase 1 = Null)

- `NotifierPort` → `NullNotifier`
- `PresencePort` → `NullPresence`
- `AiAssistPort` → `NullAiAssist`
- `CallSessionPort` → `NullCallSession`

Bind replacements later without changing message write paths.

## Permissions (`comms.*`)

Seeded via `PermissionRegistry` group `comms`. Super Admin receives all keys on `RolesAndPermissionsSeeder`.

## Seed

```bash
php artisan migrate
php artisan db:seed --class=CommunicationChannelSeeder
php artisan db:seed --class=RolesAndPermissionsSeeder
```

## Tests

```bash
php artisan test --filter=CommunicationPlatformPhase1Test
```

## Staff chat MVP status (complete enough)

Usable staff chat is ready for daily work:

- Floating Staff Chat (admin corner) — DM teammates, online dots, unread badge
- Bridges still dual-write Staff Inbox / Agricart Inbox
- Supplier / Brand / Internal Notes / Tasks channel UIs remain available on records

Still later (optional): delivery adapters, topbar push for unread, realtime websockets.

## Simple Notes (Dashboard) — implemented

A separate **simple notes / reminders** tool (not the Communication Task channel):

| Piece | Detail |
|-------|--------|
| Menu | **Dashboard → Notes** |
| Table | `staff_notes` |
| Actions | Create, edit, forward/assign, mark done, reopen, delete (creator) |
| Example | “Pay electricity bill on 25th” → assign Mahoor → they mark done |

```bash
php artisan migrate
php artisan test --filter=StaffNotesTest
```

## Phase 7 — Simple staff messenger + UX cleanup (implemented)

Phase 7 focuses on **easy daily use**, not more complexity:

| Piece | Detail |
|-------|--------|
| Floating Staff Chat | Bottom-right FAB on every admin page (WhatsApp-style) |
| Service | `DirectStaffMessengerService` on `staff_ops` DMs |
| Online hint | Green dot from active `sessions` (last 5 minutes) |
| Forwarded fix | Restored **Mark released** + **Open packing** on Sales → Forwarded |
| Inbox UX | Print invoice/packing buttons moved to thread header (always visible) |
| Record panels | Tabs (Messages / Notes / Tasks) instead of stacking everything |

### Phase 7 tests

```bash
php artisan test --filter=StaffMessengerPhase7Test
php artisan test --filter=ForwardedOrdersPipelineTest
```

## Phase 6 — Supplier + Brand channels (implemented)

Phase 6 adds **config-driven record threads** for Supplier and Brand channels, with minimal staff UI on the existing forms. Delivery adapters remain Null.

| Piece | Detail |
|-------|--------|
| Config | `communication.record_threads` profiles (`supplier`, `brand`) |
| Service | `App\Services\Communication\RecordChannelThreadService` |
| UI | `RecordChannelThreadPanel` via `thread-profile` on `x-communication.record-panels` |
| Surfaces | Supplier form (Contacts) + Brand form (Catalog) |
| Supplier extras | Supplier actor added as participant on the thread |
| Brand | Staff-only thread linked to the brand record |
| Access | Channel permission **or** fallbacks; supplier link ACL accepts `purchasing.view` **or** `contacts.view` |

### Phase 6 tests

```bash
php artisan test --filter=SupplierBrandThreadPhase6Test
```

## Phase 5 — Task Communication + deeper Internal Notes (implemented)

Phase 5 adds **Task Communication** UI on the `task` channel and embeds **Internal Notes + Tasks** on more record surfaces.

| Piece | Detail |
|-------|--------|
| Service | `App\Services\Communication\TaskCommunicationService` |
| Channel | `task` (create / message / complete / reopen) |
| Record UI | `TaskCommunicationPanel` + shared `x-communication.record-panels` |
| Inbox UI | Sales → **Tasks** (`ListTaskInbox`) |
| Notes surfaces | Product form, Online order form, Product review detail, Staff Inbox order context |
| Access | `comms.channel.task.access` **or** module fallbacks (sales/catalog/store/…) |

### Phase 5 tests

```bash
php artisan test --filter=TaskCommunicationPhase5Test
```

## Phase 4 — Internal Notes + richer attachments (implemented)

Phase 4 adds **staff-only Internal Notes** on linked ERP records (via `internal_notes` channel) and richer file attachment UX on Staff Inbox replies.

| Piece | Detail |
|-------|--------|
| Service | `App\Services\Communication\InternalNotesService` |
| Channel | `internal_notes` (`type=notes`, visibility `record_acl`) |
| UI | `App\Livewire\Communication\InternalNotesPanel` — embedded on Staff Inbox when an order context is open |
| Attachments | Optional files on notes + Staff Inbox replies → `comm_attachments` |
| Download | `admin.communication.attachments.show` (auth + conversation ACL) |
| Access | `comms.channel.internal_notes.access` **or** module fallbacks (sales/catalog/store/…) |

### Phase 4 tests

```bash
php artisan test --filter=InternalNotesPhase4Test
```

## Phase 3 — Customer Support bridge (implemented)

Phase 3 dual-writes **Agricart Customer Inbox** into the Communication Platform `customer_support` channel while keeping legacy `agricart_*` tables as the operational UI source.

| Piece | Detail |
|-------|--------|
| Bridge | `App\Services\Communication\CustomerSupportBridge` |
| Dual-write | Every `AgricartInboxService` message (contact, reply, system notices) |
| Read/status sync | Customer/admin mark-read + close/reopen |
| Mapping | `meta.legacy_agricart_conversation_id` + `event_key=legacy.agricart_message.{id}` |
| Links | customer, online_sale_order, product_review, product |
| Access | `comms.channel.customer_support.access` **or** fallback `store.view` / `store.edit` |
| Guests | Mirrored with `system` participant + guest fields in conversation meta |

### Phase 3 tests

```bash
php artisan test --filter=CustomerSupportBridgePhase3Test
```

## Phase 2 — Staff Ops bridge (implemented)

Phase 2 dual-writes **Staff Inbox / Order Forward** into the Communication Platform `staff_ops` channel while keeping legacy tables as the operational UI source.

| Piece | Detail |
|-------|--------|
| Bridge | `App\Services\Communication\StaffOpsBridge` |
| Dual-write | `StaffMessagingService::postMessage` / `markThreadRead` |
| UI | Sales Inbox shows message history + reply; invoice/packing unchanged |
| Unread / topbar | Still from legacy Staff Inbox counts |
| Mapping | `comm_conversations.meta.legacy_staff_thread_id` + `event_key=legacy.staff_message.{id}` |
| Order link | `comm_conversation_links` → `online_sale_order` |
| Access | `comms.channel.staff_ops.access` **or** fallback `sales.view` |

### Phase 2 tests

```bash
php artisan test --filter=StaffOpsBridgePhase2Test
```

### Not yet (later phases)

- Cut over UI to read only from `comm_*`
- Drop legacy `staff_message_*` / `agricart_*` inbox tables
- Delivery adapters (email/SMS/WhatsApp/Push), AI assist, realtime websockets
- Supplier / Brand external portals posting into these threads

## Explicitly out of Phase 1

- Staff / Customer chat UI
- Migrating old inbox data
- Voice, reactions, pin, typing, presence UI
- Email / SMS / WhatsApp / Push workers
- AI replies / calls

## Next phase (requires approval)

**Phase 8 proposal:** Delivery outbox + first notification adapter (in-app/email) for Staff Chat + Inbox unread, without cluttering the simple float UI.
