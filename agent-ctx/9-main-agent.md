# Task 9: Google Maps Location Picker + Admin-Configurable Custom Address Fields

## Summary
Implemented Google Maps location picker and admin-configurable custom address fields for the checkout billing/shipping page.

## Changes Made

### 1. Database Migration (`database/migrations/migrate.php`)
- Added `ALTER TABLE` statements for `shipping_latitude` TEXT and `shipping_longitude` TEXT columns after `customer_address` in the `orders` table.

### 2. Admin Settings View (`resources/views/admin/settings.php`)
- Added "Address & Map Settings" section before Notifications section.
- Google Maps toggle (`google_maps_enabled`) with toggle switch UI.
- Google Maps API Key input (`google_maps_api_key`) with password toggle.
- 6 custom address field toggles (all default disabled):
  - `address_field_receiver_name` - Receiver Name
  - `address_field_apartment` - Apartment/Building Name
  - `address_field_street` - Street Name
  - `address_field_house_no` - House/Unit Number
  - `address_field_landmark` - Landmark/Nearby Place
  - `address_field_delivery_instructions` - Additional Delivery Instructions

### 3. Admin Settings Controller (`app/Controllers/AdminSettingsController.php`)
- Added saving for `google_maps_enabled`, `google_maps_api_key`, and all 6 address field toggles.
- Group name: `address`.

### 4. Checkout View (`resources/views/customer/checkout.php`)
- **Shipping Step**: Loads address field settings from DB at top.
- **Google Maps Picker**: Map container (h-48), search input with Places autocomplete, click-to-place marker, auto-fill address fields on place selection, hidden lat/lng inputs. Only shown when enabled and API key is set.
- **Custom Address Fields**: Each field shown conditionally based on admin settings, with Lucide icons and pre-filled from session.
- **Delivery Address**: Changed from `<input>` to `<textarea>` (auto-populated by Google Maps).
- **Review Step**: Shows all non-empty custom fields (receiver name, apartment, street, house/unit, landmark, delivery instructions, coordinates).
- **Google Maps JS**: Full implementation with autocomplete, reverse geocoding, marker dragging, and address component extraction.

### 5. Checkout Controller (`app/Controllers/CheckoutPageController.php`)
- **`buildAddressString()`**: New helper method that builds a comprehensive address string from all shipping fields.
- **`storeShipping()`**: Now saves all 8 new fields to session.
- **`storeOrder()`**: Uses `buildAddressString()` for `customer_address`, saves `shipping_latitude`/`shipping_longitude`, appends delivery instructions to notes.
- **`createOrderFromCart()`**: Same address and lat/lng handling as `storeOrder()`.

### 6. Admin Order Detail View (`resources/views/admin/order-detail.php`)
- Address now rendered with `whitespace-pre-line` for multi-line display.
- Added clickable Google Maps link with lat/lng coordinates if available.