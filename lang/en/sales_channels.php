<?php

return [
    'defaults' => [
        'offline_message' => 'Wait for some time',
        'offline_message_ur' => 'تھوڑی دیر انتظار کریں',
        'banner_off_en' => 'Online ordering is temporarily unavailable. You can still browse our catalog.',
        'banner_off_ur' => 'آن لائن آرڈرنگ عارضی بند ہے۔ آپ مصنوعات دیکھ سکتے ہیں۔',
        'maintenance_message' => 'We are performing scheduled maintenance. Please check back soon.',
        'maintenance_message_ur' => 'سائٹ کی دیکھ بھال جاری ہے۔ تھوڑی دیر بعد دوبارہ آئیں۔',
    ],

    'status' => [
        'on' => 'ON',
        'off' => 'OFF',
        'badge_on' => 'Store ON',
        'badge_off' => 'Store OFF',
    ],

    'actions' => [
        'add' => 'Add sales channel',
        'toggle_store' => 'Turn channel on or off',
        'toggle_maintenance' => 'Turn maintenance mode on or off',
        'open_store' => 'Open store',
        'edit_messages' => 'Channel messages',
        'edit_messages_short' => 'Messages',
        'save' => 'Save',
    ],

    'list' => [
        'col_name' => 'Channel',
        'col_status' => 'Store status',
        'col_maintenance' => 'Maintenance',
        'col_actions' => 'Actions',
        'empty' => 'No sales channels yet.',
    ],

    'filters' => [
        'on' => 'On',
        'off' => 'Off',
    ],

    'fields' => [
        'channel' => 'Sales channel',
        'messages' => 'Messages when store is off',
        'messages_off_section' => 'Store OFF — browse only (no prices)',
        'offline_message_en' => 'Product line message (English)',
        'offline_message_ur' => 'Product line message (Urdu)',
        'banner_message_en' => 'Site banner (English)',
        'banner_message_ur' => 'Site banner (Urdu)',
        'maintenance_messages' => 'Maintenance mode — whole site closed',
        'maintenance_message_en' => 'Maintenance message (English)',
        'maintenance_message_ur' => 'Maintenance message (Urdu)',
    ],

    'maintenance' => [
        'page_title' => 'Under maintenance',
        'badge' => 'MAINTENANCE',
        'badge_on' => 'Maintenance ON',
        'badge_off' => 'Site open',
        'switch_on' => 'ON',
        'switch_off' => 'OFF',
    ],

    'messages' => [
        'saved' => 'Sales channel settings saved.',
        'store_enabled' => 'Online store is live. Customers can order again.',
        'store_disabled' => 'Online store is offline. Prices and checkout are hidden.',
        'facebook_enabled' => 'Facebook catalog feed is ON. Meta can fetch your product feed URL.',
        'facebook_disabled' => 'Facebook catalog feed is OFF. The feed URL returns not found.',
        'maintenance_enabled' => 'Maintenance mode is on. The public storefront link is closed.',
        'maintenance_disabled' => 'Maintenance mode is off. Visitors can open the store again.',
    ],

    'online_store' => [
        'title' => 'Online store (own website)',
        'lead' => 'One switch controls checkout, cart, and prices on your Agricart storefront.',
        'lead_short' => 'Use the switch or buttons below — changes apply to your public storefront immediately after save.',
        'behavior_hint' => 'When off: visitors can still browse categories and products; prices are hidden and each product shows your offline message. Cart and checkout are blocked. Admin sales and dispatch are not affected.',
    ],

    'facebook' => [
        'section_title' => 'Facebook / Meta Catalog',
        'edit_title' => 'Facebook catalog setup',
        'setup_short' => 'Setup',
        'open_page' => 'Open Facebook page',
        'lead' => 'Industry standard: Agricart publishes a product feed. You paste that feed URL into Meta Commerce Manager. Customers who click a product open your Agricart website to buy — checkout stays on your store.',
        'page_url' => 'Facebook Page URL (optional note)',
        'page_url_hint' => 'For your reference only. Pasting this URL alone does not sync products — Meta must pull the feed URL below.',
        'price_field' => 'Price to send',
        'price_wholesale' => 'Wholesale (same as online store entry)',
        'price_retail' => 'Retail',
        'feed_url' => 'Catalog feed URL (copy into Meta)',
        'feed_url_hint' => 'In Meta Commerce Manager → Catalog → Data sources → Add data source → Scheduled feed → paste this URL. Keep the token secret.',
        'feed_token' => 'Feed security token',
        'regenerate_token' => 'New token',
        'token_regenerated' => 'New feed token generated. Save, then update the URL in Meta.',
        'setup_title' => 'How to sync (once)',
        'step_1' => 'Turn this channel ON in the Sales Channels list.',
        'step_2' => 'Save this form so your feed URL is ready.',
        'step_3' => 'Open Meta Business Suite → Commerce Manager → create or open a Catalog.',
        'step_4' => 'Add a scheduled feed data source and paste the Catalog feed URL above (hourly or daily).',
        'step_5' => 'Connect the catalog to your Facebook Page with website checkout / shop links pointing to agricart.pk product pages.',
        'eligibility_hint' => 'Only active products with an image and a price are included. Click opens your store product page. Stock is shown as in/out only — Facebook does not control your inventory.',
    ],

    'control' => [
        'what_happens' => 'What happens when the store is off?',
        'saved_state' => 'Saved on server',
        'preview_hint' => 'After turning OFF, open the storefront and refresh — you should see the blue banner, no prices, and no cart.',
    ],

    'hints' => [
        'offline_message' => 'Shown under each product name instead of the price.',
        'banner_message' => 'Shown at the top of every storefront page while the store is off.',
        'maintenance_message' => 'Shown on the full-page screen when maintenance closes the storefront. English visitors see EN; Urdu visitors see UR. Leave blank to use the default text.',
        'no_toggle_permission' => 'You can view settings but cannot change the on/off switch. Ask an admin for Channels Hub edit permission.',
        'no_edit_permission' => 'You cannot edit sales channel messages. Ask an admin for Channels Hub edit permission.',
    ],

    'placeholders' => [
        'offline_message' => 'Wait for some time',
        'maintenance_message' => 'We are performing scheduled maintenance. Please check back soon.',
        'maintenance_message_ur' => 'سائٹ کی دیکھ بھال جاری ہے۔ تھوڑی دیر بعد دوبارہ آئیں۔',
    ],

    'future' => [
        'title' => 'More channels (later)',
        'lead' => 'Daraz, Alibaba, and other marketplaces will appear here with their own on/off and sync status.',
        'coming_soon' => 'Coming later',
    ],
];
